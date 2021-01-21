
<!-- 
All rights Reserved, Designed By www.youyacao.com 
@Description:视频配置方法文件
@author:成都市一颗优雅草科技有限公司     
@version 松鼠短视频系统-后端部分
注意：后端代码在获得授权之前通过其他非官方渠道获得代码均为侵权，禁止用于商业用途，否则将承担因此带来等版权纠纷。
需要商业用途或者定制开发等可访问songshu.youyacao.com   联系QQ:422108995 23625059584

 -->


<?php
namespace app\api\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
require APP_PATH.'../vendor/php-ffmpeg/php-ffmpeg/src/FFMpeg/FFMpeg.php';

use FFMpeg\FFMpeg;

use FFMpeg\Format\Video\X264;
class Video extends Command
{
    protected function configure()
    {
        $this->setName('video')->setDescription('Here is the remark ');
    }

    protected function execute(Input $input, Output $output)
    {

        //加水印
        $this->wateful($input,$output);
    }
    private function wateful($input,$output){
        $ffmpeg = FFMpeg::create();
        $video = $ffmpeg->open("D:\\1.mp4");
        $watermarkPath = str_replace("\\",DS,ROOT_PATH).'public'.DS.'1.png';
        $video->filters()
            ->watermark($watermarkPath, array(
                'position' => 'relative',
                'bottom' => 50,
                'right' => 50,
            ))->synchronize();
        $format = new X264('libmp3lame', 'libx264');
        $format->on('progress', function ($video, $format, $percentage) {
            echo "$percentage % transcoded";
            file_put_contents(ROOT_PATH."public\\1.txt","$percentage % transcoded");
        });
        $video->save($format,ROOT_PATH.'public\\path_to_video.mp4');
        $output->writeln("success");
    }
}