<?php

use Core\Lib\Cropping;
use Core\Lib\ImageProcessor;
use Core\Lib\Watermark;

require_once __DIR__ . '/app.php';

$file = __DIR__ . '/0001.jpg';
$file = __DIR__ . '/test.jpg';
//
//// (string $img = '' , array $option = array() , bool $save_original_file = true , $save_original_name = true): string
$image_processor = new ImageProcessor(__DIR__);
//$path = $image_processor->cut($file);
//var_dump($path);
//
//
//$base64 = $image_processor->compress($file , null , false);
//var_dump($base64);
//
$res = $image_processor->text('@real_yami' , null , false);
var_dump($res);

//$res = $image_processor->watermark($file , __DIR__ . '/avatar.png');
$res = $image_processor->watermark($file , $res);
var_dump($res);



