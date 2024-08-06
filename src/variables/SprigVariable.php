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
use craft\web\View;
use putyourlightson\sprig\base\Component;
use putyourlightson\sprig\services\ComponentsService;
use putyourlightson\sprig\Sprig;
use Twig\Markup;
use yii\db\Query;
use yii\web\AssetBundle;

class SprigVariable
{
    /**
     * The templates that initiated out-of-band swaps in the current request.
     */
    private ?array $oobSwapTemplates = null;

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
     * Returns whether this is a success request.
     */
    public function getIsSuccess(): bool
    {
        return Component::getIsSuccess();
    }

    /**
     * Returns whether this is an error request.
     */
    public function getIsError(): bool
    {
        return Component::getIsError();
    }

    /**
     * Returns the message resulting from a request.
     */
    public static function getMessage(): string
    {
        return Component::getMessage();
    }

    /**
     * Returns the model ID resulting from a request.
     */
    public static function getModelId(): ?int
    {
        return Component::getModelId();
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
    public function setRegisterScript(bool|array $value = true): void
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
     * Swaps a template out-of-band. Cyclical requests are mitigated by prevented the swapping of any template multiple times in the current request, including the initiating template.
     * https://htmx.org/attributes/hx-swap-oob/
     *
     * @since 2.9.0
     */
    public function swapOob(string $selector, string $template, array $variables = []): void
    {
        if (Component::getIsInclude()) {
            return;
        }

        $value = $this->getOobSwapValue($template, $variables);
        if ($value === null) {
            return;
        }

        $html = Html::tag('div', $value, ['s-swap-oob' => 'innerHTML:' . $selector]);

        Craft::$app->getView()->registerHtml($html);
    }

    /**
     * Triggers a refresh event on the provided selector. If variables are provided then they are appended to the component as hidden input fields.
     *
     * @since 2.9.0
     */
    public function triggerRefresh(string $selector, array $variables = []): void
    {
        if (Component::getIsInclude()) {
            return;
        }

        $html = '';

        if (!empty($variables)) {
            foreach ($variables as $name => $value) {
                $html .= Html::hiddenInput($name, $value);
            }
        }

        $html .= Html::tag('script', 'htmx.trigger(\'' . $selector . '\', \'refresh\')');

        $html = Html::tag(
            'div',
            $html,
            ['s-swap-oob' => 'beforeend:' . $selector],
        );

        Craft::$app->getView()->registerHtml($html);
    }

    /**
     * Triggers a refresh event on all components on load.
     * https://github.com/putyourlightson/craft-sprig/issues/279
     *
     * @since 2.3.0
     */
    public function triggerRefreshOnLoad(string $selector = ''): void
    {
        if (Component::getIsRequest()) {
            return;
        }

        $selector = $selector ?: '.' . ComponentsService::SPRIG_CSS_CLASS;
        $js = <<<JS
            fetch('/actions/users/session-info', {headers: {'Accept': 'application/json'}}).then(() => {
                for (const component of htmx.findAll('$selector')) {
                    htmx.trigger(component, 'refresh');
                }
            });
        JS;

        Craft::$app->getView()->registerJs($js, View::POS_END);
    }

    /**
     * Returns a new component.
     */
    public function getComponent(string $value, array $variables = [], array $attributes = []): Markup
    {
        return Sprig::$core->components->create($value, $variables, $attributes);
    }

    /**
     * Returns the value for the out-of-band swap from a rendered template if it exists, otherwise a rendered string
     */
    private function getOobSwapValue(string $template, array $variables = []): ?string
    {
        if (Craft::$app->getView()->resolveTemplate($template) === false) {
            return Craft::$app->getView()->renderString($template, $variables);
        }

        if (in_array($template, $this->getOobSwapTemplates())) {
            return null;
        }

        $this->oobSwapTemplates[] = $template;

        return Craft::$app->getView()->renderTemplate($template, $variables);
    }

    /**
     * Returns the templates that initiated out-of-band swaps in the current request, including the original component template.
     */
    private function getOobSwapTemplates(): array
    {
        if ($this->oobSwapTemplates === null) {
            $this->oobSwapTemplates = [];
            $template = Sprig::$core->requests->getValidatedParam('sprig:template');
            if ($template) {
                $this->oobSwapTemplates[] = $template;
            }
        }

        return $this->oobSwapTemplates;
    }
}
