<?php

/**
 * Tests the handling of component requests.
 */

use putyourlightson\sprig\models\ConfigModel;
use putyourlightson\sprig\Sprig;
use yii\web\BadRequestHttpException;

beforeEach(function() {
    Sprig::bootstrap();
});

test('Get variables', function() {
    Craft::$app->request->setQueryParams([
        'page' => 1,
        '_secret' => 'xyz',
        'sprig:template' => 't',
    ]);

    $variables = Sprig::$core->requests->getVariables();

    expect($variables)
        ->toHaveKey('page', 1);
});

test('Get validated config values in query param', function() {
    Craft::$app->request->setQueryParams([
        'sprig:template' => Craft::$app->getSecurity()->hashData('_component'),
    ]);

    $template = Sprig::$core->requests->getValidatedParam('sprig:template');

    expect($template)
        ->toBe('_component');
});

test('Get validated config action value in body param', function() {
    Craft::$app->request->setQueryParams([
        'sprig:action' => Craft::$app->getSecurity()->hashData('x/y/x'),
    ]);

    $action = Sprig::$core->requests->getValidatedParam('sprig:action');

    expect($action)
        ->toBe('x/y/x');
});

test('Get default cache duration', function() {
    Craft::$app->getRequest()->getHeaders()->set('S-Cache', 'true');

    expect(Sprig::$core->requests->getCacheDuration())
        ->toBe(Sprig::$core->requests::DEFAULT_CACHE_DURATION);
});

test('Get cache duration provided an integer', function() {
    Craft::$app->getRequest()->getHeaders()->set('S-Cache', 10);

    expect(Sprig::$core->requests->getCacheDuration())
        ->toBe(10);
});

test('Get cache duration provided a decimal', function() {
    Craft::$app->getRequest()->getHeaders()->set('S-Cache', 10.2);

    expect(Sprig::$core->requests->getCacheDuration())
        ->toBe(10);
});

test('Get cache duration when false', function() {
    Craft::$app->getRequest()->getHeaders()->set('S-Cache', false);

    expect(Sprig::$core->requests->getCacheDuration())
        ->toBe(0);
});

test('Get cache duration provided a negative value', function() {
    Craft::$app->getRequest()->getHeaders()->set('S-Cache', -10);

    expect(Sprig::$core->requests->getCacheDuration())
        ->toBe(0);
});

test('Validate data', function() {
    $data = Craft::$app->getSecurity()->hashData('xyz') . 'abc';

    Sprig::$core->requests->validateData($data);
})->throws(BadRequestHttpException::class);
