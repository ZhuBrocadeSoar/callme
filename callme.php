<?php

use Workerman\Worker;
use Workerman\Lib\Timer;
require_once __DIR__ .  '/vendor/autoload.php';

define('HEARTBEAT_TIME', 600);
define('HEARTBEAT_CHECK_TIME', HEARTBEAT_TIME / 10);
define('TESTMSG_TIME', 10);

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
                $connection->send(urlencode(json_encode(array('push' => 'timeOut'))));
                $connection->close();
            }
        }
    });
    /*
    Timer::add(TESTMSG_TIME, function()use($callme){
        foreach($callme->connections as $connection){
            $time_now_arr = array('timeStamp' => time());
            var_dump($time_now_arr);
            var_dump(json_encode($time_now_arr));
            var_dump(urlencode(json_encode($time_now_arr)));
            $connection->send(urlencode(json_encode($time_now_arr)));
        }
    });
     */
};

$callme->onMessage = function($connection, $query){
    $connection->lastMessageTime = time();
    $queryArr = json_decode(urldecode($query), true);
    if($queryArr != NULL){
        // 请求格式正确
        if(isset($queryArr['query'])){
            // 请求格式正确
            if($queryArr['query'] == 'hello'){
                // hello 请求
                $responseArr = array('push' => 'hi');
                $connection->send(urlencode(json_encode($responseArr)));
            }else{
                // 请求无效
                $responseArr = array('push' => 'error', 'msg' => 'wrong query');
                $connection->send(urlencode(json_encode($responseArr)));
            }
        }else{
            // 请求格式不正确
            $responseArr = array('push' => 'error', 'msg' => 'wrong format');
            $connection->send(urlencode(json_encode($responseArr)));
        }
    }else{
        // 请求格式不正确
        $responseArr = array('push' => 'error', 'msg' => 'wrong format');
        $connection->send(urlencode(json_encode($responseArr)));
    }
    // test
    // var_dump($queryArr);
    // $connection->send(urlencode(json_encode($queryArr)));
};

$callme->onClose = function($connection){
    var_dump('A connection closed');
    var_dump($connection);
};

Worker::runAll();

?>
