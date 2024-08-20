<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sprig\services;

use Craft;
use craft\base\Component;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use putyourlightson\sprig\Sprig;
use yii\web\BadRequestHttpException;

/**
 * @property-read array $variables
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
     * The registered HTML code blocks, indexed by swap selectors.
     *
     * @var string[][]
     */
    private array $html = [];

    /**
     * The registered JS code blocks.
     *
     * @var string[]
     */
    private array $js = [];

    /**
     * The components that initiated out-of-band swaps in the current request.
     *
     * @var string[]|null
     */
    private ?array $oobSwapSources = null;

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
            if ($this->getIsVariableAllowed($name)) {
                $variables[$name] = $value;
            }
        }

        return $variables;
    }

    /**
     * Returns a required validated request parameter.
     */
    public function getRequiredValidatedParam(string $name): string|null
    {
        $value = $this->getValidatedParam($name);

        if (empty($value)) {
            throw new BadRequestHttpException('Request missing required param.');
        }

        return $value;
    }

    /**
     * Returns a validated request parameter.
     */
    public function getValidatedParam(string $name): string|null
    {
        $value = Craft::$app->getRequest()->getParam($name);

        if ($value !== null) {
            $value = $this->validateData($value);
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
            $value = $this->validateData($value);
            $value = Json::decodeIfJson($value);
            $values[$name] = $value;
        }

        return $values;
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
     * Returns all registered HTML code blocks.
     *
     * @since 2.11.0
     */
    public function getRegisteredHtml(): string
    {
        $html = [];

        // Append registered JS to the `beforeend:body` HTML block.
        if (!empty($this->js)) {
            // Execute the JS after htmx settles, at most once.
            $js = implode(PHP_EOL, $this->js);
            $content = <<<JS
                document.body.addEventListener('htmx:afterSettle', function() {
                    $js
                }, { once: true });
            JS;
            $this->html['beforeend:body'] = $this->html['beforeend:body'] ?? [];
            $this->html['beforeend:body'][] = Html::script($content);
        }

        foreach ($this->html as $swapSelector => $blocks) {
            $content = implode(PHP_EOL, $blocks);
            $html[] = Html::tag('div', $content, [
                ComponentsService::HTMX_PREFIX . 'swap-oob' => $swapSelector,
            ]);
        }

        $this->html = [];
        $this->js = [];

        return implode(PHP_EOL, $html);
    }

    /**
     * Registers HTML code to be output.
     *
     * @since 2.11.0
     */
    public function registerHtml(string $html, string $swapSelector): void
    {
        $this->html[$swapSelector][] = $html;
    }

    /**
     * Registers JavaScript code to be output.
     *
     * @since 2.11.0
     */
    public function registerJs(string $js): void
    {
        // Trim any whitespace and ensure it ends with a semicolon.
        $js = StringHelper::ensureRight(trim($js, " \t\n\r\0\x0B"), ';');

        $this->js[] = $js;
    }

    /**
     * Returns the value for the out-of-band swap from a rendered template if it exists, otherwise a rendered string.
     */
    public function getOobSwapValue(string $selector, string $template, array $variables = []): ?string
    {
        if (Craft::$app->getView()->resolveTemplate($template) === false) {
            return Craft::$app->getView()->renderString($template, $variables);
        }

        if (in_array($selector, $this->getOobSwapSources())) {
            return null;
        }

        $this->oobSwapSources[] = $selector;

        return Craft::$app->getView()->renderTemplate($template, $variables);
    }

    /**
     * Resets the components that initiated out-of-band swaps in the current request.
     */
    public function resetOobSwapSources(): void
    {
        $this->oobSwapSources = [];
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

    /**
     * Returns the components that initiated out-of-band swaps in the current request, including the original component.
     */
    private function getOobSwapSources(): array
    {
        if ($this->oobSwapSources === null) {
            $this->oobSwapSources = [];
            $id = Sprig::$core->requests->getValidatedParam('sprig:id');
            if ($id) {
                $this->oobSwapSources[] = '#' . $id;
            }
        }

        return $this->oobSwapSources;
    }
}
