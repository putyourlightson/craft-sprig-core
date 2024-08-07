<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sprig\helpers;

use Craft;
use craft\helpers\Json;
use putyourlightson\sprig\base\Component;
use putyourlightson\sprig\models\ConfigModel;

/**
 * Manages the Sprig object in the console.
 *
 * @since 2.10.0
 */
class Console
{
    /**
     * Whether the Sprig object has been initialised.
     */
    private static bool $initialised = false;

    /**
     * Initialises the Sprig object in the console.
     */
    public static function init(): void
    {
        if (self::$initialised || Component::getIsRequest()) {
            return;
        }

        self::$initialised = true;

        Component::registerJs('Sprig = {components: []}');
    }

    /**
     * Adds a component to the Sprig object in the console.
     */
    public static function addComponent(ConfigModel $config): void
    {
        if (Craft::$app->getConfig()->getGeneral()->devMode === false) {
            return;
        }

        self::init();

        $value = Json::encode($config->getAttributes());

        Component::registerJs('Sprig.components.push(' . $value . ')');
    }
}
