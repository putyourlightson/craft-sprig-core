<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sprig\services;

use Craft;
use craft\base\Component as BaseComponent;
use craft\base\ElementInterface;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\helpers\Template;
use craft\helpers\UrlHelper;
use craft\web\View;
use putyourlightson\sprig\base\Component;
use putyourlightson\sprig\errors\InvalidVariableException;
use putyourlightson\sprig\events\ComponentEvent;
use putyourlightson\sprig\helpers\Html;
use putyourlightson\sprig\helpers\HtmlHelper;
use putyourlightson\sprig\Sprig;
use putyourlightson\sprig\plugin\components\SprigPlayground;
use Twig\Markup;
use yii\base\InvalidArgumentException;
use yii\base\Model;
use yii\log\Logger;
use yii\web\BadRequestHttpException;
use yii\web\Request;

class ComponentsService extends BaseComponent
{
    /**
     * @event ComponentEvent
     */
    const EVENT_BEFORE_CREATE_COMPONENT = 'beforeCreateComponent';

    /**
     * @event ComponentEvent
     */
    const EVENT_AFTER_CREATE_COMPONENT = 'afterCreateComponent';

    /**
     * @const string
     */
    const COMPONENT_NAMESPACE = 'sprig\\components\\';

    /**
     * @const string
     */
    const RENDER_CONTROLLER_ACTION = 'sprig-core/components/render';

    /**
     * @const string[]
     */
    const SPRIG_PREFIXES = ['s-', 'sprig-'];

    /**
     * @const string[]
     */
    const HTMX_ATTRIBUTES = ['boost', 'confirm', 'delete', 'disable', 'disinherit', 'encoding', 'ext', 'get', 'headers', 'history-elt', 'include', 'indicator', 'params', 'patch', 'post', 'preserve', 'prompt', 'push-url', 'put', 'request', 'select', 'sse', 'swap', 'swap-oob', 'sync', 'target', 'trigger', 'vals', 'vars', 'ws'];

    /**
     * @const string
     */
    const HTMX_PREFIX = 'data-hx-';

    /**
     * @var string|null
     */
    private $_sprigActionUrl;

    /**
     * Creates a new component.
     *
     * @param string $value
     * @param array $variables
     * @param array $attributes
     * @return Markup
     */
    public function create(string $value, array $variables = [], array $attributes = []): Markup
    {
        $values = [];

        $siteId = Craft::$app->getSites()->getCurrentSite()->id;
        $values['sprig:siteId'] = Craft::$app->getSecurity()->hashData($siteId);

        $mergedVariables = array_merge(
            $variables,
            Sprig::$core->request->getVariables()
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
        }
        else {
            $type = 'template';

            if (!Craft::$app->getView()->doesTemplateExist($value)) {
                throw new BadRequestHttpException('Unable to find the component or template “'.$value.'”.');
            }

            $renderedContent = Craft::$app->getView()->renderTemplate($value, $mergedVariables);
        }

        $content = $this->parse($renderedContent);

        $values['sprig:'.$type] = Craft::$app->getSecurity()->hashData($value);

        // Unset the other type, so that nested components will work.
        // https://github.com/putyourlightson/craft-sprig/issues/243
        $unsetType = $type == 'component' ? 'template' : 'component';
        $values['sprig:'.$unsetType] = Craft::$app->getSecurity()->hashData(null);;

        foreach ($variables as $name => $val) {
            $values['sprig:variables['.$name.']'] = $this->_hashVariable($name, $val);
        }

        // Add token to values if this is a preview request.
        // https://github.com/putyourlightson/craft-sprig/issues/162
        if (Craft::$app->request->getIsPreview()) {
            $tokenParam = Craft::$app->config->general->tokenParam;
            $values[$tokenParam] = Craft::$app->request->getToken();
        }

        // Allow ID to be overridden, otherwise ensure random ID does not start with a digit (to avoid a JS error)
        $id = $attributes['id'] ?? ('component-'.StringHelper::randomString(6));

        // Merge base attributes with provided attributes first, to ensure that `hx-vals` is included in the attributes when they are parsed.
        $attributes = array_merge(
            [
                'id' => $id,
                'class' => 'sprig-component',
                self::HTMX_PREFIX.'target' => 'this',
                self::HTMX_PREFIX.'include' => 'this',
                self::HTMX_PREFIX.'trigger' => 'refresh',
                self::HTMX_PREFIX.'get' => $this->_getSprigActionUrl(),
                self::HTMX_PREFIX.'vals' => Json::htmlEncode($values),
            ],
            $attributes
        );

        $this->_parseAttributes($attributes);

        $event->output = Html::tag('div', $content, $attributes);

        if ($this->hasEventHandlers(self::EVENT_AFTER_CREATE_COMPONENT)) {
            $this->trigger(self::EVENT_AFTER_CREATE_COMPONENT, $event);
        }

        return Template::raw($event->output);
    }

