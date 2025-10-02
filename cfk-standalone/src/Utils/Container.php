<?php

declare(strict_types=1);

namespace CFK\Utils;

use ReflectionClass;
use ReflectionParameter;
use InvalidArgumentException;

/**
 * Simple dependency injection container
 */
class Container
{
    private array $bindings = [];
    private array $instances = [];

    /**
     * Bind a service to the container
     */
    public function bind(string $abstract, callable|string $concrete = null): void
    {
        $this->bindings[$abstract] = $concrete ?? $abstract;
    }

    /**
     * Bind a singleton service
     */
    public function singleton(string $abstract, callable|string $concrete = null): void
    {
        $this->bind($abstract, $concrete);
        $this->instances[$abstract] = null; // Mark as singleton
    }

    /**
     * Resolve a service from the container
     */
    public function make(string $abstract): object
    {
        // Return existing singleton instance
        if (array_key_exists($abstract, $this->instances) && $this->instances[$abstract] !== null) {
            return $this->instances[$abstract];
        }

        $concrete = $this->bindings[$abstract] ?? $abstract;

        // Handle callable binding
        if (is_callable($concrete)) {
            $instance = $concrete($this);
        } else {
            $instance = $this->build($concrete);
        }

        // Store singleton instance
        if (array_key_exists($abstract, $this->instances)) {
            $this->instances[$abstract] = $instance;
        }

        return $instance;
    }

    /**
     * Build a class instance with dependency injection
     */
    private function build(string $className): object
    {
        if (!class_exists($className)) {
            throw new InvalidArgumentException("Class {$className} does not exist");
        }

        $reflection = new ReflectionClass($className);

        if (!$reflection->isInstantiable()) {
            throw new InvalidArgumentException("Class {$className} is not instantiable");
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return new $className();
        }

        $parameters = $constructor->getParameters();
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $dependencies[] = $this->resolveDependency($parameter);
        }

        return $reflection->newInstanceArgs($dependencies);
    }

    /**
     * Resolve a constructor parameter dependency
     */
    private function resolveDependency(ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();

        if ($type === null || $type->isBuiltin()) {
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }

            throw new InvalidArgumentException("Cannot resolve parameter {$parameter->getName()}");
        }

        $className = $type->getName();
        return $this->make($className);
    }

    /**
     * Check if a service is bound
     */
    public function bound(string $abstract): bool
    {
        return isset($this->bindings[$abstract]);
    }

    /**
     * Get all bindings
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }
}