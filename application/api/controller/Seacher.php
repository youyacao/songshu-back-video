<?php


namespace app\api\controller;


use think\Controller;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;
use think\exception\PDOException;
use think\response\Json;

class Seacher
{
    /**
     * Notes: 搜索用户
     * User: BigNiu
     * Date: 2019/10/9
     * Time: 17:03
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     */
    public function postUser()
    {
        $user = session("user") ;
        if (!$user) {
            return error("未登录");
        }
        $page = input("page/i", 1) <= 1 ? 1 : input("page/i", 1);
        $text = input("key");
        $seacher = [
            'key'=>$text,
            'uid'=>$user['id'],
            'create_time'=>TIME,
            'type'=>"user"
        ];
        Db("seach_history")->insert($seacher);
        $data = Db("user u")
            ->join("follow f","u.id=f.follow_id",'left')
            ->group("u.id")
            ->whereLike("u.name", '%'.$text.'%', "or")
            ->whereLike("u.phone", $text, "or")
            ->field(['u.id', 'u.name', 'u.phone', "ifnull(u.head_img,'static/image/head.png') head_img","count(f.id) follow_count"])
            ->page($page, 10)
            ->select();
        if ($data) {
            return success("搜索成功", $data);
        }
        return error("暂无匹配数据");
    }

    /**
     * Notes:搜索视频
     * User: BigNiu
     * Date: 2019/10/9
     * Time: 17:03
     * @return Json
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public function postVideo()
    {
        $page = input("page/i", 1) <= 1 ? 1 : input("page/i", 1);
        $user = session("user") ;
        if (!$user) {
            return error("未登录");
        }
        $text = input("key");
        $seacher = [
            'key'=>$text,
            'uid'=>$user['id'],
            'create_time'=>TIME,
            'type'=>"video"
        ];
        Db("seach_history")->insert($seacher);
        $vids = Db("video")
            ->whereLike("title", '%'.$text.'%', "and")
            ->field(['id'])
            ->select();
        $ids = [];
        foreach ($vids as $key => $vid) {
            $ids[] = $vid['id'];
        }

        $list = Db("video v")
            ->whereIn("v.id", $ids)
            ->join("skr s", "v.id=s.vid and " . $user['id'] . "=s.uid", "left")
            ->join("skr s1", "v.id=s1.vid", "left")
            ->join("user u", "v.uid=u.id", "left")
            ->join("view_history h", "v.id=h.vid", "left")
            ->join("comment c", "v.id=c.vid and c.pid=0", "left")
            ->order("skr desc,create_time")
            ->page($page, 10)
            ->group("v.id")
            ->field([
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
            ->select();
        if ($list) {
            return success("搜索成功", $list);
        }
        return error("暂无匹配数据");
    }

    /**
     * Notes:获取搜索历史，只返回最近5条搜索记录
     * User: BigNiu
     * Date: 2019/10/9
     * Time: 17:35
     * @return Json
     */
    public function getHistory(){
        $user = session("user") ;
        if (!$user) {
            return error("未登录");
        }
        $historys = Db("seach_history")
            ->where(['uid'=>$user['id']])
            ->group("key")
            ->limit(5)
            ->field(["key"])
            ->order('create_time desc')
            ->select();
        $historys = array_column($historys,"key");
        if($historys)
        {
            return success("成功",$historys);
        }
        return error("暂无搜索记录");

    }

    /**
     * Notes:清空历史记录
     * User: BigNiu
     * Date: 2019/10/9
     * Time: 17:55
     * @return Json
     * @throws Exception
     * @throws PDOException
     */
    public function getClear(){
        $user = session("user") ;
        if (!$user) {
            return error("未登录");
        }
        Db("seach_history")->where(['uid'=>$user['id']])->delete();
        return success("清除成功");
    }
}