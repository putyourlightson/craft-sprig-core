<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sprig\services;

use Craft;
use craft\base\Component;
use craft\helpers\Json;
use yii\web\BadRequestHttpException;

/**
 * @property-read array $variables
 */
class RequestsService extends Component
{
    /**
     * @const string[]
     */
    public const DISALLOWED_PREFIXES = ['_', 'sprig:'];

    /**
     * Returns allowed request variables.
     */
    public function getVariables(): array
    {
        $variables = [];

        $request = Craft::$app->getRequest();

        $requestParams = array_merge(
            $request->getQueryParams(),
            $request->getBodyParams()
        );

        foreach ($requestParams as $name => $value) {
            if ($this->_getIsVariableAllowed($name)) {
                $variables[$name] = $value;
            }
        }

        return $variables;
    }

    /**
     * Returns a validated request parameter.
     */
    public function getValidatedParam(string $name): bool|string|null
    {
        $value = Craft::$app->getRequest()->getParam($name);

        if ($value !== null) {
            $value = self::validateData($value);
        }

        return $value;
    }

    /**
     * Returns an array of validated request parameter values.
     *
     * @return string[]
     */
    public function getValidatedParamValues(string $name): array
    {
        $values = [];

        $param = Craft::$app->getRequest()->getParam($name, []);

        foreach ($param as $name => $value) {
            $value = self::validateData($value);
            $value = Json::decodeIfJson($value);
            $values[$name] = $value;
        }

        return $values;
    }

    /**
     * Validates if the given data is tampered with and throws an exception if it is.
     */
    public function validateData(mixed $value): string
    {
        $value = Craft::$app->getSecurity()->validateData($value);

        if ($value === false) {
            throw new BadRequestHttpException('Submitted data was tampered.');
        }

        return $value;
    }

    /**
     * Returns whether a variable name is allowed.
     */
    private function _getIsVariableAllowed(string $name): bool
    {
        if ($name == Craft::$app->getConfig()->getGeneral()->getPageTrigger()) {
            return false;
        }

        foreach (self::DISALLOWED_PREFIXES as $prefix) {
            if (str_starts_with($name, $prefix)) {
                return false;
            }
        }

        return true;
    }
}
