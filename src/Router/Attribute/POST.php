<?php

namespace Xel\Async\Router\Attribute;
use Attribute;

#[Attribute(Attribute::TARGET_METHOD|Attribute::IS_REPEATABLE)]
class POST extends Router
{
    public function __construct(string $path, array $middleware = [])
    {
        parent::__construct("POST", $path,$middleware);
    }
}