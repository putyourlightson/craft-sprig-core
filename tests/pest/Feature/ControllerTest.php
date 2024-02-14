<?php

/**
 * Tests the component controller.
 */

use craft\web\Response;
use craft\web\View;
use putyourlightson\sprig\Sprig;
use putyourlightson\sprig\test\controllers\TestController;
use yii\web\BadRequestHttpException;

beforeEach(function() {
    Sprig::bootstrap();
    Sprig::$core->controllerMap['test'] = TestController::class;
    Craft::$app->view->setTemplateMode(View::TEMPLATE_MODE_SITE);
    Craft::$app->getView()->setTemplatesPath(Craft::getAlias('@putyourlightson/sprig/test/templates'));
});

test('Render', function() {
    Craft::$app->getRequest()->setQueryParams([
        'sprig:config' => Craft::$app->security->hashData('{"template":"_empty"}'),
    ]);

    /** @var Response $response */
    $response = Sprig::$core->runAction('components/render');

    expect($response->data)
        ->toBe('');
});

test('Render null', function() {
    Craft::$app->getRequest()->setQueryParams([
        'sprig:config' => Craft::$app->security->hashData('{"template":"_action"}'),
        'sprig:action' => Craft::$app->security->hashData('sprig-core/test/get-null'),
    ]);

    /** @var Response $response */
    $response = Sprig::$core->runAction('components/render');

    expect($response->data)
        ->toContain(
            'sprig.isSuccess:false',
            'sprig.isError:true',
            'sprig.message:empty',
            'sprig.modelId:null',
            'model:null',
        )
        // TODO: remove in Sprig 4.
        ->toContain(
            'success:false',
        );
});

test('Render array', function() {
    Craft::$app->getRequest()->setQueryParams([
        'sprig:config' => Craft::$app->security->hashData('{"template":"_action"}'),
        'sprig:action' => Craft::$app->security->hashData('sprig-core/test/get-array'),
    ]);

    /** @var Response $response */
    $response = Sprig::$core->runAction('components/render');

    expect($response->data)
        ->toContain(
            'sprig.isSuccess:true',
            'sprig.isError:false',
            'sprig.message:empty',
            'sprig.modelId:null',
            'model:null',
        )
        // TODO: remove in Sprig 4.
        ->toContain(
            'success:true',
        );
});

test('Render model', function() {
    Craft::$app->getRequest()->setQueryParams([
        'sprig:config' => Craft::$app->security->hashData('{"template":"_action"}'),
        'sprig:action' => Craft::$app->security->hashData('sprig-core/test/get-model'),
    ]);

    /** @var Response $response */
    $response = Sprig::$core->runAction('components/render');

    expect($response->data)
        ->toContain(
            'sprig.isSuccess:true',
            'sprig.isError:false',
            'sprig.message:empty',
            'sprig.modelId:null',
            'model:null',
        )
        // TODO: remove in Sprig 4.
        ->toContain(
            'success:true',
        );
});

test('Controller action success', function() {
    Craft::$app->getRequest()->setQueryParams([
        'sprig:config' => Craft::$app->security->hashData('{"template":"_action"}'),
        'sprig:action' => Craft::$app->security->hashData('sprig-core/test/save-success'),
    ]);

    /** @var Response $response */
    $response = Sprig::$core->runAction('components/render');

    expect($response->data)
        ->toContain(
            'sprig.isSuccess:true',
            'sprig.isError:false',
            'sprig.message:Success',
            'sprig.modelId:1',
            'model:null',
        )
        // TODO: remove in Sprig 4.
        ->toContain(
            'success:true',
            'id:1',
            'flashes[success]:Success',
        );
});

test('Controller action error', function() {
    Craft::$app->getRequest()->setQueryParams([
        'sprig:config' => Craft::$app->security->hashData('{"template":"_action"}'),
        'sprig:action' => Craft::$app->security->hashData('sprig-core/test/save-error'),
    ]);

    /** @var Response $response */
    $response = Sprig::$core->runAction('components/render');

    expect($response->data)
        ->toContain(
            'sprig.isSuccess:false',
            'sprig.isError:true',
            'sprig.message:Error',
            'sprig.modelId:null',
            'model:model',
        )
        // TODO: remove in Sprig 4.
        ->toContain(
            'success:false',
            'flashes[error]:Error',
            'model',
        );
});

test('Render without params', function() {
    Craft::$app->getRequest()->setQueryParams([]);
    Sprig::$core->runAction('components/render');
})->throws(BadRequestHttpException::class);

