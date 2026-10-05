# Changelog

All notable changes to this plugin will be documented in this file.

## [1.0.5-wp] - 2026-10-05

**Compatibility note:** This version targets **Moodle Workplace 4.5** only.

### Changed
- First Workplace release, based on `1.0.5`.
- `$plugin->release` uses the `-wp` suffix and `$plugin->supported` is limited to `[405, 405]`.
- Removed the Moodle.org release workflow (`.github/workflows/moodle-release.yml`); Workplace releases are created manually.
- Plugin CI runs on pushes to `WORKPLACE_405_STABLE` and only against `MOODLE_405_STABLE`.

## [1.0.5] - 2026-10-05

**Compatibility note:** This version is compatible from **Moodle 4.5** to **Moodle 5.2**.

### Changed
- `$plugin->supported` extended to `[405, 502]` for the Moodle 5.2 release line. CI tests Moodle 4.5, 5.0, 5.1 and 5.2.

### Fixed
- The view selector uses the Bootstrap 5 `form-select` class instead of `custom-select`, which Moodle 5.0 only keeps in the deprecated Bootstrap 4 compatibility layer (MDL-80519).
