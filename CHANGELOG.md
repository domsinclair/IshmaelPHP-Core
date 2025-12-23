# Changelog
All notable changes to this project will be documented in this file.
The format is based on Keep a Changelog and this project adheres to Semantic Versioning.

## [0.7.2](https://github.com/domsinclair/IshmaelPHP-Core/compare/v0.7.1...v0.7.2) (2025-12-23)


### Bug Fixes

* CI integration ([0da4cae](https://github.com/domsinclair/IshmaelPHP-Core/commit/0da4cae8fa89f8f9dbcc599e0ec58dbd04908b87))

## [0.7.1](https://github.com/domsinclair/IshmaelPHP-Core/compare/v0.7.0...v0.7.1) (2025-12-23)


### Features

* add GitHub Actions workflows and release-please configuration ([d948726](https://github.com/domsinclair/IshmaelPHP-Core/commit/d948726d9fcd9c48c9ac42ee5ff27b1047a111c8))


### Bug Fixes

* add .htaccess files for Apache server compatibility ([3583d0b](https://github.com/domsinclair/IshmaelPHP-Core/commit/3583d0b1968bdd6ed895f44ed67421c13d59f4e4))
* Again Hopefully the last fix to make release-please work ([26400b5](https://github.com/domsinclair/IshmaelPHP-Core/commit/26400b5b97a3ac2772e6e37a6d3488ac7af276ed))
* Another update to improve linting issues ([4c34607](https://github.com/domsinclair/IshmaelPHP-Core/commit/4c3460715fe8304b7afdb60d24dd67a60a063c7e))
* Another version fix to release-please ([6525365](https://github.com/domsinclair/IshmaelPHP-Core/commit/6525365468f6ecc46af7c4d4b91c6f90d667437c))
* Further update to improve linting issues ([a5dc5b9](https://github.com/domsinclair/IshmaelPHP-Core/commit/a5dc5b9d7cd8835b1857b2dbd848648066d33d95))
* Hopefully the last fix to make release-please work ([1980cb2](https://github.com/domsinclair/IshmaelPHP-Core/commit/1980cb2dbc5688fd9a5c3cc397073ca97a95e3cf))
* Hopefully the last fix to make release-please work ([460df0c](https://github.com/domsinclair/IshmaelPHP-Core/commit/460df0c4e8ac1336c3625561832b2297d2240bb6))
* resolve release-please config error and fix CI workflows ([818f96d](https://github.com/domsinclair/IshmaelPHP-Core/commit/818f96d3cd0201cca5da02f3598fd0989f12b726))
* update release-please config format and fix MCP linting issues ([e080f70](https://github.com/domsinclair/IshmaelPHP-Core/commit/e080f709a0a767c052c2fa29c63e325997a0aed1))

## [0.7.0] - 2025-12-19
### Added
- Public Beta release preparation.
- Integrated MCP test support.
- Enhanced layout path resolution for view rendering.

### Changed
- Moved versioning and changelog management into the core repository for public release.
- Cleaned up example modules (Contacts, Users, TodoApi) to focus on core functionality.

## [0.6.4] - 2025-11-29
### Changed
- Removed example modules from the core repository.

## [0.6.3] - 2025-11-29
### Added
- PhpStorm integration for Ishmael CLI.
- Updated documentation for IDE integration.

## [0.6.2] - 2025-11-29
### Changed
- Updated environment handling to prioritize `.env.example`.
- Enhanced migration CLI flexibility.

## [0.6.1] - 2025-11-29
### Changed
- Minor improvements to environment and migration CLI.

## [0.6.0] - 2025-11-28
### Added
- Comprehensive middleware guide.
- Improved CSRF handling consistency.
- Enhanced views and layouts support with layout path resolution improvements.
- `make:routes` command and enhanced scaffolding for CRUD resources.
- Flash helper APIs and idempotency tokens.
- Controller autowiring with PSR-11 container support.
- `lastInsertId` method to Database class.

### Changed
- Improved seeder dependency resolution.
- Switched to Tailwind CSS v4 CLI for UI scaffolding.

## [0.1.0] - 2025-11-10
- Initial public pre-release of Ishmael v0.1.0.
- Core runtime, routing v2, middleware, route cache, CLI, caching, conditional requests, view/layout helpers, docs generator, and error/logging polish.
