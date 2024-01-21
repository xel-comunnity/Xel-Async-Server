<?php

namespace Xel\Async\Http\Server;
use Swoole\Http\Server;

class Servers
{
    public Server $instance;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config)
    {
        // ? Initialize the server instance
        $this->initialize($config);

        // ? Set event handler for 'start'
        $this->instance->on('start', function () use ($config) {
            echo "Listening at " . $config['api_server']['host'] . ":" . $config['api_server']['port'] . " \n";
        });
    }

    /**
     * @param array<string, mixed> $config
     * @return void
     */
    private function initialize(array $config): void
    {
        // ? Get server configuration
        $mode = $config['api_server']['mode'];
        $host = $config['api_server']['host'];
        $port = $config['api_server']['port'];

        $this->instance = ($mode == 1)
            ? new Server($host, $port, $mode)
            : new Server($host, $port, $mode, SWOOLE_SOCK_TCP);

        $this->instance->set($config['api_server']['options']);
    }

    public function launch(): void
    {
        $this->instance->start();
    }
}