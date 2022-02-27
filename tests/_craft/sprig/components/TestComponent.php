<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace sprig\components;

use putyourlightson\sprig\base\Component;

class TestComponent extends Component
{
    protected ?string $_template = '_component';

    public $number = 0;
}
