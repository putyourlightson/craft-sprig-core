# Release Notes for Sprig Core

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
