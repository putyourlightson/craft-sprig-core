<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sprig\events;

use yii\base\Event;

class ComponentEvent extends Event
{
    /**
     * @var string
     */
    public string $value;

    /**
     * @var array
     */
    public array $variables;

    /**
     * @var array
     */
    public array $attributes;

    /**
     * @var string|null
     */
    public ?string $output = null;
}
