# Interacting with the Plugin via Chat

While the Ishmael MCP plugin has a visual tool window, its true power is unlocked through interaction within the AI assistant's chat window. The plugin acts as a mediator, allowing the AI to "talk" to your project's code and infrastructure.

## How the AI Interacts with Your Project

The AI assistant doesn't just "see" text; it has a set of **Tools** provided by the Ishmael MCP server. These tools allow the AI to perform complex actions such as:
*   `ish:listRoutes`: Discovering application entry points.
*   `ish:database:schema`: Inspecting the database.
*   `ish:test:run`: Executing automated tests.
*   `ish:make:module`: Scaffolding new code components.
*   `ish:mcp:transition`: Advancing the project pipeline.

## How You Can Interact via Chat

You can trigger these tool calls by simply asking the AI questions or giving it commands in plain English:
*   *"What columns are in the `users` table?"* (Triggers database schema inspection)
*   *"List all registered application events."* (Triggers event discovery)
*   *"Run the tests for the Blog module."* (Triggers test execution)

### 1. Using Roles-Based Prompts
The plugin includes pre-configured prompts that you can copy to your clipboard from the **Prompts** tab or the **Ishmael** menu. Paste these into the chat to quickly switch the AI's "brain" to a specific role:
*   **`role:analyst`**: Tell the AI to analyze a new feature request.
*   **`role:architect`**: Ask the AI to design the necessary changes.
*   **`role:developer`**: Command the AI to start coding.
*   **`role:reviewer`**: Request the AI to verify the completed work.

### 2. Manual State and Mode Commands
If the AI is stuck or you want to force a change, you can use these direct commands:
*   `ish:mcp:mode mode="standard"` (to switch to Standard mode)
*   `ish:mcp:transition state="ANALYSIS_COMPLETE"` (to manually advance the pipeline)

## Getting the Most Out of Chat

### Provide Clear Context
While the AI has access to your code, it doesn't always know *where* to look. You can help it by referencing specific files or modules in your request.

### Review AI Tool Calls
When the AI wants to use a tool (like `ish:make:module`), it will often show you the proposed parameters. Review these carefully, as this is how the AI interacts with your project's filesystem.

### Use the Output Tab
When an AI tool call is executed, its detailed output is often sent to the **Output** tab in the Ishmael tool window. This is useful for seeing long error logs or detailed reports that might be too large for the chat window.

### Use Intent Routing
For large tasks, start with the **Plan Feature** prompt or the `ishmael:intent-router` command. This helps the AI understand the scope of the request and suggests the best orchestration path (Quick vs. Standard mode) to take.
