<?php

namespace Xel\Async\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use SplQueue;
use Xel\Async\Router\RouterRunner;

class Runner implements RequestHandlerInterface
{
    private SplQueue $queue;

    public function __construct(array $middlewares, private readonly RouterRunner $routerRunner)
    {
        $this->queue = new SplQueue();
        foreach ($middlewares as $middleware) {
            $instance = new $middleware;
            $this->queue->enqueue($instance);
        }
    }

    /**
     * @inheritDoc
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->queue->isEmpty()){
            $middleware = $this->queue->dequeue();

            if ($middleware instanceof RequestHandlerInterface){
                return $middleware->handle($request);
            }

            if ($middleware instanceof MiddlewareInterface) {
                return $middleware->process($request, $this);
            }
        }
        return $this->routerRunner->init();
    }
}