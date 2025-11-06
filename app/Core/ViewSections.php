<?php
declare(strict_types=1);

namespace Ishmael\Core;

/**
 * ViewSections
 * ------------
 * A minimal, dependency-free helper for defining and rendering layout sections
 * in plain PHP view files. Designed to be opt-in and framework-agnostic.
 *
 * Usage inside a child view:
 *   $layoutFile = 'layout'; // opt-in layout relative to Views/
 *   $sections->start('title');
 *   echo 'Posts';
 *   $sections->end();
 *
 *   $sections->start('content');
 *   echo '<h1>All Posts</h1>';
 *   $sections->end();
 *
 * Usage inside a layout view (layout.php):
 *   <!doctype html>
 *   <html>
 *     <head><title><?= $sections->yield('title', 'Untitled') ?></title></head>
 *     <body>
 *       <?= $sections->yield('content') ?>
 *     </body>
 *   </html>
 *
 * Notes:
 * - Sections can be defined in any order; later definitions overwrite by default.
 * - The helper is stateful per render() call and is not a global.
 */
final class ViewSections
{
    /**
     * @var array<string,string> Accumulated named sections
     */
    private array $sections = [];

    /**
     * @var string[] Stack of section names being captured (supports nesting)
     */
    private array $captureStack = [];

    /**
     * Begin capturing output for a section with the given name.
     *
     * @param string $name Section name (e.g., "title", "content").
     * @return void
     */
    public function start(string $name): void
    {
        $this->captureStack[] = $name;
        ob_start();
    }

    /**
     * End the most recently started section capture and store its contents.
     *
     * @return void
     */
    public function end(): void
    {
        if (empty($this->captureStack)) {
            // Nothing to end; safely ignore to keep behavior minimal and predictable
            return;
        }

        $content = (string) ob_get_clean();
        $name = array_pop($this->captureStack);
        $this->sections[$name] = $content;
    }

    /**
     * Render (return) a section's contents if defined, else a default value.
     *
     * @param string $name Section name to render.
     * @param string $default Default content to return if the section is absent.
     * @return string The section content or the default.
     */
    public function yield(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    /**
     * Determine whether a section has been defined.
     *
     * @param string $name Section name to check.
     * @return bool True if the section exists; otherwise false.
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->sections);
    }

    /**
     * Set a section's contents programmatically.
     *
     * @param string $name Section name.
     * @param string $content Content to assign to the section.
     * @param bool $overwrite Whether to overwrite an existing section (default true).
     * @return void
     */
    public function set(string $name, string $content, bool $overwrite = true): void
    {
        if (!$overwrite && $this->has($name)) {
            return;
        }
        $this->sections[$name] = $content;
    }
}
