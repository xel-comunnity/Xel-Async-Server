<?php

use Xel\Async\Http\Applications;

require_once __DIR__ . "/../../vendor/autoload.php";

/**
 * Launch Server
 */
$config = require __DIR__ . "/config/config.php";
$service = require __DIR__."/service/serviceRegister.php";

$app = new Applications();
try {
  $app
      ->initialize($config)
      ->onEvent($service)
      ->run();
} catch (ReflectionException $e) {
    echo "error : ".$e->getMessage();
}
