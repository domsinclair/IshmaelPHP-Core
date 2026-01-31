# IshmaelPHP Semantic Framework Map

This map explains the intent and responsibility of different directories and files within the IshmaelPHP framework and a typical Ishmael application.

## 1. Application Zones (User Land)

These are the areas where you typically write your application code.

- `/app/Domain`  
  **Intent**: Core business logic, entities, and invariants.  
  **Guidance**: Keep this free of framework dependencies. Use POPOs (Plain Old PHP Objects).
  
- `/app/Infrastructure`  
  **Intent**: Implementation details for external services (Persistence, Mailing, API Clients).  
  **Guidance**: Code here should implement interfaces defined in `/app/Domain`.

- `/app/Http`  
  **Intent**: Web entry points, Controllers, Middleware, and Request/Response handling.

- `/Modules`  
  **Intent**: Self-contained feature units. This is the **primary extension point** of Ishmael.  
  **Guidance**: Each module should ideally contain its own `Domain`, `Infrastructure`, `Http`, and `Resources` (Views/Assets).
  **Reference**: Use `ish:make:module` to scaffold new modules.

- `/config`  
  **Intent**: Declarative application configuration.  
  **Guidance**: Use `.env` for environment-specific values and reference them here.

- `/public`  
  **Intent**: Web server document root. Contains `index.php` and public assets.

## 2. Framework Core (Internals)

Located under `vendor/ishmael/framework/`. These are read-only internals.

- `/app/Core`  
  **Intent**: The engine of the framework. Contains the DI Container, Router, Module Manager, and base classes (Model, Controller).
  
- `/bootstrap`  
  **Intent**: Framework lifecycle and bootstrapping.
  
- `/bin`  
  **Intent**: CLI binaries, including the `ish` command.

- `/Resources`  
  **Intent**: Default framework resources like error views, stubs, and translations.

## 3. Extension & Packaging

- `/feature-packs`  
  **Intent**: Reusable, modular extensions that can be shared across projects.
  **Guidance**: Can be licensed or unlicensed. Managed via `ish:feature-pack:*` tools.

## 4. Documentation & Metadata

- `/Docs`  
  **Intent**: Project-specific documentation.
- `/.ai-manifesto.md`  
  **Intent**: AI-specific instructions and coding standards for this project.
