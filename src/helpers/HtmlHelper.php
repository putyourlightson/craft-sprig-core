<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sprig\helpers;

use Yii;
use yii\base\InvalidArgumentException;

/**
 * This class provides methods that Craft’s Html helper normally provides,
 * fixing issues that were addressed in Craft 3.7 and above, while remaining
 * compatible with PHP 7.0 (and hence not extending the class).
 * https://github.com/putyourlightson/craft-sprig/issues/178
 *
 * TODO: remove in place of craft\helpers\HTML in version 2.0.0
 */
class HtmlHelper
{
    /**
     * Parses an HTML tag to find its attributes.
     *
     * @see Html::parseTagAttributes()
     */
    public static function parseTagAttributes(string $tag, int $offset = 0, int &$start = null, int &$end = null, bool $decode = false): array
    {
        list($type, $tagStart) = self::_findTag($tag, $offset);
        $start = $tagStart + strlen($type) + 1;
        $anchor = $start;
        $attributes = [];

        do {
            $attribute = static::parseTagAttribute($tag, $anchor, $attrStart, $attrEnd);

            // Did we just reach the end of the tag?
            if ($attribute === null) {
                $end = $anchor;
                break;
            }

            list($name, $value) = $attribute;
            $attributes[$name] = $value;
            $anchor = $attrEnd;
        } while (true);

        $attributes = Html::normalizeTagAttributes($attributes);

        if ($decode) {
            foreach ($attributes as &$value) {
                if (is_string($value)) {
                    $value = Html::decode($value);
                }
            }
        }

        return $attributes;
    }

    /**
     * Parses the next HTML tag attribute in a given string.
     *
     * @see Html::parseTagAttribute()
     */
    public static function parseTagAttribute(string $html, int $offset = 0, int &$start = null, int &$end = null)
    {
        if (!preg_match('/\s*([^=\/>\s]+)/A', $html, $match, PREG_OFFSET_CAPTURE, $offset)) {
            if (!preg_match('/(\s*)\/?>/A', $html, $m, 0, $offset)) {
                // No `>`
                throw new InvalidArgumentException("Malformed HTML tag attribute in string: $html");
            }

            // No more attributes here
            return null;
        }

        $value = true;

        // Does the tag have an explicit value?
        $offset += strlen($match[0][0]);

        if (preg_match('/\s*=\s*/A', $html, $m, 0, $offset)) {
            $offset += strlen($m[0]);

            // Wrapped in quotes?
            if (isset($html[$offset]) && in_array($html[$offset], ['\'', '"'])) {
                $q = preg_quote($html[$offset], '/');
                if (!preg_match("/$q(.*?)$q/A", $html, $m, 0, $offset)) {
                    // No matching end quote
                    throw new InvalidArgumentException("Malformed HTML tag attribute in string: $html");
                }

                $offset += strlen($m[0]);
                if (isset($m[1]) && $m[1] !== '') {
                    $value = $m[1];
                }
            } elseif (preg_match('/[^\s>]+/A', $html, $m, 0, $offset)) {
                $offset += strlen($m[0]);
                $value = $m[0];
            }
        }

        $start = $match[1][1];
        $end = $offset;

        return [$match[1][0], $value];
    }

    /**
     * Encodes special characters into HTML entities without double encoding by default.
     * Using this helps prevent double encoding in nested Sprig components.
     * https://github.com/putyourlightson/craft-sprig/issues/178#issuecomment-948505292
     */
    public static function encode($content, $doubleEncode = false): string
    {
        return htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, Yii::$app ? Yii::$app->charset : 'UTF-8', $doubleEncode);
    }

    private static function _findTag(string $html, int $offset = 0): array
    {
        // Find the first HTML tag that isn't a DTD or a comment
        if (!preg_match('/<(\/?[\w\-]+)/', $html, $match, PREG_OFFSET_CAPTURE, $offset) || $match[1][0][0] === '/') {
            throw new InvalidArgumentException('Could not find an HTML tag in string: ' . $html);
        }

        return [strtolower($match[1][0]), $match[0][1]];
    }
}
