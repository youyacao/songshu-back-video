<?php


namespace app\api\controller;


use app\api\common\AsyncCommand;
use app\api\common\Mail;
use app\api\common\Sms;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
use think\Config;
use think\Controller;
use think\exception\ErrorException;
use think\File;
use think\Image;
use OSS\OssClient;
use OSS\Core\OssException;

class Api extends Controller
{
    /**
     * Notes:上传公共接口
     * @param type 上传类型  可选（video，img），可通过api/config.php文件进行配置上传类型以及其后缀
     * User: BigNiu
     * Date: 2019/10/8
     * Time: 16:01
     * @return \think\response\Json
     * @throws \Exception
     */
    public function upload()
    {
        //dump(Config::has('use_qiniu'));exit;
        //如果开启使用七牛云上传
        if (config("use_qiniu") == '1') {
            return $this->upload_qiniu();
        }
        if (config("use_aliyun") == '1') {
            return $this->upload_aliyun();
        }
        if (config("use_ftp") == '1') {
            return $this->upload_ftp();
        }

        $type = input("type");
        $config = Config::get($type);
        if (!$config) {
            return error("上传类型错误");
        }
        // 获取表单上传视频 例如上传了001.mp4
        $file = request()->file('file');
        if (!$file) {
            return error("请选择上传文件");
        }
        // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $file->validate(['ext' => $config['ext']])->rule('uniqid')->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . $type);


        if ($info) {
            $url = 'uploads/' . $type . "/" . str_replace(DS, "/", $info->getSaveName());
            if ($type == 'video') {
                $data = [
                    'url' => $url,
                    'img' => getImg($url)
                ];
                return success("上传成功", $data);
            } else {
                $data = [
                    'url' => $url
                ];
                return success("上传成功", $data);
            }
            //上传成功返回路径

        } else {
            // 上传失败获取错误信息
            return error($file->getError());
        }
    }

    /**
     * Notes:七牛云上传
     * User: BigNiu
     * Date: 2019/10/8
     * Time: 16:00
     * @return \think\response\Json 图片完整URL
     * @throws \Exception
     */
    public function upload_qiniu()
    {
        if (request()->isPost()) {
            $type = input("type");
            $config = Config::get($type);
            if (!$config) {
                return error("上传类型错误");
            }
            $file = request()->file('file');
            // 要上传图片的本地路径
            $filePath = $file->getRealPath();
            $ext = pathinfo($file->getInfo('name'), PATHINFO_EXTENSION);  //后缀
            //获取当前控制器名称
            // 上传到七牛后保存的文件名
            $key = substr(md5($file->getRealPath()), 0, 5) . date('YmdHis') . rand(0, 9999) . '.' . $ext;
            require_once APP_PATH . '/../vendor/qiniu/autoload.php';
            // 需要填写你的 Access Key 和 Secret Key
            $accessKey = config('accesskey');
            $secretKey = config('secretkey');
            // 构建鉴权对象
            $auth = new Auth($accessKey, $secretKey);
            // 要上传的空间
            $bucket = config('bucket');
            $domain = config('domain');
            $token = $auth->uploadToken($bucket);
            // 初始化 UploadManager 对象并进行文件的上传
            $uploadMgr = new UploadManager();
            // 调用 UploadManager 的 putFile 方法进行文件的上传
            list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
            if ($err !== null) {
                return error($err);
            } else {
                $url = $domain . $ret['key'];
                if ($type == 'video') {
                    $data = [
                        'url' => $url,
                        //'img' => getImg($url)
                        'img' => $url.'?vframe/jpg/offset/1'
                    ];
                    return success("上传成功", $data);
                } else {
                    $data = [
                        'url' => $url
                    ];
                    return success("上传成功", $data);
                }
                //返回图片的完整URL
                return success("上传成功", $data);
            }
        }
    }

    /**
     * 阿里云上传
     */
    public function upload_aliyun()
    {
        if (request()->isPost()) {
            $type = input("type");
            $file = request()->file('file');
            // 要上传图片的本地路径
            $filePath = $file->getRealPath();
            $ext = pathinfo($file->getInfo('name'), PATHINFO_EXTENSION);  //后缀
            //获取当前控制器名称
            // 上传到七牛后保存的文件名
            $key = substr(md5($file->getRealPath()), 0, 5) . date('YmdHis') . rand(0, 9999) . '.' . $ext;

            $accessKey = config('aliyun_accesskey');
            $secretKey = config('aliyun_secretkey');
            $endpoint = "http://oss-accelerate.aliyuncs.com";
            $bucket = config('aliyun_bucket');
            $domain = config('aliyun_domain');

            try{
                $ossClient = new OssClient($accessKey, $secretKey, $endpoint);
                $ossClient->uploadFile($bucket, $key, $filePath);
            } catch(OssException $e) {
                return error($e->getMessage());
            }
            $url = $domain . $key;
            if ($type == 'video') {
                $data = [
                    'url' => $url,
                    'img' => $url.'?x-oss-process=video/snapshot,t_1000,f_jpg,w_800,h_600,m_fast'
                ];
                return success("上传成功", $data);
            } else {
                $data = [
                    'url' => $url
                ];
                return success("上传成功", $data);
            }
        }
    }

