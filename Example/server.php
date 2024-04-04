<?php

use Swoole\Coroutine\Http\Client;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;
use Swoole\Process;

use Xel\Async\Http\Server\Server_v2;

require __DIR__ . "/../vendor/autoload.php";



//$server = new Server('0.0.0.0', 9508, SWOOLE_PROCESS);
//
//$server->on('request', function (Request $req, Response $res) use ($server){
//    $res->end('do');
//});
//
//$server->on(
//    'message',
//    function (Server $server, Frame $frame) {
//        $server->push($frame->fd, "Hello, {$frame->data}");
//    }
//);
//
//$process = new Process(
//    function () {
//        // To simulate task processing. Here we simply print out a message.
//        // In reality, a task queue system works like following:
//        //   1. Use some storage system (e.g., Redis) to store tasks dispatched from worker processes, cron jobs or
//        //      another source;
//        //   2. In the task processing processes, get tasks from the storage system, process them, then remove them once
//        //      done.
//        echo 'Task processed (in file ',  __FILE__, ').', PHP_EOL;
//        sleep(29);
//    }
//);
//
//
//$server->addProcess($process);
//
//$process = new Process(
//    function () {
//        while (true) { // @phpstan-ignore while.alwaysTrue
//            sleep(31);
//            echo 'Cron executed (in file ',  __FILE__, ').', PHP_EOL; // To simulate cron executions.
//        }
//    }
//);
//$server->addProcess($process);
//
//$server->start();


$config = [
    'api_server' => [
        'host' => 'http://localhost',
        'port' => 9501,
        'mode' => 2,
        'options' => [
            'worker_num' => 35,
            //            'daemonize' => 1,
            //            'http_gzip_level' => 9,

            /**Enable it when use mode 2*/

            /**Optional Config*/

            'open_tcp_nodelay' => true,
            'reload_async' => true,
            'max_wait_time' => 60,
            'enable_reuse_port' => true,
            'enable_coroutine' => true,
            'http_compression' => true,
            'enable_static_handler' => false,
            'buffer_output_size' => swoole_cpu_num() * 1024 * 1024,
        ],

    ],

    'ws_server' => [
        'host' => 'http://localhost',
        'port' => 9502,
        'mode' => 1,
        'options' => [
            'worker_num' => swoole_cpu_num(),
            //            'daemonize' => 1,
            //            'http_gzip_level' => 9,
            /**Optional Config*/

            'open_tcp_nodelay' => true,
            'reload_async' => true,
            'max_wait_time' => 60,
            'enable_reuse_port' => true,
            'enable_coroutine' => true,
            'http_compression' => true,
            'enable_static_handler' => false,
            'buffer_output_size' => swoole_cpu_num() * 1024 * 1024,
        ],
    ]
];
Server_v2::init($config);
$server = Server_v2::getServer();

// ? Worker Event
$server->on('workerStart', function () {});


// ? Http Server
$server->on('start', function () {});
$server->on('request', function (Request $request, Response $response) {
    $response->end('hello world');
});

// ? Asynchronous Task Handler
$server->on('task', function () {});

// ? Websocket Protocols
$server->on('open', function (Server $server, $Request){});

$server->on('message', function (Server $server, Frame $frame){});

$server->on('close', function (Server $server, $fd){});

$server->start();