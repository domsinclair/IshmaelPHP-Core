<?php
declare(strict_types=1);

namespace Ishmael\Core\Http\Middleware;

use Ishmael\Core\Http\Request;
use Ishmael\Core\Http\Response;

/**
 * TrailingSlashMiddleware
 *
 * Normalizes request path by trimming trailing slashes internally. This middleware does not
 * perform redirects; the Router already matches paths without caring about trailing slashes.
 * It exists to support future features and to keep a consistent place for path normalization.
 */
final class TrailingSlashMiddleware
{
    public function __invoke(Request $req, Response $res, callable $next): Response
    {
        // No mutation needed for current Router which already trims; pass through.
        return $next($req, $res);
    }
}
