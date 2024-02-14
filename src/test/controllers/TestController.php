<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sprig\test\controllers;

use Craft;
use craft\models\Section;
use craft\web\Controller;
use craft\web\UrlManager;
use putyourlightson\sprig\test\models\TestModel;
use yii\base\Model;
use yii\web\Response;

class TestController extends Controller
{
    protected int|bool|array $allowAnonymous = true;

    public function actionGetNull(): null
    {
        return null;
    }

    public function actionGetArray(): Response
    {
        return $this->asJson(['success' => true]);
    }

    public function actionGetModel(): Response
    {
        return $this->asJson(new TestModel(['success' => true]));
    }

    public function actionSaveSuccess(): Response
    {
        Craft::$app->getSession()->setSuccess('Success');

        return $this->redirectToPostedUrl(new Section(['id' => 1]));
    }

    public function actionSaveError(): null
    {
        Craft::$app->getSession()->setError('Error');

        /** @var UrlManager $urlManager */
        $urlManager = Craft::$app->getUrlManager();
        $urlManager->setRouteParams(['model' => new Model()]);

        return null;
    }
}
