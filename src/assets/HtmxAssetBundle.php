<?php

namespace putyourlightson\sprig\assets;

use craft\web\AssetBundle;
use putyourlightson\sprig\services\ComponentsService;

class HtmxAssetBundle extends AssetBundle
{
    public $sourcePath = '@putyourlightson/sprig/resources/lib/htmx/' . ComponentsService::HTMX_VERSION;

    public function init(): void
    {
        $this->js = [
            'htmx.min.js',
        ];

        parent::init();
    }
}
