# LoggerManager

Namespace: `Ishmael\Core\Log`  
Source: `IshmaelPHP-Core\app\Core\Log\LoggerManager.php`

LoggerManager resolves log channels from configuration and returns PSR-3 loggers.

### Public methods
- `__construct(array $config)`
- `channel(string $name): Psr\Log\LoggerInterface`
- `default(): Psr\Log\LoggerInterface`
