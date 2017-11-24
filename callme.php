<?php

use Workerman\Worker;
require_once __DIR__ .  '/Workerman/Autoload.php';

$ws_worker = new Worker("websocket://0.0.0.0:2000");

$ws_worker->count = 4;

$ws_worker->onMessage = function($connection, $data){
    $connection->send('hello', $data);
};

Worker::runAll();

?>
