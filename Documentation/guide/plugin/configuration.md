# Configuration and Settings

The Ishmael MCP plugin is designed to work with minimal configuration, but you can customize several aspects of its behavior through the PhpStorm settings.

## Accessing Settings

Go to **File | Settings | Tools | Ishmael MCP** (or **PhpStorm | Settings | Tools | Ishmael MCP** on macOS).

## Configuration Options

### 1. MCP Server Connection
By default, the plugin automatically detects the Ishmael MCP server in your project's `vendor` directory. If you're using a custom server path or want to manually override the detection, you can do so here:

*   **Server Executable Path:** The path to the MCP server binary (e.g., `vendor/ishmael/mcp-server/bin/ishmael-mcp`).
*   **Startup Arguments:** Any additional arguments that should be passed to the server when it starts.

### 2. Project Detection
You can customize how the plugin identifies an Ishmael project:

*   **Detection File:** The file the plugin looks for to confirm a project is an Ishmael project (defaults to `ishmael.json`).
*   **Root Directory:** Specify a custom root directory if your Ishmael project is located in a subdirectory of your PhpStorm project.

### 3. AI Assistant Integration
If you're using JetBrains AI Assistant, you can configure how the Ishmael plugin interacts with it:

*   **Enable AI Interaction:** Toggle whether the plugin should provide tools and context to the AI assistant.
*   **Default Prompt Mode:** Choose whether the AI should default to "Quick" or "Standard" orchestration mode for new tasks.

## Troubleshooting Your Configuration

If the plugin is unable to connect to the MCP server, use the **Troubleshoot** action from the Ishmael menu. This will verify your configuration settings and provide a detailed report with potential fixes.
