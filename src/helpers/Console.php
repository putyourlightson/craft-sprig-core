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
    private static bool $initialized = false;

    /**
     * Initialises the Sprig object in the console.
     */
    public static function init(): void
    {
        if (self::$initialized || Component::getIsRequest()) {
            return;
        }

        self::$initialized = true;

        Component::registerJs('Sprig = {components: []}');
    }

    /**
     * Adds a component to the Sprig object in the console.
     */
    public static function addComponent(array $config): void
    {
        if (Craft::$app->getConfig()->getGeneral()->devMode === false) {
            return;
        }

        self::init();

        $value = Json::encode($config);

        Component::registerJs('Sprig.components.push(' . $value . ')');
    }
}
