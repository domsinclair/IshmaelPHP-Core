<?php
declare(strict_types=1);

namespace Ishmael\Core;

use Ishmael\Core\Http\Request;
use Ishmael\Core\Http\Response;

/**
 * Minimal HTTP router supporting fluent route definitions, groups, and module-aware dispatch.
 *
 * Exposes both static and instance APIs to allow route registration during module discovery
 * and conventional routing fallbacks for controller actions.
 */
class Router
{
    /** @var array<int, array{methods:string[], regex:string, paramNames:string[], handler:mixed, middleware:callable[], module?:string}> */
    private array $routes = [];
    /** @var array<int, array{prefix:string, middleware:callable[], module?:string}> */
    private array $groupStack = [];
    private bool $appliedModuleClosures = false;
    /** Last dispatched Response instance */
    private ?Response $lastResponse = null;

    /** Static facade active instance */
    private static ?self $active = null;

    public function getLastResponse(): ?Response
    {
        return $this->lastResponse;
    }

    public static function setActive(?self $router): void
    {
        self::$active = $router;
    }

    public static function get(string $pattern, $handler, array $middleware = []): self
    {
        return self::forwards()->add(['GET'], $pattern, $handler, $middleware);
    }
    public static function post(string $pattern, $handler, array $middleware = []): self
    {
        return self::forwards()->add(['POST'], $pattern, $handler, $middleware);
    }
    public static function put(string $pattern, $handler, array $middleware = []): self
    {
        return self::forwards()->add(['PUT'], $pattern, $handler, $middleware);
    }
    public static function patch(string $pattern, $handler, array $middleware = []): self
    {
        return self::forwards()->add(['PATCH'], $pattern, $handler, $middleware);
    }
    public static function delete(string $pattern, $handler, array $middleware = []): self
    {
        return self::forwards()->add(['DELETE'], $pattern, $handler, $middleware);
    }
    public static function any(string $pattern, $handler, array $middleware = []): self
    {
        return self::forwards()->add(['GET','POST','PUT','PATCH','DELETE','OPTIONS','HEAD'], $pattern, $handler, $middleware);
    }

    /** Group routes with options: prefix, middleware, module */
    public static function group(array $options, callable $callback): void
    {
        $r = self::forwards();
        $r->pushGroup($options);
        try {
            $callback($r);
        } finally {
            $r->popGroup();
        }
    }

    /** instance API equivalents */
    public function add(array $methods, string $pattern, $handler, array $middleware = []): self
    {
        $ctx = $this->currentGroup();
        $prefixed = $this->joinPaths($ctx['prefix'] ?? '', $pattern);
        [$regex, $paramNames] = $this->compilePattern($prefixed);
        $entry = [
            'methods' => array_map('strtoupper', $methods),
            'regex' => $regex,
            'paramNames' => $paramNames,
            'handler' => $handler,
            'middleware' => array_merge($ctx['middleware'] ?? [], $middleware),
        ];
        if (!empty($ctx['module'])) {
            $entry['module'] = (string)$ctx['module'];
        }
        $this->routes[] = $entry;
        return $this;
    }

    public function getInstance(): self { return $this; }

    public function dispatch(string $uri): void
    {
        // Apply module route closures once to allow modules to register via fluent API
        $this->applyModuleClosuresOnce();

        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        $path = trim((string)$path, '/');
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        // 1) Match API routes
        foreach ($this->routes as $route) {
            if (!in_array($method, $route['methods'], true)) {
                continue;
            }
            if (preg_match('#^' . $route['regex'] . '$#', $path, $m)) {
                $params = $this->extractParams($route['paramNames'], $m);
                $this->runRoute($route, $params);
                return;
            }
        }

        // 2) Legacy module array routes (BC)
        foreach (ModuleManager::$modules as $moduleName => $moduleData) {
            foreach ($moduleData['routes'] as $pattern => $handler) {
                if (preg_match('#^' . trim($pattern, '/') . '$#', $path, $matches)) {
                    [$controller, $action] = explode('@', $handler);
                    $this->execute($moduleName, $controller, $action, array_slice($matches, 1));
                    return;
                }
            }
        }

        // 3) Convention fallback (/{module}/{controller}/{action}/{params...})
        $parts = $path ? explode('/', $path) : [];
        $module = $parts[0] ?? 'HelloWorld';
        $controller = $parts[1] ?? 'Home';
        $action = $parts[2] ?? 'index';
        $params = array_slice($parts, 3);

        $this->execute($module, $controller, $action, $params);
    }

