<?php

use HttpSoft\Message\ResponseFactory;
use HttpSoft\Message\ServerRequestFactory;
use HttpSoft\Message\StreamFactory;
use HttpSoft\Message\UploadedFileFactory;
use Xel\Async\Http\Applications;
use Xel\Async\Http\Response;
use function Xel\Async\Router\Attribute\Generate\loadCachedClass;
use function Xel\Async\Router\Attribute\Generate\loaderClass;

require_once __DIR__ . "/../../vendor/autoload.php";
$config = require __DIR__ . "/config/config.php";
$service = require __DIR__."/service/serviceRegister.php";

/**
 * Register Container
 */
$register = new Xel\Async\Http\Container\Register();
$register->register('ServerFactory', ServerRequestFactory::class);
$register->register('StreamFactory', StreamFactory::class);
$register->register('UploadFactory', UploadedFileFactory::class);
$register->register("ResponseFactory", ResponseFactory::class);
$register->register('ResponseInterface', Response::class);

/**
 * Class Cache Loader
 *
 */
$path = __DIR__."/../test/cache";

try {
    loaderClass($service, $path);
    $cacheClass = loadCachedClass($path);

    /**
     * Launch Class
     */
    $app = new Applications();
    $app
        ->initialize($config)
        ->onEvent($cacheClass, $register)
        ->run();

} catch (ReflectionException $e) {
    echo "Reflection error : ". $e->getMessage();
}