<?php


namespace app\api\controller;


use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
use think\Config;
use think\Controller;

class Api extends Controller
{
    public function upload(){
        //dump(Config::has('use_qiniu'));exit;
        //如果开启使用七牛云上传
        if(Config::get("use_qiniu")){
            return $this->upload_qiniu();
        }
        $type = input("type");
        $config = Config::get($type);
        if(!$config)
        {
            return error("上传类型错误");
        }
        // 获取表单上传视频 例如上传了001.mp4
        $file = request()->file('file');
        if(!$file)
        {
            return error("请选择上传文件");
        }
        // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $file->validate(['ext'=>$config['ext']])->rule('uniqid')->move(ROOT_PATH . 'public' . DS . 'uploads'.DS.$type);
        if($info){
            //上传成功返回路径
            return success( "上传成功",'uploads/'.$type."/".str_replace(DS,"/",$info->getSaveName()));
        }else{
            // 上传失败获取错误信息
            return error($file->getError());
        }
    }
    /**
     * 七牛云上传
     * @return String 图片的完整URL
     */
    public function upload_qiniu()
    {
        if(request()->isPost()){
            $file = request()->file('file');
            // 要上传图片的本地路径
            $filePath = $file->getRealPath();
            $ext = pathinfo($file->getInfo('name'), PATHINFO_EXTENSION);  //后缀
            //获取当前控制器名称
            // 上传到七牛后保存的文件名
            $key =substr(md5($file->getRealPath()) , 0, 5). date('YmdHis') . rand(0, 9999) . '.' . $ext;
            require_once APP_PATH . '/../vendor/qiniu/autoload.php';
            // 需要填写你的 Access Key 和 Secret Key
            $accessKey = Config::get('ACCESSKEY');
            $secretKey = Config::get('SECRETKEY');
            // 构建鉴权对象
            $auth = new Auth($accessKey, $secretKey);
            // 要上传的空间
            $bucket = Config::get('BUCKET');
            $domain = Config::get('DOMAIN');
            $token = $auth->uploadToken($bucket);
            // 初始化 UploadManager 对象并进行文件的上传
            $uploadMgr = new UploadManager();
            // 调用 UploadManager 的 putFile 方法进行文件的上传
            list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
            if ($err !== null) {
                return error($err);
            } else {
                //返回图片的完整URL
                return success("上传成功","http://".$domain ."/". $ret['key']);
            }
        }
    }
    public function test(){
        $url = "uploads/video/5d9195f335cd7.mp4";

        $cmd = "ffmpeg -i ".str_replace("&","",$url)." -ss 00:00:00 -t 1 uploads/img/".md5($url).".png";
        $res = shell_exec($cmd);
        var_dump($res);
    }

}