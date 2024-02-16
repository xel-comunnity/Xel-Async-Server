<?php

namespace Xel\Async\Router\Attribute\Generate;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use Xel\Async\Router\Attribute\Middlewares;
use Xel\Async\Router\Attribute\Router;

const CACHE_NAME = "class.cache";


/**
 * @param class-string[] $loader
 * @param string $cachePath
 * @return void
 */
function loaderClass(array $loader, string $cachePath): void
{
    try {
        if (!file_exists(CACHE_NAME)){
            $class = extractClass($loader);

            // ? CacheAble
            generateCacheClass($class, $cachePath);
        }
    } catch (RuntimeException $exception) {
        // Print a message (you might want to log it instead in a real-world scenario)
        echo 'Exception in loaderClass: ' . $exception->getMessage() . PHP_EOL;
        // Rethrow the exception
    }
}

/**
 * @param class-string[] $loader
 * @return array<int|string, mixed>
 */
function extractClass(array $loader): array
{
    $param = [];
    foreach ($loader as $value) {
        try {
            $reflection = new ReflectionClass($value);
            $methods = $reflection->getMethods();

            $middlewareClass = extractMiddlewareClass($reflection);
            foreach ($methods as $method) {
                $attr = $method->getAttributes(Router::class, ReflectionAttribute::IS_INSTANCEOF);
                foreach ($attr as $attribute) {

                    /**@var Router $getAttrInstance*/
                    $getAttrInstance = $attribute->newInstance();
                    $getMethod = $method->getName();

                    $tmp = [
                        "Uri" => $getAttrInstance->getPath(),
                        "RequestMethod" => $getAttrInstance->getMethod(),
                        "Class" => $value,
                        "Method" => $getMethod,
                        "Middlewares" => array_merge($middlewareClass, $getAttrInstance->getMiddlewares())
                    ];

                    $param[] = $tmp;
                }
            }
        } catch (ReflectionException $exception) {
            // Print a message (you might want to log it instead in a real-world scenario)
            echo 'Exception in extractClass: ' . $exception->getMessage() . PHP_EOL;
        }
    }
    return $param;

}

/**
 * Cache Class Generator
 * @param array<int|string, mixed> $class
 * @param string $path
 */
function generateCacheClass(array $class, string $path): void
{
    try {
        file_put_contents($path . "/" . CACHE_NAME, '<?php return ' . var_export($class, true) . ';');
    } catch (RuntimeException $exception) {
        // Print a message (you might want to log it instead in a real-world scenario)
        echo 'Exception in generateCacheClass: ' . $exception->getMessage() . PHP_EOL;
    }
}

/**
 * @param string $cachePath
 * @return array<int|string, mixed>|null
 */
function loadCachedClass(string $cachePath): array|null
{
    try {
        if (file_exists($cachePath . "/" . CACHE_NAME)) {
            return include $cachePath . "/" . CACHE_NAME;
        }

    } catch (RuntimeException $exception) {
        echo 'Exception: ' . $exception->getMessage() . PHP_EOL;
    }
    return null;
}

/**
 * @param class-string[] $loader
 * @param string $cachePath
 * @return void
 */
function renewClass(array $loader, string $cachePath): void
{
    try {
        $class = extractClass($loader);
        generateCacheClass($class, $cachePath);
    } catch (RuntimeException $exception) {
        // Print a message (you might want to log it instead in a real-world scenario)
        echo 'Exception in renewClass: ' . $exception->getMessage() . PHP_EOL;
    }
}

/**
 * Middlewares Section
 * @param ReflectionClass $reflectionClass
 * @return class-string[]
 */

function extractMiddlewareClass(ReflectionClass $reflectionClass): array
{
    $data = $reflectionClass->getAttributes();
    $middleware = [];
    foreach ($data as $attribute){
        /**@var Middlewares $param*/
        $param = $attribute->newInstance();
        /** @phpstan-ignore-next-line*/
        $middleware = $param->getMiddlewareClass();
    }
    return $middleware;
}

