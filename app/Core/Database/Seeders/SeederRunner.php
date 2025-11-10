<?php
declare(strict_types=1);

namespace Ishmael\Core\Database\Seeders;

use Ishmael\Core\DatabaseAdapters\DatabaseAdapterInterface;
use Psr\Log\LoggerInterface;

/**
 * Class SeederRunner
 *
 * Discovers and executes module-first seeders with deterministic ordering and environment guards.
 *
 * Filesystem layout per module:
 *   Modules/<Module>/Database/Seeders/
 *
 * Conventions:
 * - Seeders implement SeederInterface (or extend BaseSeeder) and define run().
 * - Optional dependsOn(): string[] of class names; runner performs topological sort with stable ties.
 * - Optional module entrypoint: DatabaseSeeder class as an orchestrator root; otherwise all seeders run.
 *
 * Environment guard:
 * - By default runs only in development and test environments. Pass $force = true to override.
 *
 * Public API:
 *  seed(?string $module = null, ?string $class = null, bool $refresh = false, ?string $env = null, bool $force = false): void
 */
final class SeederRunner
{
    private DatabaseAdapterInterface $adapter;
    private LoggerInterface $logger;

    /**
     * Build correlation context (request/process ID) when available.
     * @return array<string,mixed>
     */
    private function correlationContext(): array
    {
        $rid = null;
        if (function_exists('app')) {
            try { $rid = app('request_id'); } catch (\Throwable $_) { $rid = null; }
        }
        if (!is_string($rid) || $rid === '') {
            $pid = function_exists('getmypid') ? @getmypid() : null;
            return $pid ? ['request_id' => (string)$pid] : [];
        }
        return ['request_id' => $rid];
    }

    /**
     * Construct a SeederRunner.
     *
     * @param DatabaseAdapterInterface $adapter Active database adapter (connected)
     * @param LoggerInterface|null $logger Optional PSR-3 logger; will default to app logger or NullChannel
     */
    public function __construct(DatabaseAdapterInterface $adapter, ?LoggerInterface $logger = null)
    {
        $this->adapter = $adapter;
        if ($logger) {
            $this->logger = $logger;
        } else {
            $resolved = null;
            if (function_exists('app')) {
                $resolved = app(\Psr\Log\LoggerInterface::class) ?? app('logger');
            }
            $this->logger = $resolved instanceof LoggerInterface ? $resolved : new \Ishmael\Core\Log\NullChannel();
        }
    }

    /**
     * Run seeders with optional module and class filters.
     *
     * When $module is null, runs all discovered modules. If $class is provided, only that seeder
     * (and its transitive dependencies) will be executed. When $refresh is true, the runner does not
     * change behavior other than logging intent; idempotence is expected to be handled by seeders themselves.
     *
     * @param string|null $module Module name to target, or null for all modules.
     * @param string|null $class Specific seeder class name (FQCN or short name) to run; includes dependencies.
     * @param bool $refresh Whether to hint a refresh run (no bookkeeping; provided for symmetry and tooling).
     * @param string|null $env Current environment; defaults to env('APP_ENV', 'development').
     * @param bool $force Set true to override environment guard (allow seeding in production).
     * @return void
     */
    public function seed(?string $module = null, ?string $class = null, bool $refresh = false, ?string $env = null, bool $force = false): void
    {
        $envName = $env ?? (function_exists('env') ? (string)env('APP_ENV', 'development') : 'development');
        $allowed = ['dev', 'development', 'test', 'testing', 'local'];
        if (!$force && !in_array(strtolower($envName), $allowed, true)) {
            throw new \RuntimeException("Seeding is disabled in '{$envName}' environment. Pass force=true to override.");
        }

        $modules = $module ? [$module] : $this->discoverModules();
        $this->logger->info('Starting seeding', [
            'modules' => $modules,
            'class' => $class,
            'refresh' => $refresh,
            'env' => $envName,
            'forced' => $force,
        ] + $this->correlationContext());

        $total = 0;
        foreach ($modules as $mod) {
            $plan = $this->buildExecutionPlan($mod, $class);
            if (empty($plan)) {
                $this->logger->info('No seeders to run', ['module' => $mod] + $this->correlationContext());
                continue;
            }
            $this->logger->info('Seeder plan', ['module' => $mod, 'count' => count($plan), 'seeders' => array_map(fn($c) => $c['name'], $plan)] + $this->correlationContext());
            foreach ($plan as $entry) {
                /** @var SeederInterface $instance */
                $instance = $entry['instance'];
                $ctx = ['module' => $mod, 'seeder' => $entry['name']] + $this->correlationContext();
                $this->logger->info('Running seeder', $ctx);
                try {
                    $instance->run($this->adapter, $this->logger);
                    $this->logger->info('Finished seeder', $ctx);
                    $total++;
                } catch (\Throwable $e) {
                    $this->logger->error('Seeder failed', $ctx + [
                        'error' => $e->getMessage(),
                        'exception' => get_class($e),
                    ]);
                    throw $e;
                }
            }
        }

        $this->logger->info('Finished seeding', ['total_seeders_run' => $total] + $this->correlationContext());
    }

