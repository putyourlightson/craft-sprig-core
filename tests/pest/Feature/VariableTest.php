<?php

/**
 * Tests the Sprig variable methods.
 */

use craft\helpers\Html;
use putyourlightson\sprig\services\ComponentsService;
use putyourlightson\sprig\Sprig;
use putyourlightson\sprig\variables\SprigVariable;

beforeEach(function() {
    Sprig::bootstrap();
    Craft::$app->getResponse()->getHeaders()->removeAll();
    Craft::$app->getView()->clear();
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

describe('Sprig request', function() {
    beforeEach(function() {
        Craft::$app->getRequest()->getHeaders()->set('HX-Request', true);
        Craft::$app->getView()->clear();
    });

    test('Swap OOB', function() {
        getVariable()->swapOob('#test1', '_component');

        expect(Craft::$app->getView()->getBodyHtml())
            ->toContain(Html::beginTag('div', ['s-swap-oob' => 'innerHTML:#test1']));
    });

    test('Swap OOB with variables', function() {
        getVariable()->swapOob('#test1', '_component', ['number' => 12345]);

        expect(Craft::$app->getView()->getBodyHtml())
            ->toContain(Html::beginTag('div', ['s-swap-oob' => 'innerHTML:#test1']))
            ->toContain(12345);
    });

    test('Trigger refresh', function() {
        getVariable()->triggerRefresh('#test1');

        expect(Craft::$app->getResponse()->getHeaders()->get('HX-Trigger-After-Swap'))
            ->toEqual('{"refresh":{"target":"#test1"}}');
    });

    test('Trigger refresh and other events', function() {
        getVariable()->triggerRefresh('#test2');
        getVariable()->triggerEvents('a', 'swap');

        expect(Craft::$app->getResponse()->getHeaders()->get('HX-Trigger-After-Swap'))
            ->toEqual('{"refresh":{"target":"#test2"},"a":"a"}');
    });

    test('Trigger refresh with variables', function() {
        getVariable()->triggerRefresh('#test3', ['a' => 'b']);

        expect(Craft::$app->getView()->getBodyHtml())
            ->toContain(Html::hiddenInput('a', 'b'))
            ->and(Craft::$app->getResponse()->getHeaders()->get('HX-Trigger-After-Swap'))
            ->toEqual('{"refresh":{"target":"#test3"}}');
    });
});

describe('Sprig include', function() {
    beforeEach(function() {
        Craft::$app->getRequest()->getHeaders()->set('HX-Request', false);
        Craft::$app->getView()->clear();
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
