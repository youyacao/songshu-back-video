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
     * 播放视频（用户已看视频请求该链接进行标记）
     * @return Json
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public function getView()
    {

        $vid = input("id");
        $user = session("user") ? session("user") : session("guest_user");
        $video = Db("video")->where(['id' => $vid])->find();

        if (!$video) {
            return error("该视频已删除");
        }
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
     * 根据用户ID获取视频数据
     * @param $user 用户信息
     * @param bool $newVideo 是否加载新视频
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    private function getVideoData($user, $newVideo = false)
    {
        $page = input("page/i", 1) <= 1 ? 1 : input("page/i", 1);
        if ($newVideo) {

            $list = Db("video v")
                ->where(['v.state'=>1])
                ->join("skr s", " v.id=s.vid and s.type=0 and '" . $user['id'] . "'=s.uid", "left")//视频ID等于点赞视频ID并且当前用户ID登录点赞用户ID
                ->join("skr s1",  "v.id=s1.vid and s1.type=0", "left")//视频ID等于点赞视频ID
                ->join("user u", "v.uid=u.id", "left")//视频用户ID等于用户ID
                ->join("follow f","v.uid=f.follow_id and f.uid = '".$user['id']."'","left")//视频发布者ID等于被关注人ID并且关注用户ID等于当前用户ID
                ->join("collection co","v.id=co.vid and co.uid = '".$user['id']."'","left")//视频ID等于收藏的视频ID并且收藏的用户ID为当前用户ID
                ->join("view_history h", "v.id=h.vid", "left")//视频ID等于播放历史视频ID
                ->join("comment c", "v.id=c.vid and c.pid=0 and c.type=0", "left")//视频ID等于评论视频ID并且评论上级ID未0，即一级评论
                ->page($page, 20)
                ->group("v.id")
                ->field([
                    "v.id",//视频ID
                "v.title",//视频标题
                    "v.url",//视频链接
                    "v.img",//视频图片
                    "v.create_time",//视频创建时间
                    "v.uid",//视频对应用户ID
                    "v.state",//视频状态
                    "u.name",//视频发布人名称
                    "ifnull(u.head_img,'static/image/head.png') head_img",//用户头像
                    "count(distinct s1.id) skr_count",//点赞数
                    "ifnull(s.skr,'0') skr",//当前用户是否点赞
                    "ifnull(co.create_time,'0') collection",//当前用户是否收藏
                    "ifnull(f.id,'0') follow",//当前用户是否关注
                    "count(distinct c.id) comment_count",//评论数
                    "count(distinct h.id) view_count",//播放次数
                ])
                ->order(['create_time' => 'desc'])//根据点赞数排序如同级根据发布时间排序，最新的在最上面
                ->select();
            return $list;
        } else {
            //通过ID获取已看视频ID
            $vids = Db("view_history")->where(["uid" => $user['id']])->field("vid")->select();
            $ids = array_column($vids, "vid");;

            //通过已看视频ID获取未看视频并通过发布时间倒序排序
            $list = Db("video v")
                ->where(['v.state'=>1])
                ->whereNotIn("v.id", $ids)
                ->join("skr s", " v.id=s.vid and s.type=0 and '" . $user['id'] . "'=s.uid", "left")
                ->join("skr s1",  "v.id=s1.vid and s1.type=0", "left")
                ->join("user u", "v.uid=u.id", "left")
                ->join("follow f","v.uid=f.follow_id and f.uid = '".$user['id']."'","left")//视频发布者ID等于被关注人ID并且关注用户ID等于当前用户ID
                ->join("collection co","v.id=co.vid and co.uid = '".$user['id']."'","left")//视频ID等于收藏的视频ID并且收藏的用户ID为当前用户ID
                ->join("view_history h", "v.id=h.vid", "left")
                ->join("comment c", "v.id=c.vid and c.pid=0 and c.type=0", "left")
                ->order("skr desc")
                ->page($page, 20)
                ->group("v.id")
                ->field([
                    "v.id",//视频ID
                "v.title",//视频标题
                    "v.url",//视频链接
                    "v.img",//视频图片
                    "v.create_time",//视频创建时间
                    "v.uid",//视频对应用户ID
                    "v.state",//视频状态
                    "u.name",//视频发布人名称
                    "ifnull(u.head_img,'static/image/head.png') head_img",//用户头像
                    "count(distinct s1.id) skr_count",//点赞数
                    "ifnull(s.skr,'0') skr",//当前用户是否点赞
                    "ifnull(co.create_time,'0') collection",//当前用户是否收藏
                    "ifnull(f.id,'0') follow",//当前用户是否关注
                    "count(distinct c.id) comment_count",//评论数
                    "count(distinct h.id) view_count",//播放次数
                ])
                ->select();

            return $list;
        }


    }

    /**
     * Notes:获取用户关注的视频列表
     * User: BigNiu
     * Date: 2019/10/9
     * Time: 16:30
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    private function getFollowList()
    {
        $page = input("page/i", 1) <= 1 ? 1 : input("page/i", 1);
        $user = session("user");
        if (!$user) {
            return error("未登录");
        }
        $follow_ids = Db("follow")
            ->where(['uid' => $user['id']])
            ->field("follow_id")
            ->select();
        $ids = array_column($follow_ids, "follow_id");;


        //通过已看视频ID获取未看视频并通过发布时间倒序排序
        $list = Db("video v")
            ->whereIn("v.uid", $ids)
            ->where(['v.state'=>1])
            ->join("skr s", " v.id=s.vid and s.type=0 and '" . $user['id'] . "'=s.uid", "left")
            ->join("skr s1",  "v.id=s1.vid and s1.type=0", "left")
            ->join("user u", "v.uid=u.id", "left")
            ->join("follow f","v.uid=f.follow_id and f.uid = '".$user['id']."'","left")//视频发布者ID等于被关注人ID并且关注用户ID等于当前用户ID
            ->join("collection co","v.id=co.vid and co.uid = '".$user['id']."'","left")//视频ID等于收藏的视频ID并且收藏的用户ID为当前用户ID
            ->join("view_history h", "v.id=h.vid", "left")
            ->join("comment c", "v.id=c.vid and c.pid=0 and c.type=0", "left")
            ->order("skr desc,create_time")
            ->page($page, 20)
            ->group("v.id")
            ->field([
                "v.id",//视频ID
                "v.title",//视频标题
                "v.url",//视频链接
                "v.img",//视频图片
                "v.create_time",//视频创建时间
                "v.uid",//视频对应用户ID
                "v.state",//视频状态
                "u.name",//视频发布人名称
                "ifnull(u.head_img,'static/image/head.png') head_img",//用户头像
                "count(distinct s1.id) skr_count",//点赞数
                "ifnull(s.skr,'0') skr",//当前用户是否点赞
                "ifnull(co.create_time,'0') collection",//当前用户是否收藏
                "ifnull(f.id,'0') follow",//当前用户是否关注
                "count(distinct c.id) comment_count",//评论数
                "count(distinct h.id) view_count",//播放次数
            ])
            ->select();
        return $list;
    }

    /**
     * Notes:根据用户传入分类ID获取视频列表
     * User: BigNiu
     * Date: 2019/10/9
     * Time: 17:07
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    private function getCustomList()
    {
        $page = input("page/i", 1) <= 1 ? 1 : input("page/i", 1);
        $typeid = input("typeid/i");//分类ID
        if (!$typeid) {
            //分类信息不存在，直接返回空数据
            return [];
        }
        $typeinfo = Db("type")->where(['id' => $typeid])->find();
        if (!$typeinfo) {
            //分类信息不存在，直接返回空数据
            return [];
        }

        $user = session("user") ? session("user") : session("guest_user");
        //判断当前用户是否登录
        if (!$user) {
            //未登录，使用访客用户
            session("guest_user", ['id' => adminpass(header("user-agent") . time())]);
            $user = session("guest_user");
        }
        //通过已看视频ID获取未看视频并通过发布时间倒序排序
        $list = Db("video v")
            ->where("v.type", $typeid)
            ->where(['v.state'=>1])
            ->join("skr s", " v.id=s.vid and s.type=0 and '" . $user['id'] . "'=s.uid", "left")
            ->join("skr s1",  "v.id=s1.vid and s1.type=0", "left")
            ->join("user u", "v.uid=u.id", "left")
            ->join("follow f","v.uid=f.follow_id and f.uid = '".$user['id']."'","left")//视频发布者ID等于被关注人ID并且关注用户ID等于当前用户ID
            ->join("collection co","v.id=co.vid and co.uid = '".$user['id']."'","left")//视频ID等于收藏的视频ID并且收藏的用户ID为当前用户ID
            ->join("view_history h", "v.id=h.vid", "left")
            ->join("comment c", "v.id=c.vid and c.pid=0 and c.type=0", "left")
            ->order("create_time desc")
            ->page($page, 20)
            ->group("v.id")
            ->field([
                "v.id",//视频ID
                "v.title",//视频标题
                "v.url",//视频链接
                "v.img",//视频图片
                "v.create_time",//视频创建时间
                "v.uid",//视频对应用户ID
                "v.state",//视频状态
                "u.name",//视频发布人名称
                "ifnull(u.head_img,'static/image/head.png') head_img",//用户头像
                "count(distinct s1.id) skr_count",//点赞数
                "ifnull(s.skr,'0') skr",//当前用户是否点赞
                "ifnull(co.create_time,'0') collection",//当前用户是否收藏
                "ifnull(f.id,'0') follow",//当前用户是否关注
                "count(distinct c.id) comment_count",//评论数
                "count(distinct h.id) view_count",//播放次数
            ])
            ->select();
        return $list;
    }


    /**
     * Notes:用户发布的作品
     * @param page 第几页
     * @param bool $isFilter 是否过滤审核中的
     * User: BigNiu
     * Date: 2019/10/9
     * Time: 14:55
     *
     * @return Json
     */
    private function getUserVideo($uid,$isFilter=false)
    {
        $where = [];
        if($isFilter){
            $where = ['v.state'=>1];
        }
        $page = input("page/i", 1) <= 1 ? 1 : input("page/i", 1);

        $list = Db("video v")
            ->where(['v.uid' => $uid])
            ->where($where)
            ->join("skr s", " v.id=s.vid and s.type=0 and '" .$uid . "'=s.uid", "left")//视频ID等于点赞视频ID并且当前用户ID登录点赞用户ID
            ->join("skr s1",  "v.id=s1.vid and s1.type=0", "left")//视频ID等于点赞视频ID
            ->join("user u", "v.uid=u.id", "left")//视频用户ID等于用户ID
            ->join("follow f","v.uid=f.follow_id and f.uid = '".$uid."'","left")//视频发布者ID等于被关注人ID并且关注用户ID等于当前用户ID
            ->join("collection co","v.id=co.vid and co.uid = '".$uid."'","left")//视频ID等于收藏的视频ID并且收藏的用户ID为当前用户ID
            ->join("view_history h", "v.id=h.vid", "left")//视频ID等于播放历史视频ID
            ->join("comment c", "v.id=c.vid and c.pid=0 and c.type=0", "left")//视频ID等于评论视频ID并且评论上级ID未0，即一级评论
            ->page($page, 20)
            ->group("v.id")
            ->field([
                "v.id",//视频ID
                "v.title",//视频标题
                "v.url",//视频链接
                "v.img",//视频图片
                "v.create_time",//视频创建时间
                "v.uid",//视频对应用户ID
                "v.state",//视频状态
                "u.name",//视频发布人名称
                "ifnull(u.head_img,'static/image/head.png') head_img",//用户头像
                "count(distinct s1.id) skr_count",//点赞数
                "ifnull(s.skr,'0') skr",//当前用户是否点赞
                "ifnull(co.create_time,'0') collection",//当前用户是否收藏
                "ifnull(f.id,'0') follow",//当前用户是否关注
                "count(distinct c.id) comment_count",//评论数
                "count(distinct h.id) view_count",//播放次数
            ])
            ->order(['create_time' => 'desc'])//根据点赞数排序如同级根据发布时间排序，最新的在最上面
            ->select();
        return $list;
    }

    /**
     * Notes:我喜欢的视频
     * User: BigNiu
     * Date: 2019/10/9
     * Time: 14:59
     * @return Json
     */
    private function getLikeList($uid)
    {
        $page = input("page/i", 1) <= 1 ? 1 : input("page/i", 1);

        $list = Db("skr s")
            ->where(['s.uid' => $uid])
            ->where(['v.state'=>1])
            ->join("video v", "s.vid = v.id", "left")
            ->join("skr s1",  "v.id=s1.vid and s1.type=0", "left")//视频ID等于点赞视频ID
            ->join("user u", "v.uid=u.id", "left")//视频用户ID等于用户ID
            ->join("follow f","v.uid=f.follow_id and f.uid = '".$uid."'","left")//视频发布者ID等于被关注人ID并且关注用户ID等于当前用户ID
            ->join("collection co","v.id=co.vid and co.uid = '".$uid."'","left")//视频ID等于收藏的视频ID并且收藏的用户ID为当前用户ID
            ->join("view_history h", "v.id=h.vid", "left")//视频ID等于播放历史视频ID
            ->join("comment c", "v.id=c.vid and c.pid=0 and c.type=0", "left")//视频ID等于评论视频ID并且评论上级ID未0，即一级评论
            ->page($page, 20)
            ->group("v.id")
            ->field([
                "v.id",//视频ID
                "v.title",//视频标题
                "v.url",//视频链接
                "v.img",//视频图片
                "v.create_time",//视频创建时间
                "v.uid",//视频对应用户ID
                "v.state",//视频状态
                "u.name",//视频发布人名称
                "ifnull(u.head_img,'static/image/head.png') head_img",//用户头像
                "count(distinct s1.id) skr_count",//点赞数
                "ifnull(s.skr,'0') skr",//当前用户是否点赞
                "ifnull(co.create_time,'0') collection",//当前用户是否收藏
                "ifnull(f.id,'0') follow",//当前用户是否关注
                "count(distinct c.id) comment_count",//评论数
                "count(distinct h.id) view_count",//播放次数
            ])
            ->order(['s.create_time' => 'desc'])//根据点赞数排序如同级根据发布时间排序，最新的在最上面
            ->select();

        return $list;
    }

    /**
     * Notes:获取视频
     * User: BigNiu
     * Date: 2019/10/8
     * Time: 16:56
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getData()
    {
        $type = input("type", "hot");
        $types = [
            'follow',//关注
            'collection',//收藏
            'new',//普通
            'user',//用户发布
            'likes',//用户点赞的视频
            'hot',//热门
            'custom',//自定义分类
            'other',//其他用户发布视频
            'otherLike',//其他用户喜欢视频
        ];

        if (!in_array($type, $types)) {
            return error("非法操作");
        }
        $user = session("user") ? session("user") : session("guest_user");
        //判断当前用户是否登录
        if (!$user) {
            //未登录，使用访客用户
            session("guest_user", ['id' => adminpass(header("user-agent") . time())]);
            $user = session("guest_user");
        }
        $data = [];
        switch ($type) {
            case "follow":
                //关注
                $data = $this->getFollowList();
                break;
            case "collection":
                $data = $this->getCollection();
                break;
            case "custom":
                //自定义
                $data = $this->getCustomList();
                break;
            case "new":
                //最新
                $data = $this->getVideoData($user, true);
                break;
            case "user":
                //用户作品
                $data = $this->getUserVideo($user['id']);
                break;
            case "likes":
                //用户点赞的视频
                $data = $this->getLikeList($user['id']);
                break;
            case "other":
                //其他用户作品
                $uid = input('uid/i');
                $data = $this->getUserVideo($uid,true);
                break;
            case "otherLike":
                //其他用户点赞作品
                $uid = input('uid/i');
                $data = $this->getLikeList($uid);
                break;
            case "hot":
                //最热
                $data = $this->getVideoData($user);
                if (!$data) {
                    $data = $this->getVideoData($user, true);
                }
                break;
        }

        if (!$data) {
            return error("暂无数据");
        }
        //通过用户token获取数据
        return success("获取成功", $data);
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
        if (!is_file($img)) {
            u_log("用户" . $user['name'] . "(" . $user['id'] . ")发布视频失败('生成略缩图失败')", "error");
            return error("生成略缩图失败!");
        }
        $typeInfo = Db("type")->where(['id' => $type, "level" => 2])->find();
        if (!$typeInfo) {
            u_log("用户" . $user['name'] . "(" . $user['id'] . ")发布视频失败('类型选择错误')", "error");
            return error("类型选择错误");
        }
        $data = [
            "title" => $title,
            "uid" => $user['id'],
            "type" => $type,//视频分类
            "img" => $img,
            "url" => $url,
            "create_time" => TIME
        ];
        $id = Db("video")->insertGetId($data);
        $data['id'] = $id;
        u_log("用户" . $user['name'] . "(" . $user['id'] . ")发布视频成功");
        return success("成功", $data);
    }

    /**
     * Notes:获取收藏列表
     * User: BigNiu
     * Date: 2019/10/9
     * Time: 12:56
     * @return Json
     */

    private function getCollection()
    {

        $page = input("page/i", 1) <= 1 ? 1 : input("page/i", 1);
        $user = session("user");
        if (!$user) {
            return error("未登录");
        }
        $collections = Db("collection co")
            ->where(['co.uid' => $user['id']])//收藏用户ID等于当前用户的ID
            ->where(['v.state'=>1])
            ->whereNotNull("v.id")//视频未被删除
            ->join("video v", "co.vid=v.id", "left")//收藏ID等于视频的ID
            ->join("skr s", " v.id=s.vid and s.type=0 and " . $user['id'] . "=s.uid", "left")//视频ID等于点赞视频ID并且当前用户ID登录点赞用户ID
            ->join("skr s1",  "v.id=s1.vid and s1.type=0", "left")//视频ID等于点赞视频ID
            ->join("user u", "v.uid=u.id", "left")//视频用户ID等于用户ID
            ->join("follow f","v.uid=f.follow_id and f.uid = '".$user['id']."'","left")//视频发布者ID等于被关注人ID并且关注用户ID等于当前用户ID
            ->join("view_history h", "v.id=h.vid", "left")//视频ID等于播放历史视频ID
            ->join("comment c", "v.id=c.vid and c.pid=0 and c.type=0", "left")//视频ID等于评论视频ID并且评论上级ID未0，即一级评论
            ->group("v.id")
            ->field([
                "v.id",//视频ID
                "v.title",//视频标题
                "v.url",//视频链接
                "v.img",//视频图片
                "v.create_time",//视频创建时间
                "v.uid",//视频对应用户ID
                "v.state",//视频状态
                "u.name",//视频发布人名称
                "ifnull(u.head_img,'static/image/head.png') head_img",//用户头像
                "count(distinct s1.id) skr_count",//点赞数
                "ifnull(s.skr,'0') skr",//当前用户是否点赞
                "ifnull(co.create_time,'0') collection",//当前用户是否收藏
                "ifnull(f.id,'0') follow",//当前用户是否关注
                "count(distinct c.id) comment_count",//评论数
                "count(distinct h.id) view_count",//播放次数
            ])
            ->page($page, 20)
            ->select();
        u_log("用户" . $user['name'] . "(" . $user['id'] . ")查看收藏列表");
        return $collections;
    }
    public function postDelete(){
        $user = session("user");
        if (!$user) {
            return error("未登录");
        }
        $ids = input('ids/a');
        if(!$ids){
            return error("删除失败");
        }
        $res = Db("video")->where(['uid'=>$user['id']])->whereIn('id',$ids)->delete();
        if($res){
            return success("删除成功");
        }
        return error("删除失败");
    }
}