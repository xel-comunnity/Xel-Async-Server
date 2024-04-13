<?php

namespace Xel\Async\Contract;

use Swoole\Http\Request;
use Swoole\Http\Response;

interface MiddlewareInterfaces
{
    public function process(Request $request, RequestHandlerInterfaces $handler, Response $response): void;

}


