# Versioning and Backwards Compatibility Policy

Ishmael follows Semantic Versioning (SemVer) 2.0.0:

- MAJOR (x.0.0): Breaking changes, removals of deprecated APIs.
- MINOR (0.x.0): Backwards compatible features and improvements.
- PATCH (0.0.x): Backwards compatible bug fixes and documentation/CI-only changes.

Backwards Compatibility (BC) Guarantees for v0.x
- Until v1.0.0, we aim to avoid breaking changes within a MINOR release, but reserve the right to make necessary adjustments before 1.0.
- Public APIs documented in the guides and reference are considered stable for the v0.1.x series.
- Any change that may impact users will be called out in the CHANGELOG and release notes.

Deprecation Policy
- When feasible, APIs will be soft-deprecated in a MINOR release with clear alternatives and removal targeted for the next MAJOR.
- Deprecated APIs will be annotated in PHPDoc and documented.

Supported PHP Versions and Platforms
- Minimum PHP version: 8.2
- Tested in CI: PHP 8.2, 8.3, 8.4 on Ubuntu Linux and Windows
- macOS is expected to work but is currently community-supported (no CI job).

Release Cadence
- Patch releases: as needed for fixes.
- Minor releases: when notable new features or improvements are ready and tested.

Changelog & Releases
- Releases and changelogs are automated via Release Please.
- Conventional Commits are used to infer semantic version bumps and generate notes.

Conventional Commits (short form)
- feat: a new feature (may trigger a MINOR bump)
- fix: a bug fix (PATCH)
- docs, ci, test, refactor, perf, build, chore: housekeeping (usually PATCH or no release)
- feat!: indicates a breaking change (MAJOR)

See also: CONTRIBUTING.md for commit and PR guidelines.

---

## Using Git tags with Composer (important)

Composer resolves package versions from Git tags when a package is required from a VCS repository (GitHub/GitLab/etc.). You usually do NOT put a static `version` field in `composer.json`. Instead, you create and push SemVer tags like `v1.0.0`, `v1.0.1`, `v1.1.0`.

The starter app can then update the core by running `composer update ishmael/framework`, and Composer will pick the latest tag that matches the version constraint in the starter app’s `composer.json` (for example `^1.0`).

### Quick start: create and push your first tag

1. Ensure `main` (or your release branch) contains the desired code and is pushed.
2. Create an annotated tag locally:
   - `git tag -a v1.0.0 -m "v1.0.0: initial stable release"`
3. Push the tag to the origin:
   - `git push origin v1.0.0`
   - Or push all local tags: `git push --tags`

That’s it. Consumers can now install/update to `v1.0.0` subject to their version constraints.

### Why annotated tags?

- Prefer annotated tags (`-a` or `-s`) over lightweight tags. Annotated tags carry a message, date, and tagger identity, and are considered release-grade. Composer recognizes both, but annotated tags are best practice.
- If you sign releases, use `-s` (GPG-signed): `git tag -s v1.0.0 -m "v1.0.0"`.

### Pre-releases and release candidates

- You can publish pre-releases using SemVer pre-release labels:
  - Examples: `v1.0.0-beta.1`, `v1.0.0-rc.1`.
- Composer resolves these when the consumer constraint allows them. With the default stability, pre-releases are considered lower stability than the final.
- If the starter app uses `"minimum-stability": "dev"` with `"prefer-stable": true` (as the core does), final releases are preferred; pre-releases are used only when explicitly constrained.

### Development branches (no tags yet)

- Our `composer.json` defines a branch alias: `dev-main` → `1.x-dev`.
- Consumers can depend on the development line before tags exist by requiring either:
  - `"ishmael/framework": "dev-main"`, or
  - `"ishmael/framework": "1.x-dev"` (thanks to the branch alias).
- Once you start tagging `v1.x.y`, switch consumers to a proper tagged constraint like `^1.0`.

### Common tasks

- List tags: `git tag --list`
- Delete a local tag: `git tag -d v1.0.0`
- Delete a remote tag: `git push origin :refs/tags/v1.0.0`
- Retag a release (if you tagged the wrong commit):
  1) Delete the wrong tag locally and remotely (see above)
  2) Create the new tag pointing at the correct commit
  3) Push the corrected tag

### Choosing version constraints in the starter app

- `^1.2` → allows any `1.x` (non-breaking) releases: `1.2.0` up to but not including `2.0.0`.
- `~1.2.0` → allows `>=1.2.0 <1.3.0`.
- Exact pin: `1.2.3`.
- Dev line: `1.x-dev` or `dev-main`.

After changing constraints, run `composer update ishmael/framework` in the starter app. Note that `composer install` respects the lockfile and will not upgrade if `composer.lock` is present.

### CI/CD and authentication for private repositories

- If your core repository is private, the consumer environment must authenticate:
  - SSH key with repo access, or
  - `auth.json` with GitHub token (`github-oauth`), or GitHub Packages credentials when using the Composer registry.
- Ensure CI pushes tags as part of a release workflow when appropriate.

### Release checklist (manual)

1. Ensure tests, static analysis, and docs checks pass in CI.
2. Update `CHANGELOG.md` (or rely on automated release tooling).
3. Merge to `main`.
4. Tag the release (`git tag -a vX.Y.Z -m "vX.Y.Z: summary"`).
5. Push the tag (`git push origin vX.Y.Z`).
6. Verify the tag is visible on the host (GitHub/GitLab) and that `composer show -a ishmael/framework` in a clean environment lists it.
7. In the starter app, run `composer update ishmael/framework`.

### PhpStorm one-click tag command (optional)

For convenience, this repo includes a small helper you can wire into PhpStorm’s Command Line Tool Support to create and push tags and optionally auto-bump a stored version:

- Script: `tools/release/new-tag.ps1` (Windows PowerShell; wrapper: `tools/release/new-tag.bat`)
- PhpStorm tool template XML: `extras/phpstorm/release-tool.xml`

How to set up in PhpStorm:

1) Tools → Command Line Tool Support → + → From file → Choose `extras/phpstorm/release-tool.xml`.
2) The tool alias will be `release` and points to `tools/release/new-tag.bat`.

Usage examples (Tools → Run Command… or double-Shift → type `release`):

- Explicit version and message:
  - `release tag -Version 1.0.0 -Message "v1.0.0: initial stable release"`
- Auto-bump from stored version (.idea/release-version.txt) by a patch:
  - `release tag -Bump patch`
- Bump minor and push all tags:
  - `release tag -Bump minor -PushAll`

Notes:
- The script stores/reads the last version at `.idea/release-version.txt` by default so it can auto-increment on subsequent runs. You can change the location with `-StorePath`.
- Tag prefix defaults to `v`. Change with `-Prefix` if needed.
- It creates an annotated tag and pushes it (by default only that tag; use `-PushAll` to push all local tags).