    private function runRoute(array $route, array $params): void
    {
        $request = Request::fromGlobals();
        $response = new Response();

        $terminal = function(Request $req, Response $res) use ($route, $params): Response {
            $handler = $route['handler'];
            // Support callable handler directly
            if (is_callable($handler)) {
                return $handler($req, $res, $params);
            }
            // Controller handler formats
            $module = $route['module'] ?? null;
            [$controller, $action] = is_array($handler)
                ? [$handler[0], $handler[1] ?? 'index']
                : explode('@', (string)$handler);

            return $this->invokeControllerHandler($module, $controller, $action, $params, $req, $res);
        };

        // Build middleware pipeline
        $pipeline = array_reduce(
            array_reverse($route['middleware']),
            function(callable $next, callable $mw) {
                return function(Request $req, Response $res) use ($mw, $next): Response {
                    return $mw($req, $res, $next);
                };
            },
            $terminal
        );

        $result = $pipeline($request, $response);
        // Track last response and emit into current SAPI (consistent with existing router behavior)
        $this->lastResponse = $result;
        http_response_code($result->getStatusCode());
        foreach ($result->getHeaders() as $k => $v) {
            header($k . ': ' . $v, true);
        }
        echo $result->getBody();
    }

    private function invokeControllerHandler(?string $module, string $controller, string $action, array $params, Request $req, Response $res): Response
    {
        // Ensure controller suffix
        if (substr($controller, -10) !== 'Controller' && !str_contains($controller, '\\')) {
            $controller .= 'Controller';
        }
        // Build FQCN
        if ($module === 'App') {
            $class = "App\\Controllers\\{$controller}";
        } elseif ($module) {
            $class = "Modules\\{$module}\\Controllers\\{$controller}";
        } elseif (str_contains($controller, '\\')) {
            $class = $controller; // fully-qualified provided
        } else {
            // default to App if none specified
            $class = "App\\Controllers\\{$controller}";
        }

        // Call via capturing output to preserve legacy echo-style controllers
        ob_start();
        if (!class_exists($class)) {
            http_response_code(404);
            echo "Controller not found: {$class}";
            return $res->setStatusCode(http_response_code() ?: 404)->setBody(ob_get_clean() ?: '');
        }
        $ctrl = new $class();
        if (!method_exists($ctrl, $action)) {
            http_response_code(404);
            echo "Action not found: {$action}";
            return $res->setStatusCode(http_response_code() ?: 404)->setBody(ob_get_clean() ?: '');
        }

        // Build argument list supporting (Request $req, Response $res, ...$params)
        $args = [];
        try {
            $ref = new \ReflectionMethod($ctrl, $action);
            $methodParams = $ref->getParameters();
            $positionals = array_values($params);
            $i = 0;
            foreach ($methodParams as $mp) {
                $type = $mp->getType();
                $typeName = $type instanceof \ReflectionNamedType ? $type->getName() : null;
                if ($typeName === Request::class) {
                    $args[] = $req;
                    continue;
                }
                if ($typeName === Response::class) {
                    $args[] = $res;
                    continue;
                }
                // Fill remaining user params positionally
                if (array_key_exists($i, $positionals)) {
                    $args[] = $positionals[$i++];
                    continue;
                }
                // Default if available
                if ($mp->isDefaultValueAvailable()) {
                    $args[] = $mp->getDefaultValue();
                    continue;
                }
                // No value available; break and let call handle missing args (will error) or supply null
                $args[] = null;
            }
            $ret = $ref->invokeArgs($ctrl, $args);
        } catch (\Throwable $e) {
            // Fallback to legacy behavior with positional params only
            $ret = call_user_func_array([$ctrl, $action], array_values($params));
        }

        $echoed = ob_get_clean() ?: '';

        if ($ret instanceof Response) {
            return $ret;
        }
        if (is_string($ret)) {
            return $res->setBody($ret);
        }
        return $res->setBody($echoed);
    }

