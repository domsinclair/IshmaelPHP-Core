# Blog Tutorial — Part 3 (Revised): Controllers, Services, Routes, and Working Screens

In this revised Part 3 you will:
- Build PostService and AuthorService with simple, testable methods.
- Implement PostsController and AuthorsController actions that render views (or JSON while developing).
- Generate views with the new make:* CLI commands and wire up routes.
- Add a basic create/edit flow for posts including an Author dropdown and a URL slug.
- End with a fully working (simple) Blog app: list posts, view a post, create and edit posts.

Prerequisites:
- You completed Part 2 (authors + posts migrations and seeds).
- You can run the Ishmael CLI from your app root:

  ```bash
  php vendor/bin/ish
  ```

Tip: This part focuses on controllers/services and minimal views. In Part 4 we’ll polish the views and layout further.

---

## 1) Generate scaffolding (controllers, services, views)

If you haven’t already created these in Part 2, generate them now.

```bash
# Services
php vendor/bin/ish make:service Blog Author
php vendor/bin/ish make:service Blog Post

# Controllers
php vendor/bin/ish make:controller Blog Authors
php vendor/bin/ish make:controller Blog Posts

# Views for each resource (index, show, create, edit, _form)
php vendor/bin/ish make:views Blog authors
php vendor/bin/ish make:views Blog posts
```

This creates files under `Modules/Blog`:
- Services: `Services/AuthorService.php`, `Services/PostService.php`
- Controllers: `Controllers/AuthorsController.php`, `Controllers/PostsController.php`
- Views: `Views/authors/*`, `Views/posts/*`

---

## 2) Implement the services

We’ll keep services query‑centric and small. They return plain arrays suitable for controllers and views.

`Modules/Blog/Services/AuthorService.php`

```php
<?php
declare(strict_types=1);

namespace Modules\Blog\Services;

use Ishmael\Core\Database;

final class AuthorService
{
    public function all(): array
    {
        return Database::query('SELECT * FROM authors ORDER BY name')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $row = Database::query('SELECT * FROM authors WHERE id = :id', ['id' => $id])->fetch();
        return $row ?: null;
    }
}
```

`Modules/Blog/Services/PostService.php`

```php
<?php
declare(strict_types=1);

namespace Modules\Blog\Services;

use Ishmael\Core\Database;

final class PostService
{
    public function paginateWithAuthors(int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;
        $items = Database::query(
            'SELECT p.*, a.name AS author_name FROM posts p JOIN authors a ON a.id = p.author_id ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset',
            ['limit' => $perPage, 'offset' => $offset]
        )->fetchAll();
        $total = (int) Database::query('SELECT COUNT(*) AS c FROM posts')->fetch()['c'];
        return ['items' => $items, 'total' => $total, 'page' => $page, 'perPage' => $perPage];
    }

    public function findById(int $id): ?array
    {
        $row = Database::query('SELECT * FROM posts WHERE id = :id', ['id' => $id])->fetch();
        return $row ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $row = Database::query('SELECT * FROM posts WHERE slug = :slug', ['slug' => $slug])->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        // Minimal validation/sanitization
        $title = trim((string)($data['title'] ?? ''));
        $slug = trim((string)($data['slug'] ?? ''));
        $body = (string)($data['body'] ?? '');
        $authorId = (int)($data['author_id'] ?? 0);
        if ($slug === '' && $title !== '') {
            $slug = $this->slugify($title);
        }

        Database::query(
            'INSERT INTO posts (title, slug, body, author_id, created_at, updated_at) VALUES (:t,:s,:b,:a,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)',
            ['t' => $title, 's' => $slug, 'b' => $body, 'a' => $authorId]
        );
        return (int) Database::lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $title = trim((string)($data['title'] ?? ''));
        $slug = trim((string)($data['slug'] ?? ''));
        $body = (string)($data['body'] ?? '');
        $authorId = (int)($data['author_id'] ?? 0);
        if ($slug === '' && $title !== '') {
            $slug = $this->slugify($title);
        }
        Database::query(
            'UPDATE posts SET title = :t, slug = :s, body = :b, author_id = :a, updated_at = CURRENT_TIMESTAMP WHERE id = :id',
            ['t' => $title, 's' => $slug, 'b' => $body, 'a' => $authorId, 'id' => $id]
        );
    }

    public function delete(int $id): void
    {
        // If you enabled soft deletes, change this to set deleted_at instead
        Database::query('DELETE FROM posts WHERE id = :id', ['id' => $id]);
    }

    private function slugify(string $title): string
    {
        $s = strtolower(trim($title));
        $s = preg_replace('/[^a-z0-9]+/i', '-', $s) ?? $s;
        return trim($s, '-');
    }
}
```

Notes
- If you adopted soft deletes (deleted_at) in your schema, replace `delete()` with an UPDATE that sets `deleted_at = CURRENT_TIMESTAMP` and filter accordingly in select queries.
- You can later move validation into a dedicated validator or form request object; here we keep it simple.

