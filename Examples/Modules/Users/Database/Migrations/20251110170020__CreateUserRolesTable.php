<?php
declare(strict_types=1);

use Ishmael\Core\Database\Migrations\BaseMigration;
use Ishmael\Core\Database\Schema\TableDefinition;
use Ishmael\Core\Database\Schema\ColumnDefinition;
use Ishmael\Core\Database\Schema\IndexDefinition;

final class CreateUserRolesTable extends BaseMigration
{
    public function up(): void
    {
        $def = new TableDefinition('user_roles');
        $def->addColumn(new ColumnDefinition('user_id', 'integer'));
        $def->addColumn(new ColumnDefinition('role_id', 'integer'));
        $def->addIndex(new IndexDefinition('user_roles_unique', ['user_id', 'role_id'], 'unique'));
        $def->addIndex(new IndexDefinition('idx_user_roles_user_id', ['user_id'], 'index'));
        $def->addIndex(new IndexDefinition('idx_user_roles_role_id', ['role_id'], 'index'));
        $this->adapter()->createTable($def);
    }

    public function down(): void
    {
        $this->adapter()->dropTable('user_roles');
    }
}
