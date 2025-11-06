<?php
declare(strict_types=1);

namespace Ishmael\Core\Session;

/**
 * FileSessionStore persists session payloads as JSON files under storage/sessions.
 * It uses exclusive flock during writes to avoid corruption on concurrent requests.
 */
final class FileSessionStore implements SessionStore
{
    private string $directory;

    /**
     * @param string $directory Directory path where session files are stored.
     */
    public function __construct(string $directory)
    {
        $this->directory = rtrim($directory, "\\/");
        if (!is_dir($this->directory)) {
            @mkdir($this->directory, 0777, true);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function load(string $id): array
    {
        $path = $this->filePath($id);
        if (!is_file($path)) {
            return [];
        }
        $raw = @file_get_contents($path);
        if ($raw === false) {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }
        $expiresAt = isset($decoded['__meta']['exp']) ? (int)$decoded['__meta']['exp'] : 0;
        if ($expiresAt > 0 && $expiresAt < time()) {
            // Expired — best-effort delete
            @unlink($path);
            return [];
        }
        // Data is stored under 'data' key
        return isset($decoded['data']) && is_array($decoded['data']) ? $decoded['data'] : [];
    }

    /**
     * {@inheritdoc}
     */
    public function persist(string $id, array $data, int $ttlSeconds): void
    {
        $path = $this->filePath($id);
        $payload = [
            '__meta' => [
                'exp' => $ttlSeconds > 0 ? (time() + $ttlSeconds) : 0,
                'ts'  => time(),
            ],
            'data' => $data,
        ];
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        $fh = @fopen($path, 'c+');
        if ($fh === false) {
            throw new \RuntimeException("Unable to open session file for writing: {$path}");
        }
        try {
            if (!@flock($fh, LOCK_EX)) {
                throw new \RuntimeException('Failed to obtain lock for session file');
            }
            // Truncate and write
            ftruncate($fh, 0);
            rewind($fh);
            fwrite($fh, $json);
            fflush($fh);
        } finally {
            @flock($fh, LOCK_UN);
            fclose($fh);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function destroy(string $id): void
    {
        $path = $this->filePath($id);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function generateId(): string
    {
        return bin2hex(random_bytes(32));
    }

    private function filePath(string $id): string
    {
        // Avoid directory traversal; keep flat structure by hashing id
        $safe = preg_replace('/[^a-f0-9]/', '', strtolower($id)) ?? $id;
        return $this->directory . DIRECTORY_SEPARATOR . $safe . '.json';
    }
}
