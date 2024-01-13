<?php

use Xel\Async\Http\Applications;
use Xel\Devise\Service\RegisterService\Register;

require_once __DIR__ . "/../vendor/autoload.php";

/**
 * Launch Server
 */
$config = require __DIR__ . "/../config/config.php";

try {
    Applications::initialize($config)
        ::onEvent(Register::serviceProvider())
        ::run();
} catch (ReflectionException $e) {

    echo "error : ".$e->getMessage();
}

