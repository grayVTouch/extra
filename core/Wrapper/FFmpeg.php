<?php

/**
 * * 2020-07-27
 *
 * 视频 | 图片处理类
 */

namespace Core\Wrapper;


use Core\Lib\File;
use Exception;
use function core\format_path;

class FFmpeg
{

    /**
     * @title 执行的命令
     *
     * @var string
     */
    private $command = '';

    private $sizeCommand = '';

    private $filterCommand = '';

    private $framesCommand = '';

    private $ssForInputCommand = '';

    private $ssForOutputCommand = '';

    private $durationForInputCommand = '';

    private $durationForOutputCommand = '';

    private $toForInputCommand = '';

    private $toForOutputCommand = '';

    private $codecCommand = '';

    private $output = '';

    private $input = '';


    public function __construct()
    {

    }

    /**
     * 不创建实例便捷调用
     *
     * @return FFmpeg
     * @throws Exception
     */
    public static function create(): FFmpeg
    {
        return new self();
    }

    public function input(string $input): FFmpeg
    {
        $this->input = "\"" . $input . "\"";
        return $this;
    }

    /**
     * 设置视频的尺寸
     * @param int $width   如果为 0，则使用原尺寸
     * @param int $height  如果为 0，则使用原尺寸
     * @return $this
     */
    public function size(int $width = 0 , int $height = 0): FFmpeg
    {
        $size_command = '-s ';
        $size_command .= empty($width) ? '' : $width . 'x';
        $size_command .= empty($height) ? '' : $height;
        $size_command = ltrim($size_command , 'x');
        $this->sizeCommand = $size_command;
        return $this;
    }

    // 设置视频帧率
    public function fpsForInput(int $fps = 0): FFmpeg
    {
        if (empty($fps)) {
            return $this;
        }
        $this->filterCommand = '-filter:v fps=' . $fps;
        return $this;
    }

    // 设置视频帧率
    public function fpsForOutput(int $fps = 0): FFmpeg
    {
        if (empty($fps)) {
            return $this;
        }
        $this->filterForOutputCommand = '-filter:v fps=' . $fps;
        return $this;
    }

    public function fps(int $fps = 0 , string $mode = 'output'): FFmpeg
    {
        switch ($mode)
        {
            case 'input':
                return $this->fpsForInput($fps);
                break;
            case 'output':
                return $this->fpsForOutput($fps);
                break;
        }
        throw new Exception('不支持的操作模式');
    }

    // 设置截取的帧数
    public function frames(int $count = 0): FFmpeg
    {
        if (empty($count)) {
            return $this;
        }
        $this->framesCommand = '-frames:v ' . $count;
        return $this;
    }

    /**
     * 设置开始时间
     * @param $timepoint 可以是 秒数；也可以是 hh:mm:ss[.xxx]
     *
     */
    public function ssForInput($timepoint = 0): FFmpeg
    {
        if (empty($timepoint)) {
            return $this;
        }
        $this->ssForInputCommand = '-ss ' . $timepoint;
        return $this;
    }


    public function ssForOutput($timepoint = 0): FFmpeg
    {
        if (empty($timepoint)) {
            return $this;
        }
        $this->ssForOutputCommand = '-ss ' . $timepoint;
        return $this;
    }


    public function ss(int $timepoint = 0 , string $mode = 'input'): FFmpeg
    {
        switch ($mode)
        {
            case 'input':
                return $this->ssForInput($timepoint);
                break;
            case 'output':
                return $this->ssForOutput($timepoint);
                break;
        }
        throw new Exception('不支持的操作模式');
    }

    public function to(int $timepoint = 0 , string $mode = 'input'): FFmpeg
    {
        switch ($mode)
        {
            case 'input':
                return $this->toForInput($timepoint);
                break;
            case 'output':
                return $this->toForOutput($timepoint);
                break;
        }
        throw new Exception('不支持的操作模式');
    }

    /**
     * 设置开始时间
     * @param $timepoint 可以是 秒数；也可以是 hh:mm:ss[.xxx]
     *
     */
    public function toForInput($timepoint = 0): FFmpeg
    {
        if (empty($timepoint)) {
            return $this;
        }
        $this->toForInputCommand = '-to ' . $timepoint;
        return $this;
    }

    /**
     * 设置开始时间
     * @param $timepoint 可以是 秒数；也可以是 hh:mm:ss[.xxx]
     *
     */
    public function toForOutput($timepoint = 0): FFmpeg
    {
        if (empty($timepoint)) {
            return $this;
        }
        $this->toForOutputCommand = '-to ' . $timepoint;
        return $this;
    }

    public function durationForInput(float $duration = 0): FFmpeg
    {
        if (empty($duration)) {
            return $this;
        }
        $this->durationForInputCommand = '-t ' . $duration;
        return $this;
    }

    public function durationForOutput(float $duration = 0): FFmpeg
    {
        if (empty($duration)) {
            return $this;
        }
        $this->durationForOutputCommand = '-t ' . $duration;
        return $this;
    }

    public function duration(float $duration = 0 , string $mode = 'output'): FFmpeg
    {
        switch ($mode)
        {
            case 'input':
                return $this->durationForInput($duration);
                break;
            case 'output':
                return $this->durationForOutput($duration);
                break;
        }
        throw new Exception('不支持的操作模式');
    }

    // 设置编码器
    // libx264 | copy 等...
    public function codec(string $codec = 'h264'): FFmpeg
    {
        $this->codecCommand = '-codec:v ' . $codec;
        return $this;
    }

    public function save(string $file): void
    {
        $this->output = $file;
        $this->run();
    }

    public function disabledAudio(): FFmpeg
    {
        $this->anCommand = '-an ';
        return $this;
    }

    public function disableVideo(): FFmpeg
    {
        $this->vnCommand = '-vn ';
        return $this;
    }

    // 重新运行
    public function run(): void
    {
        if (empty($this->input)) {
            throw new Exception('请提供输入文件');
        }
        if (empty($this->output)) {
            throw new Exception('请提供输出文件');
        }
        $command = 'ffmpeg -y ';
        if (!empty($this->ssForInputCommand)) {
            $command .= $this->ssForInputCommand . ' ';
        }
        if (!empty($this->durationForInputCommand)) {
            $command .= $this->durationForInputCommand . ' ';
        }
        if (!empty($this->toForInputCommand)) {
            $command .= $this->toForInputCommand . ' ';
        }
        $command .= '-i ' . $this->input . ' ';
        if (!empty($this->framesCommand)) {
            $command .= $this->framesCommand . ' ';
        }
        if (!empty($this->filterCommand)) {
            $command .= $this->filterCommand . ' ';
        }
        if (!empty($this->ssForOutputCommand)) {
            $command .= $this->ssForOutputCommand . ' ';
        }
        if (!empty($this->toForOutputCommand)) {
            $command .= $this->toForOutputCommand . ' ';
        }
        if (!empty($this->durationForOutputCommand)) {
            $command .= $this->durationForOutputCommand . ' ';
        }
        if (!empty($this->sizeCommand)) {
            $command .= $this->sizeCommand . ' ';
        }
        if (!empty($this->codecCommand)) {
            $command .= $this->codecCommand . ' ';
        }
        $command .= $this->output;

        exec($command , $res , $status);
        if ($status > 0) {
            throw new Exception("ffmpeg 执行发生错误\n command: " . $command . "\nerror: " . implode("\n" , $res));
        }
    }
}
