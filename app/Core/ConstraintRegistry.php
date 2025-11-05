<?php
declare(strict_types=1);

namespace Ishmael\Core;

/**
 * ConstraintRegistry manages named route parameter constraints used by the Router.
 *
 * A constraint has two parts:
 * - A regex pattern (without delimiters) used to match the URL segment
 * - An optional converter callable that turns the matched string into a typed value
 *
 * Built-in constraints:
 * - int: one or more digits, converted to integer
 * - numeric: integer or decimal, converted to float
 * - bool: common boolean words/flags, converted to boolean
 * - slug: URL slug (letters, digits, dashes, or percent-encoded bytes), rawurldecoded
 * - alpha: letters only (ASCII plus percent-encoded bytes), rawurldecoded
 * - alnum: letters and digits (ASCII plus percent-encoded bytes), rawurldecoded
 * - uuid: UUID v1-5 canonical, passed through (lowercased)
 */
final class ConstraintRegistry
{
    /**
     * @var array<string, array{pattern:string, converter:callable|null}>
     */
    private static array $constraints = [];

    /**
     * Register or override a named constraint.
     *
     * @param string $name Constraint name used in route patterns (e.g., "int")
     * @param string|callable $regexOrCallable Either a regex pattern string (no delimiters) or a converter callable.
     * @param callable|null $converter Optional converter when pattern is provided separately.
     */
    public static function add(string $name, string|callable $regexOrCallable, ?callable $converter = null): void
    {
        if (is_string($regexOrCallable)) {
            self::$constraints[$name] = [
                'pattern' => $regexOrCallable,
                'converter' => $converter,
            ];
            return;
        }
        // If only a callable given, treat pattern as a permissive segment and use callable to validate/convert
        self::$constraints[$name] = [
            'pattern' => '[^/]+',
            'converter' => $regexOrCallable,
        ];
    }

    /**
     * Get the regex pattern for a constraint or null if unknown.
     */
    public static function getPattern(string $name): ?string
    {
        self::ensureBootstrapped();
        return self::$constraints[$name]['pattern'] ?? null;
    }

    /**
     * Convert a matched value using the constraint's converter (if any).
     * Returns the original value if no converter is set or constraint unknown.
     *
     * @param string $name
     * @param string $value Raw matched value (not yet decoded unless caller decoded)
     * @return mixed
     */
    public static function convert(string $name, string $value): mixed
    {
        self::ensureBootstrapped();
        $entry = self::$constraints[$name] ?? null;
        if ($entry === null || $entry['converter'] === null) {
            return $value;
        }
        return ($entry['converter'])($value);
    }

    /**
     * Ensure built-in constraints are registered.
     */
    private static function ensureBootstrapped(): void
    {
        if (!empty(self::$constraints)) {
            return;
        }
        // Percent-encoded byte or match group helpers
        $pct = '(?:%[0-9A-Fa-f]{2})';
        $alpha = '(?:[A-Za-z]|' . $pct . ')';
        $alnum = '(?:[A-Za-z0-9]|' . $pct . ')';

        // int → integer cast
        self::add('int', '\\d+', static function (string $v): int {
            return (int)$v;
        });
        // numeric → float cast (accept 123, 123.45)
        self::add('numeric', '\\d+(?:\\.\\d+)?', static function (string $v): float {
            return (float)$v;
        });
        // bool → common truthy/falsey tokens
        self::add('bool', '(?i:true|false|1|0|yes|no|on|off)', static function (string $v): bool {
            $vl = strtolower($v);
            return in_array($vl, ['1','true','yes','on'], true);
        });
        // slug → letters, digits, dashes, percent-encoded bytes; decode to return
        self::add('slug', '(?:' . $alnum . '|-)+', static function (string $v): string {
            return rawurldecode($v);
        });
        // alpha → letters only (with percent-encoded support), decode
        self::add('alpha', $alpha . '+', static function (string $v): string {
            return rawurldecode($v);
        });
        // alnum → letters/digits (with percent-encoded support), decode
        self::add('alnum', $alnum . '+', static function (string $v): string {
            return rawurldecode($v);
        });
        // uuid → canonical 8-4-4-4-12 (case-insensitive), normalize to lowercase
        self::add('uuid', '[0-9A-Fa-f]{8}-(?:[0-9A-Fa-f]{4}-){3}[0-9A-Fa-f]{12}', static function (string $v): string {
            return strtolower($v);
        });
    }
}
