<?php
//
require __DIR__."/vendor/autoload.php";


use Swoole\Http\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Xel\Async\Gemstone\SlidingWindowsLimiter_V2;


$server = new Server('0.0.0.0', 9501);
$server->set([
    'worker_num' => swoole_cpu_num(),
//    'socket_buffer_size' => swoole_cpu_num() * 1024 *1024, //必须为数字，单位为字节，如128 * 1024 *1024表示每个TCP客户端连接最大允许有128M待发送的数据
//    'send_yield' => true,
//    'send_timeout' => 5, // 1.5秒


]);
$limiter = new SlidingWindowsLimiter_V2(1000, 60, false, __DIR__.'/blacklist.php');
$server->on('connect', function (Server $server, int $fd) use ($limiter) {
    try {
        if (!$limiter->isAllowed($server, $fd)) {
            $server->close($fd);
        }
    } catch (Exception $e) {
        // Log the exception
        $server->close($fd);
    }
});

// To enable blacklisting later:
// $limiter->enableBlacklisting(true);

// To add an IP to the blacklist:
// $limiter->addToBlacklist('192.168.1.1');

// To remove an IP from the blacklist:
// $limiter->removeFromBlacklist('192.168.1.1');

$server->on('Request', function (Request $request, Response $response) {
    $response->header('Content-Type', 'text/plain');
    $response->end("Hello, Swoole!");
});

$server->start();
//// ClassSnapshot class
//class ClassSnapshot
//{
//    private static array $instances = [];
//
//    public static function register($instance): void
//    {
//        $className = get_class($instance);
//        if (!isset(self::$instances[$className])) {
//            self::$instances[$className] = [];
//        }
//        self::$instances[$className][] = $instance;
//    }
//
//    public static function flush()
//    {
//        foreach (self::$instances as $className => $instances) {
//            foreach ($instances as $instance) {
//                if (method_exists($instance, 'flush')) {
//                    $instance->flush();
//                }
//            }
//        }
//        self::$instances = [];
//    }
//}
//
//
//// Logger class (same as before)
//class Logger
//{
//    private static ?Logger $instance = null;
//    private $logFile;
//    private $logFilePath = 'log.txt';
//
//    private function __construct()
//    {
//        $this->openLogFile();
//        ClassSnapshot::register($this);
//    }
//
//    private function openLogFile(): void
//    {
//        $this->logFile = fopen($this->logFilePath, 'a');
//    }
//
//    public static function getInstance(): ?Logger
//    {
//        if (self::$instance === null) {
//            self::$instance = new self();
//        }
//
//        return self::$instance;
//    }
//
//    public function log($message): void
//    {
//        fwrite($this->logFile, $message . PHP_EOL);
//    }
//
//    public function flush()
//    {
//        fclose($this->logFile);
//        $this->openLogFile();
//    }
//}
//
//
//// Example usage in your application code
//$logger = Logger::getInstance();
//
//// Log a message
//$logger->log('This is a log message');
//
//// Log another message
//$logger->log('This is another log message');
//
//// In your Swoole HTTP server code
//$server = new Swoole\Http\Server('0.0.0.0', 9501);
//
//$server->on('request', function ($request, $response) {
//    // Flush or reset shared state
//    ClassSnapshot::flush();
//
//    // Process the request
//    $logger = Logger::getInstance();
//    $logger->log('Processing request: ' . $request->server['request_uri']);
//
//    // ... (handle the request)
//
//    $logger->log('Request processed successfully');
//    // ...
//});
//
//$server->start();


