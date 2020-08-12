<?php

/**
 * 要求开启 php zip|zlib 两个扩展
 */

namespace Core\Lib;


use Exception;
use ZipArchive;
use function core\format_path;

class ZipHandler
{
    // 压缩
    public static function zip($zip_file , $path , bool $is_append = true)
    {
        if (!$is_append && file_exists($zip_file)) {
            unlink($zip_file);
        }
        $zip = new ZipArchive();
        $zip_res = $zip->open($zip_file , ZipArchive::CREATE);
        if ($zip_res !== true) {
            throw new Exception(self::getOpenErrorMessage($zip_res));
        }
        $path = format_path($path);
        if (!file_exists($path)) {
            throw new Exception('无效的路径');
        }
        if (is_dir($path)) {
            // 目录
            $handle = function($dir) use(&$handle , $path , $zip , &$count) {
                $dir_name = str_replace($path , '' , $dir);
                $zip->addEmptyDir($dir_name);
                $file_and_dir = File::get($dir , false , true);
                foreach ($file_and_dir as $v)
                {
                    if (is_dir($v)) {
                        $handle($v);
                        continue ;
                    }
                    $filename = str_replace($path , '' , $v);
                    $zip->addFile($v , $filename);
                }
            };
            $handle($path);
        } else {
            // 文件
            $zip->addFile($path);
        }
        $zip->close();
    }

    private static function getOpenErrorMessage($code)
    {
        switch ($code)
        {
            case ZipArchive::ER_EXISTS:
                return 'File already exists.';
            case ZipArchive::ER_INCONS:
                return 'Zip archive inconsistent.';
            case ZipArchive::ER_INVAL:
                return 'Invalid argument.';
            case ZipArchive::ER_MEMORY:
                return 'Malloc failure.';
            case ZipArchive::ER_NOENT:
                return 'No such file.';
            case ZipArchive::ER_NOZIP:
                return 'Not a zip archive.';
            case ZipArchive::ER_OPEN:
                return 'Can\'t open file. ';
            case ZipArchive::ER_READ:
                return 'Read error.';
            case ZipArchive::ER_SEEK:
                return 'Seek error.';
            default:
                return 'unknow error code: ' . $code;
        }
    }

    // 解压
    public static function unzip($zip_file , $unzip_path)
    {
        if (!file_exists($zip_file)) {
            throw new Exception('压缩文件不存在');
        }
        $zip = new ZipArchive();
        $zip_res = $zip->open($zip_file);
        if ($zip_res !== true) {
            throw new Exception(self::getOpenErrorMessage($zip_res));
        }
        $zip->extractTo($unzip_path);
    }
}
