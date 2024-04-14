<?php

namespace Xel\Async\Http;

use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use Swoole\Database\PDOConfig;
use Swoole\Database\PDOPool;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Server;
use Swoole\Server\Task;
use Xel\Async\Contract\ApplicationInterface;
use Xel\Async\Contract\JobInterface;
use Xel\Async\Gemstone\TokenBucketLimiter;
use Xel\Async\Http\Server\Server_v2;
use Xel\Async\Router\Main_v2;
use Xel\DB\QueryBuilder\QueryDML;

final readonly class Application_v3 implements ApplicationInterface {

    public Server $server;
    private TokenBucketLimiter $bucketLimiter;
    private Main_v2 $main_v2;
    public function __construct
    (
        private array     $config,
        private array     $loader,
        private array     $dbConfig,
        private Container $register,
    )
    {}

    public function init(): void
    {
        Server_v2::init($this->config);
        $server = Server_v2::getServer();

        // Init Server
        $this->server = $server;

        // ? server start
        $server->on('Start', [$this, 'onStart']);
        $server->on('WorkerStart', [$this, 'onWorkerStart']);
        $server->on('Request', [$this, 'onRequest']);

        // ? Task Deployment
        $server->on('task', [$this, 'onTask']);
        $server->on('finish', [$this, 'onFinish']);
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
        $config = $this->register->get('gemstone');
        if ($config['gemstone_limiter']['condition'] === false){
            $this->main_v2
                ->routerMapper()
                ->dispatch($request->server['request_method'],$request->server['request_uri'])
                ->execute($request, $response);
        }else{
            if ($this->bucketLimiter->isPermitted()){
                $this->main_v2
                    ->routerMapper()
                    ->dispatch($request->server['request_method'],$request->server['request_uri'])
                    ->execute($request, $response);
            }else{
                $response->setStatusCode(429, "To Many Request");
                $response->end(json_encode([
                    "error" => "Too Many Request !! , Please Try in a View Minutes",
                ]));
            }
        }
    }


    /******************************************************************************************************************
     * Server Async Task Dispatcher
     ******************************************************************************************************************/
    /**
     * @param Server $server
     * @param Task $task
     * @return void
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function onTask(Server $server, Server\Task $task): void
    {
        $instance =  $this->register->get($task->data);
        if($instance instanceof JobInterface){
            try {
                $instance->process();
                $task->finish(true);
            }catch (Exception $e){
                $task->finish($e->getMessage());
            }
        }
    }

    public function onFinish(Server $server, int $taskId, $data): void
    {}

    /******************************************************************************************************************
     * Server Utility Section
     ******************************************************************************************************************/
    private function router(): void
    {
         $instance =  new Main_v2($this->register, $this->loader, $this->server);
         $this->main_v2 = $instance;
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function gemstoneLimiter():void
    {
        $config = $this->register->get('gemstone');

        $instance = new TokenBucketLimiter
        (
            $config['gemstone_limiter']['max_token'],
            $config['gemstone_limiter']['refill_rate'],
            $config['gemstone_limiter']['interval']
        );
        $this->bucketLimiter = $instance;
    }
}