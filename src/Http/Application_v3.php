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
use Xel\Async\SessionManager\SwooleSession;
use Xel\DB\QueryBuilder\QueryDML;

final class Application_v3 implements ApplicationInterface
{
    public Server $server;
    private SlidingWindowLimiter $bucketLimiter;
    private Main_v2 $main_v2;
    private SwooleSession $session;
    private Csrf_Shield $csrfManager;

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

        // ? Init Server
        $this->server = $server;

        // ? Init Swoole Session
        $session = new SwooleSession();
        $session->__init();
        $this->session = $session;

        // ? Init Csrf Shield
        $config = $this->register->get('gemstone');
        $csrfConfig = $config['gemstone_csrf'];
        $this->csrfManager = new Csrf_Shield($this->session, $csrfConfig);    
   

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
    public function onWorkerStart(Server $server, $workerId): void
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
        
        if ($workerId === 0) {
            // ? get config 
            $config = $this->register->get('gemstone');
            $sessionConfig = $config['gemstone_csrf'];
            $session = $this->session;


            if($sessionConfig['condition'] !== false){
                // Start a coroutine to handle session cleanup
                \Swoole\Coroutine::create(function () use ($session, $sessionConfig){
                    while (true) {
                        // Sleep for 5 seconds
                        \Swoole\Coroutine::sleep($sessionConfig['clear_rate']);

                        // Get the current session data
                        $data = $session->currentSession();

                        // Initialize a flag to track if any session was cleared
                        $sessionCleared = 0;
                        // Iterate over the session data
                        if($session->count() > 0){
                            foreach ($data as $key => $value) {
                                if ($value['expired'] <= time()) {
                                    // Delete the expired session
                                    $session->delete($key);
                                    $sessionCleared = 1;
                                }else{
                                    $sessionCleared = 2;
                                }
                            }
                        }else{
                            $sessionCleared = 0;
                        }
                    
                        
                        switch($sessionCleared){
                            case 1 :
                                echo "Already cleared and current session is : " . $session->count() . PHP_EOL;
                                break;
                            case 2 :
                                echo "current session is : " . $session->count() . PHP_EOL;
                                break;    
                            default:
                                echo '[HTTP1-ADVANCED]: Empty (' . date('H:i:s') . ')', PHP_EOL;
                                break;
                        }
                    
                    }
                });
            }

         
        }
        
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
        /**
         * Global Cors
         */
        if ($config['securePost']['condition'] !== false) {
            if (isset($config['securePost']['cors'])) {
                $corsConfig = $config['securePost']['cors'];

                // Set CORS headers for all requests
                $whiteLits = $corsConfig['whitelists'];
                if(isset($request->header['origin'])){
                    // ? check origin in white list
                    $origin = $request->header['origin'];
                    if(in_array($origin, $whiteLits)){
                        // Add CORS headers
                        $response->header('Access-Control-Allow-Origin', $origin);
                        $response->header('Access-Control-Allow-Methods', implode(', ', $corsConfig['allowMethods']));
                        $response->header('Access-Control-Allow-Headers', implode(', ', $corsConfig['allowHeaders']));
                        $response->header('Access-Control-Expose-Headers', implode(', ',$corsConfig['allowExposeHeaders'])); // Add this line
            
                    }else{
                        $response->setStatusCode(403, 'Forbiden Access');
                        $response->end('Forbiden access');
                    }
                }else{
                    $response->header('Access-Control-Allow-Origin', $request->header['host']);
                    $response->header('Access-Control-Allow-Methods', implode(', ', $corsConfig['allowMethods']));
                    $response->header('Access-Control-Allow-Headers', implode(', ', $corsConfig['allowHeaders']));
                    $response->header('Access-Control-Expose-Headers', implode(', ',$corsConfig['allowExposeHeaders'])); // 

                }
  
                if ($corsConfig['allowCredentials']) {
                    $response->header('Access-Control-Allow-Credentials', $corsConfig['allowCredentials']);
                }

                // Handle preflight requests
                if ($request->server['request_method'] === 'OPTIONS') {
                    $response->header('Access-Control-Max-Age', $corsConfig['maxAge']);
                    $response->status(200);                    
                    return;
                }
            }
        }
        /**
         * Gemstone Limiter
         */
        if ($config['gemstone_csrf']['condition'] === true && $request->getMethod() === 'POST' || $request->getMethod() === 'PUT'  || $request->getMethod() === 'PATCH' || $request->getMethod() === 'DELETE') {
            $this->csrfShield($request,$response, $config['gemstone_csrf']['key']);
        }

        /**
         * Gemstone CSRF
         */
        if ($config['gemstone_limiter']['condition'] === false) {
            $router($this->server)
                ->routerMapper()
                ->dispatch($request->server['request_method'], $request->server['request_uri'])
                ->execute($request, $response);
        }else{
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
        $data = $this->csrfManager;
        if ($request->header['x-csrf-token'] != null) {
           if ($data->validateToken($request->header['x-csrf-token'], $key) === false){
               $response->setStatusCode(419, "Csrf Token Mismatch");
               $response->end(json_encode(["error" =>"csrf token mismatch"]));
           }
           return;
        }else{
            $response->setStatusCode(419, "Csrf Token Mismatch");
            $response->end(json_encode(["error" =>"csrf token mismatch"]));
        }

    }
}