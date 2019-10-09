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
     * Notes:获取收藏列表
     * User: BigNiu
     * Date: 2019/10/9
     * Time: 12:56
     * @return Json
     */
    public function getList()
    {
        $page  = input("page/i",1)<=1?1:input("page/i",1);
        $user = session("user");
        if (!$user) {
            return error("未登录");
        }
        $collections = Db("collection co")
            ->where(['co.uid'=>$user['id']])//收藏用户ID等于当前用户的ID
            ->whereNotNull("v.id")//视频未被删除
            ->join("video v","co.vid=v.id","left")//收藏ID等于视频的ID
            ->join("skr s", "v.id=s.vid and " . $user['id'] . "=s.uid", "left")//视频ID等于点赞视频ID并且当前用户ID登录点赞用户ID
            ->join("skr s1", "v.id=s1.vid", "left")//视频ID等于点赞视频ID
            ->join("user u", "v.uid=u.id", "left")//视频用户ID等于用户ID
            ->join("view_history h", "v.id=h.vid", "left")//视频ID等于播放历史视频ID
            ->join("comment c", "v.id=c.vid and c.pid=0", "left")//视频ID等于评论视频ID并且评论上级ID未0，即一级评论
            ->group("v.id")
            ->field([
                "co.id coid",
                "v.id",//视频ID
                "v.title",//视频标题
                "v.url",//视频链接
                "v.img",//视频图片
                "v.create_time",//视频创建时间
                "v.uid",//视频对应用户ID
                "u.name",//视频发布人名称
                "ifnull(u.head_img,'static/image/head.png') head_img",//用户头像
                "count(distinct s1.id) skr_count",//点赞数
                "ifnull(s.skr,'0') skr",//当前用户是否点赞
                "count(distinct c.id) comment_count",//评论数
                "count(distinct h.id) view_count",//播放次数
            ])
            ->page($page,10)
            ->select();
        u_log("用户".$user['name']."(".$user['id'].")查看收藏列表");
        if(!$collections)
        {
            return error("暂无数据");

        }
        return success("获取成功",$collections);
    }

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