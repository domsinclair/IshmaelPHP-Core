# Install Example Modules with the ish CLI

The core package ships with two realistic example modules you can install into any Ishmael app:

- Contacts (HTML + MVC): a conventional CRUD module showing controllers, views with optional layouts/sections, CSRF posts, and simple listing.
- TodoApi (JSON API): a REST-style API with versioned routes and basic ETag headers.

These modules live inside the core repo under Examples/Modules and are published into your app’s Modules/ directory via the CLI.

## Prerequisites
- You have an Ishmael app with Composer dependencies installed.
- Your database.php config is set up and reachable.

## List available examples
```
php ish examples:list
```
You should see:
```
Contacts - Example MVC CRUD module with views, CSRF, and pagination
TodoApi - Example JSON API module with ETag, 304s, and throttling
```

## Install a module
Install the Contacts module into your app’s Modules/Contacts:
```
php ish examples:install Contacts
```
Or install all examples:
```
php ish examples:install --all
```
Options:
- --path=Modules   Choose a different destination directory (relative to your app root).
- --force          Overwrite/replace the destination directory if it already exists.

## Run migrations and seeders
After installing modules, run migrations and seeders:
```
php ish migrate
php ish seed --class=Modules\\Contacts\\Database\\Seeders\\ContactsSeeder
php ish seed --class=Modules\\TodoApi\\Database\\Seeders\\TodoSeeder
```

## Try the routes
- Contacts index (HTML): GET /contacts
- Contacts create: GET /contacts/create, submit the form to POST /contacts
- TodoApi list (JSON): GET /api/v1/todos

## Customizing
Because these examples are installed as plain source files into your app’s Modules/ directory, you can freely customize them:
- Update views or layout styling.
- Adjust database columns, add migrations, or tweak services.
- Rename routes using Router::name and generate URLs via Router::url in views.

## Windows quickstart script (optional)
If you’re on Windows, a helper script is provided at tools/Install-Examples.ps1 in the core repo. From your app root, run:
```
powershell -ExecutionPolicy Bypass -File vendor/ishmael/core/tools/Install-Examples.ps1
```
It will install all examples, run migrations, seed data for both modules, and run (optional) smoke tests if available.

## What you’ll learn
- How module structure works (Controllers, Views, routes.php, Database/Migrations, and Seeders).
- How to use named routes and generate URLs in views.
- Basic JSON APIs and conditional response headers like ETag.

## Notes
- The example code uses camelCase for PHP identifiers and routes names; database columns follow conventional snake_case naming.
- CSRF and throttle middleware are referenced by name; enable or adjust them according to your app’s middleware pipeline.
