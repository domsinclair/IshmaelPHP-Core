<?php
declare(strict_types=1);

namespace Modules\Users\Services;

use Ishmael\Core\Database;

final class UserService
{
    public function __construct(private PasswordHasher $hasher = new PasswordHasher()) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function list(string $q = ''): array
    {
        $adapter = Database::adapter();
        $params = [];
        $where = '';
        if ($q !== '') {
            $where = 'WHERE name LIKE ? OR email LIKE ?';
            $like = '%' . $q . '%';
            $params = [$like, $like];
        }
        return $adapter->query("SELECT id, name, email, created_at FROM users {$where} ORDER BY id DESC LIMIT 50", $params)->all();
    }

    public function find(int $id): ?array
    {
        $adapter = Database::adapter();
        $u = $adapter->query('SELECT id, name, email, created_at, updated_at FROM users WHERE id = ?', [$id])->first();
        return $u ?: null;
    }

    public function create(string $name, string $email, string $password): int
    {
        $adapter = Database::adapter();
        $now = date('Y-m-d H:i:s');
        $hash = $this->hasher->hash($password);
        $adapter->execute('INSERT INTO users (name, email, password_hash, created_at, updated_at) VALUES (?,?,?,?,?)', [$name, $email, $hash, $now, $now]);
        return (int)$adapter->lastInsertId();
    }

    public function update(int $id, string $name, string $email, ?string $password = null): void
    {
        $adapter = Database::adapter();
        $now = date('Y-m-d H:i:s');
        if ($password !== null && $password !== '') {
            $hash = $this->hasher->hash($password);
            $adapter->execute('UPDATE users SET name=?, email=?, password_hash=?, updated_at=? WHERE id=?', [$name, $email, $hash, $now, $id]);
        } else {
            $adapter->execute('UPDATE users SET name=?, email=?, updated_at=? WHERE id=?', [$name, $email, $now, $id]);
        }
    }

    public function delete(int $id): void
    {
        $adapter = Database::adapter();
        $adapter->execute('DELETE FROM user_roles WHERE user_id=?', [$id]);
        $adapter->execute('DELETE FROM users WHERE id=?', [$id]);
    }

    public function authenticate(string $email, string $password): ?array
    {
        $adapter = Database::adapter();
        $u = $adapter->query('SELECT * FROM users WHERE email = ? LIMIT 1', [$email])->first();
        if ($u && isset($u['password_hash']) && is_string($u['password_hash']) && $this->hasher->verify($password, (string)$u['password_hash'])) {
            return $u;
        }
        return null;
    }
}
