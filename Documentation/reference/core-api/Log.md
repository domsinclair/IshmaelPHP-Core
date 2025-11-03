# Log

Namespace: `Ishmael\Core\Support`  
Source: `IshmaelPHP-Core\app\Core\Support\Log.php`

Static logging facade for newcomer ergonomics.

Recommended for quick starts and simple apps:
  use Ishmael\Core\Support\Log;
  Log::info('Hello');

Framework internals and advanced apps should prefer DI by type-hinting
Psr\Log\LoggerInterface in constructors or handlers. This facade simply
resolves the PSR logger from the lightweight service locator when available,
and otherwise falls back to CoreLogger's static entrypoints.

