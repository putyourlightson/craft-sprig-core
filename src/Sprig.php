<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sprig;

use Craft;
use craft\events\RegisterTemplateRootsEvent;
use craft\web\twig\variables\CraftVariable;
use craft\web\View;
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

        $this->_registerTemplateRoots();
        $this->_registerTwigExtensions();
        $this->_registerVariables();
    }

    /**
     * Registers template roots
     */
    private function _registerTemplateRoots()
    {
        Event::on(
            View::class, View::EVENT_REGISTER_CP_TEMPLATE_ROOTS,
            function(RegisterTemplateRootsEvent $event) {
                // Register as `sprig-core` because `sprig` will be overwritten by the plugin.
                $event->roots['sprig-core'] = $this->getBasePath().'/templates';
            }
        );
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
