<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sprigcore\base;

interface ComponentInterface
{
    /**
     * Returns the rendered component as a string.
     *
     * @return string
     */
    public function render(): string;
}
