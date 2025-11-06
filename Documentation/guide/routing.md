# Routing

Ishmael provides convention-based routing and a fluent Router API inside module `routes.php` files. You can continue to use simple regex-to-`Controller@action` arrays, or export a closure that receives a Router instance and call methods like `get()`, `post()`, and `group()`.

Example (legacy array):

```php
<?php
return [
    '^$' => 'HomeController@index',
    '^admin$' => 'AdminController@index',
    '^posts/(\d+)$' => 'PostsController@show',
];
```

Example (fluent closure):

```php
<?php
use Ishmael\Core\Router;

return function (Router $r): void {
    $r->get('/', [HomeController::class, 'index']);
    $r->get('/posts/{id:int}', [PostsController::class, 'show']);

    $r->group(['prefix' => '/admin', 'middleware' => [AuthMiddleware::class]], function () use ($r): void {
        $r->get('/', [AdminController::class, 'index']);
    });
};
```

Convention fallback: `/{module}/{controller}/{action}/{params...}`

## Parameters and constraints

- `{id}` matches any non-slash by default
- `{id:int}` restricts to digits
- `{slug:slug}` restricts to URL slugs `[A-Za-z0-9-]+`

## Middleware

Middleware entries on routes and groups must be cacheable when using the route cache.

Cacheable:
- Class strings (`AuthMiddleware::class`)
- Static callable arrays (`[SomeClass::class, 'handle']`)

Not cacheable:
- Closures and object-bound callables

See the Route Cache guide for details and conversion tips.

## Route cache

For production, you can precompile routes:

```
ish route:cache            # strict by default, fails on closures
ish route:cache --force    # strips non-cacheable entries and records warnings
ish route:clear            # remove cache file
```

Learn more in Guide → Route Cache.
