# Blog Tutorial — Part 4: Views with Layout and Sections

In Part 4 you will:
- Create simple PHP views for posts.
- Use a layout with minimal sections helper (yield/start/end) if available in your version.
- Add a reusable _form partial used by create and edit.

Prerequisites:
- Parts 1–3 completed. PostController and PostService exist.

## 1) Create a layout (optional but recommended)

Create `Modules/Blog/Views/layout.php`:

```php
<?php
/** @var \Ishmael\Core\View\ViewSections $sections */
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo $sections->yield('title', 'Blog'); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@3.4.0/dist/tailwind.min.css" />
</head>
<body class="min-h-screen bg-gray-50 text-gray-900">
<header class="p-4 bg-white border-b">
    <div class="container mx-auto">
        <h1 class="text-xl font-semibold">Ishmael Blog</h1>
    </div>
</header>
<main class="container mx-auto p-4">
    <?php echo $sections->yield('content'); ?>
</main>
</body>
</html>
```

## 2) Create the _form partial

Create `Modules/Blog/Views/posts/_form.php`:

```php
<?php
/** @var array{post?: array<string,mixed>} $data */
$post = $data['post'] ?? ['title' => '', 'body' => ''];
?>
<div class="space-y-4">
    <div>
        <label class="block text-sm font-medium">Title</label>
        <input name="title" class="border rounded w-full p-2" value="<?php echo htmlspecialchars((string)($post['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" />
    </div>
    <div>
        <label class="block text-sm font-medium">Body</label>
        <textarea name="body" class="border rounded w-full p-2" rows="8"><?php echo htmlspecialchars((string)($post['body'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
    </div>
</div>
```

## 3) Create index view

Create `Modules/Blog/Views/posts/index.php`:

```php
<?php
/** @var array{items: array<int, array<string,mixed>>} $data */
$layoutFile = __DIR__ . '/../layout.php';
$sections->start('title'); ?>Posts<?php $sections->end();
$sections->start('content');
?>
<div class="flex items-center justify-between mb-4">
    <h2 class="text-2xl font-semibold">Posts</h2>
    <a class="px-3 py-2 bg-blue-600 text-white rounded" href="/blog/posts/create">New Post</a>
</div>
<ul class="space-y-2">
    <?php foreach (($data['items'] ?? []) as $post): ?>
        <li class="p-3 bg-white border rounded">
            <a class="text-blue-700" href="/blog/posts/<?php echo (int)$post['id']; ?>">
                <?php echo htmlspecialchars((string)$post['title'], ENT_QUOTES, 'UTF-8'); ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>
<?php $sections->end();
```

## 4) Create show view

Create `Modules/Blog/Views/posts/show.php`:

```php
<?php
/** @var array{post: array<string,mixed>} $data */
$layoutFile = __DIR__ . '/../layout.php';
$post = $data['post'];
$sections->start('title'); echo htmlspecialchars((string)$post['title']); $sections->end();
$sections->start('content');
?>
<article class="prose max-w-none">
    <h2 class="text-3xl font-bold mb-2"><?php echo htmlspecialchars((string)$post['title']); ?></h2>
    <div class="mt-4 whitespace-pre-wrap"><?php echo nl2br(htmlspecialchars((string)$post['body'])); ?></div>
</article>
<div class="mt-6 flex space-x-2">
    <a class="px-3 py-2 bg-gray-200 rounded" href="/blog/posts">Back</a>
    <a class="px-3 py-2 bg-blue-600 text-white rounded" href="/blog/posts/<?php echo (int)$post['id']; ?>/edit">Edit</a>
    <form method="post" action="/blog/posts/<?php echo (int)$post['id']; ?>/delete">
        <button class="px-3 py-2 bg-red-600 text-white rounded" type="submit">Delete</button>
    </form>
</div>
<?php $sections->end();
```

## 5) Create create and edit views

Create `Modules/Blog/Views/posts/create.php`:

```php
<?php
$layoutFile = __DIR__ . '/../layout.php';
$sections->start('title'); ?>New Post<?php $sections->end();
$sections->start('content');
?>
<h2 class="text-2xl font-semibold mb-4">New Post</h2>
<form method="post" action="/blog/posts" class="space-y-4">
    <?php $data = []; include __DIR__ . '/_form.php'; ?>
    <button class="px-3 py-2 bg-blue-600 text-white rounded" type="submit">Create</button>
</form>
<?php $sections->end();
```

Create `Modules/Blog/Views/posts/edit.php`:

```php
<?php
/** @var array{post: array<string,mixed>} $data */
$layoutFile = __DIR__ . '/../layout.php';
$sections->start('title'); ?>Edit Post<?php $sections->end();
$sections->start('content');
?>
<h2 class="text-2xl font-semibold mb-4">Edit Post</h2>
<form method="post" action="/blog/posts/<?php echo (int)$data['post']['id']; ?>" class="space-y-4">
    <?php include __DIR__ . '/_form.php'; ?>
    <button class="px-3 py-2 bg-blue-600 text-white rounded" type="submit">Save</button>
</form>
<?php $sections->end();
```

Note: If your current framework version doesn’t yet provide `$sections` or `$layoutFile` support, you can render these without a layout and skip section calls. This tutorial anticipates the minimal helper described in Phase‑9.

## Exact classes and variables
- Sections helper (if available): `Ishmael\Core\View\ViewSections` exposed to views as `$sections`
- Controller rendering exposes: `$request`, `$response`, `$data` (convention)

## Related reading
- Guide: [Controllers & Views](./controllers-and-views.md)
- How‑to: [Generate URLs in views/controllers](../how-to/generate-urls-in-views-and-controllers.md)
- Guide: [Security Headers](./security-headers.md)

## What you learned
- How to structure views, use a layout, and share markup through partials.
- How to opt a child view into a layout using `$layoutFile` and sections.
