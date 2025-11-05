<?php
declare(strict_types=1);

namespace Ishmael\Core\Http\Middleware;

use Ishmael\Core\Http\Request;
use Ishmael\Core\Http\Response;

/**
 * MethodOverrideMiddleware
 *
 * Supports HTTP method override using the X-HTTP-Method-Override header or a
 * form field named _method. When present and valid, the request method is
 * replaced before reaching downstream middleware/handlers.
 */
final class MethodOverrideMiddleware
{
    /** @var string[] */
    private array $allowed = ['GET','POST','PUT','PATCH','DELETE','OPTIONS','HEAD'];

    public function __invoke(Request $req, Response $res, callable $next): Response
    {
        $override = $req->getHeader('X-HTTP-Method-Override');
        if (!$override && isset($_POST['_method']) && is_string($_POST['_method'])) {
            $override = $_POST['_method'];
        }
        if (is_string($override) && $override !== '') {
            $ov = strtoupper($override);
            if (in_array($ov, $this->allowed, true)) {
                $req = $req->withMethod($ov);
            }
        }
        return $next($req, $res);
    }
}
