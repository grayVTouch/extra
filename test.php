<?php

require_once __DIR__ . '/app.php';

use Core\Wrapper\FFmpeg;

$video_src = __DIR__ . '/test.mp4';
$subtitle_src = __DIR__ . '/test.ass';
$output = __DIR__ . '/output.mp4';

FFmpeg::create()
    ->input($video_src)
    ->subtitle($subtitle_src)
    ->save($output);

