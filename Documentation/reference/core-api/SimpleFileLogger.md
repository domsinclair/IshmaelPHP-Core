# SimpleFileLogger

Namespace: `Ishmael\Core\Log`  
Source: `IshmaelPHP-Core\app\Core\Log\SimpleFileLogger.php`

Minimal PSR-3 compliant logger that appends to a single file.

### Public methods
- `__construct(string $path, string $minLevel = 'debug')`
- `alert($message, array $context = array (
)): void`
- `critical($message, array $context = array (
)): void`
- `debug($message, array $context = array (
)): void`
- `emergency($message, array $context = array (
)): void`
- `error($message, array $context = array (
)): void`
- `info($message, array $context = array (
)): void`
- `log($level, $message, array $context = array (
)): void`
- `notice($message, array $context = array (
)): void`
- `warning($message, array $context = array (
)): void`
