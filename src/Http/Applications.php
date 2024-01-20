<?php

namespace Xel\Async\Http;
use ReflectionException;
use SensitiveParameter;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Xel\Async\Http\Server\Servers;
use Xel\Async\Router\Main;

class Applications
{
    private static ?Servers $instance;

    public static function initialize(#[SensitiveParameter] array $config): self
    {
        static::$instance = new Servers($config);

        return new self();
    }

    /**
     * @throws ReflectionException
     */
    public static function onEvent(array $loader): self
    {
        // ? initial loader for dynamic router
        Main::initialize($loader);

        static::$instance->instance
            ->on('request', function
            (
                Request $request,
                Response $response
            ) {

            // ? Server Loader
            Main::load(
                Main::dispatch($request->server["request_method"], $request->server["request_uri"]),
                $response
            );
       });

        return new self();
    }

    public static function run(): void
    {
        static::$instance->launch();
    }
}