<?php

namespace app\index\controller;

use FFMpeg\Coordinate\Dimension;
use FFMpeg\Coordinate\FrameRate;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\Driver\FFMpegDriver;
use FFMpeg\Exception\RuntimeException;
use FFMpeg\Filters\Frame\CustomFrameFilter;
use FFMpeg\Filters\Gif\GifFilters;
use FFMpeg\Filters\Video\ResizeFilter;
use FFMpeg\Filters\Video\VideoFilters;
use FFMpeg\Format\Video\WMV;
use FFMpeg\Media\Gif;
use PHPMailer\PHPMailer\PHPMailer;
use think\captcha\Captcha;

require ROOT_PATH . 'vendor/autoload.php';

use FFMpeg\FFMpeg;

use FFMpeg\Format\Video\X264;
use think\Exception;
use think\Log;

class Index
{
    public function index()
    {
        return '<style type="text/css">*{ padding: 0; margin: 0; } .think_default_text{ padding: 4px 48px;} a{color:#2E5CD5;cursor: pointer;text-decoration: none} a:hover{text-decoration:underline; } body{ background: #fff; font-family: "Century Gothic","Microsoft yahei"; color: #333;font-size:18px} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.6em; font-size: 42px }</style><div style="padding: 24px 48px;"> <h1>:)</h1><p> ThinkPHP V5<br/><span style="font-size:30px">十年磨一剑 - 为API开发设计的高性能框架</span></p><span style="font-size:22px;">[ V5.0 版本由 <a href="http://www.qiniu.com" target="qiniu">七牛云</a> 独家赞助发布 ]</span></div><script type="text/javascript" src="https://tajs.qq.com/stats?sId=9347272" charset="UTF-8"></script><script type="text/javascript" src="https://e.topthink.com/Public/static/client.js"></script><think id="ad_bd568ce7058a1091"></think>';
    }

    public function captcha()
    {
        $captcha = new Captcha();
        return $captcha->entry();
    }

    public function test()
    {
        $code = rand(100000, 999999);
        $mail = '201309512@qq.com';
        if(sendRegisterMain($mail,$code)){
            return success("发送成功");
        }else{
            return error("发送失败");
        }
    }

    /**
     * Notes: 视频转码工具类<br>
     * User:bigniu <br>
     * Date:2020-03-11 <br>
     * Time:1:09:08 <br>
     * Company:成都市一颗优雅草科技有限公司 <br>
     */
    public function video()
    {
        ignore_user_abort(true); // 忽略客户端断开
        set_time_limit(0);    // 设置执行不超时
        //日志存储路径初始化
        Log::init([
            'type' => 'File',
            'path' => LOG_PATH . 'video/'
        ]);
        $path = input("path");

        if (!is_file($path)) {
            Log::record('========路径不存在:' . $path, 'error');
            return;
        } else {
            Log::record('========开始转码:' . $path, 'info');
        }
        $new_path = 'uploads' . DS . 'video' . DS . 'watermark' . DS . md5($path) . '.mp4';
        //如果文件存在
        if (is_file($new_path)) {
            //直接更新数据库信息
            Db("video")->where(['url'=>str_replace(DS, "/",$path)])->update(['watermark_status'=>2,'watermark_progress'=>100,'url'=>str_replace(DS, "/",$new_path)]);
            //删除源文件释放空间
            unlink($path);
            return;
        }
        Log::record('========开始转码:' . $path, 'info');
        $video = Db("video")->where(['url'=>str_replace(DS, "/",$path)])->find();
        //判断视频是否存在数据库
        if (!$video) {
            Log::record('========该视频已转码或不存在:' . $path, 'error');
            return;
        }
        //更新所有视频链接为该地址的视频转码状态为转码中
        Db("video")->where(['url' => str_replace(DS, "/",$path)])->update(['watermark_status' => 1, 'watermark_progress' => 0]);
        Log::record("命令执行的路径：".$path);
        try {
            $ffmpeg = FFMpeg::create(['ffmpeg.threads' => 4]);
            $video = $ffmpeg->open($path);
            //启用添加水印
            if(config("watermark_status")=="1"){
                $watermarkPath = config("watermark_path") != "" ? config("watermark_path") : 'logo.png';
                $position = config("watermark_position");
                switch ($position){
                    case "left_top"://水印位置，左上
                        $waterMarkOption = [
                            'position' => 'relative',
                            'left' => 10,
                            'top' => 10,
                        ];
                        break;
                    case "right_top"://水印位置，右上
                        $waterMarkOption = [
                            'position' => 'relative',
                            'right' => 10,
                            'top' => 10,
                        ];
                        break;

                    case "right_bottom"://水印位置，左下
                        $waterMarkOption = [
                            'position' => 'relative',
                            'right' => 10,
                            'bottom' => 10,
                        ];
                        break;
                    case "left_bottom"://水印位置，右下
                        $waterMarkOption = [
                            'position' => 'relative',
                            'left' => 10,
                            'bottom' => 10,
                        ];
                        break;
                    case "center"://水印位置，居中
                        $waterMarkOption = [
                            'position' => 'relative',
                            'right' => 'main_w/2 - overlay_w/2 +overlay_w',
                            'bottom' => 'main_h/2 - overlay_h/2 + overlay_h',
                        ];
                        break;
                    default://水印位置，左上
                        $waterMarkOption = [
                            'position' => 'relative',
                            'left' => 10,
                            'top' => 10,
                        ];
                }
                //添加水印
                $video
                    ->filters()
                    ->watermark($watermarkPath,$waterMarkOption)
                    ->synchronize();
            }

            //通用转码操作
            $video->filters()->framerate(new FrameRate(60),20)
                ->resize(new Dimension(720,1280),ResizeFilter::RESIZEMODE_FIT,true)->synchronize();
            $format = new X264('libmp3lame', 'libx264');
            $format->on('progress', function ($video, $format, $percentage) use ($path) {
                //进度监听
                //更新状态为 转码中 进度信息
                Db("video")->where(['url' => str_replace(DS, "/",$path),'watermark_status'=>1])->update(['watermark_progress' => $percentage]);
            });
            //设置比特率
            $format->setKiloBitrate(2700);
            $video->save($format, $new_path);
            file_put_contents("1.txt", "100 % transcoded");
            Log::record("========转码成功：" . $path . "\t" . TIME, 'success');
            //更新转码状态中的为已完成
            Db("video")->where(['url' => str_replace(DS, "/",$path),'watermark_status'=>1])->update(['watermark_status' => 2, 'watermark_progress' => 100, 'url' => str_replace(DS, "/",$new_path)]);
            //删除源文件
            unlink($path);
        } catch (RuntimeException $e) {
            Log::record("========转码失败：" . $path.'\t'.$new_path . "\t" . $e->getMessage() . "\t" . TIME, 'error');
            //更新转码状态中的为转码失败
            Db("video")->where(['url' => str_replace(DS, "/",$path),'watermark_status' => 1])->update(['watermark_status' => 3]);
        }
    }
}
