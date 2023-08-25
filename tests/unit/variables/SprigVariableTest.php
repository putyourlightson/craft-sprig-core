<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sprigcoretests\unit\variables;

use Codeception\Test\Unit;
use Craft;
use putyourlightson\sprig\variables\SprigVariable;
use UnitTester;

/**
 * @author    PutYourLightsOn
 * @package   Sprig
 * @since     1.0.0
 */

class SprigVariableTest extends Unit
{
    protected UnitTester $tester;
    protected SprigVariable $variable;

    protected function _before(): void
    {
        parent::_before();

        $this->variable = new SprigVariable();
    }

    public function testCreateRefreshOnLoadComponent()
    {
        $html = $this->variable->triggerRefreshOnLoad();

        $this->assertStringContainsString('RefreshOnLoad', $html);
    }

    public function testSetConfig()
    {
        $config = ['x' => 1, 'y' => 2];
        $this->variable->setConfig($config);
        $content = str_replace('"', '&quot;', json_encode($config));

        $this->assertEquals(
            '<meta name="htmx-config" content="' . $content . '">',
            Craft::$app->getView()->metaTags['htmx-config'],
        );
    }
}
