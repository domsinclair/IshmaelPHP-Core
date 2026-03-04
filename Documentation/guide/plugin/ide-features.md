# IDE Features and Integration

The Ishmael MCP plugin provides deep integration with the PhpStorm editor, offering more than just a chat interface. It leverages the information gathered by the MCP server to provide real-time feedback, intelligent completion, and easy navigation within your PHP code and configuration files.

## 1. Intelligent Code Completion

The plugin enhances PhpStorm's completion capabilities by providing context-aware suggestions for Ishmael-specific components:

*   **Services:** When using the service container (e.g., `$this->get(...)`), the plugin suggests available services registered in the container.
*   **Configuration:** Provides completion for Ishmael configuration keys within `config/*.php` files.
*   **Events:** Suggests registered application events when using event dispatchers or listeners.

## 2. Real-time Inspections

The plugin includes several custom inspections to catch common Ishmael-related issues early in the development process:

*   **Shadow Dependency Detection:** Identifies when you're using a class or service that isn't explicitly declared in your module's dependencies, preventing runtime errors in modular environments.
*   **Migration Safety Analysis:** Analyzes database migrations for potentially destructive operations (like `DROP TABLE`) and warns you before they are applied.
*   **Database Schema Validation:** Checks your code against the actual database schema discovered by the MCP server, flagging references to non-existent tables or columns.
*   **Route Constraint Validation:** Ensures that Ishmael route constraints (e.g., `whereUuid('id')`) are using valid constraint names.
*   **Manifest Annotator:** Provides real-time validation and error reporting for Ishmael manifest files (`ishmael.json`).

## 3. Advanced Navigation and Line Markers

To help you navigate complex event-driven and modular architectures, the plugin adds specialized line markers in the editor gutter:

*   **Capabilities:** Markers next to classes that implement Ishmael capabilities, allowing you to quickly see where they are defined and used.
*   **Attributes:** Identifies Ishmael attributes used for routing, dependency injection, and event handling.
*   **Events:** Shows markers next to event dispatching and listening points, with the ability to jump between the two.
*   **Go To Declaration:** Support for jumping to the definition of a service directly from its string identifier.

## 4. Documentation Provider

The plugin integrates Ishmael-specific documentation directly into PhpStorm's "Quick Documentation" lookup (`Ctrl+Q` or `F1`). When you hover over an Ishmael class, method, or service identifier, the plugin fetches relevant documentation from the MCP server to provide immediate context without leaving the IDE.

## 5. Live Templates

Pre-configured Live Templates are available for quickly scaffolding common Ishmael code patterns (e.g., controllers, services, migrations). You can access these by typing the template prefix (e.g., `ish:`) and pressing `Tab`.
