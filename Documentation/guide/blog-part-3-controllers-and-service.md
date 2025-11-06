# Blog Tutorial — Part 3: Controllers and PostService

In Part 3 you will:
- Implement PostController actions for index/show/create/edit/store/update/destroy.
- Create a lightweight PostService to encapsulate DB access.

Prerequisites:
- You completed Part 2 and have a posts table with sample data.

## 1) Create PostService

Create `Modules/Blog/Services/PostService.php`:

```php
<?php

declare(strict_types=1);

namespace Modules\Blog\Services;

use Ishmael\Core\Database\DB;

/**
 * Provides simple data access methods for blog posts.
 */
final class PostService
{
    public function paginate(int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;
        $items = DB::table('posts')->orderBy('id', 'desc')->limit($perPage)->offset($offset)->get();
        $total = (int) DB::table('posts')->count();
        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
        ];
    }

    public function find(int $id): ?array
    {
        return DB::table('posts')->where('id', '=', $id)->first();
    }

    public function create(array $attributes): int
    {
        return (int) DB::table('posts')->insertGetId([
            'title' => (string) ($attributes['title'] ?? ''),
            'body' => (string) ($attributes['body'] ?? ''),
        ]);
    }

    public function update(int $id, array $attributes): void
    {
        DB::table('posts')->where('id', '=', $id)->update([
            'title' => (string) ($attributes['title'] ?? ''),
            'body' => (string) ($attributes['body'] ?? ''),
        ]);
    }

    public function delete(int $id): void
    {
        DB::table('posts')->where('id', '=', $id)->delete();
    }
}
```

## 2) Implement PostController actions

Open `Modules/Blog/Controllers/PostController.php` and implement CRUD actions using the service.

```php
<?php

declare(strict_types=1);

namespace Modules\Blog\Controllers;

use Ishmael\Core\Http\Request;
use Ishmael\Core\Http\Response;
use Modules\Blog\Services\PostService;

final class PostController
{
    public function __construct(private PostService $posts)
    {
    }

    public function index(Request $request, Response $response): Response
    {
        $page = (int) ($request->query('page') ?? 1);
        $data = $this->posts->paginate($page, 10);
        return $response->view('Modules/Blog/Views/posts/index.php', $data);
    }

    public function show(Request $request, Response $response, int $id): Response
    {
        $post = $this->posts->find($id);
        if ($post === null) {
            return $response->withStatus(404);
        }
        return $response->view('Modules/Blog/Views/posts/show.php', ['post' => $post]);
    }

    public function create(Request $request, Response $response): Response
    {
        return $response->view('Modules/Blog/Views/posts/create.php');
    }

    public function edit(Request $request, Response $response, int $id): Response
    {
        $post = $this->posts->find($id);
        if ($post === null) {
            return $response->withStatus(404);
        }
        return $response->view('Modules/Blog/Views/posts/edit.php', ['post' => $post]);
    }

    public function store(Request $request, Response $response): Response
    {
        $id = $this->posts->create($request->all());
        return $response->redirect('/blog/posts/' . $id);
    }

    public function update(Request $request, Response $response, int $id): Response
    {
        $this->posts->update($id, $request->all());
        return $response->redirect('/blog/posts/' . $id);
    }

    public function destroy(Request $request, Response $response, int $id): Response
    {
        $this->posts->delete($id);
        return $response->redirect('/blog/posts');
    }
}
```

Adjust view paths to match your project’s conventions.

## Exact classes and methods referenced
- Controller: `Modules\Blog\Controllers\PostController::{index,show,create,edit,store,update,destroy}`
- Service: `Modules\Blog\Services\PostService::{paginate,find,create,update,delete}`
- Request: `Ishmael\Core\Http\Request`
- Response: `Ishmael\Core\Http\Response`

## Related reading
- Guide: [Controllers & Views](../guide/controllers-and-views.md)
- Guide: [Request & Response](../concepts/request-response.md)
- Reference: [Routes](../reference/routes/_index.md)

## What you learned
- How to encapsulate DB access in a small service.
- How to implement controller actions delegating to a service.
