<?php

namespace Xel\Async\Contract;

use Swoole\Http\Request;

interface RequestHandlerInterfaces
{
    public function handle(Request $request): void;
}

