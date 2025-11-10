# Contacts Module (Example)

An example MVC CRUD module demonstrating Ishmael controllers, views with optional layouts/sections, named routes, and simple form handling. It also includes a migration and a seeder.

## What it shows
- Controllers extending Ishmael\Core\Controller with render() and redirects
- Views composed with an optional layout and a simple sections helper
- Named routes and URL generation via Router::url in templates
- Database access via the core Database adapter (no ORM required)
- A timestamped migration using TableDefinition and a seeder using BaseSeeder

## Routes
- GET /contacts → list
- GET /contacts/create → new form
- POST /contacts → create
- GET /contacts/{id} → show
- GET /contacts/{id}/edit → edit form
- POST /contacts/{id} → update
- POST /contacts/{id}/delete → delete

All routes are named under the `contacts.*` namespace.

## Database
Run migrations then seed sample rows:
```
php ish migrate
php ish seed --class=Modules\\Contacts\\Database\\Seeders\\ContactsSeeder
```

## Customization tips
- Adjust the Views/ layout styles or split into partials as needed
- Add pagination and search to the index using query params
- Wire CSRF middleware for POST endpoints in your app’s middleware pipeline

