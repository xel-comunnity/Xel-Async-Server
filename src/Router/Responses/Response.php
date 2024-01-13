<?php

namespace Xel\Async\Router\Responses;

class Response
{
    public static function Json(\Swoole\Http\Response $response, mixed $data, int $status): void
    {
        $response->header('Content-Type', 'application/json');
        $response->end($data);
        $response->status($status);
    }

    public static function Plain(\Swoole\Http\Response $response, mixed $data, int $status): void
    {
        $response->header('Content-Type', 'text/plain');
        $response->end($data);
        $response->status($status);
    }
}