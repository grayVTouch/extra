<?php

namespace Core\Lib;

use Core\Wrapper\FFmpeg;
use Exception;
use function core\format_time;
use function core\get_filename;
use function core\random;

/**
 * m3u8 视频类
 * Class M3U8
 */
class M3U8
{
    /**
     * 内容
     *
     * @var string
     */
    private $content;

    /**
     * 访问地址
     *
     * @var string
     */
    private $url;

    /**
     * 用户设置的配置，参考 defaultOption
     */
    private $option = [];

    /**
     * 默认配置
     *
     * @var array
     */
    private $defaultOption = [
        'proxy_pass'        => '' ,
        'retry'             => 15 ,
        'url'               => '' ,
    ];

    public function __construct(string $file , array $option = [])
    {
        $option['proxy_pass']       = $option['proxy_pass'] ?? $this->defaultOption['proxy_pass'];
        $option['retry']            = $option['retry'] ?? $this->defaultOption['retry'];
        $option['url']              = $option['url'] ?? $this->defaultOption['url'];

        $this->option = $option;
        $this->url = empty($option['url']) ? '' : rtrim($option['url'] , '/\\') . '/';

        if (file_exists($file)) {
            $content = file_get_contents($file);
        } else {
            if ($this->isUrl($file)) {
                $failed_count = 0;
                while (true)
                {
                    $download_res = $this->curl($file);
                    if ($download_res['code'] === 0) {
                        break;
                    }
                    if ($failed_count >= $this->option['retry']) {
                        throw new Exception($download_res['data']);
                    }
                    $failed_count++;
                    usleep(800 * 1000);
                }

                $content = $download_res['data'];
            } else {
                $content = $file;
            }
        }
        $this->content = $content;
        if (!$this->isM3u8()) {
            throw new Exception('提供文件或内容非M3U8格式');
        }
    }

    public function isUrl(string $str): bool
    {
        return preg_match("/https?:\/\//" , $str) > 0;
    }

    public function isM3u8(): bool
    {
        return preg_match('/^#EXTM3U/' , $this->content) > 0;
    }

    /**
     * 获取文件类型
     * source - 视频源（不同清晰度的视频源）
     * playlist - 切片列表
     * @return string source | sequence
     */
    public function getType(): string
    {
        if (preg_match('/#EXT-X-STREAM-INF:/' , $this->content) > 0) {
            return 'source';
        }
        if (preg_match('/#EXTINF:/' , $this->content) > 0) {
            return 'sequence';
        }
        throw new Exception('未知的文件类型');
    }

    /**
     * 获取视频清晰度
     * @return array
     */
    public function getDefinitions(): array
    {
        $type = $this->getType();
        if ($type !== 'source') {
            return [];
        }
        preg_match_all('/#EXT-X-STREAM-INF:(.*?)RESOLUTION=(\w+x\w+).*(\n|\r|\n\r|\r\n)(.*?)\3/m' , $this->content , $matches);
        $definitions = $matches[2];
        $definition_srcs = $matches[4];
        $res = [];
        foreach ($definitions as $k => $v)
        {
            $res[$v] = $this->url . $definition_srcs[$k];
        }
        return $res;
    }

    /**
     * 获取切片列表
     * @return array
     */
    public function getSequences(): array
    {
        preg_match_all('/#EXTINF:(.*?)(\n|\r|\n\r|\r\n)(.*?)\2/m' , $this->content , $matches);
        $sequences = $matches[3];
        foreach ($sequences as &$v)
        {
            $v = $this->url . $v;
        }
        return $sequences;
    }

