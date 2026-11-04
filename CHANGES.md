# Changelog

All notable changes to this plugin will be documented in this file.

## [1.0.6-wp] - 2026-11-04

**Compatibility note:** This version is compatible only with **Moodle Workplace 4.5**.

### Fixed
- Recommended course names are escaped only once, so a name with `&` is no longer shown as `&amp;` (#7)
- A failure of the recommendations service no longer breaks the page that hosts the block: the error is logged with `debugging()` and the block shows a warning notification (#8)
- The paging bar links keep the user on the page that hosts the block (Dashboard or site home) instead of always sending them to the Dashboard (#9)

## [1.0.5-wp] - 2026-10-05

**Compatibility note:** This version targets **Moodle Workplace 4.5** only.

### Changed
- First Workplace release, based on `1.0.5`.
- `$plugin->release` uses the `-wp` suffix and `$plugin->supported` is limited to `[405, 405]`.
- Removed the Moodle.org release workflow (`.github/workflows/moodle-release.yml`); Workplace releases are created manually.
- Plugin CI runs on pushes to `WORKPLACE_405_STABLE` and only against `MOODLE_405_STABLE`.

### Fixed
- The PHPUnit rating fixture sets `tenant_id`, which the Workplace schema of `local_datacurso_ratings` requires.

## [1.0.5] - 2026-10-05

**Compatibility note:** This version is compatible from **Moodle 4.5** to **Moodle 5.2**.

### Changed
- `$plugin->supported` extended to `[405, 502]` for the Moodle 5.2 release line. CI tests Moodle 4.5, 5.0, 5.1 and 5.2.

### Fixed
- The view selector uses the Bootstrap 5 `form-select` class instead of `custom-select`, which Moodle 5.0 only keeps in the deprecated Bootstrap 4 compatibility layer (MDL-80519).
