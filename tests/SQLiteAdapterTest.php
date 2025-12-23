<?php

declare(strict_types=1);

namespace Ishmael\Tests;

use Ishmael\Core\DatabaseAdapters\SQLiteAdapter;
use PHPUnit\Framework\TestCase;

final class SQLiteAdapterTest extends TestCase
{
    public function testConnectInMemorySetsAttributesAndReturnsPDO(): void
    {
        $adapter = new SQLiteAdapter();
        $pdo = $adapter->connect(['database' => ':memory:']);
        $this->assertInstanceOf(PDO::class, $pdo);
        $this->assertSame('sqlite', $pdo->getAttribute(PDO::ATTR_DRIVER_NAME));
        $this->assertSame(PDO::ERRMODE_EXCEPTION, $pdo->getAttribute(PDO::ATTR_ERRMODE));
        $this->assertSame(PDO::FETCH_ASSOC, $pdo->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE));
        $pdo->exec('CREATE TABLE t (id INTEGER PRIMARY KEY, name TEXT)');
        $pdo->exec("INSERT INTO t (name) VALUES ('a'), ('b')");
        $rows = $pdo->query('SELECT * FROM t ORDER BY id')->fetchAll();
        $this->assertCount(2, $rows);
        $this->assertSame(['id' => 1, 'name' => 'a'], $rows[0]);
    }

    public function testConnectCreatesDirectoryIfMissing(): void
    {
        $tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ish_sqlite_' . uniqid();
        $dbPath = $tmpDir . DIRECTORY_SEPARATOR . 'test.sqlite';
        $adapter = new SQLiteAdapter();
        $pdo = $adapter->connect(['database' => $dbPath]);
        $this->assertInstanceOf(PDO::class, $pdo);
        $this->assertFileExists($dbPath);
// cleanup
        $pdo = null;
// close connection on Windows to allow unlink
        @unlink($dbPath);
        @rmdir($tmpDir);
    }
}
