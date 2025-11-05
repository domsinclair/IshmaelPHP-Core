<?php
declare(strict_types=1);

namespace Tests\Database;

use Ishmael\Core\DatabaseAdapters\DatabaseAdapterInterface;
use Ishmael\Core\Database\Migrations\MigrationRunner;
use PHPUnit\Framework\TestCase;

final class MigrationRunnerTest extends TestCase
{
    private DatabaseAdapterInterface $adapter;
    private string $moduleDir;

    protected function setUp(): void
    {
        $this->adapter = AdapterTestUtil::sqliteAdapter();
        // Create a temporary module under base_path('Modules/TestMod')
        $this->moduleDir = \base_path('Modules' . DIRECTORY_SEPARATOR . 'TestMod');
        @mkdir($this->moduleDir . DIRECTORY_SEPARATOR . 'Database' . DIRECTORY_SEPARATOR . 'Migrations', 0777, true);

        // Write two simple migrations that create two tables, with proper timestamps
        $migs = $this->moduleDir . DIRECTORY_SEPARATOR . 'Database' . DIRECTORY_SEPARATOR . 'Migrations' . DIRECTORY_SEPARATOR;
        file_put_contents($migs . '20000101000000_CreateAlpha.php', $this->migrationCreateTable('Alpha'));
        file_put_contents($migs . '20000101000100_CreateBeta.php', $this->migrationCreateTable('Beta'));
    }

    protected function tearDown(): void
    {
        // Clean up created files and tables
        if (is_dir($this->moduleDir)) {
            $this->rrmdir($this->moduleDir);
        }
        try { $this->adapter->runSql('DROP TABLE IF EXISTS Alpha'); } catch (\Throwable $_) {}
        try { $this->adapter->runSql('DROP TABLE IF EXISTS Beta'); } catch (\Throwable $_) {}
        try { $this->adapter->runSql('DROP TABLE IF EXISTS ishmael_migrations'); } catch (\Throwable $_) {}
    }

    private function rrmdir(string $dir): void
    {
        $items = @scandir($dir) ?: [];
        foreach ($items as $i) {
            if ($i === '.' || $i === '..') continue;
            $path = $dir . DIRECTORY_SEPARATOR . $i;
            if (is_dir($path)) { $this->rrmdir($path); } else { @unlink($path); }
        }
        @rmdir($dir);
    }

    private function migrationCreateTable(string $name): string
    {
        return <<<'PHP'
<?php
use Ishmael\Core\Database\Migrations\BaseMigration;

return new class extends BaseMigration {
    public function up(): void { $this->sql("CREATE TABLE IF NOT EXISTS %s (id INTEGER PRIMARY KEY, name TEXT)"); }
    public function down(): void { $this->sql("DROP TABLE IF EXISTS %s"); }
};
PHP;
    }

    public function testApplyMigrationsInOrderAndIdempotent(): void
    {
        // Fill in table names inside the anonymous classes
        $this->patchMigration('Alpha');
        $this->patchMigration('Beta');

        $runner = new MigrationRunner($this->adapter, null);
        // First run applies both
        $runner->migrate(null, 0, false);
        $this->assertTrue($this->tableExists('Alpha'));
        $this->assertTrue($this->tableExists('Beta'));

        // Second run should be idempotent (no errors, no duplicates)
        $runner->migrate(null, 0, false);
        $this->assertTrue($this->tableExists('Alpha'));
        $this->assertTrue($this->tableExists('Beta'));

        // Roll back the last batch across all modules (both tables) by two steps
        $runner->rollback('TestMod', 1);
        $this->assertTrue($this->tableExists('Alpha'));
        $this->assertFalse($this->tableExists('Beta'));

        $runner->rollback('TestMod', 1);
        $this->assertFalse($this->tableExists('Alpha'));
    }

    public function testTransactionalBehaviorRollsBackOnFailure(): void
    {
        // Create a new migration that fails after creating a table; DDL should be rolled back in SQLite
        $migs = $this->moduleDir . DIRECTORY_SEPARATOR . 'Database' . DIRECTORY_SEPARATOR . 'Migrations' . DIRECTORY_SEPARATOR;
        file_put_contents($migs . '20000101000200_Failing.php', <<<'PHP'
<?php
use Ishmael\Core\Database\Migrations\BaseMigration;

return new class extends BaseMigration {
    public function up(): void {
        $this->sql('CREATE TABLE IF NOT EXISTS WillRollback (id INTEGER PRIMARY KEY)');
        throw new \RuntimeException('boom');
    }
    public function down(): void { $this->sql('DROP TABLE IF EXISTS WillRollback'); }
};
PHP);
        $runner = new MigrationRunner($this->adapter, null);
        try {
            $runner->migrate('TestMod', 0, false);
            $this->fail('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('boom', $e->getMessage());
        }
        // Table should not exist due to transactional DDL
        $this->assertFalse($this->tableExists('WillRollback'));
    }

    private function tableExists(string $table): bool
    {
        try {
            $row = $this->adapter->query('SELECT name FROM sqlite_master WHERE type="table" AND name = :t', [':t' => $table])->first();
            return $row !== null;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function patchMigration(string $table): void
    {
        $migs = $this->moduleDir . DIRECTORY_SEPARATOR . 'Database' . DIRECTORY_SEPARATOR . 'Migrations' . DIRECTORY_SEPARATOR;
        $file = $migs . ($table === 'Alpha' ? '20000101000000_CreateAlpha.php' : '20000101000100_CreateBeta.php');
        $code = file_get_contents($file);
        $code = sprintf($code, $table, $table);
        file_put_contents($file, $code);
    }
}
