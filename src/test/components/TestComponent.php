<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sprig\test\components;

use putyourlightson\sprig\base\Component;

class TestComponent extends Component
{
    protected ?string $_template = '_component';

    public int $number = 0;
}
