# Posts Example Module (Skeleton App)

This example demonstrates a minimal, SQL‑first module with one migration, one seeder, and a simple controller to list rows.

What you get
- A Posts module under SkeletonApp/Modules/Posts
- A migration that creates a posts table (id, title, body, created_at)
- A seeder that inserts three deterministic rows
- A controller that lists posts as JSON at GET /posts
- A one‑command demo script to run everything on SQLite

Quick start (copy‑paste)
1) Ensure your SkeletonApp database config uses SQLite (recommended for a quick demo). In SkeletonApp/config/database.php set the default connection to 'sqlite' and (optionally) use storage/database.sqlite as the database path.

2) Run the demo script from the repository root:

```bash
php bin/demo_posts.php
```

You should see output similar to:

```
=== Posts Example ===
[1] Hello Ishmael
First post seeded by PostsSeeder
-- 2025-11-04 15:05:00

[2] Second Post
Seeding makes demos reproducible
-- 2025-11-04 15:05:00

[3] SQLite Friendly
Works out of the box with one command
-- 2025-11-04 15:05:00
```

3) Start the dev server for the SkeletonApp and visit /posts

If you are using PHP’s built‑in server from the SkeletonApp directory:

```bash
php -S 127.0.0.1:8080 -t public
```

Then browse to:

```
http://127.0.0.1:8080/posts
```

You should see a JSON array of posts.

Module layout
```
SkeletonApp/
  Modules/
    Posts/
      Controllers/
        PostsController.php    # GET /posts → list rows as JSON
      Database/
        Migrations/
          20251104150500_CreatePostsTable.php
        Seeders/
          PostsSeeder.php
      routes.php               # closure‑based routes: GET /posts
```

How it works
- bin/demo_posts.php uses the thin Migrator and SeedManager APIs to apply the module’s migration and run the seeder programmatically.
- Migrations live next to the module, named with a timestamp prefix and extending BaseMigration.
- Seeders extend BaseSeeder and implement run($adapter, $logger) with deterministic, re‑runnable logic.

Troubleshooting
- If php bin/demo_posts.php reports that seeding is disabled in your environment, it’s because seeders are guarded in production. The demo script forces seeding on by passing force=true.
- If you don’t see data on /posts, ensure you ran the demo script and your app is using the same SQLite database file.


---

## Related reference
- Reference: [Routes](../reference/routes/_index.md)
- Reference: [CLI Route Commands](../reference/cli-route-commands.md)
- Reference: [Core API (Markdown stubs)](../reference/core-api/_index.md)
