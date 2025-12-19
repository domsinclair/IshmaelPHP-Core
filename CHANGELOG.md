# Changelog
All notable changes to this project will be documented in this file.
The format is based on Keep a Changelog and this project adheres to Semantic Versioning.

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
