<?php
namespace Xel\Async\Http;

use DI\Container;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Xel\Async\Contract\ApplicationInterface;
use Xel\Async\Http\Server\QueryBuildersManager;
use Xel\Async\Http\Server\Servers;
use Xel\Async\Router\Main;
use Xel\DB\XgenConnector;
use Xel\Psr7bridge\PsrFactory;

readonly class Applications implements ApplicationInterface
{
    public function __construct
    (
        private array     $config,
        private array     $loader,
        private array     $dbConfig,
        private Container $register,
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
        $router = new Main($this->register, $psrBridge, $this->loader);

        /**
         * On workerStart
         */
        $instance->instance
            ->on("workerStart", function (){
                // ? xgen connector
                $conn = new XgenConnector($this->dbConfig, $this->dbConfig['poolMode'], $this->dbConfig['pool']);
                $conn->initializeConnections();

                // ? Query Builder
                $builder = new QueryBuildersManager($conn, $this->dbConfig['poolMode']);
                $this->register->set('xgen', $builder->getQueryBuilder());
            });

        /**
         * On request
         */
        $instance->instance->on('request', function
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

        /**
         * On Task
         */

        $instance->launch();
    }
}