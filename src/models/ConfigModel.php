<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sprig\models;

use Craft;
use craft\base\Model;
use craft\helpers\Json;

/**
 * @since 3.0.0
 *
 * @property-read string $hashed
 */
class ConfigModel extends Model
{
    public ?int $siteId = null;
    public ?string $component = null;
    public ?string $template = null;
    public ?string $action = null;
    public array $variables = [];

    /**
     * Returns a hashed, JSON-encoded array of non-empty config attributes.
     */
    public function getHashed(): string
    {
        $attributes = array_filter($this->getAttributes());
        $encoded = Json::encode($attributes);

        return Craft::$app->getSecurity()->hashData($encoded);
    }
}
