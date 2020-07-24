<?php
namespace Core\Lib;

/*
 * author 陈学龙 2016-10-04
 * 图片水印 允许多开
 */

use Exception;
use function core\format_path;
use function core\get_extension;
use function core\get_image_info;
use function core\random;
use function core\utf8;

class Watermark {

	private $dir = '';

	private $positionRange  = [
	    'top' ,
        'right' ,
        'bottom' ,
        'left' ,
        'center' ,
        'top_left' ,
        'top_right' ,
        'bottom_left' ,
        'bottom_right'
    ];

	private $extensionRange = array('gif' , 'jpg' , 'png' , 'jpeg');

	public function __construct($dir = '')
    {
		if (!File::isDir($dir)) {
			throw new Exception('目录不存在');
		}
		$this->dir = format_path($dir);
	}

	// 水印合成（原图 + 水印图片）
	public function watermark(string $image = '' , string $watermark = '' , array $option = null){
		if (!File::isFile($image)){
			throw new Exception('参数 1 错误，文件不存在');
		}
		if (!File::isFile($watermark)){
            throw new Exception('参数 2 错误，文件不存在');
		}
		$image_extension        = get_extension($image);
		$watermark_extension    = get_extension($watermark);
		if (!in_array($image_extension , $this->extensionRange)) {
			throw new Exception('参数 1 文件类型错误');
		}
		if (!in_array($watermark_extension , $this->extensionRange)) {
			throw new Exception('参数 2 文件类型错误');
		}
		$default = [
            // 水印位置
            'position'   => 'center' ,
            // 水印尺寸
            'size'  => [
                /**
                 * 模式
                 * 1. ratio
                 * 2. fix-width
                 * 3. fix-height
                 */
                'mode' => 'ratio' ,
                // 宽
                'width' => 100 ,
                // 高
                'height' => 50
            ] ,
            // 水印的透明度 范围：0 - 100 透明度逐渐增强
            'opacity' => 50 ,
            // 最终生成的图片类型: jpg|jpeg|gif|png
            'extension' => 'jpg'
        ];
		if (empty($option)) {
            $option = $default;
		}
		$option['position'] = isset($option['size']) && in_array($option['position'] , $this->positionRange) ? $option['position'] : $default['position'];
        $option['size'] = $option['size'] ?? $default['size'];
        $option['opacity'] = isset($option['opacity']) && $option['opacity'] >= 0 && $option['opacity'] <= 100 ? $option['opacity'] : $default['opacity'];
        $option['extension'] = isset($option['extension']) && in_array($option['extension'] , $this->extensionRange) ? $option['extension'] : $default['extension'];





        $this->powerUp();
		// 处理原图
		switch ($image_extension)
        {
            case 'gif':
                $image_cav = imagecreatefromgif($image);
                break;
            case 'jpg':
            case 'jpeg':
                $image_cav = imagecreatefromjpeg($image);
                break;
            case 'png':
                $image_cav = imagecreatefrompng($image);
                break;
        }
		// 处理水印图片
		$watermark_cav  = imagecreatetruecolor($option['size']['width'] , $option['size']['height']);
		switch ($watermark_extension)
        {
            case 'gif':
                $cav_watermark_origin = imagecreatefromgif($watermark);
                break;
            case 'jpg':
            case 'jpeg':
                $cav_watermark_origin = imagecreatefromjpeg($watermark);
                break;
            case 'png':
                $cav_watermark_origin = imagecreatefrompng($watermark);
                break;
        }
        $image_info     = get_image_info($image);
        $watermark_info = get_image_info($watermark);
		imagecopyresampled($watermark_cav , $cav_watermark_origin , 0 , 0 , 0 , 0 , $option['size']['width'] , $option['size']['height'] , $watermark_info['width'] , $watermark_info['height']);
		// 计算水印位置
		if ($option['position'] === 'left') {
			$dst_x = 0;
			$dst_y = abs($image_info['height'] - $option['size']['height']) / 2;
		}

		if ($option['position'] === 'top') {
			$dst_x = abs($image_info['width'] - $option['size']['width']) / 2;
			$dst_y = 0;
		}

		if ($option['position'] === 'bottom') {
			$dst_x = abs($image_info['width'] - $option['size']['width']) / 2;
			$dst_y = $image_info['height'] - $option['size']['height'];
		}

		if ($option['position'] === 'right') {
			$dst_x = $image_info['width'] - $option['size']['width'];
			$dst_y = abs($image_info['height'] - $option['size']['height']) / 2;
		}

		if ($option['position'] === 'center') {
			$dst_x = abs($image_info['width'] - $option['size']['width']) / 2;
			$dst_y = abs($image_info['height'] - $option['size']['height']) / 2;
		}

		if ($option['position'] === 'top_left') {
			$dst_x = 0;
			$dst_y = 0;
		}

		if ($option['position'] === 'top_right') {
			$dst_x = $image_info['width'] - $option['size']['width'];
			$dst_y = 0;
		}

		if ($option['position'] === 'bottom_left') {
			$dst_x = 0;
			$dst_y = $image_info['height'] - $option['size']['height'];
		}

		if ($option['position'] === 'bottom_right') {
			$dst_x = $image_info['width'] - $option['size']['width'];
			$dst_y = $image_info['height'] - $option['size']['height'];
		}

		// 合成 = 原图 + 水印
		if (!imagecopymerge($image_cav , $watermark_cav , $dst_x , $dst_y , 0 , 0 , $option['size']['width'] , $option['size']['height'] , $option['opacity'])) {
			throw new Exception('合成图像失败');
		}
		// 保存处理后的图片
		$filename = date('YmdHis') . random(6 , 'letter' , true);
		$filename = $filename . '.' . $option['extension'];
		$watermark  = $this->dir . '/' . $filename;
		$watermark  = gbk($watermark);
		switch ($option['extension'])
        {
            case 'gif':
                imagegif($image_cav  , $watermark);
                break;
            case 'jpg':
            case 'jpeg':
                imagejpeg($image_cav , $watermark);
                break;
            case 'png':
                imagepng($image_cav  , $watermark);
                break;
        }

        $this->powerReset();
		$watermark = utf8($watermark);
		return $watermark;
	}
}


