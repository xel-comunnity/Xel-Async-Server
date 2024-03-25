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
    private ?XgenConnector $dbConnection = null;

    public function __construct
    (
        private readonly array $config,
        private readonly array $loader,
        private readonly array $dbConfig,
        private readonly Container $register
    )
    {}

    /**
     * @return void
     */
    public function initialize(): void
    {
        // ? server Init
        $instance = null;
        $instance = new Servers($this->config);

        // ? initial Psr Bridge Http Request & Response
        $psrBridge = new PsrFactory($this->register);
        $router = new Main($this->register, $psrBridge);

        /**
         * On workerStart
         */
        $instance
            ->instance
            ->on("workerStart", function (){
                $db = new XgenConnector($this->dbConfig, $this->dbConfig['poolMode'], $this->dbConfig['pool']);
                $db->initializeConnections();
                $this->dbConnection = $db;

                // ? Query Builder
                $queryBuilder = QueryBuilders::getQueryBuilder($this->dbConnection, $this->dbConfig['poolMode']);
                $this->register->set('xgen', $queryBuilder);
            });

        /**
         * On request
         */
        $instance->instance
            ->on('request', function
            (
                SwooleRequest $request,
                SwooleResponse $response
            ) use ($psrBridge, $router){
                // ? Bridge Swoole Http Request
                $req = $psrBridge->connectRequest($request);
                // ? Router Dynamic Loader
                $router
                    ->routerMapper($this->loader,$req->getMethod(),$req->getUri()->getPath())
                    ->Execute($req, $response);
            });

            $instance->launch();
    }

}