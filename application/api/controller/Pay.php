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


use think\Cache;
use think\Controller;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\Log;
use think\response\Json;

class Pay extends Controller
{
 
    public function getPayment(){
        $sid = request()->param('sid');
        $shop_info = Db('vip_shop')->where('id', $sid)->find();
        if (empty($shop_info)){
            return error("参数不对，请重试！");
        }
        $user = session("user");
        if (empty($user)){
            return error("请先登录！");
        }
        $payConfig = getPayConfig();
        $params = [];
        $params['uid']  = $user['id'];
        $params['shop_id']  = $sid;
        $params['amount']   = $shop_info['price'];
        $params['payType']  = '12';
        $params['customerOrderTime'] = date('Y-m-d H:i:s');
        $params['ip'] = request()->ip();
        $params['appKey'] = $payConfig['pay_memberid'];
        $order_id = Db('vip_log')->insertGetId($params);
        unset($params['uid']);
        unset($params['shop_id']);
        $params['customerOrderId'] = $order_id;
        $params['notifyUrl'] = request()->domain().'/pay/notify';
        $params['token'] = paySign($params, $payConfig['pay_key']);
        $headers = [];
        $headers['Content-Type'] = 'application/json';
        $result = curl($payConfig['pay_submit_url'], json_encode($params), $headers);
        $res = json_decode($result, true);
        Log::write($result,'notice');
        if ($res['code'] != 'success'){
            return error("下单失败，请重试！");
        }
        $codeUrl = $res['data']['codeUrl'] ?? '';
        Db('vip_log')->where('id', $order_id)->update(['codeUrl' => $codeUrl]);
        return success("下单成功",['codeUrl' => $codeUrl]);
    }

    public function postNotify() {
        $payConfig = getPayConfig();
        $params = request()->param();
        $token = paySign($params, $payConfig['pay_key']);
        Log::record(json_encode($params));
        if ($token != $params['token']){
            Log::record('----token不正确-----');
            exit(json_encode([
                'code'  => 'fail',
                'message'   => '失败'
            ]));
        }
        $order_id = $params['customerOrderId'];
        $order_info = Db('vip_log')->where('id', $order_id)->find();
        if($params['status'] !== '1'){
            Db('vip_log')->where('id', $order_id)->update([
                'status'    => $params['status'],
                'payTime'   => $params['payTime'],
                'remark'    => '支付失败'
            ]);
            exit(json_encode([
                'code'  => 'fail',
                'message'   => '失败'
            ]));
        }
        if($params['amount'] != $order_info['amount']){
            Db('vip_log')->where('id', $order_id)->update([
                'status'    => 2,
                'payTime'   => $params['payTime'],
                'remark'    => '支付金额不正确-'.$params['amount']
            ]);
            exit(json_encode([
                'code'  => 'success',
                'message'   => '成功'
            ]));
        }
        Db('vip_log')->where('id', $order_id)->update([
            'status'    => $params['status'],
            'payTime'   => $params['payTime'],
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