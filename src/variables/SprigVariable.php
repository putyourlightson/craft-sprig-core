<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sprig\variables;

use craft\db\Paginator;
use craft\web\twig\variables\Paginate;
use putyourlightson\sprig\base\Component;
use putyourlightson\sprig\services\ComponentsService;
use putyourlightson\sprig\Sprig;
use yii\db\Query;
use yii\web\AssetBundle;

class SprigVariable
{
    /**
     * Returns the script tag with the given attributes.
     *
     * @deprecated in 2.6.0
     */
    public function getScript(array $attributes = []): string
    {
        Craft::$app->getDeprecator()->log(__METHOD__, '`sprig.script` has been deprecated. It is no longer required and can be safely removed.');

        return '';
    }

    /**
     * Returns the htmx version number.
     *
     * @since 2.6.0
     */
    public function getHtmxVersion(): string
    {
        return ComponentsService::HTMX_VERSION;
    }

    /**
     * Returns the message resulting from a request.
     */
    public function getMessage(): string
    {
        return Component::getMessage();
    }

    /**
     * Returns the model ID resulting from a request.
     */
    public function getModelId(): ?int
    {
        return Component::getModelId();
    }

    /**
     * Returns the value entered by the user when prompted via `s-prompt`.
     */
    public function getPrompt(): string
    {
        return Component::getPrompt();
    }

    /**
     * Returns the ID of the target element.
     */
    public function getTarget(): string
    {
        return Component::getTarget();
    }

    /**
     * Returns the ID of the element that triggered the request.
     */
    public function getTrigger(): string
    {
        return Component::getTrigger();
    }

    /**
     * Returns the name of the element that triggered the request.
     */
    public function getTriggerName(): string
    {
        return Component::getTriggerName();
    }

    /**
     * Returns the URL that the Sprig component was loaded from.
     */
    public function getUrl(): string
    {
        return Component::getUrl();
    }

    /**
     * Returns whether this is a boosted request.
     */
    public function isBoosted(): bool
    {
        return Component::isBoosted();
    }

    /**
     * Returns whether this is an error request.
     */
    public function isError(): bool
    {
        return Component::isError();
    }

    /**
     * Returns whether this is a history restore request.
     */
    public function isHistoryRestoreRequest(): bool
    {
        return Component::isHistoryRestoreRequest();
    }

    /**
     * Returns whether this is a Sprig include.
     */
    public function isInclude(): bool
    {
        return Component::isInclude();
    }

    /**
     * Returns whether this is a Sprig request.
     */
    public function isRequest(): bool
    {
        return Component::isRequest();
    }

    /**
     * Returns whether this is a success request.
     */
    public function isSuccess(): bool
    {
        return Component::isSuccess();
    }

    public function location(string $url): void
    {
        Component::location($url);
    }

    /**
     * Paginates an element query.
     */
    public function paginate(Query $query, int $currentPage = 1, array $config = []): Paginate
    {
        $paginatorQuery = clone $query;
        $paginatorQuery->limit(null);

        $defaultConfig = [
            'currentPage' => $currentPage,
            'pageSize' => $query->limit ?: 100,
        ];
        $config = array_merge($defaultConfig, $config);
        $paginator = new Paginator($paginatorQuery, $config);

        return PaginateVariable::create($paginator);
    }

    public function pushUrl(string $url): void
    {
        Component::pushUrl($url);
    }

    public function redirect(string $url): void
    {
        Component::redirect($url);
    }

    public function refresh(bool $refresh = true): void
    {
        Component::refresh($refresh);
    }

    public function registerJs(string $js): void
    {
        Component::registerJs($js);
    }

    /**
     * Registers the script and returns the asset bundle.
     *
     * @since 2.6.3
     */
    public function registerScript(array $attributes = []): AssetBundle
    {
        return Sprig::$core->components->registerScript($attributes);
    }

    public function replaceUrl(string $url): void
    {
        Component::replaceUrl($url);
    }

    public function reswap(string $value): void
    {
        Component::reswap($value);
    }

    public function retarget(string $target): void
    {
        Component::retarget($target);
    }

    /**
     * Sets config options and registers them as a meta tag.
     *
     * @since 2.5.0
     */
    public function setConfig(array $options = []): void
    {
        Sprig::$core->components->setConfig($options);
    }

    /**
     * Sets whether the script should automatically be registered, and optionally how.
     *
     * @since 2.6.3
     */
    public function setRegisterScript(bool|array $value = true): void
    {
        Sprig::$core->components->setRegisterScript($value);
    }

    public function swapOob(string $selector, string $template, array $variables = []): void
    {
        Component::swapOob($selector, $template, $variables);
    }

    public function triggerEvents(array|string $events, string $on = 'load'): void
    {
        Component::triggerEvents($events, $on);
    }

    public function triggerRefresh(string $selector, array $variables = []): void
    {
        Component::triggerRefresh($selector, $variables);
    }

    public function triggerRefreshOnLoad(string $selector = ''): void
    {
        Component::triggerRefreshOnLoad($selector);
    }

    /**
     * @deprecated since 2.12.0. Use `isBoosted()` instead.
     */
    public function getIsBoosted(): bool
    {
        return Component::getIsBoosted();
    }

    /**
     * @deprecated since 2.12.0. Use `isError()` instead.
     */
    public function getIsError(): bool
    {
        return Component::getIsError();
    }

    /**
     * @deprecated since 2.12.0. Use `isHistoryRestoreRequest()` instead.
     */
    public function getIsHistoryRestoreRequest(): bool
    {
        return Component::getIsHistoryRestoreRequest();
    }

    /**
     * @deprecated since 2.12.0. Use `isInclude()` instead.
     */
    public function getIsInclude(): bool
    {
        return Component::getIsInclude();
    }

    /**
     * @deprecated since 2.12.0. Use `isRequest()` instead.
     */
    public function getIsRequest(): bool
    {
        return Component::getIsRequest();
    }

    /**
     * @deprecated since 2.12.0. Use `isSuccess()` instead.
     */
    public function getIsSuccess(): bool
    {
        return Component::getIsSuccess();
    }
}
