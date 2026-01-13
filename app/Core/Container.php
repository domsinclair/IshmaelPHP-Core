<?php

declare(strict_types=1);

namespace Ishmael\Core;

use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * Minimal PSR-11 compliant DI container for Ishmael Framework.
 */
class Container implements ContainerInterface
{
    /** @var array<string, mixed> */
    private array $entries = [];

    /** @var array<string, object> */
    private array $instances = [];

    /**
     * Bind an abstract type to a concrete implementation or a closure.
     */
    public function bind(string $id, mixed $concrete = null): void
    {
        $this->entries[$id] = $concrete ?? $id;
        unset($this->instances[$id]);
    }

    /**
     * @inheritDoc
     */
    public function get(string $id): mixed
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (!$this->has($id)) {
            throw new RuntimeException("Entry not found: {$id}");
        }

        $concrete = $this->entries[$id];

        if ($concrete instanceof \Closure) {
            $instance = $concrete($this);
        } elseif (is_string($concrete) && class_exists($concrete)) {
            $instance = $this->resolve($concrete);
        } else {
            $instance = $concrete;
        }

        if (is_object($instance)) {
            $this->instances[$id] = $instance;
        }

        return $instance;
    }

    /**
     * @inheritDoc
     */
    public function has(string $id): bool
    {
        return isset($this->entries[$id]);
    }

    /**
     * Resolve a class using the container or autowiring if possible.
     */
    private function resolve(string $class): object
    {
        $reflection = new \ReflectionClass($class);

        if (!$reflection->isInstantiable()) {
            throw new RuntimeException("Class {$class} is not instantiable.");
        }

        $constructor = $reflection->getConstructor();

        if (!$constructor) {
            return new $class();
        }

        $parameters = $constructor->getParameters();
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            if (!$type || !($type instanceof \ReflectionNamedType) || $type->isBuiltin()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                    continue;
                }
                throw new RuntimeException("Cannot resolve parameter {$parameter->getName()} for {$class}");
            }

            $dependencies[] = $this->get($type->getName());
        }

        return $reflection->newInstanceArgs($dependencies);
    }
}
