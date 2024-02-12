<?php

namespace Xel\Async\Router;
use DI\Container;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Psr\Http\Message\ServerRequestInterface;
use Swoole\Http\Response;
use Xel\Async\Middleware\Runner;
use Xel\Async\test\Service\Auth;
use Xel\Psr7bridge\PsrFactory;
use Xel\Async\Http\Response as XelResponse;
use function FastRoute\simpleDispatcher;

class Main
{
    private array $dispatch;

    public function __construct(
        private readonly Container  $register,
        private readonly PsrFactory $psrFactory,
    )
    {}

    private function responseInterface(): XelResponse
    {
        /** @var XelResponse $instance */
        clone  $instance =  $this->register->get('ResponseInterface');
        return $instance($this->register);
    }

    private function routerRunner(): RouterRunner
    {
        /** @var RouterRunner $runner */
        $runner = $this->register->get("RouterRunner");
        return $runner;
    }

    private function globalMiddleware(): array
    {
        /** @var array $globalMiddleware */
        $globalMiddleware = $this->register->get("GlobalMiddleware");
        return $globalMiddleware;
    }

    public function routerMapper(array $loader, string $method, string $uri): static
    {
        $router = simpleDispatcher(function (RouteCollector $routeCollector) use ($loader){
            $routeCollector->addGroup('/api', function (RouteCollector $r) use ($loader){
                foreach ($loader as $item){
                    $class = $item['Class'];
                    $method = $item['Method'];
                    $r->addRoute($item['RequestMethod'], $item['Uri'], [$class, $method, $item["Middlewares"]]);
                }
            });
        });

        $this->dispatch = $router->dispatch($method, $uri);
        return $this;
    }

    public function execute(ServerRequestInterface $request, Response $response): void
    {
        switch ($this->dispatch[0]) {
            case Dispatcher::NOT_FOUND:
                $response->status('404', "NOT FOUND");
                break;
            case Dispatcher::METHOD_NOT_ALLOWED:
                $response->status('405', "NOT ALLOWED");
                break;
            case Dispatcher::FOUND:
                // ? Router Dispatch
                $res = $this->routerRunner()(
                    $request,
                    $this->responseInterface(),
                    $this->dispatch
                );

                // ? execute middleware stack
                $middlewares = $this->dispatch[1][2];
                $mergeMiddleware = array_merge($this->globalMiddleware(),$middlewares);

                $data = new Runner($mergeMiddleware, $res);

                // ? execute router when already run stack of middleware
                $responses = $data->handle($request);
                $this->routerRunner()->exec($this->psrFactory, $response, $responses);
        }
    }

}