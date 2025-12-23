<?php

declare(strict_types=1);

namespace Ishmael\Tests;

use Ishmael\Core\DatabaseAdapters\DatabaseAdapterFactory;
use Ishmael\Core\DatabaseAdapters\DatabaseAdapterInterface;
use Ishmael\Core\DatabaseAdapters\MySQLAdapter;
use Ishmael\Core\DatabaseAdapters\SQLiteAdapter;
use Ishmael\Core\DatabaseAdapters\PostgresAdapter;
use Ishmael\Tests\Fixtures\DummyAdapter;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use InvalidArgumentException;
use PDO;

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
