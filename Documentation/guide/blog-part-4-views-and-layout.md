# Blog Tutorial — Part 4 (Revised): Views, Layout, Forms, and Tailwind v4

In this revised Part 4 you will:
- Build a real layout and reusable view partials.
- Flesh out the generated view stubs for Authors + Posts so they talk to controllers and services.
- Add good UX for forms (create/edit) including an Author select and slug handling.
- Wire your layout to a compiled Tailwind CSS v4 file (not a CDN) and optionally HTMX.
- Learn patterns you can reuse across modules (Sales, Docs, etc.).

Prerequisites:
- Completed Parts 1–3 (module scaffold, migrations + seeds, controllers + services).
- You can run the Ishmael CLI from your app root:

  ```bash
  php vendor/bin/ish
  ```

Tip: This part focuses on views. For a deeper dive into CSS/JS/HTMX/TypeScript, see Parts 13–16 and link your assets into the layout built here.

---

## 1) Create a production‑ready layout

We’ll use a simple layout that:
- Links a compiled Tailwind v4 CSS bundle (built once, reused everywhere).
- Shows a top navigation for the Blog module.
- Exposes a slot for page title and main content. If your version has a sections helper, we’ll use it; otherwise we’ll show a no‑sections fallback.

First, pick a path for your compiled CSS. Common choices:
- App‑wide: `public/assets/app.css`
- Module‑scoped: `public/modules/blog/blog.css`

We’ll reference `/assets/app.css` below to keep it simple. See Part 13 for how to build it. A quick Tailwind v4 build reminder:

```bash
npm init -y
npm install tailwindcss @tailwindcss/cli --save-dev

# Create source
echo "@import 'tailwindcss';" > resources/css/app.css

# Build once (production)
npx @tailwindcss/cli -i resources/css/app.css -o public/assets/app.css --minify

# Or watch during development
npx @tailwindcss/cli -i resources/css/app.css -o public/assets/app.css --watch
```

Create `Modules/Blog/Views/layout.php`:

```php
<?php
/** Optional: \Ishmael\Core\View\ViewSections $sections when available */
?><!doctype html>
<html lang="en" class="h-full">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= isset($sections) ? $sections->yield('title', 'Blog') : htmlspecialchars($title ?? 'Blog') ?></title>
  <link rel="stylesheet" href="/assets/app.css" />
  <!-- Optional progressive enhancement libraries (see Parts 14–16): -->
  <!-- <script src="https://unpkg.com/htmx.org@1.9.12" defer></script> -->
</head>
<body class="min-h-screen bg-zinc-50 text-zinc-900">
  <header class="border-b bg-white/90 backdrop-blur">
    <div class="max-w-5xl mx-auto px-4 py-3 flex items-center gap-6">
      <a href="/" class="font-semibold">Ishmael Starter</a>
      <nav class="text-sm text-zinc-600 flex gap-4">
        <a href="/blog/posts" class="hover:text-zinc-900">Posts</a>
        <a href="/blog/authors" class="hover:text-zinc-900">Authors</a>
      </nav>
    </div>
  </header>
  <main class="max-w-5xl mx-auto px-4 py-6">
    <?php if (isset($sections)): ?>
      <?= $sections->yield('content') ?>
    <?php else: ?>
      <?= $content ?? '' ?>
    <?php endif; ?>
  </main>
</body>
</html>
```

Notes
- We intentionally avoid a CSS CDN. Your bundle at `/assets/app.css` is built with Tailwind v4 CLI; see Part 13 for full instructions, dark mode tips, and module‑scoped assets.
- If your app doesn’t have the `$sections` helper yet, pass `$title` and `$content` from controllers (fallback shown above).

---

## 2) Reusable partials: flash + form fields

Create `Modules/Blog/Views/_flash.php` to show simple messages (optional):

```php
<?php if (!empty($flash ?? '')): ?>
  <div class="mb-4 rounded border border-emerald-200 bg-emerald-50 text-emerald-900 px-3 py-2 text-sm">
    <?= htmlspecialchars($flash) ?>
  </div>
<?php endif; ?>
```

Create `Modules/Blog/Views/posts/_form.php` with an Author dropdown and slug input:

