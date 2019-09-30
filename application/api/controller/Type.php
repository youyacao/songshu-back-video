<?php


namespace app\api\controller;


use think\Cache;
use think\Controller;

class Type extends Controller
{
    /**
     * 获取分类列表
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getType(){
        if($type1=Cache::get("type"))
        {
            return success("成功",$type1);
        }
        $type1 = Db("type")->where(["level"=>1,"enable"=>1])->select();
        foreach ($type1 as $key=>$item)
        {
            $type2 = Db("type")->where(["pid"=>$item['id'],"enable"=>1])->select();
            $type1[$key]['sub_type']=$type2?$type2:[];
        }
        Cache::set("type",$type1);
        return success("成功",$type1,300);
    }

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

}