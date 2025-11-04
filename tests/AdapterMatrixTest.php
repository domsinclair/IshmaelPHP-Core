<?php
declare(strict_types=1);

use Ishmael\Core\DatabaseAdapters\DatabaseAdapterFactory;
use Ishmael\Core\DatabaseAdapters\SQLiteAdapter;
use Ishmael\Core\DatabaseAdapters\MySQLAdapter;
use Ishmael\Core\DatabaseAdapters\PostgresAdapter;
use Ishmael\Core\Database\Schema\{TableDefinition, ColumnDefinition, IndexDefinition};
use PHPUnit\Framework\TestCase;

final class AdapterMatrixTest extends TestCase
{
    private function tmpTableName(): string
    {
        return 't_' . substr(str_replace('-', '', (string)uniqid('', true)), 0, 10);
    }

    public function testSQLiteMatrix(): void
    {
        if (!class_exists(SQLiteAdapter::class)) {
            $this->markTestSkipped('SQLite adapter not available');
        }
        $adapter = new SQLiteAdapter();
        $adapter->connect(['database' => ':memory:']);

        $table = $this->tmpTableName();
        $def = new TableDefinition($table, [
            new ColumnDefinition('id', 'INTEGER', nullable: false, autoIncrement: true),
            new ColumnDefinition('name', 'TEXT', nullable: false, default: 'anon'),
        ], [
            new IndexDefinition($table . '_name_idx', ['name'], 'index'),
        ]);
        $adapter->createTable($def);
        $this->assertTrue($adapter->tableExists($table));
        $this->assertTrue($adapter->columnExists($table, 'name'));

        // Insert and lastInsertId
        $adapter->execute("INSERT INTO {$table} (name) VALUES (?)", ['a']);
        $id1 = $adapter->lastInsertId();
        $this->assertNotSame('', $id1);

        // Add column
        $adapter->addColumn($table, new ColumnDefinition('age', 'INT', nullable: true));
        $this->assertTrue($adapter->columnExists($table, 'age'));
    }

    public function testMySQLMatrix(): void
    {
        $dsn = $_SERVER['ISH_TEST_MYSQL_DSN'] ?? null;
        if (!$dsn) {
            $host = $_SERVER['ISH_TEST_MYSQL_HOST'] ?? null;
            $db   = $_SERVER['ISH_TEST_MYSQL_DB'] ?? null;
            $user = $_SERVER['ISH_TEST_MYSQL_USER'] ?? null;
            $pass = $_SERVER['ISH_TEST_MYSQL_PASS'] ?? '';
            if (!$host || !$db || !$user) {
                $this->markTestSkipped('MySQL env not provided');
            }
            $config = ['host' => $host, 'database' => $db, 'username' => $user, 'password' => $pass];
        } else {
            // DSN not supported by adapter directly; parse minimally mysql:host=...;dbname=...
            $parts = [];
            foreach (explode(';', str_replace('mysql:', '', $dsn)) as $kv) {
                if (!$kv) continue; $a = explode('=', $kv, 2); if (count($a) === 2) { $parts[$a[0]] = $a[1]; }
            }
            $config = ['host' => $parts['host'] ?? '127.0.0.1', 'database' => $parts['dbname'] ?? '', 'username' => $_SERVER['ISH_TEST_MYSQL_USER'] ?? 'root', 'password' => $_SERVER['ISH_TEST_MYSQL_PASS'] ?? ''];
        }

        $adapter = new MySQLAdapter();
        $adapter->connect($config);

        $table = $this->tmpTableName();
        $def = new TableDefinition($table, [
            new ColumnDefinition('id', 'INT', nullable: false, autoIncrement: true),
            new ColumnDefinition('name', 'VARCHAR', nullable: false, length: 100, default: 'anon'),
        ]);
        $adapter->createTable($def);
        $this->assertTrue($adapter->tableExists($table));

        $adapter->execute("INSERT INTO `{$table}` (`name`) VALUES (?)", ['a']);
        $id1 = $adapter->lastInsertId();
        $this->assertNotSame('', $id1);

        $adapter->addColumn($table, new ColumnDefinition('age', 'INT', nullable: true));
        $this->assertTrue($adapter->columnExists($table, 'age'));

        $adapter->dropTable($table);
    }

    public function testPostgresMatrix(): void
    {
        $dsn = $_SERVER['ISH_TEST_PG_DSN'] ?? null;
        if (!$dsn) {
            $host = $_SERVER['ISH_TEST_PG_HOST'] ?? null;
            $db   = $_SERVER['ISH_TEST_PG_DB'] ?? null;
            $user = $_SERVER['ISH_TEST_PG_USER'] ?? null;
            $pass = $_SERVER['ISH_TEST_PG_PASS'] ?? '';
            $port = (int)($_SERVER['ISH_TEST_PG_PORT'] ?? 5432);
            if (!$host || !$db || !$user) {
                $this->markTestSkipped('Postgres env not provided');
            }
            $config = ['host' => $host, 'port' => $port, 'database' => $db, 'username' => $user, 'password' => $pass];
        } else {
            // pgsql:host=...;port=...;dbname=...
            $parts = [];
            foreach (explode(';', str_replace('pgsql:', '', $dsn)) as $kv) {
                if (!$kv) continue; $a = explode('=', $kv, 2); if (count($a) === 2) { $parts[$a[0]] = $a[1]; }
            }
            $config = [
                'host' => $parts['host'] ?? '127.0.0.1',
                'port' => isset($parts['port']) ? (int)$parts['port'] : 5432,
                'database' => $parts['dbname'] ?? '',
                'username' => $_SERVER['ISH_TEST_PG_USER'] ?? 'postgres',
                'password' => $_SERVER['ISH_TEST_PG_PASS'] ?? '',
            ];
        }

        $adapter = new PostgresAdapter();
        $adapter->connect($config);

        $table = $this->tmpTableName();
        $def = new TableDefinition($table, [
            new ColumnDefinition('id', 'INT', nullable: false, autoIncrement: true),
            new ColumnDefinition('name', 'VARCHAR', nullable: false, length: 120, default: 'anon'),
        ], [
            new IndexDefinition($table . '_name_idx', ['name'], 'index', where: null),
        ]);
        $adapter->createTable($def);
        $this->assertTrue($adapter->tableExists($table));

        // Insert returning id
        $adapter->execute('INSERT INTO ' . '"' . $table . '"' . ' ("name") VALUES ($1)', ['a']);
        // Try both with and without sequence
        $id = $adapter->lastInsertId();
        if ($id === '') {
            $seq = $table . '_id_seq';
            $id = $adapter->lastInsertId($seq);
        }
        $this->assertNotSame('', $id);

        $adapter->addColumn($table, new ColumnDefinition('age', 'INT', nullable: true));
        $this->assertTrue($adapter->columnExists($table, 'age'));

        $adapter->dropTable($table);
    }
}
