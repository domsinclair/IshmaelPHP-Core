<?php

declare(strict_types=1);

namespace Ishmael\Core\Http\Middleware;

use Ishmael\Core\Authz\AuthorizationException;
use Ishmael\Core\Authz\Gate;
use Ishmael\Core\Http\Request;
use Ishmael\Core\Http\Response;

/**
 * Can middleware checks an authorization ability via Gate.
 * Usage: Router::add([...], 'path', handler, [Can::for('post.update')])
 */
final class Can
{
    /**
     * Factory returning a middleware callable that authorizes the given ability.
     * Optionally provide a resource resolver callable to extract the resource from Request/params.
     *
     * @param string $ability Ability name to check
     * @param callable|null $resourceResolver fn(Request $req, array $params): mixed
     * @return callable(Request, Response, callable): Response
     */
    public static function for(string $ability, ?callable $resourceResolver = null): callable
    {
        return function (Request $req, Response $res, callable $next) use ($ability, $resourceResolver): Response {

            /** @var Gate $gate */
            $gate = \app('gate');
            if (!$gate instanceof Gate) {
                $gate = new Gate();
                \app(['gate' => $gate]);
            }
            $params = [];
// Router passes params as third arg to handlers, but not to middleware. We allow resolvers to pull from globals when needed.
            if (is_callable($resourceResolver)) {
                try {
                    $resource = $resourceResolver($req, $params);
                } catch (\Throwable) {
                    $resource = null;
                }
            } else {
                $resource = null;
            }

            try {
                $gate->authorize($ability, $resource);
                return $next($req, $res);
            } catch (AuthorizationException $ex) {
                $accept = (string)($req->getHeader('Accept') ?? '');
                $isJson = str_contains(strtolower($accept), 'application/json')
                    || strtolower((string)$req->getHeader('X-Requested-With', '')) === 'xmlhttprequest';
                if ($isJson) {
                    return Response::json([
                        'error' => 'forbidden',
                        'message' => $ex->getMessage(),
                        'ability' => $ability,
                    ], 403);
                }
                return Response::html('<h1>Forbidden</h1>', 403);
            }
        };
    }
}
