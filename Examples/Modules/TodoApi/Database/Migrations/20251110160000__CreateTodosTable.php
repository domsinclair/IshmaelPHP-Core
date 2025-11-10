<?php
declare(strict_types=1);

use Ishmael\Core\Database\Migrations\BaseMigration;
use Ishmael\Core\Database\Schema\TableDefinition;
use Ishmael\Core\Database\Schema\ColumnDefinition;
use Ishmael\Core\Database\Schema\IndexDefinition;

final class CreateTodosTable extends BaseMigration
{
    public function up(): void
    {
        $def = new TableDefinition('todos');
        $def->addColumn(new ColumnDefinition('id', 'integer', nullable: false, autoIncrement: true));
        $def->addColumn(new ColumnDefinition('title', 'string', length: 200));
        $def->addColumn(new ColumnDefinition('completed', 'boolean', default: 0));
        $def->addColumn(new ColumnDefinition('created_at', 'datetime'));
        $def->addColumn(new ColumnDefinition('updated_at', 'datetime'));
        $def->addIndex(new IndexDefinition('todos_completed_idx', ['completed'], 'index'));
        $this->adapter()->createTable($def);
    }

    public function down(): void
    {
        $this->adapter()->dropTable('todos');
    }
}
