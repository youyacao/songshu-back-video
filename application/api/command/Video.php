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
        $this->setName('video')->addArgument('name', Argument::OPTIONAL, 'Do you like ThinkPHP')->setDescription('Here is the remark ');
    }

    protected function execute(Input $input, Output $output)
    {
        //加水印
        $this->wateful($input,$output);
    }
    private function wateful($input,$output){
        $ffmpeg = FFMpeg::create();
        $video = $ffmpeg->open('uploads\\video\\1.mp4');
        $watermarkPath = '1.png';
        $video->filters()
            ->watermark($watermarkPath, array(
                'position' => 'relative',
                'bottom' => 50,
                'right' => 50,
            ));
        $format = new X264('libmp3lame', 'libx264');
        $format->on('progress', function ($video, $format, $percentage) {
            echo "$percentage % transcoded";
            file_put_contents("1.txt","$percentage % transcoded");
        });
        $video->save($format,'path_to_video.mp4');
        $output->writeln("success");
    }
}