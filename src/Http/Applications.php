<?php
namespace Xel\Async\Http;

use DI\Container;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Xel\Async\Http\Server\QueryBuilders;
use Xel\Async\Http\Server\Servers;
use Xel\Async\Router\Main;
use Xel\DB\XgenConnector;
use Xel\Psr7bridge\PsrFactory;

class Applications
{
    private ?Servers $instance = null;
    private ?XgenConnector $dbConnection = null;

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
        $router = new Main($register, $psrBridge);


        /**
         * On workerStart
         */
        $this->instance
            ->instance
            ->on("workerStart", function () use($dbConfig){
                $db = new XgenConnector($dbConfig, $dbConfig['poolMode'], $dbConfig['pool']);
                $db->initializeConnections();
                $this->dbConnection = $db;
            });

        /**
         * On request
         */
        $this->instance->instance
            ->on('request', function
            (
                SwooleRequest $request,
                SwooleResponse $response
            ) use ($loader, $psrBridge, $router, $register, $dbConfig){
                // ? Bridge Swoole Http Request
                $req = $psrBridge->connectRequest($request);

                // ? Query Builder
                $queryBuilder = QueryBuilders::getQueryBuilder($this->dbConnection,$dbConfig['poolMode']);
                $register->set('xgen', $queryBuilder);

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