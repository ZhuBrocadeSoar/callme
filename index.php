<?php
    //$_GET['query'] = "seller_list"; // 测试
    // 连接数据库
    $connToMysql = mysqli_connect("localhost", "nitmaker_cn", "nitmaker.cn", "callme");
    if(mysqli_connect_errno()){
        echo "Error: " . mysqli_connect_error();
    }
    // 处理请求
    if(isset($_GET['query']) /*&& isset($_GET['sessionkey'])*/){
        // 检查session
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
            // 验证登陆态
            if($_GET['isseller']){
                $idWxAppInfo = 2;
            }else{
                $idWxAppInfo = 1;
            }
            $retval = mysqli_query($connToMysql, "SELECT wxappid, wxsecret FROM wxapp_info WHERE id_wxappInfo = " . $idWxAppInfo);
            $row = mysqli_fetch_array($retval, MYSQLI_NUM);
            $wxappid = $row[0];
            $wxsecret = $row[1];
            $wxcode = $_GET['code'];
            // echo json_encode($_GET['code']);
            $wxgrantType = "authorization_code";
            $connToWxApi = curl_init();
            $urlWithGet = "https://api.weixin.qq.com/sns/jscode2session?appid=" . $wxappid . "&secret=" . $wxsecret . "&js_code=" . $wxcode . "&grant_type=" . $wxgrantType;
            curl_setopt($connToWxApi, CURLOPT_URL, $urlWithGet);
            curl_setopt($connToWxApi, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($connToWxApi, CURLOPT_HEADER, true);
            $response = curl_exec($connToWxApi);
            $loginInfoJson = substr($response, curl_getinfo($connToWxApi, CURLINFO_HEADER_SIZE));
            $loginInfo = json_decode($loginInfoJson, true);
            echo json_encode($loginInfo); // test
            if($_GET['isseller'] == "yes"){
                $sql = 'SELECT id_seller FROM seller_list WHERE hash_openid = \"' . sha1($loginInfo['openid']) . '\"';
                // $retval = mysqli_query($connToMysql, "SELECT id_seller FROM seller_list WHERE hash_openid = " . sha1($loginInfo['openid'] ));
                $retval = mysql_query($connToMysql, $sql);
                $row = mysqli_fetch_array($retval, MYSQLI_NUM);
                // echo json_encode($row); // test
                if($row[0] != NULL){
                    $sellerJustice = true;
                    //$sessionR
                }else{
                    $sellerJustice = false;
                }
            }else{
                $sellerJustice = false;
            }
            if($loginInfo == NULL){
                $loginSuccess = "fail";
                $failMsg = "API Error";
                $resultArray = array('loginSuccess' => $loginSuccess, 'failMsg' => $failMsg);
            }else if(isset($loginInfo['errcode'])){
                $loginSuccess = "fail";
                $failMsg = "Login Error";
                $resultArray = array('loginSuccess' => $loginSuccess, 'failMsg' => $failMsg);
            }else if($_GET['isseller'] == "yes" && $sellerJustice == false){
                $loginSuccess = "fail";
                $failMsg = "Seller Openid Error";
                $resultArray = array('loginSuccess' => $loginSuccess, 'failMsg' => $failMsg, 'testOpenid' => $loginInfo['openid'], 'testHashOpenid' => sha1($loginInfo['openid']));
            }else{
                $loginSuccess = "success";
                // 生成3rd_session
                $sessionKey = sha1($loginInfo['openid'] . $loginInfo['session_key']);
                $resultArray = array('loginSuccess' => $loginSuccess, 'sessionKey' => $sessionKey, 'testOpenid' => $loginInfo['openid'], 'testHashOpenid' => sha1($loginInfo['openid']));
            }
            // 存储session
            //
            $retval = mysqli_query($connToMysql, "INSERT INTO session_record (3rd_session_key, time_session) VALUES (" . $sessionKey . ", NOW())");
            // 返回json
            echo json_encode($resultArray);
        }else if($_GET['query'] == "seller_list"){ // 商家列表请求
            $retval = mysqli_query($connToMysql, "SELECT COUNT(*) FROM seller_list");
            if(!$retval){
                echo "Error: " . mysqli_error();
            }
            $row = mysqli_fetch_array($retval, MYSQLI_NUM);
            $resultArray['count'] = $row[0];
            $retval = mysqli_query($connToMysql, "SELECT id_seller, name_seller, path_photo FROM seller_list");
            if(!$retval){
                echo "Error: " . mysqli_error();
            }
            $sellerArray = array();
            $i = '1';
            while($row = mysqli_fetch_array($retval, MYSQLI_NUM)){
                $sellerArray[$i] = array("id" => $row[0], "name" => $row[1], "imageURL" => $row[2]);
                $i++;
            }
            $resultArray['list'] = $sellerArray;
            echo json_encode($resultArray);
        }else if($GET['query'] == "good_list"){ // 货单请求
        }else{
            echo "Error: 非法请求";
        }
    }
?>

