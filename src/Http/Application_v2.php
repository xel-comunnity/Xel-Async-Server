<?php

namespace Xel\Async\Http;
use DI\Container;
use Exception;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Swoole\Server;
use Xel\Async\Http\Server\QueryBuilders;
use Xel\Async\Router\Main;
use Xel\DB\XgenConnector;
use Xel\Psr7bridge\PsrFactory;
class Application_v2
{
    private Server $server;
    private ?XgenConnector $dbConnection = null;

    public function __construct
    (
        private readonly array $config,
        private readonly array $loader,
        private readonly array $dbConfig,
        private readonly Container $container
    )
    {
        $config = $this->config;
        $mode = $config['api_server']['mode'];

        /**
         * Init Server Mode
         */
        $this->server = ($mode == 1)
            ? new Server($config['api_server']['mode'], $config['api_server']['host'], $config['api_server']['port'])
            : new Server($config['api_server']['mode'], $config['api_server']['host'], $config['api_server']['port'], SWOOLE_SOCK_TCP);

        /**
         * Init Server Set
         */
        $this
            ->server
            ->set($config['api_server']['options']);

        /**
         * Init Event
         */
        $this->server->on('Start', [$this, 'onStart']);
        $this->server->on('WorkerStart', [$this, 'onWorkerStart']);
        $this->onRequest();
        $this->server->on('Receive', [$this, 'onReceive']);
        $this->server->on('Task', [$this, 'onTask']);
        $this->server->on('Finish', [$this, 'onFinish']);

    }

    public function onStart(): void
    {
        echo "Listening at " . $this->config['api_server']['host'] . ":" . $this->config['api_server']['port'] . " \n";
    }

    /**
     * @throws Exception
     */
    public function onWorkerStart(): void
    {
        $db = new XgenConnector(
            $this->dbConfig,
            $this->dbConfig['poolMode'],
            $this->dbConfig['pool']
        );

        $db->initializeConnections();
        $this->dbConnection = $db;
    }

    public function onRequest(): void
    {
        // ? initial Psr Bridge Http Request & Response
        $psrBridge = new PsrFactory($this->container);
        $router = new Main($this->container, $psrBridge);

        $this->server->on('Request', function (SwooleRequest $request, SwooleResponse $response) use ($psrBridge, $router){
            // ? Bridge Swoole Http Request
            $req = $psrBridge->connectRequest($request);

            // ? Query Builder
            $queryBuilder = QueryBuilders::getQueryBuilder($this->dbConnection, $this->dbConfig['poolMode']);
            $this->container->set('xgen', $queryBuilder);

            // ? Router Dynamic Loader
            $router
                ->routerMapper($this->loader,$req->getMethod(),$req->getUri()->getPath())
                ->Execute($req, $response);
        });


    }


    public function onReceive(): void
    {
        echo "onReceive";
    }

    public function onTask(): void
    {
        echo "onTask";

    }

    public function onFinish(): void
    {
        echo "onTask";

    }
}