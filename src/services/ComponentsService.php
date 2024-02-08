<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sprig\services;

use Craft;
use craft\base\Component as BaseComponent;
use craft\base\ElementInterface;
use craft\base\Event;
use craft\events\AssetBundleEvent;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\helpers\Template;
use craft\helpers\UrlHelper;
use craft\web\View;
use putyourlightson\sprig\assets\HtmxAssetBundle;
use putyourlightson\sprig\base\Component;
use putyourlightson\sprig\components\RefreshOnLoad;
use putyourlightson\sprig\errors\FriendlyInvalidVariableException;
use putyourlightson\sprig\events\ComponentEvent;
use putyourlightson\sprig\helpers\Html;
use putyourlightson\sprig\plugin\components\SprigPlayground;
use putyourlightson\sprig\Sprig;
use Twig\Markup;
use yii\base\InvalidArgumentException;
use yii\base\Model;
use yii\web\AssetBundle;
use yii\web\BadRequestHttpException;
use yii\web\Request;

/**
 * @property-write bool|array $registerScript
 * @property-write array $config
 */
class ComponentsService extends BaseComponent
{
    /**
     * @event ComponentEvent
     */
    public const EVENT_BEFORE_CREATE_COMPONENT = 'beforeCreateComponent';

    /**
     * @event ComponentEvent
     */
    public const EVENT_AFTER_CREATE_COMPONENT = 'afterCreateComponent';

    /**
     * @const string
     */
    public const COMPONENT_NAMESPACE = 'sprig\\components\\';

    /**
     * @const string
     */
    public const RENDER_CONTROLLER_ACTION = 'sprig-core/components/render';

    /**
     * @const string[]
     */
    public const SPRIG_PREFIXES = ['s-', 'sprig-'];

    /**
     * @const string
     */
    public const SPRIG_PARSED_ATTRIBUTE = 'sprig-parsed';

    /**
     * @const string
     */
    public const SPRIG_CSS_CLASS = 'sprig-component';

    /**
     * @const string[]
     */
    public const SPRIG_ATTRIBUTES = [
        'action',
        'cache',
        'listen',
        'method',
        'replace',
        'val',
    ];

    /**
     * @const string[]
     */
    public const HTMX_ATTRIBUTES = [
        'boost',
        'confirm',
        'delete',
        'disable',
        'disabled-elt',
        'disinherit',
        'encoding',
        'ext',
        'get',
        'headers',
        'history',
        'history-elt',
        'include',
        'indicator',
        'on',
        'params',
        'patch',
        'post',
        'preserve',
        'prompt',
        'push-url',
        'put',
        'replace-url',
        'request',
        'select',
        'select-oob',
        'sse',
        'swap',
        'swap-oob',
        'sync',
        'target',
        'trigger',
        'validate',
        'vals',
        'ws',
    ];

    /**
     * @const string
     */
    public const HTMX_PREFIX = 'data-hx-';

    /**
     * @const string The htmx version to load (must exist in `resources/lib/htmx/`).
     * Downloaded from https://unpkg.com/htmx.org
     *
     * @since 2.6.0
     */
    public const HTMX_VERSION = '1.9.9';

    /**
     * @var string|null
     */
    private ?string $_componentName = null;

    /**
     * @var string|null
     */
    private ?string $_sprigActionUrl = null;

    /**
     * @var bool|array
     */
    private bool|array $_registerScript = true;

    /**
     * Registers the script and returns the asset bundle.
     *
     * @since 2.6.3
     */
    public function registerScript(array $attributes = []): AssetBundle
    {
        /**
         * View::EVENT_AFTER_REGISTER_ASSET_BUNDLE was only added in Craft 4.5.0.
         * TODO: Remove the outer condition in Sprig Core 3.
         */
        if (class_exists(AssetBundleEvent::class)) {
            Event::on(View::class, View::EVENT_AFTER_REGISTER_ASSET_BUNDLE,
                function(AssetBundleEvent $event) use ($attributes) {
                    if ($event->bundle instanceof HtmxAssetBundle) {
                        $event->bundle->jsOptions = $attributes;
                    }
                }
            );
        }

        return Craft::$app->getView()->registerAssetBundle(HtmxAssetBundle::class);
    }

