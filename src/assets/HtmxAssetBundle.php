<?php

namespace putyourlightson\sprig\assets;

use craft\web\AssetBundle;
use putyourlightson\sprig\services\ComponentsService;

class HtmxAssetBundle extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = __DIR__ . '/../resources/lib/htmx/' . ComponentsService::HTMX_VERSION;

    /**
     * @inheritdoc
     */
    public $js = [
        'htmx.min.js',
    ];
}
