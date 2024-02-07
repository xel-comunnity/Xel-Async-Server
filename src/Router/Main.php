<?php

namespace Xel\Async\Router;
use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use FastRoute\Dispatcher;
use FastRoute\Dispatcher\Result\Matched;
use FastRoute\Dispatcher\Result\MethodNotAllowed;
use FastRoute\Dispatcher\Result\NotMatched;
use FastRoute\RouteCollector;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Swoole\Http\Response;
use Xel\Psr7bridge\PsrFactory;
use Xel\Async\Http\Response as XelResponse;
use function FastRoute\simpleDispatcher;

class Main
{
    private Matched|MethodNotAllowed|NotMatched $dispatch;

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

    public function routerMapper(array $loader, string $method, string $uri): static
    {
        $dispatch = simpleDispatcher(function (RouteCollector $routeCollector) use ($loader){
            $routeCollector->addGroup('/api', function (RouteCollector $r) use ($loader){
                foreach ($loader as $item){
                    $class = $item['Class'];
                    $method = $item['Method'];
                    $r->addRoute($item['RequestMethod'], $item['Uri'], [$class, $method]);
                }
            });
        });

        $this->dispatch = $dispatch->dispatch($method, $uri);
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
                $this->createRouterInstance($request, $response);
        }
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    private function createRouterInstance(ServerRequestInterface $request, Response $response): void
    {
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
        $instance = new $class();
        $object = [$instance, $method];

        /**
         * Injecting Request and Response Interface
         */
        $instance->setRequest($request);
        $instance->setResponse($this->responseInterface());

        // ? Inject response as param to handle return value
        foreach ($vars as $value) {
            $param[] = $value;
        }

        // ? Ensure that $instance is an object before calling the method
        /** @var callable $object */
        $responseData = call_user_func_array($object, $param);
        $this->psrFactory->connectResponse($responseData, $response);
    }
}