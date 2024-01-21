<?php
namespace Xel\Async\Http;

use HttpSoft\Message\ServerRequestFactory;
use HttpSoft\Message\StreamFactory;
use HttpSoft\Message\UploadedFileFactory;
use HttpSoft\Message\ResponseFactory;
use ReflectionException;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Xel\Async\Http\Server\Servers;
use Xel\Async\Router\Main;
use Xel\Psr7bridge\PsrFactory;

class Applications
{
    private Servers $instance;

    /**
     * @param array<string, mixed> $config
     * @return self
     */
    public function initialize(array $config): self
    {
        $this->instance = new Servers($config);
        return $this;
    }

    /**
     * @param array<string, mixed> $loader
     * @return self
     * @throws ReflectionException
     */
    public function onEvent(array $loader): self
    {
        // ? initial loader for dynamic router
        $routerApp =  (new Main())($loader);

        // ? initial Psr Bridge for Http Request & Response
        $psrRequestBridge = new PsrFactory(
            new ServerRequestFactory(),
            new StreamFactory(),
            new UploadedFileFactory()
        );

        $psrResponseBride = new ResponseFactory();

        $psrStreamBride = new StreamFactory();

        $this->instance->instance
            ->on('request', function
            (
                SwooleRequest $request,
                SwooleResponse $response
            ) use
            (
                $routerApp,
                $psrResponseBride,
                $psrRequestBridge,
                $psrStreamBride
            ) {

            // ? Bridge Swoole Request
            $bridgeRequest = $psrRequestBridge->connectRequest($request);

            // ? Server Loader
            $routerApp->load(
                $routerApp->dispatch($bridgeRequest->getMethod(), $bridgeRequest->getUri()->getPath()),
                $psrRequestBridge,
                $psrStreamBride,
                $bridgeRequest,
                $psrResponseBride,
                $response
            );
       });

        return $this;
    }

    public function run(): void
    {
        $this->instance->launch();
    }
}