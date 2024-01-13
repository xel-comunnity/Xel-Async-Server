<?php

namespace Xel\Devise\Service\RegisterService;

use Xel\Devise\Service\hello;
use Xel\Devise\Service\Services;

class Register
{
    private static array $ServiceManager = [];

    public static function serviceProvider(): array
    {
        return static::$ServiceManager = [
          hello::class,
          Services::class
        ];
    }
}