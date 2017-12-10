## API

POST JSON https://callme.brocadesoar.cn/

### 请求基本规则

    request

    {
        "query" : queryStr,
        ...
    }

##### 对于合法的 queryStr

[login](#login)

[seller\_list](#seller\_list)

[menu](#menu)

[fetch](#fetch)

[note](#note)

[push](#push)

[hungry](#hungry)

[call](#call)

[done](#done)

[signup](#signup)

[info](#info)

[admin](#admin)

[renew](#renew)

[update](#update)

[qrcode](#qrcode)

    response

    {
        queryStrSuccess : "success",
        ...
    }

    {
        queryStrSuccess : "fail",
        "failMsg" : failMsg,
        ...
    }

##### 对于不合法的 queryStr

    response

    {
        "queryErr" : "Illegal query"
    }

#### 登陆请求

    request

    {
        "query" : "login",
        "isseller" : "yes" | "no",
        "code" : code
    }

    response

    {
        "loginSuccess" : "success" | "fail",
        ...
    }

#### 非登陆请求

    request

    {
        "query" : queryStr,
        "sessionKey" : sessionKey,
        ...
    }

    response

    {
        queryStrSuccess : "success" | "fail",
        ...
    }

    {
        "queryErr" : "Session Time Out"
    }

### login 

登陆，用code换取sessionKey

    request

    {
        "query" : "login",
        "isseller" : "yes",
        "code" : code
    }

    response

    {
        "loginSuccess" : "success",
        "sessionKey" : sessionKey,
        "balanceMon" : balanceMon
    }

    {
        "loginSuccess" : "fail",
        "failMsg" : "API Error" | "Login Error"
    }

    request

    {
        "query" : "login",
        "isseller" : "no",
        "code" : code
    }

    response

    {
        "loginSuccess" : "success",
        "sessionKey" :sessionKey
    }

    {
        "loginSuccess" : "fail",
        "failMsg" : "API Error" | "Login Error"
    }

### seller_list

商家列表

    request

    {
        "query" : "seller_list",
        "sessionKey" : sessionKey
    }

    response

    {
        "seller_listSuccess" : "success",
        "count" : count,
        "list" : [
            {
                "id" : sellerId,
                "name" : sellerName,
                "imageUrl" : imageUrl
            },
            ...
        ]
    }

    response

    {
        "seller_listSuccess" : "fail",
        "failMsg" : "Null list Error"
    }

### menu

获取商家的菜单

    request

    {
        "query" : "menu",
        "sessionKey" : sessionKey,
        "sellerId" : sellerId
    }

    response

    {
        "menuSuccess" : "success",
        "menuContent" : [
            menuStr1,
            ...
        ],
        "takenFlag" : "fail"
    }

    {
        "menuSuccess" : "success",
        "menuContent" : [
            menuStr1,
            ...
        ],
        "takenFlag" : "success",
        "takenSellerId" : takenSellerId,
        "takenSellerName" : takenSellerName,
        "takenMarchSn" : takenMarchSn,
        "takenMenuName" : takenMenuName
    }

    {
        "menuSuccess" : "fail",
        "failMsg" : "Illegal Seller Error";
    }

### fetch

取号

    request

    {
        "query" : "fetch",
        "sessionKey" : sessionKey
    }

    response

    {
        "fetchSuccess" : "success",
        "marchSn" : marchSn
    }

    {
        "fetchSuccess" : "fail",
        "failMsg" : "No Sn Valid" | "Out Of Duty Error"
    }

### note

商家获取备注关联情况

    request

    {
        "query" : "note",
        "sessionKey" : sessionKey
    }

    response

    {
        "noteSuccess" : "success",
        "sellerId" : sellerId,
        "orderList" : {
            "notedList" : [
                {
                    "marchSn" : marchSn,
                    "noteContent" : noteContent
                },
                ...
            ],
            "unnotedList" : [
                {
                    "marchSn" : marchSn,
                    "noteContent" : NULL
                },
                ...
            ]
        }
    }

### push

用户推送备注

    request

    {
        "query" : "push",
        "sessionKey" : sessionKey,
        "sellerId" : sellerId,
        "marchSn" : marchSn,
        "noteContent" : noteContent
    }

    response

    {
        "pushSuccess" : "success"
    }

    {
        "pushSuccess" : "fail",
        "failMsg" : "Taken Error" | "Invalid Sn Error"
    }

### hungry

    request

    {
        "query" : "hungry",
        "sessionKey" : sessionKey,
        "sellerId" : sellerId,
        "marchSn" : marchSn
    }

    response

    {
        "hungrySuccess" : "success"
    }

    {
        "hungrySuccess" : "fail",
        "failMsg" : "Not Ready Yet"
    }

### call

    request

    {
        "query" : "call",
        "sessionKey" : sessionKey,
        "marchSn" : marchSn
    }

    response

    {
        "callSuccess" : "success"
    }

### done

    request

    {
        "query" : "done",
        "sessionKey" : sessionKey
        "isseller" : "yes",
        "marchSn" : marchSn
    }

    response

    {
        "doneSuccess" : "success"
    }

    request

    {
        "query" : "done",
        "sessionKey" : sessionKey,
        "sellerId" : sellerId,
        "marchSn" : marchSn
    }

    response

    {
        "doneSuccess" : "success"
    }

### signup

    request

    {
        "query" : "signup",
        "sessionKey" : sessionKey,
        "telNum" : telNum,
        "sellerName" : sellerName,
        "personName" : personName
    }

    response

    {
        "signupSuccess" : "success"
    }

    {
        "signupSuccess" : "fail",
        "failMsg" : "Need Renew Error" | "Invalid Tel Error"
    }

### info

    request

    {
        "query" : "info",
        "sessionKey" : sessionKey
    }

    response

    {
        "infoSuccess" : "success",
        "sellerName" : sellerName,
        "imageUrl" : imageUrl,
        "menuList" : [
            menuStr1,
            ...
        ],
        "balanceMon" : balanceMon,
        "personName" : personName
    }

    {
        "infoSuccess" : "fail",
        "failMsg" : "Invalid Session Error"
    }

### admin

    request

    {
        "query" : "admin",
        "sessionKey" : sessionKey
    }

    response

    {
        "adminSuccess" : "success",
        "sellerList" : [
            {
                "sellerName" : sellerName,
                "personName" : personName,
                "telNum" : telNum,
                "balanceMon" : balanceMon
            },
            ...
        ]
    }

    {
        "adminSuccess" : "fail",
        "failMsg" : "Not Admin Error"
    }

### renew

    request

    {
        "query" : "renew",
        "sessionKey" : sessionKey,
        "telNum" : telNum,
        "term" : term
    }

    response

    {
        "renewSuccess" : "success"
    }

    {
        "renewSuccess" : "fail",
        "failMsg" : "Not Admin Error"
    }

### update

    request

    {
        "query" : "update",
        "sessionKey" : sessionKey,
        "sellerName" : sellerName,
        "imageName" : "sellerImage",
        "menuList" : menuListStr,
        "personName" : personName
    }

    response

    {
        "updateSuccess" : "success",
        "updateImageSuccess" : "success"
    }

    {
        "updateSuccess" : "success",
        "updateImageSuccess" : "fail",
        "failMsg" : "No Error" | "Unlink Error" | "Move 1 Error" | "Move 2 Error" | "Size Error" | "No File Uploaded"
    }

    {
        "updateSuccess" : "fail",
        "failMsg" : "Need Renew Error" | "Need Signup Error"
    }

### qrcode

    request

    {
        "query" : "qrcode",
        "sessionKey" : sessionKey
    }

    response

    {
        "qrcodeSuccess" : "success",
        "qrcodeImageUrl" : qrcodeImageUrl
    }

    {
        "qrcodeSuccess" : "fail",
        "failMsg" : "Invalid Session Error"
    }
