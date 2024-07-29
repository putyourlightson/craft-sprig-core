<?php

/**
 * Tests the Sprig variable methods.
 */

use putyourlightson\sprig\services\ComponentsService;
use putyourlightson\sprig\Sprig;
use putyourlightson\sprig\variables\SprigVariable;

beforeEach(function() {
    Sprig::bootstrap();
    Craft::$app->getResponse()->getHeaders()->removeAll();
});

test('Trigger events as strings', function() {
    getVariable()->triggerEvents('a,b,c');

    expect(Craft::$app->getResponse()->getHeaders()->get('HX-Trigger'))
        ->toEqual('{"a":"a","b":"b","c":"c"}');
});

test('Trigger events as arrays', function() {
    getVariable()->triggerEvents(['a', 'b', 'c']);

    expect(Craft::$app->getResponse()->getHeaders()->get('HX-Trigger'))
        ->toEqual('{"a":"a","b":"b","c":"c"}');
});

test('Trigger events as arrays with key-value pairs', function() {
    getVariable()->triggerEvents(['a' => 'a', 'b' => 'b', 'c' => 'c']);

    expect(Craft::$app->getResponse()->getHeaders()->get('HX-Trigger'))
        ->toEqual('{"a":"a","b":"b","c":"c"}');
});

test('Trigger multiple events', function() {
    getVariable()->triggerEvents('a');
    getVariable()->triggerEvents(['b']);
    getVariable()->triggerEvents(['c' => 'c']);

    expect(Craft::$app->getResponse()->getHeaders()->get('HX-Trigger'))
        ->toEqual('{"a":"a","b":"b","c":"c"}');
});

test('Trigger refresh', function() {
    getVariable()->triggerRefresh('#test');

    expect(Craft::$app->getResponse()->getHeaders()->get('HX-Trigger'))
        ->toEqual('{"refresh":{"target":"#test"}}');
});

test('Trigger refresh and multiple events', function() {
    getVariable()->triggerRefresh('#test');
    getVariable()->triggerEvents('a');

    expect(Craft::$app->getResponse()->getHeaders()->get('HX-Trigger'))
        ->toEqual('{"refresh":{"target":"#test"},"a":"a"}');
});

test('Trigger refresh on load', function() {
    $selector = '.' . ComponentsService::SPRIG_CSS_CLASS;
    getVariable()->triggerRefreshOnLoad();

    $html = Craft::$app->getView()->getBodyHtml();

    expect($html)
        ->toContain("htmx.findAll('$selector'))");
});

test('Trigger refresh on load with selector', function() {
    $selector = '#test';
    getVariable()->triggerRefreshOnLoad($selector);

    $html = Craft::$app->getView()->getBodyHtml();

    expect($html)
        ->toContain("htmx.findAll('$selector'))");
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
