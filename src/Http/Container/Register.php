<?php

namespace Xel\Async\Http\Container;
use RuntimeException;
class Register
{
    private array $bindings = [];
    private array $instances = [];

    public function register(string $abstract, $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
    }

    public function resolve(string $abstract)
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if (isset($this->bindings[$abstract])) {
            $concrete = $this->bindings[$abstract];

            // Check if the concrete is a class name
            if (is_string($concrete) && class_exists($concrete)) {
                $instance = new $concrete();
            } elseif (is_callable($concrete)) {
                $instance = call_user_func($concrete);
            } else {
                throw new RuntimeException("Invalid concrete type for dependency: $abstract");
            }

            $this->instances[$abstract] = $instance;
            return $instance;
        }

        throw new RuntimeException("Unresolved dependency: $abstract");
    }

    public function get(string $abstract)
    {
        return $this->resolve($abstract);
    }
}