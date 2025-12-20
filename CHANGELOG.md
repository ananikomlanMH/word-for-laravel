# Changelog

All notable changes to `word-for-laravel` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2025-12-20

### Added
- Added `<page-number>` support for dynamic page numbering in documents, headers, and footers.
- Added table merge support with `rowspan` and `colspan` attributes in tables.
- Added `CONTRIBUTING.md` guide.

### Changed
- Updated `README.md` with more detailed usage examples and documentation for new features.

## [1.0.0] - 2025-11-23

### Added
- Initial release of Word for Laravel.
- HTML to Word conversion support.
- Support for common HTML elements: `p`, `b`, `i`, `u`, `h1`-`h6`, `table`, `ul`, `ol`, `img`, `a`.
- Support for special tags: `<pagebreak>`, `<wordheader>`, `<wordfooter>`.
- Blade view to Word conversion.
- Multiple output formats support (download, save, string).
