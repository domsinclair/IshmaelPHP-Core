<?php

declare(strict_types=1);

namespace Ishmael\Core\Http;

use DateTimeImmutable;
use DateTimeInterface;

/**
 * HTTP validators helpers for ETag and Last-Modified.
 */
final class HttpValidators
{
    /**
     * Generate an HTTP ETag value from a payload.
     * Strong ETags use a stable hash of the payload; Weak ETags are prefixed with "W/".
     *
     * @param string $payload Raw string payload to hash (e.g., response body or canonical representation)
     * @param bool $weak When true, returns a weak ETag (W/"...")
     * @return string The formatted ETag header value, including quotes and optional W/ prefix
     */
    public static function makeEtag(string $payload, bool $weak = false): string
    {
        // Use a stable fast hash; sha256 is stable across platforms and PHP versions
        $hash = base64_encode(hash('sha256', $payload, true));
        $tag = '"' . $hash . '"';
        return $weak ? 'W/' . $tag : $tag;
    }

    /**
     * Normalize an ETag value by trimming whitespace.
     * @param string $etag
     * @return string
     */
    public static function normalizeEtag(string $etag): string
    {
        return trim($etag);
    }

    /**
     * Parse an If-None-Match header into an array of raw tag values.
     * It preserves W/ prefixes and surrounding quotes for each token.
     *
     * @param string $header
     * @return array<int,string>
     */
    public static function parseIfNoneMatch(string $header): array
    {
        $header = trim($header);
        if ($header === '') {
            return [];
        }
        if ($header === '*') {
            return ['*'];
        }
        $parts = array_map('trim', explode(',', $header));
        $tags = [];
        foreach ($parts as $p) {
            if ($p !== '') {
                $tags[] = $p;
            }
        }
        return $tags;
    }

    /**
     * Compare client ETags against a current ETag, observing strong/weak semantics.
     *
     * - A strong comparison requires exact match including quotes with no W/ prefix on either side.
     * - A weak comparison matches tags whose opaque value is equal, ignoring optional leading "W/".
     * - If client sends '*', it matches any current representation with an ETag.
     *
     * @param array<int,string> $clientEtags Values from If-None-Match
     * @param string $currentEtag Current server ETag
     * @param bool $allowWeak When true, allow weak comparison to match
     * @return bool True if any tag matches according to the rules
     */
    public static function isEtagMatch(array $clientEtags, string $currentEtag, bool $allowWeak = true): bool
    {
        $current = self::normalizeEtag($currentEtag);
        if ($current === '') {
            return false;
        }
        foreach ($clientEtags as $tag) {
            $t = self::normalizeEtag($tag);
            if ($t === '*') {
                return true;
            }
            // Strong match: exact equality and neither has W/ prefix
            $tIsWeak = str_starts_with($t, 'W/');
            $cIsWeak = str_starts_with($current, 'W/');
            if (!$tIsWeak && !$cIsWeak && $t === $current) {
                return true;
            }
            if ($allowWeak) {
                $tVal = ltrim($t, 'W/');
                $cVal = ltrim($current, 'W/');
                if ($tVal === $cVal) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Format a DateTimeInterface to an RFC7231 IMF-fixdate string for Last-Modified header.
     */
    public static function formatHttpDate(DateTimeInterface $dt): string
    {
        // Always output in GMT per RFC
        return gmdate('D, d M Y H:i:s', $dt->getTimestamp()) . ' GMT';
    }

    /**
     * Parse an HTTP-date string into a DateTimeImmutable, or null if invalid.
     */
    public static function parseHttpDate(string $date): ?DateTimeImmutable
    {
        $ts = strtotime($date);
        if ($ts === false) {
            return null;
        }
        return (new DateTimeImmutable('@' . $ts))->setTimezone(new \DateTimeZone('UTC'));
    }
}
