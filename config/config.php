<?php
return [
    'api_server' => [
        'host' => 'http://localhost',
        'port' => 9501,
        'mode' => 2,
        'options' => [
            'worker_num' => 50,
            'enable_coroutine' => true,
            'dispatch_mode' => 1,
            'reload_async' => true,
        ],
    ],
];