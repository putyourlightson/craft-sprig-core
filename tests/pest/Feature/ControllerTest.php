<?php

/**
 * Tests the component controller.
 */

use craft\web\Response;
use craft\web\View;
use putyourlightson\sprig\Sprig;
use putyourlightson\sprig\test\mockclasses\controllers\TestController;
use yii\web\BadRequestHttpException;

beforeEach(function() {
    Sprig::bootstrap();
    Sprig::$core->controllerMap['test'] = TestController::class;
    Craft::$app->view->setTemplateMode(View::TEMPLATE_MODE_SITE);
    Craft::$app->getView()->setTemplatesPath(Craft::getAlias('@putyourlightson/sprig/test/templates'));
    Craft::$app->getView()->clear();
});

test('Render', function() {
    Craft::$app->getRequest()->setQueryParams([
        'sprig:template' => Craft::$app->security->hashData('_empty'),
    ]);

    /** @var Response $response */
    $response = Sprig::$core->runAction('components/render');

    expect($response->data)
        ->toBe('');
});

test('Render null', function() {
    Craft::$app->getRequest()->setQueryParams([
        'sprig:template' => Craft::$app->security->hashData('_action'),
        'sprig:action' => Craft::$app->security->hashData('sprig-core/test/get-null'),
    ]);

    /** @var Response $response */
    $response = Sprig::$core->runAction('components/render');

    expect($response->data)
        ->toContain('success:false');
});

test('Render array', function() {
    Craft::$app->getRequest()->setQueryParams([
        'sprig:template' => Craft::$app->security->hashData('_action'),
        'sprig:action' => Craft::$app->security->hashData('sprig-core/test/get-array'),
    ]);

    /** @var Response $response */
    $response = Sprig::$core->runAction('components/render');

    expect($response->data)
        ->toContain('success:true');
});

test('Render model', function() {
    Craft::$app->getRequest()->setQueryParams([
        'sprig:template' => Craft::$app->security->hashData('_action'),
        'sprig:action' => Craft::$app->security->hashData('sprig-core/test/get-model'),
    ]);

    /** @var Response $response */
    $response = Sprig::$core->runAction('components/render');

    expect($response->data)
        ->toContain('success:true');
});

test('Controller action success', function() {
    Craft::$app->getRequest()->setQueryParams([
        'sprig:template' => Craft::$app->security->hashData('_action'),
        'sprig:action' => Craft::$app->security->hashData('sprig-core/test/save-success'),
    ]);

    /** @var Response $response */
    $response = Sprig::$core->runAction('components/render');

    expect($response->data)
        ->toContain('success:true', 'id:1', 'flashes[notice]:Success');
});

test('Controller action error', function() {
    Craft::$app->getRequest()->setQueryParams([
        'sprig:template' => Craft::$app->security->hashData('_action'),
        'sprig:action' => Craft::$app->security->hashData('sprig-core/test/save-error'),
    ]);

    /** @var Response $response */
    $response = Sprig::$core->runAction('components/render');

    expect($response->data)
        ->toContain('success:false', 'flashes[error]:the_error_message', 'model');
});

test('Render without params', function() {
    Craft::$app->getRequest()->setQueryParams([]);
    Sprig::$core->runAction('components/render');
})->throws(BadRequestHttpException::class);

