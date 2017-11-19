<?php
    //$_GET['query'] = "seller_list"; // 测试
    // 连接数据库
    $connToMysql = mysqli_connect("localhost", "nitmaker_cn", "nitmaker.cn", "callme");
    // 处理请求
    if(isset($_GET['query']) /*&& isset($_GET['sessionkey'])*/){
        // 是不是login请求
        $sessionTimeOut = false;
        if($_GET['query'] == "login"){
            // 登陆请求合法
            $flagQueryErr = false;
        }else if(isset($_GET['sessionKey'])){
            // 包含sessionKey 合法，检查session有效
            $sessionKey = $_GET['sessionKey'];
            $retval = mysqli_query($connToMysql, "SELECT flag_isseller FROM session_record WHERE sessionKey = '$sessionKey' ");
            $row = mysqli_fetch_array($retval, MYSQLI_NUM);
            if($row != NULL){
                // sessionKey 匹配
            }else{
                // sessionKey 不匹配
                $sessionTimeOut = true;
            }
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
                    $idSeller = $row[0];
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
                $sessionKey = sha1($loginInfo['openid']/* . $loginInfo['session_key']*/);
                $resultArray = array('loginSuccess' => $loginSuccess, 'sessionKey' => $sessionKey, 'testOpenid' => $loginInfo['openid'], 'testHashOpenid' => sha1($loginInfo['openid']));
                // 存储session
                $retval = mysqli_query($connToMysql, "SELECT flag_isseller FROM session_record WHERE sessionKey = '$sessionKey' ");
                $row = mysqli_fetch_array($retval, MYSQLI_NUM);
                if($row != NULL){
                    // sessionKey 匹配 无需再记录
                }else{
                    // sessionKey 不匹配 需要记录
                    if($flagIsseller == "1"){
                        $retval = mysqli_query($connToMysql, "INSERT INTO session_record (sessionkey, time_session, flag_isseller, id_seller)VALUES('$sessionKey', NOW(), '$flagIsseller', '$idSeller')");
                    }else{
                        $retval = mysqli_query($connToMysql, "INSERT INTO session_record (sessionkey, time_session, flag_isseller)VALUES('$sessionKey', NOW(), '$flagIsseller')");
                    }
                }
            }
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
            // 查询数据库可用Sn
            $sql = "SELECT id_order FROM order_list";
            $retval = mysqli_query($connToMysql, $sql);
            $j = 0;
            $unvalidSn = array();
            while($row = mysqli_fetch_array($retval, MYSQLI_NUM)){
                $unvalidSn[$j] = $row[0];
                $j++;
            }
            if(empty($unvalidSn)){
                // 无记录，001号可用
                $validSn = 1;
            }else{
                // 有记录，查询下一个可用号码
                for($i = 1; $i <= 999; $i++){
                    // 第$i号是否可用
                    for($j = 0; $j < count($unvalidSn); $j++){
                        if($unvalidSn[$j] == $i){
                            // 第$i号不可用
                            break;
                        }
                    }
                    if($j == count($unvalidSn)){
                        // 完整查询，第$i号可用
                        break;
                    }
                }
                if($i == 1000){
                    // 无号码可用
                    $validSn = 0;
                }else{
                    // 可用号码 $i
                    $validSn = $i;
                }
            }
            // 响应
            if($validSn != 0){
                $resultArray = array('fetchSuccess' => 'success', 'marchSn' => $validSn);
                // 插入数据库
                $sessionKey = $_GET['sessionKey'];
                $flag_done = "0";
                $retval = mysqli_query($connToMysql, "SELECT id_seller FROM session_record WHERE sessionkey = '$sessionKey'");
                $row = mysqli_fetch_array($connToMysql, MYSQLI_NUM);
                $id_seller = $row[0];
                $sql = "INSERT INTO order_list (id_order, session_key_seller, flag_done, id_seller) VALUES ('$i', '$sessionkey', '$flag_done', '$id_seller')";
            }else{
                $resultArray = array('fetchSuccess' => 'fail', 'failMsg' => 'No Sn Valid');
            }
        }else{
            // 未定义的请求
            $flagQueryErr = true;
        }
        // $flagQueryErr = false; // test
        if($flagQueryErr){
            $resultArray = array('queryErr' => 'Illegal query');
        }
        if(isset($sessionTimeOut)){
            if($sessionTimeOut == true){
                $resultArray = array('queryErr' => 'Session Time Out');
            }
        }
        echo json_encode($resultArray);
    }
?>