```php
<?php
/** @var array $post */
/** @var array<int,array{id:int,name:string}> $authors */
$post = $post ?? ['title' => '', 'slug' => '', 'body' => '', 'author_id' => ''];
?>
<div class="grid gap-4">
  <label class="grid gap-1">
    <span class="text-sm font-medium">Title</span>
    <input name="title" value="<?= htmlspecialchars((string)($post['title'] ?? '')) ?>" required class="border rounded px-3 py-2" />
  </label>

  <label class="grid gap-1">
    <span class="text-sm font-medium">Slug (optional)</span>
    <input name="slug" value="<?= htmlspecialchars((string)($post['slug'] ?? '')) ?>" class="border rounded px-3 py-2" />
    <span class="text-xs text-zinc-500">Leave blank to auto‑generate from Title.</span>
  </label>

  <label class="grid gap-1">
    <span class="text-sm font-medium">Author</span>
    <select name="author_id" required class="border rounded px-3 py-2">
      <option value="" disabled <?= empty($post['author_id'] ?? '') ? 'selected' : '' ?>>Choose an author</option>
      <?php foreach (($authors ?? []) as $a): $aid = (int)$a['id']; ?>
        <option value="<?= $aid ?>" <?= (int)($post['author_id'] ?? 0) === $aid ? 'selected' : '' ?>>
          <?= htmlspecialchars($a['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>

  <label class="grid gap-1">
    <span class="text-sm font-medium">Body</span>
    <textarea name="body" rows="10" class="border rounded px-3 py-2"><?= htmlspecialchars((string)($post['body'] ?? '')) ?></textarea>
  </label>
</div>
```

---

## 3) Posts views (index, show, create, edit)

These views assume the controllers from Part 3 call `render()` with arrays like `['items' => ..., 'post' => ..., 'authors' => ...]`.

Create (or replace) `Modules/Blog/Views/posts/index.php`:

```php
<?php
/** @var array{items: array<int, array{id:int,title:string,slug?:string,author_name?:string,created_at?:string}>, page?:int, perPage?:int, total?:int} $data */
$layoutFile = __DIR__ . '/../layout.php';
if (isset($sections)) { $sections->start('title'); echo 'Posts'; $sections->end(); $sections->start('content'); }
?>

<?php $flash = $flash ?? ''; include __DIR__ . '/../_flash.php'; ?>

<div class="flex items-center justify-between mb-4">
  <h1 class="text-2xl font-semibold">Posts</h1>
  <a class="inline-flex items-center rounded bg-blue-600 px-3 py-2 text-white hover:bg-blue-700" href="/blog/posts/create">New Post</a>
</div>

<div class="divide-y rounded border bg-white">
  <?php foreach (($items ?? []) as $p): ?>
    <div class="p-4 flex items-center justify-between gap-4">
      <div>
        <a class="font-medium text-blue-700 hover:underline" href="/blog/posts/<?= htmlspecialchars($p['slug'] ?? (string)$p['id']) ?>">
          <?= htmlspecialchars($p['title'] ?? '') ?>
        </a>
        <?php if (!empty($p['author_name'])): ?>
          <div class="text-sm text-zinc-600">by <?= htmlspecialchars($p['author_name']) ?></div>
        <?php endif; ?>
      </div>
      <div class="flex items-center gap-3">
        <a class="text-sm text-zinc-700 hover:text-zinc-900" href="/blog/posts/<?= (int)($p['id'] ?? 0) ?>/edit">Edit</a>
        <form action="/blog/posts/<?= (int)($p['id'] ?? 0) ?>/delete" method="post">
          <button class="text-sm text-red-600 hover:text-red-700" type="submit">Delete</button>
        </form>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php if (!empty($total ?? null) && !empty($perPage ?? null)): $pages = max(1, (int)ceil(($total ?? 0) / ($perPage ?? 10))); $page = (int)($page ?? 1); ?>
  <nav class="mt-4 flex items-center justify-between text-sm">
    <a class="px-2 py-1 rounded border <?= $page <= 1 ? 'opacity-50 pointer-events-none' : '' ?>" href="?page=<?= max(1, $page-1) ?>">Prev</a>
    <span>Page <?= $page ?> of <?= $pages ?></span>
    <a class="px-2 py-1 rounded border <?= $page >= $pages ? 'opacity-50 pointer-events-none' : '' ?>" href="?page=<?= min($pages, $page+1) ?>">Next</a>
  </nav>
<?php endif; ?>

<?php if (isset($sections)) { $sections->end(); } ?>
```

Create (or replace) `Modules/Blog/Views/posts/show.php`:

