<?php

namespace Xel\Async\Router;

use FastRoute\Dispatcher;
use FastRoute\Dispatcher\Result\Matched;
use FastRoute\Dispatcher\Result\MethodNotAllowed;
use FastRoute\Dispatcher\Result\NotMatched;
use FastRoute\RouteCollector;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use ReflectionException;
use Swoole\Http\Response as SwooleResponse;
use Swoole\Http\Request as SwooleRequest;
use Xel\Async\Router\Attribute\Extract\Extractor;
use function FastRoute\simpleDispatcher;

class Main_old
{
    /**
     * @var Dispatcher
     */
    private Dispatcher $dispatcher;

    /**
     * @var array<string|int|false, mixed>
     */
    private array $param = [];

    /**
     * @param array<string, mixed> $loader
     * @return MainOld
     */
    public function __invoke(array $loader): self
    {
      // ? inject to Loader
//      $inLoader = (new Extractor())->setLoader($loader);
      $this->dispatcher = simpleDispatcher(function (RouteCollector $routeCollector) use ($loader){
        $routeCollector->addGroup('/api', function (RouteCollector $r) use ($loader){
            foreach ($loader as $item){
                $class = $item['Class'];
                $method = $item['Method'];
                $r->addRoute($item['RequestMethod'], $item['Uri'], [$class, $method]);

            }
        });
      });
      return $this;
    }

    /**
     * @param string $method
     * @param string $uri
     * @return Dispatcher\Result\Matched|Dispatcher\Result\MethodNotAllowed|Dispatcher\Result\NotMatched
     */
    public function dispatch(string $method, string $uri): Dispatcher\Result\Matched|Dispatcher\Result\MethodNotAllowed|Dispatcher\Result\NotMatched
    {
        return $this->dispatcher->dispatch($method,$uri);
    }

    /**
     * @param Matched|NotMatched|MethodNotAllowed $routeInfo
     * @param SwooleRequest $request
     * @param SwooleResponse $response
     * @return void
     */
    public function load
    (
        Dispatcher\Result\Matched|Dispatcher\Result\NotMatched|Dispatcher\Result\MethodNotAllowed $routeInfo,
        SwooleRequest $request,
        SwooleResponse         $response
    ): void
    {
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                $response->status('404', "NOT FOUND");
                break;
            case Dispatcher::METHOD_NOT_ALLOWED:
                $response->status('405', "NOT ALLOWED");
                break;
            case Dispatcher::FOUND:
                $res = $this->createInstanceForRequest($routeInfo[1], $routeInfo[2], $request);
                $response->end($res);
                break;
        }
    }

    /**
     * Regular Handler for GET, DELETE, HEAD Request
     * @param array{string, string} $handler
     * @param array{mixed} $vars
     * @param SwooleRequest $serverRequest
     * @return ResponseInterface|string|false
     */
    private function createInstanceForRequest(array $handler, array $vars, SwooleRequest $serverRequest): ResponseInterface|string|false
    {
        // ? Ensure that $handler is an array and has at least two elements
        if (count($handler) >= 2) {
            $class = $handler[0];
            $method = $handler[1];

            // ? Check if $class is a valid class name
            if (class_exists($class)) {
                // ? Check if $method is a valid method of $class
                if (method_exists($class, $method)) {
                    // ? Create an instance of $class
                    $instance = new $class();
                    $object = [$instance, $method];

                    $this->param[] = $serverRequest;

                    // ? Inject response as param to handle return value
                    foreach ($vars as $value) {
                        $this->param[] = $value;
                    }

                    // ? Ensure that $instance is an object before calling the method
                    /** @var callable $object */
                    return call_user_func_array($object, $this->param);
                } else {
                    throw new InvalidArgumentException('Invalid method name');
                }
            } else {
                throw new InvalidArgumentException('Invalid class name');
            }
        } else {
            throw new InvalidArgumentException('Invalid $handler array structure');
        }
    }
}