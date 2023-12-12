<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sprig\variables;

use Craft;
use craft\db\Paginator;
use craft\helpers\Template;
use craft\web\twig\variables\Paginate;
use putyourlightson\sprig\base\Component;
use putyourlightson\sprig\services\ComponentsService;
use putyourlightson\sprig\Sprig;
use Twig\Markup;
use yii\db\Query;
use yii\web\AssetBundle;

class SprigVariable
{
    /**
     * Returns the script tag with the given attributes.
     *
     * @deprecated in 2.6.0
     */
    public function getScript(array $attributes = []): Markup
    {
        Craft::$app->getDeprecator()->log(__METHOD__, '`sprig.script` has been deprecated. It is no longer required and can be safely removed.');

        return Template::raw('');
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
     * Returns whether this is a Sprig request.
     */
    public function getIsRequest(): bool
    {
        return Component::getIsRequest();
    }

    /**
     * Returns whether this is a Sprig include.
     */
    public function getIsInclude(): bool
    {
        return Component::getIsInclude();
    }

    /**
     * Returns whether this is a boosted request.
     */
    public static function getIsBoosted(): bool
    {
        return Component::getIsBoosted();
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
     * Registers the script and returns the asset bundle.
     *
     * @since 2.6.3
     */
    public function registerScript(array $attributes = []): AssetBundle
    {
        return Sprig::$core->components->registerScript($attributes);
    }

    /**
     * Sets whether the script should automatically be registered.
     *
     * @since 2.6.3
     */
    public function setRegisterScript(bool|array $value): void
    {
        Sprig::$core->components->setRegisterScript($value);
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

    public function location(string $url): void
    {
        Component::location($url);
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

    public function triggerEvents(array|string $events, string $on = 'load'): void
    {
        Component::triggerEvents($events, $on);
    }

    /**
     * Returns a [[RefreshOnLoad]] component.
     *
     * @see https://github.com/putyourlightson/craft-sprig/issues/279
     */
    public function triggerRefreshOnLoad(string $selector = null): Markup
    {
        return Sprig::$core->components->create(
            'RefreshOnLoad',
            ['selector' => $selector],
            ['s-trigger' => 'load']
        );
    }

    /**
     * Returns a new component.
     */
    public function getComponent(string $value, array $variables = [], array $attributes = []): Markup
    {
        return Sprig::$core->components->create($value, $variables, $attributes);
    }
}
