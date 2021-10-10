<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sprig\services;

use Craft;
use craft\base\Component as BaseComponent;
use craft\base\ElementInterface;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\helpers\Template;
use craft\helpers\UrlHelper;
use craft\web\Request;
use craft\web\View;
use putyourlightson\sprig\base\Component;
use putyourlightson\sprig\errors\InvalidVariableException;
use putyourlightson\sprig\events\ComponentEvent;
use putyourlightson\sprig\Sprig;
use putyourlightson\sprig\plugin\components\SprigPlayground;
use Twig\Markup;
use yii\base\Model;
use yii\web\BadRequestHttpException;

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
    const SPRIG_PREFIXES = ['s', 'sprig', 'data-s', 'data-sprig'];

    /**
     * @const string
     */
    const SPRIG_VERBATIM_TAG = 's-verbatim';

    /**
     * @const string[]
     */
    const HTMX_ATTRIBUTES = ['boost', 'confirm', 'delete', 'disable', 'encoding', 'ext', 'get', 'headers', 'history-elt', 'include', 'indicator', 'params', 'patch', 'post', 'preserve', 'prompt', 'push-url', 'put', 'request', 'select', 'sse', 'swap', 'swap-oob', 'target', 'trigger', 'vals', 'vars', 'ws'];

    /**
     * @const string
     */
    const HTMX_PREFIX = 'data-hx-';

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
                self::HTMX_PREFIX.'include' => '#'.$id.' *',
                self::HTMX_PREFIX.'trigger' => 'refresh',
                self::HTMX_PREFIX.'get' => UrlHelper::actionUrl(self::RENDER_CONTROLLER_ACTION),
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
     * Parses and returns content.
     *
     * Content wrapped in verbatim tags is not parsed. If the subject is very large
     * then increasing the value of `pcre.backtrack_limit` may be necessary.
     * https://www.php.net/manual/en/pcre.configuration.php#ini.pcre.backtrack-limit
     *
     * @param string $content
     * @return string
     */
    public function parse(string $content): string
    {
        // Do this once, and stash the object for re-used
        $dom = new \DOMDocument();
        $pattern = '<[a-z\s\'"]*[s-|sprig-|data-s|data-sprig][^>]*>';
        // The test
        if (preg_match_all('`'.$pattern.'`i', $content,$matches) !== false) {
            foreach ($matches[0] as $match) {
                $htmlArray = $this->htmlTagToArray($dom, $match);
                if ($htmlArray) {
                    $this->_parseAttributes($htmlArray['attributes']);
                    $newTag = $this->htmlArrayToTag($htmlArray);
                    $content = str_replace($match, $newTag, $content);
                }
            }
        }

        return $content;
    }

    /**
     * Convert an HTML tag passed in as a string to an array containing its name, and attributes
     *
     * @param \DOMDocument $dom The DOMDocument to use; allocate it once, and re-used it
     * @param string $htmlTag   The HTML tag to be parsed
     * @return array|null
     */
    private function htmlTagToArray($dom, $htmlTag)
    {
        libxml_use_internal_errors(true);
        $dom->loadHTML($htmlTag, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_use_internal_errors(false);
        $domElement = $dom->firstChild;
        if ($domElement === null) {
            return null;
        }
        $attrs = [];
        foreach($domElement->attributes as $attr) {
            $attrs[$attr->name] = $attr->value;
        }

        return [
            'name' => $domElement->nodeName,
            'attributes' => $attrs,
        ];
    }

    /**
     * Convert an HTML tag passed in as an array to an HTML tag string
     *
     * @param array $htmlArray
     * @return string
     */
    private function htmlArrayToTag(array $htmlArray): string
    {
        $html = '<' . $htmlArray['name'] . Html::renderTagAttributes($htmlArray['attributes']) . '>';

        return $html;
    }

    /**
     * Parses an array of attributes.
     *
     * @param array $attributes
     */
    private function _parseAttributes(array &$attributes)
    {
        $this->_parseSprigAttribute($attributes);

        foreach ($attributes as $key => $value) {
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
        // Use `!isset` over `!empty` because the attributes value will be an empty string
        if (!isset($attributes['sprig']) && !isset($attributes['data-sprig'])) {
            return;
        }

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

        $attributes[self::HTMX_PREFIX.$verb] = UrlHelper::actionUrl(self::RENDER_CONTROLLER_ACTION, $params);
    }

    /**
     * Parses an attribute in an array of attributes.
     *
     * @param array $attributes
     * @param string $key
     * @param string $value
     */
    private function _parseAttribute(array &$attributes, string $key, string $value)
    {
        $name = $this->_getSprigAttributeName($key);

        if (!$name) {
            return;
        }

        if (strpos($name, 'val:') === 0) {
            $name = StringHelper::toCamelCase(substr($name, 4));

            $this->_mergeJsonAttributes($attributes, 'vals', [$name => $value]);
        }
        elseif ($name == 'replace') {
            $attributes[self::HTMX_PREFIX.'select'] = $value;
            $attributes[self::HTMX_PREFIX.'target'] = $value;
            $attributes[self::HTMX_PREFIX.'swap'] = 'outerHTML';
        }
        elseif (in_array($name, self::HTMX_ATTRIBUTES)) {
            if ($name == 'headers' || $name == 'vals') {
                $this->_mergeJsonAttributes($attributes, $name, $value);
            }
            else {
                $attributes[self::HTMX_PREFIX.$name] = $value;
            }

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
     */
    private function _mergeJsonAttributes(array &$attributes, string $name, $values)
    {
        if (is_string($values)) {
            if (strpos($values, 'javascript:') === 0) {
                throw new BadRequestHttpException('The “s-'.$name.'” attribute in Sprig components may not contain a “javascript:” prefix for security reasons. Use a JSON encoded value instead.');
            }

            $values = Json::decode($values);
        }

        $key = self::HTMX_PREFIX.$name;

        if (!empty($attributes[$key])) {
            $values = array_merge(Json::decode($attributes[$key]), $values);
        }

        $attributes[$key] = Json::htmlEncode($values);
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
            if (strpos($key, $prefix.'-') === 0) {
                return substr($key, strlen($prefix) + 1);
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
            if (!empty($attributes[$prefix.'-'.$name])) {
                return $attributes[$prefix.'-'.$name];
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
