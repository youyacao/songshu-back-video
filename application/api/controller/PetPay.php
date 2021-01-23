<?php
/**
All rights Reserved, Designed By www.youyacao.com
@Description:支付回调方法文件
@author:成都市一颗优雅草科技有限公司
@version 松鼠短视频系统-后端部分
注意：后端代码在获得授权之前通过其他非官方渠道获得代码均为侵权，禁止用于商业用途，否则将承担因此带来等版权纠纷。
需要商业用途或者定制开发等可访问songshu.youyacao.com   联系QQ:422108995 23625059584

 */

namespace app\api\controller;

use ptpaysdk\core\PtSdk;
use think\Controller;
use think\Log;

class PetPay extends Controller
{

    protected $pay_pet_user_id = '';
    protected $pay_pet_key = '';

    public function _initialize()
    {
        parent::_initialize();
        $payConfig = getPayConfig();
        $this->pay_pet_user_id = $payConfig['pay_pet_user_id'];
        $this->pay_pet_key = $payConfig['pay_pet_key'];
        if (empty($this->pay_pet_user_id) || empty($this->pay_pet_key)) {
            return error('秘钥不能为空');
        }
    }

    public function get(){
        $amount = request()->param('amount');
        $payType = request()->param('payType', 'wxpay');

        if (empty($amount)){
            return error("金额不能为空，请重试！");
        }

        if (!in_array($payType, ['wxpay', 'alipay'])) {
            return error("支付方式[wxpay, alipay]");
        }

        $user = session("user");
        if (empty($user)){
            return error("请先登录！");
        }


        $params = [];
        $params['uid']  = $user['id'];
        $params['shop_id']  = 0;
        $params['amount']   = $amount;
        $params['payType']  = $payType;
        $params['customerOrderTime'] = date('Y-m-d H:i:s');
        $params['ip'] = request()->ip();
        $params['appKey'] = $this->pay_pet_user_id;
        $order_id = Db('recharge_log')->insertGetId($params);

        $pay = new PtSdk($this->pay_pet_user_id, $this->pay_pet_key);
        $parameter = array(
            //支付方式 1.是微信，2是支付宝
            "type" => $payType == 'wxpay' ? 1:2,
            //商户订单号
            "payId" => $order_id,
            //自定义参数
            "param" => '充值',
            //金额
            "price" => $amount,
            "returnUrl" => request()->domain() . 'petPay/return',
            "notifyUrl" => request()->domain() . 'petPay/notify',
        );

        echo $pay->createOrder($parameter);
    }

    public function returnUrl()
    {
        exit("支付完成");
    }

    public function notify()
    {
        $param = request()->param();
        Log::record(json_encode($param));
        $pay = new PtSdk($this->pay_pet_user_id, $this->pay_pet_key);
        //验证签名
        if($pay->isSign())
        {
            //检查订单是否存在
            if($pay->isCheckOrder()){
                //检查订单是否支付
                if ($pay->checkOrderState()){
                    //签名验证成功,订单验证成功
                    //---------开始业务逻辑----------------
                    $param = request()->param();
                    Log::record(json_encode($param));
                    $order_id = (string)$param['payId'];
                    $price = (float)$param['price'];
                    $reallyPrice = (float)$param['reallyPrice'];
                    if (intval($price) != intval($reallyPrice)) {
                        Db('recharge_log')->where('id', $order_id)->update([
                            'status'    => 2,
                            'payTime'   => date('Y-m-d H:i:s'),
                            'remark'    => "支付金额不对,实际支付{$reallyPrice}"
                        ]);
                        exit('fail');
                    }
                    $order_info = Db('recharge_log')->where('id', $order_id)->find();
                    if ($order_info['status'] == '1') {
                        echo 'success';die;
                    }
                    Db('recharge_log')->where('id', $order_id)->update([
                        'status'    => 1,
                        'payTime'   => date('Y-m-d H:i:s'),
                        'remark'    => "支付完成，实际支付{$reallyPrice}"
                    ]);
                    //----------业务逻辑结束---------------
                    //告诉服务器已经收到通知
                    echo 'success';die;
                }else{
                    exit('fail');
                }
            }else{
                exit('fail');
            }

        }
        else
        {
            exit('fail');
        }
    }
}