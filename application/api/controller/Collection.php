<?php


namespace app\api\controller;


use think\Controller;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;
use think\response\Json;

/**
 * 收藏操作
 * Class Collection
 * @package app\api\controller
 */
class Collection extends Controller
{



    /**
     * Notes:添加收藏
     * @param vid 视频ID
     * User: BigNiu
     * Date: 2019/10/9
     * Time: 12:56
     * @return Json
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     * @throws Exception
     */
    public function postChange(){
        $type = input("type")=="add"?"add":"cancel";//更改类型，add添加收藏，cancel取消收藏
        $user = session("user");
        if (!$user) {
            return error("未登录");
        }
        $vid = input("vid/i");
        $video = Db("video")->where(['id'=>$vid])->find();
        //视频不存在
        if(!$video)
        {
            u_log("用户".$user['name']."(".$user['id'].")收藏视频"."(".$vid.")失败");
            return error("视频已删除");
        }
        if($type=="add")
        {
            //添加收藏


            $collection = Db("collection")->where(['uid'=>$user['id'],'vid'=>$vid])->find();
            if($collection)
            {
                u_log("用户".$user['name']."(".$user['id'].")收藏视频".$video['title']."(".$vid.")成功，视频已收藏");
                //如果收藏已存在，直接返回成功
                return success("收藏成功",$collection);

            }
            $data = [
                "uid"=>$user['id'],
                "vid"=>$vid,
                "create_time"=>TIME
            ];
            $id = Db("collection")->insertGetId($data);
            if($id)
            {
                $data['id']=$id;
                u_log("用户".$user['name']."(".$user['id'].")收藏视频".$video['title']."(".$vid.")成功，视频已收藏");
                return success("收藏成功",$data);
            }
            u_log("用户".$user['name']."(".$user['id'].")收藏视频".$video['title']."(".$vid.")失败");
            return error("收藏失败，请稍后重试");
        }elseif($type=='cancel'){
            //取消收藏
            Db("collection")->where(['uid'=>$user['id'],'vid'=>$vid])->delete();
            u_log("用户".$user['name']."(".$user['id'].")取消收藏视频".$video['title']."(".$vid.")成功");
            return success("取消收藏成功");
        }
        return error("非法操作");
    }
}