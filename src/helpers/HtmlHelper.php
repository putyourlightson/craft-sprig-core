<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sprig\helpers;

use craft\helpers\Html;
use Yii;

class HtmlHelper extends Html
{
    /**
     * Encodes special characters into HTML entities without double encoding by default.
     * Using this helps prevent double encoding in nested Sprig components.
     */
    public static function encode($content, $doubleEncode = false): string
    {
        return htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, Yii::$app ? Yii::$app->charset : 'UTF-8', $doubleEncode);
    }
}
