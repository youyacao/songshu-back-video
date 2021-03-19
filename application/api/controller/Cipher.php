<?php
/**
All rights Reserved, Designed By www.youyacao.com
@Description:码支付方法文件
@author:成都市一颗优雅草科技有限公司
@version 松鼠短视频系统-后端部分
注意：后端代码在获得授权之前通过其他非官方渠道获得代码均为侵权，禁止用于商业用途，否则将承担因此带来等版权纠纷。
需要商业用途或者定制开发等可访问songshu.youyacao.com   联系QQ:422108995 23625059584

 */

namespace app\api\controller;

use think\Controller;

class Cipher extends Controller
{

    public function receive(){
        $user = session("user");
        if (!$user) {
            return error('请先登录');
        }

        $code = request()->param('code');
        if (empty($code)) {
            return error('卡密不能为空');
        }

        $info = Db('cipher')->where('status', 1)->where('code', $code)->where('get_user_id', 0)->find();
        if (empty($info)) {
            return error('卡密不存在或者已经被兑换');
        }
        if ($info['over_time'] && $info['over_time'] < strtotime(date('Y-m-d'))) {
            return error('卡密已经过期，请重新获取');
        }
        $result = Db('cipher')->where('id', $info['id'])->update([
            'get_user_id' => $user['id'],
            'get_time' => time(),
            'status' => 2
        ]);
        if ($result) {
            Db('user')->where('id', $user['id'])->setInc('money', $info['amount']);
            return success('兑换成功');
        } else {
            return error('兑换失败');
        }
    }
}