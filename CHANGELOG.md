# Changelog

All notable changes to this project will be documented in this file.

## [1.1.2] - 2026-01-02
### Fixed
- **Improved Popup Lifecycle**: Added immediate fallback detection for scripts rendered inside already-active popups to fix timing issues.
- **Namespaced Events**: Switched to namespaced jQuery events (`.cjs_ID`) to prevent duplicate listener attachments.
- **Execution Guard**: Implemented unique initialization guards per widget instance to eliminate duplicate script execution.

## [1.1.1] - 2026-01-02
### Fixed
- **Duplicate Execution**: Fixed a bug where scripts placed in the "Header" were running twice (once on page load and once on the event).
- **Execution Consistency**: Unified JS processing logic to ensure consistent behavior between early and late script enqueuing.

## [1.1.0] - 2026-01-02
### Added
- **Execution Triggers**: Added "On Elementor Event" triggers (Immediate, Elementor Init, Popup Show, Custom Event).
- **Popup Context**: Added ability to restrict scripts to run only when inside an Elementor Popup.
- **Popup Filtering**: Added support for filtering "On Popup Show" by specific Popup ID.
- **Editor UI**: Enhanced script placement placeholders to show active triggers.

## [1.0.2] - 2026-01-01
### Fixed
- **Security**: Replaced `md5()` with `sha256()` for script hashing to resolve Snyk/CWE-916 warnings.
### Changed
- **Releases**: GitHub releases now use semantic versioning (v1.0.2) instead of commit SHAs.

## [1.0.1] - 2026-01-01
### Added
- GitHub Actions workflow for automatic release generation.
- Manual trigger (`workflow_dispatch`) for releases.

### Fixed
- **Header Placement**: Implemented early parsing of Elementor data to ensure scripts set to "Head" load correctly.
- **Permissions**: Added `contents: write` permissions to GitHub Actions to fix release upload errors (403).
- **Redundancy**: Added duplicate check in script queuing to prevent multiple injections of the same code.

## [1.0.0] - 2026-01-01
### Added
- Initial release.
- Custom JS Widget for Elementor with Inline, Header, and Footer placement.
- GitHub Update Checker for auto-updates.
- PHP 8.2 strict typing and singleton architecture.
