<?php
declare(strict_types=1);

namespace Modules\Users\Services;

use Ishmael\Core\Database;

final class RoleService
{
    /**
     * @return array<int, array<string,mixed>>
     */
    public function all(): array
    {
        return Database::adapter()->query('SELECT * FROM roles ORDER BY id')->all();
    }

    public function assign(int $userId, int $roleId): void
    {
        $adapter = Database::adapter();
        $exists = $adapter->query('SELECT 1 FROM user_roles WHERE user_id=? AND role_id=? LIMIT 1', [$userId, $roleId])->first();
        if (!$exists) {
            $adapter->execute('INSERT INTO user_roles (user_id, role_id) VALUES (?,?)', [$userId, $roleId]);
        }
    }

    public function remove(int $userId, int $roleId): void
    {
        $adapter = Database::adapter();
        $adapter->execute('DELETE FROM user_roles WHERE user_id=? AND role_id=?', [$userId, $roleId]);
    }
}
