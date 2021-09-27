<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sprig;

use Craft;
use craft\web\twig\variables\CraftVariable;
use putyourlightson\sprig\controllers\ComponentsController;
use putyourlightson\sprig\services\ComponentsService;
use putyourlightson\sprig\services\RequestService;
use putyourlightson\sprig\twigextensions\SprigTwigExtension;
use putyourlightson\sprig\variables\SprigVariable;
use yii\base\BootstrapInterface;
use yii\base\Event;
use yii\base\Module;

/**
 * @property ComponentsService $components
 * @property RequestService $request
 */
class Sprig extends Module implements BootstrapInterface
{
    /**
     * @var SprigVariable
     */
    public static $sprigVariable;

    /**
     * @inheritdoc
     */
    public function bootstrap($app)
    {
        self::setInstance($this);

        self::$sprigVariable = new SprigVariable();

        $this->setComponents([
            'components' => ComponentsService::class,
            'request' => RequestService::class,
        ]);

        // Register the controller map manually since the module is bootstrapped.
        if (!Craft::$app->request->isConsoleRequest) {
            Craft::$app->controllerMap['sprig'] = self::class;
        }

        $this->_registerTwigExtensions();
        $this->_registerVariables();
    }

    /**
     * Registers Twig extensions
     */
    private function _registerTwigExtensions()
    {
        Craft::$app->view->registerTwigExtension(new SprigTwigExtension());
    }

    /**
     * Registers variables.
     */
    private function _registerVariables()
    {
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT,
            function(Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('sprig', self::$sprigVariable);
            }
        );
    }
}