```php
<?php
/** @var array{post: array{id:int,title:string,slug?:string,body:string,author_name?:string}} $data */
$layoutFile = __DIR__ . '/../layout.php';
$post = $post ?? ($data['post'] ?? []);
if (isset($sections)) { $sections->start('title'); echo htmlspecialchars((string)($post['title'] ?? 'Post')); $sections->end(); $sections->start('content'); }
?>

<article class="prose max-w-none">
  <h1 class="mb-1 text-3xl font-bold"><?= htmlspecialchars((string)($post['title'] ?? '')) ?></h1>
  <?php if (!empty($post['author_name'])): ?>
    <p class="mt-0 text-sm text-zinc-600">by <?= htmlspecialchars($post['author_name']) ?></p>
  <?php endif; ?>
  <div class="mt-6 whitespace-pre-wrap leading-7"><?= nl2br(htmlspecialchars((string)($post['body'] ?? ''))) ?></div>
  <div class="mt-8 flex items-center gap-3">
    <a class="rounded border px-3 py-1" href="/blog/posts">Back</a>
    <a class="rounded bg-blue-600 px-3 py-1 text-white" href="/blog/posts/<?= (int)($post['id'] ?? 0) ?>/edit">Edit</a>
  </div>
  
  <!-- Example: Add an HTMX inline comment form here (see Part 15) -->
</article>

<?php if (isset($sections)) { $sections->end(); } ?>
```

Create (or replace) `Modules/Blog/Views/posts/create.php`:

```php
<?php
/** @var array{authors: array<int,array{id:int,name:string}>} $data */
$layoutFile = __DIR__ . '/../layout.php';
if (isset($sections)) { $sections->start('title'); echo 'Create Post'; $sections->end(); $sections->start('content'); }
$authors = $authors ?? ($data['authors'] ?? []);
?>

<h1 class="text-2xl font-semibold mb-4">Create Post</h1>
<form method="post" action="/blog/posts" class="grid gap-4">
  <?php $post = $post ?? []; include __DIR__ . '/_form.php'; ?>
  <div class="flex items-center gap-3">
    <button class="rounded bg-blue-600 px-3 py-2 text-white" type="submit">Save</button>
    <a class="rounded border px-3 py-2" href="/blog/posts">Cancel</a>
  </div>
  <p class="text-xs text-zinc-500">On save, the controller calls PostService::create(), which inserts the row and redirects.</p>
  <input type="hidden" name="_intent" value="create-post">
</form>

<?php if (isset($sections)) { $sections->end(); } ?>
```

Create (or replace) `Modules/Blog/Views/posts/edit.php`:

```php
<?php
/** @var array{post: array, authors: array<int,array{id:int,name:string}>} $data */
$layoutFile = __DIR__ . '/../layout.php';
if (isset($sections)) { $sections->start('title'); echo 'Edit Post'; $sections->end(); $sections->start('content'); }
$post = $post ?? ($data['post'] ?? []);
$authors = $authors ?? ($data['authors'] ?? []);
?>

<h1 class="text-2xl font-semibold mb-4">Edit Post</h1>
<form method="post" action="/blog/posts/<?= (int)($post['id'] ?? 0) ?>" class="grid gap-4">
  <?php include __DIR__ . '/_form.php'; ?>
  <div class="flex items-center gap-3">
    <button class="rounded bg-blue-600 px-3 py-2 text-white" type="submit">Save</button>
    <a class="rounded border px-3 py-2" href="/blog/posts">Cancel</a>
  </div>
  <input type="hidden" name="_intent" value="update-post">
</form>

<?php if (isset($sections)) { $sections->end(); } ?>
```

---

## 4) Authors views (bonus)

Add a basic list so you can link to it from the layout. Create `Modules/Blog/Views/authors/index.php`:

```php
<?php
/** @var array{items?: array<int,array{id:int,name:string,email?:string}>} $data */
$layoutFile = __DIR__ . '/../layout.php';
if (isset($sections)) { $sections->start('title'); echo 'Authors'; $sections->end(); $sections->start('content'); }
?>

<h1 class="text-2xl font-semibold mb-4">Authors</h1>
<div class="rounded border bg-white divide-y">
  <?php foreach (($items ?? []) as $a): ?>
    <div class="p-4">
      <div class="font-medium"><?= htmlspecialchars($a['name']) ?></div>
      <?php if (!empty($a['email'])): ?><div class="text-sm text-zinc-600"><?= htmlspecialchars($a['email']) ?></div><?php endif; ?>
    </div>
  <?php endforeach; ?>
  <?php if (empty($items ?? [])): ?>
    <div class="p-4 text-sm text-zinc-600">No authors yet.</div>
  <?php endif; ?>
  
</div>

<?php if (isset($sections)) { $sections->end(); } ?>
```

Controller tip: In `AuthorsController::index()` return `$this->render(__DIR__ . '/../Views/authors/index.php', ['items' => $this->authors->all()]);`.

