# ConstraintRegistry

Namespace: `Ishmael\Core`  
Source: `IshmaelPHP-Core\app\Core\ConstraintRegistry.php`

ConstraintRegistry manages named route parameter constraints used by the Router.

### Public methods
- `add(string $name, callable|string $regexOrCallable, ?callable $converter = NULL): void` — Register or override a named constraint.
- `convert(string $name, string $value): mixed` — Convert a matched value using the constraint's converter (if any).
- `getPattern(string $name): ?string` — Get the regex pattern for a constraint or null if unknown.
