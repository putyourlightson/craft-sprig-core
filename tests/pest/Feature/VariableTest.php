<?php

/**
 * Tests the Sprig variable methods.
 */

use putyourlightson\sprig\Sprig;
use putyourlightson\sprig\variables\SprigVariable;

beforeEach(function() {
    Sprig::bootstrap();
});

test('Create refresh on load component', function() {
    $html = getVariable()->triggerRefreshOnLoad();

    expect((string)$html)
        ->toContain('RefreshOnLoad');
});

test('Set config', function() {
    $config = ['x' => 1, 'y' => 2];
    getVariable()->setConfig($config);
    $content = str_replace('"', '&quot;', json_encode($config));

    expect(Craft::$app->getView()->metaTags['htmx-config'])
        ->toBe('<meta name="htmx-config" content="' . $content . '">');
});

function getVariable(): SprigVariable
{
    return new SprigVariable();
}
