<?php

/**
 * Tests the creation of components.
 */

use craft\elements\Entry;
use putyourlightson\sprig\errors\FriendlyInvalidVariableException;
use putyourlightson\sprig\services\ComponentsService;
use putyourlightson\sprig\Sprig;
use Twig\Markup;
use yii\base\Model;
use yii\web\BadRequestHttpException;

beforeEach(function() {
    Sprig::bootstrap();
    Craft::$app->getConfig()->getGeneral()->devMode = false;
    Craft::$app->getView()->setTemplatesPath(CRAFT_TEST_PATH . '/templates');
});

test('Creating a component with variables and options', function() {
    $markup = Sprig::$core->components->create(
        '_component',
        ['number' => 15],
        [
            'id' => 'abc',
            's-trigger' => 'load',
            's-vars' => 'limit:1',
            's-push-url' => 'new-url',
            's-cache' => 10,
        ]
    );

    expect((string)$markup)
        ->toContain(
            'id="abc"',
            'data-hx-include="this"',
            'data-hx-trigger="load"',
            'limit:1',
            'data-hx-push-url="new-url"',
            'data-hx-headers="{&quot;S-Cache&quot;:&quot;10&quot;}"',
            'xyz 15',
        );
});

test('Creating an empty component', function() {
    $markup = Sprig::$core->components->create('_empty');

    expect((string)$markup)
        ->toContain('data-hx-get=');
});

test('Creating an invalid component throws an exception', function() {
    Sprig::$core->components->create('_no-component');
})->throws(BadRequestHttpException::class);

test('Creating a component that refreshes on load', function() {
    $selector = ComponentsService::SPRIG_CSS_CLASS;
    $object = Sprig::$core->components->createObject('RefreshOnLoad');

    Craft::$app->getRequest()->getHeaders()->set('HX-Request', 'true');

    expect($object->render())
        ->toContain("htmx.findAll('.$selector'))");
});

test('Creating a component that refreshes on load with a selector', function() {
    $selector = '.test-class';
    $object = Sprig::$core->components->createObject(
        'RefreshOnLoad',
        ['selector' => $selector]
    );

    Craft::$app->getRequest()->getHeaders()->set('HX-Request', 'true');

    expect($object->render())
        ->toContain('htmx.findAll(\'' . $selector . '\'))');
});

test('Creating an object from a component', function() {
    require CRAFT_TEST_PATH . '/components/TestComponent.php';

    $object = Sprig::$core->components->createObject(
        'TestComponent',
        ['number' => 15]
    );

    expect($object->render())
        ->toContain('xyz 15');
});

test('Creating an object from no component returns `null`', function() {
    $object = Sprig::$core->components->createObject('NoComponent');

    expect($object)
        ->toBeNull();
});

test('Creating a component with an entry variable throws an exception', function() {
    Sprig::$core->components->create('_component', ['entry' => new Entry()]);
})->throws(FriendlyInvalidVariableException::class);

test('Creating a component with a model variable throws an exception', function() {
    Sprig::$core->components->create('_component', ['model' => new Model()]);
})->throws(FriendlyInvalidVariableException::class);

test('Creating a component with an object variable throws an exception', function() {
    Sprig::$core->components->create('_component', ['object' => (object)[]]);
})->throws(FriendlyInvalidVariableException::class);

test('Creating a component with an array variable containing a model throws an exception', function() {
    Sprig::$core->components->create('_component', ['array' => [new Model()]]);
})->throws(FriendlyInvalidVariableException::class);

test('Creating a component with a nested array variable is allowed', function() {
    $markup = Sprig::$core->components->create('_component', ['array' => [[[1, 2], 2]]]);

    expect($markup)
        ->toBeInstanceOf(Markup::class);
});
