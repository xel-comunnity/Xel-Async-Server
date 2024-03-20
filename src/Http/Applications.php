<?php
namespace Xel\Async\Http;

use DI\Container;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Xel\Async\Http\Server\Servers;
use Xel\Async\Router\Main;
use Xel\DB\QueryBuilder\QueryBuilder;
use Xel\DB\QueryBuilder\QueryBuilderExecutor;
use Xel\DB\XgenConnector;
use Xel\Psr7bridge\PsrFactory;

class Applications
{
    private Servers $instance;

    /**
     * @param array<string, mixed> $config
     * @return self
     */
    public function initialize(array $config): self
    {
        $this->instance = new Servers($config);
        return $this;
    }

    /**
     * @param array<string, mixed> $loader
     * @param array $dbConfig
     * @param Container $register
     * @return self
     */
    public function onEvent(array $loader, array $dbConfig,  Container $register): self
    {
        // ? initial Psr Bridge Http Request & Response
        $psrBridge = new PsrFactory($register);
        // ? initial loader for dynamic router
        $router = new Main($register, $psrBridge, $this->instance->instance->setting['QueryBuilder']);

        /**
         * On workerStart
         */
        $this->instance
            ->instance
            ->on("workerStart", function () use($dbConfig){
                $db = (new XgenConnector($dbConfig, $dbConfig['pool'], $dbConfig['maxConnections']));
                $queryBuilderExecutor = new QueryBuilderExecutor($db);
                $queryBuilder = new QueryBuilder($queryBuilderExecutor);

                $this->instance
                    ->instance->setting = [
                    'QueryBuilder' => $queryBuilder,
                    'XgenQueryAdapterInterface' => $db
                ];

            });

        /**
         * On request
         */
        $this->instance->instance
            ->on('request', function
            (
                SwooleRequest $request,
                SwooleResponse $response
            ) use ($loader, $psrBridge, $router){
                // ? Bridge Swoole Http Request
                $req = $psrBridge->connectRequest($request);

                // ? Router Dynamic Loader
                $router
                    ->routerMapper($loader,$req->getMethod(),$req->getUri()->getPath())
                    ->Execute($req, $response);
       });
        return $this;
    }

    public function run(): void
    {
        $this->instance->launch();
    }
}