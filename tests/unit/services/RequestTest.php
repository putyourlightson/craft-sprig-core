<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sprigcoretests\unit\services;

use Craft;
use craft\test\TestCase;
use putyourlightson\sprig\Sprig;
use UnitTester;
use yii\web\BadRequestHttpException;

/**
 * @author    PutYourLightsOn
 * @package   Sprig
 * @since     1.0.0
 */
class RequestTest extends TestCase
{
    protected UnitTester $tester;

    protected function _before(): void
    {
        parent::_before();

        // Bootstrap the module
        Sprig::bootstrap();
    }

    public function testGetVariables()
    {
        $this->_mockRequestMethods([
            'getQueryParams' => [
                'page' => 1,
                '_secret' => 'xyz',
                'sprig:template' => 't',
            ],
        ]);

        $variables = Sprig::$core->requests->getVariables();

        $this->assertEquals(['page' => 1], $variables);
    }

    public function testGetValidatedParam()
    {
        $this->_mockRequestMethods([
            'getParam' => [
                Craft::$app->getSecurity()->hashData('xyz'),
            ],
        ]);

        $this->tester->mockCraftMethods('request', [
            'getFullPath' => [''],
            'getParam' => Craft::$app->getSecurity()->hashData('xyz'),
        ]);

        $param = Sprig::$core->requests->getValidatedParam('page');

        $this->assertEquals('xyz', $param);
    }

    public function testGetValidatedParamValues()
    {
        $this->_mockRequestMethods([
            'getParam' => [
                Craft::$app->getSecurity()->hashData('abc'),
                Craft::$app->getSecurity()->hashData('xyz'),
            ],
        ]);

        $values = Sprig::$core->requests->getValidatedParamValues('page');

        $this->assertEquals(['abc', 'xyz'], $values);
    }

    public function testGetCacheDuration()
    {
        Craft::$app->getRequest()->getHeaders()->set('Sprig-Cache', 'true');
        $this->assertEquals(Sprig::$core->requests::DEFAULT_CACHE_DURATION, Sprig::$core->requests->getCacheDuration());

        Craft::$app->getRequest()->getHeaders()->set('Sprig-Cache', 10);
        $this->assertEquals(10, Sprig::$core->requests->getCacheDuration());

        Craft::$app->getRequest()->getHeaders()->set('Sprig-Cache', 10.2);
        $this->assertEquals(10, Sprig::$core->requests->getCacheDuration());

        Craft::$app->getRequest()->getHeaders()->set('Sprig-Cache', 'false');
        $this->assertEquals(0, Sprig::$core->requests->getCacheDuration());

        Craft::$app->getRequest()->getHeaders()->set('Sprig-Cache', -10);
        $this->assertEquals(0, Sprig::$core->requests->getCacheDuration());
    }

    public function testValidateData()
    {
        $this->expectException(BadRequestHttpException::class);

        $data = Craft::$app->getSecurity()->hashData('xyz') . 'abc';

        Sprig::$core->requests->validateData($data);
    }

    private function _mockRequestMethods(array $methods): void
    {
        $this->tester->mockCraftMethods('request',
            array_merge(['getFullPath' => ['']], $methods)
        );
    }
}
