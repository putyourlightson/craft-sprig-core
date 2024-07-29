<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sprig\controllers;

use Craft;
use craft\base\Element;
use craft\elements\User;
use craft\events\ModelEvent;
use craft\helpers\ArrayHelper;
use craft\web\Controller;
use craft\web\UrlManager;
use craft\web\UrlRule;
use putyourlightson\sprig\Sprig;
use yii\base\Event;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class ComponentsController extends Controller
{
    private const REDIRECT_PREFIX = 'https://';

    /**
     * @inheritdoc
     */
    protected int|bool|array $allowAnonymous = true;

    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        if ($this->request->getIsCpRequest() && Craft::$app->getUser()->getIdentity()->can('accessCp') === false) {
            throw new ForbiddenHttpException();
        }

        return parent::beforeAction($action);
    }

    /**
     * Renders a component.
     */
    public function actionRender(): Response
    {
        $siteId = Sprig::$core->requests->getValidatedParam('sprig:siteId');
        Craft::$app->getSites()->setCurrentSite($siteId);

        $component = Sprig::$core->requests->getValidatedParam('sprig:component');
        $action = Sprig::$core->requests->getValidatedParam('sprig:action');

        $variables = ArrayHelper::merge(
            Sprig::$core->requests->getValidatedParamValues('sprig:variables'),
            Sprig::$core->requests->getVariables()
        );

        $content = '';

        if ($component) {
            $componentObject = Sprig::$core->components->createObject($component, $variables);

            if ($componentObject) {
                if ($action && method_exists($componentObject, $action)) {
                    call_user_func([$componentObject, $action]);
                }

                $content = $componentObject->render();
            }
        } else {
            if ($action) {
                $actionVariables = $this->runActionInternal($action);
                $variables = ArrayHelper::merge($variables, $actionVariables);
            }

            $template = Sprig::$core->requests->getRequiredValidatedParam('sprig:template');
            $content = Craft::$app->getView()->renderTemplate($template, $variables);
        }

        // Append queued HTML to the content.
        $content .= Craft::$app->getView()->getBodyHtml();

        $response = Craft::$app->getResponse();
        $response->statusCode = 200;
        $response->format = Response::FORMAT_HTML;
        $response->data = Sprig::$core->components->parse($content);

        $cacheDuration = Sprig::$core->requests->getCacheDuration();
        if ($cacheDuration > 0) {
            $response->headers->set('Cache-Control', 'private, max-age=' . $cacheDuration);
        }

        return $response;
    }

    /**
     * Runs an action and returns the variables from the response
     */
    private function runActionInternal(string $action): array
    {
        if ($action == 'users/set-password') {
            return $this->runActionWithJsonRequest($action);
        }

        if ($action == 'users/save-user') {
            $this->registerSaveCurrentUserEvent();
        }

        // Add a redirect to the body params, so we can extract the ID on success
        $redirectPrefix = 'https://';
        Craft::$app->getRequest()->setBodyParams(ArrayHelper::merge(
            Craft::$app->getRequest()->getBodyParams(),
            ['redirect' => Craft::$app->getSecurity()->hashData($redirectPrefix . '{id}')]
        ));

        $actionResponse = Craft::$app->runAction($action);

        // Extract the variables from the route params which are generally set when there are errors
        /** @var UrlManager $urlManager */
        $urlManager = Craft::$app->getUrlManager();
        $variables = $urlManager->getRouteParams() ?: [];

        /**
         * Merge and unset any variable called `variables`
         * https://github.com/putyourlightson/craft-sprig/issues/94#issuecomment-771489394
         *
         * @see UrlRule::parseRequest()
         */
        if (isset($variables['variables'])) {
            $variables = ArrayHelper::merge($variables, $variables['variables']);
            unset($variables['variables']);
        }

        // Override the `currentUser` global variable with a fresh version, in case it was just updated
        // https://github.com/putyourlightson/craft-sprig/issues/81#issuecomment-758619306
        $variables['currentUser'] = Craft::$app->getUser()->getIdentity();

        $success = $actionResponse !== null;
        $modelId = null;

        $variables['success'] = $success;

        if ($success) {
            $modelId = $this->getModelId();

            $variables['id'] = $modelId;
        }

        $message = ($success ? Craft::$app->getSession()->getSuccess() : Craft::$app->getSession()->getError()) ?: '';

        // Set flash messages variable and delete them
        $variables['flashes'] = Craft::$app->getSession()->getAllFlashes(true);

        $this->setSessionValues($success, $message, $modelId);

        return $variables;
    }

    /**
     * Runs the action with a JSON request for special case handling.
     * https://github.com/putyourlightson/craft-sprig/issues/300
     */
    private function runActionWithJsonRequest(string $action): array
    {
        Craft::$app->getRequest()->getHeaders()->set('Accept', 'application/json');

        $actionResponse = Craft::$app->runAction($action);
        $success = $actionResponse->getIsOk();
        $message = $actionResponse->data['message'] ?? '';

        $variables = [
            'success' => $success,
            'message' => $message,
        ];

        if (!$actionResponse->getIsOk()) {
            $variables['errors'] = $actionResponse->data;
        }

        $this->setSessionValues($success, $message);

        return $variables;
    }

    /**
     * Returns the model ID resulting from a request.
     */
    private function getModelId(): ?int
    {
        $location = Craft::$app->getResponse()->getHeaders()->get('location', '');
        $modelId = str_replace(self::REDIRECT_PREFIX, '', $location);

        // Remove the redirect header
        Craft::$app->getResponse()->getHeaders()->remove('location');

        if (!is_numeric($modelId)) {
            return null;
        }

        return (int)$modelId;
    }

    /**
     * Sets values for the current session, so we can retrieve them in the same request.
     *
     * @used-by Component::getIsSuccess()
     * @used-by Component::getIsError()
     * @used-by Component::getMessage()
     * @used-by Component::getModelId()
     */
    private function setSessionValues(bool $success, string $message, ?int $modelId = null): void
    {
        $session = Craft::$app->getSession();
        $session->setFlash('sprig:isSuccess', $success);
        $session->setFlash('sprig:isError', $success === false);
        $session->setFlash('sprig:message', $message);
        $session->setFlash('sprig:modelId', $modelId);
    }

    /**
     * Registers an event when saving the current user
     */
    private function registerSaveCurrentUserEvent(): void
    {
        $currentUserId = Craft::$app->getUser()->getId();
        $userId = Craft::$app->getRequest()->getBodyParam('userId');

        if (!$currentUserId || $currentUserId != $userId) {
            return;
        }

        Event::on(User::class, Element::EVENT_AFTER_SAVE,
            function(ModelEvent $event) {
                /** @var User $user */
                $user = $event->sender;

                // Update the user identity and regenerate the CSRF token in case the password was changed
                // https://github.com/putyourlightson/craft-sprig/issues/136
                Craft::$app->getUser()->setIdentity($user);
                Craft::$app->getRequest()->regenCsrfToken();
            }
        );
    }
}
