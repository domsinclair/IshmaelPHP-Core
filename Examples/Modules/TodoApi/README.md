# TodoApi Module (Example)

A JSON‑first example module demonstrating a small REST‑style API with versioned routes, simple validations, and ETag support.

## What it shows
- Controllers extending Ishmael\Core\Controller with JSON responses
- Versioned routes under /api/v1/todos with named routes
- Basic conditional response via ETag header on list endpoint
- Database access using the core Database adapter
- Migration and seeder for a tiny todos table

## Routes
- GET /api/v1/todos → list
- GET /api/v1/todos/{id} → get
- POST /api/v1/todos → create { title }
- POST /api/v1/todos/{id}/toggle → toggle completion
- POST /api/v1/todos/{id}/delete → delete

Routes are named under the `api.todos.*` namespace.

## Database
Run migrations then seed sample rows:
```
php ish migrate
php ish seed --class=Modules\\TodoApi\\Database\\Seeders\\TodoSeeder
```

## Customization tips
- Add validation and richer JSON error payloads
- Implement throttling/conditional middleware in your pipeline
- Consider adding pagination and filtering to the list endpoint
