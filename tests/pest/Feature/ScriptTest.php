<?php

/**
 * Tests the existence and inclusion of the htmx script.
 */

use putyourlightson\sprig\assets\HtmxAssetBundle;
use putyourlightson\sprig\Sprig;

beforeEach(function() {
    Sprig::bootstrap();
    Craft::$app->getAssetManager()->bundles = [];
    Craft::$app->getView()->assetBundles = [];
    Craft::$app->getView()->setTemplatesPath(CRAFT_TEST_PATH . '/templates');
});

test('Script exists locally', function() {
    $bundle = Sprig::$core->components->registerScript();
    $path = $bundle->sourcePath . '/htmx.min.js';
    $url = Craft::$app->getAssetManager()->getPublishedUrl($path, true);
    preg_match('/cpresources(.*?)\?v=/', $url, $matches);
    $path = Craft::getAlias(Craft::$app->config->general->resourceBasePath) . $matches[1];

    expect(file_exists($path))
        ->toBeTrue();
});

test('Script is added', function() {
    Sprig::$core->components->setRegisterScript();
    Sprig::$core->components->create('_component');

    expect(Craft::$app->getAssetManager()->bundles)
        ->toHaveKey(HtmxAssetBundle::class);
});

test('Script is not added when set to `false`', function() {
    Sprig::$core->components->setRegisterScript(false);
    Sprig::$core->components->create('_component');

    expect(Craft::$app->getAssetManager()->bundles)
        ->not()->toHaveKey(HtmxAssetBundle::class);
});

test('Script is added with attributes', function() {
    Sprig::$core->components->setRegisterScript(['data-x' => 1]);
    Sprig::$core->components->create('_component');
    $bundle = Craft::$app->getAssetManager()->getBundle(HtmxAssetBundle::class);

    expect($bundle->jsOptions['data-x'] ?? null)
        ->toBe(1);
});
