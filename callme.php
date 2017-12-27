<?php

$accessToken = array(1 => array('accessToken' => 'TOKEN', 'expiresIn' => 7200, 'timeStamp' => time()),
                     2 => array('accessToken' => 'TOKEN', 'expiresIn' => 7200, 'timeStamp' => time()));

$wxAppId = 'WXAPPID';
$wxSecret = 'WXSECRET';

function updateAccessToken($id){
    // 更新accessToken
    /*
    $connToMysql = new mysqli("localhost", "nitmaker_cn", "nitmaker.cn", "callme");
    $stmt = $connToMysql->prepare("SELECT wxappid, wxsecret FROM wxapp_info WHERE id_wxappInfo = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($wxAppId, $wxSecret);
    if($stmt->fetch()){
     */
        $wxGrantType = 'client_credential';
        // cURL 获取 accessToken
        $connToWxApi = curl_init();
        $urlWithGet = "https://api.weixin.qq.com/cgi-bin/token?appid=" . $GLOBALS['wxAppId'] . "&secret=" . $GLOBALS['wxSecret'] . "&grant_type=" . $wxGrantType;
        curl_setopt($connToWxApi, CURLOPT_URL, $urlWithGet);
        curl_setopt($connToWxApi, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($connToWxApi, CURLOPT_HEADER, false);
        $tokenInfo = json_decode(curl_exec($connToWxApi), true);
        if(isset($tokenInfo['errcode'])){
            // api错误
            var_dump($tokenInfo);
            return 1;
        }else{
            $GLOBALS['accessToken'][$id]['accessToken'] = $tokenInfo['access_token'];
            $GLOBALS['accessToken'][$id]['expiresIn'] = $tokenInfo['expires_in'];
            $GLOBALS['accessToken'][$id]['timeStamp'] = time();
            return 0;
        }
        $GLOBALS['accessToken'][$id]['accessToken'];
        /*
    }else{
        return 2;
    }
    $stmt->close();
    $connToMysql->close();
         */
}

function arr2msg($arr){
    return urlencode(json_encode($arr));
}

function msg2arr($msg){
    return json_decode(urldecode($msg), true);
}

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
                $connection->send(arr2msg(array('push' => 'timeOut')));
                $connection->close();
            }
        }
    });
    $connToMysql = new mysqli("localhost", "nitmaker_cn", "nitmaker.cn", "callme");
    $stmt = $connToMysql->prepare("SELECT wxappid, wxsecret FROM wxapp_info WHERE id_wxappInfo = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($GLOBALS['wxAppId'], $GLOBALS['wxSecret']);
    $stmt->fetch();
    $stmt->close();
    $connToMysql->close();
};

$callme->onConnect = function($connection){
    $connection->connToMysql = new mysqli("localhost", "nitmaker_cn", "nitmaker.cn", "callme");
};

$callme->onMessage = function($connection, $query){
    $connection->lastMessageTime = time();
    $queryArr = msg2arr($query);
    if($queryArr != NULL && $queryArr['query'] == 'login'){
        // 登陆请求
        // 换取openid
        $code = $queryArr['code'];
        // 记录openid
        $openid = 'test'; // test
        $connection->openid = $openid;
        $responseArr = array('push' => 'login', 'state' => 'success');
        $connection->send(arr2msg($responseArr));
    }else if(isset($connection->openid)){
        // 有openid
        if($queryArr != NULL){
            // 请求格式正确
            if(isset($queryArr['query'])){
                // 请求格式正确
                if($queryArr['query'] == 'hello'){
                    // hello 请求
                    $responseArr = array('push' => 'hi');
                    $connection->send(arr2msg($responseArr));
                }else if($queryArr['query'] == 'accessToken'){
                    updateAccessToken($queryArr['id']);
                    var_dump($GLOBALS['accessToken']);
                    $id = $queryArr['id'];
                    $responseArr = array('push' => 'accessToken', 'id' => $id, 'info' => $GLOBALS['accessToken'][$id]);
                    $connection->send(arr2msg($responseArr));
                }else{
                    // 请求无效
                    $responseArr = array('push' => 'error', 'msg' => 'wrong query');
                    $connection->send(arr2msg($responseArr));
                }
            }else{
                // 请求格式不正确
                $responseArr = array('push' => 'error', 'msg' => 'wrong format');
                $connection->send(arr2msg($responseArr));
            }
        }else{
            // 请求格式不正确
            $responseArr = array('push' => 'error', 'msg' => 'wrong format');
            $connection->send(arr2msg($responseArr));
        }
    }else{
        $responseArr = array('push' => 'whoareyou');
        $connection->send(arr2msg($responseArr));
    }
    // test
    // var_dump($queryArr);
    // $connection->send(urlencode(json_encode($queryArr)));
};

$callme->onClose = function($connection){
    $connection->connToMysql->close();
    var_dump('A connection closed');
    var_dump($connection);
};

Worker::runAll();

?>
