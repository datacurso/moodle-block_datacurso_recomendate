# Changelog

All notable changes to this plugin will be documented in this file.

## [1.0.5] - 2026-10-05

**Compatibility note:** This version is compatible from **Moodle 4.5** to **Moodle 5.2**.

### Changed
- `$plugin->supported` extended to `[405, 502]` for the Moodle 5.2 release line. CI tests Moodle 4.5, 5.0, 5.1 and 5.2.

### Fixed
- The view selector uses the Bootstrap 5 `form-select` class instead of `custom-select`, which Moodle 5.0 only keeps in the deprecated Bootstrap 4 compatibility layer (MDL-80519).
