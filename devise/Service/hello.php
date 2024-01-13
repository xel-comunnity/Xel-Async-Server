<?php

namespace Xel\Devise\Service;

use Xel\Async\Router\Attribute\Router;

class hello
{
    #[Router('GET', '/hello')]
    public function index(): void
    {
        echo "hello";
    }
}