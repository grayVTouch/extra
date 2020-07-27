<?php



require_once __DIR__ . '/app.php';

use Core\Lib\ImageProcessor;
use Core\Wrapper\FFmpeg;
use Core\Wrapper\FFprobe;

//
//$file = __DIR__ . '/0001.jpg';
//$file = __DIR__ . '/test.jpg';
////
////// (string $img = '' , array $option = array() , bool $save_original_file = true , $save_original_name = true): string
//$image_processor = new ImageProcessor(__DIR__);
////$path = $image_processor->cut($file);
////var_dump($path);
////
////
////$base64 = $image_processor->compress($file , null , false);
////var_dump($base64);
////
//$res = $image_processor->text('@real_yami' , null , false);
//var_dump($res);
//
////$res = $image_processor->watermark($file , __DIR__ . '/avatar.png');
//$res = $image_processor->watermark($file , $res);
//var_dump($res);


//var_dump(is_dir('.'));

$output_dir = 'e:/ffmpeg/output';
$file = 'e:/ffmpeg/mkv.mkv';

$ffmpeg = new FFmpeg($output_dir);
$ffprobe = new FFprobe($file);

//$ffmpeg->input($file)
//    ->fps(1)
//    ->duration(10)
//    ->output('test_ffmpeg_%03d.png')
//    ->run();

$res = $ffprobe->coreInfo();
print_r($res);

