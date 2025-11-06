# Create and Run Seeders

Date: 2025-11-04

Seeders populate your database with deterministic dev/test data. Ishmael keeps seeders module-first and provides a small, explicit API with environment guards.

Key points
- Filesystem layout: Modules/<Module>/Database/Seeders/
- Each seeder is a class with run() and optional dependsOn()
- Execution is ordered deterministically with a topological sort
- Environment guard: runs only in dev/test/local by default (force to override)

Seeder contracts

- Implement SeederInterface, or extend BaseSeeder for a convenient default implementation of dependsOn().

Example seeder

```php
<?php
use Ishmael\Core\Database\Seeders\BaseSeeder;
use Ishmael\Core\DatabaseAdapters\DatabaseAdapterInterface;
use Psr\Log\LoggerInterface;

class ExampleSeeder extends BaseSeeder
{
    /** @return string[] */
    public function dependsOn(): array
    {
        // return [OtherSeeder::class];
        return [];
    }

    public function run(DatabaseAdapterInterface $adapter, LoggerInterface $logger): void
    {
        // Deterministic logic: check then insert
        $row = $adapter->query('SELECT id FROM widgets WHERE slug = :s', [':s' => 'example'])->first();
        if (!$row) {
            $adapter->execute('INSERT INTO widgets (slug, name) VALUES (:s,:n)', [':s' => 'example', ':n' => 'Example']);
        }
        $logger->info('ExampleSeeder completed.');
    }
}
```

Module entrypoint seeder

- You may add a DatabaseSeeder class in the same folder to serve as an entrypoint. If present, the runner will resolve and execute DatabaseSeeder and all its dependencies. Otherwise, all seeders in the folder are executed.

Template files

- Templates/Module/Database/Seeders/DatabaseSeeder.php
- Templates/Module/Database/Seeders/ExampleSeeder.php

Environment guard

- SeederRunner only runs in environments: dev, development, test, testing, local.
- To run in other environments (e.g., staging or production) you must explicitly force it.

Programmatic API

```php
use Ishmael\Core\Database\Seeders\SeederRunner;
use Ishmael\Core\DatabaseAdapters\DatabaseAdapterInterface;

$adapter = /* resolve your DatabaseAdapterInterface and connect */;
$runner = new SeederRunner($adapter, app('logger'));

// Run all modules (dev/test only by default)
$runner->seed();

// Run for a single module
$runner->seed(module: 'HelloWorld');

// Run a specific seeder (and its dependencies)
$runner->seed(module: 'HelloWorld', class: 'ExampleSeeder');

// Override environment guard (dangerous — know what you are doing)
$runner->seed(module: 'HelloWorld', force: true, env: 'production');
```

Determinism and re-runnability

- Seeders should be idempotent: checking for existence before inserting, or using upsert logic.
- The runner does not store seeding state; re-running should not create duplicates when coded deterministically.

Logging

- SeederRunner logs start, plan, each seeder execution, and a final summary via PSR-3.

Top-level app orchestration (optional)

- You can create an application-level DatabaseSeeder (e.g., under your app) that coordinates module seeders by declaring dependsOn() entries for module DatabaseSeeder classes.

Troubleshooting

- "Seeding is disabled" error: you are outside the allowed environments; pass force: true to override.
- Cyclic dependency detected: resolve the cycle in dependsOn() declarations.


---

## Related reference
- Reference: [CLI Route Commands](../reference/cli-route-commands.md)
- Reference: [Config Keys](../reference/config-keys.md)
- Reference: [Core API (Markdown stubs)](../reference/core-api/_index.md)
