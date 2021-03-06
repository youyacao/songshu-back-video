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
        $type = input("type/i",0);
        $uSkr = input("skr/i")==0?0:1;//0为取消点赞，1为点赞
        $vid = input("vid/i");
        $uid = $user['id'];
        switch ($type){
            case 0:
                $video = Db("video")->where(['id'=>$vid])->find();
                if(!$video)
                {
                    u_log("用户".$user['name']."(".$user['id'].")点赞".typeToName($type)."<".$video['title']."(".$vid.")>失败，".typeToName($type)."已删除");
                    return error("点赞失败，".typeToName($type)."已删除");
                }
                break;
            case 1:
                $text_image = Db("text_image")->where(['id'=>$vid])->find();
                if(!$text_image)
                {
                    u_log("用户".$user['name']."(".$user['id'].")点赞".typeToName($type)."<".$text_image['title']."(".$vid.")>失败，".typeToName($type)."已删除");
                    return error("点赞失败，".typeToName($type)."已删除");
                }
                break;
        }

        $skr = Db("skr")->where(['uid'=>$uid,'vid'=>$vid,'type'=>$type])->find();
        $negative = Db("negative")->where(['uid'=>$uid,'negative'=>1,'vid'=>$vid,'type'=>$type])->find();
        if($negative){
            return error("点踩后就不能反悔哦");
        }

        if($skr)
        {
            if($skr['skr']==$uSkr)
            {

                if($uSkr==0)
                {
                    u_log("用户".$user['name']."(".$user['id'].")取消点赞".typeToName($type)."(".$vid.")成功");
                    return success("取消点赞成功");
                }
                u_log("用户".$user['name']."(".$user['id'].")点赞".typeToName($type)."(".$vid.")成功");
                return success("点赞成功");
            }
            //如果点赞记录已存在，直接修改
            $id = Db("skr")->where(['uid'=>$uid,'vid'=>$vid,'type'=>$type])->update(['skr'=>$uSkr,'update_time'=>TIME]);
        }else{
            $data = [
                'uid'=>$uid,
                'vid'=>$vid,
                'skr'=>$uSkr,
                'create_time'=>TIME,
                'type'=>$type
            ];
            $id = Db("skr")->insertGetId($data);
        }
        if($id)
        {
            if($uSkr==0)
            {
                u_log("用户".$user['name']."(".$user['id'].")取消点赞".typeToName($type)."(".$vid.")>成功");
                return success("取消点赞成功");
            }
            u_log("用户".$user['name']."(".$user['id'].")点赞".typeToName($type)."(".$vid.")>成功");
            return success("点赞成功");
        }
        if($uSkr==0)
        {
            u_log("用户".$user['name']."(".$user['id'].")取消点赞视频<".$video['title']."(".$vid.")>失败");
            return error("取消点赞失败");
        }
        u_log("用户".$user['name']."(".$user['id'].")点赞视频<".$video['title']."(".$vid.")>失败");
        return error("点赞失败");
    }
}