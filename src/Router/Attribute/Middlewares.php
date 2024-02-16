<?php

namespace Xel\Async\Router\Attribute;
use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class Middlewares
{
    /**
     * @param class-string[] $classMiddleware
     */
    public function __construct
    (
        private readonly array $classMiddleware
    )
    {}

    /**
     * @return class-string[]
     */

    public function getMiddlewareClass(): array
    {
        return $this->classMiddleware;
    }
}