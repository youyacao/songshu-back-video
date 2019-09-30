<?php


namespace app\api\controller;


use think\Db;
use think\Request;

class Video
{
    /**
     * 根据用户ID获取视频数据
     * @param $user 用户信息
     * @param bool $newVideo 是否加载新视频
     * @return mixed
     */
    private function getVideoData($user,$newVideo=false)
    {
        if($newVideo){
            $list = Db("video v")
                ->join("skr s", "v.id=s.vid and v.uid=s.uid", "left")
                ->join("user u", "v.uid=u.id", "left")
                ->order("v.create_time")
                ->field("v.id,v.title,v.url,v.img,v.create_time,v.uid,u.name,ifnull(u.head_img,'static/image/head.png') head_img,ifnull(s.skr,'0') skr")
                ->limit(10)
                ->select();
            return $list;
        }else{
            //通过ID获取已看视频ID
            $vids = Db("view_history")->where(["uid" => $user['id']])->field("vid")->select();
            $ids = [];
            foreach($vids as $key=>$vid)
            {
                $ids[]=$vid['vid'];
            }

            //通过已看视频ID获取未看视频并通过发布时间倒序排序
            $list = Db("video v")
                ->whereNotIn("v.id",$ids)
                ->join("skr s", "v.id=s.vid and v.uid=s.uid", "left")
                ->join("user u", "v.uid=u.id", "left")
                ->order("v.create_time")
                ->limit(10)
                ->field("v.id,v.title,v.url,v.img,v.create_time,v.uid,u.name,ifnull(u.head_img,'static/image/head.png') head_img,ifnull(s.skr,'0') skr")
                ->select();
            return $list;
        }



    }

    /**
     * 播放视频（用户已看视频请求该链接进行标记）
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
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
     * 获取视频
     * @return \think\response\Json
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
        if(!$data)
        {
            $data = $this->getVideoData($user,true);
        }
        return success("", $data);
    }

    /**
     * 发布视频
     * @return \think\response\Json
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
        $typeInfo = Db("type")->where(['id' => $type, "level" => 2])->find();
        if (!$typeInfo) {
            return error("类型选择错误");
        }
        $data = [
            "title" => $title,
            "uid" => $user['id'],
            "type" => $type,
            "img" => $img,
            "url" => $url,
            "create_time"=>TIME
        ];
        $id = Db("video")->insertGetId($data);
        $data['id'] = $id;
        return success("成功", $data);
    }
}