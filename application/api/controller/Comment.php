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
        $vid = input("vid");
        $cid = input("cid",0);
        $page = input("page",1);
        $comments = Db("comment c")
            ->where(['c.vid'=>$vid,"pid"=>$cid,"state"=>0])
            ->join("user u","c.uid=u.id","left")
            ->join("skr_comment s","c.id=s.cid","left")
            ->field([
                "c.id",//评论ID
                "c.content",//评论内容
                "c.vid",//视频ID
                "c.pid",//上级评论ID
                "c.uid",//评论用户ID
                "u.name",//评论用户名
                "ifnull(u.head_img,'static/image/head.png') head_img",//评论用户头像
                "count(distinct s.id) skr_count"//评论点赞数
            ])
            ->page($page,10)
            ->order("c.create_time desc")
            ->group("c.id")
            ->select();
        $comments=$this->subComment($comments,$vid);
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
     * @return mixed
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    private function subComment($comments,$vid){
        foreach ($comments  as $key=>$item)
        {

            $subcomments = Db("comment c")
                ->where(['c.vid'=>$vid,"c.pid"=>$item['id'],"state"=>0])
                ->join("user u","c.uid=u.id","left")
                ->join("skr_comment s","c.id=s.cid","left")
                ->field([
                    "c.id",//评论ID
                    "c.content",//评论内容
                    "c.vid",//视频ID
                    "c.pid",//上级评论ID
                    "c.uid",//评论用户ID
                    "u.name",//评论用户名
                    "ifnull(u.head_img,'static/image/head.png') head_img",//评论用户头像
                    "count(distinct s.id) skr_count"//评论点赞数
                ])
                ->page(0 ,10)
                ->group("c.id")
                ->order("c.create_time")
                ->select();
            $subcomments=$this->subComment($subcomments,$vid);
            $comments[$key]['sub_comment']=$subcomments;

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
        $vid = input("vid/i");//视频ID
        $uid = $user['id'];//用户ID
        $pid = input("pid/i");//上级评论ID
        $content = input("content");//评论内容
        $video = Db("video")->where(["id" => $vid,"state"=>0])->find();
        if (!$video)
        {
            u_log("用户".$user['name']."(".$user['id'].")发送评论(".$content.")失败","error");
            return error("该视频已删除");
        }
        if($pid&&$pid>0) {
            //视频二级评论
            $pComment = Db("comment")->where(["id" => $pid,"state"=>0])->find();
            if (!$pComment)
            {
                u_log("用户".$user['name']."(".$user['id'].")发送评论(".$content.")失败","error");
                return error("该评论已删除");
            }
            //上级评论存在，开始增加评论
            $data = [
                "uid"=>$uid,
                "content"=>$content,
                "type"=>2,
                "vid"=>$vid,
                "create_time"=>TIME,
                "pid"=>$pid
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
                "type"=>1,
                "vid"=>$vid,
                "create_time"=>TIME,
                "pid"=>0
            ];
            $id = Db("comment")->insertGetId($data);
            $data["id"]=$id;
            u_log("用户".$user['name']."(".$user['id'].")发送评论(".$content.")成功");
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
    private function delComment($comment,$ids){
        $ids[]=$comment['id'];
        $sub_comments = Db("comment")->where(['pid'=>$comment['id']])->select();
        foreach ($sub_comments as $key=>$item)
        {
            //遍历删除
            $ids[]=$item['id'];
            $ids=$this->delComment($item,$ids);
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
        $cid = input("cid/i");//评论ID
        $comment = Db("comment")->where(['id'=>$cid,'uid'=>$user['id']])->find();
        //遍历删除下级评论
        $ids = [];//用于存储待删除的下级ID
        $ids = $this->delComment($comment,$ids);

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