<?php
    //$_POST['query'] = "seller_list"; // 测试
    // 连接数据库
    $connToMysql = mysqli_connect("localhost", "nitmaker_cn", "nitmaker.cn", "callme");
    // 处理请求
    if(isset($_POST['query']) /*&& isset($_POST['sessionkey'])*/){
        // 是不是login请求
        $sessionTimeOut = false;
        if($_POST['query'] == 'login'){
            // 登陆请求合法
            $flagQueryErr = false;
        }else if(isset($_POST['sessionKey'])){
            // 包含sessionKey 合法，检查session有效
            $sessionKey = $_POST['sessionKey'];
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
        // 处理请求
        if($_POST['query'] == "login"){ // (Q00) 登陆请求
            // 决定使用哪个小程序信息
            if($_POST['isseller']){
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
            $wxcode = $_POST['code'];
            $wxgrantType = "authorization_code";
            // curl 调用API
            $connToWxApi = curl_init();
            $urlWithGet = "https://api.weixin.qq.com/sns/jscode2session?appid=" . $wxappid . "&secret=" . $wxsecret . "&js_code=" . $wxcode . "&grant_type=" . $wxgrantType;
            curl_setopt($connToWxApi, CURLOPT_URL, $urlWithGet);
            curl_setopt($connToWxApi, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($connToWxApi, CURLOPT_HEADER, true);
            $response = curl_exec($connToWxApi);
            // echo $response;
            // 分割响应头只保留body的JSON
            $loginInfoJson = substr($response, curl_getinfo($connToWxApi, CURLINFO_HEADER_SIZE));
            // JSON 解码为数组
            $loginInfo = json_decode($loginInfoJson, true);
            // 判断是否为商家请求
            if($_POST['isseller'] == "yes"){
                // 查询数据库获得是否匹配openid
                $hashopenid = sha1($loginInfo['openid']);
                $retval = mysqli_query($connToMysql, "SELECT id_seller FROM seller_list WHERE hash_openid = '$hashopenid' ");
                $row = mysqli_fetch_array($retval, MYSQLI_NUM);
                if($row[0] != NULL){
                    $sellerJustice = true;
                    $flagIsseller = "1";
                    $sellerId = $row[0];
                    // 查询余额
                    $sql = "SELECT mon_balance FROM seller_list WHERE id_seller = $sellerId";
                    $retval = mysqli_query($connToMysql, $sql);
                    $row = mysqli_fetch_array($retval, MYSQLI_NUM);
                    $balanceMon = $row[0];
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
            /*
            }else if($_POST['isseller'] == "yes" && $sellerJustice == false){
                // 商家id不匹配错误
                $loginSuccess = "fail";
                $failMsg = "Seller Openid Error";
                $resultArray = array('loginSuccess' => $loginSuccess, 'failMsg' => $failMsg);
            */
            }else{
                // 商家id匹配
                // 成功响应
                $loginSuccess = "success";
                // 生成3rd_session
                $sessionKey = sha1($loginInfo['openid']/* . $loginInfo['session_key']*/);
                $resultArray = array('loginSuccess' => $loginSuccess, 'sessionKey' => $sessionKey, 'balanceMon' => $balanceMon /*, 'testOpenid' => $loginInfo['openid'], 'testHashOpenid' => sha1($loginInfo['openid'])*/);
                // 存储session
                $retval = mysqli_query($connToMysql, "SELECT flag_isseller FROM session_record WHERE sessionKey = '$sessionKey' ");
                $row = mysqli_fetch_array($retval, MYSQLI_NUM);
                if($row != NULL){
                    // sessionKey 匹配 无需再记录
                }else{
                    // sessionKey 不匹配 需要记录
                    if($flagIsseller == "1"){
                        $retval = mysqli_query($connToMysql, "INSERT INTO session_record (sessionkey, time_session, flag_isseller, id_seller)VALUES('$sessionKey', NOW(), '$flagIsseller', '$sellerId')");
                    }else{
                        $retval = mysqli_query($connToMysql, "INSERT INTO session_record (sessionkey, time_session, flag_isseller)VALUES('$sessionKey', NOW(), '$flagIsseller')");
                    }
                }
            }
        }else if($_POST['query'] == 'seller_list'){ // (Q03) 商家列表请求
            // 查询列表记录数量
            $retval = mysqli_query($connToMysql, "SELECT COUNT(*) FROM seller_list WHERE mon_balance > 0");
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
                $retval = mysqli_query($connToMysql, "SELECT id_seller, name_seller, path_photo FROM seller_list WHERE mon_balance > 0");
                // 组成返回JSON
                $sellerArray = array();
                $i = 0;
                while($row = mysqli_fetch_array($retval, MYSQLI_NUM)){
                    $sellerArray[$i] = array("id" => $row[0], "name" => $row[1], "imageURL" => $row[2]);
                    $i++;
                }
                $resultArray['list'] = $sellerArray;
            }
            // echo json_encode($resultArray);
        }else if($_POST['query'] == 'menu'){ // (Q04) 菜单请求
            $sellerId = $_POST['sellerId'];
            $sessionKey = $_POST['sessionKey'];
            // 检查是否有未完成的订单
            $sql = "SELECT id_order, id_seller, sn_march, note_order  FROM order_list WHERE session_key_client = '$sessionKey'";
            $retval = mysqli_query($connToMysql, $sql);
            $row = mysqli_fetch_array($retval, MYSQLI_NUM);
            $takenFlag = false;
            $takenSellerId = NULL;
            $takenMarchSn = NULL;
            if($row != NULL){
                // 有订单
                $takenFlag = true;
                $takenSellerId = $row[1];
                $takenMarchSn = $row[2];
                $takenMenuName = $row[3];
                $sql = "SELECT name_seller FROM seller_list WHERE id_seller = $takenSellerId";
                $retval = mysqli_query($connToMysql, $sql);
                $row = mysqli_fetch_array($retval, MYSQLI_NUM);
                $takenSellerName = $row[0];
            }else{
                // 无订单
                $takenFlag = false;
            }
            $sql = "SELECT json_menu, name_seller FROM seller_list WHERE id_seller = $sellerId AND mon_balance > 0";
            $retval = mysqli_query($connToMysql, $sql);
            $row = mysqli_fetch_array($retval, MYSQLI_NUM);
            if($row != NULL){
                $menuContent = json_decode($row[0]);
                $sellerName = $row[1];
                foreach($menuContent as $k => $v){
                    $menuContent[$k] = urldecode($v);
                }
                $resultArray = array('menuSuccess' => 'success', 'menuContent' => $menuContent, 'sellerName' => $sellerName);
                if($takenFlag){
                    $resultArray['takenFlag'] = 'success';
                    $resultArray['takenSellerId'] = $takenSellerId;
                    $resultArray['takenSellerName'] = $takenSellerName;
                    $resultArray['takenMarchSn'] = $takenMarchSn;
                    $resultArray['takenMenuName'] = $takenMenuName;
                    $resultArray['takenSellerName'] = $takenSellerName;
                }else{
                    $resultArray['takenFlag'] = 'fail';
                }
            }else{
                $resultArray = array('menuSuccess' => 'fail', 'failMsg' => 'Illegal Seller Error');
            }
        }else if($_POST['query'] == "fetch"){ // (Q05) 取号
            // 获得商家id
            $sessionKey = $_POST['sessionKey'];
            $sql = "SELECT id_seller FROM session_record WHERE sessionkey = '$sessionKey'";
            $retval = mysqli_query($connToMysql, $sql);
            $row = mysqli_fetch_array($retval, MYSQLI_NUM);
            $sellerId = $row[0];
            // 获取商家注册日期
            $sql = "SELECT mon_balance FROM seller_list WHERE id_seller = $sellerId";
            $retval = mysqli_query($connToMysql, $sql);
            $row = mysqli_fetch_array($retval, MYSQLI_NUM);
            $balance = $row[0];
            // 查询数据库可用Sn
            $sql = "SELECT sn_march FROM order_list WHERE id_seller = $sellerId";
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
                for($i = 1; $i <= 99; $i++){
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
                if($i == 100){
                    // 无号码可用
                    $validSn = 0;
                }else{
                    // 可用号码 $i
                    $validSn = $i;
                }
            }
            // 响应
            if($balance == 0){
                // 可用月数余额不足
                $resultArray = array('fetchSuccess' => 'fail', 'failMsg' => 'Out Of Duty Error');
            }else if($validSn != 0){
                $resultArray = array('fetchSuccess' => 'success', 'marchSn' => $validSn);
                // 插入数据库
                $sessionKey = $_POST['sessionKey'];
                $flag_done = "0";
                $sql2 = "INSERT INTO order_list (sn_march, session_key_seller, flag_done, id_seller) VALUES ($validSn, '$sessionKey', '$flag_done', $sellerId)";
                $retval = mysqli_query($connToMysql, $sql2);
                // $resultArray['testMsg'] = $sql1 . "____" . $sql2; // test;
            }else{
                $resultArray = array('fetchSuccess' => 'fail', 'failMsg' => 'No Sn Valid');
            }
        }else if($_POST['query'] == "note"){ // (Q06) 商家检查关联，获取备注
            // 获得商家id
            $sessionKey = $_POST['sessionKey'];
            $sql = "SELECT id_seller FROM session_record WHERE sessionkey = '$sessionKey'";
            $retval = mysqli_query($connToMysql, $sql);
            $row = mysqli_fetch_array($retval, MYSQLI_NUM);
            $sellerId = $row[0];
            /*
            $marchSn = $_POST['marchSn'];
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
            $sql = "SELECT sn_march, note_order FROM order_list WHERE id_seller = $sellerId ORDER BY id_order";
            $retval = mysqli_query($connToMysql, $sql);
            $resultArray = array(); 
            // $i = 0; // test
            while(($row = mysqli_fetch_array($retval, MYSQLI_NUM)) != NULL){
                // $resultArray[$i] = $row; //test
                if($row[1] != NULL){
                    // 该记录有备注
                    // $notedList = array_merge($notedList, array('marchSn' => $row[0], 'noteContent' => $row[1]));
                    $notedList[$noted] = array('marchSn' => $row[0], 'noteContent' => $row[1]);
                    $noted++;
                }else{
                    // 该记录无备注
                    // $unnotedList = array_merge($unnotedList, array('marchSn' => $row[0], 'noteContent' => NULL));
                    $unnotedList[$unnoted] = array('marchSn' => $row[0], 'noteContent' => NULL);
                    $unnoted++;
                }
            }
            $orderList = array('notedList' => $notedList, 'unnotedList' => $unnotedList);
            // $resultArray = array('noteSuccess' => 'success', 'orderList' => $orderList);
            $resultArray['noteSuccess'] = 'success';
            $resultArray['orderList'] = $orderList; 
        }else if($_POST['query'] == "push"){ // (Q07) 买家推送备注
            $sessionKey = $_POST['sessionKey'];
            $sellerId = $_POST['sellerId'];
            $marchSn = $_POST['marchSn'];
            $noteContent = $_POST['noteContent'];
            $sql1 = "SELECT note_order FROM order_list WHERE sn_march = $marchSn AND id_seller = $sellerId";
            $retval = mysqli_query($connToMysql, $sql1);
            $row = mysqli_fetch_array($retval, MYSQLI_NUM);
            if($row != NULL){
                // 有记录
                if($row[0] != NULL){
                    // 已经有备注
                    $resultArray = array('pushSuccess' => 'fail', 'failMsg' => 'Taken Error');
                }else{
                    // 没有备注
                    $sql2 = "UPDATE order_list SET note_order = '$noteContent', session_key_client = '$sessionKey' WHERE sn_march = $marchSn AND id_seller = $sellerId";
                    $retval = mysqli_query($connToMysql, $sql2);
                    $mysqlierror = mysqli_error();
                    $resultArray = array('pushSuccess' => 'success' /*, 'testMsg1' => $sql2, 'testMsg2' => $retval, 'testMsg3' => $mysqlierror*/);
                }
            }else{
                // 无此记录
                $resultArray = array('pushSuccess' => 'fail', 'failMsg' => 'Invalid Sn Error');
            }
        }else if($_POST['query'] == "hungry"){ // (Q08) 买家查询是否可以取餐
            $sellerId = $_POST['sellerId'];
            $marchSn = $_POST['marchSn'];
            $sql = "SELECT flag_done FROM order_list WHERE sn_march = $marchSn AND id_seller = $sellerId";
            $retval = mysqli_query($connToMysql, $sql);
            $row = mysqli_fetch_array($retval, MYSQLI_NUM);
            if($row[0] == "1"){
                // 餐完成
                $resultArray = array('hungrySuccess' => 'success');
            }else{
                // 餐未完成
                $resultArray = array('hungrySuccess' => 'fail', 'failMsg' => 'Not Ready Yet');
            }
        }else if($_POST['query'] == "call"){ // (Q09) 卖家叫号
            // 获得商家id
            $sessionKey = $_POST['sessionKey'];
            $sql = "SELECT id_seller FROM session_record WHERE sessionkey = '$sessionKey'";
            $retval = mysqli_query($connToMysql, $sql);
            $row = mysqli_fetch_array($retval, MYSQLI_NUM);
            $sellerId = $row[0];
            $marchSn = $_POST['marchSn'];
            $sql = "UPDATE order_list SET flag_done = '1' WHERE sn_march = $marchSn AND id_seller = $sellerId";
            $retval = mysqli_query($connToMysql, $sql);
            $resultArray = array('callSuccess' => 'success');
        }else if($_POST['query'] == "done"){ // (Q10) 买家有意识或无意识完成订单
            $sellerId = $_POST['sellerId'];
            $marchSn = $_POST['marchSn'];
            $sql = "DELETE FROM order_list WHERE sn_march = $marchSn AND id_seller = $sellerId";
            $retval = mysqli_query($connToMysql, $sql);
            $resultArray = array('doneSuccess' => 'success');
        }else if($_POST['query'] == 'signup'){
            $sessionKey = $_POST['sessionKey'];
            $telNum = $_POST['telNum'];
            $sql = "SELECT mon_balance FROM seller_list WHERE tel_banding = '$telNum'";
            $retval = mysqli_query($connToMysql, $sql);
            $row = mysqli_fetch_array($retval, MYSQLI_NUM);
            if($row != NULL){
                // 有记录
                if($row[0] == 0){
                    // 需要续费
                    $resultArray = array('signupSuccess' => 'fail', 'failMsg' => 'Need Renew Error');
                }else{
                    // 匹配成功
                    $resultArray = array('signupSuccess' => 'success');
                    $sql = "UPDATE seller_list SET hash_openid = '$sessionKey' WHERE tel_banding = '$telNum'";
                    $retval = mysqli_query($connToMysql, $sql);
                }
            }else{
                // 无记录
                $resultArray = array('signupSuccess' => 'fail', 'failMsg' => 'Invalid Tel Error');
            }
        }else if($_POST['query'] == 'info'){ // (Q12) 商家提交信息请求
            $sessionKey = $_POST['sessionKey'];
            // 获取商家信息
            $sql = "SELECT name_seller, path_photo, json_menu, mon_balance, name_person FROM seller_list WHERE hash_openid = '$sessionKey'";
            $retval = mysqli_query($connToMysql, $sql);
            $row = mysqli_fetch_array($retval, MYSQLI_NUM);
            if($row != NULL){
                $sellerName = $row[0];
                $imageUrl = $row[1];
                $menuList = json_decode($row[2]);
                foreach($menuList as $key => $value){
                    $menuList[$key] = urldecode($value);
                }
                $balanceMon = $row[3];
                $personName = $row[4];
                $resultArray = array('infoSuccess' => 'success', 'sellerName' => $sellerName, 'imageUrl' => $imageUrl, 'menuList' => $menuList, 'balanceMon' => $balanceMon, 'personName' => $personName);
            }else{
                $resultArray = array('infoSuccess' => 'fail', 'failMsg' => 'Invalid Session Error');
            }
        }else if($_POST['query'] == 'admin'){ // (Q13) 登陆管理员请求
            $sessionKey = $_POST['sessionKey'];
            $sql = "SELECT id_admin FROM admin_list WHERE hash_openid = '$sessionKey'";
            $retval = mysqli_query($connToMysql, $sql);
            $row = mysqli_fetch_array($retval, MYSQLI_NUM);
            if($row != NULL){
                $resultArray = array('adminSuccess' => 'success');
            }else{
                $resultArray = array('adminSuccess' => 'fail', 'failMsg' => 'Not Admin Error');
            }
        }else if($_POST['query'] == 'renew'){ // (Q14) 管理员续费请求
            $sessionKey = $_POST['sessionKey'];
            $telNum = $_POST['telNum'];
            $term = $_POST['term'];
            // 查询资格
            $sql = "SELECT id_admin FROM admin_list WHERE hash_openid = '$sessionKey'";
            $retval = mysqli_query($connToMysql, $sql);
            $row = mysqli_fetch_array($retval, MYSQLI_NUM);
            if($row != NULL){
                // 是管理员
                $resultArray = array('renewSuccess' => 'success');
                // renew process
                // 判断是否是新注册的
                $sql = "SELECT id_seller FROM seller_list WHERE tel_banding = '$telNum'";
                $retval = mysqli_query($connToMysql, $sql);
                $row = mysqli_fetch_array($retval, MYSQLI_NUM);
                if($row != NULL){
                    // 续费的
                    $sql = "UPDATE seller_list SET mon_balance = mon_balance + $term WHERE tel_banding = '$telNum'";
                }else{
                    // 注册的
                    $sql = "INSERT INTO seller_list (tel_banding, mon_balance) VALUES ('$telNum', $term)";
                }
                $retval = mysqli_query($connToMysql, $sql);
                $row = mysqli_fetch_array($retval, MYSQLI_NUM);
            }else{
                // 不是管理员
                $resultArray = array('renewSuccess' => 'fail', 'failMsg' => 'Not Admin Error');
            }
        }else if($_POST['query'] == 'update'){
            $sessionKey = $_POST['sessionKey'];
            $sellerName = $_POST['sellerName'];
            $imageName = $_POST['imageName'];
            $imageName = 'sellerImage';
            $menuListStr = $_POST['menuList'];
            $menuListArray = explode(",", $menuListStr);
            foreach($menuListArray as $key => $value){
                $menuListArray[$key] = urlencode($value);
            }
            $menuList = json_encode($menuListArray/*, JSON_FORCE_OBJECT*/);
            $personName = $_POST['personName'];
            // 检查商家id
            $sql = "SELECT id_seller, mon_balance FROM seller_list WHERE hash_openid = '$sessionKey'";
            $retval = mysqli_query($connToMysql, $sql);
            $row = mysqli_fetch_array($retval, MYSQLI_NUM);
            if($row != NULL){
                // sessionkey匹配
                $sellerId = $row[0];
                $balanceMon = $row[1];
                if($balanceMon > 0){
                    // 还有余额
                    // 检查是否上传图片
                    if(is_uploaded_file($_FILES['sellerImage']['tmp_name'])){
                        // 上传了文件
                        // 检查图片大小 和保存操作
                        if($_FILES['sellerImage']['size'] <= (512 * 1024)){
                            // 大小符合
                            // 保存名
                            $saveName = $sellerId . '.png';
                            // 检查存在性并保存
                            if(file_exists('/var/www/html/callme/images/seller/' . $saveName)){
                                // 删除已存在
                                if(!unlink('/vat/www/html/callme/images/seller/' . $saveName)){
                                    $imageSavedFlag2 = 1;
                                }else{
                                    $imageSavedFlag2 = 0;
                                    // 保存
                                    if(!move_uploaded_file($_FILES['sellerImage']['tmp_name'], '/var/www/html/callme/images/seller/' . $saveName)){
                                        $imageSavedFlag2 = 2;
                                    }else{
                                        $imageSavedFlag2 = 0;
                                    }
                                }
                            }else{
                                // 保存 
                                if(!move_uploaded_file($_FILES['sellerImage']['tmp_name'], '/var/www/html/callme/images/seller/' . $saveName)){
                                    $imageSavedFlag2 = 3;
                                }else{
                                    $imageSavedFlag2 = 0;
                                }
                            }
                            $imageSavedFlag = $imageSavedFlag2;
                        }else{
                            // 大小不符合
                            $imageSavedFlag = 4;
                        }
                        if($imageSavedFlag){
                            $resultArray = array('updateSuccess' => 'success', 'updateImageSuccess' => 'success');
                        }else{
                            $imageSavedErrors = array('No Error', 'Unlink Error', 'Move 1 Error', 'Move 2 Error', 'Size Error');
                            $resultArray = array('updateSuccess' => 'success', 'updateImageSuccess' => 'fail', 'failMsg' => $imageSavedErrors);
                        }
                    }else{
                        // 未上传图片
                        $resultArray = array('updateSuccess' => 'success', 'updateImageSuccess' => 'fail', 'failMsg' => 'No File Uploaded');
                    }
                    // 保存其他记录
                    $imageUrlTmp = 'https://callme.brocadesoar.cn/images/seller/' . $sellerId . '.png';
                    $sql = "UPDATE seller_list SET name_seller = '$sellerName', path_photo = '$imageUrlTmp', json_menu = '$menuList', name_person = '$personName'  WHERE id_seller = $sellerId";
                    $retval = mysqli_query($connToMysql, $sql);
                }else{
                    // 没有余额
                    $resultArray = array('updateSuccess' => 'fail', 'failMsg' => 'Need Renew Error');
                }
            }else{
                // sessionKey不匹配
                $resultArray = array('updateSuccess' => 'fail', 'failMsg' => 'Need Signup Error');
            }
        }else if($_POST['query'] == 'qrcode'){
            $sessionKey = $_POST['sessionKey'];
            $sql = "SELECT id_seller FROM seller_list WHERE hash_openid = '$sessionKey'";
            $retval = mysqli_query($connToMysql, $sql);
            $row = mysqli_fetch_array($retval, MYSQLI_NUM);
            if($row[0] != NULL){
                $sellerId = $row[0];
                // 获取微信小程序信息
                $sql = "SELECT wxappid, wxsecret FROM wxapp_info WHERE id_wxappInfo = 1";
                $retval = mysqli_query($connToMysql, $sql);
                $row = mysqli_fetch_array($retval, MYSQLI_NUM);
                $wxappid = $row[0];
                $wxsecret = $row[1];
                $wxgrantType = 'client_credential';
                // 获取token
                // curl 调用API
                $connToWxApi = curl_init();
                $urlWithGet = "https://api.weixin.qq.com/cgi-bin/token?appid=" . $wxappid . "&secret=" . $wxsecret . "&js_code=" . $wxcode . "&grant_type=" . $wxgrantType;
                curl_setopt($connToWxApi, CURLOPT_URL, $urlWithGet);
                curl_setopt($connToWxApi, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($connToWxApi, CURLOPT_HEADER, false);
                $response = curl_exec($connToWxApi);
                // JSON 解码为数组
                $tokenInfo = json_decode($response, true);
                // var_dump($tokenInfo);
                $token = $tokenInfo['access_token'];
                // var_dump($token);
                $url = "https://api.weixin.qq.com/wxa/getwxacode?access_token=$token";
                // 本地图片保存
                $fp = tmpfile();
                var_dump($fp);
                $flagFwrite = fwrite($fp, 'test msg00000\n');
                var_dump($flagFwrite);
                $statOfFp = fstat($fp);
                var_dump($statOfFp);
                $flagFread = fread($fp, $statOfFp[7]);
                var_dump($flagFread);
                // echo "FILE: " . $localUrl;
                // 获取二维码
                $connToWxApi = curl_init();
                $pathWithGet = 'pages/qu/qu?sellerId=' . strval($sellerId);
                $width = 430;
                $auto_color = false;
                // $line_color = (object)array('r' => '0', 'g' => '0', 'b' => '0');
                // $line_color = json_encode(array('r' => '0', 'g' => '0', 'b' => '0'));
                $line_color = array('r' => '0', 'g' => '0', 'b' => '0');
                $postData = json_encode(array('path' => $pathWithGet, 'width' => $width, 'auto_color' => $auto_color, 'line_color' => $line_color));
                // var_dump($postData);
                curl_setopt($connToWxApi, CURLOPT_URL, $url);
                curl_setopt($connToWxApi, CURLOPT_RETURNTRANSFER, false);
                curl_setopt($connToWxApi, CURLOPT_HEADER, false);
                curl_setopt($connToWxApi, CURLOPT_POST, true);
                curl_setopt($connToWxApi, CURLOPT_POSTFIELDS, $postData);
                // curl_setopt($connToWxApi, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($connToWxApi, HTTPHEADER, array(
                    'Content-Type:application/json',
                    'Content-Length:' . strlen($postData)
                ));
                // curl_setopt($connToWxApi, CURLOPT_FILE, $fp);
                $response = curl_exec($connToWxApi);
                // var_dump($response);
                $resultArray = $response;
                // var_dump($resultArray);
                // 响应
                header('Content-Type:image/jpeg');
                header('Content-Length:' . strlen($resultArray));
                // readfile($localUrl);
                // header("Location:$url");
                /*$response = curl_exec($connToWxApi);
                // echo $response;
                // 分割响应头只保留body的JSON
                $tokenInfoJson = substr($response, curl_getinfo($connToWxApi, CURLINFO_HEADER_SIZE));
                // JSON 解码为数组
                 */
            }else{
                $resultArray = json_encode(array('qrcodeSuccess' => 'fail', 'failMsg' => 'Invalid Session Error'));
            }
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
        if($_POST['query'] == 'qrcode'){
            // echo $resultArray;
        }else{
            echo json_encode($resultArray/*, JSON_FORCE_OBJECT*/); // (Response) 响应
        }
    }/*else if(isset($_POST['query'])){ // post请求
        // 是不是login请求
        $sessionTimeOut = false;
        if($_POST['query'] == "login"){
            // 登陆请求合法
            $flagQueryErr = false;
        }else if(isset($_POST['sessionKey'])){
            // 包含sessionKey 合法，检查session有效
            $sessionKey = $_POST['sessionKey'];
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
        // 处理请求
        if($_POST['query'] == "update"){
        }
    }*/
?>

