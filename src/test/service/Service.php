<?php
namespace Xel\Async\test\service;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Xel\Async\Router\Attribute\GET;
use Xel\Async\Http\Response;
class Service
{
    #[GET("/")]
    public function index(): MessageInterface|ResponseInterface
    {
        return Response::create()->json(['helloWorld'],201);
    }

    #[GET("/data")]
    public function sample(): MessageInterface|ResponseInterface
    {
        return Response::create()->json(['helloThere'],201);
    }
}