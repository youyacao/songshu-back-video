<?php

use ptpaysdk\core\PtSdk;
require_once dirname(__FILE__).'/core/PtSdk.php';
$request_parameter = get_notify_parameter();

$pay = new PtSdk();
//创建订单需要构建的参数
$parameter = array(
    //支付方式 1.是微信，2是支付宝
    "type" => '2',
    //商户订单号
    "payId" => build_order_no(),
    //自定义参数
    "param" => '测试',
    //金额
    "price" => '10',
    "returnUrl" => 'http://zyh5227.tpddns.cn:2020/OK.html',
    "notifyUrl" => 'http://zyh5227.tpddns.cn:2020/notifyUrlDemo.php'

);

//创建订单,会自动跳转页面
echo $pay->createOrder($parameter);





