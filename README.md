[![Stable Version](https://img.shields.io/packagist/v/putyourlightson/craft-sprig-core?label=stable)]((https://packagist.org/packages/putyourlightson/craft-sprig-core))
[![Total Downloads](https://img.shields.io/packagist/dt/putyourlightson/craft-sprig-core)](https://packagist.org/packages/putyourlightson/craft-sprig-core)

<p align="center"><img width="150" src="https://raw.githubusercontent.com/putyourlightson/craft-sprig-core/v1/src/icon.svg"></p>

# Sprig Core Module for Craft CMS

This module provides the core functionality for the [Sprig plugin](https://github.com/putyourlightson/craft-sprig), a reactive Twig component framework for [Craft CMS](https://craftcms.com/). If you are developing a Craft plugin/module and would like to use Sprig in the control panel, then you can require this package to give you its functionality, without requiring that the site has the Sprig plugin installed. 

First require the package in your plugin/module's `composer.json` file.

```json
{
    "require": {
        "putyourlightson/craft-sprig-core": "^1.0"
    }
}
```

Then bootstrap the module from within your plugin/module's `init` method.

```php
use craft\base\Plugin;
use putyourlightson\sprig\Sprig;

class MyPlugin extends Plugin
{
    public function init()
    {
        parent::init();

        Sprig::bootstrap();
    }
}
```

Then use the Sprig function and tags as normal in your control panel templates.

```twig
{{ sprig('_components/search') }}

{{ sprig.script }}
```

Sprig plugin issues should be reported to https://github.com/putyourlightson/craft-sprig/issues

Sprig plugin changes are documented in https://github.com/putyourlightson/craft-sprig/blob/develop/CHANGELOG.md

## Documentation

Learn more and read the documentation at [putyourlightson.com/plugins/sprig »](https://putyourlightson.com/plugins/sprig)

To see working examples and video tutorials, visit the [learning resources](https://putyourlightson.com/sprig).

## License

This package is licensed for free under the MIT License.

## Requirements

Craft CMS 3.1.19 or later.

## Installation

Install this package via composer.

```
composer require putyourlightson/craft-sprig-core
```

---

Created by [PutYourLightsOn](https://putyourlightson.com/).
