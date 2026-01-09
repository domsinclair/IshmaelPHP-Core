# Ishmael AI Manifesto: The Prime Directive

This document serves as the "Prime Directive" for AI assistants working within the Ishmael framework ecosystem. It outlines the core philosophy, preferred architectural patterns, and safety protocols that must be followed to ensure project integrity and maintainability.

## 1. Core Philosophy: "The Ishmael Way"

Ishmael is designed to be a "Zero-Friction, High-Intelligence" framework. It favors explicit patterns over magic, modularity over monoliths, and CLI-driven workflows over manual scaffolding.

### 1.1 CLI-First Development
*   **Directive**: Always prefer using the `ish` CLI or MCP tools for scaffolding and management.
*   **Reason**: The CLI ensures that files are created in the correct locations with appropriate namespaces, boilerplate, and registrations (e.g., `make:module`, `make:migration`, `make:resource`).
*   **Safety**: Manual file creation often leads to missing autoloader entries or broken conventions.

### 1.2 Module-First Architecture
*   **Directive**: All business logic should reside within a Module (`Modules/`).
*   **Reason**: Ishmael is strictly modular. The `app/` directory is for framework-level overrides and core bootstrap, not for feature logic.
*   **Interdependency**: Modules must explicitly declare their dependencies in `module.php` or `module.json`. Avoid "Shadow Dependencies" (using code from another module without declaring it).
*   **Pattern**: If you need a new feature, start with `ish make:module <Name> --dependencies=Core,Auth`.

### 1.3 Service-Based Decoupling
*   **Directive**: Use the Dependency Injection (DI) container (`app()` helper) to resolve services.
*   **Pattern**: Prefer injecting interfaces or service classes into constructors rather than hardcoding concrete dependencies.

## 2. Safety Protocols

To prevent data loss, environment corruption, or runtime errors, follow these protocols strictly.

### 2.1 Environment Validation
*   **Directive**: Always run `ish:env:validate` before performing major operations (migrations, deployment).
*   **Check**: Ensure `.env` is present and all required keys (e.g., `APP_KEY`, `DB_DATABASE`) are set.

### 2.2 Database Safety
*   **Directive**: Never execute raw SQL for schema changes. Always use migrations (`make:migration`).
*   **Pre-Migration**: Run `ish:migrate --pretend` to preview changes if the environment is sensitive.
*   **Verification**: After migrating, verify the schema using `ish://stubs/Project/Tables.php`.

### 2.3 Route Integrity
*   **Directive**: After adding or modifying routes, verify them using `ish:listRoutes`.
*   **Check**: Ensure the `integrity.valid` flag is `true` for all newly defined routes.

### 2.4 Module Integrity
*   **Directive**: After modifying module manifests or imports, run `ish:modules:check`.
*   **Check**: Ensure no circular dependencies or shadow dependencies are reported.

### 2.5 Scaffolding Preview
*   **Directive**: When generating code using `make:` commands, prefer using the `preview` flag.
*   **Reason**: This allows you to review the generated code and present it to the user for approval before it is committed to the filesystem.
*   **Action**: Use the `preview` parameter in `MakeControllerTool`, `MakeModuleTool`, etc.

## 3. Preferred Coding Patterns

### 3.1 Controllers
*   **Rule**: Keep controllers "thin". They should handle request validation, call a Service, and return a `Response`.
*   **Return Types**: Always type-hint return values as `Ishmael\Core\Http\Response` or `void`.

### 3.2 Services
*   **Rule**: Business logic lives here. Services should be stateless whenever possible.
*   **Naming**: Use the `Service` suffix (e.g., `PostService`).

### 3.3 Models
*   **Rule**: Use Models for data access and persistence logic. Avoid putting complex business logic inside Models.

### 3.4 Knowledge & RAG (Retrieval-Augmented Generation)
*   **Rule**: When creating documentation or content-heavy views, use the semantic helpers to ensure clear structure for AI extraction.
*   **Helpers**:
    *   `knowledge_page(string $title, Closure $content)`: Root container for knowledge assets.
    *   `concept(string $title, Closure $details)`: Focused sections describing a single idea.
    *   `code_example(string $language, Closure $code)`: Explicitly marked code blocks.
*   **Scaffolding**: Use the `--knowledge` flag with `make:module` or `make:view` to automatically apply these patterns.

## 4. MCP Server Interaction

The MCP server is your "Internal Consultant".

*   **Introspect Before Acting**: Use `ish://config/manifest` to see available classes and methods before suggesting a code change.
*   **Use Stubs for Accuracy**: Reference `ish://stubs/Project/Config.php` and `ish://stubs/Project/Tables.php` to avoid "guessing" configuration keys or table names.
*   **Workflow**: When asked to build a feature, start with `ishmael:plan-feature` to ensure the architectural approach aligns with the Ishmael way.

## 5. IDE Integration

The Ishmael ecosystem provides specialized configurations for major IDEs.

### 5.1 Run Configurations & Extensions
*   **PhpStorm**: Run `ide:setup-run-configs --ide=phpstorm` to generate `.idea/runConfigurations` and `jetbrains-mcp.json`.
*   **VSCode**: Run `ide:setup-run-configs --ide=vscode` to generate `.vscode/launch.json`, `.vscode/tasks.json`, `.vscode/extensions.json`, and `jetbrains-mcp.json`.
*   **Directive**: When a user is setting up a new project, suggest running these commands to provide them with integrated CLI access (Help, Migrate, Routes List) and recommended extensions directly from their IDE.

### 5.2 Connecting the MCP Server
To use the Ishmael MCP server with your AI assistant:
1.  **JetBrains**: The IDE should automatically detect the `jetbrains-mcp.json` file.
2.  **VSCode (Cline/Roo Code)**: You may need to manually add the server to your `mcp_settings.json`.
    *   **Configuration**:
        ```json
        "ishmael": {
          "command": "php",
          "args": ["vendor/ishmael/mcp-server/bin/ish-mcp"]
        }
        ```
    *   **Location**: Usually found in global settings, but some extensions are moving toward project-local support. Refer to the specific extension's documentation.
3.  **Claude Desktop**: Add the same configuration to your `claude_desktop_config.json`.

---
*Version 1.2.0*
