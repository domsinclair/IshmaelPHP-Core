# CLI Reference

Auto-generated from command metadata.

| Command | Synopsis | Description |
|---|---|---|
| `help` | ish help | Show CLI help. |
| `version` | ish --version \| -V \| version | Print the Ishmael CLI version. |
| `make:module` | ish make:module <Name> | Scaffold a new module skeleton (controllers, models, views, routes.php, module.json). |
| `make:migration` | ish make:migration <name> [--module=Name] | Create a new migration file. |
| `migrate` | ish migrate [--module=Name] [--steps=N] [--pretend] [--force] | Run outstanding migrations. |
| `migrate:rollback` | ish migrate:rollback [--module=Name] [--steps=N] | Rollback the last batch or specified steps. |
| `status` | ish status [--module=Name] | Show migration status. |
| `seed` | ish seed [--module=Name] [--class=FQCN] [--force] [--env=ENV] | Run database seeders. |
| `db:seed` | ish db:seed [--module=Name] [--class=FQCN] [--force] [--env=ENV] | Alias of seed; runs database seeders. |
| `db:reset` | ish db:reset [--purge] [--force] | Reset database identities/sequences; with --purge truncate all tables FK-safely. |
| `route:list` | ish route:list [--method=GET] [--module=Name] | List registered routes. |
| `route:cache` | ish route:cache [--force] | Compile and cache route map. |
| `route:clear` | ish route:clear | Clear route cache file. |
| `config:cache` | ish config:cache | Compile and cache configuration. |
| `config:clear` | ish config:clear | Clear configuration cache. |
| `cache:clear` | ish cache:clear [--stats] | Clear application cache. |
| `docs:generate` | ish docs:generate | Generate documentation (CLI and Module references). |
| `docs:check-links` | ish docs:check-links [--fail-on-warn] | Scan site/ output for broken internal links (href/src). |

## Options

### help

(No options)

### version

(No options)

### make:module

| Option | Takes Value | Default | Description |
|---|---|---|---|
| <Name> | yes |  | Module name (StudlyCase preferred). |

### make:migration

| Option | Takes Value | Default | Description |
|---|---|---|---|
| <name> | yes |  | Migration name (e.g., CreatePostsTable). |
| --module | yes |  | Target module (optional). |

### migrate

| Option | Takes Value | Default | Description |
|---|---|---|---|
| --module | yes |  | Restrict to a module. |
| --steps | yes | 0 | Limit number of steps. |
| --pretend | no | false | Dry run (print SQL without executing). |
| --force | no | false | Run in production without confirmation. |

### migrate:rollback

| Option | Takes Value | Default | Description |
|---|---|---|---|
| --module | yes |  | Restrict to a module. |
| --steps | yes | 1 | Number of steps to rollback (default 1). |

### status

| Option | Takes Value | Default | Description |
|---|---|---|---|
| --module | yes |  | Restrict to a module. |

### seed

| Option | Takes Value | Default | Description |
|---|---|---|---|
| --module | yes |  | Restrict to a module. |
| --class | yes |  | Seeder class FQCN. |
| --force | no | false | Run in production without confirmation. |
| --env | yes |  | Environment name (e.g., ci). |

### db:seed

| Option | Takes Value | Default | Description |
|---|---|---|---|
| --module | yes |  | Restrict to a module. |
| --class | yes |  | Seeder class FQCN. |
| --force | no | false | Run in production without confirmation. |
| --env | yes |  | Environment name (e.g., ci). |

### db:reset

| Option | Takes Value | Default | Description |
|---|---|---|---|
| --purge | no | false | Truncate all user tables and reset identities. |
| --force | no | false | Run in production without confirmation. |

### route:list

| Option | Takes Value | Default | Description |
|---|---|---|---|
| --method | yes |  | HTTP method filter. |
| --module | yes |  | Module name filter. |

### route:cache

| Option | Takes Value | Default | Description |
|---|---|---|---|
| --force | no | false | Strip non-cacheable entries instead of failing. |

### route:clear

(No options)

### config:cache

(No options)

### config:clear

(No options)

### cache:clear

| Option | Takes Value | Default | Description |
|---|---|---|---|
| --stats | no | false | Print driver and item count after clearing. |

### docs:generate

(No options)

### docs:check-links

| Option | Takes Value | Default | Description |
|---|---|---|---|
| --fail-on-warn | no | false | Exit non-zero even if only warnings found. |

