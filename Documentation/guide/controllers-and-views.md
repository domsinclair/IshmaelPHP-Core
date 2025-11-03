# Controllers & Views

Ishmael controllers extend the `Ishmael\Core\Controller` base class and can render PHP views or return JSON responses.

Example JSON action:

```php
public function api(): void
{
    $this->json(['ok' => true]);
}
```

Example view render:

```php
public function index(): void
{
    $this->render('home', ['message' => 'Hello']);
}
```
