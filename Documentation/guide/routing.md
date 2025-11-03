# Routing

Ishmael provides convention-based routing and (soon) a fluent API. For now, routes can be declared in each module's `routes.php` file as regex patterns mapping to `Controller@action`.

Example:

```php
<?php
return [
    '^$' => 'HomeController@index',
    '^admin$' => 'AdminController@index',
    '^posts/(\d+)$' => 'PostsController@show',
];
```

Convention fallback: `/{module}/{controller}/{action}/{params...}`
