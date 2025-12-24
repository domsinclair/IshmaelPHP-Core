# Changelog
All notable changes to this project will be documented in this file.
The format is based on Keep a Changelog and this project adheres to Semantic Versioning.

## [0.7.8](https://github.com/domsinclair/IshmaelPHP-Core/compare/v0.7.7...v0.7.8) (2025-12-24)


### Miscellaneous Chores

* add archive configuration ([5f52d3c](https://github.com/domsinclair/IshmaelPHP-Core/commit/5f52d3c417c48b6d0bec6f7f53fb3075278f1e29))

## [0.7.7](https://github.com/domsinclair/IshmaelPHP-Core/compare/v0.7.6...v0.7.7) (2025-12-24)


### Miscellaneous Chores

* More  CI Integration fixes ([84fcc1f](https://github.com/domsinclair/IshmaelPHP-Core/commit/84fcc1f8bd0fb9940407f5e9b96d79522c28be7e))

## [0.7.6](https://github.com/domsinclair/IshmaelPHP-Core/compare/v0.7.5...v0.7.6) (2025-12-24)


### Miscellaneous Chores

* More Minor CI Integration fixes ([540b742](https://github.com/domsinclair/IshmaelPHP-Core/commit/540b742534c9e7430afd6b43d804a029b149500c))

## [0.7.5](https://github.com/domsinclair/IshmaelPHP-Core/compare/v0.7.4...v0.7.5) (2025-12-24)


### Miscellaneous Chores

* Latest CI Integration fixes ([7a88e38](https://github.com/domsinclair/IshmaelPHP-Core/commit/7a88e381f44fae7ec378bdff54bcf13eaf49c18d))
* Minor CI Integration fixes ([0b18b3c](https://github.com/domsinclair/IshmaelPHP-Core/commit/0b18b3cfa479d7558b945bacdb2f3f2f9845ce4f))

## [0.7.4](https://github.com/domsinclair/IshmaelPHP-Core/compare/v0.7.3...v0.7.4) (2025-12-24)


### Miscellaneous Chores

* Continue fixing php CI Integration ([0a9377e](https://github.com/domsinclair/IshmaelPHP-Core/commit/0a9377e5e82448b2c0716ffc7015245102455203))

## [0.7.3](https://github.com/domsinclair/IshmaelPHP-Core/compare/v0.7.2...v0.7.3) (2025-12-23)


### Bug Fixes

* CI integration phpstan.neon.dist ([33970c6](https://github.com/domsinclair/IshmaelPHP-Core/commit/33970c6fbac88585c984fb88aee0097096c7681b))

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
