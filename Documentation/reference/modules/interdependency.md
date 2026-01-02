# Module Interdependency and Discovery

Ishmael supports a dependency-aware module system. Modules can declare dependencies on other modules, ensuring they are discovered and booted in the correct order.

## 1) Declaring Dependencies

Dependencies are declared in the module manifest (`module.php` or `module.json`) using the `dependencies` key.

### module.php (Preferred)

```php
return [
    'name' => 'Blog',
    'env' => 'shared',
    'dependencies' => ['Core', 'User'],
    // ...
];
```

### module.json

```json
{
  "name": "Blog",
  "env": "shared",
  "dependencies": ["Core", "User"]
}
```

## 2) Topological Boot Order

When `Ishmael\Core\ModuleManager::discover()` is called, it performs a **topological sort** on all discovered modules based on their dependencies.

- If Module B depends on Module A, Module A will always appear before Module B in the `ModuleManager::$modules` array.
- This ensures that when the application iterates over modules to register routes or hooks, dependencies are handled first.

## 3) Safety Checks

The `ModuleManager` performs several safety checks during the discovery phase:

### Circular Dependency Detection
If Module A depends on Module B, and Module B depends on Module A (directly or indirectly), Ishmael will throw a `RuntimeException` to prevent infinite loops.

### Environment Safety
To prevent production failures, **Shared** or **Production** modules are not allowed to depend on **Development** modules.
- `shared` -> `development` (Forbidden)
- `production` -> `development` (Forbidden)
- `development` -> `shared` (Allowed)

If such a violation is detected, a `RuntimeException` is thrown.

## 4) Validating Dependencies

You can validate your module dependency graph using the `ish` CLI:

```bash
php ish modules:check
```

This command will:
1. Verify that all declared dependencies exist.
2. Check for circular dependencies.
3. Identify "shadow dependencies" (classes from other modules used in your code but not declared in your `dependencies` list).

## 5) Shadow Dependencies

A "shadow dependency" occurs when a module uses a class from another module without declaring it as a dependency. While the code might work if the other module happens to be loaded first, it is brittle and can break if the loading order changes or if the other module is removed.

Always declare every module you explicitly reference as a dependency in your manifest.
