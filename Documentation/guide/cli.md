# Ishmael CLI

The Ishmael CLI provides a single entrypoint to common developer workflows like running migrations and seeders. It lives at `bin/ish` in the repository root and boots the SkeletonApp and Core libraries before dispatching commands.

## Installation

No extra installation is required when working inside this repository. Ensure you have installed SkeletonApp dependencies:

```bash
cd SkeletonApp
composer install
```

On Windows, invoke the CLI with PHP:

```bash
php bin\ish help
```

On macOS/Linux (if executable bit is set):

```bash
./bin/ish help
```

## Usage

```
ish help
ish migrate [--module=Name] [--steps=N] [--pretend]
ish migrate:rollback [--module=Name] [--steps=N]
ish status [--module=Name]
ish seed [--module=Name] [--class=FQCN] [--force] [--env=ENV]
```

### Commands

- help: Prints usage information and examples.
- migrate: Applies pending migrations. When `--module` is omitted, all discovered modules are processed. Use `--steps` to limit how many migrations are applied for a specific module. Use `--pretend` to print what would be done without executing.
- migrate:rollback: Rolls back migrations. With `--module`, rolls back the last `--steps` applied for that module (default 1). Without `--module`, rolls back the last batch applied globally (behavior delegated to the core migrator).
- status: Outputs JSON with migration status information. Accepts `--module` to scope the status view.
- seed: Runs database seeders via the SeedManager. Use `--class` to run a specific seeder (e.g., `DatabaseSeeder`). Use `--module` to scope seeding to a module. Use `--env` to specify the current environment and `--force` to bypass the environment guard for CI or special cases.

## Configuration

The CLI initializes the database adapter using `SkeletonApp/config/database.php`. Ensure this file points to your desired database (e.g., SQLite files in `storage/`).

Environment variables are loaded using the core helper `load_env()`. You can set `APP_ENV` to influence the seeding environment guard.

## Examples

- Apply all pending migrations across all modules:
  ```bash
  php bin\ish migrate
  ```

- Apply two migrations for the HelloWorld module:
  ```bash
  php bin\ish migrate --module=HelloWorld --steps=2
  ```

- Dry run of all migrations to see planned actions:
  ```bash
  php bin\ish migrate --pretend
  ```

- Roll back the last migration for HelloWorld:
  ```bash
  php bin\ish migrate:rollback --module=HelloWorld --steps=1
  ```

- Show status for all modules:
  ```bash
  php bin\ish status
  ```

- Run the default database seeder for all modules (forcing in CI):
  ```bash
  php bin\ish seed --class=DatabaseSeeder --force --env=ci
  ```

## Notes and Roadmap

- Future versions will add migration batching, checksums, and additional scaffolding commands per the Phase‑5 plan.
- Raw programmatic APIs remain available (`Migrator`, `SeedManager`) for scenarios where a CLI is not desired.
