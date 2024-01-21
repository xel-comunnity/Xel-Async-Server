<?php

namespace Xel\Async\test\service;
use Xel\Async\Router\Attribute\GET;

class hello
{
    #[GET("/test")]
    public function index(): string|false
    {
        return json_encode("hello world");
    }

    #[GET("/bro")]
    public function sample(): string|false
    {
        $data = [
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",

            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",

            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",

            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",

            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",

            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",

            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",

            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",

            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",

            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",
            "value","value","value","value","value","value","value","value","value",

        ];
        return json_encode($data);
    }
}