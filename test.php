<?php



require_once __DIR__ . '/app.php';

use Core\Wrapper\FFmpeg;
use Core\Wrapper\FFprobe;
use function core\random;

$video_src = 'D:\web\xinqu\api\storage\app/public\20200728/ObzvrbXv82DOSMpY98NlXGILDXC5B9vcDH4b2AfY.mp4';

$ffprobe = new FFprobe($video_src);
$video_info = $ffprobe->coreInfo();

// 预览视频截取段数
$video_simple_preview = [
    'count' => 4 ,

    'duration' => 1.5
];

// 视频预览单张间隔时间
$video_preview = [
    // 间隔 2s 截取一张
    'duration' => 2 ,
    // 单张图片尺寸：宽
    'width' => 320 ,
    // 单张图片尺寸：高
    'height' => 160 ,
];

$video_temp_dir = 'D:\web\xinqu\api\storage\app\video';
if (!file_exists($video_temp_dir)) {
    mkdir($video_temp_dir , 0400);
}
$avg_duration = floor($video_info['duration'] / $video_simple_preview['count']);
$remain_duration = $video_info['duration'] - $avg_duration * 2;
$avg_remain_duration = $remain_duration / $video_simple_preview['count'];
$start_duration = 0;
$ts = [];
$ffmpeg = new FFmpeg($video_temp_dir);
$ffmpeg = $ffmpeg->input($video_src);
$input_command = 'concat:';
for ($i = 0; $i < $video_simple_preview['count']; ++$i)
{
    $output = date('YmdHis') . random(6 , 'letter' , true) . '.ts';
    $start_duration = $avg_remain_duration + $avg_remain_duration* $i;
    $ffmpeg->ss($start_duration , 'input')
        ->duration($video_simple_preview['duration'] , 'output')
        ->output($output)
        ->run();
    $res_path = $video_temp_dir . '/' . $output;
    $input_command .= $res_path . '|';
}

$input_command = rtrim($input_command , '|');
$ffmpeg_1 = new FFmpeg($video_temp_dir);
$output_1 = date('YmdHis') . random(6 , 'letter' , true) . '.gif';


$res_path_1 = $video_temp_dir . '/' . $output_1;
$ffmpeg_1->input($input_command)
    ->output($output_1)
    ->run();
// 简略预览
$simple_preview = res_realpath($output_1);
var_dump($simple_preview);
