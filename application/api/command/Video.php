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