<?php

use Workerman\Worker;
use Workerman\Lib\Timer;
require_once __DIR__ .  '/vendor/autoload.php';

define('HEARTBEAT_TIME', 600);
define('HEARTBEAT_CHECK_TIME', HEARTBEAT_TIME / 10);

$context = array(
    'ssl' => array(
        'local_cert' => '/etc/pki/tls/certs/2_callme.brocadesoar.cn.crt',
        'local_pk' => '/etc/pki/tls/private/3_callme.brocadesoar.cn.key',
        'verify_peer' => false
    )
);

$callme = new Worker("websocket://0.0.0.0:4431", $context);
$callme->transport = 'ssl';
$callme->count = 4;
$callme->name = 'callme_ws';
$callme->user = 'apache';

$callme->onWorkerStart = function($callme){
    Timer::add(HEARTBEAT_CHECK_TIME, function()use($callme){
        $time_now = time();
        foreach($callme->connections as $connection){
            // 为每一个connection 检查心跳
            // 有的connection尚未通信
            if(empty($connection->lastMessageTime)){
                $connection->lastMessageTime = $time_now;
                continue;
            }
            // 上次通信时间超过心跳间隔
            if($time_now - $connection->lastMessageTime > HEARTBEAT_TIME){
                $connection->close();
            }
        }
    });
};

$callme->onMessage = function($connection, $data){
    $connection->lastMessageTime = time();
    var_dump($data);
    var_dump(urldecode($data));
    var_dump(json_decode(urldecode($data)));
    $connection->send('time stamp = ' . $connection->lastMessageTime . '\t data from client = '. $data);
};

Worker::runAll();

?>
