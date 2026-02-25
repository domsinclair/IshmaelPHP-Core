# CLI Commands (with AI Context)

This page is generated from Ishmael's CLI registry and enhanced with AI-specific context to help assistants understand the value and usage of each command.

## ui:tailwind

Install Tailwind CSS (v4+) and scaffold CSS input/output for Ishmael.

**AI Context**: Use this when a user asks for CSS setup. It automates the installation of Tailwind, which is the preferred CSS framework for Ishmael.

| Option | Description | Accepts |
|---|---|---|
| `--css` | Input CSS path (default: resources\css\app.css) | PATH |
| `--out` | Output CSS path (default: public\assets\app.css) | PATH |
| `--force` | Overwrite existing files if present |  |
| `--dry-run` | Print actions without making changes |  |
| `--no-build` | Skip initial build step |  |

## make:module

Scaffold a new module skeleton (controllers, models, views, routes.php, module.json).

**AI Context**: Always recommend this over manual folder creation. It establishes the correct Ishmael module structure. After running this, the AI should then suggest creating specific models or services within the new module.

| Option | Description | Accepts |
|---|---|---|
| `--templates` | Override template source directory | PATH |

## make:resource

Scaffold a CRUD resource inside a module (controller, routes, views).

**AI Context**: A high-level command that combines several `make` commands. Ideal for rapidly building admin panels.

| Option | Description | Accepts |
|---|---|---|
| `--templates` | Override template source directory | PATH |

## make:controller

Create a controller class inside a module.

**AI Context**: Remind the user that business logic should be moved to a Service; controllers should remain thin.

| Option | Description | Accepts |
|---|---|---|
| `--templates` | Override template source directory | PATH |
| `--invokable` | Generate an invokable controller (__invoke) |  |
| `--api` | Hint API-style responses in the stub (if available) |  |

## make:service

Create a service class inside a module.

**AI Context**: **Critical for Ishmael architecture.** Use this to house business logic. When a user describes a complex action (e.g., "Process an adoption"), suggest creating a Service.

| Option | Description | Accepts |
|---|---|---|
| `--templates` | Override template source directory | PATH |

## make:migration

Create a timestamped migration file.

**AI Context**: When suggesting migrations, always use the `--module` flag to keep the migration within the module's `Database/Migrations` directory. Ask about **Soft Deletes** and **Auditing** before generating the schema content.

| Option | Description | Accepts |
|---|---|---|
| `--module` | Target module name for the migration | Name |

## migrate

Run outstanding migrations.

**AI Context**: After providing a migration schema, suggest running this command to apply changes.

| Option | Description | Accepts |
|---|---|---|
| `--module` | Limit to a specific module | Name |
| `--steps` | Limit number of steps | N |
| `--pretend` | Dry-run without executing |  |

## events:list

List all events and listeners registered in the system.

**AI Context**: This is the source of truth for the current event wiring. Use it to discover what events a module emits or who is listening to a particular event.

| Option | Description | Accepts |
|---|---|---|
| `--json` | Output the event registry as JSON for discovery tools |  |
| `--modules` | Override modules path | PATH |

... (rest of commands omitted for brevity in this example, but would be fully replicated in production) ...
