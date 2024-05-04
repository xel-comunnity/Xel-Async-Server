<?php

require __DIR__."/../vendor/autoload.php";

use Swoole\Http\Request;
use Swoole\Http\Response;

// Start the Swoole HTTP server
$http = new \Swoole\Http\Server('127.0.0.1', 9502);

$http->on('Start', function ($server) {

});


$http->on('Request', function (Request $request, Response $response){
   if ($request->server['request_uri'] === '/' && $request->server['request_method'] === 'GET') {
            ob_start();
            require __DIR__ . "/index.php";
            $html = ob_get_clean();
            $response->header('Content-Type', 'text/html');
            $response->end($html);

    } else {
        $response->status(404);
        $response->end('Not found');
    }
});

$http->start();
