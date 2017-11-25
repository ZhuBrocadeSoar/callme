# callme

微信小程序“取个号”应用于高校食堂部分店家呼叫学生取餐。以下是开发日记和参考资料（可能偏向后端）。

该项目下目前包含了取个号的后台程序（php）

# 三端工作数据流
![图](http://on-img.com/chart_image/59edea77e4b07162476d91fb.png)

# 接口

https://callme.brocadesoar.cn/?

# 数据库

mysql\> USE callme;

![mysql> SHOW TABLES](https://github.com/ZhuBrocadeSoar/callme/blob/master/images/build/tables.png)

![mysql> DESC wxapp_info](https://github.com/ZhuBrocadeSoar/callme/blob/master/images/build/wxapp_info.png)

![mysql> DESC seller_list](https://github.com/ZhuBrocadeSoar/callme/blob/master/images/build/seller_list.png)

![mysql> DESC session_record](https://github.com/ZhuBrocadeSoar/callme/blob/master/images/build/session_record.png)

![mysql> DESC order_list](https://github.com/ZhuBrocadeSoar/callme/blob/master/images/build/order_list.png)

# 接口记录

 母接口 

https://callme.brocadesoar.cn/?

	- (Q00) login 

		- (Q01) 商家请求JSON

		{
			"query" : "login",
			"isseller" : "yes"
		}

			- (Q01R00) 登陆成功响应JSON

				{
					"loginSuccess" : "success",
					"sessionKey" : sessionKey
				}

			- (Q01R01) 接口错误(code换取openid和session_key时出错)响应JSON

			{
				"loginSuccess" : "fail",
				"failMsg" : "API Error"
			}

			- (Q01R02) 登陆错误(提交的code无效)响应JSON

			{
				"loginSuccess" : "fail",
				"failMsg" : "Login Error"
			}

			- (Q01R03) 商家匹配错误(商家的openid与数据库记录不匹配)响应JSON

			{
				"loginSuccess" : "fail",
				"failMsg" : "Seller Openid Error"
			}

		- (Q02) 吃客请求JSON

		{
			"query" : "login",
			"isseller" : "no"
		}

			- (Q02R00) 登陆成功响应JSON

			{
				"loginSuccess" : "success",
				"sessionKey" : sessionKey
			}

			- (Q02R01) 接口错误(code换取openid和session_key时出错)响应JSON

			{
				"loginSuccess" : "fail",
				"failMsg" : "API Error"
			}

			- (Q02R02) 登陆错误(提交的code无效)响应JSON

			{
				"loginSuccess" : "fail",
				"failMsg" : "Login Error"
			}

	- (Q03) 吃客请求商家列表请求JSON

	{
		"query" : "seller_list",
		"sessionKey" : sessionKey
	}

		- (Q03R00) 列表成功响应JSON

		{
			"seller_listSuccess" : "success",
			"count" : count,
			"list" : {
				list
			}
		}

		- (Q03R01) 列表错误(无列表)响应JSON

		{
			"seller_listSuccess" : "fail",
			"failMsg" : "Null List Error"
		}

	- (Q04) 吃客获取商家备注列表请求JSON

	{
		"query" : "menu",
		"sessionKey" : sessionKey,
		"sellerId" : sellerId
	}

		- (Q04R00) 备注列表响应JSON

		{
			"menuSuccess" : "success",
			"menuContent" : menuContant
		}

	- (Q05) 商家取号请求JSON

	{
		"query" : "fetch",
		"sessionKey" : sessionKey,
	}

		- (Q05R00) 取号成功响应JSON

		{
			"fetchSuccess" : "success",
			"marchSn" : marchSn
		}

		- (Q05R01) 取号错误响应JSON

		{
			"fetchSuccess" : "fail",
			"failMsg" : failMsg
		}

	- (Q06) 商家检查关联情况，备注请求JSON

	{
		"query" : "note",
		"sessionKey" : sessionKey,
		"marchSn" : marchSn
	}

		- (Q06R00) 返回两个列表，备注响应JSON

		{
			"noteSuccess" : "success",
			"noteContent" : noteContent
		}

	- (Q07) 买家输入备注，备注提交请求JSON

	{
		"query" : "push",
		"sessionKey" : sessionKey,
		"marchSn" : marchSn,
        "sellerId" : sellerId,
		"noteContent" : noteContent
	}

		- (Q07R00) 备注提交成功响应JSON

		{
			"pushSuccess" : "success"
		}

		- (Q07R01) 备注提交失败(号码不匹配)响应JSON

		{
			"pushSuccess" : "fail",
			"failMsg" : "Invalid Sn Error"
		}

		- (Q07R02) 备注提交失败(备注栏已被填写)响应JSON

		{
			"pushSuccess" : "fail",
			"failMsg" : "Taken Error"
		}

	- (Q08) 买家查餐请求JSON

	{
		"query" : "hungry",
		"sessionKey" : sessionKey,
		"marchSn" : marchSn,
        "sellerId" : sellerId
	}

		- (Q08R00) 餐完成响应JSON

		{
			"hungrySuccess" : "success"
		}

		- (Q08R01) 餐未完成响应JSON

		{
			"hungrySuccess" : "fail",
			"failMsg" : "Not Ready Yet"
		}

	- (Q09) 卖家叫号请求JSON

	{
		"query" : "call",
		"sessionKey" : sessionKey,
		"marchSn" : marchSn
	}

		- (Q09R00) 叫号成功响应JSON

		{
			"callSuccess" : "success"
		}

	- (Q10) 买家(有意识或无意识)结束订单请求JSON

	{
		"query" : "done",
		"sessionKey" : sessionKey,
		"marchSn" : marchSn,
        "sellerId" : sellerId
	}

		- (Q10R00) 结束订单成功响应JSON

		{
			"doneSuccess" : "success"
		}

	- (Q11) 管理员录入请求JSON

	{
		"query" : "signup",
		"sessionKey" : sessionKey,
		"tellNum" : tellNum
	}

		- (Q11R00) 录入成功响应JSON

		{
			"signupSuccess" : "success"
		}

	- (Q12) 商家提交信息

	{
		"query" : "info",
		"sessionKey" : sessionKey,
		""
	}


# 开发日记

+ 2017-11-11 00:33:45 将callme完全从wuaiwulu中剥离并开始做开发日记的编写，虽然任然实际上使用同一个服务器。

+ 2017-11-11 00:54:48 申请了callme.brocadesoar.cn的SSL域名认证

+ 2017-11-11 01:43:52 实现了callme.brocadesoar.cn的SSL域名认证,接口改为 https://callme.brocadesoar.cn/?

+ 2017-11-11 20:52:54 code换取openid和session_key的方法已经实现，识别用户的3rd_session=sha1(openid . session_key),商家的openid会以暗码记录在数据库中用以匹配。下一步开始对数据流的实现。

+ 2017-11-12 16:36:36 整理了接口，数据库session记录每10分钟删除一次大于1小时的记录

+ 2017-11-20 01:02:10 考虑了一下，必须把GET方式改为POST，现在有postman用于调试了，主要是sessionkey字段太敏感，容易被session劫持

+ 2017-11-20 03:36:58 完成基本所有数据流的接口并编号，更新了开发日记

+ 2017-11-20 04:31:58 下一步要首先要做的是商家入驻方面，图片的上传，菜单的设置等等

+ 2017-11-20 19:21:31 要把号码池改为每个商家单独号码池

+ 2017-11-24 20:52:00 将note请求改为传回两个列表

+ 2017-11-24 21:36:00 改为了独立号码池，每个号码池为1-99

# 参考

+ NameVirtualHost *:80

+ NameVirtualHost *:443

+ CREATE EVENT timeout30min ON SCHEDULE EVERY 1 MINUTE STARTS TIMESTAMP '2017-11-12 00:00:00' DO DELETE FROM session_record WHERE TIMEDIFF(NOW(), time_session) > '00:00:01';

