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
        if($_GET['query'] == "login"){ // (Q00) 登陆请求
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
        }else if($_GET['query'] == "seller_list"){ // (Q03) 商家列表请求
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
        }else if($_GET['query'] == "menu"){ // (Q04) 菜单请求
            $sellerId = $_GET['sellerId'];
            $sql = "SELECT json_menu FROM seller_list WHERE id_seller = $sellerId";
            $retval = mysqli_query($connToMysql, $sql);
            $row = mysqli_fetch_array($retval, MYSQLI_NUM);
            $resultArray = array();
            $resultArray['menuSuccess'] = 'success';
            $resultArray['menuContent'] = json_decode($row[0]);
        }else if($_GET['query'] == "fetch"){ // (Q05) 取号
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
                $sql1 = "SELECT id_seller FROM session_record WHERE sessionkey = '$sessionKey'";
                $retval = mysqli_query($connToMysql, $sql1);
                $row = mysqli_fetch_array($retval, MYSQLI_NUM);
                $id_seller = $row[0];
                $sql2 = "INSERT INTO order_list (id_order, session_key_seller, flag_done, id_seller) VALUES ($validSn, '$sessionKey', '$flag_done', $id_seller)";
                $retval = mysqli_query($connToMysql, $sql2);
                // $resultArray['testMsg'] = $sql1 . "____" . $sql2; // test;
            }else{
                $resultArray = array('fetchSuccess' => 'fail', 'failMsg' => 'No Sn Valid');
            }
        }else if($_GET['query'] == "note"){ // (Q06) 商家检查关联，获取备注
            /*
            $marchSn = $_GET['marchSn'];
            $sql = "SELECT note_order FROM order_list WHERE id_order = $marchSn";
            $retval = mysqli_query($connToMysql, $sql);
            $row = mysqli_fetch_array($retval, MYSQLI_NUM);
            if($row[0] != NULL){
                $resultArray = array('noteSuccess' => 'success', 'noteContent' => $row[0]);
            }else{
                $resultArray = array('noteSuccess' => 'fail', 'failMsg' => 'No Note Error');
            }
             */

            $notedList = array();
            $noted = 0;
            $unnotedList = array();
            $unnoted = 0;
            $sql = "SELECT id_order, note_order FROM order_list";
            $retval = mysqli_query($connToMysql, $sql);
            if($row = mysqli_fetch_array($retval, MYSQLI_NUM) != NULL){
                if($row[1] != NULL){
                    // 该记录有备注
                    $notedList[$noted] = array('marchSn' => $row[0], 'noteContent' => $row[1]);
                    $noted++;
                }else{
                    // 该记录无备注
                    $unnotedList[$unnoted] = array('marchSn' => $row[0], 'noteContent' => NULL);
                    $unnoted++;
                }
            }
            $orderList = array('notedList' => $notedList, 'unnotedList' => $unnotedList);
            $resultArray = array('noteSuccess' => 'success', 'orderList' => $orderList);
        }else if($_GET['query'] == "push"){ // (Q07) 买家推送备注
            $marchSn = $_GET['marchSn'];
            $noteContent = $_GET['noteContent'];
            $sql1 = "SELECT note_order FROM order_list WHERE id_order = $marchSn";
            $retval = mysqli_query($connToMysql, $sql1);
            $row = mysqli_fetch_array($retval, MYSQLI_NUM);
            if($row != NULL){
                // 有记录
                if($row[0] != NULL){
                    // 已经有备注
                    $resultArray = array('pushSuccess' => 'fail', 'failMsg' => 'Taken Error');
                }else{
                    // 没有备注
                    $sql2 = "UPDATE order_list SET note_order = '$noteContent' WHERE id_order = $marchSn";
                    $retval = mysqli_query($connToMysql, $sql2);
                    $mysqlierror = mysqli_error();
                    $resultArray = array('pushSuccess' => 'success' /*, 'testMsg1' => $sql2, 'testMsg2' => $retval, 'testMsg3' => $mysqlierror*/);
                }
            }else{
                // 无此记录
                $resultArray = array('pushSuccess' => 'fail', 'failMsg' => 'Invalid Sn Error');
            }
        }else if($_GET['query'] == "hungry"){ // (Q08) 买家查询是否可以取餐
            $marchSn = $_GET['marchSn'];
            $sql = "SELECT flag_done FROM order_list WHERE id_order = $marchSn";
            $retval = mysqli_query($connToMysql, $sql);
            $row = mysqli_fetch_array($retval, MYSQLI_NUM);
            if($row[0] == "1"){
                // 餐完成
                $resultArray = array('hungrySuccess' => 'success');
            }else{
                // 餐未完成
                $resultArray = array('hungrySuccess' => 'fail', 'failMsg' => 'Not Ready Yet');
            }
        }else if($_GET['query'] == "call"){ // (Q09) 卖家叫号
            $marchSn = $_GET['marchSn'];
            $sql = "UPDATE order_list SET flag_done = '1' WHERE id_order = $marchSn";
            $retval = mysqli_query($connToMysql, $sql);
            $resultArray = array('callSuccess' => 'success');
        }else if($_GET['query'] == "done"){ // (Q10) 买家有意识或无意识完成订单
            $marchSn = $_GET['marchSn'];
            $sql = "DELETE FROM order_list WHERE id_order = $marchSn";
            $retval = mysqli_query($connToMysql, $sql);
            $resultArray = array('doneSuccess' => 'success');
        }else{ // 未知的请求
            $flagQueryErr = true;
        }
        // $flagQueryErr = false; // test
        if($flagQueryErr){ // 检查请求是否有效
            $resultArray = array('queryErr' => 'Illegal query');
        }
        if(isset($sessionTimeOut)){ // 检查session是否过期
            if($sessionTimeOut == true){
                $resultArray = array('queryErr' => 'Session Time Out');
            }
        }
        echo json_encode($resultArray); // (Response) 响应
    }else{
        // 无请求
    }
?>

