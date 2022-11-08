<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sprig;

use Craft;
use craft\events\RegisterTemplateRootsEvent;
use craft\log\MonologTarget;
use craft\web\twig\variables\CraftVariable;
use craft\web\View;
use Monolog\Formatter\LineFormatter;
use Psr\Log\LogLevel;
use putyourlightson\sprig\services\ComponentsService;
use putyourlightson\sprig\services\RequestsService;
use putyourlightson\sprig\twigextensions\SprigTwigExtension;
use putyourlightson\sprig\variables\SprigVariable;
use yii\base\Event;
use yii\base\Module;

/**
 * @property-read ComponentsService $components
 * @property-read RequestsService $requests
 */
class Sprig extends Module
{
    /**
     * The unique ID of this module.
     */
    public const ID = 'sprig-core';

    /**
     * @var Sprig
     */
    public static Sprig $core;

    /**
     * @var SprigVariable
     */
    public static SprigVariable $sprigVariable;

    /**
     * The bootstrap process creates an instance of the module.
     */
    public static function bootstrap(): void
    {
        static::getInstance();
    }

    /**
     * @inheritdoc
     */
    public static function getInstance(): Sprig
    {
        if ($module = Craft::$app->getModule(self::ID)) {
            /** @var Sprig $module */
            return $module;
        }

        $module = new Sprig(self::ID);
        static::setInstance($module);
        Craft::$app->setModule(self::ID, $module);
        Craft::setAlias('@putyourlightson/sprig', __DIR__);

        return $module;
    }

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();
        self::$core = $this;
        self::$sprigVariable = new SprigVariable();

        $this->_registerComponents();
        $this->_registerTemplateRoots();
        $this->_registerTwigExtensions();
        $this->_registerVariables();
        $this->_registerLogTarget();
    }

    /**
     * Registers components.
     */
    private function _registerComponents()
    {
        $this->setComponents([
            'components' => ComponentsService::class,
            'requests' => RequestsService::class,
        ]);
    }

    /**
     * Registers template roots.
     */
    private function _registerTemplateRoots()
    {
        Event::on(
            View::class, View::EVENT_REGISTER_CP_TEMPLATE_ROOTS,
            function(RegisterTemplateRootsEvent $event) {
                $event->roots['sprig-core'] = $this->getBasePath() . '/templates';
            }
        );
    }

    /**
     * Registers Twig extensions.
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

    /**
     * Registers a custom log target, keeping the format as simple as possible.
     *
     * @see LineFormatter::SIMPLE_FORMAT
     */
    private function _registerLogTarget(): void
    {
        // Check that dispatcher exists, to avoid error when testing, since this is a bootstrapped module.
        // https://github.com/verbb/verbb-base/pull/1/files
        /** @phpstan-ignore-next-line */
        if (Craft::getLogger()->dispatcher) {
            Craft::getLogger()->dispatcher->targets[] = new MonologTarget([
                'name' => 'sprig',
                'categories' => ['sprig'],
                'level' => LogLevel::INFO,
                'logContext' => false,
                'allowLineBreaks' => false,
                'formatter' => new LineFormatter(
                    format: "[%datetime%] %message%\n",
                    dateFormat: 'Y-m-d H:i:s',
                ),
            ]);
        }
    }
}