    /**
     * Sets whether the script should automatically be registered.
     *
     * @since 2.6.3
     */
    public function setRegisterScript(bool|array $value): void
    {
        $this->_registerScript = $value;
    }

    /**
     * Sets config options and registers them as a meta tag.
     *
     * @since 2.6.4
     */
    public function setConfig(array $options = []): void
    {
        Craft::$app->getView()->registerMetaTag([
            'name' => 'htmx-config',
            'content' => json_encode($options),
        ], 'htmx-config');
    }

    /**
     * Creates a new component.
     */
    public function create(string $value, array $variables = [], array $attributes = []): Markup
    {
        $this->_componentName = $value;
        $values = [];

        $siteId = Craft::$app->getSites()->getCurrentSite()->id;
        $values['sprig:siteId'] = Craft::$app->getSecurity()->hashData((string)$siteId);

        $mergedVariables = array_merge(
            $variables,
            Sprig::$core->requests->getVariables()
        );

        $event = new ComponentEvent([
            'value' => $value,
            'variables' => $mergedVariables,
            'attributes' => $attributes,
        ]);
        $this->trigger(self::EVENT_BEFORE_CREATE_COMPONENT, $event);

        // Repopulate values from event
        $value = $event->value;
        $mergedVariables = $event->variables;
        $attributes = $event->attributes;

        $componentObject = $this->createObject($value, $mergedVariables);

        if ($componentObject) {
            $type = 'component';
            $renderedContent = $componentObject->render();
        } else {
            $type = 'template';

            if (!Craft::$app->getView()->doesTemplateExist($value)) {
                throw new BadRequestHttpException('Unable to find the component or template “' . $value . '”.');
            }

            // Unset the component type, so that nested components will work.
            // https://github.com/putyourlightson/craft-sprig/issues/243
            $values['sprig:component'] = Craft::$app->getSecurity()->hashData('');

            $renderedContent = Craft::$app->getView()->renderTemplate($value, $mergedVariables);
        }

        $content = $this->parse($renderedContent);

        $values['sprig:' . $type] = Craft::$app->getSecurity()->hashData($value);

        foreach ($variables as $name => $variable) {
            $this->_validateVariable($name, $variable);
            $val = $this->_normalizeVariable($variable);
            $values['sprig:variables[' . $name . ']'] = $this->_hashVariable($name, $val);
        }

        // Add token to values if this is a preview request.
        // https://github.com/putyourlightson/craft-sprig/issues/162
        if (Craft::$app->request->getIsPreview()) {
            $token = Craft::$app->request->getToken();

            // Ensure token is not null.
            // https://github.com/putyourlightson/craft-sprig/issues/269
            if ($token !== null) {
                $tokenParam = Craft::$app->config->general->tokenParam;
                $values[$tokenParam] = $token;
            }
        }

        // Allow ID to be overridden, otherwise ensure random ID does not start with a digit (to avoid a JS error)
        $id = $attributes['id'] ?? ('component-' . StringHelper::randomString(6));

        // Merge base attributes with provided attributes first, to ensure that `hx-vals` is included in the attributes when they are parsed.
        $attributes = array_merge(
            [
                'id' => $id,
                'class' => self::SPRIG_CSS_CLASS,
                self::HTMX_PREFIX . 'target' => 'this',
                self::HTMX_PREFIX . 'include' => 'this',
                self::HTMX_PREFIX . 'trigger' => 'refresh',
                self::HTMX_PREFIX . 'get' => $this->_getSprigActionUrl(),
                self::HTMX_PREFIX . 'vals' => Json::htmlEncode($values),
            ],
            $attributes
        );

        $this->_parseAttributes($attributes);

        $event->output = Html::tag('div', $content, $attributes);

        if ($this->hasEventHandlers(self::EVENT_AFTER_CREATE_COMPONENT)) {
            $this->trigger(self::EVENT_AFTER_CREATE_COMPONENT, $event);
        }

        if ($this->_registerScript !== false) {
            $attributes = is_array($this->_registerScript) ? $this->_registerScript : [];
            $this->registerScript($attributes);
        }

        return Template::raw($event->output);
    }

