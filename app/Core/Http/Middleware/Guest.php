<?php
declare(strict_types=1);

namespace Ishmael\Core\Http\Middleware;

use Ishmael\Core\Auth\AuthManager;
use Ishmael\Core\Http\Request;
use Ishmael\Core\Http\Response;

/**
 * Guest middleware redirects authenticated users away from guest-only routes
 * (like the login page) to a configured home path.
 */
final class Guest
{
    public function __invoke(Request $req, Response $res, callable $next): Response
    {
        /** @var AuthManager $auth */
        $auth = \app('auth') instanceof AuthManager ? \app('auth') : new AuthManager();
        if ($auth->guest()) {
            return $next($req, $res);
        }
        $home = (string) (\config('auth.redirects.home') ?? '/');
        return new Response('', 302, ['Location' => $home]);
    }
}
