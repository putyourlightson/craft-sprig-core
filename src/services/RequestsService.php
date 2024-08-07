<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sprig\services;

use Craft;
use craft\base\Component;
use craft\helpers\Json;
use putyourlightson\sprig\models\ConfigModel;
use yii\web\BadRequestHttpException;

/**
 * @property-read array $variables
 * @property-read ConfigModel $validatedConfig
 * @property-read int $cacheDuration
 */
class RequestsService extends Component
{
    /**
     * @const int
     */
    public const DEFAULT_CACHE_DURATION = 300;

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
            $request->getBodyParams(),
        );

        foreach ($requestParams as $name => $value) {
            if ($this->getIsVariableAllowed($name)) {
                $variables[$name] = $value;
            }
        }

        return $variables;
    }

    /**
     * Returns a validated config request.
     */
    public function getValidatedConfig(): ConfigModel
    {
        $configParam = Craft::$app->getRequest()->getParam('sprig:config');
        $configParam = $this->validateData($configParam);
        $configValues = Json::decode($configParam);

        $config = new ConfigModel();
        $config->setAttributes($configValues, false);

        foreach ($config->variables as $name => $value) {
            $config->variables[$name] = $value;
        }

        $actionParam = Craft::$app->getRequest()->getParam('sprig:action');
        if ($actionParam) {
            $config->action = Craft::$app->getSecurity()->validateData($actionParam);
        }

        $triggerRefreshSourcesParam = Craft::$app->getRequest()->getParam('sprig:triggerRefreshSources');
        if ($triggerRefreshSourcesParam) {
            $config->triggerRefreshSources = Json::decode(Craft::$app->getSecurity()->validateData($triggerRefreshSourcesParam));
        }

        return $config;
    }

    /**
     * Returns the request’s cache duration or `0` if not set.
     */
    public function getCacheDuration(): int
    {
        $duration = Craft::$app->getRequest()->getHeaders()->get('S-Cache', 0);

        if ($duration === 'true') {
            return self::DEFAULT_CACHE_DURATION;
        }

        if (!is_numeric($duration)) {
            return 0;
        }

        $duration = (int)$duration;

        if ($duration < 0) {
            return 0;
        }

        return $duration;
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
    private function getIsVariableAllowed(string $name): bool
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
