<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sprig\base;

use Craft;
use craft\base\Component as BaseComponent;

abstract class Component extends BaseComponent implements ComponentInterface
{
    /**
     * @var string|null The path to the template that the `render` method should render.
     */
    protected ?string $_template;

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
     * Returns whether this is a Sprig request.
     */
    public static function getIsRequest(): bool
    {
        return Craft::$app->getRequest()->getHeaders()->get('HX-Request', false) == 'true';
    }

    /**
     * Returns whether this is a Sprig include.
     */
    public static function getIsInclude(): bool
    {
        return !self::getIsRequest();
    }

    /**
     * Returns whether this is a boosted request.
     */
    public static function getIsBoosted(): bool
    {
        return Craft::$app->getRequest()->getHeaders()->get('HX-Boosted', false) == 'true';
    }

    /**
     * Returns whether this is a history restore request.
     */
    public static function getIsHistoryRestoreRequest(): bool
    {
        return Craft::$app->getRequest()->getHeaders()->get('HX-History-Restore-Request', false) == 'true';
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
     * Replaces the current URL in the location bar.
     * https://htmx.org/headers/hx-replace-url/
     */
    public static function replaceUrl(string $url): void
    {
        Craft::$app->getResponse()->getHeaders()->set('HX-Replace-Url', $url);
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
     * Triggers client-side events.
     * https://htmx.org/headers/hx-trigger/
     */
    public static function triggerEvents(array|string $events, string $on = 'load'): void
    {
        if (is_array($events)) {
            $events = json_encode(array_combine($events, $events));
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
}
