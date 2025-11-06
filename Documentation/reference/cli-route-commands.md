# CLI Route Commands

Ishmael CLI provides commands to inspect and manage routes:

## ish route:list

Lists all routes with optional filtering.

Usage:
```
ish route:list [--method=GET] [--module=Name]
```

Columns:
- METHOD: Allowed methods (pipe-delimited)
- PATH: The route path pattern
- NAME: Route name if assigned
- HANDLER: Controller@action or callable
- MODULE: Module hint (if provided via groups)
- MW: Number of middleware entries on the route

## ish route:cache

Compiles the application's routes to a deterministic cache file for faster cold boots.

Usage:
```
ish route:cache [--force]
```

- Fails fast if non-cacheable handlers or middleware are present (closures or object callables). The error will include details and a hint.
- With `--force`, non-cacheable entries are stripped, and warnings are embedded in the cache metadata and printed after writing the cache.

## ish route:clear

Clears the previously generated route cache, restoring dynamic route discovery.

Usage:
```
ish route:clear
```

In production, the Kernel loads the cache when present and fresh; in development, dynamic discovery remains the default.
