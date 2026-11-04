# Changelog

All notable changes to this plugin will be documented in this file.

## [1.0.6] - 2026-11-04

**Compatibility note:** This version is compatible from **Moodle 4.5** to **Moodle 5.3**.

### Changed
- `$plugin->supported` extended to `[405, 503]` for the Moodle 5.3 release line. CI tests Moodle 4.5, 5.0, 5.1, 5.2 and 5.3

### Fixed
- Recommended course names are escaped only once, so a name with `&` is no longer shown as `&amp;` (#7)
- A failure of the recommendations service no longer breaks the page that hosts the block: the error is logged with `debugging()` and the block shows a warning notification (#8)
- The paging bar links keep the user on the page that hosts the block (Dashboard or site home) instead of always sending them to the Dashboard (#9)

## [1.0.5] - 2026-10-05

**Compatibility note:** This version is compatible from **Moodle 4.5** to **Moodle 5.2**.

### Changed
- `$plugin->supported` extended to `[405, 502]` for the Moodle 5.2 release line. CI tests Moodle 4.5, 5.0, 5.1 and 5.2.

### Fixed
- The view selector uses the Bootstrap 5 `form-select` class instead of `custom-select`, which Moodle 5.0 only keeps in the deprecated Bootstrap 4 compatibility layer (MDL-80519).
