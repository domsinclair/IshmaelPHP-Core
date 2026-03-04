# Troubleshooting and Support

The Ishmael MCP plugin includes built-in diagnostics to help you resolve issues with the MCP server or the AI's understanding of your project.

## The Troubleshooting Tool

One of the most powerful features of the plugin is the **Troubleshoot** action (accessible from the **Ishmael** menu or the tool window toolbar). When you run this action, the plugin:

1.  **Gathers System State:** It collects logs from the plugin and the MCP server, checks the PHP environment, and verifies the connection to the database.
2.  **Analyzes Current Health:** It performs several health checks to identify common configuration errors.
3.  **Presents a Solution:** Instead of just showing you raw logs, it compiles this information into a specialized "Troubleshooting Report" and asks the AI assistant to analyze it and suggest a direct solution.

## Common Issues and Fixes

### 1. "MCP Server Not Found"
This usually means that the plugin cannot find the Ishmael MCP server binary in your project's `vendor` directory.
*   **Fix:** Run `composer install` to ensure that all dependencies, including `ishmael/mcp-server`, are correctly installed.

### 2. "Database Connection Failed"
The MCP server needs to connect to your project's database to provide the database schema to the AI.
*   **Fix:** Check your `.env` file and ensure that the database credentials are correct. You can also run the **Validate Env** action from the Ishmael menu to check for environment-related issues.

### 3. "AI Not Calling Tools"
Sometimes the AI assistant might "forget" that it has tools available or might not understand how to use them.
*   **Fix:** Try refreshing the AI context by starting a new chat or by using the **Plan Feature** prompt from the Ishmael menu. This re-introduces the AI to the available Ishmael tools.

### 4. "Stale Project Metadata"
If you've recently added new routes, events, or services, the plugin's metadata might be out of date.
*   **Fix:** Click the **Refresh Metadata** button in the tool window toolbar to force a re-synchronization with the MCP server.

## Getting Further Help

If you're still stuck, you can use the **Troubleshoot with Ishmael** action directly from a console or terminal window. This will automatically capture the context of the error you're seeing and help the AI provide a more targeted fix.
