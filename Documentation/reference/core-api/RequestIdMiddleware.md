# RequestIdMiddleware

Namespace: `Ishmael\Core\Http\Middleware`  
Source: `IshmaelPHP-Core\app\Core\Http\Middleware\RequestIdMiddleware.php`

RequestIdMiddleware
- Accepts incoming X-Request-Id or generates a UUIDv4
- Stores it in a global accessor (app('request_id')) for processors to use
- Adds X-Request-Id response header

