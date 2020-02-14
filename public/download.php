<?php
/**
 * Notes: <br>
 * User:Now大牛 <br>
 * QQ:201309512 <br>
 * Date:2020-2-10<br>
 * Time:11:52:39 <br>
 *  <br>
 */
//判断设备是否是ios
if(get_device_type()=="ios"){
	//如果是ios直接跳转到苹果商店
	echo "正在下载...";
	header('location:https://apps.apple.com/cn/app/%E5%BE%A1%E5%A7%90%E5%85%AC%E7%A4%BE-%E6%89%93%E5%BC%80%E6%83%85%E8%B6%A3%E4%B9%8B%E9%97%A8/id1498012209');
}else{
	//判断是否是微信浏览器
	if(is_weixin()){
		//这里的响应头为关键代码，可根据自己逻辑做修改
		header("Content-Type: text/html; charset=UTF-8");
		echo file_get_contents('redirect.html');
	}else{
		echo "正在下载...";
		header('location:https://mp.eonmode.cn/xz/yujie.apk');
	}
}

//判断是否为微信内置浏览器
function is_weixin(){ 

	if ( strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false ) {
		return true;
	}  

	return false;
}
//获取设备类型
function get_device_type()
{
	 //全部变成小写字母
	 $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
	 $type = 'other';
	 //分别进行判断
	 if(strpos($agent, 'iphone') || strpos($agent, 'ipad'))
	{
	 $type = 'ios';
	 } 
	  
	 if(strpos($agent, 'android'))
	{
	 $type = 'android';
	 }
	 return $type;
}