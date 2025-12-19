<?php
declare(strict_types=1);

namespace Tests\Integration;

final class MigrateSeedTest extends CliTestCase
{
    private string $moduleName;
    private string $moduleDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->moduleName = 'CliMod' . substr(bin2hex(random_bytes(2)), 0, 4);
        $this->moduleDir = $this->appRoot . DIRECTORY_SEPARATOR . 'Modules' . DIRECTORY_SEPARATOR . $this->moduleName;
        if (is_dir($this->moduleDir)) {
            $this->rrmdir($this->moduleDir);
        }
        // Ensure storage directories exist for db/logs
        @mkdir($this->appRoot . DIRECTORY_SEPARATOR . 'storage', 0777, true);
    }

    protected function tearDown(): void
    {
        // Rollback best-effort: remove module and drop table if exists
        if (is_dir($this->moduleDir)) {
            $this->rrmdir($this->moduleDir);
        }
        parent::tearDown();
    }

    public function testMigrateAndSeedAgainstSqlite(): void
    {
        $bin = $this->repoRoot . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'ish';

        // 1) make:module
        $r1 = $this->runPhpScript($bin, ['make:module', $this->moduleName], $this->appRoot);
        $this->assertSame(0, $r1['exit'], 'make:module failed: ' . $r1['err']);

        // 2) make:migration (create items table)
        $r2 = $this->runPhpScript($bin, ['make:migration', $this->moduleName, 'create_items_table'], $this->appRoot);
        $this->assertSame(0, $r2['exit'], 'make:migration failed: ' . $r2['err'] . ' OUT: ' . $r2['out']);

        // Find migration file and replace contents with a simple table create
        $migrationsDir = $this->moduleDir . DIRECTORY_SEPARATOR . 'Database' . DIRECTORY_SEPARATOR . 'Migrations';
        $files = glob($migrationsDir . DIRECTORY_SEPARATOR . '*_CreateItemsTable.php') ?: [];
        if (empty($files)) {
             // Fallback for case-sensitive filesystems or different naming conventions in recent ish changes
             $files = glob($migrationsDir . DIRECTORY_SEPARATOR . '*_create_items_table.php') ?: [];
        }
        $this->assertNotEmpty($files, 'Expected migration file to be created');
        $migPath = $files[0];
        $className = 'CreateItemsTable' . substr(bin2hex(random_bytes(2)), 0, 4);
        $migrationPhp = <<<PHP
        <?php
        declare(strict_types=1);
        
        use Ishmael\\Core\\Database\\Migrations\\BaseMigration;
        
        final class {$className} extends BaseMigration
        {
            public function up(): void
            {
                // SQLite table for test
                \Ishmael\\Core\\Database::adapter()->runSql('CREATE TABLE IF NOT EXISTS cli_items (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
            }
            public function down(): void
            {
                \Ishmael\\Core\\Database::adapter()->runSql('DROP TABLE IF EXISTS cli_items');
            }
        }
        PHP;
        file_put_contents($migPath, $migrationPhp);

        // 3) migrate for this module
        $r3 = $this->runPhpScript($bin, ['migrate', '--module=' . $this->moduleName, '--force'], $this->appRoot);
        $this->assertSame(0, $r3['exit'], 'migrate failed: ' . $r3['err'] . '\nOUT:' . $r3['out']);

        // Verify table exists in sqlite
        $dbPath = $this->sqliteDbPath();
        $this->assertNotNull($dbPath, 'Expected SQLite database path from config');
        $this->assertFileExists($dbPath, 'Expected SQLite DB file to exist after migrate');
        $pdo = new \PDO('sqlite:' . $dbPath);
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='cli_items'");
        $this->assertNotFalse($stmt, 'sqlite_master query failed');
        $this->assertNotFalse($stmt->fetch(), 'cli_items table not found');

        // 4) make:seeder and implement it to insert a row
        $r4 = $this->runPhpScript($bin, ['make:seeder', $this->moduleName, 'ItemsSeeder'], $this->appRoot);
        $this->assertSame(0, $r4['exit'], 'make:seeder failed: ' . $r4['err']);
        $seederPath = $this->moduleDir . DIRECTORY_SEPARATOR . 'Database' . DIRECTORY_SEPARATOR . 'Seeders' . DIRECTORY_SEPARATOR . 'ItemsSeeder.php';
        $seederPhp = <<<PHP
        <?php
        declare(strict_types=1);
        namespace Modules\\{$this->moduleName}\\Database\\Seeders;
        
        final class ItemsSeeder
        {
            public function run(): void
            {
                \Ishmael\\Core\\Database::adapter()->execute("INSERT INTO cli_items (name) VALUES ('alpha'), ('beta')");
            }
        }
        PHP;
        file_put_contents($seederPath, $seederPhp);

        // 5) seed with FQCN
        $fqcn = 'Modules\\' . $this->moduleName . '\\Database\\Seeders\\ItemsSeeder';
        $r5 = $this->runPhpScript($bin, ['seed', '--class=' . $fqcn, '--force', '--env=ci'], $this->appRoot);
        $this->assertSame(0, $r5['exit'], 'seed failed: ' . $r5['err'] . '\nOUT:' . $r5['out']);

        // Assert rows
        $count = (int)$pdo->query('SELECT COUNT(*) FROM cli_items')->fetchColumn();
        $this->assertGreaterThanOrEqual(2, $count, 'Expected at least 2 rows seeded');
    }

    private function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) return;
        $items = scandir($dir) ?: [];
        foreach ($items as $it) {
            if ($it === '.' || $it === '..') continue;
            $p = $dir . DIRECTORY_SEPARATOR . $it;
            if (is_dir($p)) $this->rrmdir($p); else @unlink($p);
        }
        @rmdir($dir);
    }
}
