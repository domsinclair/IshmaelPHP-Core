<?php
declare(strict_types=1);

namespace Ishmael\Core;

use Ishmael\Core\Http\Request;
use Ishmael\Core\Http\Response;

/**
 * RouterMiddleware utilities.
 *
 * Provides helpers to resolve middleware entries declared as callables or
 * invokable class names/arrays into callables with the standard signature:
 *   function(Request $req, Response $res, callable $next): Response
 */
final class RouterMiddleware
{
    /**
     * Normalize a middleware entry into a callable.
     *
     * @param callable|string|array{0: class-string, 1?: string} $entry
     * @return callable
     */
    public static function resolve(callable|string|array $entry): callable
    {
        if (is_callable($entry)) {
            return $entry;
        }
        if (is_string($entry) && class_exists($entry)) {
            $instance = new $entry();
            if (is_callable($instance)) {
                /** @var callable $call */
                $call = $instance;
                return $call;
            }
            throw new \InvalidArgumentException("Middleware class '$entry' is not invokable.");
        }
        if (is_array($entry) && isset($entry[0]) && is_string($entry[0]) && class_exists($entry[0])) {
            $obj = new $entry[0]();
            $method = $entry[1] ?? '__invoke';
            if (!method_exists($obj, (string)$method)) {
                throw new \InvalidArgumentException("Middleware method '$method' not found on class '{$entry[0]}'.");
            }
            /** @var callable $cb */
            $cb = [$obj, (string)$method];
            return $cb;
        }
        throw new \InvalidArgumentException('Unsupported middleware entry type.');
    }

    /**
     * Resolve an array stack of middleware entries into callables.
     * @param array<int, callable|string|array> $stack
     * @return array<int, callable>
     */
    public static function resolveStack(array $stack): array
    {
        $resolved = [];
        foreach ($stack as $mw) {
            $resolved[] = self::resolve($mw);
        }
        return $resolved;
    }
}
