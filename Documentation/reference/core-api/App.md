# App

Namespace: `Ishmael\Core`  
Source: `IshmaelPHP-Core\app\Core\App.php`

Kernel v1 - tiny application wrapper responsible for bootstrapping

### Public methods
- `boot(): void` — Boot the application (idempotent).
- `handle(Ishmael\Core\Http\Request $request): Ishmael\Core\Http\Response` — Handle a request and return a response.
- `terminate(Ishmael\Core\Http\Request $request, Ishmael\Core\Http\Response $response): void` — Termination hook for post-response tasks (no-op initially).
