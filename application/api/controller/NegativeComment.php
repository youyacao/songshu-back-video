<?php


namespace app\api\controller;


use think\Controller;

class NegativeComment extends Controller
{
    /**
     * Notes:点踩/取消点踩操作
     * User: BigNiu
     * Date: 2019/10/8
     * Time: 17:57
     */
    public function getNegative(){
        $user = session("user") ;

        if (!$user) {
            return error("未登录");
        }
        $type = input("type/i",0);
        $uNegative = input("negative/i")==0?0:1;//0为取消点踩，1为点踩
        $cid = input("cid/i");
        $vid = input("vid/i");
        $uid = $user['id'];
        switch ($type){
            case 0:
                $video = Db("video")->where(['id'=>$vid])->find();
                if(!$video)
                {
                    u_log("用户".$user['name']."(".$user['id'].")点踩<".$video['title']."(".$vid.")>的评论失败，".typeToName($type)."已删除");
                    return error("点踩失败，".typeToName($type)."已删除");
                }
                break;
            case 1:
                $text_image = Db("text_image")->where(['id'=>$vid])->find();
                if(!$text_image)
                {
                    u_log("用户".$user['name']."(".$user['id'].")点踩".typeToName($type)."<".$text_image['title']."(".$vid.")>的评论失败，".typeToName($type)."已删除");
                    return error("点踩失败，".typeToName($type)."已删除");
                }
                break;
        }

        $comment = Db("comment")->where(['id'=>$cid,"type"=>$type])->find();
        if(!$comment)
        {
            u_log("用户".$user['name']."(".$user['id'].")点踩".typeToName($type)."<".$vid.">的评论失败，评论已删除");
            return error("点踩失败，该评论已删除");
        }
        $negative_comment = Db("negative_comment")->where(['uid'=>$uid,'cid'=>$cid,'vid'=>$vid,'type'=>$type])->find();
        $skr = Db("skr_comment")->where(['uid'=>$uid,'skr'=>1,'cid'=>$cid,'vid'=>$vid,'type'=>$type])->find();

        if($skr){
            return error("点赞后不能反悔哦");
        }
        if($negative_comment)
        {
            if($negative_comment['negative']==$uNegative)
            {

                if($uNegative==0)
                {
                    u_log("用户".$user['name']."(".$user['id'].")取消点踩".typeToName($type)."<(".$vid.")>的评论“".$comment['content']."”成功");
                    return success("取消点踩成功");
                }
                u_log("用户".$user['name']."(".$user['id'].")点踩".typeToName($type)."<(".$vid.")>的评论“".$comment['content']."”成功");
                return success("点踩成功");
            }
            //如果点踩记录已存在，直接修改
            $id = Db("negative_comment")->where(['uid'=>$uid,'cid'=>$cid,'vid'=>$vid])->update(['negative'=>$uNegative,'update_time'=>TIME]);
        }else{
            //如果不存在，新增一条记录
            $data = [
                'uid'=>$uid,
                'vid'=>$vid,
                'cid'=>$cid,
                'negative'=>$uNegative,
                'type'=>$type,
                'create_time'=>TIME
            ];
            $id = Db("negative_comment")->insertGetId($data);
        }
        if($id)
        {
            if($uNegative==0)
            {
                u_log("用户".$user['name']."(".$user['id'].")取消点踩".typeToName($type)."<(".$vid.")>的评论“".$comment['content']."”成功");
                return success("取消点踩成功");
            }
            u_log("用户".$user['name']."(".$user['id'].")点踩".typeToName($type)."<(".$vid.")>的评论“".$comment['content']."”成功");
            return success("点踩成功");
        }
        if($uNegative==0)
        {
            u_log("用户".$user['name']."(".$user['id'].")取消点踩".typeToName($type)."<(".$vid.")>的评论“".$comment['content']."”失败");
            return error("取消点踩失败");
        }
        u_log("用户".$user['name']."(".$user['id'].")点踩".typeToName($type)."<(".$vid.")>的评论“".$comment['content']."”失败");
        return error("点踩失败");
    }
}