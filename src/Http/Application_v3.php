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
use Xel\Async\Gemstone\Csrf_Shield;
use Xel\Async\Gemstone\SlidingWindowLimiter;
use Xel\Async\Http\Server\Server_v2;
use Xel\Async\Router\Main_v2;
use Xel\DB\QueryBuilder\QueryDML;

final class Application_v3 implements ApplicationInterface
{

    public Server $server;
    private SlidingWindowLimiter $bucketLimiter;
    private Main_v2 $main_v2;

    public function __construct
    (
        private readonly array     $config,
        private readonly array     $loader,
        private readonly array     $dbConfig,
        private readonly Container $register,
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
     * @throws Exception
     */
    public function onRequest(Request $request, Response $response): void
    {
        $router = $this->main_v2;
        $config = $this->register->get('gemstone');
        var_dump($request->header);
        var_dump($request->cookie);
        /**
         * Global Cors
         */
        if ($config['securePost']['condition'] !== false) {
            if (isset($config['securePost']['cors'])) {
                $corsConfig = $config['securePost']['cors'];

                // Set CORS headers for all requests
                $response->header('Access-Control-Allow-Origin', $corsConfig['allowOrigin']);
                if ($corsConfig['allowCredentials']) {
                    $response->header('Access-Control-Allow-Credentials', 'true');
                }

                // Handle preflight requests
                if ($request->server['request_method'] === 'OPTIONS') {
                    $response->status(200);
                    $response->header('Access-Control-Allow-Methods', implode(', ', $corsConfig['allowMethods']));
                    $response->header('Access-Control-Allow-Headers', implode(', ', $corsConfig['allowHeaders']));
                    $response->header('Access-Control-Max-Age', $corsConfig['maxAge']);
                    $response->end();
                    return;
                }
            }
        }
        // csrf check
        if ($config['gemstone_csrf']['condition'] === true && $request->getMethod() === 'POST' || $request->getMethod() === 'PUT'  || $request->getMethod() === 'PATCH' || $request->getMethod() === 'DELETE') {
            $this->csrfShield($request,$response, $config['gemstone_csrf']['key']);
        }

        if ($config['gemstone_limiter']['condition'] === false) {
            $router($this->server)
                ->routerMapper()
                ->dispatch($request->server['request_method'], $request->server['request_uri'])
                ->execute($request, $response);
        }

        try {
            if ($this->bucketLimiter->isAllowed($request, $response)) {
                $router($this->server)
                    ->routerMapper()
                    ->dispatch($request->server['request_method'], $request->server['request_uri'])
                    ->execute($request, $response);
            }
        } catch (Exception $e) {
            $response->status($e->getCode(), $e->getMessage());
            $response->end($e->getMessage());
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
        $instance = $this->register->get($task->data);
        if ($instance instanceof JobInterface) {
            try {
                $instance->process();
                $task->finish(true);
            } catch (Exception $e) {
                $task->finish($e->getMessage());
            }
        }
    }

    public function onFinish(Server $server, int $taskId, $data): void
    {
    }

    /******************************************************************************************************************
     * Server Utility Section
     ******************************************************************************************************************/
    public function router(): Application_v3
    {
        $instance = new Main_v2($this->register, $this->loader);
        $this->main_v2 = $instance;
        return $this;
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function gemstoneLimiter(): Application_v3
    {
        $config = $this->register->get('gemstone');

        $instance = new SlidingWindowLimiter
        (
            $config['gemstone_limiter']['max_token'],
            $config['gemstone_limiter']['interval'],
            $config['gemstone_limiter']['block_ip'],
        );

        $this->bucketLimiter = $instance;
        return $this;
    }

    public function csrfShield(Request $request, Response $response, $key): void
    {
        $data = new Csrf_Shield();
        if ($request->header['x-csrf-token'] != null) {
           if ($data->validateToken($request->header['x-csrf-token'], $key) === false){
               $response->setStatusCode(419, "Csrf Token Mismatch");
               $response->end(json_encode(["error" =>"csrf token mismatch"]));
           }
           return;
        }
        $response->setStatusCode(419, "Csrf Token Mismatch");
        $response->end(json_encode(["error" =>"csrf token mismatch"]));
    }
}