    // ----- discovery and planning -----

    /**
     * Discover modules present under known roots.
     *
     * @return string[] Module names sorted alphabetically
     */
    private function discoverModules(): array
    {
        $roots = [base_path('Modules'), base_path('SkeletonApp/Modules')];
        $mods = [];
        foreach ($roots as $root) {
            if (!is_dir($root)) { continue; }
            foreach (scandir($root) ?: [] as $entry) {
                if ($entry === '.' || $entry === '..') { continue; }
                if (is_dir($root . DIRECTORY_SEPARATOR . $entry)) {
                    $mods[$entry] = true;
                }
            }
        }
        ksort($mods);
        return array_keys($mods);
    }

    /**
     * Build execution plan for a module with dependency ordering.
     *
     * @param string $module Module name
     * @param string|null $class Optional target seeder class (FQCN or short name)
     * @return array<int,array{name:string,instance:SeederInterface}>
     */
    private function buildExecutionPlan(string $module, ?string $class): array
    {
        $seeders = $this->discoverSeeders($module);
        if (empty($seeders)) { return []; }

        // Normalize names and dependencies
        $nameMap = []; // short name => FQCN
        foreach ($seeders as $fqcn => $obj) {
            $pos = strrpos($fqcn, '\\');
            $short = ($pos !== false) ? substr($fqcn, $pos + 1) : $fqcn;
            $nameMap[$short] = $fqcn;
        }

        // Target set
        $targets = array_keys($seeders);
        if ($class !== null) {
            $want = isset($seeders[$class]) ? $class : ($nameMap[$class] ?? null);
            if (!$want) { return []; }
            $targets = $this->collectWithDependencies($want, $seeders);
        } else {
            // If DatabaseSeeder exists, use it as the root to collect with deps; otherwise run all
            $dbSeeder = $nameMap['DatabaseSeeder'] ?? null;
            if ($dbSeeder) {
                $targets = $this->collectWithDependencies($dbSeeder, $seeders);
            }
        }

        // Topological sort
        $ordered = $this->topologicalOrder($seeders, $targets);
        $plan = [];
        foreach ($ordered as $fqcn) {
            $plan[] = ['name' => $fqcn, 'instance' => $seeders[$fqcn]];
        }
        return $plan;
    }

