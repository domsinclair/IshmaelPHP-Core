# The Ishmael MCP Tool Window

The **Ishmael MCP tool window** (located on the right side of the IDE by default) is your primary dashboard for monitoring the interaction between your project and the AI assistant. It provides several tabs that visualize different parts of your application's state as seen by the MCP server.

## Tool Window Tabs

### 1. Status Tab
This tab shows the health of the connection between the plugin and the **Ishmael MCP Server**. It also displays the current state of the active orchestration pipeline (e.g., `INIT`, `ANALYSIS_COMPLETE`). This is the first place to check if the AI assistant seems unable to access your code.

### 2. Database Tab
Displays the database schema as discovered by the MCP server. This includes tables, their columns (with types), and any identified relationships. It's a quick way to verify if the AI's understanding of your database matches the actual schema.

### 3. Events Tab
Lists all registered events in your Ishmael application. You can see which events are available to be dispatched or listened to. Clicking on an event can sometimes jump to its declaration or show its usages.

### 4. Routes Tab
Displays all the HTTP routes registered in your application. This includes the URI, the controller action it maps to, and any associated middleware or constraints.

### 5. Feature Packs Tab
Managed specifically for Ishmael's modular architecture. This tab lists any "Feature Packs" that have been identified or are being developed in the current project. You can use it to quickly access the configuration and manifests for these modules.

### 6. Output Tab
Provides a live log of the communication between the plugin and the MCP server. When the AI assistant calls a tool (e.g., to run tests or design a database), the detailed output from that tool execution is shown here. This is especially useful for reviewing long error messages or complex reports that might be truncated in the chat window.

## Tool Window Toolbar

The toolbar at the top of the tool window offers several quick actions:

*   **Switch MCP Mode:** Toggle between "Quick" and "Standard" orchestration modes.
*   **Refresh Metadata:** Force the plugin to reconnect to the MCP server and pull the latest project state.
*   **Troubleshoot:** Automatically gather diagnostic information (logs, server status) and present it in a way the AI can analyze to suggest a fix.
*   **Settings:** Quick access to the Ishmael MCP settings page.
