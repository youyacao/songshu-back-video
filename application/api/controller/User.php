<?php


namespace app\api\controller;


use app\api\common\Mail;
use app\api\common\Sms;
use think\captcha\Captcha;
use think\Controller;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;
use think\response\Json;
use think\Db;

class User extends Controller
{
    /**
     * Notes:生成一个邀请码
     * User: JackXie
     * Date: 2020/02/22
     * Time: 18:12
     * @return String
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    private function getInvite()
    {
        $str = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';
        for ($i = 0; $i < 6; $i++) {
            $code .= $str[rand(0, strlen($str) - 1)];
        }
        if (Db('user')->where('invit_code', $code)->find()) {
            return $this->getInvite();
        }
        return $code;
    }

    /**
     * Notes:执行邀请成功后的奖励操作
     * User: JackXie
     * Date: 2020/02/22
     * Time: 20:59
     * @param $user 被邀请用户
     * @param $invitor 邀请人
     */
    private function invite_do($user, $invitor)
    {
        $bonus_start = intval(config('bonus_start'));
        $bonus_end = intval(config('bonus_end'));
        if ($bonus_end <= $bonus_start || $bonus_start < 0) return;
        $bonus = mt_rand($bonus_start, $bonus_end);
        // 启动事务
        Db::startTrans();
        try {
            //添加邀请记录
            $data = array();
            $data['user_id'] = $invitor['id'];
            $data['invite_uid'] = $user['id'];
            $data['invite_code'] = $user['reg_code'];
            $data['bonus'] = $bonus;
            $data['created_at'] = date('Y-m-d H:i:s');

            $id = Db("invite")->insertGetId($data);

            $invitor = Db("user")->where('id', $invitor['id'])->find();
            $allMoney = $invitor['money'] + $bonus;

            if (!Db('user')->where('id', $invitor['id'])->update(['money' => $allMoney])) {
                Db::rollback();
                return;
            }

            // 推广获取免费次数
            $num = db("config")->where(array("name" => 'video_free_num'))->value('value');
            if ($num) {
                $invit_free_num = db("config")->where(array("name" => 'invit_free_num'))->value('value');
                $res = Db('user')->where('id', $user['id'])->setInc('invit_get_num', $invit_free_num);
                if (!$res) {
                    Db::rollback();
                    return;
                }
            }

            //添加账变记灵
            $data = array();
            $data['user_id'] = $invitor['id'];
            $data['num'] = $bonus;
            $data['before_money'] = $invitor['money'];
            $data['after_money'] = $allMoney;
            $data['info'] = '推广奖励';
            $data['data_id'] = $id;
            $data['data_type'] = 'invite';
            $data['created_at'] = date('Y-m-d H:i:s');
            Db('account_change')->insert($data);


            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
    }

    /**
     * Notes: 登录
     *  1.用户名或密码登录
     *  2.手机验证码登录
     *  3,第三方登录
     * 微信登录返回数据：
     *     {
     * "access_token": "26_VsXY7jFe0Q68s45mDNdgg15uzZ7iw7V9YVrzHjwi4kPRcHtxWHXx9_bZwtaK-iXBGVjWFEE93EqO_I8cZlGqd_DtrTnesaGbTM1uuGO6T3c",
     * "expires_in": 7200,
     * "refresh_token": "26_1B7iawAH9A2v2JKrUNqCQCI1Dq1qzQneJNA4-JmPzZ1sWt5KVnBwBLD10wnFGt8JPpCWuzp-AMaEdRX7QdaP54BJ2BUv-3yyQ3gzrricevU",
     * "openid": "oRrdQt18I0MOhKLQWpiGXx2qpz70",
     * "scope": "snsapi_userinfo",
     * "unionid": "oU5Yyt3Vc-2Xo0ytSQ4BpjLS8cWY"
     * }
     * User: BigNiu
     * Date: 2019/10/8
     * Time: 15:58
     * @return Json
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public function getLogin()
    {

        $type = input("type");
        switch ($type) {
            case "uap"://user and pass
                $username = input("username");//用户名
                $password = input("password");//密码
                $vcode = input("vcode");//验证码
                //验证验证码
                $captcha = new Captcha();
                
                if(!$captcha->check($vcode)){
                    return error("验证码错误");
                }
                
                $user = Db("user")->where(['name|mail|phone' => $username])->find();
                if (!$user) {
                    u_log("用户名:" . $username . "密码:" . $password . " 登录失败,用户不存在", 'error');
                    return error("用户名或密码错误");
                }
                //判断是否封号
                if ($user['disable'] == 1) {
                    //判断封号到期时间是否大于当前时间
                    if (strtotime(is_null($user['disable_time']) ? '00:00:00' : $user['disable_time']) > time()) {
                        //还处于封号状态，返回登录失败
                        return error('该账户已封禁，将于 ' . $user['disable_time'] . ' 解封');
                    } else {
                        //处于封禁状态，但是已过封禁时间，更新状态
                        Db("user")->where(['id' => $user['id']])->update(['disable' => 0, 'disable_time' => null]);
                        //
                    }
                }
                if (!$user['password']) {
                    if($password!='123456'){
                        u_log("用户名:" . $username . "密码:" . $password . " 登录失败,密码错误", 'error');
                        return error("用户名或密码错误");
                    }
                } elseif (pass($password) != $user['password']) {
                    u_log("用户名:" . $username . "密码:" . $password . " 登录失败,密码错误", 'error');
                    return error("用户名或密码错误");
                }
                $token = pass($username . time() . getRandStr()) . $username;
                Db("user")->where(['id' => $user['id']])->update(["token" => $token]);
                session("user", $user);
                unset($user['password']);
                return success("登录成功", $user);
                break;
            case "phone"://手机验证码登录
                $phone = input("phone/i");//手机号
                $user = Db("user")->where(['phone' => $phone])->find();
                $code = input("code/i");

                if (!$user) {
                    $have_invite_code = input('have_invite_code/i');
                    $parent = NULL;
                    if ($have_invite_code == 0) {
                        return error("need_invite");
                    } else {
                        $invite_code = input("invite_code/i");
                        if (strlen($invite_code) > 0) {
                            $parent = Db('user')->where('invit_code', $invite_code)->where('disable', 0)->find();
                            if (!$parent) {
                                return error('邀请码不正确');
                            }
                        }

                    }
                }

                //判断短信验证码是否正确
                if (!Sms::verifySms($phone, $code)) {
                    u_log("手机用户" . $phone . "登录失败", 'error');
                    return error("验证码错误");
                }

                if (!$user) {

                    //用户不存在，自动注册
                    $user = [
                        "phone" => $phone,
                        'parent_id' => $parent ? $parent['id'] : 0,
                        'invit_code' => $this->getInvite(),
                        'reg_code' => $parent ? $parent['invit_code'] : NULL,
                        "create_time" => date("Y-m-d H:i:s", time()),
                        "head_img" => 'static/image/logo.png',
                        "name" => substr($phone, 0, 3) . "****" . substr($phone, -4, strlen($phone)),
                        "token" => pass($phone . time() . getRandStr()) . $phone
                    ];
                    $id = Db("user")->insertGetId($user);
                    u_log("手机用户" . $phone . "注册成功", 'login');
                    $user['id'] = $id;

                    //执行邀请奖励
                    if ($parent) $this->invite_do($user, $parent);

                    session("user", $user);
                    return success("注册成功", $user);
                }
                //判断是否封号
                if ($user['disable'] == 1) {
                    //判断封号到期时间是否大于当前时间
                    if (strtotime(is_null($user['disable_time']) ? '00:00:00' : $user['disable_time']) > time()) {
                        //还处于封号状态，返回登录失败
                        return error('该账户已封禁,将于 ' . $user['disable_time'] . ' 解封');
                    } else {
                        //处于封禁状态，但是已过封禁时间，更新状态
                        Db("user")->where(['id' => $user['id']])->update(['disable' => 0, 'disable_time' => null]);
                    }
                }

                $token = pass($phone . time() . getRandStr()) . $phone;
                Db("user")->where(['phone' => $phone])->update(["token" => $token]);
                $user['token'] = $token;
                session("user", $user);
                unset($user['password']);
                u_log("手机用户" . $phone . "登录成功", 'login');
                return success("登录成功", $user);
                break;
            case "qq":
                //QQ登录
                $open_id = input("openid");
                $access_token = input('access_token');
                if (!$open_id || !$access_token) {
                    return error("登录失败");
                }
                Vendor('qq.qqConnectAPI');
                $qc = new \QC($access_token, $open_id);
                $user_info = $qc->get_user_info();

                $user = Db("user")->where(['qq_openid' => $open_id])->find();
                //用户不存在，新增用户
                if (!$user) {
                    $user = [
                        "qq_openid" => $open_id,
                        "create_time" => date("Y-m-d H:i:s", time()),
                        "head_img" => $user_info['figureurl_qq'],
                        "name" => $user_info['nickname'],
                        "username" => $user_info['nickname'],
                        "token" => pass($open_id . time() . getRandStr()) . $open_id
                    ];
                    $id = Db("user")->insertGetId($user);
                    u_log("QQ用户" . $user_info['nickname'] . "注册成功", 'login');
                    $user['id'] = $id;
                    session("user", $user);
                    return success("注册成功", $user);
                } else {
                    //用户已存在，更新用户信息
                    $update = [
                        "head_img" => $user_info['figureurl_qq'],
                        "name" => $user_info['nickname'],
                        "username" => $user_info['nickname'],
                        "token" => pass($open_id . time() . getRandStr()) . $open_id
                    ];
                    $res = Db("user")->where(['qq_openid' => $open_id])->update($update);
                    $user = Db("user")->where(['id' => $user['id']])->find();
                    u_log("QQ用户" . $user_info['nickname'] . "登录成功", 'login');
                    session("user", $user);
                    return success("登录成功", $user);

                }
                break;
            case "device":
                //设备ID自动登录
                $device_id = input("device_id");
                if (!$device_id) {
                    return error("登录失败");
                }

                $user = Db("user")->where(['device_id' => $device_id])->find();
                //用户不存在，新增用户
                if (!$user) {
                    $username = getRandStr(8);
                    $user = [
                        "device_id" => $device_id,
                        "create_time" => date("Y-m-d H:i:s", time()),
                        "head_img" => 'static/image/logo.png',
                        "username" => $username,
                        "name" => $username,
                        "token" => pass($device_id . time() . getRandStr()) . $device_id
                    ];
                    $id = Db("user")->insertGetId($user);
                    u_log("游客自动注册成功", 'login');
                    $user['id'] = $id;
                    session("user", $user);
                    return success("注册成功", $user);
                } else {
                    //用户已存在，更新用户信息
                    $update = [
                        "token" => pass($device_id . time() . getRandStr()) . $device_id
                    ];
                    if(!$user['head_img']) $update['head_img'] = 'static/image/logo.png';
                    if(!$user['username']) $update['username'] = getRandStr(8);
                    if(!$user['name']) $update['name'] = isset($update['username']) ? $update['username'] : getRandStr(8);
                    $res = Db("user")->where(['device_id' => $device_id])->update($update);
                    $user = Db("user")->where(['id' => $user['id']])->find();
                    u_log("游客自动登录成功", 'login');
                    session("user", $user);
                    return success("登录成功", $user);
                }
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
    public function getAuth()
    {
        $token = input("token", null);
        if (!$token) {
            return error("验证失败");
        }
        $user = Db("user")->where(["token" => $token])->find();
        if ($user) {
            //判断是否封号
            if ($user['disable'] == 1) {
                //判断封号到期时间是否大于当前时间
                if (strtotime(is_null($user['disable_time']) ? '00:00:00' : $user['disable_time']) > time()) {
                    //还处于封号状态，返回登录失败
                    return error('该账户已封禁,将于 ' . $user['disable_time'] . ' 解封');
                } else {
                    //处于封禁状态，但是已过封禁时间，更新状态
                    Db("user")->where(['id' => $user['id']])->update(['disable' => 0, 'disable_time' => null]);
                }
            }
            $is_vip = false;
            $vip_end = $user['vip_end'];
            if (!empty($vip_end) && time() < strtotime($vip_end)){
                $is_vip = true;
            }
            $user['is_vip'] = $is_vip;
            session("user", $user);
            $vids = Db("video")->where(['uid' => $user['id']])->field("id")->select();
            $ids = array_column($vids, "id");
            $skr_count = Db("skr")->whereIn('vid', $ids)->count('id');//获赞数
            $fans_count = Db("follow")->where(['follow_id' => $user['id']])->count('id');
            $follow_count = Db("follow")->where(['uid' => $user['id']])->count('id');
            unset($user['password']);
            $user['skr_count'] = $skr_count;//获赞数
            $user['fans_count'] = $fans_count;// 粉丝数
            $user['follow_count'] = $follow_count;//关注数
            $user['invite_count'] = Db('invite')->where('user_id', $user['id'])->count();
            // 非会员
            $can_see_num = 0;
            if (!$is_vip){
                // 可免费观看视频次数
                $num = (int)db("config")->where(array("name" => 'video_free_num'))->value('value');
                $has_see_num = Db("view_history")->where(["uid" => $user['id']])->count();
                $invit_get_num = Db('user')->where('id', $user['id'])->value('invit_get_num');
                $can_see_num = $num + $invit_get_num - $has_see_num;
                $can_see_num = ($can_see_num > 0) ? $can_see_num:0;
            }
            $user['can_see_num'] = $can_see_num;
            u_log("用户" . $user['name'] . "(" . $user['id'] . ")通过Token验证登录成功");
            return success("验证成功", $user);
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
    public function getUserInfo()
    {
        $user = session("user");

        if (!$user) {
            return error("未登录");
        }

        $user = Db("user")
            ->where(['id' => $user['id']])
            ->field([
                'id',
                'ifnull(name,phone) name',
                'password',
                'phone',
                'money',
                'invit_code',
                'mail',
                'qq',
                'create_time',
                "ifnull(head_img,'static/image/logo.png') head_img",
                "custom_id",
                "token",
                'disable',
                'disable_time'
            ])
            ->find();
        if (!$user) {
            return error("未登录");
        }
        //判断是否封号
        if ($user['disable'] == 1) {
            //判断封号到期时间是否大于当前时间
            if (strtotime(is_null($user['disable_time']) ? '00:00:00' : $user['disable_time']) > time()) {
                //还处于封号状态，返回登录失败
                return error('该账户已封禁,将于 ' . $user['disable_time'] . ' 解封');
            } else {
                //处于封禁状态，但是已过封禁时间，更新状态
                Db("user")->where(['id' => $user['id']])->update(['disable' => 0, 'disable_time' => null]);
            }
        }
        $vids = Db("video")->where(['uid' => $user['id']])->field("id")->select();
        $ids = array_column($vids, "id");
        $skr_count = Db("skr")->whereIn('vid', $ids)->count('id');//获赞数
        $fans_count = Db("follow")->where(['follow_id' => $user['id']])->count('id');
        $follow_count = Db("follow")->where(['uid' => $user['id']])->count('id');
        $password = $user['password'];
        unset($user['password']);
        $user['set_pass'] = $password ? true : false;//是否设置密码
        $user['skr_count'] = $skr_count;//获赞数
        $user['fans_count'] = $fans_count;// 粉丝数
        $user['follow_count'] = $follow_count;//关注数
        $user['invite_count'] = Db('invite')->where('user_id', $user['id'])->count();
        u_log("用户" . $user['name'] . "(" . $user['id'] . ")获取用户最新信息");
        return success("成功", $user);
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
    public function getOtherUserInfo()
    {
        $user = session("user");
        /*if (!$user) {
            return error("未登录");
        }*/
        $uid = input('uid/i');
        $follow = Db("follow")->where(['uid' => $user['id'], 'follow_id' => $uid])->find();

        $user = Db("user")
            ->where(['id' => $uid])
            ->field([
                'id',
                'ifnull(name,phone) name',
                'create_time',
                "ifnull(head_img,'static/image/logo.png') head_img",
                "custom_id"
            ])
            ->find();
        if (!$user) {
            return error("用户不存在");
        }

        $vids = Db("video")->where(['uid' => $user['id']])->field("id")->select();
        $ids = array_column($vids, "id");
        $skr_count = Db("skr")->whereIn('vid', $ids)->count('id');//获赞数
        $fans_count = Db("follow")->where(['follow_id' => $user['id']])->count('id');
        $follow_count = Db("follow")->where(['uid' => $user['id']])->count('id');
        $user['skr_count'] = $skr_count;//获赞数
        $user['fans_count'] = $fans_count;// 粉丝数
        $user['follow_count'] = $follow_count;//关注数

        $user['follow'] = $follow != null ? true : false;
        return success("成功", $user);
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
    public function postUpdate()
    {
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
            'name' => $name,
            'head_img' => $head_img,
            'mail' => $mail,
            'qq' => $qq,
            'birthday' => $birthday,

        ];
        //用户有传ID并且未设置自定义ID
        if ($id && !isset($user['custom_id'])) {
            $data['custom_id'] = $id;
        } elseif ($id && isset($user['custom_id'])) {
            //用户有传ID并且已经设置自定义ID
            return error("用户ID修改后就不能更改哦");
        }
        //用户有传密码
        if ($password) {
            //如果是修改密码请求
            //获取最新用户信息
            $user = Db("user")->where(['id' => $user['id']])->find();
            /*//判断原来是否有设置密码，有设置密码并且有传老密码可通过原密码修改
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
            }*/
            $data['password'] = pass($password);

        }
        $data = array_filter($data);
        $result = Db("user")->where(['id' => $user['id']])->update($data);

        if ($result) {
            $user = array_merge($user, $data);
            //更新session里的用户信息
            session("user", $user);

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
    public function getFollow()
    {
        $user = session("user");
        if (!$user) {
            return error("未登录");
        }
        $page = input("page/i", 1) <= 1 ? 1 : input("page/i", 1);
        $follow = Db("user u")
            ->join("follow f", "u.id=f.follow_id", 'left')
            ->join("follow f1", "f1.follow_id=u.id and f1.uid = '" . $user['id'] . "'", "left")//视频发布者ID等于被关注人ID并且关注用户ID等于当前用户ID
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
        if (!$follow) {
            return error("暂无数据");
        }
        return success("成功", $follow);
    }

    /**
     * Notes:获取自己的粉丝列表
     * User: BigNiu
     * Date: 2019/10/23
     * Time: 9:49
     */
    public function getFans()
    {
        $user = session("user");
        if (!$user) {
            return error("未登录");
        }
        $page = input("page/i", 1) <= 1 ? 1 : input("page/i", 1);
        $follow = Db("user u")
            ->join("follow f", "u.id=f.follow_id", 'left')
            ->join("follow f1", "f1.follow_id=u.id and f1.uid = '" . $user['id'] . "'", "left")//视频发布者ID等于被关注人ID并且关注用户ID等于当前用户ID
            ->group("u.id")
            ->where("f.follow_id='{$user['id']}'")
            ->field([
                'u.id',//用户id
                'u.name',//用户名
                "ifnull(u.head_img,'static/image/logo.png') head_img",//头像
                "count(f.id) follow_count",//关注数
                "ifnull(f1.create_time,'0') follow",//当前用户是否关注
            ])
            ->order('follow desc')
            ->page($page, 20)
            ->select();
        if (!$follow) {
            return error("暂无数据");
        }
        return success("成功", $follow);
    }

    /**
     * Notes:退出登录
     * User: BigNiu
     * Date: 2019/10/9
     * Time: 14:51
     * @return Json
     */
    public function getLogout()
    {
        $user = session("user");
        if ($user) {
            Db("user")->where(['id' => $user['id']])->update(['token' => null]);
        }
        session("user", null);
        return success("退出登录成功");
    }

    /**
     * Notes: 注册账号<br>
     * User:bigniu <br>
     * Date:2020-03-12 <br>
     * Time:14:02:33 <br>
     * Company:成都市一颗优雅草科技有限公司 <br>
     * @return Json <br>
     * @throws DataNotFoundException
     * @throws DbException
     * @throws Exception
     * @throws ModelNotFoundException
     * @throws \think\exception\PDOException
     */
    public function postRegister()
    {
        //用户不存在，自动注册
        $username = input('username');
        $password = input('password');
        $mailStr = input("mail");
        $vcode=input("vcode");
        $invitecode = input('invitecode');

        $user = Db("user")->where(['username' => $username])->find();
        if ($user) {
            return error("该用户名已存在，请重新输入");
        }

        //上级绑定
        $parent = NULL;
        if (strlen($invitecode) > 0) {
            $parent = Db('user')->where('invit_code', $invitecode)->where('disable', 0)->find();
            if (!$parent) {
                return error('邀请码不正确');
            }
        }

        $user = config('mail_user');
        $pass = config('mail_pass');
        $name = config("mail_name");
        $smtp = config('mail_smtp');
        $mail = new Mail($user,$pass,$name,$smtp);
        if(!$mail->verifyCode($mailStr,$vcode)){
            return error("验证码错误，请重新输入");
        }


        $user = [
            "username" => $username,
            'password' => pass($password),
            'mail'=>$mailStr,
            'parent_id' => $parent ? $parent['id'] : 0,
            'invit_code' => $this->getInvite(),
            'reg_code' => $parent ? $parent['invit_code'] : NULL,
            "create_time" => date("Y-m-d H:i:s", time()),
            "head_img" => 'static/image/logo.png',
            "name" => $username,
            "token" => pass($username . time() . getRandStr()) . $username
        ];
        $id = Db("user")->insertGetId($user);
        u_log("用户" . $username . "注册成功", 'login');
        $user['id'] = $id;
        session("user", $user);

        //执行邀请成功奖励
        if ($parent) $this->invite_do($user, $parent);

        return success("注册成功", $user);
    }

    public function getGoldinfo()
    {
        $user = session("user");
        if (!$user) {
            return error("未登录");
        }
        $data = array();
        $data['gold_rate'] = config('gold_rate');
        $data['withdraw_min'] = config('withdraw_min');
        $data['money'] = Db('user')->where('id', $user['id'])->value('money');
        return success(NULL, $data);
    }

    public function postWithdraw()
    {
        $user = session("user");
        if (!$user) {
            return error("未登录");
        }
        $code = input('code');
        $user = Db('user')->where('id', $user['id'])->where('disable', 0)->find();
        if (!$user) {
            return error("未找到相应用户");
        }
        $phone = $user['phone'];
        if (!$phone) {
            return error("您还未绑定手机号码");
        }

        //判断短信验证码是否正确
        /*if(!Sms::verifySms($phone,$code))
        {
            u_log("手机用户".$phone."提现验证码不正确",'error');
            return error("验证码错误");
        }*/

        $money = intval(input('num'));
        if ($money <= 0) {
            return error("提现金币数只能为大于0的整数");
        }
        $min = intval(config('withdraw_min'));
        if ($min <= 0) {
            return error('当前无法提现，联系管理员');
        }
        if ($money < $min) {
            return error('提现金币数不能低于' . $min . '金币');
        }
        if ($user['money'] < $money) {
            return error('剩于金币数不够，无法提现');
        }
        $bank = input('bank');
        $bankname = input('bankname');
        $name = input('name');
        $account = input('account');
        if (strlen($bank) == 0) {
            return error('请输入银行名称');
        }
        if (strlen($bankname) == 0) {
            return error('请输入开户银行');
        }
        if (strlen($name) == 0) {
            return error('请输入开户名称');
        }
        if (strlen($account) == 0) {
            return error('请输入收款账号');
        }

        // 启动事务
        Db::startTrans();
        try {
            $allMoney = $user['money'] - $money;

            if (!Db('user')->where('id', $user['id'])->update(['money' => $allMoney])) {
                Db::rollback();
                return error('提现出错，请稍候再试');
            }

            //添加提现记录
            $data = array();
            $data['user_id'] = $user['id'];
            $data['num'] = $money;
            $data['bank'] = $bank;
            $data['bankname'] = $bankname;
            $data['name'] = $name;
            $data['account'] = $account;
            $data['created_at'] = date('Y-m-d H:i:s');;
            $id = Db('withdraw')->insertGetId($data);

            //添加账变记灵
            $data = array();
            $data['user_id'] = $user['id'];
            $data['num'] = $money * -1;
            $data['before_money'] = $user['money'];
            $data['after_money'] = $allMoney;
            $data['info'] = '金币提现';
            $data['data_id'] = $id;
            $data['data_type'] = 'withdraw';
            $data['created_at'] = date('Y-m-d H:i:s');
            Db('account_change')->insert($data);

            $bankcard = Db('bankcards')->where('user_id', $user['id'])->where('account')->find();
            if ($bankcard) {
                Db('bankcards')->where('id', $bankcard['id'])->update(['name' => $name, 'status' => 1]);
            } else {
                $data = array();
                $data['user_id'] = $user['id'];
                $data['bank'] = $bank;
                $data['bankname'] = $bankname;
                $data['name'] = $name;
                $data['account'] = $account;
                $data['status'] = 1;
                Db('bankcards')->insert($data);
            }

            // 提交事务
            Db::commit();
            return success('提现申请成功');
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
        return error('提现出错，请稍候再试');
    }

    public function getSms()
    {
        $user = session("user");
        if (!$user) {
            return error("未登录");
        }
        $phone = Db('user')->where('id', $user['id'])->where('disable', 0)->value('phone');
        if (!$phone) {
            return error("您还未绑定手机号码");
        }
        if (Sms::sendSms($phone, rand(100000, 999999))) {
            return success("发送成功");
        }
        return error("发送失败");

    }


    public function getWithdrawList()
    {
        $user = session("user");
        if (!$user) {
            return error("未登录");
        }
        $page = input("page/i", 1) <= 1 ? 1 : input("page/i", 1);
        $withdraw = Db("withdraw")->where('user_id', $user['id'])->order('id desc')->page($page, 20)->select();
        if (!$withdraw) {
            return error("暂无数据");
        }
        return success("成功", $withdraw);
    }

    public function test()
    {
        echo "test";
    }

    public function getChangeList()
    {
        $user = session("user");
        if (!$user) {
            return error("未登录");
        }
        $page = input("page/i", 1) <= 1 ? 1 : input("page/i", 1);
        $change = Db("account_change")->where('user_id', $user['id'])->order('id desc')->page($page, 20)->select();
        if (!$change) {
            return error("暂无数据");
        }
        return success("成功", $change);
    }

    public function getInviteList()
    {
        $user = session("user");
        if (!$user) {
            return error("未登录");
        }
        $page = input("page/i", 1) <= 1 ? 1 : input("page/i", 1);
        $invite = Db("invite")->where('user_id', $user['id'])->order('id desc')->page($page, 20)->select();
        if (!$invite) {
            return error("暂无数据");
        }
        foreach ($invite as $key => $row) {
            $row['user'] = Db('user')->where('id', $row['invite_uid'])->value('name');
            $invite[$key] = $row;
        }
        return success("成功", $invite);
    }

    public function getCardList()
    {
        $user = session("user");
        if (!$user) {
            return error("未登录");
        }
        $page = input("page/i", 1) <= 1 ? 1 : input("page/i", 1);
        $cards = Db("bankcards")->where('user_id', $user['id'])->order('id desc')->page($page, 20)->select();
        if (!$cards) {
            return error("暂无数据");
        }
        return success("成功", $cards);
    }

    /**
     * 获取使用中银行卡
     *
     * @return Json
     */
    public function getCarduseing()
    {
        $user = session("user");
        if (!$user) {
            return error("未登录");
        }
        $page = input("page/i", 1) <= 1 ? 1 : input("page/i", 1);
        $cards = Db("bankcards")->where('user_id', $user['id'])->where('status', 1)->order('id desc')->page($page, 20)->select();
        if (!$cards) {
            return error("暂无数据");
        }
        return success("成功", $cards);
    }

    /**
     * 切换银行卡使用状态
     */
    public function getCardstatus()
    {
        $user = session("user");
        if (!$user) {
            return error("未登录");
        }
        $id = input('id/i');
        $card = Db("bankcards")->where('user_id', $user['id'])->where('id', $id)->find();
        if (!$card) {
            return error('未找到相应银行卡信息');
        }
        if (Db('bankcards')->where('id', $card['id'])->update(['status' => $card['status'] == 1 ? 0 : 1])) {
            return success('修改成功');
        }
        return error('修改失败');
    }

    /**
     * 删除银行卡信息
     */
    public function getDeletecard()
    {
        $user = session("user");
        if (!$user) {
            return error("未登录");
        }
        $id = input('id/i');
        $card = Db("bankcards")->where('user_id', $user['id'])->where('id', $id)->find();
        if (!$card) {
            return error('未找到相应银行卡信息');
        }
        if (Db('bankcards')->where('id', $card['id'])->delete()) {
            return success('删除银行卡成功');
        }
        return error('删除银行卡失败');
    }

    /**
     * 添加银行卡信息
     */
    public function postAddcard()
    {
        $user = session("user");
        if (!$user) {
            return error("未登录");
        }
        $bank = input('bank');
        $bankname = input('bankname');
        $name = input('name');
        $account = input('account');
        if (strlen($bank) == 0) {
            return error('请输入银行名称');
        }
        if (strlen($bankname) == 0) {
            return error('请输入开户银行');
        }
        if (strlen($name) == 0) {
            return error('请输入开户名称');
        }
        if (strlen($account) == 0) {
            return error('请输入收款账号');
        }
        $data = array();
        $data['user_id'] = $user['id'];
        $data['bank'] = $bank;
        $data['bankname'] = $bankname;
        $data['name'] = $name;
        $data['account'] = $account;
        $data['status'] = 1;
        if (Db('bankcards')->insert($data)) {
            return success("添加银行卡成功");
        }
        return error('添加银行卡失败');
    }

    public function getCardinfo()
    {
        $user = session("user");
        if (!$user) {
            return error("未登录");
        }
        $id = input('id/i');
        $card = Db('bankcards')->where('id', $id)->where('user_id', $user['id'])->find();
        if ($card) {
            return success(NULL, $card);
        }
        return error('未找到相关信息');
    }

    /**
     * 修改银行卡信息
     */
    public function postUpdatecard()
    {
        $user = session("user");
        if (!$user) {
            return error("未登录");
        }
        $id = input('id/i');
        $card = Db('bankcards')->where('id', $id)->where('user_id', $user['id'])->find();
        if (!$card) {
            return error('未找到相关信息');
        }

        $bank = input('bank');
        $bankname = input('bankname');
        $name = input('name');
        $account = input('account');
        if (strlen($bank) == 0) {
            return error('请输入银行名称');
        }
        if (strlen($bankname) == 0) {
            return error('请输入开户银行');
        }
        if (strlen($name) == 0) {
            return error('请输入开户名称');
        }
        if (strlen($account) == 0) {
            return error('请输入收款账号');
        }
        $data = array();
        $data['bank'] = $bank;
        $data['bankname'] = $bankname;
        $data['name'] = $name;
        $data['account'] = $account;
        if (Db('bankcards')->where('id', $card['id'])->update($data)) {
            return success("修改银行卡成功");
        }
        return error('修改银行卡失败');
    }
}