    /**
     * Creates a new component object with the provided variables.
     */
    public function createObject(string $component, array $variables = []): ?Component
    {
        if ($component == 'RefreshOnLoad') {
            return new RefreshOnLoad(['variables' => $variables]);
        }

        if ($component == 'SprigPlayground' && class_exists(SprigPlayground::class)) {
            return new SprigPlayground(['variables' => $variables]);
        }

        $componentClass = self::COMPONENT_NAMESPACE . $component;

        if (!class_exists($componentClass)) {
            return null;
        }

        if (!is_subclass_of($componentClass, Component::class)) {
            throw new BadRequestHttpException('Component class “' . $componentClass . '” must extend “' . Component::class . '”.');
        }

        return Craft::createObject([
            'class' => $componentClass,
            'attributes' => $variables,
        ]);
    }

    /**
     * Parses content for Sprig attributes.
     */
    public function parse(string $content): string
    {
        $parseableTags = $this->_getParseableTags($content);

        foreach ($parseableTags as $tag) {
            if ($newTag = $this->_getParsedTag($tag)) {
                $content = str_replace($tag, $newTag, $content);
            }
        }

        return $content;
    }

    /**
     * Returns parseable tags.
     */
    private function _getParseableTags(string $content): array
    {
        // Look for all possible Sprig attributes, with reasonable backtick limits.
        $attributes = array_merge(self::SPRIG_ATTRIBUTES, self::HTMX_ATTRIBUTES);
        $attributes = array_merge(
            ['sprig', 'data-sprig'],
            array_map(fn($attribute) => 's-' . $attribute, $attributes),
            array_map(fn($attribute) => 'data-s-' . $attribute, $attributes),
            array_map(fn($attribute) => 'sprig-' . $attribute, $attributes),
            array_map(fn($attribute) => 'data-sprig-' . $attribute, $attributes),
        );
        $pattern = '/<[^>]{1,10000}\s(' . implode('|', $attributes) . ')[^>]{0,10000}>/im';

        if (preg_match_all($pattern, $content, $matches)) {
            return $matches[0];
        }

        if (preg_last_error() == PREG_BACKTRACK_LIMIT_ERROR) {
            Craft::error('Backtrack limit was exhausted!', __METHOD__);
        }

        return $matches[0] ?? [];
    }

    /**
     * Returns a parsed tag.
     */
    private function _getParsedTag(string $tag): ?string
    {
        try {
            // Replace new lines with spaces, to ensure parsing works.
            // https://github.com/putyourlightson/craft-sprig/issues/264
            $tag = str_replace(PHP_EOL, ' ', $tag);

            $attributes = Html::parseTagAttributes($tag);
        } catch (InvalidArgumentException $exception) {
            Craft::error($exception->getMessage(), __METHOD__);

            return null;
        }

        if (isset($attributes['data'][self::SPRIG_PARSED_ATTRIBUTE])) {
            return $tag;
        }

        $name = $this->_getTagName($tag);
        $this->_parseAttributes($attributes);
        $attributes['data'][self::SPRIG_PARSED_ATTRIBUTE] = true;

        return Html::beginTag($name, $attributes);
    }

    /**
     * Returns the name of a given tag.
     */
    private function _getTagName(string $tag): string
    {
        preg_match('/<([\w\-]+)/', $tag, $match);

        return strtolower($match[1]);
    }

    /**
     * Parses an array of attributes.
     */
    private function _parseAttributes(array &$attributes): void
    {
        foreach ($attributes as $key => &$value) {
            $this->_parseAttribute($attributes, $key, $value);
        }
    }

