<?php

namespace putyourlightson\sprig\web\assets;

use Craft;
use craft\web\AssetBundle;

class HtmxAssetBundle extends AssetBundle
{
    public function init(): void
    {
        $this->sourcePath = '@putyourlightson/sprig/resources/lib/htmx/1.9.6';

        if (Craft::$app->getConfig()->env === 'dev') {
            $this->js = [
                'htmx.js'
            ];
        } else {
            $this->js = [
                'htmx.min.js'
            ];
        }

        parent::init();
    }
}