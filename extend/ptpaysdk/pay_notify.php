<?php

use ptpaysdk\core\PtSdk;
require_once dirname(__FILE__).'/core/PtSdk.php';
$pay = new PtSdk();
//验证签名
if($pay->isSign())
{
        //签名验证成功,订单验证成功
        //---------开始业务逻辑----------------
        $request_data =get_notify_parameter();

        //----------业务逻辑结束---------------
        //告诉服务器已经收到通知
        echo 'success';
}
else
{
    exit('fail');
}
