# Release Notes for Sprig Core

## 2.12.4 - 2024-10-23

### Changed

- Updated htmx to version 2.0.3 ([changelog](https://github.com/bigskysoftware/htmx/blob/master/CHANGELOG.md#203---2024-10-03)).
- The fetch function is now called inside an event listener when calling `sprig.triggerRefreshOnLoad()`, making it more robust.
- Reverted some variable method name changes to improve autocompletion in PhpStorm.

## 2.12.3 - 2024-09-19

### Fixed

- Fixed a missing import ([#16](https://github.com/putyourlightson/craft-sprig-core/pull/16)).

## 2.12.2 - 2024-09-17

### Changed

- Reverted the removal of the `sprig.script` function ([#391](https://github.com/putyourlightson/craft-sprig/issues/391).

## 2.12.1 - 2024-09-11

### Fixed

- Fixed the ability to pass a JSON encoded string into the `sprig.triggerEvents()` function ([#15](https://github.com/putyourlightson/craft-sprig-core/issues/15).

## 2.12.0 - 2024-08-29

### Added

- Added the ability to assign an ID to a component by passing a string as the third argument to the `sprig()` function.

### Changed

- Updated htmx to version 2.0.2 ([changelog](https://github.com/bigskysoftware/htmx/blob/master/CHANGELOG.md#202---2024-08-12)).

### Fixed

- Fixed the positioning of the JavaScript output by the `sprig.triggerRefreshOnLoad` function.

<<<<<<< HEAD
### Deprecated

- Deprecated `sprig.getIsBoosted`, `sprig.getIsError`, `sprig.getIsHistoryRestoreRequest`, `sprig.getIsInclude`, `sprig.getIsRequest`, `sprig.getIsSuccess`. Use `sprig.isBoosted`, `sprig.isError`, `sprig.isHistoryRestoreRequest`, `sprig.isInclude`, `sprig.isRequest`, `sprig.isSuccess` instead.

## 2.11.1 - 2024-08-20
=======
## 3.4.1 - 2024-08-20
>>>>>>> 0d34434 (Revert variable method name changes)

### Fixed

- Fixed a bug that was breaking the `Sprig` variable in the browser console with `devMode` enabled.

## 2.11.0 - 2024-08-20

### Changed

- The `sprig.registerJs(js)` function now executes the registered JavaScript after htmx settles, and is now the recommended way of outputting JavaScript in Sprig components.
- Components no longer render markup added via `{% html %}`, `{% css %}` and `{% js %}` tags during Sprig requests.

## 2.10.2 - 2024-08-12

### Fixed

- Fixed a bug in which asset bundles were being registered and rendered in control panel Sprig requests ([#383](https://github.com/putyourlightson/craft-sprig/issues/383)).

## 2.10.1 - 2024-08-08

### Fixed

- Fixed CSRF tokens not being sent with requests using a method other than `GET`.

## 2.10.0 - 2024-08-08

### Added

- Added the component configurations to a `Sprig` variable in the browser console when `devMode` is enabled.
- Added the ability to pass any value into `s-method`, including the `put`, `patch` and `delete` verbs.
- Added the `sprig.registerJs(js)` function.

### Changed

- The `sprig.triggerRefresh(selector, variables)` function now prevents cyclical requests.

## 2.9.1 - 2024-08-06

### Changed

- The `sprig.swapOob()` function now accepts a string in addition to a template to be rendered, in the second argument.

## 2.9.0 - 2024-08-01

### Added

- Added the `sprig.swapOob(selector, template, variables)` function that swaps a template out-of-band.
- Added the `sprig.triggerRefresh(selector, variables)` function that triggers a refresh on a Sprig component.

### Changed

- Components now render markup added via `{% html %}`, `{% css %}` and `{% js %}` tags.
- The `sprig.triggerRefreshOnLoad(selector)` function now appends output to the end of the body and should be called using `{% do sprig.triggerRefreshOnLoad(selector) %}`.

## 2.8.0 - 2024-07-15

> [!IMPORTANT]
> This update introduces htmx 2. [Read about the changes →](https://putyourlightson.com/articles/sprig-htmx-2)

### Added

- Added the [s-inherit](https://putyourlightson.com/plugins/sprig#s-inherit) attribute that allows you to control and enable automatic attribute inheritance for child nodes, if it has been disabled by default.

### Changed

- Updated htmx to version 2.0.1 ([2.0.0 release notes](https://htmx.org/posts/2024-06-17-htmx-2-0-0-is-released/)).
- The htmx file is now output even when Sprig components are used inside of `{% cache %}` tags ([#329](https://github.com/putyourlightson/craft-sprig/issues/329)).

### Removed

- Removed the `s-sse` and `s-ws` attributes.

## 2.7.4 - 2024-05-15

### Changed

- Made it possible to pass a fully namespaced component class into the `sprig()` function ([#14](https://github.com/putyourlightson/craft-sprig-core/issues/14)).

## 2.7.3 - 2024-04-30

### Fixed

- Fixed a bug in which control panel messages were not being correctly set.

## 2.7.2 - 2024-04-26

### Changed

- Updated htmx to version 1.9.12 ([changelog](https://github.com/bigskysoftware/htmx/blob/master/CHANGELOG.md#1912---2024-04-17)).

## 2.7.1 - 2024-04-21

### Fixed

- Fixed a bug in which some `sprig` variables were incorrectly persisting across requests ([#363](https://github.com/putyourlightson/craft-sprig/issues/363)).

## 2.7.0 - 2024-03-26

### Added

- Added the `sprig.isSuccess` variable.
- Added the `sprig.isError` variable.
- Added the `sprig.message` variable.
- Added the `sprig.modelId` variable.

### Deprecated

- Deprecated the `success` variable. Use `sprig.isSuccess` or `sprig.isError` instead.
- Deprecated the `flashes` variable. Use `sprig.message` instead.
- Deprecated the `id` variable. Use `sprig.modelId` instead.

## 2.6.7 - 2024-02-08

### Fixed

- Fixed a bug with the set user password action ([#348](https://github.com/putyourlightson/craft-sprig/issues/348)).

## 2.6.6 - 2023-12-15

### Fixed

- Fixed the check for the existence of an event introduced in Craft 4.5.0 properly ([#344](https://github.com/putyourlightson/craft-sprig/issues/344)).

## 2.6.5 - 2023-12-14

### Fixed

- Fixed a bug in which an event introduced in Craft 4.5.0 was being used without first checking for its existence ([#344](https://github.com/putyourlightson/craft-sprig/issues/344)).

## 2.6.4 - 2023-12-12

### Added

- Added the ability to pass script tag attributes via the `sprig.registerScript()` and `sprig.setRegisterScript()` functions ([#338](https://github.com/putyourlightson/craft-sprig/issues/338)).
- Added the `ComponentsService::setConfig()` method.

### Changed

- Updated htmx to version 1.9.9 ([changelog](https://github.com/bigskysoftware/htmx/blob/master/CHANGELOG.md#199---2023-11-21)).

## 2.6.3 - 2023-09-29

### Added

- Added the `sprig.registerScript()` function ([#329](https://github.com/putyourlightson/craft-sprig/issues/329)).

### Changed

- Renamed the `sprig.setAddScript()` function to `sprig.setRegisterScript()`.

## 2.6.2 - 2023-09-27

### Changed

- Changed the asset bundle source to use a relative path instead of an alias.

## 2.6.1 - 2023-09-26

### Changed

- Renamed the `sprig.setAddScripts()` function to `sprig.setAddScript()`.

## 2.6.0 - 2023-09-26

### Added

- Added the Sprig component generator that scaffolds PHP components via a console command (`php craft make sprig-component --path sprig/components`).
- Added the [s-cache](https://putyourlightson.com/plugins/sprig#s-cache) attribute that allows you to specify if and for how long a request should be cached locally in the browser.
- Added the [s-on:*](https://putyourlightson.com/plugins/sprig#s-on) attribute that allows you to respond to events directly on an element.
- Added the [s-disabled-elt](https://putyourlightson.com/plugins/sprig#s-disabled-elt) attribute that allows you to specify elements that will have the disabled attribute added to them for the duration of the request.
- Added the `sprig.htmxVersion` function.
- Added the `sprig.setAddScripts()` function.
- Added friendly invalid variable exceptions that are shown when the [Canary](https://plugins.craftcms.com/canary) plugin is installed.

### Changed

- The htmx script is now automatically injected into the end of the page whenever a Sprig component is created, meaning that the `sprig.script` function is no longer required and can be safely removed.
- Updated htmx to version 1.9.6 ([changelog](https://github.com/bigskysoftware/htmx/blob/master/CHANGELOG.md#196---2023-09-22)).
- Simplified and improved invalid variable error messages.
- Invalid variable error messages are now only shown when `devMode` is turned on.

### Fixed

- Fixed the response status code that is sent when required request parameters are not supplied ([#325](https://github.com/putyourlightson/craft-sprig/issues/325)).
- Fixed a potential security issue.

### Deprecated

- Deprecated the `sprig.script` function. It is no longer required and can be safely removed.
- Deprecated the `s-on` attribute. Use the `s-on:*` attribute instead.

## 2.5.2 - 2023-05-01

### Changed

- Updated htmx to version 1.9.2 ([changelog](https://github.com/bigskysoftware/htmx/blob/master/CHANGELOG.md#192---2023-04-28)).

## 2.5.1 - 2023-04-25

### Fixed

- Fixed a bug in which the htmx file was not being published even if it did not already exist locally ([#305](https://github.com/putyourlightson/craft-sprig/issues/305)).

## 2.5.0 - 2023-04-23

### Added

- Added the [sprig.setConfig](https://putyourlightson.com/plugins/sprig#sprig-setconfigoptions) template variable that allows you to set [configuration options](https://htmx.org/docs/#config) for htmx (via a meta tag).
- Added the [s-on](https://putyourlightson.com/plugins/sprig#s-on) attribute that allows you to respond to events directly on an element.

### Changed

- Updated htmx to version 1.9.0 ([release notes](https://htmx.org/posts/2023-04-11-htmx-1-9-0-is-released/)).
- The htmx file is now loaded locally rather than from a CDN, to reduce dependency on third-party sites ([#303](https://github.com/putyourlightson/craft-sprig/issues/303)).

## 2.4.2 - 2023-03-26

### Changed

- The set user password action is now handled using a JSON request to accommodate its quirks ([#300](https://github.com/putyourlightson/craft-sprig/issues/300)).

## 2.4.1 - 2023-03-03

### Changed

- Updated htmx to version 1.8.6 ([release notes](https://htmx.org/posts/2023-03-02-htmx-1.8.6-is-released/)).

## 2.4.0 - 2023-01-18

### Added

- Added the [s-history](https://putyourlightson.com/plugins/sprig#s-history) attribute that prevents sensitive data being saved to the history cache.

### Changed

- Updated htmx to version 1.8.5 ([release notes](https://htmx.org/posts/2023-01-17-htmx-1.8.5-is-released/)).

## 2.3.0 - 2022-12-08

### Added

- Added the [sprig.triggerRefreshOnLoad](https://putyourlightson.com/plugins/sprig#sprig-triggerrefreshonloadselector) template variable that triggers a refresh event on all components on page load ([#279](https://github.com/putyourlightson/craft-sprig/issues/279)).

## 2.2.0 - 2022-11-07

### Added

- Added the [s-validate](https://putyourlightson.com/plugins/sprig#s-validate) attribute that forces an element to validate itself before it submits a request.
- Added the [sprig.isHistoryRestoreRequest](https://putyourlightson.com/plugins/sprig#sprig-ishistoryrestorerequest) template variable that returns whether the request is for history restoration after a miss in the local history cache a client-side redirect without reloading the page.

### Changed

- Updated htmx to version 1.8.4 ([changelog](https://github.com/bigskysoftware/htmx/blob/master/CHANGELOG.md#184---2022-11-05)).

## 2.1.1 - 2022-10-08

### Fixed

- Fixed a bug in which Sprig requests were failing in live preview requests ([#269](https://github.com/putyourlightson/craft-sprig/issues/269)).

## 2.1.0 - 2022-10-05

### Added

- Added the [s-replace-url](https://putyourlightson.com/plugins/sprig#s-replace-url) attribute that allows you to replace the current url of the browser location history.
- Added the [s-select-oob](https://putyourlightson.com/plugins/sprig#s-select-oob) attribute that selects one or more elements from a server response to swap in via an out-of-band swap.
- Added the [sprig.location(url)](https://putyourlightson.com/plugins/sprig#sprig-locationurl) template variable that triggers a client-side redirect without reloading the page.
- Added the [sprig.replaceUrl(url)](https://putyourlightson.com/plugins/sprig#sprig-replaceurlurl) template variable that replaces the current URL in the location bar.
- Added the [sprig.reswap(value)](https://putyourlightson.com/plugins/sprig#sprig-reswapvalue) template variable that allows you to change the swap behaviour.

### Changed

- Updated htmx to version 1.8.0 ([release notes](https://htmx.org/posts/2022-07-12-htmx-1.8.0-is-released/)).

### Fixed

- Fixed a bug in the `sprig.pushUrl()` template variable.

## 2.0.6 - 2022-09-25

### Fixed

- Fixed a bug in which tags containing line breaks were not being parsed ([#264](https://github.com/putyourlightson/craft-sprig/issues/264)).

## 2.0.5 - 2022-06-30

### Fixed

- Fixed a bug in which nested components were inheriting their parent's component type ([#243](https://github.com/putyourlightson/craft-sprig/issues/243)).

## 2.0.4 - 2022-05-25

### Changed

- Optimised the regular expression pattern in the component parser by matching all possible Sprig attributes only.

## 2.0.3 - 2022-05-25

### Changed

- Improved the parser to help prevent backtick limit errors by enforcing reasonable backtick limits.

## 2.0.2 - 2022-05-25

### Changed

- Improved the parser to return matches even if a backtick limit error is encountered.

## 2.0.1 - 2022-05-17

### Fixed

- Fixed the format of the `sprig.triggerEvents` function to work with htmx.

## 2.0.0 - 2022-05-04

### Added

- Added compatibility with Craft 4.

### Changed

- Restyled invalid variable error messages.

### Removed

- Removed the deprecated `sprig.element`, `sprig.elementName`, `sprig.elementValue` and `sprig.eventTarget` tags.
- Removed the deprecated `s-vars` attribute.
