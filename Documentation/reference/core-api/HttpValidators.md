# HttpValidators

Namespace: `Ishmael\Core\Http`  
Source: `IshmaelPHP-Core\app\Core\Http\HttpValidators.php`

HTTP validators helpers for ETag and Last-Modified.

### Public methods
- `formatHttpDate(DateTimeInterface $dt): string` — Format a DateTimeInterface to an RFC7231 IMF-fixdate string for Last-Modified header.
- `isEtagMatch(array $clientEtags, string $currentEtag, bool $allowWeak = true): bool` — Compare client ETags against a current ETag, observing strong/weak semantics.
- `makeEtag(string $payload, bool $weak = false): string` — Generate an HTTP ETag value from a payload.
- `normalizeEtag(string $etag): string` — Normalize an ETag value by trimming whitespace.
- `parseHttpDate(string $date): ?DateTimeImmutable` — Parse an HTTP-date string into a DateTimeImmutable, or null if invalid.
- `parseIfNoneMatch(string $header): array` — Parse an If-None-Match header into an array of raw tag values.
