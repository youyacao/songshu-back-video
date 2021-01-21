<!-- 
All rights Reserved, Designed By www.youyacao.com 
@Description:分类方法文件
@author:成都市一颗优雅草科技有限公司     
@version 松鼠短视频系统-后端部分
注意：后端代码在获得授权之前通过其他非官方渠道获得代码均为侵权，禁止用于商业用途，否则将承担因此带来等版权纠纷。
需要商业用途或者定制开发等可访问songshu.youyacao.com   联系QQ:422108995 23625059584

 -->
<?php


namespace app\api\controller;


use think\Cache;
use think\Controller;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\response\Json;

class Type extends Controller
{
    /**
     * Notes: 获取分类列表
     * User: BigNiu
     * Date: 2019/10/8
     * Time: 16:04
     * @return Json
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public function getType(){
        /*if($type1=Cache::get("type"))
        {
            return success("成功",$type1);
        }*/
        $type1 = Db("type")->where(["level"=>1,"enable"=>1])->order('sort_id')->select();
        foreach ($type1 as $key=>$item)
        {
            $type2 = Db("type")->where(["pid"=>$item['id'],"enable"=>1])->order('sort_id')->select();
            $type1[$key]['sub_type']=$type2?$type2:[];
        }

//        Cache::set("type",$type1,60);
        return success("成功",$type1);
    }

    /**
     * Notes:添加分类
     * User: BigNiu
     * Date: 2019/10/8
     * Time: 16:05
     * @return Json
     */
    public function postType(){
        $name = input("name");
        $icon = input("icon");
        $level = input("level/i")==1?1:2;
        $pid = input("pid");
        $data = [
            "name"=>$name,
            "icon"=>$icon,
            "level"=>$level,
            "pid"=>1,
            "enable"=>1,
            "create_time"=>date("Y-m-d H:i:s",time()),
            "sort_id"=>999
        ];
        if($level==2)
        {
            $data['pid']=$pid;
        }
        $id = Db("type")->insertGetId($data);
        if($id)
        {
            $data['id']=$id;
            return success("",$data);
        }
        return error("添加失败");
    }

    public function getVipShopList(){
        $list = Db('vip_shop')->select();
        return success('',$list);
    }

    public function getPaytypeList(){
        $list = Db('pay_type')->select();
        return success('',$list);
    }

}