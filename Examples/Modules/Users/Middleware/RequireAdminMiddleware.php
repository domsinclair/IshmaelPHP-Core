<?php
declare(strict_types=1);

namespace Modules\Users\Middleware;

use Ishmael\Core\Http\Request;
use Ishmael\Core\Http\Response;
use Ishmael\Core\Database;

final class RequireAdminMiddleware
{
    public function __invoke(Request $req, Response $res, callable $next): Response
    {
        /** @var \Ishmael\Core\Session\SessionManager|null $mgr */
        $mgr = session();
        $userId = $mgr ? (int)($mgr->get('user_id', 0)) : 0;
        if ($userId <= 0) {
            // Redirect to login
            return $res->setStatusCode(302)->header('Location', '/auth/login');
        }
        // Check role membership
        $adapter = Database::adapter();
        $has = $adapter->query(
            'SELECT 1 FROM user_roles ur INNER JOIN roles r ON r.id=ur.role_id WHERE ur.user_id=? AND r.slug=? LIMIT 1',
            [$userId, 'admin']
        )->first();
        if (!$has) {
            return $res->setStatusCode(403)->setBody('Forbidden');
        }
        return $next($req, $res);
    }
}
