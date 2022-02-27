<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sprig\base;

interface ComponentInterface
{
    /**
     * Returns the rendered component as a string.
     */
    public function render(): string;
}
