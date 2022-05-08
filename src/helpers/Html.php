<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sprig\helpers;

use Craft;

/**
 * This class overrides Craft’s Html helper to fix a double encoding issue.
 */
class Html extends \craft\helpers\Html
{
    /**
     * Encodes special characters into HTML entities without double encoding by default.
     * Using this helps prevent double encoding in nested Sprig components.
     * https://github.com/putyourlightson/craft-sprig/issues/178#issuecomment-948505292
     */
    public static function encode($content, $doubleEncode = false): string
    {
        return htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, Craft::$app->charset, $doubleEncode);
    }
}
