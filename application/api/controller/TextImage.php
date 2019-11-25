<?php


namespace app\api\controller;


use think\Controller;

class TextImage extends Controller
{
    /**
     * Notes:根据类型获取文字内容或图文内容
     * User: BigNiu
     * Date: 2019/11/19
     * Time: 16:16
     * @return \think\response\Json
     */
    public function getList(){
        $page = input("page/i", 1) <= 1 ? 1 : input("page/i", 1);
        $user = session("user") ? session("user") : session("guest_user");
        //判断当前用户是否登录
        if (!$user) {
            //未登录，使用访客用户
            session("guest_user", ['id' => adminpass(header("user-agent") . time())]);
            $user = session("guest_user");
        }

        //根据传入的类型获取图文列表
        $type = input('type/i',0);
        $where = [];
        if($type!=null){
            $where=['t.type'=>$type];
        }
        //获取列表同时获取：分享数、评论数、踩数、点赞数、自己是否点赞、发布者用户名、用户头像、用户ID、是否关注该用户
        $list = Db("text_image t")
            ->where(['t.state'=>1])
            ->where($where)
            ->join("share sh","sh.vid=t.id and sh.type=1",'left')
            ->join("comment c","c.vid=t.id and c.type=1",'left')
            ->join("negative n","n.vid=t.id and n.type=1",'left')
            ->join("negative n1","n1.vid=t.id and n1.type=1 and n1.uid='{$user['id']}'",'left')
            ->join("skr s","s.vid=t.id and s.type=1 and s.skr=1",'left')
            ->join("skr s1","s1.vid=t.id and s1.type=1 and s1.uid='{$user['id']}'",'left')
            ->join("user u","u.id=t.uid")
            ->join("follow f","t.uid=f.follow_id and f.uid = '".$user['id']."'","left")//视频发布者ID等于被关注人ID并且关注用户ID等于当前用户ID
            ->group("t.id")
            ->page($page,20)
            ->field([
                't.id',
                't.title',//标题
                't.content',//内容
                't.type',//内容
                't.uid',//用户ID
                'ifnull(t.images,"") images',//图片地址
                't.create_time',//创建时间
                't.state',//状态
                'count(distinct c.id) comment_count',//评论数
                'count(distinct sh.id) share_count',//分享数
                'count(distinct n.id) negative_count',//踩数
                'count(distinct s.id) skr_count',//点赞数
                'ifnull(s1.skr,0) skr',//是否点赞
                'ifnull(n1.negative,0) negative',//是否点踩
                "ifnull(u.head_img,'static/image/head.png') head_img",//用户头像
                "u.name",//用户名
                "ifnull(f.id,'0') follow",//当前用户是否关注
            ])
            ->order('create_time desc')
            ->select();

        if(!$list){
            return error("暂无数据");
        }
        return success("获取成功",$list);
    }

    /**
     * Notes:添加文字内容或图文内容
     * User: BigNiu
     * Date: 2019/11/19
     * Time: 16:17
     * @return \think\response\Json
     */
    public function postAdd(){

        $user = session("user") ;
        $user = Db("user")->where(['id'=>27])->find();
        if (!$user) {
            return error("未登录");
        }
        $type = input("type/i",0);
        switch($type){
            case 0:
                //纯文本内容
                $content = input("content");
                $title = substr($content,0,50);
                if(strlen($content)<1||strlen($title)<1){
                    return error("请输入内容");
                }
                $data=[
                    "uid"=>$user['id'],
                    'title'=>$title,
                    'content'=>$content,
                    'type'=>$type,
                    'create_time'=>TIME,
                ];
                break;
            case 1:
                //图文内容
                //纯文本内容
                $content = input("content");
                $images = input("images");
                $title = substr($content,0,50);
                if(strlen($content)<1||strlen($title)<1){
                    return error("请输入内容");
                }
                if(!$images||sizeof(explode($images,","))<1){
                    return error("请上传图片");
                }
                $data=[
                    "uid"=>$user['id'],
                    'title'=>$title,
                    'content'=>$content,
                    'images'=>$images,
                    'type'=>$type,
                    'create_time'=>TIME,
                ];
                break;
            default:
                //纯文本内容
                $content = input("content");
                $title = substr($content,0,50);
                if(strlen($content)<1||strlen($title)<1){
                    return error("请输入内容");
                }
                $data=[
                    "uid"=>$user['id'],
                    'title'=>$title,
                    'content'=>$content,
                    'type'=>$type,
                    'create_time'=>TIME,
                ];
        }
        $id = Db("text_image")->insertGetId($data);
        if(!$id){
            return error("添加失败");
        }
        $data['id']=$id;
        return success("添加成功",$data);
    }
}