    /**
     * Discover seeder classes for a module by loading PHP files and instantiating classes implementing SeederInterface.
     *
     * @param string $module Module name
     * @return array<string, SeederInterface> Map of FQCN => instance
     */
    private function discoverSeeders(string $module): array
    {
        $dirs = [
            base_path('Modules/' . $module . '/Database/Seeders'),
            base_path('SkeletonApp/Modules/' . $module . '/Database/Seeders'),
        ];
        $declaredBefore = get_declared_classes();
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) { continue; }
            $files = scandir($dir) ?: [];
            sort($files, SORT_STRING);
            foreach ($files as $f) {
                if ($f === '.' || $f === '..') { continue; }
                if (str_ends_with($f, '.php')) {
                    require_once $dir . DIRECTORY_SEPARATOR . $f;
                }
            }
        }
        $declaredAfter = get_declared_classes();
        $diff = array_values(array_diff($declaredAfter, $declaredBefore));

        // Normalize module directories for robust Windows/Unix path comparison
        $normDirs = [];
        foreach ($dirs as $d) {
            $rp = realpath($d) ?: $d;
            $rp = rtrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $rp), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            $normDirs[] = $rp;
        }

        $out = [];
        // Consider all declared classes so repeated runs (require_once no-ops) still discover seeders
        foreach ($declaredAfter as $class) {
            try {
                $ref = new \ReflectionClass($class);
            } catch (\Throwable $_) {
                continue; // skip bogus
            }
            // Verify class file is inside one of the module seeder directories
            $file = $ref->getFileName() ?: '';
            $filePath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, (string)$file);
            $isInModule = false;
            foreach ($normDirs as $nd) {
                if ($filePath !== '' && str_starts_with($filePath, $nd)) { $isInModule = true; break; }
            }
            if (!$isInModule || $ref->isAbstract()) { continue; }

            $implements = class_implements($class) ?: [];
            $implementsInterface = is_subclass_of($class, SeederInterface::class) || in_array(SeederInterface::class, $implements, true);

            if ($implementsInterface) {
                // Standard: implements SeederInterface
                /** @var SeederInterface $obj */
                $obj = $ref->newInstanceWithoutConstructor();
                $out[$class] = $obj;
                continue;
            }

            // Back-compat: allow simple seeders exposing a public run() method without interface
            if ($ref->hasMethod('run')) {
                $m = $ref->getMethod('run');
                if ($m->isPublic()) {
                    $paramCount = $m->getNumberOfRequiredParameters();
                    // Support 0-arg or 2-arg (adapter, logger) methods
                    if ($paramCount === 0 || $paramCount === 2) {
                        $instance = $ref->newInstanceWithoutConstructor();
                        // Wrap in anonymous adapter implementing SeederInterface
                        $adapterWrapper = new class($instance, $paramCount) implements SeederInterface {
                            private object $inner;
                            private int $arity;
                            public function __construct(object $inner, int $arity) { $this->inner = $inner; $this->arity = $arity; }
                            public function run(\Ishmael\Core\DatabaseAdapters\DatabaseAdapterInterface $adapter, \Psr\Log\LoggerInterface $logger): void
                            {
                                if ($this->arity === 2) {
                                    $this->inner->run($adapter, $logger);
                                } else {
                                    $this->inner->run();
                                }
                            }
                            public function dependsOn(): array { return []; }
                        };
                        $out[$class] = $adapterWrapper;
                    }
                }
            }
        }
        ksort($out, SORT_STRING);
        return $out;
    }

    /**
     * Collect target class and all transitive dependencies.
     *
     * @param string $root Root FQCN
     * @param array<string, SeederInterface> $seeders
     * @return string[] FQCN list (unique)
     */
    private function collectWithDependencies(string $root, array $seeders): array
    {
        $seen = [];
        $stack = [$root];
        while ($stack) {
            $curr = array_pop($stack);
            if (isset($seen[$curr]) || !isset($seeders[$curr])) { continue; }
            $seen[$curr] = true;
            $deps = $this->normalizeDeps($seeders, $curr, array_keys($seeders));
            foreach (array_reverse($deps) as $d) { // reverse to maintain a bit of declaration order
                if (!isset($seen[$d])) { $stack[] = $d; }
            }
        }
        $keys = array_keys($seen);
        sort($keys, SORT_STRING);
        return $keys;
    }

    /**
     * Topologically order the selected seeders by their dependencies; ties broken alphabetically for determinism.
     *
     * @param array<string, SeederInterface> $seeders All discovered seeders
     * @param string[] $targets Selected FQCNs to run
     * @return string[] Ordered FQCNs
     */
    private function topologicalOrder(array $seeders, array $targets): array
    {
        $inTarget = array_fill_keys($targets, true);
        $depsMap = [];
        foreach ($targets as $fqcn) {
            $depsMap[$fqcn] = array_values(array_filter(
                $this->normalizeDeps($seeders, $fqcn, $targets),
                fn($d) => isset($inTarget[$d])
            ));
        }
        $result = [];
        $tempMark = [];
        $permMark = [];
        $visit = function(string $n) use (&$visit, &$result, &$tempMark, &$permMark, $depsMap): void {
            if (isset($permMark[$n])) { return; }
            if (isset($tempMark[$n])) { throw new \RuntimeException('Cyclic seeder dependency detected at ' . $n); }
            $tempMark[$n] = true;
            $deps = $depsMap[$n] ?? [];
            sort($deps, SORT_STRING);
            foreach ($deps as $m) { $visit($m); }
            $permMark[$n] = true; unset($tempMark[$n]);
            $result[] = $n;
        };
        $nodes = $targets;
        sort($nodes, SORT_STRING);
        foreach ($nodes as $n) { $visit($n); }
        // dedupe while preserving order
        $seen = [];
        $ordered = [];
        foreach ($result as $n) { if (!isset($seen[$n])) { $seen[$n] = true; $ordered[] = $n; } }
        return $ordered;
    }

    /**
     * Normalize dependencies for a given seeder, mapping short names to FQCNs.
     *
     * @param array<string, SeederInterface> $seeders
     * @param string $fqcn
     * @param string[] $universe Allowed names (FQCNs) for resolution
     * @return string[] FQCN dependencies
     */
    private function normalizeDeps(array $seeders, string $fqcn, array $universe): array
    {
        $shortMap = [];
        foreach (array_keys($seeders) as $k) {
            $pos = strrpos($k, '\\');
            $short = ($pos !== false) ? substr($k, $pos + 1) : $k;
            $shortMap[$short] = $k;
        }
        $declared = [];
        try { $declared = $seeders[$fqcn]->dependsOn(); } catch (\Throwable $_) { /* ignore */ }
        $resolved = [];
        foreach ($declared as $d) {
            $fq = $seeders[$d] ?? ($shortMap[$d] ?? null);
            if ($fq && in_array($fq, $universe, true)) { $resolved[] = $fq; }
        }
        sort($resolved, SORT_STRING);
        return $resolved;
    }
}
