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
use putyourlightson\sprig\base\Component;
use putyourlightson\sprig\helpers\Console;
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
        if ($this->request->getIsCpRequest() && !Craft::$app->getUser()->getIdentity()->can('accessCp')) {
            throw new ForbiddenHttpException();
        }

        return parent::beforeAction($action);
    }

    /**
     * Renders a component.
     */
    public function actionRender(): Response
    {
        $config = Sprig::$core->requests->getValidatedConfig();
        Craft::$app->getSites()->setCurrentSite($config->siteId);
        $variables = ArrayHelper::merge($config->variables, Sprig::$core->requests->getVariables());

        $content = '';

        if ($config->component) {
            $componentObject = Sprig::$core->components->createObject($config->component, $variables);

            if ($componentObject) {
                if ($config->action && method_exists($componentObject, $config->action)) {
                    call_user_func([$componentObject, $config->action]);
                }

                $content = $componentObject->render();
            }
        } else {
            if ($config->action) {
                $actionVariables = $this->runActionInternal($config->action);
                $variables = ArrayHelper::merge($variables, $actionVariables);
            }

            $content = Craft::$app->getView()->renderTemplate($config->template, $variables);
        }

        Console::addComponent($config);

        // Remove flashes after rendering, so they don’t appear on subsequent requests.
        Craft::$app->getSession()->removeAllFlashes();

        $content .= Sprig::$core->requests->getRegisteredHtml();

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
     * Runs an action and returns variables from the response.
     *
     * We intentionally treat this as an action request that does not accept JSON, as otherwise the model would be returned as an array by `Controller::asModelFailure()`.
     */
    private function runActionInternal(string $action): array
    {
        if ($action == 'users/set-password') {
            return $this->runActionWithJsonRequest($action);
        }

        if ($action == 'users/save-user') {
            $this->registerSaveCurrentUserEvent();
        }

        $this->addRedirectToRequest();

        $actionResponse = Craft::$app->runAction($action);

        // Extract the variables from the route params which are generally set when there are errors.
        /** @var UrlManager $urlManager */
        $urlManager = Craft::$app->getUrlManager();
        $variables = $urlManager->getRouteParams() ?: [];

        /**
         * Merge and unset any variable called `variables`.
         * https://github.com/putyourlightson/craft-sprig/issues/94#issuecomment-771489394
         *
         * @see UrlRule::parseRequest()
         */
        if (isset($variables['variables'])) {
            $variables = ArrayHelper::merge($variables, $variables['variables']);
            unset($variables['variables']);
        }

        // Override the `currentUser` global variable with a fresh version, in case it was just updated.
        // https://github.com/putyourlightson/craft-sprig/issues/81#issuecomment-758619306
        $variables['currentUser'] = Craft::$app->getUser()->getIdentity();

        $success = $actionResponse !== null;
        $modelId = null;

        if ($success) {
            $modelId = $this->getModelId();
        }

        $message = ($success ? Craft::$app->getSession()->getSuccess() : Craft::$app->getSession()->getError()) ?: '';
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

        $variables = [];
        if (!$success) {
            $variables['errors'] = $actionResponse->data;
        }

        $this->setSessionValues($success, $message);

        return $variables;
    }

    /**
     * Adds a redirect to request, so we can extract the ID on success.
     */
    private function addRedirectToRequest(): void
    {
        Craft::$app->getRequest()->setBodyParams(ArrayHelper::merge(
            Craft::$app->getRequest()->getBodyParams(),
            ['redirect' => Craft::$app->getSecurity()->hashData(self::REDIRECT_PREFIX . '{id}')]
        ));
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
     * Registers an event when saving the current user.
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

                // Update the user identity and regenerate the CSRF token, in case the password was changed.
                // https://github.com/putyourlightson/craft-sprig/issues/136
                Craft::$app->getUser()->setIdentity($user);
                Craft::$app->getRequest()->regenCsrfToken();
            }
        );
    }
}
