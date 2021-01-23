<?php
$ptpay_config = array();
//派特支付的用户通讯秘钥
$ptpay_config['paykey']='4094f86f7850e76e546939ce99cbe7a2';
//派特支付的用户id
$ptpay_config['payUserID']='87038898593101';
//同步地址 替换为你的同步地址
$ptpay_config['returnUrl']='http://'.$_SERVER['HTTP_HOST'].'/ptpaysdk/pay_return.php';
//异步地址 替换为你的异步地址
$ptpay_config['notifyUrl']='http://'.$_SERVER['HTTP_HOST'].'/ptpaysdk/pay_notify.php';

$ptpay_config['payfile'] = 'http://'.$_SERVER['HTTP_HOST'].'/payPage/pay.php?orderid=';
//微信用户信息获取的地址
$ptpay_config['weChat_userInfo_returnUrl']='';
//是否返回页面,1是返回HTML,0是JSON数据
$ptpay_config['isHtml']='3';
//访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
$ptpay_config['transport'] = 'https://';
//派特支付的支付apiurl
$ptpay_config['apiurl'] = 'css.mtyhq.cn/createOrder';
//获取订单api
$ptpay_config['getOrderApiUrl'] = 'css.mtyhq.cn/getOrder';
//关闭订单api
$ptpay_config['closeOrderApiUrl']  = 'css.mtyhq.cn/closeOrder';
//获取微信用户信息
$ptpay_config['getWeChatInfoApiUrl']  = 'css.mtyhq.cn/wxLoginWithPt';

$ptpay_config['getWxInfo']  = 'css.mtyhq.cn/admin/getWxInfo';

$ptpay_config['version'] = '1.1';