    /**
     * Creates a new component object with the provided variables.
     *
     * @param string $component
     * @param array $variables
     * @return Component|object|null
     */
    public function createObject(string $component, array $variables = [])
    {
        if ($component == 'SprigPlayground') {
            return new SprigPlayground(['variables' => $variables]);
        }

        $componentClass = self::COMPONENT_NAMESPACE.$component;

        if (!class_exists($componentClass)) {
            return null;
        }

        if (!is_subclass_of($componentClass, Component::class)) {
            throw new BadRequestHttpException('Component class “'.$componentClass.'” must extend “'.Component::class.'”.');
        }

        return Craft::createObject([
            'class' => $componentClass,
            'attributes' => $variables,
        ]);
    }

    /**
     * Parses content for Sprig attributes.
     *
     * @param string $content
     * @return string
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
     *
     * @param string $content
     * @return array
     */
    private function _getParseableTags(string $content): array
    {
        $attributePrefixes = ['sprig', 'data-sprig', 's-', 'data-s-'];
        $pattern = '/<[^!>][^>]*\s(' . implode('|', $attributePrefixes) . ')[^>]*>/im';

        if (preg_match_all($pattern, $content, $matches)) {
            return $matches[0];
        }

        if (preg_last_error() == PREG_BACKTRACK_LIMIT_ERROR) {
            Craft::getLogger()->log('Backtrack limit was exhausted!', Logger::LEVEL_ERROR, 'sprig-core');
        }

        return [];
    }

    /**
     * Returns a parsed tag.
     *
     * @param string $tag
     * @return string|null
     */
    private function _getParsedTag(string $tag)
    {
        try {
            $attributes = HtmlHelper::parseTagAttributes($tag);
        }
        catch (InvalidArgumentException $exception) {
            return null;
        }

        if (isset($attributes['data']['sprig-parsed'])) {
            return $tag;
        }

        $name = $this->_getTagName($tag);
        $this->_parseAttributes($attributes);
        $attributes['data-sprig-parsed'] = true;

        return Html::beginTag($name, $attributes);
    }

    /**
     * Returns the name of a given tag.
     *
     * @param string $tag
     * @return string
     */
    private function _getTagName(string $tag): string
    {
        preg_match('/<([\w\-]+)/', $tag, $match);

        return strtolower($match[1]);
    }

    /**
     * Parses an array of attributes.
     *
     * @param array $attributes
     */
    private function _parseAttributes(array &$attributes)
    {
        foreach ($attributes as $key => &$value) {
            $this->_parseAttribute($attributes, $key, $value);
        }
    }

    /**
     * Parses the Sprig attribute on an array of attributes.
     *
     * @param array $attributes
     */
    private function _parseSprigAttribute(array &$attributes)
    {
        $verb = 'get';
        $params = [];

        $method = $this->_getSprigAttributeValue($attributes, 'method');

        // Make the check case-insensitive
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

        $attributes[self::HTMX_PREFIX.$verb] = $this->_getSprigActionUrl($params);
    }

    /**
     * Parses an attribute in an array of attributes.
     *
     * @param array $attributes
     * @param string $key
     * @param string|array $value
     */
    private function _parseAttribute(array &$attributes, string $key, $value)
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

