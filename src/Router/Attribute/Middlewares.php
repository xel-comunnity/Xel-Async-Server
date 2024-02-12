<?php

namespace Xel\Async\Router\Attribute;
use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class Middlewares
{
    public function __construct
    (
        private array $classMiddleware
    )
    {}

    public function getMiddlewareClass(): array
    {
        return $this->classMiddleware;
    }
}