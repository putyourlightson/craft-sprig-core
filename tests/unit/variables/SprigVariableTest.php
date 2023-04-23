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

    public function testHtmxScriptExistsForDev()
    {
        Craft::$app->getConfig()->env = 'dev';

        $this->_testScriptExistsLocally();
    }

    public function testHtmxScriptExistsForProduction()
    {
        Craft::$app->getConfig()->env = 'production';

        $this->_testScriptExistsLocally();
    }

    private function _testScriptExistsLocally(): void
    {
        $script = $this->variable->getScript([], true);
        preg_match('/src=".*?\/cpresources(.*?)\?v=.*"/', $script, $matches);
        $path = Craft::getAlias(Craft::$app->getConfig()->getGeneral()->resourceBasePath) . $matches[1];

        $this->assertFileExists($path);
    }
}
