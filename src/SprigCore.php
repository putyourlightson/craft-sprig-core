<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sprigcore;

use Craft;
use craft\events\RegisterTemplateRootsEvent;
use craft\web\twig\variables\CraftVariable;
use craft\web\View;
use putyourlightson\sprigcore\services\ComponentsService;
use putyourlightson\sprigcore\services\RequestService;
use putyourlightson\sprigcore\twigextensions\SprigTwigExtension;
use putyourlightson\sprigcore\variables\SprigVariable;
use yii\base\Event;
use yii\base\Module;

/**
 * @property ComponentsService $components
 * @property RequestService $request
 */
class SprigCore extends Module
{
    /**
     * @var SprigVariable
     */
    public static $sprigVariable;

    public static function bootstrap()
    {
        static::getInstance();
    }

    /**
     * @return SprigCore
     */
    public static function getInstance(): Module
    {
        $id = 'sprig-core';

        if ($module = Craft::$app->getModule($id)) {
            return $module;
        }

        $module = new SprigCore($id);
        static::setInstance($module);
        Craft::$app->setModule($id, $module);

        return $module::getInstance();
    }

    public function init()
    {
        parent::init();

        self::$sprigVariable = new SprigVariable();

        $this->setComponents([
            'components' => ComponentsService::class,
            'request' => RequestService::class,
        ]);

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
