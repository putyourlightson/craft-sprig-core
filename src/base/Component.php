<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sprig\base;

use Craft;
use craft\base\Component as BaseComponent;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\web\View;
use putyourlightson\sprig\services\ComponentsService;
use putyourlightson\sprig\Sprig;

abstract class Component extends BaseComponent implements ComponentInterface
{
    /**
     * @var string|null The path to the template that the `render` method should render.
     */
    protected ?string $_template = null;

    /**
     * Set all attributes to be safe by default.
     */
    protected function defineRules(): array
    {
        return [[$this->attributes(), 'safe']];
    }

    /**
     * @inheritdoc
     */
    public function render(): string
    {
        if ($this->_template !== null) {
            return Craft::$app->getView()->renderTemplate($this->_template, $this->getAttributes());
        }

        return '';
    }

    /**
     * Returns the message resulting from a request.
     */
    public static function getMessage(): string
    {
        return Craft::$app->getSession()->getFlash('sprig:message', '');
    }

    /**
     * Returns the model ID resulting from a request.
     */
    public static function getModelId(): ?int
    {
        return Craft::$app->getSession()->getFlash('sprig:modelId');
    }

    /**
     * Returns the value entered by the user when prompted via `s-prompt` or `hx-prompt`.
     */
    public static function getPrompt(): string
    {
        return Craft::$app->getRequest()->getHeaders()->get('HX-Prompt', '');
    }

    /**
     * Returns the ID of the target element.
     */
    public static function getTarget(): string
    {
        return Craft::$app->getRequest()->getHeaders()->get('HX-Target', '');
    }

    /**
     * Returns the ID of the element that triggered the request.
     */
    public static function getTrigger(): string
    {
        return Craft::$app->getRequest()->getHeaders()->get('HX-Trigger', '');
    }

    /**
     * Returns the name of the element that triggered the request.
     */
    public static function getTriggerName(): string
    {
        return Craft::$app->getRequest()->getHeaders()->get('HX-Trigger-Name', '');
    }

    /**
     * Returns the URL that the Sprig component was loaded from.
     */
    public static function getUrl(): string
    {
        return Craft::$app->getRequest()->getHeaders()->get('HX-Current-URL', '');
    }

    /**
     * Returns whether this is a boosted request.
     */
    public static function isBoosted(): bool
    {
        return Craft::$app->getRequest()->getHeaders()->get('HX-Boosted', false) == 'true';
    }

    /**
     * Returns whether this is an error request.
     */
    public static function isError(): bool
    {
        return Craft::$app->getSession()->getFlash('sprig:isError', false);
    }

    /**
     * Returns whether this is a history restore request.
     */
    public static function isHistoryRestoreRequest(): bool
    {
        return Craft::$app->getRequest()->getHeaders()->get('HX-History-Restore-Request', false) == 'true';
    }

    /**
     * Returns whether this is a Sprig include.
     */
    public static function isInclude(): bool
    {
        return !static::isRequest();
    }

    /**
     * Returns whether this is a Sprig request.
     */
    public static function isRequest(): bool
    {
        return Craft::$app->getRequest()->getHeaders()->get('HX-Request', false) == 'true';
    }

    /**
     * Returns whether this is a success request.
     */
    public static function isSuccess(): bool
    {
        return Craft::$app->getSession()->getFlash('sprig:isSuccess', false);
    }

    /**
     * Triggers a client-side redirect without reloading the page.
     * https://htmx.org/headers/hx-location/
     */
    public static function location(string $url): void
    {
        Craft::$app->getResponse()->getHeaders()->set('HX-Location', $url);
    }

    /**
     * Pushes the URL into the history stack.
     * https://htmx.org/headers/hx-push-url/
     */
    public static function pushUrl(string $url): void
    {
        Craft::$app->getResponse()->getHeaders()->set('HX-Push-Url', $url);
    }

    /**
     * Redirects the browser to the URL.
     * https://htmx.org/reference#response_headers
     */
    public static function redirect(string $url): void
    {
        Craft::$app->getResponse()->getHeaders()->set('HX-Redirect', $url);
    }

    /**
     * Refreshes the browser.
     * https://htmx.org/reference#response_headers
     */
    public static function refresh(bool $refresh = true): void
    {
        Craft::$app->getResponse()->getHeaders()->set('HX-Refresh', $refresh ? 'true' : '');
    }

    /**
     * Registers JavaScript code to be output. This method takes care of registering the code in the appropriate way depending on whether it is part of an include or a request.
     *
     * @since 2.11.0
     */
    public static function registerJs(string $js): void
    {
        if (static::isInclude()) {
            Craft::$app->getView()->registerJs($js, View::POS_END);

            return;
        }

        Sprig::$core->requests->registerJs($js);
    }

    /**
     * Replaces the current URL in the location bar.
     * https://htmx.org/headers/hx-replace-url/
     */
    public static function replaceUrl(string $url): void
    {
        Craft::$app->getResponse()->getHeaders()->set('HX-Replace-Url', $url);
    }

    /**
     * Specifies how the response will be swapped.
     * https://htmx.org/reference#response_headers
     */
    public static function reswap(string $value): void
    {
        Craft::$app->getResponse()->getHeaders()->set('HX-Reswap', $value);
    }

    /**
     * Retargets the element to update with a CSS selector.
     * https://htmx.org/reference#response_headers
     */
    public static function retarget(string $target): void
    {
        Craft::$app->getResponse()->getHeaders()->set('HX-Retarget', $target);
    }

