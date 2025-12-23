<?php

declare(strict_types=1);

namespace Tests\Database;

use Ishmael\Core\Database\SchemaManager;
use Ishmael\Core\Database\Schema\TableDefinition;
use Ishmael\Core\Database\Schema\ColumnDefinition;
use Ishmael\Core\Database\Schema\IndexDefinition;
use PHPUnit\Framework\TestCase;

final class SchemaManagerTest extends TestCase
{
    protected function setUp(): void
    {
        // Ensure a clean slate
        $adapter = AdapterTestUtil::sqliteAdapter();
        try {
            $adapter->runSql('DROP TABLE IF EXISTS sm_users');
        } catch (\Throwable $_) {
        }
    }

    public function testCreateAndAdditionsAreSafe(): void
    {
        $adapter = AdapterTestUtil::sqliteAdapter();
        $sm = new SchemaManager($adapter, null);
        $def = new TableDefinition('sm_users', [
            new ColumnDefinition(
                'id',
                'INTEGER',
                nullable: false,
                autoIncrement: true
            ),
            new ColumnDefinition('email', 'TEXT', false),
        ], [
            new IndexDefinition('idx_sm_users_email', ['email'], 'index'),
        ]);
// Create table
        $sm->synchronize([$def]);
        $this->assertTrue($adapter->tableExists('sm_users'));
        $this->assertTrue($adapter->columnExists('sm_users', 'email'));
// Add a new column should be safe
        $def2 = new TableDefinition('sm_users', [
            new ColumnDefinition('id', 'INTEGER', false, null, null, null, null, false, true),
            new ColumnDefinition('email', 'TEXT', false),
            new ColumnDefinition('name', 'TEXT', true),
        ], [
            new IndexDefinition('idx_sm_users_email', ['email'], 'index'),
        ]);
        $sm->synchronize([$def2]);
        $this->assertTrue($adapter->columnExists('sm_users', 'name'));
    }

    public function testUnsafeChangesAreRefused(): void
    {
        $adapter = AdapterTestUtil::sqliteAdapter();
        $sm = new SchemaManager($adapter, null);
// Prepare initial table
        $adapter->runSql('CREATE TABLE IF NOT EXISTS sm_users (id INTEGER PRIMARY KEY, email TEXT NOT NULL)');
// Change email type or nullability should be considered unsafe and throw
        $unsafe = new TableDefinition('sm_users', [
            new ColumnDefinition('id', 'INTEGER', false, null, null, null, null, false, true),
            new ColumnDefinition('email', 'INTEGER', true),
        ]);
        $this->expectException(\RuntimeException::class);
        $sm->synchronize([$unsafe]);
    }
}
