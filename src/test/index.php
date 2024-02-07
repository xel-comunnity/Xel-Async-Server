<?php

use DI\ContainerBuilder;
use Xel\Async\Http\Applications;
use Xel\Container\Dock;
use function Xel\Async\Router\Attribute\Generate\loadCachedClass;
use function Xel\Async\Router\Attribute\Generate\loaderClass;
use function Xel\Async\test\config\entryLoader;
require_once __DIR__ . "/../../vendor/autoload.php";

try {

    /**
     * Register Container
     */
    $DIContainer = new ContainerBuilder();
    $xelContainer = new Xel\Container\XelContainer($DIContainer);
    $Dock = new Dock($xelContainer, entryLoader());
    $container = $Dock->launch();
    $Class = $container->get('ServiceDock');

    /**
     * Server config
     */
    $Config = $container->get('server');

    /**
     * Class Cache Loader
     *
     */
    $path = __DIR__."/../test/cache";

    loaderClass($Class, $path);
    $cacheClass = loadCachedClass($path);

    /**
     * Launch Class
     */
    $app = new Applications();
    $app
        ->initialize($Config)
        ->onEvent($cacheClass, $container)
        ->run();

} catch (ReflectionException $e) {
    echo "Reflection error : ". $e->getMessage();
}