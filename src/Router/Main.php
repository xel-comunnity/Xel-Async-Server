<?php

namespace Xel\Async\Router;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use HttpSoft\Message\ResponseFactory;
use HttpSoft\Message\StreamFactory;
use InvalidArgumentException;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionException;
use Swoole\Http\Response as SwooleResponse;
use Xel\Async\Router\Attribute\Extract\Extractor;
use Xel\Psr7bridge\PsrFactory;
use function FastRoute\cachedDispatcher;

class Main
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
     * @return Main
     * @throws ReflectionException
     */
    public function __invoke(array $loader): self
    {
      // ? inject to Loader
      $inLoader = (new Extractor())->setLoader($loader);

      // encode json
      $data = json_encode($loader);

      // Check if encoding was successful
      if ($data === false) {
        echo 'JSON encoding failed!';
        return $this;
      }
      // ? make hash
      $hashRoute =  hash('sha256', $data);

      // ? get instance tmp cache
      $cache_key = 'xel_route_cache'. $hashRoute;

      $cache_dir = __DIR__.'/../../src/test/cache/'.$cache_key;
      $this->dispatcher = cachedDispatcher(function (RouteCollector $routeCollector) use ($inLoader, $loader){
        $routeCollector->addGroup('/api', function (RouteCollector $r) use ($inLoader, $loader){
            foreach ($inLoader as $item){
                $class = $item['Class'];
                $method = $item['Method'];
                // ? check the class in exist or not
                if (in_array($class, $loader,true)){
                    $r->addRoute($item['RequestMethod'], $item['Uri'], [$class, $method]);
                }else {
                    // ? Handle unknown class
                    echo "Unknown class: $class\n";
                }
            }
        });
      }, [
          'cacheDisabled' => false,
          'cacheKey' => $cache_dir

      ]);
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
     * @param Dispatcher\Result\Matched|Dispatcher\Result\NotMatched|Dispatcher\Result\MethodNotAllowed $routeInfo
     * @param PsrFactory $psrFactory
     * @param StreamFactory $streamFactory
     * @param ServerRequestInterface $requestFactory
     * @param ResponseFactory $responseFactory
     * @param SwooleResponse $response
     * @return void
     */
    public function load
    (
        Dispatcher\Result\Matched|Dispatcher\Result\NotMatched|Dispatcher\Result\MethodNotAllowed $routeInfo,
        PsrFactory             $psrFactory,
        StreamFactory          $streamFactory,
        ServerRequestInterface $requestFactory,
        ResponseFactory        $responseFactory,
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
                $res = $this->generateInstance($routeInfo[1], $routeInfo[2]);
                $psrFactory->connectResponse($res, $response);
                break;
        }
    }

    /**
     * @param array{string, string} $handler
     * @param array{mixed} $vars
     * @return MessageInterface|ResponseInterface
     */
    private function generateInstance(array $handler, array $vars):MessageInterface|ResponseInterface
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

                    // ? Inject response as param to handle return value
                    foreach ($vars as $value) {
                        $this->param[] = $value;
                    }

                    // ? Ensure that $instance is an object before calling the method
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