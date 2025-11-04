# Logger

Namespace: `Ishmael\Core`  
Source: `IshmaelPHP-Core\app\Core\Logger.php`

Static facade for application logging built on top of LoggerManager and PSR-3.

### Public methods
- `error(string $message, array $context = array (
)): void` — Log an error message.
- `info(string $message, array $context = array (
)): void` — Log an informational message.
- `init(array $config): void` — Initialize logging.
- `log(string $level, string $message, array $context = array (
)): void` — Log a message at an arbitrary level.
