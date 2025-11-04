# StackChannel

Namespace: `Ishmael\Core\Log`  
Source: `IshmaelPHP-Core\app\Core\Log\StackChannel.php`

Fan-out logger that forwards each record to multiple underlying channels.

### Public methods
- `__construct(array $channels)`
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
)): void` — Forward a record to each configured channel.
- `notice($message, array $context = array (
)): void`
- `warning($message, array $context = array (
)): void`
