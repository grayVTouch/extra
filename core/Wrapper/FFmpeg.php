<?php

/**
 * * 2020-07-27
 *
 * 视频 | 图片处理类
 *
 *
 */

namespace Core\Wrapper;


use Exception;

class FFmpeg
{

    /**
     * @title 执行的命令
     *
     * @var string
     */
    private $command = '';

    private $sizeCommand = '';

    private $framesCommand = '';

    private $ssForInputCommand = '';

    private $ssForOutputCommand = '';

    private $durationForInputCommand = '';

    private $durationForOutputCommand = '';

    private $fpsForInputCommand = '';

    private $fpsForOutputCommand = '';

    private $toForInputCommand = '';

    private $toForOutputCommand = '';

    private $codecCommand = '';

    private $output = '';

    private $input = [];

    private $subtitleForInputCommand = '';

    private $subtitleForOutputCommand = '';

    private $concatCommand = '';

    private $debug = false;

    private $log = '';


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
        $this->input[] = "\"" . $input . "\"";
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

    public function quiet(): FFmpeg
    {
        $this->log = '-v quiet';
        return $this;
    }

    // 设置视频帧率
    public function fpsForInput(int $fps = 0): FFmpeg
    {
        if (empty($fps)) {
            return $this;
        }
        $this->fpsForInputCommand = '-filter:v fps=' . $fps;
        return $this;
    }

    // 设置视频帧率
    public function fpsForOutput(int $fps = 0): FFmpeg
    {
        if (empty($fps)) {
            return $this;
        }
        $this->fpsForOutputCommand = '-filter:v fps=' . $fps;
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

    public function subtitleForOutput(string $subtitle = ''): FFmpeg
    {
        if (empty($subtitle)) {
            return $this;
        }
        $subtitle = str_replace('\\' , '/' , $subtitle);
        $this->subtitleForOutputCommand = '-filter:v "subtitles=\\\'' . $subtitle . '\\\'" ';
        return $this;
    }

    public function subtitleForInput(string $subtitle = ''): FFmpeg
    {
        if (empty($subtitle)) {
            return $this;
        }
        $this->subtitleForInputCommand = '-filter:v subtitles=\'"' . $subtitle . '"\' ';
        return $this;
    }

    public function subtitle(string $subtitle , string $mode = 'output'): FFmpeg
    {
        switch ($mode)
        {
            case 'input':
                return $this->subtitleForInput($subtitle);
                break;
            case 'output':
                return $this->subtitleForOutput($subtitle);
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
    // h264 | copy 等...
    public function codec(string $codec = 'copy' , string $meida = 'video'): FFmpeg
    {
        switch ($meida)
        {
            case 'video':
                $this->codecCommand .= '-codec:v ' . $codec . ' ';
                break;
            case 'audio':
                $this->codecCommand .= '-codec:a ' . $codec . ' ';
                break;
            case 'subtitle':
                $this->codecCommand .= '-codec:s ' . $codec . ' ';
                break;
            default:
                throw new Exception('不支持的媒体类型');
        }
        return $this;
    }

    public function save(string $file): void
    {
        $this->output = '"' . $file . '"';
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

    public function debug(bool $debug)
    {
        $this->debug = $debug;
        return $this;
    }

    public function concat(array $ts)
    {
        $concat = 'concat:';
        foreach ($ts as $v)
        {
            $concat .= $v . '|';
        }
        $concat = rtrim($concat , '|');
        $this->input($concat);
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
        if (!empty($this->fpsForInputCommand)) {
            $command .= $this->fpsForInputCommand . ' ';
        }
        if (preg_match("/filter:v/" , $command) > 0) {
            $command .= str_replace('filter:v ' , '' , $this->subtitleForInputCommand);
        } else {
            $command .= $this->subtitleForInputCommand;
        }
        foreach ($this->input as $v)
        {
            $command .= '-i ' . $v . ' ';
        }
        if (!empty($this->framesCommand)) {
            $command .= $this->framesCommand . ' ';
        }
        if (!empty($this->fpsForOutputCommand)) {
            $command .= $this->fpsForOutputCommand . ' ';
        }
        if (preg_match("/filter:v/" , $command) > 0) {
            $command .= str_replace('filter:v ' , '' , $this->subtitleForOutputCommand);
        } else {
            $command .= $this->subtitleForOutputCommand;
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
        if (!empty($this->anCommand)) {
            $command .= $this->anCommand . ' ';
        }
        if (!empty($this->vnCommand)) {
            $command .= $this->vnCommand . ' ';
        }
        if (!empty($this->codecCommand)) {
            $command .= $this->codecCommand . ' ';
        }
        if (!empty($this->log)) {
            $command .= $this->log . ' ';
        }

        $command .= $this->output;

        if ($this->debug) {
            // 调试模式
            file_put_contents(__DIR__ . '/ffmpeg.log' , '【' . date('Y-m-d H:i:s') . '】 ' . $command . "\n" , FILE_APPEND);
        }
        exec($command , $res , $status);
        if ($status > 0) {
            throw new Exception("ffmpeg 执行发生错误\n command: " . $command . "\nerror: " . implode("\n" , $res));
        }
    }
}
