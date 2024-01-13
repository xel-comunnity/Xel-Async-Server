<?php

namespace Xel\Async\Router\Attribute\Extract;

use ReflectionClass;
use ReflectionException;
use Xel\Async\Router\Attribute\Router;
use Xel\Async\Router\Loader\ClassLoader;
use Xel\Devise\Service\RegisterService\Register;

//require_once __DIR__."/../../../../vendor/autoload.php";


class Extractor
{
    private static array $param = [];

    /**
     * @throws ReflectionException
     */
    public static function setLoader(array $loader): array
    {
       return static::Extract($loader);
    }

    /**
     * @throws ReflectionException
     */
    private static function Extract(array $loader): array
    {
        foreach ($loader as $value){
            $reflection = new ReflectionClass($value);
            $methods = $reflection->getMethods();
            foreach ($methods as $method) {
                $attr = $method->getAttributes(Router::class);
                foreach ($attr as $attribute) {
                    $getAttrInstance = $attribute->newInstance();
                    $getMethod = $method->getName();
                    $tmp = [
                        "Uri" => $getAttrInstance->getPath(),
                        "RequestMethod" => $getAttrInstance->getMethod(),
                        "Class" => $value,
                        "Method" => $getMethod,
                    ];
                    self::$param[] = $tmp;
                }
            }
        }

        return static::$param;
    }

}