    /**
     * Notes: FTP上传模式<br>
     * User:bigniu <br>
     * Date:2019-12-29 <br>
     * Time:23:00:17 <br>
     * @return \think\response\Json <br>
     */
    public function upload_ftp()
    {

        if (request()->isPost()) {
            $type = input("type");
            $config = Config::get($type);
            if (!$config) {
                return error("上传类型错误");
            }
            // 获取表单上传视频 例如上传了001.mp4
            $file = request()->file('file');
            if (!$file) {
                return error("请选择上传文件");
            }
            // 移动到框架应用根目录/public/uploads/ 目录下
            $info = $file->validate(['ext' => $config['ext']])->rule('uniqid')->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . $type);
            if ($info) {
                $config = [
                    'host' => config("ftp_host"),
                    'user' => config("ftp_user"),
                    'pass' => config("ftp_pass"),
                    'url' => config('ftp_url')
                ];
                $ftp = new Ftp($config);
                $result = $ftp->connect();

                if (!$result) {
                    echo $ftp->get_error_msg();
                    exit;
                }
                $file = request()->file('file');
                if (!$file) {
                    return error("请选择上传文件");
                }

                // 要上传图片的本地路径
                $filePath = ROOT_PATH . 'public' . DS . 'uploads/' . DS . $type . DS . $info->getSaveName();
                $local_file = $filePath;
                $remote_file = date('Y-m') . '/' . getRandStr() . "." . $info->getExtension();
                //上传文件
                if ($ftp->upload($local_file, $remote_file)) {
                    $remote_url = $config['url'] . $remote_file;

                    if ($type == 'video') {
                        $data = [
                            'url' => $remote_url,
                            'img' => getImg($remote_url)
                        ];
                        return success("上传成功", $data);
                    } else {
                        $data = [
                            'url' => $remote_url
                        ];
                        return success("上传成功", $data);
                    }
                } else {
                    return error("上传失败");
                }
            } else {
                // 上传失败获取错误信息
                return error($file->getError());
            }

        }
    }

    /**
     * Notes:检测更新
     * User: BigNiu
     * Date: 2019/10/30
     * Time: 10:48
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function update()
    {
        $appid = input('appid');
        $version = input('version');
        $os = input("os");
        $update = Db("update")->where(['appid' => $appid])->order('version desc')->find();
        $pattern = '/^\d+\.\d+.\d+$/';//需要转义/
        preg_match($pattern, $version, $match);
        if (!$match) {
            return error("您的版本号不符合规范，格式为: 1.1.1");
        }
        //未找到新版本信息，直接返回最新版
        if (!$update) {
            return error("暂无更新");
        }
        $newVersion = $update['version'];
        $newVersion = explode(".", $newVersion);

        $newVersion1 = $newVersion[0];
        $newVersion2 = $newVersion[1];
        $newVersion3 = $newVersion[2];

        $version = explode('.', $version);
        $version1 = $version[0];
        $version2 = $version[1];
        $version3 = $version[2];
        //主版本号大于当前版本
        if ($newVersion1 > $version1) {
            $data = [
                "status" => 1,//升级标志，1：需要升级；0：无需升级
                "note" => $update['content'],//release notes
                "url" => $os == 'Android' ? $update['android_download'] : $update['ios_download'], //更新包下载地址
                "open_type" => $os == 'Android' ? $update['open_type'] : 2//打开方式，安卓独有。IOS默认外部打开
            ];
            return success("成功", $data);
        }
        if ($newVersion1 == $version1 && $newVersion2 > $version2) {
            $data = [
                "status" => 1,//升级标志，1：需要升级；0：无需升级
                "note" => $update['content'],//release notes
                "url" => $os == 'Android' ? $update['android_download'] : $update['ios_download'], //更新包下载地址
                "open_type" => $os == 'Android' ? $update['open_type'] : 2//打开方式，安卓独有。IOS默认外部打开
            ];
            return success("成功", $data);
        }
        if ($newVersion1 == $version1 && $newVersion2 == $version2 && $newVersion3 > $version3) {
            $data = [
                "status" => 1,//升级标志，1：需要升级；0：无需升级
                "note" => $update['content'],//release notes
                "url" => $os == 'Android' ? $update['android_download'] : $update['ios_download'], //更新包下载地址
                "open_type" => $os == 'Android' ? $update['open_type'] : 2//打开方式，安卓独有。IOS默认外部打开
            ];
            return success("成功", $data);
        }
        if ($newVersion1 == $version1 && $newVersion2 == $version2 && $newVersion3 == $version3) {
            return error("暂无更新");
        }
        //都小于当前版本
        return error("暂无更新");
    }

    /**
     * Notes:获取系统配置
     * User: BigNiu
     * Date: 2019/11/19
     * Time: 10:55
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function config()
    {

        $publicConfig = [
            'use_qiniu',//是否开启七牛云
            'domain',//七牛云域名
            'video_free_time',//免费观看时间
            'advert_count',//广告刷到的频率
            'share_url',//分享地址
            'video_free_num', // 免费观看视频条数
        ];

        $config = Db("config")->whereIn("name", $publicConfig)->field("name,value")->select();
        $res = [];
        foreach ($config as $key => $value) {
            $res[$value['name']] = $value['value'];
        }
        return success("获取成功", $res);
    }

    /**
     * Notes:获取七牛云Token
     * User: BigNiu
     * Date: 2019/11/19
     * Time: 10:55
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function getQiNiuToken()
    {
        //判断是否开启七牛云
        if (config("use_qiniu") == 0) {
            return error("未开启七牛云存储");
        }
        /*//上传策略
        $policyFields =[
            'mimeLimit'=>'image/jpeg;image/png',
        ];
        $expires = 3600;*/
        $accessKey = config('accesskey');//获取七牛AK
        $secretKey = config('secretkey');//获取七牛SK
        require_once APP_PATH . '/../vendor/qiniu/autoload.php';
        $auth = new Auth($accessKey, $secretKey);
        $bucket = config('bucket');//设置存储空间
        $token = $auth->uploadToken($bucket);
