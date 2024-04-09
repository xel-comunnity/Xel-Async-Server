<?php

namespace Xel\Async\Router;
use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Swoole\Http\Response;
use Xel\Async\Middleware\MiddlewareRunner;
use Xel\Psr7bridge\PsrFactory;
use Xel\Async\Http\Response as XelResponse;
use function FastRoute\simpleDispatcher;

class Main
{
    /**
     * @var array<int|string,mixed>
     */
    private array $dispatch;
    private Dispatcher $dispatcher;

    public function __construct(
        private readonly Container  $register,
        private readonly PsrFactory $psrFactory,
        private readonly array $loader,

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
     * @throws Exception
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
                [$class,$method] = $this->dispatch[1];
                $vars = $this->dispatch[2];

                $param = [];

                if (!class_exists($class)) {
                    throw new InvalidArgumentException('Invalid class name');
                }

                if (!method_exists($class, $method)) {
                    throw new InvalidArgumentException('Invalid method name');
                }

                // ? Create an instance of $class
                $instance = $this->register->make($class);
                $object = [$instance, $method];

                /**
                 * Injecting Request, Response Interface, Container
                 */
                if ($instance instanceof $abstractClass){
                    $instance->setRequest($request);
                    $instance->setResponse($this->responseInterface());
                    $instance->setContainer($this->register);
                }

                // ? Inject response as param to handle return value
                foreach ($vars as $value) {
                    $param[] = $value;
                }

                // ? execute middleware stack
                $middlewares = $this->dispatch[1][2];
                $mergeMiddleware = array_merge($this->globalMiddleware(), $middlewares);

                /***
                 * @var ResponseInterface $bindParam
                 */
                $bindParam = call_user_func_array($object, $param);
                $data = new MiddlewareRunner($mergeMiddleware, $bindParam);
                $responses = $data->handle($request);

                // ? merge Result of Response
                $this->psrFactory->connectResponse($responses, $response);
        }
    }


}