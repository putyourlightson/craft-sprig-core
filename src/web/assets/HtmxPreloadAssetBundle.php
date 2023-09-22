<?php

namespace putyourlightson\sprig\web\assets;

use Craft;
use craft\web\AssetBundle;

class HtmxPreloadAssetBundle extends AssetBundle
{
    public function init(): void
    {
        $this->sourcePath = '@putyourlightson/sprig/resources/lib/htmx/1.9.5/ext';

        $this->js = [
            'preload.js'
        ];

        parent::init();
    }

}