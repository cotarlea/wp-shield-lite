# Changelog

All notable changes to WP Shield Lite are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Continuous integration: GitHub Actions workflow that runs a PHP syntax check
  on PHP 7.4–8.3 for every push and pull request.

## [1.2.0]

### Added
- Strong-password policy enforced on registration, profile updates and password
  resets, with a configurable minimum length and a common-password blocklist.

## [1.1.0]

### Added
- Email alert when an administrator logs in from an IP address not seen before,
  with a configurable recipient.

## [1.0.0]

### Added
- Initial release: brute-force login protection, one-click hardening toggles,
  security response headers and an activity log.

[Unreleased]: https://github.com/cotarlea/wp-shield-lite/compare/v1.2.0...HEAD
[1.2.0]: https://github.com/cotarlea/wp-shield-lite/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/cotarlea/wp-shield-lite/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/cotarlea/wp-shield-lite/releases/tag/v1.0.0
