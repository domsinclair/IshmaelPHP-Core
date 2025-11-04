# DailyRotatingFileChannel

Namespace: `Ishmael\Core\Log`  
Source: `IshmaelPHP-Core\app\Core\Log\DailyRotatingFileChannel.php`

File channel that rotates logs daily and enforces a retention policy.

### Public methods
- `__construct(string $basePath, int $days = 7, string $minLevel = 'debug', ?Ishmael\Core\Log\FormatterInterface $formatter = NULL)`
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
)): void` — Write a record to the current day's file and apply retention once per run.
- `notice($message, array $context = array (
)): void`
- `setMinLevel(string $level): void`
- `warning($message, array $context = array (
)): void`