---

## 3) Implement the controllers

Controllers coordinate HTTP input/output and delegate to services. We’ll return JSON for quick checks, then wire views.

`Modules/Blog/Controllers/AuthorsController.php`

```php
<?php
declare(strict_types=1);

namespace Modules\Blog\Controllers;

use Ishmael\Core\Controller;
use Modules\Blog\Services\AuthorService;

final class AuthorsController extends Controller
{
    public function __construct(private AuthorService $authors) {}

    public function index(): string
    {
        return $this->json($this->authors->all());
    }
}
```

`Modules/Blog/Controllers/PostsController.php`

```php
<?php
declare(strict_types=1);

namespace Modules\Blog\Controllers;

use Ishmael\Core\Controller;
use Modules\Blog\Services\PostService;
use Modules\Blog\Services\AuthorService;

final class PostsController extends Controller
{
    public function __construct(private PostService $posts, private AuthorService $authors) {}

    public function index(): string
    {
        $page = (int)($_GET['page'] ?? 1);
        $data = $this->posts->paginateWithAuthors($page, 10);
        // Switch to a view once you’re ready:
        // return $this->render(__DIR__ . '/../Views/posts/index.php', $data);
        return $this->json($data);
    }

    public function show(string $slug): string
    {
        $post = $this->posts->findBySlug($slug);
        if (!$post) { return $this->notFound('Post not found'); }
        // return $this->render(__DIR__ . '/../Views/posts/show.php', ['post' => $post]);
        return $this->json($post);
    }

    public function create(): string
    {
        $authors = $this->authors->all();
        return $this->render(__DIR__ . '/../Views/posts/create.php', ['authors' => $authors]);
    }

    public function edit(int $id): string
    {
        $post = $this->posts->findById($id);
        if (!$post) { return $this->notFound('Post not found'); }
        $authors = $this->authors->all();
        return $this->render(__DIR__ . '/../Views/posts/edit.php', ['post' => $post, 'authors' => $authors]);
    }

    public function store(): string
    {
        $id = $this->posts->create($_POST);
        // Simple redirect
        header('Location: /blog/posts/' . htmlspecialchars((string)$id));
        return '';
    }

    public function update(int $id): string
    {
        $this->posts->update($id, $_POST);
        header('Location: /blog/posts/' . htmlspecialchars((string)$id));
        return '';
    }

    public function destroy(int $id): string
    {
        $this->posts->delete($id);
        header('Location: /blog/posts');
        return '';
    }
}
```

Notes
- The base `Controller` provides helpers like `json()` and may provide `render()` (see Part 1’s HelloWorld example). If your base controller differs, adjust accordingly.
- For production, prefer named routes and a Redirect helper. For now, simple header redirects are sufficient.

---

## 4) Routes

Edit `Modules/Blog/routes.php` and add routes for posts and authors. If the file doesn’t exist, create it with this wrapper.

```php
<?php
declare(strict_types=1);

use Ishmael\Core\Router;
use Modules\Blog\Controllers\AuthorsController;
use Modules\Blog\Controllers\PostsController;

return function (Router $router): void {
    // Lists
    $router->get('/blog/authors', [AuthorsController::class, 'index'])->name('blog.authors.index');
    $router->get('/blog/posts', [PostsController::class, 'index'])->name('blog.posts.index');

    // Show post by slug
    $router->get('/blog/posts/{slug}', [PostsController::class, 'show'])->name('blog.posts.show');

    // Create/edit
    $router->get('/blog/posts/create', [PostsController::class, 'create'])->name('blog.posts.create');
    $router->post('/blog/posts', [PostsController::class, 'store'])->name('blog.posts.store');
    $router->get('/blog/posts/{id}/edit', [PostsController::class, 'edit'])->name('blog.posts.edit');
    $router->post('/blog/posts/{id}', [PostsController::class, 'update'])->name('blog.posts.update');
    $router->post('/blog/posts/{id}/delete', [PostsController::class, 'destroy'])->name('blog.posts.destroy');
};
```

Tip: If you cache routes in production, remember to run:

```bash
php vendor/bin/ish route:cache --env=production
```

---

## 5) Minimal views to make it work

The `make:views` command created starter files in `Modules/Blog/Views/posts`. Replace their contents with the examples below to get a working UI quickly.

`Views/posts/index.php`