    private function extractParams(array $names, array $matches): array
    {
        $out = [];
        $i = 1;
        foreach ($names as $n) {
            $out[$n] = $matches[$i++] ?? null;
        }
        return $out;
    }

    private function compilePattern(string $pattern): array
    {
        $pattern = trim($pattern, '/');
        $paramNames = [];
        $regex = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)(?::([a-zA-Z_][a-zA-Z0-9_]*))?\}/', function($m) use (&$paramNames) {
            $name = $m[1];
            $type = $m[2] ?? '';
            $paramNames[] = $name;
            return '(' . $this->constraintRegex($type) . ')';
        }, $pattern);
        if ($regex === null) {
            $regex = $pattern;
        }
        return [$regex, $paramNames];
    }

    private function constraintRegex(string $type): string
    {
        if ($type === 'int') {
            return '\\d+';
        }
        if ($type === 'slug') {
            return '[A-Za-z0-9-]+';
        }
        return '[^/]+';
    }

    private function joinPaths(string $a, string $b): string
    {
        $a = trim($a, '/');
        $b = trim($b, '/');
        $joined = ($a !== '' ? $a . '/' : '') . $b;
        return $joined === '' ? '/' : $joined;
    }

    private function pushGroup(array $options): void
    {
        $current = $this->currentGroup();
        $this->groupStack[] = [
            'prefix' => $this->joinPaths($current['prefix'] ?? '', (string)($options['prefix'] ?? '')),
            'middleware' => array_merge($current['middleware'] ?? [], (array)($options['middleware'] ?? [])),
            'module' => $options['module'] ?? ($current['module'] ?? null),
        ];
    }

    private function popGroup(): void
    {
        array_pop($this->groupStack);
    }

    /** @return array{prefix:string, middleware:callable[], module?:string} */
    private function currentGroup(): array
    {
        return $this->groupStack[count($this->groupStack) - 1] ?? ['prefix' => '', 'middleware' => []];
    }

    private static function forwards(): self
    {
        if (!self::$active) {
            self::$active = new self();
        }
        return self::$active;
    }

    /** Execute legacy controller path */
    private function execute(string $module, string $controller, string $action, array $params): void
    {
        // Ensure controller name ends with "Controller"
        if (substr($controller, -10) !== 'Controller') {
            $controller .= 'Controller';
        }
        // Determine fully qualified class name
        if ($module === 'App') {
            $controllerClass = "App\\Controllers\\{$controller}";
        } else {
            $controllerClass = "Modules\\{$module}\\Controllers\\{$controller}";
        }
        // Check controller exists
        if (!class_exists($controllerClass)) {
            http_response_code(404);
            $this->lastResponse = Response::text("Controller not found: {$controllerClass}", 404);
            echo $this->lastResponse->getBody();
            return;
        }
        $ctrl = new $controllerClass();
        // Check action exists
        if (!method_exists($ctrl, $action)) {
            http_response_code(404);
            $this->lastResponse = Response::text("Action not found: {$action}", 404);
            echo $this->lastResponse->getBody();
            return;
        }
        // Call action
        call_user_func_array([$ctrl, $action], $params);
    }

    public static function cache(bool $enabled = true): void
    {
        // Stub for future route caching
    }

    private function applyModuleClosuresOnce(): void
    {
        if ($this->appliedModuleClosures) { return; }
        foreach (ModuleManager::$modules as $moduleName => $moduleData) {
            $closure = $moduleData['routeClosure'] ?? null;
            if ($closure instanceof \Closure) {
                // Run closure within a group carrying module context
                $this->pushGroup(['module' => $moduleName, 'prefix' => '', 'middleware' => []]);
                try {
                    $closure($this);
                } finally {
                    $this->popGroup();
                }
            }
        }
        $this->appliedModuleClosures = true;
    }
}
