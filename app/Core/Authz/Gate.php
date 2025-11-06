<?php
declare(strict_types=1);

namespace Ishmael\Core\Authz;

use Ishmael\Core\Auth\AuthManager;

/**
 * Gate provides a minimal authorization API to define abilities and
 * evaluate them against the current user and an optional resource.
 */
final class Gate
{
    /** @var array<string, callable> */
    private array $abilities = [];
    /** @var array<string, class-string> Map resource class => policy class */
    private array $policies = [];

    public function __construct()
    {
        // Load policy map from config if present
        $map = (array) (\config('auth.policies') ?? []);
        foreach ($map as $resource => $policy) {
            if (is_string($resource) && is_string($policy)) {
                $this->policies[$resource] = $policy;
            }
        }
    }

    /**
     * Define a new ability using a callback: fn(?array $user, mixed $resource): bool
     *
     * @param string $ability
     * @param callable $callback
     */
    public function define(string $ability, callable $callback): void
    {
        $this->abilities[$ability] = $callback;
    }

    /**
     * Determine if the given ability is allowed for the current user and resource.
     */
    public function allows(string $ability, mixed $resource = null): bool
    {
        $user = $this->currentUser();
        // 1) Explicit ability definitions win
        if (isset($this->abilities[$ability])) {
            return (bool) ($this->abilities[$ability])($user, $resource);
        }
        // 2) Policy resolution based on resource type
        if ($resource !== null) {
            $policy = $this->resolvePolicy($resource);
            if ($policy !== null && method_exists($policy, $ability)) {
                return (bool) $policy->{$ability}($user, $resource);
            }
        }
        // Default deny
        return false;
    }

    /** Shortcut for !allows(). */
    public function denies(string $ability, mixed $resource = null): bool
    {
        return !$this->allows($ability, $resource);
    }

    /** Authorize or throw AuthorizationException. */
    public function authorize(string $ability, mixed $resource = null, string $message = 'Forbidden'): void
    {
        if ($this->denies($ability, $resource)) {
            throw new AuthorizationException($ability, $resource, $message);
        }
    }

    /** Resolve a policy instance for a resource by class map or instanceof chain. */
    private function resolvePolicy(mixed $resource): object|null
    {
        $class = is_object($resource) ? $resource::class : (is_string($resource) ? $resource : null);
        if ($class === null) {
            return null;
        }
        // Direct map
        if (isset($this->policies[$class]) && class_exists($this->policies[$class])) {
            $p = $this->policies[$class];
            return new $p();
        }
        // Try parent class/interface matches
        foreach ($this->policies as $res => $policy) {
            if (class_exists($res) || interface_exists($res)) {
                if (is_a($class, $res, true) && class_exists($policy)) {
                    $p = $policy;
                    return new $p();
                }
            }
        }
        // Convention-based: If resource is a class Foo, try FooPolicy in same namespace
        if (class_exists($class)) {
            $ns = substr($class, 0, strrpos($class, '\\')) ?: '';
            $short = substr($class, strrpos($class, '\\') + 1);
            $candidate = ($ns ? $ns . '\\' : '') . $short . 'Policy';
            if (class_exists($candidate)) { return new $candidate(); }
        }
        return null;
    }

    /** @return array<string,mixed>|null */
    private function currentUser(): ?array
    {
        /** @var AuthManager|null $auth */
        $auth = \app('auth');
        if ($auth instanceof AuthManager) {
            $id = $auth->id();
            if ($id === null) {
                return null;
            }
            // Return a lightweight user shape without triggering a provider/DB call
            return ['id' => $id];
        }
        return null;
    }
}
