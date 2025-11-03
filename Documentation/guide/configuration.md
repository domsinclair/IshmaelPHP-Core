# Configuration

Configuration files live under `IshmaelPHP-Core/config/`. Use the `config()` helper to retrieve values and `env()` to read environment variables (loaded from `.env`).

```php
$debug = env('APP_DEBUG', false);
$name  = config('app.name', 'Ishmael');
```

The framework auto-creates a default `.env` file on first run if none is present.