//
//use Swoole\Http\Request;
//use Swoole\Http\Response;
//use Xel\Async\SessionManager\SwooleSession;
//use Xel\Async\Gemstone\Csrf_Shield;
//// ? key
//$key = "dummykey";
//// ? Swoole Session
//$session = new SwooleSession();
//$session->__init();
//
//$data = [
//    'condition' => true,
//    'key' => "dummykey",
//    'expired' => 60, //in second,
//    'clear_rate' => 300 //in second
//];
//
//// ? Csrf Manager
//$csrfManager = new Csrf_Shield($session, $data);
//
//
//// ? process
//$http = new \Swoole\Http\Server('127.0.0.1', 9501);
//$http->set([
//    'document_root' => __DIR__,
//    'enable_static_handler' => true
//]);
//
//// Start the Swoole HTTP server
//$http->on('Start', function ($server){
//
//});
//
//$http->on('workerStart', function($http , $workerId) use ($session){
//    if ($workerId === 0) {
//        // Start a coroutine to handle session cleanup
//        \Swoole\Coroutine::create(function () use ($session) {
//            while (true) {
//                // Sleep for 5 seconds
//                \Swoole\Coroutine::sleep(5);
//
//                // Get the current session data
//                $data = $session->currentSession();
//
//                // Initialize a flag to track if any session was cleared
//                $sessionCleared = 0;
//                // Iterate over the session data
//                if($session->count() > 0){
//                    foreach ($data as $key => $value) {
//                        if ($value['expired'] <= time()) {
//                            // Delete the expired session
//                            $session->delete($key);
//                            $sessionCleared = 1;
//                        }else{
//                            $sessionCleared = 2;
//                        }
//                    }
//                }else{
//                    $sessionCleared = 0;
//                }
//
//
//                switch($sessionCleared){
//                    case 1 :
//                        echo "Already cleared and current session is : " . $session->count() . PHP_EOL;
//                        break;
//                    case 2 :
//                        echo "current session is : " . $session->count() . PHP_EOL;
//                        break;
//                    default:
//                        echo '[HTTP1-ADVANCED]: Empty (' . date('H:i:s') . ')', PHP_EOL;
//                        break;
//                }
//
//            }
//        });
//    }
//});
//
//$http->on('Request', function (Request $request, Response $response) use ($csrfManager, $key, $session) {
////    $whiteLits = [
////        'http://localhost:9501',
////        'http://localhost:9502',
////        'http://localhost:8080'
////
////    ];
//
//    var_dump($request->server);
//    $response->end('a');
//
////    // ? check
////    if( isset($request->header['origin'])){
////        $origin = $request->header['origin'];
////        if(in_array($origin, $whiteLits)){
////            // Add CORS headers
////            $response->header('Access-Control-Allow-Origin', $origin);
////            $response->header('Access-Control-Allow-Headers', 'Origin, Content-Type, Accept, X-CSRF-Token');
////            $response->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
////            $response->header('Access-Control-Expose-Headers', 'X-CSRF-Token'); // Add this line
////
////            if($request->server['request_method'] === 'OPTIONS'){
////                $response->header('Access-Control-Max-Age', '3600'); // Cache preflight request for 24 hours
////                $response->status(200);
////                return;
////            }
////        }else{
////            $response->setStatusCode(403, 'Forbiden Access');
////            $response->end('Forbiden access');
////        }
////    }else{
////          // Add CORS headers
////        $response->header('Access-Control-Allow-Origin', $request->header['host']);
////        $response->header('Access-Control-Allow-Headers', 'X-CSRF-Token, Content-Type');
////        $response->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
////    }
////
////    if ($request->server['request_uri'] === '/xel-csrf' && $request->server['request_method'] === 'GET') {
////        $csrfToken = $csrfManager->generateCSRFToken($key, 60); // Generate CSRF token with 60 seconds expiration
////        $response->header('X-CSRF-Token', $csrfToken);
////        $response->end('CSRF token generated');
////
////    } elseif ($request->server['request_uri'] === '/' && $request->server['request_method'] === 'GET') {
////            ob_start();
////            require __DIR__."/index.php";
////            $html = ob_get_clean();
////            $response->header('Content-Type', 'text/html');
////            $response->end($html);
////
////    }elseif ($request->server['request_uri'] === '/xss' && $request->server['request_method'] === 'GET') {
////
////        var_dump($request->get);
////    } elseif ($request->server['request_uri'] === '/test' && $request->server['request_method'] === 'POST') {
////        $requestToken = $request->header['x-csrf-token']; // Get CSRF token from the request header
////
////        var_dump($requestToken);
////
////        if ($csrfManager->validateToken($requestToken)) {
////            $requestBody = $request->getContent();
////            var_dump($requestBody);
////            $response->end('Request processed');
////        } else {
////            $response->status(403);
////            $response->end('Invalid CSRF token');
////        }
////    } else {
////        $response->status(404);
////        $response->end('Not found');
////    }
//});
//
//$http->start();






    // $table->set('1', ['name' => 'John', 'value' => time() + 15]);
    // $table->set('2', ['name' => 'Jane', 'value' => time() + 30]);
    // Clear the table every 30 seconds
    // \Swoole\Timer::tick(15000, function () use ($table) {
    //     // ? Check if the table is empty
    //     if ($table->count() > 0) {
    //         $currentTime = time();
    //         foreach ($table as $key => $row) {
    //             if($row['value'] <= $currentTime){
    //                 $table->del($key);
    //             }
    //         }
    //     }else{
    //         echo "Empty " . date('Y-m-d H:i:s') . "\n";
    //     }      
    // });


    // ? Table
// $table = new \Swoole\Table(1024);
// $table->column('name', \Swoole\Table::TYPE_STRING, 64);
// $table->column('value', \Swoole\Table::TYPE_INT, 16);
// $table->create();


// additional cors setup

/**
 * [  ]
 */