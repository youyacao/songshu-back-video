<?php


namespace app\api\controller;


use think\Controller;

class Negative extends Controller
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
        $vid = input("vid/i");
        $uid = $user['id'];
        switch ($type){
            case 0:
                $video = Db("video")->where(['id'=>$vid])->find();
                if(!$video)
                {
                    u_log("用户".$user['name']."(".$user['id'].")点踩".typeToName($type)."<".$video['title']."(".$vid.")>失败，".typeToName($type)."已删除");
                    return error("点踩失败，".typeToName($type)."已删除");
                }
                break;
            case 1:
                $text_image = Db("text_image")->where(['id'=>$vid])->find();
                if(!$text_image)
                {
                    u_log("用户".$user['name']."(".$user['id'].")点踩".typeToName($type)."<".$text_image['title']."(".$vid.")>失败，".typeToName($type)."已删除");
                    return error("点踩失败，".typeToName($type)."已删除");
                }
                break;
        }

        $negative = Db("negative")->where(['uid'=>$uid,'vid'=>$vid,'type'=>$type])->find();
        $skr = Db("skr")->where(['uid'=>$uid,'skr'=>1,'vid'=>$vid,'type'=>$type])->find();
        if($skr){
            return error("点赞后不能反悔哦");
        }
        if($negative)
        {
            if($negative['negative']==$uNegative)
            {

                if($uNegative==0)
                {
                    u_log("用户".$user['name']."(".$user['id'].")取消点踩".typeToName($type)."(".$vid.")成功");
                    return success("取消点踩成功");
                }
                u_log("用户".$user['name']."(".$user['id'].")点踩".typeToName($type)."(".$vid.")成功");
                return success("点踩成功");
            }
            //如果点踩记录已存在，直接修改
            $id = Db("negative")->where(['uid'=>$uid,'vid'=>$vid,'type'=>$type])->update(['negative'=>$uNegative,'update_time'=>TIME]);
        }else{
            $data = [
                'uid'=>$uid,
                'vid'=>$vid,
                'negative'=>$uNegative,
                'create_time'=>TIME,
                'type'=>$type
            ];
            $id = Db("negative")->insertGetId($data);
        }
        if($id)
        {
            if($uNegative==0)
            {
                u_log("用户".$user['name']."(".$user['id'].")取消点踩".typeToName($type)."(".$vid.")>成功");
                return success("取消点踩成功");
            }
            u_log("用户".$user['name']."(".$user['id'].")点踩".typeToName($type)."(".$vid.")>成功");
            return success("点踩成功");
        }
        if($uNegative==0)
        {
            u_log("用户".$user['name']."(".$user['id'].")取消点踩视频<".$video['title']."(".$vid.")>失败");
            return error("取消点踩失败");
        }
        u_log("用户".$user['name']."(".$user['id'].")点踩视频<".$video['title']."(".$vid.")>失败");
        return error("点踩失败");
    }
}