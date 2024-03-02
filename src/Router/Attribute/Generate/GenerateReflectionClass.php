<?php

namespace Xel\Async\Router\Attribute\Generate;

use InvalidArgumentException;
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
 * @param string $build
 * @return array|null
 */
function loaderClass(array $loader, string $cachePath, string $build = 'dev'): ?array
{
    if ($build !== "dev"){
        return extractClass($loader);
    }

    return loadCachedClass($cachePath);
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
 * @return void
 */
function generateCacheClass(array $class, string $path): void
{
    try {
        file_put_contents($path . "/" . CACHE_NAME, '<?php return ' . var_export($class, true) . ';');
    } catch (RuntimeException $exception) {
        // Log the exception (you might want to use a proper logging mechanism)
        error_log('Exception in generateCacheClass: ' . $exception->getMessage());
        // Re-throw the exception to be caught by higher-level error handling
        throw $exception;
    }
}

/**
 * @param string $cachePath
 * @return array<int|string, mixed>|null
 */
function loadCachedClass(string $cachePath): ?array
{
    if (!file_exists($cachePath . "/" . CACHE_NAME)) {
        throw new InvalidArgumentException("File not provided: $cachePath".'/'.CACHE_NAME);
    }
    return require $cachePath . "/" . CACHE_NAME;

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

