<?php
    //$_GET['query'] = "seller_list"; // 测试
    // 连接数据库
    $connToMysql = mysqli_connect("localhost", "nitmaker_cn", "nitmaker.cn", "callme");
    if(mysqli_connect_errno()){
        echo "Error: " . mysqli_connect_error();
    }
    // 处理请求
    if(isset($_GET['query']) /*&& isset($_GET['sessionkey'])*/){
        // 是不是login请求
        if($_GET['query'] == "login"){
            // 登陆请求合法
            $flagQueryErr = false;
        }else if(isset($_GET['sessionKey'])){
            // 包含sessionKey 合法，检查session有效
            $flagQueryErr = false;
        }else{
            // 不包含sessionKey 不合法
            $flagQueryErr = true;
        }
/*        $retval = mysqli_query($connToMysql, "SELECT openid, sessionkey, time_session FROM session_record WHERE sessionkey = " . $_GET['sessionkey']);
        $row = mysqli_fetch_array($retval, MYSQLI_NUM);
        if($row == NULL){
            echo "Error: need login";
        }else{
            $now = date("Y-m-d H:i:s", time());
            $datatimediff = date_diff($now, $row[2]);
            if($datatimediff['y'] > 0 || $datatimediff['m'] > 0 || $datatimediff['d'] > 0 || $datatimediff['h'] > 6){
                echo "Error: time out";
            }
        }
*/        if($_GET['query'] == "login"){ // 登陆请求
            // 决定使用哪个小程序信息
            if($_GET['isseller']){
                $idWxAppInfo = 2;
            }else{
                $idWxAppInfo = 1;
            }
            // 从数据库获取小程序信息
            $retval = mysqli_query($connToMysql, "SELECT wxappid, wxsecret FROM wxapp_info WHERE id_wxappInfo = " . $idWxAppInfo);
            $row = mysqli_fetch_array($retval, MYSQLI_NUM);
            // 设置code换取openid和session_key的API参数
            $wxappid = $row[0];
            $wxsecret = $row[1];
            $wxcode = $_GET['code'];
            $wxgrantType = "authorization_code";
            // curl 调用API
            $connToWxApi = curl_init();
            $urlWithGet = "https://api.weixin.qq.com/sns/jscode2session?appid=" . $wxappid . "&secret=" . $wxsecret . "&js_code=" . $wxcode . "&grant_type=" . $wxgrantType;
            curl_setopt($connToWxApi, CURLOPT_URL, $urlWithGet);
            curl_setopt($connToWxApi, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($connToWxApi, CURLOPT_HEADER, true);
            $response = curl_exec($connToWxApi);
            // 分割响应头只保留body的JSON
            $loginInfoJson = substr($response, curl_getinfo($connToWxApi, CURLINFO_HEADER_SIZE));
            // JSON 解码为数组
            $loginInfo = json_decode($loginInfoJson, true);
            // 判断是否为商家请求
            if($_GET['isseller'] == "yes"){
                // 查询数据库获得是否匹配openid
                $hashopenid = sha1($loginInfo['openid']);
                $retval = mysqli_query($connToMysql, "SELECT id_seller FROM seller_list WHERE hash_openid = '$hashopenid' ");
                $row = mysqli_fetch_array($retval, MYSQLI_NUM);
                if($row[0] != NULL){
                    $sellerJustice = true;
                    $flagIsseller = "1";
                    //$sessionR
                }else{
                    $sellerJustice = false;
                    $flagIsseller = "0";
                }
            }else{
                $sellerJustice = false;
                $flagIsseller = "0";
            }
            // 响应
            if($loginInfo == NULL){
                // API 错误
                $loginSuccess = "fail";
                $failMsg = "API Error";
                $resultArray = array('loginSuccess' => $loginSuccess, 'failMsg' => $failMsg);
            }else if(isset($loginInfo['errcode'])){
                // 登陆错误
                $loginSuccess = "fail";
                $failMsg = "Login Error";
                $resultArray = array('loginSuccess' => $loginSuccess, 'failMsg' => $failMsg);
            }else if($_GET['isseller'] == "yes" && $sellerJustice == false){
                // 商家id不匹配错误
                $loginSuccess = "fail";
                $failMsg = "Seller Openid Error";
                $resultArray = array('loginSuccess' => $loginSuccess, 'failMsg' => $failMsg, 'testOpenid' => $loginInfo['openid'], 'testHashOpenid' => sha1($loginInfo['openid']));
            }else{
                // 成功响应
                $loginSuccess = "success";
                // 生成3rd_session
                $sessionKey = sha1($loginInfo['openid'] . $loginInfo['session_key']);
                $resultArray = array('loginSuccess' => $loginSuccess, 'sessionKey' => $sessionKey, 'testOpenid' => $loginInfo['openid'], 'testHashOpenid' => sha1($loginInfo['openid']));
                // 存储session
                $retval = mysqli_query($connToMysql, "INSERT INTO session_record (sessionkey, time_session, flag_isseller)VALUES('$sessionKey', NOW(), '$flagIsseller')");
            }
            $retval = mysqli_query($connToMysql, "INSERT INTO session_record (3rd_session_key, time_session) VALUES (" . $sessionKey . ", NOW())");
            // 返回json
            // echo json_encode($resultArray);
        }else if($_GET['query'] == "seller_list"){ // 商家列表请求
            // 查询列表记录数量
            $retval = mysqli_query($connToMysql, "SELECT COUNT(*) FROM seller_list");
            if(!$retval){
                // 列表记录为0
                $seller_listSuccess = "fail";
                $failMsg = "Null List Error";
                $resultArray = array('seller_listSuccess' => $seller_listSuccess, 'failMsg' => $failMsg );
            }else{
                // 列表数量不为0
                $resultArray['seller_listSuccess'] = "success";
                // 查询列表记录数量(商家数量)
                $row = mysqli_fetch_array($retval, MYSQLI_NUM);
                $resultArray['count'] = $row[0];
                // 查询列表内容
                $retval = mysqli_query($connToMysql, "SELECT id_seller, name_seller, path_photo FROM seller_list");
                // 组成返回JSON
                $sellerArray = array();
                $i = '1';
                while($row = mysqli_fetch_array($retval, MYSQLI_NUM)){
                    $sellerArray[$i] = array("id" => $row[0], "name" => $row[1], "imageURL" => $row[2]);
                    $i++;
                }
                $resultArray['list'] = $sellerArray;
            }
            // echo json_encode($resultArray);
        }else if($GET['query'] == "good_list"){ // 货单请求
        }else if($GET['query'] == "fetch"){

        }else{
            echo "Error: 非法请求";
        }
        if($flagQueryErr){
            $resultArray = array('queryErr' => 'illegal query');
        }
        echo json_encode($resultArray);
    }
?>

