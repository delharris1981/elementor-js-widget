# Changelog

All notable changes to this project will be documented in this file.

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
