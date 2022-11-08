<?php

namespace putyourlightson\sprig\components;

use Craft;
use putyourlightson\sprig\base\Component;

/**
 * This component solves using CSRF tokens in multiple components on the first cached request.
 * @see https://github.com/putyourlightson/craft-sprig/issues/279
 */
class RefreshOnLoad extends Component
{
    /**
     * @var string|null
     */
    public ?string $selector = null;

    /**
     * @inheritdoc
     */
    public function render(): string
    {
        if (self::getIsInclude()) {
            return '';
        }

        // Get a CSRF token to ensure that the cookie is set
        Craft::$app->request->getCsrfToken();

        // Fall back to the default CSS class
        $selector = $this->selector ?? '.sprig-component';

        return <<<EOD
            <script>
                for (const component of htmx.findAll('$selector')) {
                    htmx.trigger(component, 'refresh');
                }
            </script>
        EOD;
    }
}
