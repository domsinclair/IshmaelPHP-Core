# Ishmael MCP Server: Help & Usage Guide

This guide explains how to get the most out of the Ishmael Model Context Protocol (MCP) server. These features are designed to help AI assistants understand the Ishmael framework and automate common development tasks.

## 1. AI-Driven Guidance (Prompts)

The MCP server provides "Prompts" that you can use to guide your AI assistant's behavior.

### `ishmael:best-practices`
Use this prompt to instruct the AI assistant on how to correctly work with the Ishmael framework. It emphasizes:
*   **CLI First**: Favoring `ish` commands over manual code generation.
*   **Modular Design**: Adhering to the Module-First architecture.
*   **Validation**: Regularly validating the environment.

### `ishmael:setup-project`
If you are starting a new project or onboarding a team member, use this prompt. It provides a step-by-step walkthrough:
1.  Running environment validation.
2.  Initializing the database migrations.
3.  Scaffolding the first module.
4.  Configuring the IDE.

---

## 2. IDE Automation Tools

### `ide:setup-run-configs`
This tool automatically sets up IDE "Run Configurations," allowing you to execute `ish` commands directly from your IDE's interface.
*   **Supported IDEs**: PhpStorm (more coming soon).
*   **Action**: Generates `.xml` files in `.idea/runConfigurations/` for common tasks like `help`, `migrate`, and `make:module`.
*   **How to use**: Ask your AI assistant to "Set up my IDE run configurations for Ishmael."

---

## 3. Contextual Knowledge (Resources)

Resources allow the AI to "read" specific information about your project to provide better answers.

### CLI Metadata (`ish://cli/commands`)
Exposes a full map of available Ishmael CLI commands. This ensures the AI knows exactly what flags and arguments each command supports without you having to look them up.

### Project Health (`ish://project/health`)
Provides a summary of the project's current state, including:
*   Environment validation results.
*   Pending database migrations.
*   Framework version info.

### AI Context (`ish://docs/ai-context`)
If you have a specialized `.ai-context.md` file in your project root, the AI will read it to understand your specific architectural decisions or project-specific rules.

---

## 4. Documentation Management

### `docs/sync`
Keep your project's documentation up to date by synchronizing it from the official source.
*   **Source**: Can pull from GitHub (default) or a local path.
*   **Usage**: Ask the AI to "Sync my documentation" to download the latest Ishmael guides into your `Docs/` folder.

---

## 5. Summary of Available Tools

| Tool Name | Purpose |
| :--- | :--- |
| `ish:make:module` | Scaffolds a new module skeleton. |
| `ish:migrate` | Applies or rolls back database migrations. |
| `ish:env:validate` | Checks environment requirements. |
| `ide:setup-run-configs` | Generates IDE-specific shortcuts for CLI commands. |
| `docs/sync` | Updates local documentation files. |
| `project/info` | Provides core project structure info. |
