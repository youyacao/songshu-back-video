<?php


namespace app\api\controller;


use think\Controller;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\response\Json;

/**
 * 评论类
 * Class Comment
 * @package app\api\controller
 */
class Comment extends Controller
{
    /**
     * Notes:根据视频ID获取评论列表
     * User: BigNiu
     * Date: 2019/10/8
     * Time: 15:37
     * @return Json
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public function getList(){
        $user = session("user") ? session("user") : session("guest_user");
        //判断当前用户是否登录
        if (!$user) {
            //未登录，使用访客用户
            session("guest_user", ['id' => adminpass(header("user-agent") . time())]);
            $user = session("guest_user");
        }

        $uid = $user['id'];
        $type = input("type/i",0);
        $vid = input("vid");
        $cid = input("cid",0);
        $page = input("page",1);
        $comments = Db("comment c")
            ->where(['c.vid'=>$vid,"pid"=>$cid,"state"=>0,"c.type"=>$type])
            ->join("user u","c.uid=u.id","left")
            ->join("skr_comment s","c.id=s.cid and s.skr=1 and s.type={$type}","left")
            ->join("negative_comment n"," n.cid=c.id and n.type={$type} and n.negative=1",'left')
            ->join("negative_comment n1","n1.vid={$vid} and n.cid=c.id and n1.type={$type} and n1.uid='{$user['id']}'",'left')
            ->join("skr_comment s1","c.id=s1.cid and s1.uid='{$uid}' and s1.type={$type}","left")
            ->field([
                "c.id",//评论ID
                "c.content",//评论内容
                "c.vid",//视频ID
                "c.pid",//上级评论ID
                "c.uid",//评论用户ID
                "c.create_time create_time ",//评论时间
//                "date_format( c.create_time, '%m-%d %h:%i' ) AS create_time ",//评论时间
                "u.name",//评论用户名
                "ifnull(u.head_img,'static/image/head.png') head_img",//评论用户头像
                "count(distinct s.id) skr_count",//评论点赞数
                "count(distinct n.id) negative_count",//评论点踩数
                'ifnull(n1.negative,0) negative',//是否点踩
                "ifnull(s1.skr,0) skr",//当前用户是否点赞
            ])
            ->page($page,10)
            ->order("c.create_time desc")
            ->group("c.id")
            ->select();

        $comments=$this->subComment($comments,$vid,$type,$uid);
        if(!$comments)
        {
            if($page>1)
            {
                return error("暂无更多评论");
            }
            return error("暂无评论");
        }
        return success("成功",$comments);
    }

    /**
     * Notes:获取下级评论列表
     * User: BigNiu
     * Date: 2019/10/8
     * Time: 14:41
     * @param $comments
     * @param $vid
     * @param $type
     * @param $uid
     * @return mixed
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    private function subComment($comments,$vid,$type,$uid){
        foreach ($comments  as $key=>$item)
        {

            $subcomments = Db("comment c")
                ->where(['c.vid'=>$vid,"c.pid"=>$item['id'],"state"=>0,"c.type"=>$type])
                ->join("user u","c.uid=u.id","left")
                ->join("skr_comment s","c.id=s.cid and s.skr=1 and s.type={$type}","left")
                ->join("negative_comment n","n.vid=c.vid and n.cid=c.id and n.type={$type} and n.negative=1",'left')
                ->join("negative_comment n1",
                    "n1.vid={$vid} and n1.cid=c.id and n1.type={$type} and n1.uid='{$uid}'",
                    'left')//帖子ID等于当前帖子ID，评论ID等于当前评论ID，评论类型等于当前评论类型，用户ID等于当前评论ID
                ->join("skr_comment s1","c.id=s1.cid and s1.uid='{$uid}' and s1.type={$type}","left")
                ->field([
                    "c.id",//评论ID
                    "c.content",//评论内容
                    "c.vid",//视频ID
                    "c.pid",//上级评论ID
                    "c.uid",//评论用户ID
                    "c.create_time create_time ",//评论时间
//                "date_format( c.create_time, '%m-%d %h:%i' ) AS create_time ",//评论时间
                    "u.name",//评论用户名
                    "ifnull(u.head_img,'static/image/head.png') head_img",//评论用户头像
                    "count(distinct s.id) skr_count",//评论点赞数
                    "count(distinct n.id) negative_count",//评论点踩数
                    'ifnull(n1.negative,0) negative',//是否点踩
                    "ifnull(s1.skr,0) skr",//当前用户是否点赞
                ])
                ->page(0 ,10)
                ->group("c.id")
                ->order("skr_count ,c.create_time")
                ->select();
            $sub_comment_count = Db("comment c")
                ->where(['c.vid'=>$vid,"c.pid"=>$item['id'],"state"=>0,"c.type"=>$type])->count();
            $subcomments=$this->subComment($subcomments,$vid,$type,$uid);
            $comments[$key]['sub_comment']=$subcomments;
            $comments[$key]['sub_comment_count']=$sub_comment_count;

        }
        return $comments;
    }

    /**
     * Notes:发送评论
     * User: BigNiu
     * Date: 2019/10/8
     * Time: 14:41
     */
    public function postData()
    {
        $user = session("user") ;

        if (!$user) {
            return error("未登录");
        }
        $type = input("type/i",0);
        $vid = input("vid/i");//视频ID
        $uid = $user['id'];//用户ID
        $pid = input("pid/i");//上级评论ID
        $content = input("content");//评论内容
        switch ($type){
            case 0:
                $video = Db("video")->where(["id" => $vid])->find();
                if (!$video)
                {
                    u_log("用户".$user['name']."(".$user['id'].")发送评论(".$content.")失败","error");
                    return error("该视频已删除");
                }
                break;
            case 1:
                $text_image = Db("text_image")->where(["id" => $vid])->find();
                if (!$text_image)
                {
                    u_log("用户".$user['name']."(".$user['id'].")发送评论(".$content.")失败","error");
                    return error("该信息已删除");
                }
                break;
        }

        if($pid&&$pid>0) {
            //视频二级评论
            $pComment = Db("comment")->where(["id" => $pid,"state"=>0,"type"=>$type])->find();
            if (!$pComment)
            {
                u_log("用户".$user['name']."(".$user['id'].")发送评论(".$content.")失败","error");
                return error("该评论已删除");
            }
            //上级评论存在，开始增加评论
            $data = [
                "uid"=>$uid,
                "content"=>$content,
                "vid"=>$vid,
                "create_time"=>TIME,
                "pid"=>$pid,
                "type"=>$type
            ];
            $id = Db("comment")->insertGetId($data);
            $data["id"]=$id;
            u_log("用户".$user['name']."(".$user['id'].")发送评论(".$content.")成功");
            return success("评论成功",$data);
        }else{
            //视频一级评论
            $data = [
                "uid"=>$uid,
                "content"=>$content,
                "vid"=>$vid,
                "create_time"=>TIME,
                "pid"=>0,
                "type"=>$type
            ];
            $id = Db("comment")->insertGetId($data);
            $data["id"]=$id;
            u_log("用户".$user['name']."(".$user['id'].")发送评论(".$content.")成功");
//            $data['create_time']=date("m-d H:i",time());
            return success("评论成功",$data);
        }
    }

