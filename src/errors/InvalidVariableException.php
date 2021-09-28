<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sprigcore\errors;

use Craft;
use yii\base\Exception;

class InvalidVariableException extends Exception
{
    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return 'Invalid variable';
    }
}
