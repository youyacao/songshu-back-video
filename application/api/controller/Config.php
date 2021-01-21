<!-- 
All rights Reserved, Designed By www.youyacao.com 
@Description:获取配置
@author:成都市一颗优雅草科技有限公司     
@version 松鼠短视频系统-后端部分
注意：后端代码在获得授权之前通过其他非官方渠道获得代码均为侵权，禁止用于商业用途，否则将承担因此带来等版权纠纷。
需要商业用途或者定制开发等可访问songshu.youyacao.com   联系QQ:422108995 23625059584

 -->
<?php


namespace app\api\controller;

use think\Controller;

class Config extends Controller
{

    public function index(){
        $key = request()->param('key', 'cy_');
        $list = Db('config')->where('name', 'like', "$key%")->column('value','name');
        return success("获取配置成功",$list);
    }
}