    /**
     * 下载切片
     *
     * @param string $save_dir 保存的目录
     * @param string $definition 清晰度
     * @param string $filename 如提供则合成后视频名臣采用这个
     */
    public function download(string $save_dir , string $definition = '' , string $filename = ''): string
    {
        if (!File::isDir($save_dir)) {
            File::mkdir($save_dir , 0777 , true);
        }
        $type = $this->getType();
        $sequences = [];
        $sequence_count = 0;
        $download = function(M3U8 $context) use($save_dir , $filename , &$sequences , &$sequence_count): string
        {
            $save_dir = rtrim($save_dir , '/\\');
            $filename = empty($filename) ? $this->filename('merged') : $filename;
            $target = $save_dir . '/' . $filename . '.mp4';
            $safe_suffix = random(6 , 'letter' , true);
            $temp_dir = $save_dir . '/temp_ts_' . $safe_suffix;
            $chunk_dir = $save_dir . '/chunk_ts_'  . $safe_suffix;;
            if (!File::isDir($temp_dir)) {
                FIle::mkdir($temp_dir , 0777 , true);
            }
            if (!File::isDir($chunk_dir)) {
                FIle::mkdir($chunk_dir , 0777 , true);
            }
            $sequences = $context->getSequences();
            $sequence_count = count($sequences);
            $curl_res = null;
            $max_merge_sequence = 50;
            $merge_sequence_count = 0;
            $merge_sequence = [];
            $chunks = [];
            foreach ($sequences as $k => $v)
            {
                $index = $k + 1;
                $temp_file = $temp_dir . '/sequence-' . $index . '.ts';
                $failed_count = 0;
                while (true)
                {
                    echo "【总：{$sequence_count}】下载切片{$index}开始 ... ";
                    $curl_res = $this->curl($v);
                    if ($curl_res['code'] === 0) {
                        echo "成功\n";
                        break;
                    }
                    $failed_count++;
                    if ($failed_count > $this->option['retry']) {
                        echo "失败！且超过最大重试次数！";
                        return '';
                    }
                    echo "失败 ... 正在进行第{$failed_count}次重试 ...\n";
                    usleep(800 * 1000);
                }
                $content = $curl_res['data'];
                file_put_contents($temp_file , $content);
                $merge_sequence[] = $temp_file;
                $merge_sequence_count++;
                if ($merge_sequence_count >= $max_merge_sequence) {
                    $chunk_file = $chunk_dir . '/chunk-' . (count($chunks) + 1) . '.ts';
                    FFmpeg::create()
                        ->concat($merge_sequence)
                        ->quiet()
                        ->save($chunk_file);
                    // 记录待合并的块
                    $chunks[] = $chunk_file;
                    // 重置已合并的 块
                    $merge_sequence = [];
                    $merge_sequence_count = 0;
                }
            }
            $chunks = empty($chunks) ? $merge_sequence : $chunks;
            FFmpeg::create()
                ->concat($chunks)
                ->save($target);
            File::delete($temp_dir);
            File::delete($chunk_dir);
            return $target;
        };
        $start_time = time();
        $start_datetime = date('Y-m-d H:i:s' , $start_time);
        echo "【start：{$start_datetime}】视频处理开始 ... \n";
        if ($type === 'source') {
            $definitions = $this->getDefinitions();
            if (empty($definitions)) {
                throw new Exception('视频源为空');
            }
            $selected_definition = $definitions[$definition] ?? '';
            if (empty($selected_definition)) {
                throw new Exception("当前提供的清晰度【{$definition}】未找到，支持的清晰度有：" . implode(',' , array_keys($definitions)));
            }
            $curl_res = null;
            $failed_count = 0;
            while (true)
            {
                $url_components = parse_url($selected_definition);
                $url_path = $url_components['path'];
                $filename = get_filename($url_path);
                echo "下载切片列表文件【{$filename}】开始 ... ";
                $curl_res = $this->curl($selected_definition);
                if ($curl_res['code'] === 0) {
                    echo "成功\n";
                    break;
                }
                $failed_count++;
                if ($failed_count > $this->option['retry']) {
                    echo "失败！且超过最大重试次数！";
                    return '';
                }
                echo "失败 ... 正在进行第{$failed_count}次重试 ...\n";
                usleep(800 * 1000);
            }
            $content = $curl_res['data'];
            // 临时暂存一下
//            $filename = $this->filename('index') . '.m3u8';
//            file_put_contents($save_dir . '/' . $filename , $content);
            $download_res = $download(new self($content , $this->option));
        } else {
            $download_res = $download($this);
        }
        $end_time = time();
        $end_datetime = date('Y-m-d H:i:s' , $end_time);
        $execute_time = format_time($end_time - $start_time);
        echo "【{$end_datetime}】视频处理结束！\n";
        echo "文件保存在：【{$download_res}】\n";
        echo "切片数量：{$sequence_count}；耗费时间：{$execute_time}\n";
        return $download_res;
    }

    public function filename(string $suffix = ''): string
    {
        return date('YmdHis') . random(6 , 'letter' , true) . (empty($suffix) ? '' : '-' . $suffix);
    }

    public function curl($url)
    {
        $response = function($message = '' , $data = '' , $code = 0){
            return compact('code' , 'message' , 'data');
        };
        $res = Http::get($url , [
            'proxy_tunnel'  => empty($this->option['proxy_pass']) ? false : true ,
            'proxy_pass'    => $this->option['proxy_pass'] ,
        ]);
        if (preg_match('/<html>/' , $res) > 0) {
            return $response("请求：【{$url}】失败" , $res , 400);
        }
        return $response('' , $res , 0);
    }
}
