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
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var SprigVariable
     */
    protected $variable;

    protected function _before()
    {
        parent::_before();

        $this->variable = new SprigVariable();
    }

    public function testHtmxScriptExistsForDev()
    {
        Craft::$app->getConfig()->env = 'dev';

        $this->_testScriptExistsRemotely($this->variable->getScript());
    }

    public function testHtmxScriptExistsForProduction()
    {
        Craft::$app->getConfig()->env = 'production';

        $this->_testScriptExistsRemotely($this->variable->getScript());
    }

    private function _testScriptExistsRemotely(string $script)
    {
        $client = Craft::createGuzzleClient();

        preg_match('/src="(.*?)"/', $script, $matches);
        $url = $matches[1];

        $statusCode = $client->get($url)->getStatusCode();
        $this->assertEquals(200, $statusCode);
    }
}
