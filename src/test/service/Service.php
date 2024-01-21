<?php
namespace Xel\Async\test\service;
use Xel\Async\Router\Attribute\GET;
class Service
{
    #[GET("/")]
    public function index(): string|false
    {
        return json_encode("hello world");
    }

    #[GET("/data")]
    public function sample(): string|false
    {
        return json_encode("hello there");
    }
}