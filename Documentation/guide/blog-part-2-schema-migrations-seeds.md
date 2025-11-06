# Blog Tutorial — Part 2: Schema, Migrations, and Seeds

In Part 2 you will:
- Define the posts table schema.
- Create a migration and run it.
- Seed a few example posts.

Prerequisites:
- You completed Part 1 and have the Blog module scaffolded.

## 1) Create a migration

Generate a migration for the Blog module:

```bash
php IshmaelPHP-Core/bin/ishmael make:migration Blog create_posts_table
```

Open the generated migration file and define the schema:

```php
<?php

declare(strict_types=1);

use Ishmael\Core\Database\Schema\Schema;
use Ishmael\Core\Database\Schema\Blueprint;

return new class {
    public function up(Schema $schema): void
    {
        $schema->create('posts', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(Schema $schema): void
    {
        $schema->dropIfExists('posts');
    }
};
```

Notes:
- Use camelCase for method and variable names; avoid snake_case.
- The Schema/Blueprint APIs may vary slightly depending on your adapter; consult the reference.

## 2) Run the migration

```bash
php IshmaelPHP-Core/bin/ishmael migrate
```

Check the database to confirm the posts table exists.

## 3) Create a seeder

```bash
php IshmaelPHP-Core/bin/ishmael make:seeder Blog PostsSeeder
```

Edit the seeder to insert a few posts:

```php
<?php

declare(strict_types=1);

namespace Modules\Blog\Seeders;

use Ishmael\Core\Database\DB;

final class PostsSeeder
{
    public function run(): void
    {
        DB::table('posts')->insert([
            ['title' => 'Hello World', 'body' => 'First post body'],
            ['title' => 'Second Post', 'body' => 'More content'],
        ]);
    }
}
```

Run the seeder:

```bash
php IshmaelPHP-Core/bin/ishmael db:seed Blog PostsSeeder
```

## Useful references
- Guide: [Writing and running migrations](../guide/writing-and-running-migrations.md)
- How‑to: [Create and Run Seeders](../how-to/create-and-run-seeders.md)
- Reference: [Config Keys](../reference/config-keys.md)

## Exact classes used
- Schema: `Ishmael\Core\Database\Schema\Schema`
- Blueprint: `Ishmael\Core\Database\Schema\Blueprint`

## What you learned
- How to create and run a migration in a module context.
- How to seed initial data for the Blog module.
