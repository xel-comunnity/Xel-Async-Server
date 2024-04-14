<?php

namespace Xel\Async\Router;

use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use InvalidArgumentException;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Server;
use Xel\Async\CentralManager\CentralManagerRunner;
use Xel\Async\Http\Responses;
use Xel\Async\Middleware\MiddlewareDispatcher;
use function FastRoute\simpleDispatcher;

class Main_v2
{
    /**
     * @var array<int|string,mixed>
     */
    private array $dispatch;
    private Dispatcher $dispatcher;

    public function __construct(
        private readonly Container  $register,
        private readonly array $loader,
        private readonly Server $server,
    )
    {}
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

    public function routerMapper(): static
    {
        $this->dispatcher = simpleDispatcher(function (RouteCollector $routeCollector){
            foreach ($this->loader as $item){
                $class = $item['Class'];
                $method = $item['Method'];
                $routeCollector->addRoute($item['RequestMethod'], $item['Uri'], [$class, $method, $item["Middlewares"]]);
            }
        });
        return $this;
    }

    public function dispatch(string $method, string $uri): static
    {
        $this->dispatch = $this
            ->dispatcher
            ->dispatch($method, $uri);
        return $this;
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function execute(Request $request, Response $response): void
    {
        switch ($this->dispatch[0]) {
            case Dispatcher::NOT_FOUND:
                $response->status('404', "NOT FOUND");
                break;
            case Dispatcher::METHOD_NOT_ALLOWED:
                $response->status('405', "NOT ALLOWED");
                break;
            case Dispatcher::FOUND:
                // ? check middleware if exists and launched as separated process
                $middleware = $this->middlewareMaker();
                $mergeMiddleware = array_merge($this->globalMiddleware(), $this->dispatch[1][2]);
                $middleware($mergeMiddleware, $request, $response)
                    ->addMiddleware();
                $middleware->handle($request);

                // ? process Dispatch router class which founded
                $jobMaker = $this->jobMaker()($this->server, $this->responseMaker()($response), $this->register);
                $instance = $this->instanceMaker($request, $jobMaker);
                $vars = $this->dispatch[2];

                // ? Inject response as param to handle return value
                $param = [];
                foreach ($vars as $value) {
                    $param[] = $value;
                }

                $bindParam = call_user_func_array($instance, $param);

                // ? merge Result of Response
                $response->end($bindParam);
        }
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    private function instanceMaker(Request $request, CentralManagerRunner $centralManagerRunner): array
    {
        $abstractClass = $this->register->get("AbstractService");
        [$class,$method] = $this->dispatch[1];

        if (!class_exists($class)) {
            throw new InvalidArgumentException('Invalid class name');
        }

        if (!method_exists($class, $method)) {
            throw new InvalidArgumentException('Invalid method name');
        }

        // ? Create an instance of $class
        $instance = new $class();
        $object = [$instance, $method];

        /**
         * Injecting Request, Response Interface, Container
         */
        if ($instance instanceof $abstractClass){
            $instance->serverRequest = $request;
            $instance->container = $this->register;
            $instance->jobDispatcherDispatcher = $centralManagerRunner;
        }

        return $object;
    }


    private function middlewareMaker(): MiddlewareDispatcher
    {
        return new MiddlewareDispatcher();
    }


    public function jobMaker(): CentralManagerRunner
    {
        return new CentralManagerRunner();
    }

    public function responseMaker(): Responses
    {
        return new Responses();
    }
}