<?php

    declare(strict_types=1);

namespace Ishmael\Core;

/**
     * Base Controller
     * ----------------
     * Provides common functionality for all controllers.
     * Handles view rendering, JSON responses, and shared helpers.
     *
     * View Conventions
     * - View files are plain PHP located under: Modules/{Module}/Views/{resource}/{view}.php
     * - Common view names: index.php, show.php, create.php, edit.php, _form.php, _flash.php, layout.php
     * - The render() method accepts the view path relative to the module's Views/ directory (without .php)
     * - Layouts are optional. A child view can opt into a layout by setting $layoutFile inside the view.
     *
     * Variables exposed to views
     * - $sections  Ishmael\Core\ViewSections instance available for defining/yielding sections.
     * - $data      Array of controller-scoped data (from $this->data), convenient for layouts/partials.
     * - $request   Current HTTP request when available; may be null in legacy dispatch paths.
     * - $response  Current HTTP response when available; may be null in legacy dispatch paths.
     * - $route     Callable helper for URL generation by named routes: fn(string $name, array $params = [], array $query = [], bool $absolute = false): string
     */
abstract class Controller
{
    /** @var array<string,mixed> Arbitrary data available to views */
    protected array $data = [];
/**
     * Render a view file from within the current module or app.
     *
     * Backward compatibility:
     * - If no layout is requested by the child view, rendering behaves as before (direct output).
     *
     * Layouts (optional):
     * - Inside the child view, set a variable $layoutFile (relative to Views/, without .php or with .php).
     * - Use the minimal sections helper $sections to define blocks via start()/end() and consume via yield() in layout.
     * - If no 'content' section is defined by the child view, the entire child output becomes the 'content' section.
     *
     * Variables available inside views:
     * - $sections  Ishmael\Core\ViewSections instance.
     * - $data      Array copy of $this->data from the controller instance.
     * - $request   Current request when available (best-effort, may be null).
     * - $response  Current response when available (best-effort, may be null).
     *
     * @param string $view View name relative to the module's Views/ folder (without .php).
     * @param array<string,mixed> $vars Variables extracted into the view scope.
     */
    protected function render(string $view, array $vars = []): void
    {
        $moduleName = $this->getModuleName();
        $module = ModuleManager::get($moduleName);
        if (!$module) {
            http_response_code(500);
            echo "Module not found: {$moduleName}";
            return;
        }

        $basePath = rtrim($module['path'], '/\\') . '/Views/';
        $viewPath = $basePath . $view . '.php';
        if (!file_exists($viewPath)) {
            http_response_code(500);
            echo "View not found: {$viewPath}";
            return;
        }

        // Expose a minimal sections helper to views
        $sections = new ViewSections();
// Also expose common variables to the view scope (best-effort)
        /** @var array<string,mixed> $data */
        $data = $this->data;
/** @var \Ishmael\Core\Http\Request|null $request */
        $request = \Ishmael\Core\Http\Request::fromGlobals();
/** @var \Ishmael\Core\Http\Response|null $response */
        $response = null;
// Response instance may not be available in legacy dispatch; reserved for future pipeline wiring

        // Make provided variables available in the view scope (cannot overwrite reserved ones)
        extract($vars, EXTR_SKIP);
// Provide a lightweight route() callable in view scope for named URL generation
        /** @var callable(string,array<string,mixed>,array<string,mixed>,bool):string $route */
        $route = static function (string $name, array $params = [], array $query = [], bool $absolute = false): string {

            return \Ishmael\Core\Router::url($name, $params, $query, $absolute);
        };
// Buffer child view output to allow optional layout handling without breaking existing behavior
            ob_start();
        include $viewPath;
        $childOutput = (string) ob_get_clean();
// If the child view sets $layoutFile, attempt to render a layout
        if (isset($layoutFile) && is_string($layoutFile) && $layoutFile !== '') {
// If the view did not explicitly define the 'content' section, use the child output
            if (!$sections->has('content')) {
                $sections->set('content', $childOutput);
            }

            // Resolve layout path:
            // - Absolute paths (Windows drive-letter, Windows UNC, or Unix '/') are honored as-is.
            // - Paths starting with './' or '../' are resolved relative to the VIEW file's directory.
            // - Other relative paths are resolved against the module's Views/ base.
            $layoutPath = $layoutFile;
            $isAbsolute = static function (string $path): bool {

                    // Windows drive letter, e.g., C:\...
                if (preg_match('~^[a-zA-Z]:[/\\\\]~', $path) === 1) {
                    return true;
                }
                    // Windows UNC path, e.g., \\server\share\...
                if (preg_match('~^[/\\\\]{2}~', $path) === 1) {
                    return true;
                }
                    // Unix absolute
                    return str_starts_with($path, '/');
            };
            if (!$isAbsolute($layoutPath)) {
                if (str_starts_with($layoutPath, './') || str_starts_with($layoutPath, '../') || str_starts_with($layoutPath, '.\\') || str_starts_with($layoutPath, '..\\')) {
                    $layoutPath = dirname($viewPath) . DIRECTORY_SEPARATOR . $layoutPath;
                } else {
                    $layoutPath = $basePath . ltrim($layoutPath, '/\\');
                }
            }

                // Append .php if not present
            if (substr($layoutPath, -4) !== '.php') {
                $layoutPath .= '.php';
            }

                // Normalize directory separators for portability
                $layoutPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $layoutPath);
// Try to resolve to a canonical path if the file exists, otherwise keep as constructed
                // On some Windows environments, realpath may fail with relative parent components if not absolute enough
                $resolvedLayoutPath = realpath($layoutPath);
// If realpath failed, try manually resolving .. for the check
            if (!$resolvedLayoutPath) {
                $resolvedLayoutPath = $layoutPath;
// Resolve "any/path/../" patterns
                while (preg_match('#[^' . preg_quote(DIRECTORY_SEPARATOR, '#') . ']+' . preg_quote(DIRECTORY_SEPARATOR, '#') . '\.\.(' . preg_quote(DIRECTORY_SEPARATOR, '#') . '|$)#', $resolvedLayoutPath)) {
                    $resolvedLayoutPath = preg_replace('#[^' . preg_quote(DIRECTORY_SEPARATOR, '#') . ']+' . preg_quote(DIRECTORY_SEPARATOR, '#') . '\.\.(' . preg_quote(DIRECTORY_SEPARATOR, '#') . '|$)#', '', $resolvedLayoutPath, 1);
                }
                // Clean up trailing slash if resolution emptied a segment
                $resolvedLayoutPath = rtrim($resolvedLayoutPath, DIRECTORY_SEPARATOR);
            }

            if (!file_exists($resolvedLayoutPath)) {
                http_response_code(500);
                echo "Layout not found: {$layoutPath} (Resolved: {$resolvedLayoutPath})";
                return;
            }

                // Include the layout in the same scope so it can access $sections and any view variables
                // Use the resolved path for inclusion to ensure it's absolute and correct
                include $resolvedLayoutPath;
                return;
        }

