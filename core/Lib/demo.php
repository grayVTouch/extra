<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/4/9
 * Time: 11:52
 */

require_once __DIR__ . '/../Function/base.php';
require_once __DIR__ . '/../Function/file.php';

require_once __DIR__ . '/File.php';
require_once __DIR__ . '/ImageProcessor.php';


$image = __DIR__ . '/test.jpg';

use Core\Lib\ImageProcessor;
$i = new ImageProcessor(__DIR__);
$res = $i->single($image);

echo "<img src='{$res}'>";