    /**
     * Parses the Sprig attribute on an array of attributes.
     */
    private function _parseSprigAttribute(array &$attributes): void
    {
        $verb = 'get';
        $params = [];

        $method = $this->_getSprigAttributeValue($attributes, 'method');
        if (strtolower($method) == 'post') {
            $verb = 'post';
            $this->_mergeJsonAttributes($attributes, 'headers', [
                Request::CSRF_HEADER => Craft::$app->getRequest()->getCsrfToken(),
            ]);
        }

        $action = $this->_getSprigAttributeValue($attributes, 'action');
        if ($action) {
            $params['sprig:action'] = Craft::$app->getSecurity()->hashData($action);
        }

        $attributes[self::HTMX_PREFIX . $verb] = $this->_getSprigActionUrl($params);
    }

    /**
     * Parses an attribute in an array of attributes.
     */
    private function _parseAttribute(array &$attributes, string $key, array|string|bool $value): void
    {
        if ($key == 'data' && is_array($value)) {
            foreach ($value as $dataKey => $dataValue) {
                $this->_parseAttribute($attributes, $dataKey, $dataValue);
            }

            return;
        }

        if ($key == 'sprig' || $key == 'data-sprig') {
            $this->_parseSprigAttribute($attributes);

            return;
        }

        $name = $this->_getSprigAttributeName($key);

        if (!$name) {
            return;
        }

        if (str_starts_with($name, 'on:')) {
            $attributes[self::HTMX_PREFIX . $name] = $value;
        } elseif (str_starts_with($name, 'val:')) {
            $name = StringHelper::toCamelCase(substr($name, 4));

            /**
             * If the value is `true` then convert it back to a blank string.
             * https://github.com/putyourlightson/craft-sprig/issues/178#issuecomment-950415937
             *
             * @see Html::parseTagAttribute()
             */
            $value = $value === true ? '' : $value;

            $this->_mergeJsonAttributes($attributes, 'vals', [$name => $value]);
        } elseif ($name == 'headers' || $name == 'vals') {
            $this->_mergeJsonAttributes($attributes, $name, $value);
        } elseif ($name == 'cache') {
            $this->_mergeJsonAttributes($attributes, 'headers', ['S-Cache' => $value]);
        } elseif ($name == 'listen') {
            $cssSelectors = StringHelper::split($value);
            $triggers = array_map(fn($selector) => 'htmx:afterOnLoad from:' . $selector, $cssSelectors);
            $attributes[self::HTMX_PREFIX . 'trigger'] = join(',', $triggers);
        } elseif ($name == 'replace') {
            $attributes[self::HTMX_PREFIX . 'select'] = $value;
            $attributes[self::HTMX_PREFIX . 'target'] = $value;
            $attributes[self::HTMX_PREFIX . 'swap'] = 'outerHTML';
        } elseif (in_array($name, self::HTMX_ATTRIBUTES)) {
            $attributes[self::HTMX_PREFIX . $name] = $value;
        }

        if ($name == 'on') {
            Craft::$app->getDeprecator()->log(__METHOD__, '`s-on` has been deprecated. Use `s-on:*` instead.');
        }
    }

    /**
     * Merges new values to existing JSON attribute values.
     */
    private function _mergeJsonAttributes(array &$attributes, string $name, array|string $values): void
    {
        if (is_string($values)) {
            if (str_starts_with($values, 'javascript:')) {
                throw new BadRequestHttpException('The “s-' . $name . '” attribute in Sprig components may not contain a “javascript:” prefix for security reasons. Use a JSON encoded value instead.');
            }

            $values = Json::decode(html_entity_decode($values));
        }

        $key = self::HTMX_PREFIX . $name;

        if (!empty($attributes[$key])) {
            $values = array_merge(Json::decode($attributes[$key]), $values);
        }

        $attributes[$key] = Json::htmlEncode($values);
    }