//        $token = $auth->uploadToken($bucket,null,$expires,$policyFields);//若需要限制上传文件类型，则在生成token时添加上传策略即可
        return success("获取成功", $token);
    }

    /**
     * Notes:测试ffmpeg截图
     * User: BigNiu
     * Date: 2019/10/8
     * Time: 16:03
     */
    public function test()
    {
        /* $url = "uploads/video/5d9195f335cd7.mp4";
         $cmd = "ffmpeg -i ".str_replace("&","",$url)." -ss 00:00:00 -t 1 uploads/img/".md5($url).".png";
         $res = shell_exec($cmd);
         var_dump($res);*/
        $url = "D:/1.mp4";
        $start = time();
        $cmd = "attrib +R {$url}&&ffmpeg -i {$url} -b 600k D:/111.mp4&&attrib -R {$url}";
        $res = AsyncCommand::run($cmd);
//        shell_exec($cmd);
        $end = time();
        echo "执行时间:" . ($end - $start);
        echo "\n" . $res;
    }

    public function caiji()
    {
        set_time_limit(0);
        $id = 138400;

        for ($i = 0; $i < 100; $i++) {
            $id += 10;
            $insertData = [];
            //echo "=================https://api.apiopen.top/videoRecommend?id={$id}=============<br/>";
            $data = json_decode(file_get_contents("https://api.apiopen.top/videoRecommend?id={$id}"));
            if ($data->code == 400) {
                continue;
            }
            $result = $data->result;
            foreach ($result as $item) {
                $item_data = $item->data;
                if ($item->type == 'videoSmallCard') {
                    $url = $item_data->playUrl;
                    $title = $item_data->title;
                    // echo $title."<br/>";
                    $img = $item_data->cover->detail;
                    $insert = [
                        'url' => $url,
                        'img' => $img,
                        'title' => $title,
                        'type' => rand(127, 141),
                        'uid' => rand(10, 26),
                        'create_time' => TIME
                    ];
                    array_push($insertData, $insert);

                }
            }
            Db("video")->insertAll($insertData);
        }
    }
    public function mail()
    {
        $mailStr = input("mail");
        if(!$mailStr){
            return error("参数错误：请传mail参数");
        }
        $user = config('mail_user');
        $pass = config('mail_pass');
        $name = config("mail_name");
        $smtp = config('mail_smtp');
        $mail = new Mail($user,$pass,$name,$smtp);
        if ($mail->sendRegisterMail($mailStr, rand(100000, 999999))) {
            return success("发送成功");
        }
        return error("发送失败");
    }
    public function sms()
    {
        $phone = input('phone/i');
        if (Sms::sendSms($phone, rand(100000, 999999))) {
            return success("发送成功");
        }
        return error("发送失败");
    }

    /*    public function updateUserAvater()
        {
            $userList = Db("user")->select();
            $dir = scandir("static\avatar");
            foreach ($userList as $key => $value) {
                do {
                    $name = $dir[intval(rand(0, sizeof($dir)))];
                } while ($name == '.' || $name == "..");
                Db("user")->where(['id' => $value['id']])->update(['head_img' => "static/avatar/" . $name]);
                echo "更新用户" . $value['phone'] . "头像为" . "static/avatar/" . $name . "成功<br/>";
            }
        }*/
    public function img($scale)
    {
        ob_clean();
        $scale = floatval($scale);
        $url = input("url");
        //判断需要压缩的文件是否存在
        if (!file_exists($url)) {
            header("Content-type: image/png");
            exit;
        }
        //如果缩放比例大于10，使用10
        if ($scale > 10) {
            $scale = 10;
        }
        //如果缩放比例小于1，使用1
        if ($scale < 1) {
            $scale = 1;
        }
        $file = new File($url);
        $path = $file->getPath();
        $ext = $file->getExtension();
        $type_arr = ['png', 'jpg', 'gif'];
        //文件类型检测
        if (!in_array($ext, $type_arr)) {
            header("Content-type: image/png");
            exit;
        }
        $fileName = $file->getFilename();
        $dir = "uploads/thumb/" . $scale . "/" . $path . "/";
        //判断保存文件的路径是否存在，不存在就创建
        if (!is_dir($dir)) {
            mkdirs($dir);
        }
        $image = Image::open($url);
        $save_path = $dir . $fileName;
        //判断略缩图文件是否存在，存在直接返回
        if (is_file($save_path)) {
            header('Content-type:' . $image->mime());
            echo file_get_contents($save_path);
        }
        //获取缩放后的宽高
        $width = $image->width() / $scale;
        $height = $image->height() / $scale;
        //进行缩放并保存到指定文件
        $image->thumb($width, $height, Image::THUMB_CENTER)->save($save_path);
        header('Content-type:' . $image->mime());
        //输出缩放后的文件
        echo file_get_contents($save_path);
        exit;
    }

    public function test_qq()
    {
        Vendor('qq.qqConnectAPI');
        $qc = new \QC();
        /*        $access_token = $qc->qq_callback();

                $open_id = $qc->get_openid();*/
        $qc = new \QC('2A30CC0AD30B78A477DD0B1AE732006A', '9EB708E9B66F11DB749E3ACCB6EE93B1');
        $user_info = $qc->get_user_info();
        echo json_encode($user_info);
        exit;
        // $user = D('member')->where(array('openid'=>$open_id))->find();//查询用户openid


        //此处是当用户进行QQ登录授权之后还需要进行手机号的绑定！如果不需要可直接删除此段进行
        if (!$user) {
            session('openid', $open_id);
            $this->assign('user_info', $user_info);
            $this->display();
            exit;
        } else {
            //下方为已经登记过的openID的记录
            if ($user['is_lock']) {
                $this->error('用户被锁定！', '', array('input' => ''));
            }
            //更新数据库的参数
            $data = array('id' => $user['id'], //保存时会自动为此ID的更新
                'login_time' => date('Y-m-d H:i:s'),
                'login_ip' => get_client_ip(),
                'login_num' => $user['login_num'] + 1,
            );
            //更新数据库
            M('member')->save($data);
            $uf = $request . '.' . md5($data['nickname']) . '.' . get_random(6); //检测因子
            session('uid', $user['id']);
            set_cookie(array('name' => 'uid', 'value' => $user['id']));
            set_cookie(array('name' => 'nickname', 'value' => $user['nickname']));
            set_cookie(array('name' => 'group_id', 'value' => $user['group_id'])); //20140801
            set_cookie(array('name' => 'login_time', 'value' => $user['login_time']));
            set_cookie(array('name' => 'login_ip', 'value' => $user['login_ip']));
            set_cookie(array('name' => 'status', 'value' => $user['status'])); //激活状态
            set_cookie(array('name' => 'verifytime', 'value' => time())); //激活状态
            set_cookie(array('name' => 'uf', 'value' => $uf)); //登录标识
            //因为前段是弹出窗口进行授权的。所以这里要执行JS的代码进行关闭窗口并刷新父窗口
            echo '<script>opener.location.reload(); window.close();</script>';
            exit;
        }
    }


}