        if (strpos($name, 'val:') === 0) {
            $name = StringHelper::toCamelCase(substr($name, 4));

            /**
             * If the value is `true` then convert it back to a blank string.
             * https://github.com/putyourlightson/craft-sprig/issues/178#issuecomment-950415937
             * @see HtmlHelper::parseTagAttribute()
             */
            $value = $value === true ? '' : $value;

            $this->_mergeJsonAttributes($attributes, 'vals', [$name => $value]);
        }
        elseif ($name == 'headers' || $name == 'vals') {
            $this->_mergeJsonAttributes($attributes, $name, $value);
        }
        elseif ($name == 'listen') {
            $cssSelectors = StringHelper::split($value);
            $triggers = array_map(function ($selector) {
                return 'htmx:afterOnLoad from:' . $selector;
            }, $cssSelectors);
            $attributes[self::HTMX_PREFIX . 'trigger'] = join(',', $triggers);
        }
        elseif ($name == 'replace') {
            $attributes[self::HTMX_PREFIX.'select'] = $value;
            $attributes[self::HTMX_PREFIX.'target'] = $value;
            $attributes[self::HTMX_PREFIX.'swap'] = 'outerHTML';
        }
        elseif (in_array($name, self::HTMX_ATTRIBUTES)) {
            $attributes[self::HTMX_PREFIX.$name] = $value;

            // Deprecate `s-vars`
            if ($name == 'vars') {
                Craft::$app->getDeprecator()->log(__METHOD__.':vars', 'The “s-vars” attribute in Sprig components has been deprecated for security reasons. Use the new “s-vals” or “s-val:*” attribute instead.');
            }
        }
    }

    /**
     * Merges new values to existing JSON attribute values.
     *
     * @param array $attributes
     * @param string $name
     * @param array|string $values
     * @throws BadRequestHttpException
     */
    private function _mergeJsonAttributes(array &$attributes, string $name, $values)
    {
        if (is_string($values)) {
            if (strpos($values, 'javascript:') === 0) {
                throw new BadRequestHttpException('The “s-'.$name.'” attribute in Sprig components may not contain a “javascript:” prefix for security reasons. Use a JSON encoded value instead.');
            }

            $values = Json::decode(html_entity_decode($values));
        }

        $key = self::HTMX_PREFIX.$name;

        if (!empty($attributes[$key])) {
            $values = array_merge(Json::decode($attributes[$key]), $values);
        }

        $attributes[$key] = Json::htmlEncode($values);
    }

    /**
     * Returns a Sprig action URL with optional params.
     *
     * @param array $params
     * @return string
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
            $joinSymbol = strpos($this->_sprigActionUrl, '?') === false ? '?' : '&';

            return $this->_sprigActionUrl . $joinSymbol . $query;
        }

        return $this->_sprigActionUrl;
    }

    /**
     * Returns a Sprig attribute name if it exists.
     *
     * @param string $key
     * @return string
     */
    private function _getSprigAttributeName(string $key): string
    {
        foreach (self::SPRIG_PREFIXES as $prefix) {
            if (strpos($key, $prefix) === 0) {
                return substr($key, strlen($prefix));
            }
        }

        return '';
    }

    /**
     * Returns a Sprig attribute value if it exists.
     *
     * @param array $attributes
     * @param string $name
     * @return string
     */
    private function _getSprigAttributeValue(array $attributes, string $name): string
    {
        foreach (self::SPRIG_PREFIXES as $prefix) {
            if (!empty($attributes[$prefix.$name])) {
                return $attributes[$prefix.$name];
            }

            if (!empty($attributes['data'][$prefix.$name])) {
                return $attributes['data'][$prefix.$name];
            }
        }

        return '';
    }

    /**
     * Hashes a variable, possibly throwing an exception.
     *
     * @param string $name
     * @param mixed $value
     * @return string
     * @throws InvalidVariableException
     */
    private function _hashVariable(string $name, $value): string
    {
        $this->_validateVariableType($name, $value);

        if (is_array($value)) {
            $value = Json::encode($value);
        }

        return Craft::$app->getSecurity()->hashData($value);
    }

    private function _validateVariableType(string $name, $value, $isArray = false)
    {
        $variable = [
            'name' => $name,
            'value' => $value,
            'isArray' => $isArray,
        ];

        if ($value instanceof ElementInterface) {
            throw new InvalidVariableException(
                $this->_getError('variable-element', $variable)
            );
        }

        if ($value instanceof Model) {
            throw new InvalidVariableException(
                $this->_getError('variable-model', $variable)
            );
        }

        if (is_object($value)) {
            throw new InvalidVariableException(
                $this->_getError('variable-object', $variable)
            );
        }

        if (is_array($value)) {
            foreach ($value as $arrayValue) {
                $this->_validateVariableType($name, $arrayValue, true);
            }
        }
    }

    /**
     * Returns an error from a rendered template.
     *
     * @param string $templateName
     * @param array $variables
     * @return string
     */
    private function _getError(string $templateName, array $variables = []): string
    {
        $template = 'sprig-core/_errors/'.$templateName;

        return Craft::$app->getView()->renderTemplate($template, $variables, View::TEMPLATE_MODE_CP);
    }
}
