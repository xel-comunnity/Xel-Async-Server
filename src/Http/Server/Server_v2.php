<?php

namespace Xel\Async\Http\Server;

use Swoole\Http\Server;
use Xel\Async\Contract\ServerInterface;

class Server_v2 implements ServerInterface
{
    private static ?Server $httpServer = null;
    public static function init(array $config): void
    {
        // ? Get server configuration
        $mode = $config['api_server']['mode'];
        $host = $config['api_server']['host'];
        $port = $config['api_server']['port'];

        static::$httpServer = ($mode == 1)
            ? new Server($host, $port, $mode)
            : new Server($host, $port, $mode, SWOOLE_SOCK_TCP);
        static::$httpServer->set($config['api_server']['options']);
    }

    public static function getServer(): ?Server
    {
        return static::$httpServer;
    }
}