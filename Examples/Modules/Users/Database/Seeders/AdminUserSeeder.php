<?php
declare(strict_types=1);

namespace Modules\Users\Database\Seeders;

use Ishmael\Core\DatabaseAdapters\DatabaseAdapterInterface;
use Ishmael\Core\Database\Seeders\BaseSeeder;
use Psr\Log\LoggerInterface;

final class AdminUserSeeder extends BaseSeeder
{
    public function run(DatabaseAdapterInterface $adapter, LoggerInterface $logger): void
    {
        $now = date('Y-m-d H:i:s');
        $name = 'Admin User';
        $email = getenv('ISH_ADMIN_EMAIL') ?: 'admin@example.com';
        $password = getenv('ISH_ADMIN_PASSWORD') ?: 'secret123';
        $hash = password_hash((string)$password, PASSWORD_DEFAULT);

        // Ensure user exists or create it
        $user = $adapter->query('SELECT id FROM users WHERE email = ? LIMIT 1', [$email])->first();
        if (!$user) {
            $adapter->execute('INSERT INTO users (name, email, password_hash, created_at, updated_at) VALUES (?,?,?,?,?)', [
                $name, $email, $hash, $now, $now
            ]);
            $userId = (int)$adapter->lastInsertId();
            $logger->info('Seeded admin user', ['email' => $email]);
        } else {
            $userId = (int)($user['id'] ?? 0);
            $logger->info('Admin user already exists', ['email' => $email]);
        }

        // Ensure admin role exists
        $role = $adapter->query('SELECT id FROM roles WHERE slug = ? LIMIT 1', ['admin'])->first();
        if (!$role) {
            $adapter->execute('INSERT INTO roles (name, slug, created_at, updated_at) VALUES (?,?,?,?)', ['Admin', 'admin', $now, $now]);
            $roleId = (int)$adapter->lastInsertId();
        } else {
            $roleId = (int)($role['id'] ?? 0);
        }

        // Attach pivot if missing
        if ($userId > 0 && $roleId > 0) {
            $exists = $adapter->query('SELECT 1 FROM user_roles WHERE user_id=? AND role_id=? LIMIT 1', [$userId, $roleId])->first();
            if (!$exists) {
                $adapter->execute('INSERT INTO user_roles (user_id, role_id) VALUES (?,?)', [$userId, $roleId]);
                $logger->info('Attached admin role to admin user');
            }
        }
    }
}
