<?php

/**
 * Tests the creation of components.
 */

use craft\elements\Entry;
use putyourlightson\sprig\errors\FriendlyInvalidVariableException;
use putyourlightson\sprig\Sprig;
use Twig\Markup;
use yii\base\Model;
use yii\web\BadRequestHttpException;

beforeEach(function() {
    Sprig::bootstrap();
    Craft::$app->getConfig()->getGeneral()->devMode = false;
    Craft::$app->getView()->setTemplatesPath(Craft::getAlias('@putyourlightson/sprig/test/templates'));
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

test('Creating a component with an ID passed in as a string', function() {
    $markup = Sprig::$core->components->create(
        '_component',
        [],
        'abc'
    );

    expect((string)$markup)
        ->toContain(
            'id="abc"',
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

test('Creating an object from a component', function() {
    require Craft::getAlias('@putyourlightson/sprig/test/components/TestComponent.php');

    $object = Sprig::$core->components->createObject(
        'TestComponent',
        ['number' => 15]
    );

    expect($object->render())
        ->toContain('xyz 15');
});

test('Creating an object from a namespaced component class', function() {
    require Craft::getAlias('@putyourlightson/sprig/test/components/TestNamespacedComponent.php');

    $object = Sprig::$core->components->createObject(
        'custom\\sprig\\components\\TestNamespacedComponent',
    );

    expect($object)
        ->not()->toBeNull();
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