```php
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Blog — Posts</title>
  <link rel="stylesheet" href="https://unpkg.com/@picocss/pico@2/css/pico.min.css">
  <style> body { padding: 1.25rem; } table { width: 100%; } </style>
</head>
<body>
<main class="container">
  <h1>Posts</h1>
  <p><a href="/blog/posts/create">Create Post</a></p>
  <table>
    <thead><tr><th>Title</th><th>Author</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach (($items ?? []) as $p): ?>
      <tr>
        <td><a href="/blog/posts/<?= htmlspecialchars($p['slug'] ?? (string)$p['id']) ?>"><?= htmlspecialchars($p['title'] ?? '') ?></a></td>
        <td><?= htmlspecialchars($p['author_name'] ?? '') ?></td>
        <td>
          <a href="/blog/posts/<?= (int)($p['id'] ?? 0) ?>/edit">Edit</a>
          <form action="/blog/posts/<?= (int)($p['id'] ?? 0) ?>/delete" method="post" style="display:inline">
            <button type="submit" aria-label="Delete">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</main>
</body>
</html>
```

`Views/posts/_form.php`

```php
<form method="post" action="<?= htmlspecialchars($action ?? '') ?>">
  <label>Title
    <input type="text" name="title" value="<?= htmlspecialchars($post['title'] ?? '') ?>" required>
  </label>
  <label>Slug (optional)
    <input type="text" name="slug" value="<?= htmlspecialchars($post['slug'] ?? '') ?>">
  </label>
  <label>Author
    <select name="author_id" required>
      <?php foreach (($authors ?? []) as $a): ?>
        <option value="<?= (int)$a['id'] ?>" <?= isset($post['author_id']) && (int)$post['author_id']===(int)$a['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($a['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>Body
    <textarea name="body" rows="8" required><?= htmlspecialchars($post['body'] ?? '') ?></textarea>
  </label>
  <button type="submit">Save</button>
</form>
```

`Views/posts/create.php`

```php
<?php $post = $post ?? []; $authors = $authors ?? []; $action = '/blog/posts'; ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Create Post</title>
  <link rel="stylesheet" href="https://unpkg.com/@picocss/pico@2/css/pico.min.css">
</head>
<body>
<main class="container">
  <h1>Create Post</h1>
  <?php include __DIR__ . '/_form.php'; ?>
</main>
</body>
</html>
```

`Views/posts/edit.php`

```php
<?php $authors = $authors ?? []; $action = '/blog/posts/' . (int)($post['id'] ?? 0); ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Edit Post</title>
  <link rel="stylesheet" href="https://unpkg.com/@picocss/pico@2/css/pico.min.css">
</head>
<body>
<main class="container">
  <h1>Edit Post</h1>
  <?php include __DIR__ . '/_form.php'; ?>
</main>
</body>
</html>
```

`Views/posts/show.php`

```php
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title><?= htmlspecialchars($post['title'] ?? 'Post') ?></title>
  <link rel="stylesheet" href="https://unpkg.com/@picocss/pico@2/css/pico.min.css">
</head>
<body>
<main class="container">
  <p><a href="/blog/posts">← Back to posts</a></p>
  <h1><?= htmlspecialchars($post['title'] ?? '') ?></h1>
  <article>
    <p><?= nl2br(htmlspecialchars($post['body'] ?? '')) ?></p>
  </article>
</main>
</body>
</html>
```

After saving the views, switch the `PostsController` actions for `index()` and `show()` to call `$this->render(...)` instead of `json()` to see the pages.

---

## 6) Try it out

1. Ensure migrations have been run and seeds inserted (from Part 2).
2. Visit `/blog/posts` — you should see your seeded posts.
3. Create a new post at `/blog/posts/create` — pick an author and submit.
4. Edit a post via the Edit link.
5. Delete a post via the inline Delete button.

If routes don’t change after edits, clear caches:

```bash
php vendor/bin/ish route:clear
php vendor/bin/ish modules:clear
```

---

## 7) Apply these lessons to other apps

The same pattern generalizes beyond the blog:
- Sales app: CustomersService, OrdersService, and controllers; tables `customers`, `orders`, `order_items` with FKs. Use `make:views` to bootstrap UIs quickly.
- Docs/Knowledge app: AuthorsService + ArticlesService with `authors` and `articles` tables; add `revisions` later.
- Soft deletes and auditing: Add `deleted_at` and `timestamps()` in your migrations. Filter on `deleted_at IS NULL` in services by default, and expose `restore()` actions in controllers when needed.

Design guidance:
- Keep controllers thin: parse inputs and orchestrate, but keep SQL in services.
- Model relationships explicitly with foreign keys and add indexes for the columns you filter/join on.
- Prefer named routes; centralize URL generation once your app grows.

---

## Useful references
- Guide: [Controllers & Views](../guide/controllers-and-views.md)
- Database: [Phase 12 — Database Additions](../database/phase-12-database-additions.md)
- How‑to: [Create and Run Seeders](../how-to/create-and-run-seeders.md)
- Reference: [CLI Commands (generated)](../reference/cli-commands.md)

---

## What you learned
- How to implement Controllers and Services that work together.
- How to wire routes and generate starter views with `make:views`.
- How to perform basic create/edit/delete flows tied to foreign keys (Posts → Authors).
- How to carry these patterns into other modules and apps built on Ishmael.
