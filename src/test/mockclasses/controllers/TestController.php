<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sprig\test\mockclasses\controllers;

use Craft;
use craft\models\Section;
use craft\web\Controller;
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
        Craft::$app->getSession()->setNotice('Success');

        return $this->redirectToPostedUrl(new Section(['id' => 1]));
    }

    /**
     * @return null
     */
    public function actionSaveError()
    {
        Craft::$app->getSession()->setError('the_error_message');

        Craft::$app->getUrlManager()->setRouteParams(['model' => new Model()]);

        return null;
    }
}
