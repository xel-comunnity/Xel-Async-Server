<?php

namespace Xel\Async\Router\Attribute;

use Attribute;
use Xel\Async\Router\Attribute\Contract\RouteAPP;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Router implements RouteAPP
{
    /**
     * @param string $httpMethod
     * @param string $path
     * @param class-string[] $middlewares
     */
    public function __construct
    (
        private readonly string $httpMethod,
        private readonly string $path,
        private readonly array $middlewares = []

    ){}

    public function getHttpMethod(): string
    {
        return $this->httpMethod;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getMethod(): string
    {
        return strtoupper($this->httpMethod);
    }

    /**
     * @return class-string[]
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }
}