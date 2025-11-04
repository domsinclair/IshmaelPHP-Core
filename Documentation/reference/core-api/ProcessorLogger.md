# ProcessorLogger

Namespace: `Ishmael\Core\Log`  
Source: `IshmaelPHP-Core\app\Core\Log\ProcessorLogger.php`

Wraps a logger and applies processors to the context before delegating.

### Public methods
- `__construct(Psr\Log\LoggerInterface $inner, array $processors = array (
))`
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