            // No layout requested: behave as before
            echo $childOutput;
    }

    /**
     * Issue a simple HTTP redirect (302 by default).
     * Returns a minimal Response with Location header for new pipeline.
     *
     * @param string $location Absolute or relative URL to redirect to.
     * @param int $status HTTP status code (usually 302 or 301).
     * @return \Ishmael\Core\Http\Response
     */
    protected function redirect(string $location, int $status = 302): \Ishmael\Core\Http\Response
    {
        // For pipeline-aware callers, return a Response object
        return (new \Ishmael\Core\Http\Response())
            ->setStatusCode($status)
            ->header('Location', $location);
    }

    /**
     * Alias for render().
     *
     * @param string $view
     * @param array<string,mixed> $vars
     */
    protected function view(string $view, array $vars = []): void
    {
        $this->render($view, $vars);
    }

    /**
     * Return JSON output (for API-style controllers).
     *
     * @param array<string,mixed> $payload Data to encode as JSON.
     * @param int $status HTTP status code.
     */
    protected function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($payload, JSON_PRETTY_PRINT);
    }

    /**
     * Automatically determine module name based on namespace.
     *
     * @return string Module name (e.g., "App" or first segment under Modules\\).
     */
    protected function getModuleName(): string
    {
        $class = static::class;
        if (preg_match('/^Modules\\\\([^\\\\]+)/', $class, $matches)) {
            return $matches[1];
        }
        return 'App';
    }
}
