<?php


namespace app\api\controller;


use think\Cache;
use think\Controller;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\Log;
use think\response\Json;

class CodePay extends Controller
{
 
    public function index(){
        $sid = request()->param('sid');
        $payType = request()->param('payType');
        $shop_info = Db('vip_shop')->where('id', $sid)->find();
        if (empty($shop_info)){
            return error("参数不对，请重试！");
        }
        $user = session("user");
        if (empty($user)){
            return error("请先登录！");
        }
        $payConfig = getPayConfig();
        if ($payType == 'alipay') {
            $payType = 1;
        } else {
            $payType = 3;
        }
        $params = [];
        $params['uid']  = $user['id'];
        $params['shop_id']  = $sid;
        $params['amount']   = $shop_info['price'];
        $params['payType']  = $payType;
        $params['customerOrderTime'] = date('Y-m-d H:i:s');
        $params['ip'] = request()->ip();
        $params['appKey'] = $payConfig['pay_memberid'];
        $order_id = Db('vip_log')->insertGetId($params);

        $data = array(
            "id"        => $payConfig['pay_memberid'],//你的码支付ID
            "pay_id"    => $order_id, //唯一标识 可以是用户ID,用户名,session_id(),订单ID,ip 付款后返回
            "type"      => $payType, //1支付宝支付 3微信支付 2QQ钱包
            "price"     => $shop_info['price'],//金额100元
            "param"     => "",//自定义参数
            "notify_url"=> request()->domain().'/codepay/notify',
            "return_url"=> request()->domain().'/codepay/notify'
        );
        ksort($data); //重新排序$data数组
        reset($data); //内部指针指向数组中的第一个元素

        $sign = ''; //初始化需要签名的字符为空
        $urls = ''; //初始化URL参数为空

        foreach ($data AS $key => $val) { //遍历需要传递的参数
            if ($val == ''||$key == 'sign') continue; //跳过这些不参数签名
            if ($sign != '') { //后面追加&拼接URL
                $sign .= "&";
                $urls .= "&";
            }
            $sign .= "$key=$val"; //拼接为url参数形式
            $urls .= "$key=" . urlencode($val); //拼接为url参数形式并URL编码参数值

        }
        $query = $urls . '&sign=' . md5($sign .$payConfig['pay_key']); //创建订单所需的参数
        $codeUrl = "http://api2.xiuxiu888.com/creat_order/?{$query}"; //支付页面
        Db('vip_log')->where('id', $order_id)->update(['codeUrl' => $codeUrl]);
        return success("下单成功",['codeUrl' => $codeUrl]);
    }

    public function notify() {
        $payConfig = getPayConfig();
        Log::record(json_encode($_POST));
        ksort($_POST); //排序post参数
        reset($_POST); //内部指针指向数组中的第一个元素
        $codepay_key = $payConfig['pay_key'];
        $sign = '';//初始化
        foreach ($_POST AS $key => $val) { //遍历POST参数
            if ($val == '' || $key == 'sign') continue; //跳过这些不签名
            if ($sign) $sign .= '&'; //第一个字符串签名不加& 其他加&连接起来参数
            $sign .= "$key=$val"; //拼接为url参数形式
        }
        if (!$_POST['pay_no'] || md5($sign . $codepay_key) != $_POST['sign']) { //不合法的数据
            exit('支付失败');  //返回失败 继续补单
        } else { //合法的数据
            //业务处理
            $order_id = $_POST['pay_id']; //需要充值的ID 或订单号 或用户名
            $money = (float)$_POST['money']; //实际付款金额
            $price = (float)$_POST['price']; //订单的原价
            $param = $_POST['param']; //自定义参数
            $pay_no = $_POST['pay_no']; //流水号
            $payTime= date('Y-m-d H:i:s', $_POST['payTime']);
            $order_info = Db('vip_log')->where('id', $order_id)->find();
            if ($order_info['status'] == '1') {
                exit(json_encode([
                    'code'  => 'success',
                    'message'   => '已经充值完成'
                ]));
            }
            if($pay_no){
                Db('vip_log')->where('id', $order_id)->update([
                    'status'    => 0,
                    'payTime'   => $payTime,
                    'remark'    => '支付失败'
                ]);
                exit(json_encode([
                    'code'  => 'fail',
                    'message'   => '失败'
                ]));
            }
            if($money != $order_info['amount']){
                Db('vip_log')->where('id', $order_id)->update([
                    'status'    => 2,
                    'payTime'   => $payTime,
                    'remark'    => '支付金额不正确-'.$money
                ]);
                exit(json_encode([
                    'code'  => 'success',
                    'message'   => '成功'
                ]));
            }
            Db('vip_log')->where('id', $order_id)->update([
                'status'    => 1,
                'payTime'   => $payTime,
                'remark'    => '成功'
            ]);
            $shop_info = Db('vip_shop')->where('id', $order_info['shop_id'])->find();
            $time = $shop_info['time'];
            $user_info = Db('user')->where('id', $order_info['uid'])->find();
            $vip_end = $user_info['vip_end'];
            if(!empty($vip_end) && ($vip_end > date('Y-m-d 00:00:00'))){
                $vip_end_now = date('Y-m-d 00:00:00', strtotime("{$vip_end} +{$time} day"));
            } else {
                $vip_end_now = date('Y-m-d 00:00:00', strtotime("+{$time} day"));
            }
            Db('user')->where('id', $order_info['uid'])->update(['vip_end' => $vip_end_now]);
            exit(json_encode([
                'code'  => 'success',
                'message'   => '成功'
            ]));
        }
    }
}