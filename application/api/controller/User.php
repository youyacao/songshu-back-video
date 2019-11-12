<?php


namespace app\api\controller;


use app\api\common\Sms;
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
     *  3,第三方登录
     * 微信登录返回数据：
     *     {
            "access_token": "26_VsXY7jFe0Q68s45mDNdgg15uzZ7iw7V9YVrzHjwi4kPRcHtxWHXx9_bZwtaK-iXBGVjWFEE93EqO_I8cZlGqd_DtrTnesaGbTM1uuGO6T3c",
            "expires_in": 7200,
            "refresh_token": "26_1B7iawAH9A2v2JKrUNqCQCI1Dq1qzQneJNA4-JmPzZ1sWt5KVnBwBLD10wnFGt8JPpCWuzp-AMaEdRX7QdaP54BJ2BUv-3yyQ3gzrricevU",
            "openid": "oRrdQt18I0MOhKLQWpiGXx2qpz70",
            "scope": "snsapi_userinfo",
            "unionid": "oU5Yyt3Vc-2Xo0ytSQ4BpjLS8cWY"
            }
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
                    u_log("用户名:".$username."密码:".$password." 登录失败,用户不存在",'error');
                    return error("用户名或密码错误");
                }
                if(pass($password)!=$user['password'])
                {
                    u_log("用户名:".$username."密码:".$password." 登录失败,密码错误",'error');
                    return error("用户名或密码错误");
                }
                session("user",$user);
                unset($user['password']);
                return success("登录成功",$user);
                break;
            case "phone"://手机验证码登录
                $phone = input("phone/i");//手机号
                $user = Db("user")->where(['phone'=>$phone])->find();
                $code = input("code/i");
                //判断短信验证码是否正确
                if(!Sms::verifySms($phone,$code))
                {
                    u_log("手机用户".$phone."登录失败",'error');
                    return error("验证码错误");
                }
                if(!$user)
                {
                    //用户不存在，自动注册
                    $user = [
                        "phone"=>$phone,
                        "create_time"=>date("Y-m-d H:i:s",time()),
                        "head_img"=>'static/image/head.png',
                        "name"=>substr($phone,0,3)."****".substr($phone,-4,strlen($phone)),
                        "token"=>pass($phone.time().getRandStr()).$phone
                    ];
                    $id = Db("user")->insertGetId($user);
                    u_log("手机用户".$phone."注册成功",'login');
                    $user['id']=$id;
                    session("user",$user);
                    return success("注册成功",$user);
                }
                $token = pass($phone.time().getRandStr()).$phone;
                Db("user")->where(['phone'=>$phone])->update(["token"=>$token]);
                $user['token']=$token;
                session("user",$user);
                unset($user['password']);
                u_log("手机用户".$phone."登录成功",'login');
                return success("登录成功",$user);
                break;

        }
        return error("登录失败");
    }

    /**
     * Notes: 用户根据Token验证身份并登录
     * User: BigNiu
     * Date: 2019/10/30
     * Time: 9:56
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws Exception
     */
    public function getAuth(){
        $token = input("token",null);
        if(!$token){
            return error("验证失败");
        }
        $user = Db("user")->where(["token"=>$token])->find();
        if($user){
            session("user",$user);
            $vids = Db("video")->where(['uid'=>$user['id']])->field("id")->select();
            $ids = array_column($vids,"id");
            $skr_count = Db("skr")->whereIn('vid',$ids)->count('id');//获赞数
            $fans_count = Db("follow")->where(['follow_id'=>$user['id']])->count('id');
            $follow_count = Db("follow")->where(['uid'=>$user['id']])->count('id');
            unset($user['password']);
            $user['skr_count']=$skr_count;//获赞数
            $user['fans_count']=$fans_count;// 粉丝数
            $user['follow_count']=$follow_count;//关注数
            u_log("用户".$user['name']."(".$user['id'].")通过Token验证登录成功");
            return success("验证成功",$user);
        }
        return error("验证失败");
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
                "ifnull(head_img,'static/image/head.png') head_img",
                "custom_id",
                "token"
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
        $user['follow_count']=$follow_count;//关注数
        u_log("用户".$user['name']."(".$user['id'].")获取用户最新信息");
        return success("成功",$user);
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
    public function getOtherUserInfo(){
        $user = session("user");
        /*if (!$user) {
            return error("未登录");
        }*/
        $uid = input('uid/i');
        $follow = Db("follow")->where(['uid'=>$user['id'],'follow_id'=>$uid])->find();

        $user = Db("user")
            ->where(['id'=>$uid])
            ->field([
                'id',
                'ifnull(name,phone) name',
                'create_time',
                "ifnull(head_img,'static/image/head.png') head_img",
                "custom_id"
            ])
            ->find();
        if(!$user){
            return error("用户不存在");
        }

        $vids = Db("video")->where(['uid'=>$user['id']])->field("id")->select();
        $ids = array_column($vids,"id");
        $skr_count = Db("skr")->whereIn('vid',$ids)->count('id');//获赞数
        $fans_count = Db("follow")->where(['follow_id'=>$user['id']])->count('id');
        $follow_count = Db("follow")->where(['uid'=>$user['id']])->count('id');
        $user['skr_count']=$skr_count;//获赞数
        $user['fans_count']=$fans_count;// 粉丝数
        $user['follow_count']=$follow_count;//关注数

        $user['follow']=$follow!=null?true:false;
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
        $id = input("custom_id");//用户自定义ID
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

    /**
     * Notes:获取自己的关注列表
     * User: BigNiu
     * Date: 2019/10/23
     * Time: 9:49
     */
    public function getFollow(){
        $user = session("user");
        if (!$user) {
            return error("未登录");
        }
        $page = input("page/i", 1) <= 1 ? 1 : input("page/i", 1);
        $follow = Db("user u")
            ->join("follow f","u.id=f.follow_id",'left')
            ->join("follow f1","f1.follow_id=u.id and f1.uid = '".$user['id']."'","left")//视频发布者ID等于被关注人ID并且关注用户ID等于当前用户ID
            ->group("u.id")
            ->where("f.uid='{$user['id']}'")
            ->field([
                'u.id',//用户id
                'u.name',//用户名
                "ifnull(u.head_img,'static/image/head.png') head_img",//头像
                "count(f.id) follow_count",//关注数
                "ifnull(f1.create_time,'0') follow",//当前用户是否关注
            ])
            ->order('follow desc')
            ->page($page, 20)
            ->select();
        if(!$follow){
            return error("暂无数据");
        }
        return success("成功",$follow);
    }
    /**
     * Notes:获取自己的粉丝列表
     * User: BigNiu
     * Date: 2019/10/23
     * Time: 9:49
     */
    public function getFans(){
        $user = session("user");
        if (!$user) {
            return error("未登录");
        }
        $page = input("page/i", 1) <= 1 ? 1 : input("page/i", 1);
        $follow = Db("user u")
            ->join("follow f","u.id=f.follow_id",'left')
            ->join("follow f1","f1.follow_id=u.id and f1.uid = '".$user['id']."'","left")//视频发布者ID等于被关注人ID并且关注用户ID等于当前用户ID
            ->group("u.id")
            ->where("f.follow_id='{$user['id']}'")
            ->field([
                'u.id',//用户id
                'u.name',//用户名
                "ifnull(u.head_img,'static/image/head.png') head_img",//头像
                "count(f.id) follow_count",//关注数
                "ifnull(f1.create_time,'0') follow",//当前用户是否关注
            ])
            ->order('follow desc')
            ->page($page, 20)
            ->select();
        if(!$follow){
            return error("暂无数据");
        }
        return success("成功",$follow);
    }
    /**
     * Notes:退出登录
     * User: BigNiu
     * Date: 2019/10/9
     * Time: 14:51
     * @return Json
     */
    public function getLogout(){
        $user = session("user");
        if($user){
            Db("user")->where(['id'=>$user['id']])->update(['token'=>null]);
        }
        session("user",null);
        return success("退出登录成功");
    }

}