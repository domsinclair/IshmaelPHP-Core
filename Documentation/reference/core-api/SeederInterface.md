# SeederInterface

Namespace: `Ishmael\Core\Database\Seeders`  
Source: `IshmaelPHP-Core\app\Core\Database\Seeders\SeederInterface.php`

Interface SeederInterface

### Public methods
- `dependsOn(): array` — Optional list of seeder classes that this seeder depends on.
- `run(Ishmael\Core\DatabaseAdapters\DatabaseAdapterInterface $adapter, Psr\Log\LoggerInterface $logger): void` — Execute the seeder. Implementations should be deterministic and re-runnable.
