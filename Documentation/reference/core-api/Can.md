# Can

Namespace: `Ishmael\Core\Http\Middleware`  
Source: `IshmaelPHP-Core\app\Core\Http\Middleware\Can.php`

Can middleware checks an authorization ability via Gate.

### Public methods
- `for(string $ability, ?callable $resourceResolver = NULL): callable` — Factory returning a middleware callable that authorizes the given ability.
