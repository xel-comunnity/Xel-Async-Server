<?php

namespace Xel\Async\Router;

use DI\Container;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Xel\Async\Http\Response as XelResponse;
use Swoole\Http\Response as SwooleResponse;
use Xel\DB\QueryBuilder\QueryBuilder;
use Xel\Psr7bridge\PsrFactory;

class RouterRunner
{
    private ServerRequestInterface $request;
    private XelResponse $xelResponse;

    private QueryBuilder $queryBuilder;
    private string $parentClass;
    /**
     * @var array<int|string, mixed>
     */
    private array $dispatch;
    private Container $container;

    /**
     * @param ServerRequestInterface $request
     * @param XelResponse $xelResponse
     * @param string $parentClass
     * @param array<int|string, mixed> $dispatch
     * @param Container $container
     * @return $this
     */

    public function __invoke
    (
        ServerRequestInterface $request,
        XelResponse $xelResponse,
        string $parentClass,
        array $dispatch,
        Container $container,
        QueryBuilder $queryBuilder
    ): static
    {
       $this->request = $request;
       $this->xelResponse = $xelResponse;
       $this->dispatch = $dispatch;
       $this->parentClass = $parentClass;
       $this->container = $container;
       $this->queryBuilder = $queryBuilder;
       return $this;
    }

    public function init(): ResponseInterface
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
        if ($instance instanceof $this->parentClass){
            $instance->setRequest($this->request);
            $instance->setResponse($this->xelResponse);
            $instance->setContainer($this->container);
            $instance->setQueryBuilder($this->queryBuilder);
        }

        // ? Inject response as param to handle return value
        foreach ($vars as $value) {
            $param[] = $value;
        }

        // ? Ensure that $instance is an object before calling the method
        /** @var callable $object */
        return call_user_func_array($object, $param);

    }
    public function exec(PsrFactory $psrFactory,SwooleResponse $swooleResponse, ResponseInterface $response): void
    {
        $psrFactory->connectResponse($response, $swooleResponse);
    }

}