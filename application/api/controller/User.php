<?php


namespace app\api\controller;


use think\Controller;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;
use think\response\Json;

class User extends Controller
{
    /**
     * Notes: 登录
     *  1.用户名或密码登录
     *  2.手机验证码登录
     * User: BigNiu
     * Date: 2019/10/8
     * Time: 15:58
     * @return Json
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
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
                        "create_time"=>date("Y-m-d H:i:s",time()),
                        "head_img"=>'static/image/head.png',
                        "name"=>substr($phone,0,3)."****".substr($phone,-4,strlen($phone))
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
     * @return Json
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
     * @return Json
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     * @throws Exception
     */
    public function getUserInfo(){
        $user = session("user");
        if (!$user) {
            return error("未登录");
        }
        $user = Db("user")
            ->where(['id'=>$user['id']])
            ->field([
                'id',
                'ifnull(name,phone) name',
                'password',
                'phone',
                'mail',
                'qq',
                'create_time',
                "ifnull(head_img,'static/image/head.png') head_img"
            ])
            ->find();
        $vids = Db("video")->where(['uid'=>$user['id']])->field("id")->select();
        $ids = array_column($vids,"id");
        $skr_count = Db("skr")->whereIn('vid',$ids)->count('id');//获赞数
        $fans_count = Db("follow")->where(['follow_id'=>$user['id']])->count('id');
        $follow_count = Db("follow")->where(['uid'=>$user['id']])->count('id');
        $password = $user['password'];
        unset($user['password']);
        $user['set_pass']=$password?true:false;//是否设置密码
        $user['skr_count']=$skr_count;//获赞数
        $user['fans_count']=$fans_count;// 粉丝数
        $user['follow_count']=$follow_count;//TODO 关注数
        u_log("用户".$user['name']."(".$user['id'].")获取用户最新信息");
        return success("成功",$user);
    }

    /**
     * Notes:更新用户资料
     * User: BigNiu
     * Date: 2019/10/10
     * Time: 9:31
     * @return Json
     * @throws Exception
     * @throws \think\exception\PDOException
     */
    public function postUpdate(){
        $user = session("user");
        if (!$user) {
            return error("未登录");
        }
        $name = input("name");//昵称
        $head_img = input("head_img");//头像
        $id = input("id");//用户自定义ID
        $mail = input("mail");//邮箱号
        $qq = input("qq/i");//QQ
        $birthday = input("birthday");//生日
        $password = input("password");//新密码
        $old_password = input("old_password");//老密码
        $vcode = input("vcode");//验证码
        $data = [
            'name'=>$name,
            'head_img'=>$head_img,
            'mail'=>$mail,
            'qq'=>$qq,
            'birthday'=>$birthday,

        ];
        //用户有传ID并且未设置自定义ID
        if($id&&!isset($user['custom_id']))
        {
            $data['custom_id']=$id;
        }elseif($id&&isset($user['custom_id']))
        {
            //用户有传ID并且已经设置自定义ID
            return error("用户ID修改后就不能更改哦");
        }
        //用户有传密码
        if($password)
        {
            //如果是修改密码请求
            //获取最新用户信息
            $user = Db("user")->where(['id'=>$user['id']])->find();
            //判断原来是否有设置密码，有设置密码并且有传老密码可通过原密码修改
            if($user['password']&&$old_password)
            {
                if($user['password']!=pass($old_password))
                {
                    return error("原密码错误");
                }
                $data['password']=pass($password);
            }else{
                //没有密码只能通过短信验证码修改
                //TODO 短信验证码效验
                if($vcode)
                {
                    $data['password']=pass($password);
                }
            }

        }
        $data = array_filter($data);
        $result = Db("user")->where(['id'=>$user['id']])->update($data);

        if($result)
        {
            $user=array_merge($user,$data);
            //更新session里的用户信息
            session("user",$user);

            return success("修改成功");
        }
        return error("修改失败");
    }
}