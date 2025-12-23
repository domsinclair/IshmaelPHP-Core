<?php

declare(strict_types=1);

namespace Ishmael\Core\Session;

use Ishmael\Core\Database;

/**
 * DatabaseSessionStore persists session payloads in a database table.
 * Table schema (default): sessions(id varchar(128) primary key, payload json/text, expires_at int)
 */
final class DatabaseSessionStore implements SessionStore
{
    private string $table;
/**
     * @param string $table Table name to store sessions in (default: sessions)
     */
    public function __construct(string $table = 'sessions')
    {
        $this->table = $table;
        $this->ensureTable();
    }

    public function load(string $id): array
    {
        $sql = "SELECT payload, expires_at FROM {$this->table} WHERE id = :id";
        $res = Database::adapter()->query($sql, [':id' => $id]);
        $row = $res->fetchAssoc();
        if (!$row) {
            return [];
        }
        $exp = (int)($row['expires_at'] ?? 0);
        if ($exp > 0 && time() > $exp) {
            $this->destroy($id);
            return [];
        }
        $json = (string)($row['payload'] ?? '{}');
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function persist(string $id, array $data, int $ttlSeconds): void
    {
        $expires = $ttlSeconds > 0 ? time() + $ttlSeconds : 0;
        $json = json_encode($data, JSON_UNESCAPED_SLASHES);
        $sql = "INSERT INTO {$this->table} (id, payload, expires_at) VALUES (:id, :payload, :exp)
                ON CONFLICT(id) DO UPDATE SET payload = excluded.payload, expires_at = excluded.expires_at";
// For MySQL, use REPLACE INTO or ON DUPLICATE KEY UPDATE; but many adapters exist.
        try {
            Database::adapter()->execute($sql, [':id' => $id, ':payload' => $json, ':exp' => $expires]);
        } catch (\Throwable $e) {
        // Fallback generic upsert strategy
            $exists = Database::adapter()->query("SELECT 1 FROM {$this->table} WHERE id = :id", [':id' => $id])->fetchColumn();
            if ($exists) {
                Database::adapter()->execute("UPDATE {$this->table} SET payload=:payload, expires_at=:exp WHERE id=:id", [':id' => $id, ':payload' => $json, ':exp' => $expires]);
            } else {
                Database::adapter()->execute("INSERT INTO {$this->table} (id, payload, expires_at) VALUES (:id, :payload, :exp)", [':id' => $id, ':payload' => $json, ':exp' => $expires]);
            }
        }
    }

    public function destroy(string $id): void
    {
        Database::adapter()->execute("DELETE FROM {$this->table} WHERE id=:id", [':id' => $id]);
    }

    public function generateId(): string
    {
        return bin2hex(random_bytes(32));
    }

    private function ensureTable(): void
    {
        try {
            if (!Database::adapter()->tableExists($this->table)) {
            // Minimal portable table creation
                // Use TEXT for payload to be portable; JSON if supported is acceptable by adapters.
                $ddl = sprintf('CREATE TABLE %s (id VARCHAR(128) PRIMARY KEY, payload TEXT NOT NULL, expires_at INTEGER NOT NULL)', $this->table);
                Database::adapter()->runSql($ddl);
            }
        } catch (\Throwable $e) {
        // Silent failure if adapter not initialized yet; table will be created on first use when possible.
        }
    }
}
