<?php

namespace Xel\Async\Http;
use ReflectionException;
use SensitiveParameter;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Xel\Async\Http\Server\Servers;
use Xel\Async\Router\Main;
use Imefisto\PsrSwoole\Request as PsrRequest;
use Imefisto\PsrSwoole\ResponseMerger;
use Nyholm\Psr7\Factory\Psr17Factory;

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

        /**
         * Instance of PSR17 Http interface
         */
        $uriFactory = new Psr17Factory();
        $streamFactory = new Psr17Factory();
        $responseFactory = new Psr17Factory();
        $responseMerger = new ResponseMerger();

        static::$instance->instance
            ->on('request', function (Request $request, Response $response)
            use (
                $uriFactory,
                $streamFactory,
                $responseFactory,
                $responseMerger
            )
            {

            /**
             * create psr request from swoole request
             */
            $psrRequest = new PsrRequest(
                $request,
                $uriFactory,
                $streamFactory
            );

            /**
             * create response factory
             */

            $psrResponse = $responseFactory->createResponse();

            // ? Handle the CORS preflight request (OPTIONS request)
            if ($request->server['request_method'] === 'OPTIONS') {
                $psrResponse->withStatus(200);
                $responseMerger->toSwoole(
                  $psrResponse,
                  $response
                );
                return;
            }

            // ? Load Router Dynamic router
            Main::load(
                Main::dispatch($psrRequest->getMethod(), $psrRequest->getUri()->getPath()),
                $psrResponse,
                $responseMerger,
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