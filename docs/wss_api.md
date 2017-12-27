## WSS_API

Client

wss.send(           encodeURIComponent(JSON.stringify( JSON\_OBJECT ))    );

对象->字符串->转码->发送

console.log(        JSON.parse(decodeURIComponent( e.data ))              );

接收->解码->对象

Server

$connect->send(     urlencode( JSON\_OBJECT\_STRING )                     );

对象字符串->编码->发送

var\_dump(          json\_decode(urldecode( $data ))                      );

接收->解码->对象

### Who Are You

新建立的连接无openid记录，服务器对于这种时候的任何请求有如下消息推送

```javascript
{
    "push" : "whoareyou"
}
```

### Invalid Query

当客户端发送的消息不符合规则时，服务器有如下消息推送

```javascript
{
    "push" : "error",
    "msg" : "wrong query"
}

{
    "push" : "error",
    "msg" : "wrong format"
}
```

### Client

[hello](#hello)

[login](#login)

[accessToken](#accessToken)

#### hello

```javascript
{
    "query" : "hello"
}

{
    "push" : "hi"
}
```

### login

```javascript
{
    "query" : "login",
    "code" : code,
    "id" : id
}

{
    "push" : "login",
    "state" : "success"
}
```

### accessToken

```javascript
{
    "query" : "accessToken",
    "id" : id
}
```