    /**
     * Swaps a template out-of-band. Cyclical requests are mitigated by prevented the swapping of unique components multiple times in the current request, including the initiating component.
     * https://htmx.org/attributes/hx-swap-oob/
     *
     * @since 2.9.0
     */
    public static function swapOob(string $selector, string $template, array $variables = []): void
    {
        if (static::isInclude()) {
            return;
        }

        $value = Sprig::$core->requests->getOobSwapValue($selector, $template, $variables);
        if ($value === null) {
            return;
        }

        Sprig::$core->requests->registerHtml($value, 'innerHTML:' . $selector);
    }

    /**
     * Triggers client-side events.
     * https://htmx.org/headers/hx-trigger/
     *
     * @param array|string $events An array of events, one or more comma-separated events as a string, or a JSON encoded string (that is passed along as-is).
     */
    public static function triggerEvents(array|string $events, string $on = 'load'): void
    {
        if (is_array($events)) {
            $events = Json::encode(array_combine($events, $events));
        }

        $headerMap = [
            'load' => 'HX-Trigger',
            'swap' => 'HX-Trigger-After-Swap',
            'settle' => 'HX-Trigger-After-Settle',
        ];

        $header = $headerMap[$on] ?? null;

        if ($header) {
            Craft::$app->getResponse()->getHeaders()->set($header, $events);
        }
    }

    /**
     * Triggers a refresh event on the provided selector. If variables are provided then they are appended to the component as hidden input fields. Cyclical requests are mitigated by prevented the triggering of unique components multiple times, including the initiating component.
     *
     * @since 2.9.0
     */
    public static function triggerRefresh(string $selector, array $variables = []): void
    {
        if (static::isInclude()) {
            return;
        }

        $triggerRefreshSources = Sprig::$core->requests->getValidatedParam('sprig:triggerRefreshSources') ?? [];
        if (in_array($selector, $triggerRefreshSources)) {
            return;
        }

        $id = Sprig::$core->requests->getValidatedParam('sprig:id');
        $triggerRefreshSources[] = '#' . $id;
        $variables['sprig:triggerRefreshSources'] = Craft::$app->getSecurity()->hashData(Json::encode($triggerRefreshSources));

        foreach ($variables as $name => $value) {
            $values[] = Html::hiddenInput($name, $value);
        }

        $html = implode('', $values);
        Sprig::$core->requests->registerHtml($html, 'beforeend:' . $selector);
        Sprig::$core->requests->registerJs('htmx.trigger(\'' . $selector . '\', \'refresh\')');
    }

    /**
     * Triggers a refresh event on all components on load.
     * https://github.com/putyourlightson/craft-sprig/issues/279
     *
     * @since 2.3.0
     */
    public static function triggerRefreshOnLoad(string $selector = ''): void
    {
        if (static::isRequest()) {
            return;
        }

        $selector = $selector ?: '.' . ComponentsService::SPRIG_CSS_CLASS;
        $js = <<<JS
            window.addEventListener('DOMContentLoaded', function() {
                fetch('/actions/users/session-info', {
                    headers: {
                        'Accept': 'application/json',
                    },
                }).then(function() {
                    for (const component of htmx.findAll('$selector')) {
                        htmx.trigger(component, 'refresh');
                    }
                });
            });
        JS;

        Craft::$app->getView()->registerJs($js, View::POS_END);
    }

    /**
     * @deprecated since 2.12.0. Use `isBoosted()` instead.
     */
    public static function getIsBoosted(): bool
    {
        Craft::$app->getDeprecator()->log(__METHOD__, '`sprig.getIsBoosted` has been deprecated. Use `sprig.isBoosted` instead.');

        return static::isBoosted();
    }

    /**
     * @deprecated since 2.12.0. Use `isError()` instead.
     */
    public static function getIsError(): bool
    {
        Craft::$app->getDeprecator()->log(__METHOD__, '`sprig.getIsError` has been deprecated. Use `sprig.isError` instead.');

        return static::isError();
    }

    /**
     * @deprecated since 2.12.0. Use `isHistoryRestoreRequest()` instead.
     */
    public static function getIsHistoryRestoreRequest(): bool
    {
        Craft::$app->getDeprecator()->log(__METHOD__, '`sprig.getIsHistoryRestoreRequest` has been deprecated. Use `sprig.isHistoryRestoreRequest` instead.');

        return static::isHistoryRestoreRequest();
    }

    /**
     * @deprecated since 2.12.0. Use `isInclude()` instead.
     */
    public static function getIsInclude(): bool
    {
        Craft::$app->getDeprecator()->log(__METHOD__, '`sprig.getIsInclude` has been deprecated. Use `sprig.isInclude` instead.');

        return static::isInclude();
    }

    /**
     * @deprecated since 2.12.0. Use `isRequest()` instead.
     */
    public static function getIsRequest(): bool
    {
        Craft::$app->getDeprecator()->log(__METHOD__, '`sprig.getIsRequest` has been deprecated. Use `sprig.isRequest` instead.');

        return static::isRequest();
    }

    /**
     * @deprecated since 2.12.0. Use `isSuccess()` instead.
     */
    public static function getIsSuccess(): bool
    {
        Craft::$app->getDeprecator()->log(__METHOD__, '`sprig.getIsSuccess` has been deprecated. Use `sprig.isSuccess` instead.');

        return static::isSuccess();
    }
}
