<?php
declare(strict_types=1);

use Ishmael\Core\DatabaseAdapters\DatabaseAdapterFactory;
use Ishmael\Core\DatabaseAdapters\DatabaseAdapterInterface;
use Ishmael\Core\DatabaseAdapters\MySQLAdapter;
use Ishmael\Core\DatabaseAdapters\SQLiteAdapter;
use Ishmael\Core\DatabaseAdapters\PostgresAdapter;
use Ishmael\Core\Database\Result;
use Ishmael\Core\Database\Schema\{TableDefinition, ColumnDefinition, IndexDefinition};
use PHPUnit\Framework\TestCase;

// Helper adapter kept in global namespace (no namespace) to align with other tests
class DummyAdapter implements DatabaseAdapterInterface
{
    private ?PDO $pdo = null;
    public function connect(array $config): PDO { $this->pdo = new PDO('sqlite::memory:'); $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); return $this->pdo; }
    public function disconnect(): void { $this->pdo = null; }
    public function isConnected(): bool { return $this->pdo instanceof PDO; }
    public function query(string $sql, array $params = []): Result { $s = $this->pdo->prepare($sql); $s->execute($params); return new Result($s); }
    public function execute(string $sql, array $params = []): int { $s = $this->pdo->prepare($sql); $s->execute($params); return $s->rowCount(); }
    public function lastInsertId(?string $sequence = null): string { return $this->pdo->lastInsertId($sequence ?? ''); }
    public function beginTransaction(): void { $this->pdo->beginTransaction(); }
    public function commit(): void { $this->pdo->commit(); }
    public function rollBack(): void { $this->pdo->rollBack(); }
    public function inTransaction(): bool { return $this->pdo->inTransaction(); }
    public function supportsTransactionalDdl(): bool { return true; }
    public function createTable(TableDefinition $def): void { $cols = array_map(fn($c) => $c->name . ' ' . strtoupper($c->type), $def->columns); $this->runSql('CREATE TABLE ' . $def->name . ' (' . implode(', ', $cols) . ')'); }
    public function dropTable(string $table): void { $this->runSql('DROP TABLE IF EXISTS ' . $table); }
    public function addColumn(string $table, ColumnDefinition $def): void { $this->runSql('ALTER TABLE ' . $table . ' ADD COLUMN ' . $def->name . ' ' . strtoupper($def->type)); }
    public function alterColumn(string $table, ColumnDefinition $def): void { throw new \LogicException('not implemented'); }
    public function dropColumn(string $table, string $column): void { throw new \LogicException('not implemented'); }
    public function addIndex(string $table, IndexDefinition $def): void { /* no-op */ }
    public function dropIndex(string $table, string $name): void { /* no-op */ }
    public function tableExists(string $table): bool { $s = $this->pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name = :n"); $s->execute([':n' => $table]); return (bool)$s->fetch(); }
    public function columnExists(string $table, string $column): bool { $s = $this->pdo->query('PRAGMA table_info(' . $table . ')'); foreach ($s->fetchAll() as $r) { if ($r['name'] === $column) return true; } return false; }
    public function getTableDefinition(string $table): TableDefinition { return new TableDefinition($table); }
    public function runSql(string $sql): void { $this->pdo->exec($sql); }
    public function getCapabilities(): array { return [self::CAP_TRANSACTIONAL_DDL]; }
}

final class DatabaseAdapterFactoryTest extends TestCase
{
    protected function setUp(): void
    {
        $this->resetRegistry();
    }

    protected function tearDown(): void
    {
        $this->resetRegistry();
    }

    private function resetRegistry(): void
    {
        $ref = new ReflectionClass(DatabaseAdapterFactory::class);
        $prop = $ref->getProperty('registry');
        $prop->setAccessible(true);
        $prop->setValue(null, []);
    }

    public function testRegisterDefaultsAndCreateAdapters(): void
    {
        DatabaseAdapterFactory::registerDefaults();

        $this->assertInstanceOf(MySQLAdapter::class, DatabaseAdapterFactory::create('mysql'));
        $this->assertInstanceOf(SQLiteAdapter::class, DatabaseAdapterFactory::create('sqlite'));
        $this->assertInstanceOf(PostgresAdapter::class, DatabaseAdapterFactory::create('pgsql'));
        $this->assertInstanceOf(PostgresAdapter::class, DatabaseAdapterFactory::create('postgres'));
    }

    public function testCreateUnknownDriverThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No database adapter registered for driver [unknown]');
        DatabaseAdapterFactory::create('unknown');
    }

    public function testRegisterCustomAdapterAndCreate(): void
    {
        // Register a custom key mapped to an existing adapter class to simulate extensibility
        DatabaseAdapterFactory::register('dummy', SQLiteAdapter::class);
        $adapter = DatabaseAdapterFactory::create('dummy');

        $this->assertInstanceOf(DatabaseAdapterInterface::class, $adapter);
        $pdo = $adapter->connect([]);
        $this->assertInstanceOf(PDO::class, $pdo);
        $this->assertSame('sqlite', $pdo->getAttribute(PDO::ATTR_DRIVER_NAME));
    }
}
