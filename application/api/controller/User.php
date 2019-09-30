<?php


namespace app\api\controller;


use think\Controller;

class User extends Controller
{
    /**
     * 登录
     *  1.用户名或密码登录
     *  2.手机验证码登录
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
    public function postUpdate(){
        $username = input("username");
        $head_img = input("head_img");
        $mail = input("head_img");
        $qq = input("qq");

    }
}