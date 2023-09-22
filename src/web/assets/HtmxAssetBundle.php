<?php

namespace putyourlightson\sprig\web\assets;

use Craft;
use craft\web\AssetBundle;

class HtmxAssetBundle extends AssetBundle
{
    public function init(): void
    {
        $this->sourcePath = '@putyourlightson/sprig/resources/lib/htmx/1.9.5';

        $this->js = [
            'htmx.js',
        ];

        parent::init();
    }
}