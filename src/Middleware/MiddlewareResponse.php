<?php

namespace Xel\Async\Middleware;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;

class MiddlewareResponse
{
    public static function Response(): ResponseInterface
    {
        return (new Psr17Factory())->createResponse();
    }
}