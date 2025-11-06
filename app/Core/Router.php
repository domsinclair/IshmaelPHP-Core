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
    /** @var array<int, array{methods:string[], regex:string, paramNames:string[], paramTypes:string[], handler:mixed, middleware:array<int, callable|string>, pattern:string, module?:string, name?:string}> */
    private array $routes = [];
    /** @var array<int, array{prefix:string, middleware:array<int, callable|string>, module?:string}> */
    private array $groupStack = [];
    /** @var array<int, callable|string> */
    private array $globalMiddleware = [];
    private bool $appliedModuleClosures = false;
    /** Last dispatched Response instance */
    private ?Response $lastResponse = null;
    /** Index of the most recently added route for chaining (e.g., ->name()) */
    private ?int $lastAddedIndex = null;
    /** @var array<string,int> Map route name to index in $routes */
    private array $nameIndex = [];
    /** @var array<string, array{pattern: ?string, module:?string, source:string}> Named routes coming from legacy array route files */
    private array $namedArrayRoutes = [];

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

    /**
     * Set the global middleware stack applied to every route.
     * Each entry may be a callable or an invokable class string.
     * @param array<int, callable|string> $stack
     */
    public function setGlobalMiddleware(array $stack): void
    {
        $this->globalMiddleware = $stack;
    }

    /**
     * Static facade to set global middleware on active router.
     * @param array<int, callable|string> $stack
     */
    public static function useGlobal(array $stack): void
    {
        self::forwards()->setGlobalMiddleware($stack);
    }

    /**
     * Add a single global middleware.
     * @param callable|string $mw
     */
    public function addGlobalMiddleware($mw): void
    {
        $this->globalMiddleware[] = $mw;
    }

    /**
     * Register a route on this router instance.
     *
     * Performs compile-time collision detection against existing routes sharing any HTTP method.
     *
     * @param array<int,string> $methods HTTP methods (e.g., ['GET'])
     * @param string $pattern Route pattern, may include {param} or {param:type}
     * @param mixed $handler Controller string "Controller@action", [Controller, action], or callable
     * @param array<int, callable|string|array{0:class-string,1?:string}> $middleware Middleware stack for this route
     * @return self
     * @throws \LogicException When a conflicting route is detected for one or more methods
     */
    public function add(array $methods, string $pattern, $handler, array $middleware = []): self
    {
        $ctx = $this->currentGroup();
        $prefixed = $this->joinPaths($ctx['prefix'] ?? '', $pattern);
        [$regex, $paramNames, $paramTypes] = $this->compilePattern($prefixed);

        // Collision detection: prevent conflicting static/param routes with same method
        $newMethods = array_map('strtoupper', $methods);
        foreach ($this->routes as $existing) {
            // Quick reject: no method overlap
            if (empty(array_intersect($newMethods, $existing['methods']))) {
                continue;
            }
            // 1) Collision if compiled regex is identical → would match same paths
            $conflict = ($existing['regex'] === $regex);

            // 2) Static vs param collision: if either side is static (no params) and the other's regex matches that static path
            if (!$conflict) {
                $existingIsStatic = empty($existing['paramNames']);
                $newIsStatic = empty($paramNames);
                $existingPath = '/' . trim((string)($existing['pattern'] ?? ''), '/');
                $newPath = '/' . trim($prefixed, '/');
                if ($existingIsStatic && preg_match('#^' . $regex . '$#', trim($existingPath, '/'))) {
                    $conflict = true;
                } elseif ($newIsStatic && preg_match('#^' . $existing['regex'] . '$#', trim($newPath, '/'))) {
                    $conflict = true;
                }
            }

            if ($conflict) {
                $confModule = $existing['module'] ?? ($ctx['module'] ?? 'App');
                $newModule = $ctx['module'] ?? 'App';
                $confName = $existing['name'] ?? '';
                $methodsStr = implode(',', array_intersect($newMethods, $existing['methods']));
                $msg = "Route collision detected for method(s) {$methodsStr}: '{$prefixed}' conflicts with existing pattern '{$existing['pattern']}'";
                $msg .= " (modules: new={$newModule}, existing={$confModule})";
                if ($confName !== '') { $msg .= " [existing name: {$confName}]"; }
                throw new \LogicException($msg);
            }
        }

        $entry = [
            'methods' => $newMethods,
            'regex' => $regex,
            'paramNames' => $paramNames,
            'paramTypes' => $paramTypes,
            'handler' => $handler,
            'middleware' => array_merge($ctx['middleware'] ?? [], $middleware),
            'pattern' => trim($prefixed, '/'),
        ];
        if (!empty($ctx['module'])) {
            $entry['module'] = (string)$ctx['module'];
        }
        $this->routes[] = $entry;
        $this->lastAddedIndex = count($this->routes) - 1;
        return $this;
    }

    public function getInstance(): self { return $this; }

    /**
     * Export the compiled route entries for caching.
     *
     * @return array<int, array{methods:string[], regex:string, paramNames:string[], paramTypes:string[], handler:mixed, middleware:array<int, callable|string>, pattern:string, module?:string, name?:string}>
     */
    public function exportCompiledMap(): array
    {
        return $this->routes;
    }

    /**
     * Load a previously compiled route map. This bypasses module route registration.
     *
     * @param array<int, array{methods:string[], regex:string, paramNames:string[], paramTypes:string[], handler:mixed, middleware:array<int, callable|string>, pattern:string, module?:string, name?:string}> $routes
     * @return void
     */
    public function loadCompiledMap(array $routes): void
    {
        $this->routes = $routes;
        $this->nameIndex = [];
        foreach ($this->routes as $i => $r) {
            if (isset($r['name']) && is_string($r['name'])) {
                $this->nameIndex[$r['name']] = (int)$i;
            }
        }
        // Prevent module closures from running; cache already contains compiled routes
        $this->appliedModuleClosures = true;
    }

    /**
     * Assign a name to the most recently added route for URL generation.
     */
    public function name(string $routeName): self
    {
        if ($this->lastAddedIndex === null) {
            throw new \LogicException('Cannot assign a name() before adding a route.');
        }
        $routeName = trim($routeName);
        if ($routeName === '') {
            throw new \InvalidArgumentException('Route name cannot be empty.');
        }
        $this->routes[$this->lastAddedIndex]['name'] = $routeName;
        $this->nameIndex[$routeName] = $this->lastAddedIndex;
        return $this;
    }

    /**
     * Generate a URL by route name.
     *
     * @param string $name Route name
     * @param array<string,mixed> $params Parameters to fill placeholders
     * @param array<string,mixed> $query Optional query parameters
     * @param bool $absolute When true, include scheme and host from current request
     */
    public static function url(string $name, array $params = [], array $query = [], bool $absolute = false): string
    {
        return self::forwards()->generateUrl($name, $params, $query, $absolute);
    }

    /**
     * Instance URL generation implementation.
     * @param array<string,mixed> $params
     * @param array<string,mixed> $query
     */
    public function generateUrl(string $name, array $params = [], array $query = [], bool $absolute = false): string
    {
        // Ensure any array-named legacy routes are indexed for lookup
        $this->ensureNamedArrayRoutesIndexed();

        if (isset($this->nameIndex[$name])) {
            $idx = $this->nameIndex[$name];
            $route = $this->routes[$idx];
            $path = $this->interpolatePattern($route['pattern'] ?? '', $route['paramNames'] ?? [], $route['paramTypes'] ?? [], $params, $name, 'fluent');
            return $this->finalizeUrl($path, $query, $absolute);
        }

        // Fallback to legacy named array routes
        if (isset($this->namedArrayRoutes[$name])) {
            $info = $this->namedArrayRoutes[$name];
            $pattern = (string)($info['pattern'] ?? '');
            // Only support simple static patterns from legacy arrays
            if ($pattern === '' ) {
                throw new \InvalidArgumentException("Cannot generate URL for named route '{$name}' — no pattern available (defined in {$info['source']}).");
            }
            if ($this->isSimpleStaticRegex($pattern)) {
                $path = $this->stripRegexDelimiters($pattern);
                return $this->finalizeUrl($path, $query, $absolute);
            }
            throw new \InvalidArgumentException("Cannot generate URL for named route '{$name}' defined in {$info['source']} — complex regex patterns are not supported for URL generation. Define the route via the fluent API to enable URL generation.");
        }

        throw new \InvalidArgumentException("Unknown route name '{$name}'.");
    }

    /**
     * Replace placeholder tokens in a stored pattern with provided params, validating presence and encoding.
     * @param array<int,string> $paramNames
     * @param array<int,string> $paramTypes
     * @param array<string,mixed> $params
     */
    private function interpolatePattern(string $storedPattern, array $paramNames, array $paramTypes, array $params, string $routeName, string $source): string
    {
        $missing = [];
        foreach ($paramNames as $p) {
            if (!array_key_exists($p, $params)) {
                $missing[] = $p;
            }
        }
        if (!empty($missing)) {
            $list = implode(', ', $missing);
            throw new \InvalidArgumentException("Missing parameters [{$list}] for route '{$routeName}' (source: {$source}).");
        }
        $replaced = $storedPattern;
        foreach ($paramNames as $i => $p) {
            $type = $paramTypes[$i] ?? '';
            $val = $params[$p];
            $segment = $this->encodeParamForType($val, $type);
            $replaced = preg_replace('/\{' . preg_quote($p, '/') . '(?::[a-zA-Z_][a-zA-Z0-9_]*)?\}/', $segment, $replaced, 1);
        }
        return '/' . trim($replaced, '/');
    }

    private function encodeParamForType(mixed $value, string $type): string
    {
        if ($value === null) {
            return '';
        }
        // For numeric/bool/uuid, cast to string directly
        if ($type === 'int' || $type === 'numeric' || $type === 'bool' || $type === 'uuid') {
            return (string)$value;
        }
        // Default and slug-like values: rawurlencode
        return rawurlencode((string)$value);
    }

    private function finalizeUrl(string $path, array $query, bool $absolute): string
    {
        $path = '/' . ltrim(trim($path, '/'), '/');
        if (!empty($query)) {
            $qs = http_build_query($query);
            if ($qs !== '') {
                $path .= '?' . $qs;
            }
        }
        if (!$absolute) {
            return $path;
        }
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
        return $scheme . '://' . $host . $path;
    }

    private function ensureNamedArrayRoutesIndexed(): void
    {
        static $done = false;
        if ($done) { return; }
        foreach (ModuleManager::$modules as $moduleName => $moduleData) {
            foreach ($moduleData['routes'] as $pattern => $handler) {
                if (is_array($handler)) {
                    $name = $handler['name'] ?? null;
                    if ($name) {
                        $this->namedArrayRoutes[(string)$name] = [
                            'pattern' => is_string($pattern) ? trim($pattern, '/') : null,
                            'module' => $moduleName,
                            'source' => "module:{$moduleName} routes.php",
                        ];
                    }
                }
            }
        }
        $done = true;
    }

    private function isSimpleStaticRegex(string $pattern): bool
    {
        // Allow optional ^ and $ anchors and only safe path characters
        $p = trim($pattern);
        if ($p === '') { return false; }
        $p = trim($p, '^$');
        return (bool)preg_match('#^[A-Za-z0-9_\-/]+$#', $p);
    }

    private function stripRegexDelimiters(string $pattern): string
    {
        $p = trim($pattern);
        $p = trim($p, '^$');
        return '/' . ltrim($p, '/');
    }

    public function dispatch(string $uri): void
    {
        // Apply module route closures once to allow modules to register via fluent API
        $this->applyModuleClosuresOnce();

        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        $path = trim((string)$path, '/');
        // Determine method; attempt override only if initial scan fails
        $originalMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $method = $originalMethod;
        $override = null;
        if ($originalMethod === 'POST') {
            $override = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']
                ?? $_SERVER['X_HTTP_METHOD_OVERRIDE']
                ?? $_SERVER['HTTP_X_METHOD_OVERRIDE']
                ?? null;
            if (!$override && isset($_POST['_method'])) {
                $override = $_POST['_method'];
            }
            if (is_string($override)) {
                $override = strtoupper(trim($override));
                if (!in_array($override, ['GET','POST','PUT','PATCH','DELETE','OPTIONS','HEAD'], true)) {
                    $override = null; // ignore invalid
                }
            } else {
                $override = null;
            }
        }

        $tryMatch = function(string $useMethod) use ($path): ?array {
            foreach ($this->routes as $route) {
                if (!in_array($useMethod, $route['methods'], true)) {
                    continue;
                }
                if (preg_match('#^' . $route['regex'] . '$#', $path, $m)) {
                    return [$route, $m];
                }
            }
            return null;
        };

        $match = $tryMatch($method);
        if ($match === null && $override !== null) {
            $match = $tryMatch($override);
        }
        if ($match !== null) {
            [$route, $m] = $match;
            $params = $this->extractParams($route['paramNames'], $m, $route['paramTypes'] ?? []);
            $this->runRoute($route, $params);
            return;
        }

        // If path matches any route with different method(s), return 405 with Allow header
        $allowed = [];
        foreach ($this->routes as $route) {
            if (preg_match('#^' . $route['regex'] . '$#', $path)) {
                foreach ($route['methods'] as $mth) { $allowed[$mth] = true; }
            }
        }
        if (!empty($allowed)) {
            $allowHeader = implode(', ', array_keys($allowed));
            $res = Response::text('Method Not Allowed', 405);
            $res->header('Allow', $allowHeader);
            $this->lastResponse = $res;
            http_response_code(405);
            header('Allow: ' . $allowHeader, true);
            echo $res->getBody();
            return;
        }

        // 2) Legacy module array routes (BC)
        foreach (ModuleManager::$modules as $moduleName => $moduleData) {
            foreach ($moduleData['routes'] as $pattern => $handler) {
                if (preg_match('#^' . trim((string)$pattern, '/') . '$#', $path, $matches)) {
                    $h = $handler;
                    if (is_array($handler)) {
                        $h = $handler['handler'] ?? null;
                        if (!is_string($h)) {
                            continue; // invalid legacy entry; skip
                        }
                        // If a name is present, index it for URL generation as a static pattern
                        if (!empty($handler['name'])) {
                            $this->namedArrayRoutes[(string)$handler['name']] = [
                                'pattern' => is_string($pattern) ? trim((string)$pattern, '/') : null,
                                'module' => $moduleName,
                                'source' => "module:{$moduleName} routes.php",
                            ];
                        }
                    }
                    [$controller, $action] = explode('@', (string)$h);
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

        // Build middleware pipeline: global -> route (group middleware already merged into route)
        $stack = array_merge(RouterMiddleware::resolveStack($this->globalMiddleware), RouterMiddleware::resolveStack($route['middleware']));
        $pipeline = array_reduce(
            array_reverse($stack),
            function(callable $next, callable $mw) {
                return function(Request $req, Response $res) use ($mw, $next): Response {
                    return $mw($req, $res, $next);
                };
            },
            $terminal
        );

        $result = $pipeline($request, $response);
        if (!($result instanceof Response)) {
            // Normalize non-Response returns to a Response::text
            $result = Response::text((string)$result);
        }
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
        // Ensure controller suffix for non-FQCN
        if (substr($controller, -10) !== 'Controller' && !str_contains($controller, '\\')) {
            $controller .= 'Controller';
        }
        // Build FQCN from module hint or fully-qualified name
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
            $msg = "Controller not found: {$class}. Did you create class {$class} and autoload it?";
            $body = $msg;
            ob_end_clean();
            return Response::text($body, 404);
        }
        $ctrl = new $class();
        if (!method_exists($ctrl, $action)) {
            // Suggest available public methods (excluding magic/constructor)
            $refClass = new \ReflectionClass($ctrl);
            $methods = array_values(array_filter(
                array_map(fn($m) => $m->getName(), $refClass->getMethods(\ReflectionMethod::IS_PUBLIC)),
                fn($n) => $n[0] !== '_' && $n !== '__invoke' && $n !== '__construct'
            ));
            $suggest = $methods ? (' Available actions: ' . implode(', ', $methods)) : '';
            ob_end_clean();
            return Response::text("Action not found: {$action} on {$class}." . $suggest, 404);
        }

        // Build argument list supporting (Request $req, Response $res, ...$params)
        $args = [];
        try {
            $ref = new \ReflectionMethod($ctrl, $action);
            $methodParams = $ref->getParameters();
            $positionals = array_values($params);
            $i = 0;
            $seenReq = false; $seenRes = false;
            foreach ($methodParams as $mp) {
                $type = $mp->getType();
                $typeName = $type instanceof \ReflectionNamedType ? $type->getName() : null;
                if ($typeName === Request::class) {
                    if ($seenReq) {
                        throw new \InvalidArgumentException("Invalid handler signature: multiple Request parameters in {$class}::{$action}.");
                    }
                    $seenReq = true;
                    $args[] = $req;
                    continue;
                }
                if ($typeName === Response::class) {
                    if ($seenRes) {
                        throw new \InvalidArgumentException("Invalid handler signature: multiple Response parameters in {$class}::{$action}.");
                    }
                    $seenRes = true;
                    $args[] = $res;
                    continue;
                }
                // If a class-typed parameter is present that we cannot resolve, error early with a nice message
                if ($typeName && class_exists($typeName)) {
                    throw new \InvalidArgumentException("Invalid handler parameter type '{$typeName}' in {$class}::{$action}. Only Request and Response are auto-injected; other parameters must be scalars mapped from route segments.");
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
                // No value available; supply null
                $args[] = null;
            }
            $ret = $ref->invokeArgs($ctrl, $args);
        } catch (\InvalidArgumentException $ex) {
            ob_end_clean();
            return Response::text($ex->getMessage(), 500);
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

    /**
     * Extract parameter values from regex matches and convert them according to their declared types.
     *
     * @param array<int,string> $names Parameter names in order of appearance
     * @param array<int,string> $matches Full regex matches array from preg_match
     * @param array<int,string> $types Parameter types in order (may contain empty string for default)
     * @return array<string,mixed>
     */
    private function extractParams(array $names, array $matches, array $types = []): array
    {
        $out = [];
        $i = 1;
        foreach ($names as $idx => $n) {
            $raw = $matches[$i++] ?? null;
            if ($raw === null) {
                $out[$n] = null;
                continue;
            }
            // Percent-decoding is handled in converters for types that need it; default leaves as-is
            $type = $types[$idx] ?? '';
            if ($type !== '') {
                $out[$n] = ConstraintRegistry::convert($type, $raw);
            } else {
                $out[$n] = $raw;
            }
        }
        return $out;
    }

    /**
     * Compile a human-readable pattern into a regex and capture metadata.
     * Returns tuple of [regex, paramNames, paramTypes].
     *
     * @return array{0:string,1:array<int,string>,2:array<int,string>}
     */
    private function compilePattern(string $pattern): array
    {
        $pattern = trim($pattern, '/');
        $paramNames = [];
        $paramTypes = [];
        $regex = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)(?::([a-zA-Z_][a-zA-Z0-9_]*))?\}/', function($m) use (&$paramNames, &$paramTypes) {
            $name = $m[1];
            $type = $m[2] ?? '';
            $paramNames[] = $name;
            $paramTypes[] = $type;
            $pattern = $this->constraintRegex($type);
            return '(' . $pattern . ')';
        }, $pattern);
        if ($regex === null) {
            $regex = $pattern;
        }
        return [$regex, $paramNames, $paramTypes];
    }

    private function constraintRegex(string $type): string
    {
        if ($type !== '') {
            $p = ConstraintRegistry::getPattern($type);
            if ($p !== null) {
                return $p;
            }
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

    /**
     * Toggle route caching integration (no-op placeholder; kept for BC).
     * Prefer using RouteCache at bootstrap/CLI to load or generate caches.
     *
     * @param bool $enabled Enable/disable caching (unused).
     * @return void
     */
    public static function cache(bool $enabled = true): void
    {
        // Left as a no-op to preserve public API; caching is handled by RouteCache.
    }

    /**
     * Ensure module route closures have been applied to this router instance.
     * Idempotent; safe to call multiple times.
     *
     * @return void
     */
    public function buildRoutes(): void
    {
        $this->applyModuleClosuresOnce();
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
