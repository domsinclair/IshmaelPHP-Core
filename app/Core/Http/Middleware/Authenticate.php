<?php
declare(strict_types=1);

namespace Ishmael\Core\Http\Middleware;

use Ishmael\Core\Auth\AuthManager;
use Ishmael\Core\Http\Request;
use Ishmael\Core\Http\Response;

/**
 * Authenticate middleware ensures the current request is authenticated.
 * - For JSON/XHR requests, returns 401 JSON payload.
 * - For HTML requests, redirects to configured login path.
 */
final class Authenticate
{
    public function __invoke(Request $req, Response $res, callable $next): Response
    {
        /** @var AuthManager $auth */
        $auth = \app('auth') instanceof AuthManager ? \app('auth') : new AuthManager();
        if ($auth->check()) {
            return $next($req, $res);
        }

        $accept = $req->getHeader('Accept', '');
        $xhr = strtolower((string)$req->getHeader('X-Requested-With', '')) === 'xmlhttprequest';
        if (str_contains((string)$accept, 'application/json') || $xhr) {
            return Response::json(['message' => 'Unauthenticated'], 401);
        }

        $login = (string) (\config('auth.redirects.login') ?? '/login');
        // Basic redirect response
        return new Response('', 302, ['Location' => $login]);
    }
}
