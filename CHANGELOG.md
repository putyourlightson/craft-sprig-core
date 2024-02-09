# Release Notes for Sprig Core

## 3.0.0 - Unreleased

> {warning} Flash messages have been deprecated when calling controller actions. The `message` variable should be used instead of `flashes.notice` and `flashes.error`.

### Added

- Added compatibility with Craft 5.0.0.

### Changed

- Flash messages have been deprecated when calling controller actions. The `message` variable should be used instead of `flashes.notice` and `flashes.error`.
- Requests that accept JSON responses are now used when running controller actions ([#301](https://github.com/putyourlightson/craft-sprig/issues/301)).
