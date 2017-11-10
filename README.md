# callme

微信小程序“取个号”应用于高校食堂部分店家呼叫学生取餐。以下是开发日记和参考资料（可能偏向后端）。

该项目下目前包含了取个号的后台程序（php）

# 三端工作数据流
![图](http://on-img.com/chart_image/59edea77e4b07162476d91fb.png)

# 接口

https://callme.brocadesoar.cn/?

# 数据库

mysql\> USE callme;

mysql\> DESC seller_list;

| Field           | Type         | Null | Key | Default | Extra           |
|-----------------|--------------|------|-----|---------|-----------------|
| id\_seller      | int(11)      | NO   | PRI | NULL    | auto\_increment |
| name\_seller    | varchar(255) | NO   |     | NULL    |                 |
| index\_goodList | int(11)      | NO   |     | NULL    |                 |
| path\_photo     | varchar(255) | YES  |     | NULL    |                 |
| json\_menu      | text         | YES  |     | NULL    |                 |

mysql\> DESC session_record;

| Field         | Type         | Null | Key | Default | Extra           |
|---------------|--------------|------|-----|---------|-----------------|
| id\_session   | int(11)      | NO   | PRI | NULL    | auto\_increment |
| openid        | varchar(40)  | NO   |     | NULL    |                 |
| sessionkey    | varchar(128) | NO   |     | NULL    |                 |
| time\_session | datetime     | YES  |     | NULL    |                 |

mysql\> DESC wxapp_info;

| Field         | Type         | Null | Key | Default | Extra           |
|---------------|--------------|------|-----|---------|-----------------|
| id\_wxappInfo | int(11)      | NO   | PRI | NULL    | auto\_increment |
| wxappid       | varchar(20)  | NO   |     | NULL    |                 |
| wxsecret      | varchar(40)  | NO   |     | NULL    |                 |
| wxappname     | varchar(255) | NO   |     | NULL    |                 |


# 开发日记

+ 2017-11-11 00:33:45 将callme完全从wuaiwulu中剥离并开始做开发日记的编写，虽然任然实际上使用同一个服务器。

+ 2017-11-11 00:54:48 申请了callme.brocadesoar.cn的SSL域名认证

+ 2017-11-11 01:43:52 实现了callme.brocadesoar.cn的SSL域名认证,接口改为 https://callme.brocadesoar.cn/?

# 参考

+ NameVirtualHost *:80

+ NameVirtualHost *:443
