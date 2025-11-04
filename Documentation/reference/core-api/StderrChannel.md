# StderrChannel

Namespace: `Ishmael\Core\Log`  
Source: `IshmaelPHP-Core\app\Core\Log\StderrChannel.php`

PSR-3 logging channel that writes formatted log lines to STDERR.

### Public methods
- `__construct(string $minLevel = 'debug', ?Ishmael\Core\Log\FormatterInterface $formatter = NULL)` — Create a STDERR logging channel.
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
)): void` — Write a log record to STDERR if above the configured threshold.
- `notice($message, array $context = array (
)): void`
- `setMinLevel(string $level): void`
- `warning($message, array $context = array (
)): void`
