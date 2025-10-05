<?php
define("TOKEN", "t8Uf8Q4lysg64");
//填写自己的token,跟小程序后台填的一样即可
if (isset($_GET["echostr"])) {
	//校验服务器地址URL
	valid();
} else {
	responseMsg();
}
function valid() {
	$echoStr = $_GET["echostr"];
	if(checkSignature()) {
		header("content-type:text");
		echo $echoStr;
		exit;
	} else {
		echo $echoStr."+++".TOKEN;
		exit;
	}
}
function checkSignature() {
	$signature = $_GET["signature"];
	$timestamp = $_GET["timestamp"];
	$nonce = $_GET["nonce"];
	$token = TOKEN;
	$tmpArr = array($token, $timestamp, $nonce);
	sort($tmpArr, SORT_STRING);
	$tmpStr = implode( $tmpArr );
	$tmpStr = sha1( $tmpStr );
	if( $tmpStr == $signature ) {
		return true;
	} else {
		return false;
	}
}
function responseMsg() {
	$postStr = file_get_contents("php://input");
	//此处推荐使用file_get_contents(‘php://input’)获取后台post过来的数据
	if (!empty($postStr) && is_string($postStr)) {
		$postArr = json_decode($postStr,true);
		$fp = fopen('test.txt', 'w');
		fwrite($fp, $postStr);
		fclose($fp);
		if(!empty($postArr["MsgType"]) && $postArr["Content"] == "1") {
			//用户发送1,回复公众号二维码
			$fromUsername = $postArr["FromUserName"];
			//发送者openid
			$imgurl = "/300-300.png";
			//公众号二维码,相对路径,修改为自己的
			$media_id = getMediaId($imgurl);
			//获取图片消息的media_id
			$data=array("touser"=>$fromUsername,
			"msgtype"=>"image",
			"image"=>array("media_id"=>$media_id)
			);
			$json = json_encode($data,JSON_UNESCAPED_UNICODE);
			//php5.4+
			requestAPI($json);
		} elseif(!empty($postArr["MsgType"]) && $postArr["Content"] == "2") {
			//用户发送2,回复图文链接
			$fromUsername = $postArr["FromUserName"];
			//发送者openid
			$data = array(
			"touser"=>$fromUsername,
			"msgtype"=>"link",
			"link"=>array( //修改下面几项为自己的
			"title"=>"最火壁纸",
			"description"=>"2020最火手机壁纸",
			"url"=>"https://bizhi.tarfar.com/index/index/bdauth",
			"thumb_url"=>"https://bizhi.tarfar.com/wp-content/uploads/2020/03/logo.png",
			)
			);
			$json = json_encode($data,JSON_UNESCAPED_UNICODE);
			//php5.4+
			requestAPI($json);
		} elseif(!empty($postArr["MsgType"]) && $postArr["Content"] == "3") {
			//用户发送3,回复文字
			$fromUsername = $postArr["FromUserName"];
			//发送者openid
			$content = "你好,回复1关注公众号，回复2获取官网链接";
			//修改为自己需要的文字
			$data=array(
			"touser"=>$fromUsername,
			"msgtype"=>"text",
			"text"=>array("content"=>$content)
			);
			$json = json_encode($data,JSON_UNESCAPED_UNICODE);
			//php5.4+
			requestAPI($json);
		} elseif(!empty($postArr["MsgType"]) && $postArr["Content"] == "百度") {
			//用户发送3,回复文字
			$fromUsername = $postArr["FromUserName"];
			//发送者openid
			$content = "你好,回复1关注公众号，回复2获取官网链接";
			//修改为自己需要的文字
			$data=array(
			"touser"=>$fromUsername,
			"msgtype"=>"text",
			"text"=>array("content"=>$content)
			);
			$data = array(
			"touser"=>$fromUsername,
			"msgtype"=>"link",
			"link"=>array( //修改下面几项为自己的
			"title"=>"绑定百度网盘",
			"description"=>"点击打开绑定百度网盘",
			"url"=>"https://dy.shxhyszx.com/index/index/bdauth?openid=".$fromUsername,
			"thumb_url"=>"https://dss0.bdstatic.com/-0U0bnSm1A5BphGlnYG/tam-ogel/8f7d48ec351c0e413d60ee07fd2b44ee_88_88.png",
			)
			);
			$json = json_encode($data,JSON_UNESCAPED_UNICODE);
			//php5.4+
			requestAPI($json);
		} elseif(!empty($postArr["MsgType"]) && $postArr["MsgType"] == "image") {
			//用户发送图片,这里示例为回复他公众号二维码
			$fromUsername = $postArr["FromUserName"];
			//发送者openid
			$imgurl = "/300-300.png";
			//公众号二维码,相对路径,修改为自己的
			$media_id = getMediaId($imgurl);
			//获取图片消息的media_id
			$data=array(
			"touser"=>$fromUsername,
			"msgtype"=>"image",
			"image"=>array("media_id"=>$media_id)
			);
			$json = json_encode($data,JSON_UNESCAPED_UNICODE);
			//php5.4以上版本才可使用
			requestAPI($json);
		} elseif($postArr["MsgType"] == "event" && $postArr["Event"]=="user_enter_tempsession") {
			//用户进入客服后马上回复，现在已失效,需要用户先发过消息
			$fromUsername = $postArr["FromUserName"];
			$content = "你好,回复‘百度’获取百度网盘授权地址";
			$data=array(
			"touser"=>$fromUsername,
			"msgtype"=>"text",
			"text"=>array("content"=>$content)
			);	
			$json = json_encode($data,JSON_UNESCAPED_UNICODE);
			//php5.4+
			requestAPI($json);
		} elseif($postArr["MsgType"] !== "event") {
			//用户发送其他内容,引导加客服
			$fromUsername = $postArr["FromUserName"];
			//发送者openid
			$imgurl = "/miniapp300-300.png";
			//客服微信二维码,相对路径
			$media_id = getMediaId($imgurl);
			//获取图片消息的media_id
			$data=array(
			"touser"=>$fromUsername,
			"msgtype"=>"image",
			"image"=>array("media_id"=>$media_id)
			);
			$json = json_encode($data,JSON_UNESCAPED_UNICODE);
			//php5.4+
			requestAPI($json);
		} else {
			exit;
		}
	} else {
		echo "empty";
		exit;
	}
}
function requestAPI($json) {
	$access_token = get_accessToken();
	/*
* POST发送https请求客服接口api
*/
	$url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=".$access_token;
	//以’json’格式发送post的https请求
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_POST, 1);
	// 发送一个常规的Post请求
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
	if (!empty($json)) {
		curl_setopt($curl, CURLOPT_POSTFIELDS,$json);
	}
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	//curl_setopt($curl, CURLOPT_HTTPHEADER, $headers );
	$output = curl_exec($curl);
	if (curl_errno($curl)) {
		echo "Errno".curl_error($curl);
		//捕抓异常
	}
	curl_close($curl);
	if($output == 0) {
		echo "success";
		exit();
	}
}
/* 调用微信api，获取access_token，有效期7200s*/
function get_accessToken() {
	$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wx40bd73e3ad5e0fca&secret=4cfe6076f13695ebb905bb6c61ceac8b";
	//替换成自己的小程序id和secret
	@$weixin = file_get_contents($url);
	@$jsondecode = json_decode($weixin);
	@$array = get_object_vars($jsondecode);
	$token = $array["access_token"];
	return $token;
}
//获取上传图片的media_id
function getMediaId($imgurl) {
	$token=get_accessToken();
	$url = "https://api.weixin.qq.com/cgi-bin/media/upload?access_token={$token}&type=image";
	// echo $url;
	$ch1 = curl_init();
	$timeout = 10;
	$real_path = "{$_SERVER['DOCUMENT_ROOT']}$imgurl";
	//自动转成图片文件绝对路径,如果图片发送失败,检查PHP的$_SERVER[‘DOCUMENT_ROOT’的配置
	// echo $real_path;
	$data = array("media" => new CURLFile("{$real_path}"));
	//php5.6(含)以上版本使用此方法
	// var_dump($data);
	curl_setopt($ch1, CURLOPT_URL, $url);
	curl_setopt($ch1, CURLOPT_POST, 1);
	curl_setopt($ch1, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch1, CURLOPT_CONNECTTIMEOUT, $timeout);
	curl_setopt($ch1, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch1, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch1, CURLOPT_POSTFIELDS, $data);
	$result = curl_exec($ch1);
	// echo $result;
	curl_close($ch1);
	if($result) {
		$result = json_decode($result, true);
		return $result["media_id"];
	} else {
		return null;
	}
}

