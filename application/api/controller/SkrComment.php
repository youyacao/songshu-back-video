<!-- 
All rights Reserved, Designed By www.youyacao.com 
@Description:视频点赞方法文件
@author:成都市一颗优雅草科技有限公司     
@version 松鼠短视频系统-后端部分
注意：后端代码在获得授权之前通过其他非官方渠道获得代码均为侵权，禁止用于商业用途，否则将承担因此带来等版权纠纷。
需要商业用途或者定制开发等可访问songshu.youyacao.com   联系QQ:422108995 23625059584

 -->


<?php


namespace app\api\controller;


use think\Controller;

class SkrComment extends Controller
{
    /**
     * Notes:点赞/取消点赞操作
     * User: BigNiu
     * Date: 2019/10/8
     * Time: 17:57
     */
    public function getLike(){
        $user = session("user") ;

        if (!$user) {
            return error("未登录");
        }
        $type = input("type/i",0);
        $uSkr = input("skr/i")==0?0:1;//0为取消点赞，1为点赞
        $cid = input("cid/i");
        $vid = input("vid/i");
        $uid = $user['id'];
        switch ($type){
            case 0:
                $video = Db("video")->where(['id'=>$vid])->find();
                if(!$video)
                {
                    u_log("用户".$user['name']."(".$user['id'].")点赞<".$video['title']."(".$vid.")>的评论失败，".typeToName($type)."已删除");
                    return error("点赞失败，".typeToName($type)."已删除");
                }
                break;
            case 1:
                $text_image = Db("text_image")->where(['id'=>$vid])->find();
                if(!$text_image)
                {
                    u_log("用户".$user['name']."(".$user['id'].")点赞".typeToName($type)."<".$text_image['title']."(".$vid.")>的评论失败，".typeToName($type)."已删除");
                    return error("点赞失败，".typeToName($type)."已删除");
                }
                break;
        }

        $comment = Db("comment")->where(['id'=>$cid,"type"=>$type])->find();
        if(!$comment)
        {
            u_log("用户".$user['name']."(".$user['id'].")点赞".typeToName($type)."<".$vid.">的评论失败，评论已删除");
            return error("点赞失败，该评论已删除");
        }
        $skr_comment = Db("skr_comment")->where(['uid'=>$uid,'cid'=>$cid,'vid'=>$vid,'type'=>$type])->find();
        $negative = Db("negative_comment")->where(['uid'=>$uid,'negative'=>1,'cid'=>$cid,'vid'=>$vid,'type'=>$type])->find();
        if($negative){
            return error("点踩后就不能反悔哦");
        }
        if($skr_comment)
        {
            if($skr_comment['skr']==$uSkr)
            {

                if($uSkr==0)
                {
                    u_log("用户".$user['name']."(".$user['id'].")取消点赞".typeToName($type)."<(".$vid.")>的评论“".$comment['content']."”成功");
                    return success("取消点赞成功");
                }
                u_log("用户".$user['name']."(".$user['id'].")点赞".typeToName($type)."<(".$vid.")>的评论“".$comment['content']."”成功");
                return success("点赞成功");
            }
            //如果点赞记录已存在，直接修改
            $id = Db("skr_comment")->where(['uid'=>$uid,'cid'=>$cid,'vid'=>$vid])->update(['skr'=>$uSkr,'update_time'=>TIME]);
        }else{
            //如果不存在，新增一条记录
            $data = [
                'uid'=>$uid,
                'vid'=>$vid,
                'cid'=>$cid,
                'skr'=>$uSkr,
                'type'=>$type,
                'create_time'=>TIME
            ];
            $id = Db("skr_comment")->insertGetId($data);
        }
        if($id)
        {
            if($uSkr==0)
            {
                u_log("用户".$user['name']."(".$user['id'].")取消点赞".typeToName($type)."<(".$vid.")>的评论“".$comment['content']."”成功");
                return success("取消点赞成功");
            }
            u_log("用户".$user['name']."(".$user['id'].")点赞".typeToName($type)."<(".$vid.")>的评论“".$comment['content']."”成功");
            return success("点赞成功");
        }
        if($uSkr==0)
        {
            u_log("用户".$user['name']."(".$user['id'].")取消点赞".typeToName($type)."<(".$vid.")>的评论“".$comment['content']."”失败");
            return error("取消点赞失败");
        }
        u_log("用户".$user['name']."(".$user['id'].")点赞".typeToName($type)."<(".$vid.")>的评论“".$comment['content']."”失败");
        return error("点赞失败");
    }
}