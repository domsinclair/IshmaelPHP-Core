# ish pack — Build environment-specific bundles

This command creates a deployable bundle by scanning modules, applying environment filters, and including exported assets.

Synopsis

```
ish pack --env=production [--include-dev] [--target=webhost|container] [--out=./dist] [--dry-run]
```

Options
- --env=ENV — Environment name (production|development|testing).
- --include-dev — Include development modules even when env=production.
- --target=TARGET — Deployment target (webhost|container). Reserved for future behavior adjustments.
- --out=PATH — Output directory (default: ./dist under the app root).
- --dry-run — Do not copy files; print the bundle plan.

Behavior
- Modules are discovered via ModuleManager with environment-aware filtering.
- In production, development modules are excluded unless --include-dev is set.
- The packer gathers files from each module's manifest `export` list. If omitted, defaults include Controllers, Models, Views, routes.php, schema.php (if present), and module manifests.
- Includes caches when present (storage/cache/routes.cache.php, storage/cache/modules.cache.json) and the config directory.
- Generates manifest.json with sha256 checksums of included files.

Examples

```
# Production bundle
ish pack --env=production --out=./dist

# Include development modules explicitly
ish pack --env=production --include-dev --out=./dist

# Dry-run preview
ish pack --env=production --dry-run
```

Notes
- Vendor inclusion policy is documented separately; by default this command does not copy vendor/.
- All new code follows camelCase/PascalCase conventions and includes PHPDoc.
