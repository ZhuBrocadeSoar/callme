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
| path\_photo     | varchar(255) | YES  |     | NULL    |                 |
| json\_menu      | text         | YES  |     | NULL    |                 |
| hash_openid     | char(40)     | NO   |     | NULL    |                 |

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

# 接口记录

 母接口 

https://callme.brocadesoar.cn/?

	- login 

		- 商家请求JSON

		{
			"query" : "login",
			"isseller" : "yes"
		}

			- 登陆成功响应JSON

				{
					"loginSuccess" : "success",
					"sessionKey" : sessionKey
				}

			- 接口错误(code换取openid和session_key时出错)响应JSON

			{
				"loginSuccess" : "fail",
				"failMsg" : "API Error"
			}

			- 登陆错误(提交的code无效)响应JSON

			{
				"loginSuccess" : "fail",
				"failMsg" : "Login Error"
			}

			- 商家匹配错误(商家的openid与数据库记录不匹配)响应JSON

			{
				"loginSuccess" : "fail",
				"failMsg" : "Seller Openid Error"
			}

		- 吃客请求JSON

		{
			"query" : "login",
			"isseller" : "no"
		}

			- 登陆成功响应JSON

			{
				"loginSuccess" : "success",
				"sessionKey" : sessionKey
			}

			- 接口错误(code换取openid和session_key时出错)响应JSON

			{
				"loginSuccess" : "fail",
				"failMsg" : "API Error"
			}

			- 登陆错误(提交的code无效)响应JSON

			{
				"loginSuccess" : "fail",
				"failMsg" : "Login Error"
			}

	- 吃客请求商家列表请求JSON

	{
		"query" : "seller_list",
		"sessionKey" : sessionKey
	}

		- 列表成功响应JSON

		{
			"seller_listSuccess" : "success",
			"count" : count,
			"list" : {
				list
			}
		}

		- 列表错误(无列表)响应JSON

		{
			"seller_listSuccess" : "fail",
			"failMsg" : "Null List Error"
		}

	- 商家取号请求JSON

	{
		"query" : "fetch",
		"sessionKey" : sessionKey,
	}

		- 取号成功响应JSON

		{
			"fetchSuccess" : "success",
			"marchSn" : marchSn
		}

		- 取号错误响应JSON
		{
			"fetchSuccess" : "fail",
			"failMsg" : failMsg
		}

	- 商家检查关联情况，备注请求JSON
	{
		"query" : "note",
		"sessionKey" : sessionKey,
		"marchSn" : marchSn
	}

		- 关联成功，备注响应JSON
		{
			"noteSuccess" : "success",
			"noteContent" : noteContent
		}

		- 关联失败，尚无备注响应JSON
		{
			"noteSuccess" : "fail",
			"failMsg" : "No Note Error"
		}

	- 买家输入备注，备注提交请求JSON
	{
		"query" : "push",
		"sessionKey" : sessionKey,
		"marchSn" : marchSn,
		"noteContent" : noteContent
	}

		- 备注提交成功响应JSON
		{
			"pushSuccess" : "success"
		}

		- 备注提交失败(号码不匹配)响应JSON
		{
			"pushSuccess" : "fail",
			"failMsg" : "Invalid Sn Error"
		}

		- 备注提交失败(备注栏已被填写)响应JSON
		{
			"pushSuccess" : "fail",
			"failMsg" : "Taken Error"
		}

	- 买家查餐请求JSON
	{
		"query" : "hungry",
		"sessionKey" : sessionKey,
		"marchSn" : marchSn
	}

		- 餐完成响应JSON
		{
			"hungrySuccess" : "success"
		}

		- 餐未完成响应JSON
		{
			"hungrySuccess" : "fail",
			"failMsg" : "Not Ready Yet"
		}

	- 卖家叫号请求JSON
	{
		"query" : "call",
		"sessionKey" : sessionKey,
		"marchSn" : marchSn
	}

		- 叫号成功响应JSON
		{
			"callSuccess" : "success"
		}


# 开发日记

+ 2017-11-11 00:33:45 将callme完全从wuaiwulu中剥离并开始做开发日记的编写，虽然任然实际上使用同一个服务器。

+ 2017-11-11 00:54:48 申请了callme.brocadesoar.cn的SSL域名认证

+ 2017-11-11 01:43:52 实现了callme.brocadesoar.cn的SSL域名认证,接口改为 https://callme.brocadesoar.cn/?

+ 2017-11-11 20:52:54 code换取openid和session_key的方法已经实现，识别用户的3rd_session=sha1(openid . session_key),商家的openid会以暗码记录在数据库中用以匹配。下一步开始对数据流的实现。

+ 2017-11-12 16:36:36 整理了接口，数据库session记录每10分钟删除一次大于1小时的记录

+ 2017-11-20 01:02:10 考虑了一下，必须把GET方式改为POST，现在有postman用于调试了，主要是sessionkey字段太敏感，容易被session劫持

# 参考

+ NameVirtualHost *:80

+ NameVirtualHost *:443

+ CREATE EVENT timeout30min ON SCHEDULE EVERY 1 MINUTE STARTS TIMESTAMP '2017-11-12 00:00:00' DO DELETE FROM session_record WHERE TIMEDIFF(NOW(), time_session) > '00:00:01';

