# Feature Pack Analysis & Documentation Manifesto

This document instructs AI agents on how to analyze an Ishmael Feature Pack (Module) and generate high-quality, AI-optimized documentation for it.

## The Goal
To create documentation that allows an AI agent to immediately understand a module's capabilities, dependencies, and integration points without having to re-scan the entire source code every time.

## Analysis Process

When asked to document a Feature Pack, follow this systematic approach:

### 1. Structural Inventory
- **`module.php` / `module.json`**: Examine for the module name, description, and dependencies.
- **`routes.php`**: List all entry points (Web and API). Group them by purpose.
- **`config/`**: Identify configuration keys and their default values.

### 2. Dependency Analysis
- Identify which other modules or framework core components this module relies on.
- Look at `composer.json` (if present) for third-party library requirements.

### 3. Service Layer Discovery (Crucial)
Services are the primary way modules interact.
- Locate all classes in `Services/`.
- For each service, identify its **Primary Role**.
- Extract public method signatures, including:
    - Name
    - Parameters (with types)
    - Return type
    - Brief description of the intent.

### 4. Controller & API Surface
- Scan `Controllers/` to understand the user-facing or system-facing API.
- Note if the controllers use specific Middleware (e.g., auth, validation).

### 5. Data Model
- Examine `Models/` and `Database/Migrations/` to understand the state managed by the module.
- Identify core entities and their relationships.

## Documentation Structure Strategy

When generating documentation, use the following template to ensure maximum utility for other agents:

### Technical Summary (AI-First)
Provide a concise, technical overview:
- **Namespace**: `Modules\Name`
- **Main Service**: `Modules\Name\Services\PrimaryService`
- **Key Methods**: List 3-5 most important methods with signatures.
- **Dependencies**: List required modules.

### Functional Overview
Describe what the module achieves in plain language.

### Integration Guide
Provide a "Copy-Paste Ready" example of how to use this module from another module. 
- Show the dependency registration in `module.php`.
- Show the Constructor Injection in a consumer class.
- Show a common method call.

### Extension Points
List any ways the module can be extended (e.g., overriding views, listening to events, implementing interfaces).

## Distribution & Installation

Ishmael Feature Packs are distributed as standard ZIP files via a centralized registry.

### 1. Packaging for Distribution
When a feature is ready for sharing, use the `ish feature:pack` command.
- **Command**: `ish feature:pack <ModuleName> [--out=PATH]`
- **Process**: This command reads the `export` array in `module.php`, validates essential files (`.ish-context.md`, `composer.json`), and bundles the module into a ZIP file.
- **AI Role**: Guide the developer to ensure the `export` list is correct and `.ish-context.md` is complete before suggesting the pack command.

### 2. Installation from Registry
To incorporate a feature from the centralized registry, use the `ish feature:install` command.
- **Command**: `ish feature:install <PathToZip|URL> [--force] [--no-migrate]`
- **Process**: Extracts the ZIP into the `Modules/` directory and, unless suppressed, prompts to run any identified migrations or seeders.
- **AI Role**: Search the centralized registry for capabilities matching the user's needs and suggest installation.

## Specialist Files

If a module is particularly complex, suggest or create these specialist files:

- **`.ish-context.md`**: A compact, high-density reference file specifically for LLM context windows. It should contain only signatures and integration rules, omitting verbose explanations.
- **`MIGRATION_GUIDE.md`**: If the module replaces older logic or has breaking changes.
- **`DATA_DICTIONARY.md`**: For modules with complex database schemas.

## Improving Existing Documentation

When reviewing existing documentation (like in the `Upload` module):
- **Check for Completeness**: Are all public service methods documented?
- **Verify Namespaces**: Ensure all examples use full, correct namespaces.
- **Add Context**: Ensure the "Why" (Architecture) is balanced with the "How" (Implementation).
