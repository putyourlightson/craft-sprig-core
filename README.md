<p align="center"><img width="150" src="https://raw.githubusercontent.com/khalwat/craft-sprig-core/develop/src/resources/img/icon-01.svg"><img width="150" src="https://raw.githubusercontent.com/khalwat/craft-sprig-core/develop/src/resources/img/icon-02.svg"><img width="150" src="https://raw.githubusercontent.com/khalwat/craft-sprig-core/develop/src/resources/img/icon-03.svg"><img width="150" src="https://raw.githubusercontent.com/khalwat/craft-sprig-core/develop/src/resources/img/icon-04.svg"></p>

# Sprig Core for Craft CMS

This module provides the core functionality for the [Sprig plugin](https://github.com/putyourlightson/craft-sprig), a reactive Twig component framework for Craft CMS. If you are developing a Craft plugin/module and would like to use Sprig in the control panel, then you can require this package to give you its functionality, without requiring that the site has the Sprig plugin installed. 

First require the package in your plugin/module's `composer.json` file.

```json
{
    "require": {
        "putyourlightson/craft-sprig-core": "^1.0.0"
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
