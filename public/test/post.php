<?php
header("Content-type: text/html; charset=utf-8");

//必须使用命名空间
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;

$typeArr = array("jpg", "png", "gif");//允许上传文件格式

if (isset($_POST)) {
	$name = $_FILES['photo']['name'];
	$size = $_FILES['photo']['size'];
	$name_tmp = $_FILES['photo']['tmp_name'];
	if (empty($name)) {
		echo json_encode(array("error"=>"您还未选择图片"));
		exit;
	}
	$type = strtolower(substr(strrchr($name, '.'), 1)); //获取文件类型

	if (!in_array($type, $typeArr)) {
		echo json_encode(array("error"=>"清上传jpg,png或gif类型的图片！"));
		exit;
	}
	
	/* 
	判断上传图片的大小，演示效果用不到
	if ($size > (1000 * 1024)) {
		echo json_encode(array("error"=>"图片大小已超过1000KB！"));
		exit;
	} */

	$pic_name = date('YmdHis').rand(10000, 99999) . "." . $type;//图片名称

		
	// 用于签名的公钥和私钥
	$accessKey = 'ixqX1Oi2ZpffsfUoJM3vETzF_A9MR57SupV3rE39';
	$secretKey = 'QCgNdPJO-bnkkPNYxvw4rcXExO4Q3wFSl9guD0Jj';
		
	require './qiniu/autoload.php';
	// 初始化签权对象
	$auth = new Auth($accessKey, $secretKey);
		
	// 空间名  https://developer.qiniu.io/kodo/manual/concepts
	$bucket = 'bigniudouyin';
	
	// 生成上传Token
	$token = $auth->uploadToken($bucket);
		
	// 构建 UploadManager 对象
	$uploadMgr = new UploadManager();

		
	//上传
	list($ret, $err) = $uploadMgr->putFile($token, $pic_name, $name_tmp);
		
		
	if ($err !== null) {
		var_dump($err);
	} else {
		var_dump($ret);
		echo '图片地址为：<a target="_blank" href="http://on5sddezq.bkt.clouddn.com/'.$ret['key'].'">'.$ret['key'].'</a>';
	}
		
}