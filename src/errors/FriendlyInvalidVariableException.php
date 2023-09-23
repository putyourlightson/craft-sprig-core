<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sprig\errors;

use Craft;
use craft\web\View;
use yii\web\HttpException;

class FriendlyInvalidVariableException extends HttpException
{
    private array $_variables;

    /**
     * @inheritdoc
     */
    public function __construct(array $variables = [])
    {
        $this->_variables = $variables;
        $message = 'Invalid variable `' . $variables['name'] . '` passed into `' . $variables['componentName'] . '`.';

        parent::__construct(400, $message);
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return 'Invalid Variable';
    }

    public function getSolution(): string
    {
        return Craft::$app->getView()->renderTemplate('sprig-core/_error/content', $this->_variables, View::TEMPLATE_MODE_CP);
    }
}
