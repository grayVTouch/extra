<?php

namespace core;

// 去除文件路径中最末尾的 /
function remove_backslash(string $string){
    return rtrim($string , '/\\');
}

// 处理路径中的斜杠
function chg_slash($path=''){
    return preg_replace('/\\\\/' , '/' , $path);
}

/*
 * 路径字符串处理
 * @return
	Linux    /usr/local/dir
	Windows  d:/Website/Dir
 */
function format_path($path = ''){
    if (empty($path)) {
        return $path;
    }

    $path = remove_backslash($path);
    $path = chg_slash($path);

    return $path;
}


/*
 * 单纯的对字符串进行提取（按照文件路径格式提取）
 * pathinfo 不支持中文！（php 7开始支持了）
 */
function get_file_info_from_str($str = ''){
    $str	   = chg_slash($str);
    $sIdx	   = mb_strrpos($str , '/');
    $sIdx	   = $sIdx === false ? 0 : $sIdx;
    $eIdx      = mb_strlen($str);
    $dirname   = mb_substr($str , 0 , $sIdx);
    $sepIdx    = mb_strrpos($str , '.');
    $filename  = mb_substr($str , $sIdx , $sepIdx);
    $extension = mb_substr($str , $sepIdx + 1 , mb_strlen($str));

    return array(
        'basename' => $filename . '.' . $extension ,
        'dirname'  => $dirname ,
        'filename' => $filename ,
        'extension'   => $extension
    );
}

/*
  * 获取文件信息
  * @param String $path 文件路径
  * @return Array
*/
function get_file_info($path = ''){
    $path = format_path($path);
//    $path = gbk($path);

    if (!file_exists($path)) {
        return false;
    }
    if (is_dir($path)) {
        return false;
    }
    $filename  = get_filename($path);
    $size	   = filesize($path);
    $extension = get_extension($path);
    $mime	   = get_mime(utf8($path));

    $filename  = $filename === false  ? 'unknow' : $filename;
    $size	   = $size === false	  ? 'unknow' : $size;
    $extension = $extension === false ? 'unknow' : strtolower($extension);
    $mime      = $mime === false	  ? 'unknow' : $mime;

    return array(
        'filename'  => $filename  ,
        'size'      => $size      ,
        'extension' => $extension ,
        'mime'      => $mime
    );
}

// 获取文件名（URL || Local Path 都可，不检查文件是否存在）
function get_filename($path = ''){
    $path   = format_path($path);
    $s_idx  = mb_strrpos($path , '/');
    $s_idx  = $s_idx === false ? 0 : $s_idx + 1;
    return mb_substr($path , $s_idx);
}

// 获取扩展名（URL || Local Path 都可）
function get_extension(string $path): string
{

    $path   = format_path($path);
    $s_idx  = mb_strrpos($path , '.');
    if ($s_idx === false) {
        return '';
    }
//    $s_idx += 1;
    $extension = mb_substr($path , $s_idx);
    preg_match('/^\.(\w+).*/' , $extension , $matches);
    $extension = $matches[1] ?? '';
    $extension = strtolower($extension);
    return $extension;
}

/*
 * 获取文件 mime 信息
 * @param  String  文件路径
 * @return String
 */
function get_mime($file = ''){
    $file = format_path($file);
//    $file = gbk($file);

    if (!file_exists($file)) {
        return false;
    }

    if (is_dir($file)) {
        return false;
    }

    $fres  = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($fres , $file);

    finfo_close($fres);

    return $mime;
}

/*
 * 获取图片信息
 * @param String $file
 * @return Array
 */
function get_image_info($file = ''){
    $info  = get_file_info($file);

    if (!$info) {
        return false;
    }

//    $file  = gbk($file);
    $image = getimagesize($file);

    $info['width']  = $image['0'];
    $info['height'] = $image['1'];

    return $info;
}

/**
 * 安全载入文件（可避免重复载入文件）
 * @param Mixed(Array|String)
 * @return bool
 */
function load_files($files){
    $types = ['string' , 'array'];
    $arrs = [];

    if (!in_array(gettype($files) , $types)) {
        return false;
    }

    static $file_list = [];


    if (is_string($files)) {
        $arrs[] = $files;
    } else {
        $arrs = $files;
    }

    $filename_list = array_keys($file_list);

    foreach ($arrs as $v)
    {
        $v = format_path($v);
        $v = realpath($v);

        if (!in_array($v , $filename_list)) {
//            $file_list[$v] = require gbk($v);
            $file_list[$v] = require($v);
        }
    }

    return $file_list;
}

/**
 * 检测文件编码 - 有概率会检测错误！请谨慎使用
 * @param string $file 文件名称 或 纯字符串
 * @return string|null 返回 编码名 或 null
 */
function detect_encoding(string $file): string
{
    $list = [
        // 注意顺序不能改
        'UTF-8',
        'GBK' ,
        'UTF-16LE',
        'UTF-16BE',
        'ISO-8859-1'
    ];
    $str = file_exists($file) ? file_get_contents($file) : $file;
    foreach ($list as $item)
    {
        $tmp = mb_convert_encoding($str, $item, $item);
        if (md5($tmp) == md5($str)) {
            return $item;
        }
    }
    return false;
}
