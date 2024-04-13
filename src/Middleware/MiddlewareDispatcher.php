<?php

namespace Xel\Async\Middleware;

use SplQueue;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Xel\Async\Contract\MiddlewareInterfaces;
use Xel\Async\Contract\RequestHandlerInterfaces;

readonly class MiddlewareDispatcher implements RequestHandlerInterfaces
{
    private SplQueue $splQueue;
    private array $middlewares;
    private Response $response;

    public function __invoke(array $middlewares, Request $request, Response $response): static
    {
       $this->middlewares = $middlewares;
       $this->response = $response;
       $this->queue();
       return $this;
    }

    public function addMiddleware(): void
    {
        foreach ($this->middlewares as $middleware) {
            $instance = new $middleware();
            if ($instance instanceof MiddlewareInterfaces) {
                $this->splQueue->enqueue($instance);
            }
        }
    }

    public function handle(Request $request): void
    {
        if (!$this->splQueue->isEmpty()) {
            $middleware = $this->splQueue->dequeue();
            if ($middleware instanceof RequestHandlerInterfaces) {
                $middleware->handle($request);
            }

            if ($middleware instanceof MiddlewareInterfaces) {
                $middleware->process($request, $this, $this->response);
            }
        }
    }

    private function queue(): void
    {
        $queue =  new SplQueue();
        $this->splQueue = $queue;
    }
}

