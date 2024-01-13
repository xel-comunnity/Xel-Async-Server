<?php

namespace Xel\Async\Router\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Router
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