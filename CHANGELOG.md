# Release Notes for Sprig Core

## 1.4.1 - 2022-06-30
### Fixed
- Fixed a bug in which nested components were inheriting their parent's component type ([#243](https://github.com/putyourlightson/craft-sprig/issues/243)).

## 1.4.0 - 2022-04-30
### Added
- Added the [s-listen](https://putyourlightson.com/plugins/sprig#s-listen) attribute that allows you to specify one or more components that when refreshed, should trigger a refresh of the current element.

## 1.3.2 - 2022-03-24
### Fixed
- Fixed a bug that was throwing an error when using the `paginate` variable with an ActiveRecord query ([#6](https://github.com/putyourlightson/craft-sprig-core/issues/6)).
- Fixed nested components being parsed twice, resulting in duplicate attributes ([#208](https://github.com/putyourlightson/craft-sprig/issues/208)).

## 1.3.1 - 2022-03-15
### Fixed
- Fixed an issue that caused htmx 1.7.0 to log console errors in some edge cases ([#202](https://github.com/putyourlightson/craft-sprig/issues/202)).

## 1.3.0 - 2022-03-01
### Added
- Added the [s-sync](https://putyourlightson.com/plugins/sprig#s-sync) attribute that allows you to synchronize AJAX requests between multiple elements.
- Added the [s-disinherit](https://putyourlightson.com/plugins/sprig#s-disinherit) attribute that allows you to control attribute inheritance.

### Changed
- Updated htmx to version 1.7.0 ([release notes](https://htmx.org/posts/2022-02-22-htmx-1.7.0-is-released/)).

## 1.2.1 - 2022-02-22
### Changed
- The response format is now explicitly set to HTML, to avoid a scenario in which it might be interpreted as JSON.

## 1.2.0 - 2022-02-01
### Added
- Added the [sprig.isBoosted](https://putyourlightson.com/plugins/sprig#sprig.isBoosted) template variable that returns whether this is a boosted request (requires htmx 1.6.0 or later).
- Added the [sprig.retarget()](https://putyourlightson.com/plugins/sprig#sprig.retargettarget) template variable that overrides the element to target via a CSS selector (requires htmx 1.6.1 or later).

### Changed
- Updated htmx to version 1.6.1 ([release notes](https://htmx.org/posts/2021-11-22-htmx-1.6.1-is-released/)).

## 1.1.6 - 2021-11-08
### Fixed
- Fixed a bug when parsing tags when the tag name is followed by a tab ([#183](https://github.com/putyourlightson/craft-sprig/issues/183)). 

## 1.1.5 - 2021-10-25
### Fixed
- Fixed the parsing of empty `s-val:` values to ensure the value is maintained ([#178](https://github.com/putyourlightson/craft-sprig/issues/178)). 

## 1.1.4 - 2021-10-22
### Fixed
- Fixed an issue in which attributes with spaces before or after the `=` were not being correctly parsed ([#178](https://github.com/putyourlightson/craft-sprig/issues/178)). 

## 1.1.3 - 2021-10-21
### Fixed
- Fixed a bug in which attributes could be double encoded in nested components ([#176](https://github.com/putyourlightson/craft-sprig/issues/176), [#178](https://github.com/putyourlightson/craft-sprig/issues/178)). 

## 1.1.2 - 2021-10-20
### Fixed
- Fixed a bug in which using `s-action` could throw an exception when parsed ([#177](https://github.com/putyourlightson/craft-sprig/issues/177)). 

## 1.1.1 - 2021-10-20
### Fixed
- Fixed a bug in which using `s-vals` with JSON encoded variables could throw an exception when parsed ([#176](https://github.com/putyourlightson/craft-sprig/issues/176)). 

## 1.1.0 - 2021-10-19
### Changed
- Increased the minimum required Craft version to 3.3.0.
- Removed the dependency on the DOMDocument library.
- The `s-val:*` attribute can now contain square brackets, for example `s-val:fields[field-handle]=""`.
- General performance optimisations.

### Fixed
- Fixed a bug in which comments and script tags containing `sprig` could throw an exception when parsed ([#3](https://github.com/putyourlightson/craft-sprig-core/issues/3)). 

## 1.0.3 - 2021-10-14
### Fixed
- Fixed multibyte character strings not being correctly converted ([#173](https://github.com/putyourlightson/craft-sprig/issues/173)). 

## 1.0.2 - 2021-10-11
### Added
- Optimised the performance and overhead of parsing large Sprig components ([#2](https://github.com/putyourlightson/craft-sprig-core/issues/2) ❤️@nystudio107).

## 1.0.1 - 2021-10-05
### Fixed
- Fixed an error in the CLI due to an undefined alias ([#170](https://github.com/putyourlightson/craft-sprig/issues/170)).

## 1.0.0 - 2021-10-04
- Initial release.