    /**
     * Notes:遍历删除评论
     * User: BigNiu
     * Date: 2019/10/8
     * Time: 16:41
     * @param $comment
     * @param $ids
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    private function delComment($comment,$ids,$type){
        $ids[]=$comment['id'];
        $sub_comments = Db("comment")->where(['pid'=>$comment['id'],"type"=>$type])->select();
        foreach ($sub_comments as $key=>$item)
        {
            //遍历删除
            $ids[]=$item['id'];
            $ids=$this->delComment($item,$ids,$type);
        }
        return $ids;
    }
    /**
     * Notes:删除评论
     * User: BigNiu
     * Date: 2019/10/8
     * Time: 15:43
     * @return Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function postDel(){
        $user = session("user") ;
        if (!$user) {
            return error("未登录");
        }
        $vid = input("vid/i");//视频ID
        $type = input("type/i",0);//视频ID
        $cid = input("cid/i");//评论ID
        $comment = Db("comment")->where(['id'=>$cid,'uid'=>$user['id']])->find();
        //遍历删除下级评论
        $ids = [];//用于存储待删除的下级ID
        $ids = $this->delComment($comment,$ids,$type);

        $res = Db("comment")->where('id','in',$ids)->update(['state'=>"1"]);

        if($res)
        {
            u_log("用户".$user['name']."(".$user['id'].")删除评论(".$comment['content'].")成功");
            return success("删除成功");
        }
        u_log("用户".$user['name']."(".$user['id'].")删除评论(".$comment['content'].")成功");
        return error("删除失败，请稍后再试");
    }
}