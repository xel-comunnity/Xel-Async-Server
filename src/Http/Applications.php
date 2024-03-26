<?php
namespace Xel\Async\Http;

use DI\Container;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Xel\Async\Http\Server\QueryBuildersManager;
use Xel\Async\Http\Server\Servers;
use Xel\Async\Http\Server\XgenBuilderManager;
use Xel\Async\Router\Main;
use Xel\DB\QueryBuilder\QueryBuilder;
use Xel\DB\XgenConnector;
use Xel\Psr7bridge\PsrFactory;

class Applications
{
    private ?XgenConnector $dbConnection = null;
    private QueryBuilder $queryBuilder;

    public function __construct
    (
        private readonly array $config,
        private readonly array $loader,
        private readonly array $dbConfig,
        private readonly Container $register,
    )
    {}

    /**
     * @return void
     */
    public function initialize(): void
    {
        // ? server Init
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
                // ? xgen connector
                $conn = new XgenBuilderManager($this->dbConfig, $this->dbConfig['poolMode'], $this->dbConfig['pool']);

                // ? Query Builder
                $builder = new QueryBuildersManager($conn->getConnection(), $this->dbConfig['poolMode']);
                $this->register->set('xgen', $builder->getQueryBuilder());

                // add to property value
                $this->queryBuilder = $builder->getQueryBuilder();

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

    public function getConnection(): ?XgenConnector
    {
        return $this->dbConnection;
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }

}