    /**
     * Returns a Sprig action URL with optional params.
     */
    private function _getSprigActionUrl(array $params = []): string
    {
        if ($this->_sprigActionUrl === null) {
            $this->_sprigActionUrl = UrlHelper::actionUrl(self::RENDER_CONTROLLER_ACTION);
        }

        if (empty($params)) {
            return $this->_sprigActionUrl;
        }

        $query = UrlHelper::buildQuery($params);

        if ($query !== '') {
            $joinSymbol = !str_contains($this->_sprigActionUrl, '?') ? '?' : '&';

            return $this->_sprigActionUrl . $joinSymbol . $query;
        }

        return $this->_sprigActionUrl;
    }

    /**
     * Returns a Sprig attribute name if it exists.
     */
    private function _getSprigAttributeName(string $key): string
    {
        foreach (self::SPRIG_PREFIXES as $prefix) {
            if (str_starts_with($key, $prefix)) {
                return substr($key, strlen($prefix));
            }
        }

        return '';
    }

    /**
     * Returns a Sprig attribute value if it exists.
     */
    private function _getSprigAttributeValue(array $attributes, string $name): string
    {
        foreach (self::SPRIG_PREFIXES as $prefix) {
            if (!empty($attributes[$prefix . $name])) {
                return $attributes[$prefix . $name];
            }

            if (!empty($attributes['data'][$prefix . $name])) {
                return $attributes['data'][$prefix . $name];
            }
        }

        return '';
    }

    /**
     * Hashes a variable, possibly throwing an exception.
     */
    private function _hashVariable(string $name, mixed $value): string
    {
        $this->_validateVariableType($name, $value);

        if (is_array($value)) {
            $value = Json::encode($value);
        }

        return Craft::$app->getSecurity()->hashData($value);
    }

    /**
     * Validates a variable type.
     */
    private function _validateVariableType(string $name, $value, $isArray = false): void
    {
        $variable = [
            'name' => $name,
            'value' => $value,
            'isArray' => $isArray,
        ];

        if ($value instanceof ElementInterface) {
            $this->_throwInvalidVariableError('element', $variable, $isArray);
        }

        if ($value instanceof Model) {
            $this->_throwInvalidVariableError('model', $variable, $isArray);
        }

        if (is_object($value)) {
            $this->_throwInvalidVariableError('object', $variable, $isArray);
        }

        if (is_array($value)) {
            foreach ($value as $arrayValue) {
                $this->_validateVariableType($name, $arrayValue, true);
            }
        }
    }

    /**
     * Validates a variable.
     */
    private function _validateVariable(string $name, mixed $value, bool $isArray = false): void
    {
        if (is_array($value)) {
            foreach ($value as $variable) {
                $this->_validateVariable($name, $variable, true);
            }
        }

        if (is_object($value)) {
            $this->_throwInvalidVariableError($name, $value, $isArray);
        }
    }

    /**
     * Normalizes a variable.
     */
    private function _normalizeVariable(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = Json::encode($value);
        }

        return $value;
    }

    /**
     * Throws an invalid variable error.
     */
    private function _throwInvalidVariableError(string $name, mixed $value, bool $isArray = false): void
    {
        $variables = [
            'name' => $name,
            'value' => $value,
            'isArray' => $isArray,
            'isElement' => $value instanceof ElementInterface,
            'isCraftElement' => str_starts_with($value::class, 'craft\\'),
            'className' => $value::class,
            'componentName' => $this->_componentName,
        ];

        // Only thrown an exception if Canary is enabled or devMode is off.
        if (Craft::$app->getPlugins()->getPlugin('canary') !== null
            || Craft::$app->getConfig()->getGeneral()->devMode === false
        ) {
            throw new FriendlyInvalidVariableException($variables);
        }

        $content = Craft::$app->getView()->renderPageTemplate('sprig-core/_error', $variables, View::TEMPLATE_MODE_CP);

        $response = Craft::$app->getResponse();
        $response->content = $content;
        $response->setStatusCode(400);

        Craft::$app->end();
    }
}
