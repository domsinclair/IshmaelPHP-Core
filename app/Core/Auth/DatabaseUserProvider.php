<?php
declare(strict_types=1);

namespace Ishmael\Core\Auth;

use Ishmael\Core\Database;

/**
 * DatabaseUserProvider fetches users from a configured database table and
 * validates credentials using the configured Hasher.
 */
final class DatabaseUserProvider implements UserProviderInterface
{
    /** @var array<string,string> */
    private array $config;
    private HasherInterface $hasher;

    /**
     * @param array<string,mixed>|null $providerConfig Optional provider config (uses config('auth.providers.users') by default)
     */
    public function __construct(?array $providerConfig = null, ?HasherInterface $hasher = null)
    {
        /** @var array<string,mixed> $cfg */
        $cfg = (array) ($providerConfig ?? (\config('auth.providers.users') ?? []));
        $this->config = [
            'table' => (string)($cfg['table'] ?? 'users'),
            'id' => (string)($cfg['id_column'] ?? 'id'),
            'username' => (string)($cfg['username_column'] ?? 'email'),
            'password' => (string)($cfg['password_column'] ?? 'password'),
        ];
        $this->hasher = $hasher ?? (\app('hasher') instanceof HasherInterface ? \app('hasher') : new PhpPasswordHasher());
    }

    public function retrieveById(mixed $id): ?array
    {
        $table = $this->config['table'];
        $idCol = $this->config['id'];
        $sql = "SELECT * FROM {$table} WHERE {$idCol} = :id LIMIT 1";
        $res = Database::adapter()->query($sql, [':id' => $id]);
        $row = $res->fetchAssoc();
        return $row ?: null;
    }

    public function retrieveByCredentials(array $credentials): ?array
    {
        $table = $this->config['table'];
        $userCol = $this->config['username'];
        $value = $credentials[$userCol] ?? ($credentials['username'] ?? ($credentials['email'] ?? null));
        if ($value === null) {
            return null;
        }
        $sql = "SELECT * FROM {$table} WHERE {$userCol} = :u LIMIT 1";
        $res = Database::adapter()->query($sql, [':u' => $value]);
        $row = $res->fetchAssoc();
        return $row ?: null;
    }

    public function validateCredentials(array $user, array $credentials): bool
    {
        $passwordCol = $this->config['password'];
        $plain = (string) ($credentials['password'] ?? '');
        $hash = (string) ($user[$passwordCol] ?? '');
        if ($plain === '' || $hash === '') {
            return false;
        }
        return $this->hasher->verify($plain, $hash);
    }
}