---

## 5) Wiring layout with and without sections

Depending on your Ishmael version, you might have a sections helper injected as `$sections` into views. If present, set `$layoutFile` in the child view file (as shown above), then:

```php
// Child view pseudo‑pattern
$layoutFile = __DIR__ . '/../layout.php';
$sections->start('title'); ?>Page Title<?php $sections->end();
$sections->start('content');
?>
  <!-- Your content here -->
<?php $sections->end();
```

If you don’t have sections yet, have controllers assemble `$content` using output buffering and pass `$title` and `$content` to the layout:

```php
// Inside controller action (fallback approach)
$title = 'Posts';
ob_start();
  require __DIR__ . '/../Views/posts/_table.php'; // or inline markup
$content = ob_get_clean();
return $this->render(__DIR__ . '/../Views/layout.php', compact('title', 'content'));
```

---

## 6) Link to Tailwind v4 and JS/HTMX resources (Parts 13–16)

- Part 13 (CSS): Explains Tailwind v4 in depth, including module‑scoped builds and dark mode. In this part we referenced `/assets/app.css`; build it with Tailwind v4 CLI.
- Part 14 (JavaScript): Shows where to put minimal interactivity and how to keep JS module‑scoped.
- Part 15 (Libraries & HTMX): Add HTMX for inline CRUD (e.g., comments under posts) without a full SPA.
- Part 16 (TypeScript): If your project grows, compile TS → `public/assets/app.js` and include it in the layout.

When discussing Tailwind, we stick with Tailwind v4. Example command used above:

```bash
npx @tailwindcss/cli -i resources/css/app.css -o public/assets/app.css --minify
```

In views, reference your compiled CSS via:

```html
<link rel="stylesheet" href="/assets/app.css">
```

---

## 7) End‑to‑end: make the screens live

To switch from JSON responses (used for quick checks in Part 3) to HTML screens, update controller actions:

```php
// PostsController@index
$data = $this->posts->paginateWithAuthors((int)($_GET['page'] ?? 1), 10);
return $this->render(__DIR__ . '/../Views/posts/index.php', $data);

// PostsController@show
$post = $this->posts->findBySlug($slug);
if (!$post) { return $this->notFound('Post not found'); }
return $this->render(__DIR__ . '/../Views/posts/show.php', ['post' => $post]);

// PostsController@create
$authors = $this->authors->all();
return $this->render(__DIR__ . '/../Views/posts/create.php', ['authors' => $authors]);

// PostsController@edit
$post = $this->posts->findById($id);
$authors = $this->authors->all();
return $this->render(__DIR__ . '/../Views/posts/edit.php', ['post' => $post, 'authors' => $authors]);
```

Now visit:
- `/blog/posts` → list with actions
- `/blog/posts/create` → form with Author select
- `/blog/posts/{slug}` → post detail

If routes don’t reflect changes, clear caches:

```bash
php vendor/bin/ish route:clear
php vendor/bin/ish modules:clear
```

---

## 8) Apply these patterns to other modules

- Sales: customer layout + partials for order forms; Tailwind components shared across screens.
- Docs: authors + articles with shared layout, markdown preview enhanced with HTMX (Part 15) and TS (Part 16).
- Admin: one app‑wide layout plus module‑scoped partials; compile Tailwind once and reuse.

Design guidance
- Keep markup in views, not in services.
- Reuse partials for repeated UI (forms, tables, flashes).
- Build once (Tailwind v4), serve from `/assets/app.css`, and avoid shipping unused CSS.

---

## Related reading
- Guide: [Controllers & Views](./controllers-and-views.md)
- Database: [Phase 12 — Database Additions](../database/phase-12-database-additions.md)
- Blog Part 13: [CSS for the Blog](./blog-part-13-css-for-the-blog.md)
- Blog Part 14: [JavaScript for the Blog](./blog-part-14-javascript-for-the-blog.md)
- Blog Part 15: [JavaScript Libraries and HTMX](./blog-part-15-javascript-libraries-and-htmx.md)
- Blog Part 16: [TypeScript for the Blog](./blog-part-16-typescript-for-the-blog.md)

---

## What you learned
- How to build a production‑ready layout and wire it to a compiled Tailwind v4 CSS bundle.
- How to flesh out generated view stubs to talk to controllers and services (Authors + Posts).
- How to share UI with partials, and how to fall back when sections aren’t available.
- Where CSS/JS/HTMX/TS fit (Parts 13–16) to keep your app clean and fast.
