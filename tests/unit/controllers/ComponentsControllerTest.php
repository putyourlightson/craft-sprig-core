<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sprigcoretests\unit\controllers;

use Codeception\Test\Unit;
use Craft;
use craft\web\View;
use putyourlightson\sprig\models\ConfigModel;
use putyourlightson\sprig\Sprig;
use putyourlightson\sprig\test\mockclasses\controllers\TestController;
use UnitTester;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * @author    PutYourLightsOn
 * @package   Sprig
 * @since     1.0.0
 */
class ComponentsControllerTest extends Unit
{
    protected UnitTester $tester;

    protected function _before(): void
    {
        parent::_before();

        // Bootstrap the module
        Sprig::bootstrap();

        // Add test controller
        Sprig::$core->controllerMap['test'] = TestController::class;

        Craft::$app->view->setTemplateMode(View::TEMPLATE_MODE_SITE);
        Craft::$app->view->setTemplatesPath(Craft::getAlias('@templates'));
    }

    public function testRender()
    {
        $config = new ConfigModel();
        $config->template = '_empty';
        Craft::$app->getRequest()->setQueryParams([
            'sprig:config' => $config->getHashed(),
        ]);

        /** @var Response $response */
        $response = Sprig::$core->runAction('components/render');

        $this->assertEquals('', trim($response->data));
    }

    public function testRenderNull()
    {
        $config = new ConfigModel();
        $config->template = '_action';
        $config->action = 'sprig-core/test/get-null';

        Craft::$app->getRequest()->setQueryParams([
            'sprig:config' => $config->getHashed(),
        ]);

        /** @var Response $response */
        $response = Sprig::$core->runAction('components/render');

        $this->assertStringContainsString('success:false', trim($response->data));
    }

    public function testRenderArray()
    {
        $config = new ConfigModel();
        $config->template = '_action';
        $config->action = 'sprig-core/test/get-array';
        Craft::$app->getRequest()->setQueryParams([
            'sprig:config' => $config->getHashed(),
        ]);

        /** @var Response $response */
        $response = Sprig::$core->runAction('components/render');

        $this->assertStringContainsString('success:true', trim($response->data));
    }

    public function testRenderModel()
    {
        $config = new ConfigModel();
        $config->template = '_action';
        $config->action = 'sprig-core/test/get-model';
        Craft::$app->getRequest()->setQueryParams([
            'sprig:config' => $config->getHashed(),
        ]);

        /** @var Response $response */
        $response = Sprig::$core->runAction('components/render');

        $this->assertStringContainsString('success:true', trim($response->data));
    }

    public function testControllerActionSuccess()
    {
        $config = new ConfigModel();
        $config->template = '_action';
        $config->action = 'sprig-core/test/save-success';
        Craft::$app->getRequest()->setQueryParams([
            'sprig:config' => $config->getHashed(),
        ]);

        /** @var Response $response */
        $response = Sprig::$core->runAction('components/render');

        $this->assertStringContainsString('success:true', trim($response->data));
        $this->assertStringContainsString('id:1', trim($response->data));
        $this->assertStringContainsString('flashes[notice]:Success', trim($response->data));
    }

    public function testControllerActionError()
    {
        $config = new ConfigModel();
        $config->template = '_action';
        $config->action = 'sprig-core/test/save-error';
        Craft::$app->getRequest()->setQueryParams([
            'sprig:config' => $config->getHashed(),
        ]);

        /** @var Response $response */
        $response = Sprig::$core->runAction('components/render');

        $this->assertStringContainsString('success:false', trim($response->data));
        $this->assertStringContainsString('flashes[error]:the_error_message', trim($response->data));
        $this->assertStringContainsString('model', trim($response->data));
    }

    public function testRenderWithoutParams()
    {
        $this->expectException(BadRequestHttpException::class);
        Craft::$app->getRequest()->setQueryParams([]);
        Sprig::$core->runAction('components/render');
    }
}
