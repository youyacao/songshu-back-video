<?php


namespace app\api\controller;


use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\Request;
use think\response\Json;

class Video
{
    /**
     * 根据用户ID获取视频数据
     * @param $user 用户信息
     * @param bool $newVideo 是否加载新视频
     * @return mixed
     */
    private function getVideoData($user, $newVideo = false)
    {
        if ($newVideo) {
            $list = Db("video v")
                ->join("skr s", "v.id=s.vid and v.uid=s.uid", "left")
                ->join("skr s1", "v.id=s.vid", "left")
                ->join("user u", "v.uid=u.id", "left")
                ->join("comment c", "v.id=c.vid", "left")
                ->order("v.create_time")
                ->field([
                    "v.id",//视频ID
                    "v.title",//视频标题
                    "v.url",//视频链接
                    "v.img",//视频图片
                    "v.create_time",//视频创建时间
                    "v.uid",//视频对应用户ID
                    "u.name",//视频发布人名称
                    "ifnull(u.head_img,'static/image/head.png') head_img",//用户头像
                    "count(s1.id) skr_count",//点赞数
                    "ifnull(s.skr,'0') skr",//当前用户是否点赞
                    "count(c.id) comment_count"//评论数
                ])
                ->limit(10)
                ->select();
            return $list;
        } else {
            //通过ID获取已看视频ID
            $vids = Db("view_history")->where(["uid" => $user['id']])->field("vid")->select();
            $ids = [];
            foreach ($vids as $key => $vid) {
                $ids[] = $vid['vid'];
            }

            //通过已看视频ID获取未看视频并通过发布时间倒序排序
            $list = Db("video v")
                ->whereNotIn("v.id", $ids)
                ->join("skr s", "v.id=s.vid and v.uid=s.uid", "left")
                ->join("skr s1", "v.id=s.vid", "left")
                ->join("user u", "v.uid=u.id", "left")
                ->join("comment c", "v.id=c.vid", "left")
                ->order("skr")
                ->limit(10)
                ->field([
                    "v.id",//视频ID
                    "v.title",//视频标题
                    "v.url",//视频链接
                    "v.img",//视频图片
                    "v.create_time",//视频创建时间
                    "v.uid",//视频对应用户ID
                    "u.name",//视频发布人名称
                    "ifnull(u.head_img,'static/image/head.png') head_img",//用户头像
                    "count(s1.id) skr_count",//点赞数
                    "ifnull(s.skr,'0') skr",//当前用户是否点赞
                    "count(c.id) comment_count"//评论数
                ])
                ->select();
            return $list;
        }


    }

    /**
     * 播放视频（用户已看视频请求该链接进行标记）
     * @return Json
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public function getView()
    {
        $vid = input("vid");
        $user = session("user") ? session("user") : session("guest_user");

        if (!$user) {
            return error("未登录");
        }
        $data = [
            "uid" => $user['id'],
            "vid" => $vid
        ];
        $view_history = Db("view_history")->where($data)->find();
        //未保存该条记录，新增
        if (!$view_history) {
            $data['time'] = time();
            Db("view_history")->insertGetId($data);
        }
        return success("成功");
    }

    /**
     * Notes:获取视频
     * User: BigNiu
     * Date: 2019/10/8
     * Time: 16:56
     * @return Json
     */
    public function getData()
    {

        $user = session("user") ? session("user") : session("guest_user");
        //判断当前用户是否登录
        if (!$user) {
            //未登录，使用访客用户
            session("guest_user", ['id' => adminpass(header("user-agent") . time())]);
            $user = session("guest_user");
        }
        //通过用户token获取数据
        $data = $this->getVideoData($user);
        if (!$data) {
            $data = $this->getVideoData($user, true);
        }
        return success("", $data);
    }

    /**
     * Notes:发布视频
     * User: BigNiu
     * Date: 2019/10/8
     * Time: 16:57
     * @param title 标题
     * @param url 视频播放地址
     * @param type 视频类型
     * @return Json
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public function postData()
    {
        $user = session("user");
        if (!$user) {
            return error("未登录");
        }
        $title = input("title");//标题
        $url = input("url");//视频链接
        $type = input("type");//视频类型
        $img = getImg($url);//通过视频存储路径获取视略缩图"1.png";//
        if(!is_file($img))
        {
            u_log("用户".$user['name']."(".$user['id'].")发布视频失败('生成略缩图失败')","error");
            return error("生成略缩图失败!");
        }
        $typeInfo = Db("type")->where(['id' => $type, "level" => 2])->find();
        if (!$typeInfo) {
            u_log("用户".$user['name']."(".$user['id'].")发布视频失败('类型选择错误')","error");
            return error("类型选择错误");
        }
        $data = [
            "title" => $title,
            "uid" => $user['id'],
            "type" => $type,
            "img" => $img,
            "url" => $url,
            "create_time" => TIME
        ];
        $id = Db("video")->insertGetId($data);
        $data['id'] = $id;
        u_log("用户".$user['name']."(".$user['id'].")发布视频成功");
        return success("成功", $data);
    }
}