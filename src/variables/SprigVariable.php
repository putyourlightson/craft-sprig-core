<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sprig\variables;

use Craft;
use craft\db\Paginator;
use craft\helpers\Html;
use craft\helpers\Template;
use craft\web\twig\variables\Paginate;
use putyourlightson\sprig\base\Component;
use putyourlightson\sprig\Sprig;
use Twig\Markup;
use yii\db\Query;

class SprigVariable
{
    /**
     * @var string
     */
    public string $htmxVersion = '1.8.4';

    /**
     * Get the SRI hash from https://htmx.org/docs/#installing
     * or generate it at https://www.srihash.org/
     *
     * @var string
     */
    public string $htmxSRIHash = 'sha384-wg5Y/JwF7VxGk4zLsJEcAojRtlVp1FKKdGy1qN+OMtdq72WRvX/EdRdqg/LOhYeV';

    /**
     * Returns the script tag with the given attributes.
     */
    public function getScript(array $attributes = []): Markup
    {
        return $this->_getScript($attributes);
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

    /**
     * Returns a script tag to the source file.
     */
    private function _getScript(array $attributes = []): Markup
    {
        $url = 'https://unpkg.com/htmx.org@' . $this->htmxVersion . '/dist/htmx.min.js';

        if (Craft::$app->getConfig()->env == 'dev') {
            $url = str_replace('htmx.min.js', 'htmx.js', $url);
        }
        else {
            // Add subresource integrity
            // https://github.com/bigskysoftware/htmx/issues/261
            $attributes['integrity'] = $this->htmxSRIHash;
            $attributes['crossorigin'] = 'anonymous';
        }

        $script = Html::jsFile($url, $attributes);

        return Template::raw($script);
    }
}
