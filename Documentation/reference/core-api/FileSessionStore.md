# FileSessionStore

Namespace: `Ishmael\Core\Session`  
Source: `IshmaelPHP-Core\app\Core\Session\FileSessionStore.php`

FileSessionStore persists session payloads as JSON files under storage/sessions.

### Public methods
- `__construct(string $directory)`
- `destroy(string $id): void` — {@inheritdoc}
- `generateId(): string` — {@inheritdoc}
- `load(string $id): array` — {@inheritdoc}
- `persist(string $id, array $data, int $ttlSeconds): void` — {@inheritdoc}
