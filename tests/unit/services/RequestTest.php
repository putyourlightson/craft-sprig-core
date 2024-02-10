<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sprigcoretests\unit\services;

use craft\test\TestCase;
use putyourlightson\sprig\Sprig;
use UnitTester;

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
        $this->mockRequestMethods([
            'getQueryParams' => [
                'page' => 1,
                '_secret' => 'xyz',
                'sprig:template' => 't',
            ],
        ]);

        $variables = Sprig::$core->requests->getVariables();

        $this->assertEquals(['page' => 1], $variables);
    }

    private function mockRequestMethods(array $methods): void
    {
        $this->tester->mockCraftMethods('request',
            array_merge(['getFullPath' => ['']], $methods)
        );
    }
}
