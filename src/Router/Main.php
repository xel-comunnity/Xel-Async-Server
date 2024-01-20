<?php

namespace Xel\Async\Router;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Nyholm\Psr7\Stream;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionException;
use Swoole\Http\Response;
use Xel\Async\Http\Handler\Handler;
use Xel\Async\Map\PsrFactory;
use Xel\Async\Router\Attribute\Extract\Extractor;
use function FastRoute\simpleDispatcher;

/**
 * Merging Swoole HTTP standard to swoole psr 7 using lazy implementation
 */

class Main
{
    private static ?Dispatcher $dispatcher = null;

    /**
     * @throws ReflectionException
     */
    public static function initialize(array $loader): void
    {
      // ? inject to Loader
        $inLoader = Extractor::setLoader($loader);

      static::$dispatcher = simpleDispatcher(function (RouteCollector $routeCollector) use ($inLoader){
        $routeCollector->addGroup('/api', function (RouteCollector $r) use ($inLoader){
            foreach ($inLoader as $item){
                $r->addRoute($item['RequestMethod'],$item['Uri'],[$item['Class'],$item['Method']]);
            }
        });

      });
    }

    public static function dispatch(string $method, string $uri): Dispatcher\Result\Matched|Dispatcher\Result\MethodNotAllowed|Dispatcher\Result\NotMatched
    {
        return static::$dispatcher->dispatch($method,$uri);
    }

    public static function load
    (
        $routeInfo,
        Response $response
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
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];
                $param = [];

                // ? Call the handler
                if(is_array($handler) && count($handler) == 2) {
                    $class = $handler[0];
                    $method = $handler[1];
                    $instance = new $class();
                    $handler = [$instance, $method];
                    /**
                     * Inject response as param to handle return value
                     */
                    foreach ($vars as $value) {
                        $param[] = $value;
                    }
                }

                /**
                 * Make return as response body
                 */
                $data  = call_user_func_array($handler, $param);

                $response->end($data);
                break;
        }
    }
}