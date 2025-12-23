Ishmael MCP Server (Incubation)

This is an incubating MCP (Model Context Protocol) server for IshmaelPHP-Core, implemented in PHP (>= 8.2) and kept isolated under tools/mcp for now.

Implemented (Task 0, Task 1, and Task 2 basics):
- Composer package skeleton with PSR-4 autoloading
- PSR-12 code style (PHPCS) and PHPStan config
- Stdio transport using JSON Lines (one JSON object per line)
- Minimal JSON-RPC-like handling with discovery endpoints: listTools, listResources, listPrompts, health/version
- Project discovery and sandbox (Task 2):
  - Project root auto-detection starting from CWD (composer.json with ishmaelphp/* or repo bootstrap markers)
  - Vendor binaries resolution (vendor/bin/phpunit, phpstan, phpcs)
  - Path sandbox helper to restrict operations within project root
  - project/info tool to view discovery results (read-only)

Read-only resources (Task 3: docs:* and templates:*)
- The server now exposes curated documentation and scaffolding templates through listResources.
- docs:* are discovered from:
  1) The project checkout (if present), scanning these folders under the detected project root:
     - Docs/ (Markdown sources; top-level files)
     - site/ (built docs; top-level sections exposing their index.html)
  2) A bundled copy shipped inside this MCP package at tools/mcp/resources/{Docs,site} (always considered as a fallback). This ensures docs are available even when installed via Composer without the repo docs.
- templates:* are discovered from Templates/ (top-level subdirectories are listed as template packs).
- All resource discovery is read-only and constrained by the project PathSandbox; no files are modified.
- If the server runs outside an Ishmael repo, the server still exposes built-in static entries and the bundled docs shipped with this package.

Packaging note about documentation
- This MCP package now bundles the full documentation set (~700 KB) under tools/mcp/resources/{Docs,site} so that Composer users always have offline docs. In this monorepo, maintainers can refresh the bundled copy before release:
  - composer run sync-docs
  This mirrors the repository's Docs/ and site/ into tools/mcp/resources/.

Usage
- Install: run composer install in tools/mcp or project root
- Example (PowerShell):
  echo '{"id":1,"method":"listTools"}' | php tools/mcp/bin/ish-mcp
  echo '{"id":2,"method":"health/version"}' | php tools/mcp/bin/ish-mcp
  echo '{"id":3,"method":"project/info"}' | php tools/mcp/bin/ish-mcp

Notes
- Logging goes to STDERR with the [ish-mcp] prefix.
- Transport expects one JSON object per line and outputs one per line.

Security Hardening and Performance (Task 8)
- Rate limiting:
  - Global and per-method limits enforced in-process (token bucket, per-minute window).
  - Configure via environment:
    - MCP_RATE_GLOBAL_PER_MIN (default 120)
    - MCP_RATE_<UPPER_METHOD>_PER_MIN, e.g. MCP_RATE_ISH_LISTROUTES_PER_MIN=10
- Timeouts:
  - Soft request timeout checks total processing time and returns 408 error if exceeded.
  - Configure via MCP_REQUEST_TIMEOUT_MS (default 30000).
- Cancellation:
  - Best-effort support via special request method `"$/cancelRequest"` with params `{ "id": <originalId> }`.
  - The server tracks in-flight IDs; tools may consult cancellation flags in future revisions.
- Caching:
  - In-memory result cache with TTL and file-change invalidation for expensive tools (e.g., routes, container map).
  - Configure per-tool TTLs via MCP_CACHE_TTLS (e.g., `"ish:listRoutes=10,ish:container:map=10"`).
  - Configure watch patterns via MCP_CACHE_WATCH (e.g., `"ish:listRoutes=config/routes.php;Modules/*/Routes/*.php"`).
- Telemetry (opt-in):
  - Structured JSON logs to STDERR when MCP_TELEMETRY=true.
  - Emits events: rate_limited, cache_hit, tool_executed, timeout.
- Pagination/Streaming:
  - Conventions: tools may accept `limit`, `offset` or cursor fields under `params.page` and return paging hints under `meta.page`.
  - For very large outputs, clients should prefer paged access; streaming over stdio as JSON Lines is supported at transport level.

Contracts, Validation, and Errors (Task 4)
- All tools define JSON Schemas for inputs/outputs via getInputSchema/getOutputSchema. The server validates:
  - Input (ingress) before tool execution
  - Output (egress) after tool execution
- On validation failure, a standardized error envelope is returned:
  { id, version: "0.1", error: { code, message, details? }, meta: { durationMs } }
  - Redaction: known sensitive keys in details (password, token, secret, apiKey, authorization, auth, bearer) are masked.
- Success responses are wrapped consistently:
  { id, version: "0.1", result: { ... }, meta: { durationMs, cache?, page? } }
- Parse errors (invalid JSON) are also surfaced in the same standardized error envelope.

Next steps (see Docs/MCP-Server-for-IshmaelPHP-Core.md):
- MVP tools: ish:listRoutes, ish:make (dry-run), ish:config:get

Testing and CI (Task 5)
- Run tests locally (PowerShell from tools/mcp):
  - composer install
  - composer run test
  - composer run analyse
  - composer run lint
- Tests included:
  - Contract tests: verify each Tool exposes input/output schemas and that executions validate against schemas.
  - Integration tests: boot the in-memory Server with stdio transport, exercise listTools/listResources/health/version, and assert standardized envelopes with meta.durationMs.
  - Golden file tests: compare stable subsets of outputs (e.g., resources listing and parse-error envelope) to fixtures in tests/fixtures.
- CI: .github/workflows/mcp-ci.yml runs on Ubuntu and Windows with PHP 8.2/8.3, executing lint, static analysis, and unit/integration tests.

Task 3F — Feature Packs (Phase 1) implemented
- Tools added:
  - ish:featurePack:list (read-only): aggregates a catalog from local Templates/FeaturePacks and an optional curated packaged index. Inputs: { query?, vendorPrefix?, includePrerelease? }. Output: { packs: [{ name, description, version, package, repoUrl, keywords, requires, stability, source }] }
  - ish:featurePack:create (dry-run by default): scaffolds a new Feature Pack plan using Templates/FeaturePacks/{<name>|Upload} if present, or a minimal skeleton. Inputs: { name, vendor, namespace, description?, license?, repoInit? false, targetPath?, confirm? false }. Output: { dryRun, targetPath, conflicts[], files[] }.
- Safety: All operations are sandboxed to the project root. Creation is dry-run unless confirm=true. If any file conflicts are detected, confirm write is skipped and a plan with conflicts is returned.

Examples (PowerShell)
- List available packs (read-only):
  echo '{"id":10,"method":"ish:featurePack:list","params":{"query":"upload"}}' | php tools/mcp/bin/ish-mcp
- Preview creating a pack (dry-run):
  echo '{"id":11,"method":"ish:featurePack:create","params":{"name":"Upload","vendor":"Acme","namespace":"Acme\\\\Upload","description":"Upload feature pack","confirm":false}}' | php tools/mcp/bin/ish-mcp
- Actually write files (will only proceed if no conflicts):
  echo '{"id":12,"method":"ish:featurePack:create","params":{"name":"Upload","vendor":"Acme","namespace":"Acme\\\\Upload","confirm":true}}' | php tools/mcp/bin/ish-mcp

Troubleshooting
- Parse error: ensure each input line is a single valid JSON object.
- Autoloader not found: run `composer install` in tools/mcp or project root.
- Rate-limited: check MCP_RATE_* env vars and reduce call frequency.
- Timeout: increase MCP_REQUEST_TIMEOUT_MS or narrow query parameters.
- Cache not updating: adjust MCP_CACHE_WATCH patterns; edits must change file mtimes to invalidate.