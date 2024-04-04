<?php

namespace Xel\Async\Contract;

use Swoole\Http\Server;

interface ServerInterface
{
    public static function init(array $config): void;

    public static function getServer(): ?Server;
}