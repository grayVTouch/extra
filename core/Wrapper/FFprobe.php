<?php

/**
 * 2020-07-27
 * @author running
 * 视频信息处理类
 */

namespace Core\Wrapper;


use Core\Lib\File;
use Exception;

class FFprobe
{
    private $file = '';

    private $info = null;

    public function __construct(string $file)
    {
        if (!File::isFile($file)) {
            throw new Exception('请提供有效文件');
        }
        $this->file = $file;
        $command = 'ffprobe -v quiet -show_format -show_streams -print_format json ' . $this->file;
        exec($command , $info , $status);
        if ($status > 0) {
            throw new Exception('无法获取视频信息');
        }
        $json = implode("\n" , $info);
        $info = json_decode($json , true);
        $this->info = $info;
    }

    // 获取视频信息
    public function info(): array
    {
        return $this->info;
    }

    private function getVideoStream($streams): array
    {
        foreach ($streams as $v)
        {
            if ($v['codec_type'] === 'video') {
                return $v;
            }
        }
        throw new Exception('请提供视频文件');
    }

    public function width(): int
    {
        $video_stream = $this->getVideoStream($this->info['streams']);
        return $video_stream['width'];
    }

    public function height(): int
    {
        $video_stream = $this->getVideoStream($this->info['streams']);
        return $video_stream['height'];
    }

    public function filename(): string
    {
        return $this->info['format']['filename'];
    }

    // 单位：s
    public function duration(): string
    {
        return $this->info['format']['duration'];
    }

    // 单位: byte
    public function size(): int
    {
        return $this->info['format']['size'];
    }

    // 单位: byte
    public function displayAspectRatio(): string
    {
        $video_stream = $this->getVideoStream($this->info['streams']);
        return $video_stream['display_aspect_ratio'];
    }

    // 视频的核心信息
    public function coreInfo(): array
    {
        return [
            'filename'  => $this->filename() ,
            'duration'  => $this->duration() ,
            'size'      => $this->size() ,
            'width'     => $this->width() ,
            'height'    => $this->height() ,
            'display_aspect_ratio' => $this->displayAspectRatio() ,
        ];
    }
}
