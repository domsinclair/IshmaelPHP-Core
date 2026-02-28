# MCP Server CLI Reference

The Ishmael MCP server is an essential component that connects the Ishmael framework to AI-driven tools. While it primarily operates as a background service for the PhpStorm plugin, it can also be used directly from the command line for troubleshooting or specialized automation.

## Executable Location

The MCP server executable is typically found in:
`vendor/ishmael/mcp-server/bin/ish-mcp`

## Common Commands

The MCP server supports the Model Context Protocol (MCP) and can be interacted with using standard MCP clients.

### 1. Basic Execution (STDIO)
To start the server in its default mode (ready to receive JSON-RPC over standard input):
```bash
php vendor/ishmael/mcp-server/bin/ish-mcp
```
This is the command used by the plugin to communicate with the server.

### 2. Version Information
To check the version of the MCP server:
```bash
php vendor/ishmael/mcp-server/bin/ish-mcp --version
```

### 3. Debug Mode
If you need to troubleshoot the server's internal behavior, you can enable verbose logging by setting the `ISH_MCP_DEBUG` environment variable:
```bash
# On Linux/macOS
ISH_MCP_DEBUG=1 php vendor/ishmael/mcp-server/bin/ish-mcp

# On Windows (PowerShell)
$env:ISH_MCP_DEBUG=1; php vendor/ishmael/mcp-server/bin/ish-mcp
```
When debug mode is enabled, internal logs and errors are sent to `stderr`.

## Environment Variables

The `ish-mcp` binary respects several environment variables to control its behavior:

| Variable | Description |
| :--- | :--- |
| `ISH_PROJECT_ROOT` | Manually specify the root directory of the Ishmael project. |
| `ISH_MCP_DEBUG` | If set to `1`, enables verbose internal logging to `stderr`. |
| `ISH_MCP_NO_BROWSER` | If set to `1`, prevents the server from attempting to open a web browser (e.g., for OAuth flows). |
| `DB_CONNECTION` | Can be used to override the default database connection (e.g., `sqlite`). |

## Troubleshooting

### Autoloader Not Found
If the server reports that it cannot find the `autoload.php` file, ensure you have run `composer install` in your project's root directory. The server checks several common locations for the autoloader based on its installation path.

### Root Not Detected
If the server warns that the Ishmael project root was not detected, it might not be able to find all your application's components (like routes and database). Ensure you are running the command from your project's root or have set the `ISH_PROJECT_ROOT` environment variable.
