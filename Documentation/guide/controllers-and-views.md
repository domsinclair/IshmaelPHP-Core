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


---

## Related reference
- Reference: [Routes](../reference/routes/_index.md)
- Reference: [Core API (Markdown stubs)](../reference/core-api/_index.md)
