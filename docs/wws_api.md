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
