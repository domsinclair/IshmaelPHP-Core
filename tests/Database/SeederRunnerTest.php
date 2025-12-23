<?php

declare(strict_types=1);

namespace Tests\Database;

use Ishmael\Core\DatabaseAdapters\DatabaseAdapterInterface;
use Ishmael\Core\Database\Seeding\SeedManager;
use PHPUnit\Framework\TestCase;

final class SeederRunnerTest extends TestCase
{
    private DatabaseAdapterInterface $adapter;
    private string $moduleDir;
    protected function setUp(): void
    {
        $this->adapter = AdapterTestUtil::sqliteAdapter();
        $this->moduleDir = \base_path('Modules' . DIRECTORY_SEPARATOR . 'SeedMod');
        @mkdir($this->moduleDir . DIRECTORY_SEPARATOR . 'Database' . DIRECTORY_SEPARATOR . 'Seeders', 0777, true);
        $seedDir = $this->moduleDir . DIRECTORY_SEPARATOR . 'Database' . DIRECTORY_SEPARATOR . 'Seeders' . DIRECTORY_SEPARATOR;
    // Two seeders with dependency ordering: B depends on A
        file_put_contents($seedDir . 'ASeeder.php', <<<'PHP'
<?php
use Ishmael\Core\Database\Seeders\BaseSeeder;
use Ishmael\Core\DatabaseAdapters\DatabaseAdapterInterface;
use Psr\Log\LoggerInterface;

class ASeeder extends BaseSeeder {
    public function run(DatabaseAdapterInterface $adapter, LoggerInterface $logger): void {
        $adapter->runSql('CREATE TABLE IF NOT EXISTS SeedOrder (id INTEGER PRIMARY KEY, name TEXT)');
        $adapter->execute('INSERT INTO SeedOrder (name) VALUES ("A")');
    }
}
PHP);
        file_put_contents($seedDir . 'BSeeder.php', <<<'PHP'
<?php
use Ishmael\Core\Database\Seeders\BaseSeeder;
use Ishmael\Core\DatabaseAdapters\DatabaseAdapterInterface;
use Psr\Log\LoggerInterface;

class BSeeder extends BaseSeeder {
    public function dependsOn(): array { return ['ASeeder']; }
    public function run(DatabaseAdapterInterface $adapter, LoggerInterface $logger): void {
        $adapter->execute('INSERT INTO SeedOrder (name) VALUES ("B")');
    }
}
PHP);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->moduleDir)) {
            $this->rrmdir($this->moduleDir);
        }
        try {
            $this->adapter->runSql('DROP TABLE IF EXISTS SeedOrder');
        } catch (\Throwable $_) {
        }
    }

    private function rrmdir(string $dir): void
    {
        $items = @scandir($dir) ?: [];
        foreach ($items as $i) {
            if ($i === '.' || $i === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $i;
            if (is_dir($path)) {
                $this->rrmdir($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }

    public function testOrderingAndDeterminism(): void
    {
        $seeder = new SeedManager($this->adapter, null);
// Force allowed environment
        $seeder->seed('SeedMod', null, false, 'test', false);
        $rows = $this->adapter->query('SELECT name FROM SeedOrder ORDER BY id')->all();
        $this->assertSame(['A', 'B'], array_map(static fn($r) => $r['name'], $rows));
// Second run should append nothing unexpected (our seeders are deterministic but not idempotent by themselves)
        // For test purposes, we treat refresh=false; we expect two more rows, maintaining order A then B
        $seeder->seed('SeedMod', null, false, 'test', false);
        $rows = $this->adapter->query('SELECT name FROM SeedOrder ORDER BY id')->all();
        $this->assertSame(['A','B','A','B'], array_map(static fn($r) => $r['name'], $rows));
    }

    public function testEnvironmentGuardBlocksProduction(): void
    {
        $seeder = new SeedManager($this->adapter, null);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Seeding is disabled in');
        $seeder->seed('SeedMod', null, false, 'production', false);
    }
}
