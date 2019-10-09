<?php


namespace app\api\controller;


use think\Controller;

class Skr extends Controller
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
        $type = input("type/i")==0?0:1;//0为取消点赞，1为点赞
        $vid = input("vid/i");
        $uid = $user['id'];
        $video = Db("video")->where(['id'=>$vid])->find();
        if(!$video)
        {
            u_log("用户".$user['name']."(".$user['id'].")点赞视频<".$video['title']."(".$vid.")>失败，视频已删除");
            return error("点赞失败，视频已删除");
        }
        $skr = Db("skr")->where(['uid'=>$uid,'vid'=>$vid])->find();
        if($skr)
        {
            if($skr['skr']==$type)
            {

                if($type==0)
                {
                    u_log("用户".$user['name']."(".$user['id'].")取消点赞视频<".$video['title']."(".$vid.")>成功");
                    return success("取消点赞成功");
                }
                u_log("用户".$user['name']."(".$user['id'].")点赞视频<".$video['title']."(".$vid.")>成功");
                return success("点赞成功");
            }
            //如果点赞记录已存在，直接修改
            $id = Db("skr")->where(['uid'=>$uid,'vid'=>$vid])->update(['skr'=>$type,'update_time'=>TIME]);
        }else{
            $data = [
                'uid'=>$uid,
                'vid'=>$vid,
                'skr'=>$type,
                'create_time'=>TIME
            ];
            $id = Db("skr")->insertGetId($data);
        }
        if($id)
        {
            if($type==0)
            {
                u_log("用户".$user['name']."(".$user['id'].")取消点赞视频<".$video['title']."(".$vid.")>成功");
                return success("取消点赞成功");
            }
            u_log("用户".$user['name']."(".$user['id'].")点赞视频<".$video['title']."(".$vid.")>成功");
            return success("点赞成功");
        }
        if($type==0)
        {
            u_log("用户".$user['name']."(".$user['id'].")取消点赞视频<".$video['title']."(".$vid.")>失败");
            return error("取消点赞失败");
        }
        u_log("用户".$user['name']."(".$user['id'].")点赞视频<".$video['title']."(".$vid.")>失败");
        return error("点赞失败");
    }
}