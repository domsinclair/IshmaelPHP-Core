# NullChannel

Namespace: `Ishmael\Core\Log`  
Source: `IshmaelPHP-Core\app\Core\Log\NullChannel.php`

No-op PSR-3 channel that discards all log messages.

### Public methods
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
)): void` — Accepts any record and ignores it.
- `notice($message, array $context = array (
)): void`
- `warning($message, array $context = array (
)): void`
