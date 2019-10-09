<?php


namespace app\api\controller;


use think\Controller;

class User extends Controller
{
    /**
     * Notes: 登录
     *  1.用户名或密码登录
     *  2.手机验证码登录
     * User: BigNiu
     * Date: 2019/10/8
     * Time: 15:58
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getLogin(){

        $type = input("type");
        switch ($type)
        {
            case "uap"://user and pass
                $username = input("username");//用户名
                $password = input("password");//密码
                $vcode = input("vcode");//验证码
                $user = Db("user")->where(['name'=>$username])->find();
                if(!$user)
                {
                    return error("用户名或密码错误");
                }
                if(pass($password)!=$user['password'])
                {
                    return error("用户名或密码错误");
                }
                session("user",$user);
                unset($user['password']);
                return success("登录成功",$user);
                break;
            case "phone"://手机验证码登录
                $phone = input("phone");//手机号
                $user = Db("user")->where(['phone'=>$phone])->find();
                $code = input("code");
                //TODO 判断短信验证码是否正确
                if(false)
                {
                    return error("验证码错误");
                }
                if(!$user)
                {
                    //用户不存在，自动注册
                    $user = [
                        "phone"=>$phone,
                        "create_time"=>date("Y-m-d H:i:s",time())
                    ];
                    $id = Db("user")->insertGetId($user);
                    u_log("手机用户".$phone."注册成功");
                    $user['id']=$id;
                    session("user",$user);
                    return success("注册成功",$user);
                }
                session("user",$user);
                unset($user['password']);
                return success("登录成功",$user);
                break;

        }
        return error("登录失败");
    }

    /**
     * Notes:退出登录
     * User: BigNiu
     * Date: 2019/10/9
     * Time: 14:51
     * @return \think\response\Json
     */
    public function getLogout(){
        session("user",null);
        return success("退出登录成功");
    }


    /**
     * Notes:获取用户信息
     * User: BigNiu
     * Date: 2019/10/9
     * Time: 15:11
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserInfo(){
        $user = session("user");
        if (!$user) {
            return error("未登录");
        }
        $user = Db("user")->where(['id'=>$user['id']])->find();
        $vids = Db("video")->where(['uid'=>$user['id']])->field("id")->select();
        $ids = array_column($vids,"id");
        $skr_count = Db("skr")->whereIn('vid',$ids)->field("count(id) count")->group("vid")->find();
        $user['skr_count']=$skr_count['count'];
        $user['fans_count']=0;//TODO 粉丝数
        $user['follow_count']=0;//TODO 关注数
        u_log("用户".$user['name']."(".$user['id'].")获取用户最新信息");
        return success("成功",$user);
    }
    //TODO 更新用户资料
    public function postUpdate(){
        $username = input("username");
        $head_img = input("head_img");
        $mail = input("head_img");
        $qq = input("qq");
        
    }
}