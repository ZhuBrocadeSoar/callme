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
    "seller_list"
    "menu"
    "fetch"
    "note"
    "push"
    "hungry"
    "call"
    "done"
    "signup"
    "info"
    "admin"
    "renew"
    "update"
    "qrcode"

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
    }
