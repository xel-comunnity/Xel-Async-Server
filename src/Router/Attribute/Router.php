<?php

namespace Xel\Async\Router\Attribute;

use Attribute;
use Xel\Async\Router\Attribute\Contract\RouteAPP;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Router implements RouteAPP
{
    public function __construct
    (
        private string $httpMethod,
        private string $path

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
}