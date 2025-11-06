<?php
declare(strict_types=1);

namespace Ishmael\Core\Http\Middleware;

use Ishmael\Core\Http\Request;
use Ishmael\Core\Http\Response;
use Ishmael\Core\Validation\ValidationException;

/**
 * HandleValidationExceptions captures ValidationException thrown by downstream
 * code and converts them to JSON 422 or HTML redirect with flashed error bag.
 */
final class HandleValidationExceptions
{
    public function __invoke(Request $req, Response $res, callable $next): Response
    {
        try {
            return $next($req, $res);
        } catch (ValidationException $ex) {
            $accept = (string)($req->getHeader('Accept') ?? '');
            $isJson = str_contains(strtolower($accept), 'application/json')
                || strtolower((string)$req->getHeader('X-Requested-With', '')) === 'xmlhttprequest';

            if ($isJson) {
                return Response::json([
                    'error' => 'validation_failed',
                    'messages' => $ex->getMessages(),
                    'codes' => $ex->getCodes(),
                ], 422);
            }

            // HTML: flash errors and old then redirect back
            if (function_exists('flash')) {
                flash('_errors', $ex->getMessages());
                flash('_error_codes', $ex->getCodes());
                flash('_old', $ex->getOld());
            }
            $location = function_exists('back') ? back('/') : '/';
            return new Response('', 302, ['Location' => $location]);
        }
    }
}
