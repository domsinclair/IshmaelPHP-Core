# Module Discovery

Ishmael applications are composed of modules. A module is a small, portable package that can contain controllers, routes, views, assets, migrations, seeds, and services. The framework discovers modules automatically at boot.

What you’ll learn on this page:
- Where modules live and how discovery works
- The minimal files a module needs (`module.json`, `routes.php`)
- How controllers, views, assets, and migrations are registered
- Load order, enabling/disabling modules, and route caching
- Practical examples and troubleshooting tips

## 1) Where modules live

By default, Ishmael looks for modules under your application’s Modules directory. In the SkeletonApp this is:
- Disk path: `SkeletonApp/Modules/`
- Namespace root (examples): `Modules\<Name>\...`

The root can be configured in your app config; most apps keep the default. Each first-level folder under `Modules/` is treated as a module (e.g., `Modules/Blog`, `Modules/Users`).

## 2) Minimal module structure

A simple module requires two files:
- `Modules/Blog/module.json` — metadata and flags
- `Modules/Blog/routes.php` — route registrations

Example `module.json` created by the CLI:
```json
{
  "name": "Blog",
  "description": "Tutorial Blog module",
  "version": "0.1.0",
  "enabled": true
}
```

Example `routes.php`:
```php
<?php
use Ishmael\Core\Routing\Router;
use Modules\Blog\Controllers\PostController;

/** @var Router $router */
$router->get('/blog/posts', [PostController::class, 'index'])->name('blog.posts.index');
$router->get('/blog/posts/{id}', [PostController::class, 'show'])->name('blog.posts.show');
```

You can generate this structure with the CLI:
```bash
php IshmaelPHP-Core/bin/ishmael make:module Blog
```
See also: Guide — Application Bootstrap, and Blog Tutorial Part 1.

## 3) How discovery works

At boot, the `ModuleManager`:
1. Reads the configured Modules directory.
2. Finds immediate subdirectories (candidate modules).
3. For each, loads and parses `module.json`.
   - If `enabled` is `false`, the module is skipped.
4. Registers module resources in this order:
   - Routes: if `routes.php` exists, it is required and receives a `Router` instance.
   - Views: the module’s `Views/` directory is added to the view resolver paths.
   - Migrations/Seeds: if present, they are made available to the CLI.
   - Assets: optional publish step maps `Resources/*` to `public/modules/<module>/`.

This means a module is immediately active after adding it to the Modules directory and setting `enabled: true`.

### Registration order
- By default, modules are processed in alphabetical directory order. If you have inter-module route dependencies, keep paths disjoint or use unique route prefixes.
- When using the route cache (see Guide — Route Cache), discovery runs once to build the cache; subsequent requests load the cached routes for speed.

## 4) Controllers and namespaces

Controllers are regular PHP classes under the module’s namespace. A common layout:
```
Modules/
  Blog/
    Controllers/
      PostController.php
    Views/
      posts/
        index.php
        show.php
    routes.php
    module.json
```
Example controller (excerpt):
```php
<?php
namespace Modules\Blog\Controllers;

use Ishmael\Core\Http\Request;
use Ishmael\Core\Http\Response;

final class PostController
{
    public function index(Request $req, Response $res): Response
    {
        return $res->withBody($this->view('posts/index', [/* ... */]));
    }
}
```

## 5) Views and templates

Place module views under `Modules/<Name>/Views`. The view resolver is aware of module paths, so from within that module you can render `posts/index` or `posts/show` and it will resolve to the correct file. See Guide — Controllers & Views and Blog Tutorial Part 4.

## 6) Middleware, services, and routes

- You can import and attach middleware in `routes.php`:
  ```php
  use Modules\Blog\Middleware\RequireAuthor;
  $router->post('/blog/posts', [PostController::class, 'store'])
      ->middleware(RequireAuthor::class)
      ->name('blog.posts.store');
  ```
- Group routes or apply prefixes as needed. See Guide — Routing and “Routing v2: Parameters, Constraints, and Named Routes”.

## 7) Migrations and seeds (optional)

If your module ships database changes, keep them under `Modules/<Name>/Database/Migrations` and seeds under `Modules/<Name>/Database/Seeds`. The CLI can pick these up:
```bash
php IshmaelPHP-Core/bin/ishmael migrate --module=Blog
php IshmaelPHP-Core/bin/ishmael db:seed --module=Blog
```
Consult the “Writing and running migrations” guide for details.

## 8) Static assets (optional)

Keep CSS/JS/images under `Modules/<Name>/Resources/` and publish them to the public web root during build or install, for example to `public/modules/<name>/`. See Blog Tutorial Part 13 (CSS) and Part 14 (JavaScript) for concrete patterns.

## 9) Enabling/disabling modules

Toggle the `enabled` flag in `module.json`. Disabled modules are skipped during discovery and none of their routes or views are registered. If using route cache, rebuild it after toggling:
```bash
php IshmaelPHP-Core/bin/ishmael route:cache
```

## 10) Troubleshooting

- “My routes don’t appear”: ensure `module.json` has `"enabled": true` and that `routes.php` returns without syntax errors. Clear route cache.
- “Views not found”: confirm files are under `Modules/<Name>/Views` and that the view name matches the folder structure (`posts/index`).
- “Conflicting routes between modules”: use unique prefixes (e.g., `/blog/...` vs `/shop/...`) and named routes.

## Related reading
- Guide: [Application Bootstrap](../guide/application-bootstrap.md)
- Guide: [Routing](../guide/routing.md) and [Routing v2: Parameters, Constraints, and Named Routes](../guide/routing-v2-parameters-constraints-and-named-routes.md)
- Guide: [Controllers & Views](../guide/controllers-and-views.md)
- How‑to: [Create a Module](../how-to/create-a-module.md)
- Reference: [Routes](../reference/routes/_index.md)
