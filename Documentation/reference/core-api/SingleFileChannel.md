# SingleFileChannel

Namespace: `Ishmael\Core\Log`  
Source: `IshmaelPHP-Core\app\Core\Log\SingleFileChannel.php`

PSR-3 channel that appends newline-terminated formatted records to a single file.

### Public methods
- `__construct(string $path, string $minLevel = 'debug', ?Ishmael\Core\Log\FormatterInterface $formatter = NULL)`
- `__destruct()`
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
)): void` — Write a record if it meets the configured threshold.
- `notice($message, array $context = array (
)): void`
- `setMinLevel(string $level): void`
- `warning($message, array $context = array (
)): void`
