<?php

namespace Xel\Async\Router\Attribute;
use Attribute;

#[Attribute(Attribute::TARGET_METHOD|Attribute::IS_REPEATABLE)]
class PATCH extends Router
{
    public function __construct(string $path, array $middleware = [])
    {
        parent::__construct("PATCH", $path, $middleware);
    }
}