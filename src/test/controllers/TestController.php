<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sprig\test\mockclasses\controllers;

use Craft;
use craft\models\Section;
use craft\web\Controller;
use craft\web\UrlManager;
use putyourlightson\sprig\test\mockclasses\models\TestModel;
use yii\base\Model;
use yii\web\Response;

/**
 * @author    PutYourLightsOn
 * @package   Sprig
 * @since     1.0.0
 */

class TestController extends Controller
{
    /**
     * @inheritdoc
     */
    protected int|bool|array $allowAnonymous = true;

    /**
     * @return null
     */
    public function actionGetNull()
    {
        return null;
    }

    /**
     * Mocks an array response.
     */
    public function actionGetArray(): Response
    {
        return $this->asJson(['success' => true]);
    }

    /**
     * Mocks a Model response.
     */
    public function actionGetModel(): Response
    {
        return $this->asJson(new TestModel(['success' => true]));
    }

    /**
     * Mocks a save success response.
     */
    public function actionSaveSuccess(): Response
    {
        Craft::$app->getSession()->setNotice('Success');

        return $this->redirectToPostedUrl(new Section(['id' => 1]));
    }

    /**
     * Mocks a save error response.
     */
    public function actionSaveError()
    {
        Craft::$app->getSession()->setError('the_error_message');

        /** @var UrlManager $urlManager */
        $urlManager = Craft::$app->getUrlManager();
        $urlManager->setRouteParams(['model' => new Model()]);

        return null;
    }
}
