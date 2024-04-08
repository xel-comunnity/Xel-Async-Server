<?php

namespace Xel\Async\Http;

use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use PDO;
use Swoole\Database\PDOConfig;
use Swoole\Database\PDOPool;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Server;
use Xel\Async\Contract\ApplicationInterface;
use Xel\Async\Http\Server\QueryBuildersManager;
use Xel\Async\Http\Server\Server_v2;
use Xel\Async\Router\Main;
use Xel\DB\QueryBuilder\QueryDML;
use Xel\DB\XgenConnector;
use Xel\Psr7bridge\PsrFactory;

final class Application_v2 implements ApplicationInterface
{
    private Server $server;
    private array $asyncTask;
    public function __construct
    (
        private array $config,
        private array $loader,
        private array $dbConfig,
        private Container $register,
    )
    {}

    public function init(): void
    {
        Server_v2::init($this->config);
        $server = Server_v2::getServer();
        $this->server = $server;

        // ? server start
        $server->on('Start', [$this, 'onStart']);
        $server->on('WorkerStart', [$this, 'onWorkerStart']);
        $server->on('Request', [$this, 'onRequest']);
        $server->start();

    }

    /******************************************************************************************************************
     * HTTP Server Section
     ******************************************************************************************************************/

    public function onStart(): void
    {
        echo "Listen : {$this->config['api_server']['host']}:{$this->config['api_server']['port']}";
    }
    /**
     * @throws Exception
     */
    public function onWorkerStart(): void
    {
        // ? xgen connector
//        $conn = new XgenConnector($this->dbConfig, $this->dbConfig['poolMode'], $this->dbConfig['pool']);
//        $conn->initializeConnections();

        $conn = new PDOPool((new PDOConfig())
            ->withDriver($this->dbConfig['driver'])
            ->withCharset($this->dbConfig['charset'])
            ->withHost($this->dbConfig['host'])
            ->withUsername($this->dbConfig['username'])
            ->withPassword($this->dbConfig['password'])
            ->withDbname($this->dbConfig['dbname'])
            ->withOptions($this->dbConfig['options']),
            $this->dbConfig['pool']);

        // ? Query Builder
        $builder = new QueryDML($conn, $this->dbConfig['poolMode']);
        $this->register->set('xgen', $builder);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function onRequest(Request $request, Response $response): void
    {
        $req = $this->psr7Bridge()->connectRequest($request);
        $this->router()
            ->routerMapper($this->loader, $req->getMethod(),$req->getUri())
            ->execute($req, $response);

        // ? Execute Async Task and pass a property
    }

    public function onTask()
    {

    }

    /******************************************************************************************************************
     * Server Utility Section
     ******************************************************************************************************************/
    private function psr7Bridge(): PsrFactory
    {
        return new PsrFactory($this->register);
    }
    private function router(): Main
    {
        return new Main($this->register, $this->psr7Bridge(), $this->server);
    }
}