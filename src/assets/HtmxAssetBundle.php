<?php

namespace putyourlightson\sprig\assets;

use craft\web\AssetBundle;
use putyourlightson\sprig\services\ComponentsService;

class HtmxAssetBundle extends AssetBundle
{
    public function init(): void
    {
        $this->sourcePath = '@putyourlightson/sprig/resources/lib/htmx/' . ComponentsService::HTMX_VERSION;

        $this->js = [
            'htmx.min.js',
        ];

        parent::init();
    }
}
