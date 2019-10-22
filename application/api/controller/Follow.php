<?php


namespace app\api\controller;


use think\Controller;
use think\Db;

/**
 * 关注
 * Class Follow
 * @package app\api\controller
 */
class Follow extends Controller
{
    public function getList()
    {
        $user = session("user");
        if (!$user) {
            return error("未登录");
        }
        $follow = Db("follow f")
            ->where(['f.uid' => $user['id']])
            ->join("user u", "f.follow_id = u.id", "left")
            ->field(["u.id","ifnull(u.head_img,'static/image/head.png') head_img","ifnull(u.name,u.phone) name"])
            ->select();
        if($follow)
        {
            return success("获取成功",$follow);
        }
        return error("暂无数据");
    }

    public function postChange(){
        $user = session("user");
        if (!$user) {
            return error("未登录");
        }
        $type = input("type")=="add"?"add":"cancel";//类型
        $uid = input("uid/i");//被关注用户ID
        if(!$uid){
            return error("关注失败");
        }
        if($user['id']==$uid){
            return error("不能关注自己");
        }
        $follow  = Db("follow")->where(['uid'=>$user['id'],"follow_id"=>$uid])->find();
        if($type=='add')
        {
            if($follow)
            {
                //如果关注已存在，直接返回成功
                return success("关注成功",$follow);
            }
            //关注不存在
            $data = [
                'uid'=>$user['id'],
                'follow_id'=>$uid,
                'create_time'=>TIME,
            ];
            $id = Db("follow")->insertGetId($data);
            if($id)
            {
                $data['id']=$id;
                return success("关注成功",$data);
            }
            return error("关注失败");

        }elseif ($type=='cancel')
        {
            //取消关注
            if(!$follow)
            {
                //如果关注原本就不存在，直接返回成功
                return success("取消关注成功");
            }
            $result = Db("follow")->where(['uid'=>$user['id'],"follow_id"=>$uid])->delete();
            if($result)
            {
                return success("取消关注成功");
            }
            return error("取消关注失败");
        }
    }
}