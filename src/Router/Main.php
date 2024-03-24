<?php

namespace Xel\Async\Router;
use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Psr\Http\Message\ServerRequestInterface;
use Swoole\Http\Response;
use Xel\Async\Middleware\Runner;
use Xel\Psr7bridge\PsrFactory;
use Xel\Async\Http\Response as XelResponse;
use function FastRoute\simpleDispatcher;

class Main
{
    /**
     * @var array<int|string,mixed>
     */
    private array $dispatch;

    public function __construct(
        private readonly Container  $register,
        private readonly PsrFactory $psrFactory,
    )
    {}

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    private function responseInterface(): XelResponse
    {
        /**@var  XelResponse $instance */
        $instance =  $this->register->get('ResponseInterface');
        return $instance($this->register);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    private function routerRunner(): RouterRunner
    {
        /** @var RouterRunner $runner */
        $runner = $this->register->get("RouterRunner");
        return $runner;
    }

    /**
     * @return class-string[]
     * @throws DependencyException
     * @throws NotFoundException
     */
    private function globalMiddleware(): array
    {
        /** @var class-string[] $globalMiddleware */
        $globalMiddleware = $this->register->get("GlobalMiddleware");
        return $globalMiddleware;
    }

    /**
     * @param array<int|string, mixed> $loader
     * @param string $method
     * @param string $uri
     * @return $this
     */
    public function routerMapper(array $loader, string $method, string $uri): static
    {
        $router = simpleDispatcher(function (RouteCollector $routeCollector) use ($loader){
            foreach ($loader as $item){
                $class = $item['Class'];
                $method = $item['Method'];
                $routeCollector->addRoute($item['RequestMethod'], $item['Uri'], [$class, $method, $item["Middlewares"]]);
            }
        });

        $this->dispatch = $router->dispatch($method, $uri);
        return $this;
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
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
                $abstractClass = $this->register->get("AbstractService");

                // ? Router Dispatch
                $res = $this->routerRunner()(
                    $request,
                    $this->responseInterface(),
                    $abstractClass,
                    $this->dispatch,
                    $this->register,
                );

                // ? execute middleware stack
                $middlewares = $this->dispatch[1][2];
                $mergeMiddleware = array_merge($this->globalMiddleware(), $middlewares);

                $data = new Runner($mergeMiddleware, $res);

                // ? execute router when already run stack of middleware
                $responses = $data->handle($request);
                $this->routerRunner()->exec($this->psrFactory, $response, $responses);
        }
    }

}