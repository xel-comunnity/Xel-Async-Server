<?php
namespace Xel\Async\test\service;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Xel\Async\Router\Attribute\GET;
use Xel\Async\Router\Attribute\POST;
use Xel\Async\Http\Response;

class Service
{
    #[POST("/datas")]
    public function index(ServerRequestInterface $request, Response $response): ResponseInterface
    {
        $data = ["message" => "success"];
        return $response->json($data, 200);
    }

    #[GET("/data1")]
    public function sample(ServerRequestInterface $request, Response $response): ResponseInterface
    {
       $data = ["message" => "hello xel"];
        return $response->json($data, 200);
    }
}