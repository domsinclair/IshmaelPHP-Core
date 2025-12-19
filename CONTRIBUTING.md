# Contributing to Ishmael

Thanks for your interest in contributing!

## Development quick start
- PHP 8.2+ is required.
- Install dependencies:
  - Core: `cd IshmaelPHP-Core && composer install`
- Run code quality and tests:
  - Lint: `composer cs:check`
  - Static analysis: `composer analyse`
  - Tests: `composer test`
  - Docs build check: `composer docs:build`

## Conventional Commits
We use Conventional Commits to automate versioning and changelogs:
- feat: a new feature (MINOR bump)
- fix: a bug fix (PATCH)
- docs, ci, test, refactor, perf, build, chore: housekeeping (PATCH or no release)
- feat!: breaking change (MAJOR)

Examples:
- `feat(router): add named route URL generation`
- `fix(cache): avoid storing private responses`
- `docs: add error handling guide`

## Versioning & BC Policy
- See VERSIONING.md for our SemVer and backwards compatibility guarantees.
- Minimum supported PHP: 8.2.

## Release process (automated)
We use Release Please to automate releases and CHANGELOG.md updates.
1. Land changes on `main` using Conventional Commits in PR titles.
2. Release Please opens a release PR with a generated changelog.
3. When the release PR is merged, a tag and GitHub Release are created (e.g., `v0.1.0`).
4. The Docs workflow builds and publishes the site to GitHub Pages.

To trigger manually, maintainers can run the "Release Please" workflow dispatch.

## Docs
- Docs sources live in `IshmaelPHP-Core/Documentation/`.
- Build locally: `composer docs:build` from `IshmaelPHP-Core/`.
- Serve locally: `composer docs:serve`.

## Code style
- PSR-12 via PHP_CodeSniffer; configuration in `IshmaelPHP-Core/phpcs.xml`.

## Security
- Please do not open public issues for security problems. Contact the maintainers privately.
