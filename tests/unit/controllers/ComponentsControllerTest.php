<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sprigcoretests\unit\controllers;

use Codeception\Test\Unit;
use Craft;
use craft\web\View;
use putyourlightson\sprig\Sprig;
use putyourlightson\sprig\test\mockclasses\controllers\TestController;
use UnitTester;
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
        Craft::$app->getRequest()->setQueryParams([
            'sprig:template' => Craft::$app->security->hashData('_empty'),
        ]);

        /** @var Response $response */
        $response = Sprig::$core->runAction('components/render');

        $this->assertEquals('', trim($response->data));
    }

    public function testRenderNull()
    {
        Craft::$app->getRequest()->setQueryParams([
            'sprig:template' => Craft::$app->security->hashData('_action'),
            'sprig:action' => Craft::$app->security->hashData('sprig-core/test/get-null'),
        ]);

        /** @var Response $response */
        $response = Sprig::$core->runAction('components/render');

        $this->assertStringContainsString('success:false', trim($response->data));
    }

    public function testRenderArray()
    {
        Craft::$app->getRequest()->setQueryParams([
            'sprig:template' => Craft::$app->security->hashData('_action'),
            'sprig:action' => Craft::$app->security->hashData('sprig-core/test/get-array'),
        ]);

        /** @var Response $response */
        $response = Sprig::$core->runAction('components/render');

        $this->assertStringContainsString('success:true', trim($response->data));
    }

    public function testRenderModel()
    {
        Craft::$app->getRequest()->setQueryParams([
            'sprig:template' => Craft::$app->security->hashData('_action'),
            'sprig:action' => Craft::$app->security->hashData('sprig-core/test/get-model'),
        ]);

        /** @var Response $response */
        $response = Sprig::$core->runAction('components/render');

        $this->assertStringContainsString('success:true', trim($response->data));
    }

    public function testControllerActionSuccess()
    {
        Craft::$app->getRequest()->setBodyParams([
            'sprig:template' => Craft::$app->security->hashData('_action'),
            'sprig:action' => Craft::$app->security->hashData('sprig-core/test/save-success'),
        ]);

        /** @var Response $response */
        $response = Sprig::$core->runAction('components/render');

        $this->assertStringContainsString('success:true', trim($response->data));
        $this->assertStringContainsString('id:1', trim($response->data));
        $this->assertStringContainsString('flashes[notice]:Success', trim($response->data));
    }

    public function testControllerActionError()
    {
        Craft::$app->getRequest()->setBodyParams([
            'sprig:template' => Craft::$app->security->hashData('_action'),
            'sprig:action' => Craft::$app->security->hashData('sprig-core/test/save-error'),
        ]);

        /** @var Response $response */
        $response = Sprig::$core->runAction('components/render');

        $this->assertStringContainsString('success:false', trim($response->data));
        $this->assertStringContainsString('flashes[error]:the_error_message', trim($response->data));
        $this->assertStringContainsString('model', trim($response->data));
    }
}
