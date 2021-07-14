<?php

namespace Core\Lib;

use function core\gbk;
use function core\get_extension;
use function core\get_image_info;
use function core\random;
use function core\utf8;
use function core\format_path;

use Exception;

class ImageProcessor {
    /**
     * @var string
     */
    private $dir = '';

    /**
     * @var null
     */
    private static $oMemoryLimit = null;

    /*
     * @var null
     */
    private static $oMaxExcutionTime = null;


    private static $memoryLimit = '2048M';

    private static $maxExcutionTime = 0;

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

    private static $extensionRange = [
        'gif' ,
        'jpg' ,
        'png' ,
        'jpeg' ,
        'webp' ,
        'bmp' ,
    ];

    public $fontFile = '';

    public $fontFamily = 'msyh.ttc';

    /**
     * ImageProcessor constructor.
     * @param string $dir
     * @throws Exception
     */
    public function __construct(string $dir , string $font_file = '')
    {
        if (!File::isDir($dir)) {
            throw new Exception('目录不存在');
        }
        $this->dir = format_path($dir);
        $font_file = format_path($font_file);
        if (File::isFile($font_file)) {
            $this->fontFile = $font_file;
        } else {
            // 方正字体
            $font_file = __DIR__ . '/fonts/' . $this->fontFamily;
            $font_file = format_path($font_file);
            $this->fontFile = $font_file;
        }
    }

    private static function powerUp()
    {
        self::$oMemoryLimit  = ini_get('memory_limit');
        self::$oMaxExcutionTime    = ini_get('max_execution_time');
        ini_set('memory_limit' , self::$memoryLimit);
        ini_set('max_execution_time' , self::$maxExcutionTime);
    }

    // 性能配置恢复
    private static function powerReset()
    {
        ini_set('memory_limit' , self::$oMemoryLimit);
        ini_set('max_excution_time' , self::$oMaxExcutionTime);
    }

    public static function transparent($cav)
    {
        // 设置透明
        $transparent_cav = imagecolorallocatealpha($cav,255,255 , 255 , 127);
        imagecolortransparent($cav , $transparent_cav);
        imagefill($cav,0,0 , $transparent_cav);
    }

    /**
     * 图片压缩
     *
     * @param string $image
     * @param array|null $option
     * @param bool $base64
     * @return string
     * @throws Exception
     */
	public function compress(string $image = '' , array $option = null , bool $base64 = true): string
    {
        if (!File::isFile($image)) {
            throw new Exception("未找到对应文件【{$image}】");
        }
        $default = [
            /**
             * 缩放模式
             * 1. ratio ，   按照比例缩放
             * 2. fix-width  固定宽度，高度按比例缩放
             * 3. fix-height 固定高度，宽度按比例缩放
             * 4. fix        按照尺寸固定
             *
             */
            'mode'      => 'ratio' ,
            'ratio'     => 0.5 ,
            // 质量
            'quality'     => 100 ,
            // 处理后图片宽度
            'width'     => 1920 ,
            // 处理后图片高度
            'height'    => 1080 ,
            // 输出文件类型（如果指定，那么将会以这种类型输出，否则以源文件类型输出）
            'extension' => '' ,
        ];
		$mode_range = [
		    'ratio' ,
            'fix' ,
            'fix-width' ,
            'fix-height'
        ];
		$option['mode']     = isset($option['mode']) && in_array($option['mode'] , $mode_range) ? $option['mode'] : $default['mode'];
		$option['width']    = !empty($option['width']) ? $option['width'] : $default['width'];
		$option['height']   = !empty($option['height']) ? $option['height'] : $default['height'];
		$option['ratio']    = !empty($option['ratio']) ? $option['ratio'] : $default['ratio'];
		$option['quality']    = !empty($option['quality']) ? $option['quality'] : $default['quality'];
		$option['extension']    = !empty($option['extension']) ? $option['extension'] : $default['extension'];

		$mode   = $option['mode'];
		$w      = $option['width'];
		$h      = $option['height'];
		$ratio  = $option['ratio'];

		if (!in_array($mode , $mode_range)) {
		    throw new Exception("不支持的 mode【{$mode}】");
        }
        $info = get_image_info($image);
		$w = $info['width'] < $w ? $info['width'] : $w;
		$h = $info['height'] < $h ? $info['height'] : $h;
		switch ($mode)
        {
            case 'ratio':
                $w = $info['width'] * $ratio;
                $h = $info['height'] * $ratio;
                break;
            case 'fix-height':
                $w = ceil($info['width'] / $info['height'] * $h);
                break;
            case 'fix-width':
                $h = ceil($w / ($info['width'] / $info['height']));
                break;
        }
		// 提高脚本性能
		$this->powerUp();
		if (!in_array($info['extension'] , self::$extensionRange)) {
            throw new Exception("不支持的文件类型【{$info['extension']}】，当前支持的文件类型有：" . implode(',' , self::$extensionRange));
        }
        switch ($info['extension'])
        {
            case 'gif':
                $img = imagecreatefromgif($image);
                break;
            case 'jpg':
            case 'jpeg':
                $img = imagecreatefromjpeg($image);
                break;
            case 'png':
                $img = imagecreatefrompng($image);
                break;
            case 'webp':
                $img = imagecreatefromwebp($image);
                break;
        }

        $cav = imagecreatetruecolor($w , $h);
		$this->transparent($cav);

        // 平滑缩小到指定大小
        imagecopyresampled($cav , $img , 0 , 0 , 0 , 0 , $w , $h , $info['width'] , $info['height']);
        $filename = date('YmdHis') . random(6 , 'letter' , true) . '.' . $info['extension'];
        $file = $this->dir . '/' . $filename;
        $extension = empty($option['extension']) ? $info['extension'] : $option['extension'];
        switch ($extension)
        {
            case 'gif':
                $save = imagegif($cav , $file);
                break;
            case 'jpg':
            case 'jpeg':
                $save = imagejpeg($cav , $file , $option['quality']);
                break;
            case 'png':
                $save = imagepng($cav , $file);
                break;
            case 'webp':
                $save = imagewebp($cav , $file , $option['quality']);
                break;
        }
        if (!$save) {
            throw new Exception("保存图像失败【保存路径：{$save}】");
        }
        $res = '';
        if ($base64) {
            $content = file_get_contents($file);
            // 删除文件
            File::dFile($file);
            switch ($info['extension'])
            {
                case 'gif':
                    $res = sprintf('%s%s' , 'data:image/gif;base64,' , base64_encode($content));
                    break;
                case 'jpg':
                case 'jpeg':
                    $res = sprintf('%s%s' , 'data:image/jpg;base64,' , base64_encode($content));
                    break;
                case 'png':
                    $res = sprintf('%s%s' , 'data:image/png;base64,' , base64_encode($content));
                    break;
            }
        } else {
            $res = $file;
        }
        imagedestroy($img);
        imagedestroy($cav);
		$this->powerReset();
		return $res;
	}

	public function batchCompress(array $images = [] , array $option = null , bool $base64 = true): array
    {
        $res = [];
        foreach ($images as $v)
        {
            $res[] = $this->compress($v , $option , $base64);
        }
        return $res;
    }

    /*
         * 图片处理
         * @param  String    $img 待裁切的图片
         * @param  Array     $option 裁切设置
         */
    public function cut(string $img = '' , array $option = null , bool $base64 = false): string
    {
        if (!File::isFile($img)) {
            throw new Exception('待处理的图片不存在：' . $img);
        }
        $extension  = get_extension($img);
        if (!in_array($extension , self::$extensionRange)) {
            return false;
        }
        $info = get_image_info($img);
        $halfW  = $info['width'] * 0.5;
        $halfH  = $info['height'] * 0.5;
        $x      = ($info['width'] - $halfW ) / 2;
        $y      = ($info['height'] - $halfH ) / 2;
        $default = [
            // 裁切的起点 x 坐标
            'x'  => $x ,
            // 裁切的起点 y 坐标
            'y'  => $y ,
            // 裁切长度
            'w'  => $halfW ,
            // 裁切高度
            'h'  => $halfH ,
        ];
        if (empty($option)) {
            $option = $default;
        }
        // 相关配置
        $option['x'] = $option['x'] ?? $default['x'];
        $option['y'] = $option['y'] ?? $default['y'];
        $option['w'] = $option['w'] ?? $default['w'];
        $option['h'] = $option['h'] ?? $default['h'];
        $this->powerUp();
        // 读取源文件
        $img = gbk($img);
        switch ($info['extension'])
        {
            case 'gif' :
                $cav = imagecreatefromgif($img);
                break;
            case 'jpg':
            case 'jpeg':
                $cav = imagecreatefromjpeg($img);
                break;
            case 'png' :
                $cav = imagecreatefrompng($img);
                break;
        }
        $this->transparent($cav);
        // 图像裁切
        $cut_img = imagecreatetruecolor($option['w'] , $option['h']);
        $this->transparent($cut_img);
        imagecopy($cut_img , $cav , 0 , 0 , $option['x'] , $option['y'] , $option['w'] , $option['h']);
        $filename  = date('YmdHis') . random(6 , 'letter' , true) . '.' . $info['extension'];
        $file = $this->dir . '/' . $filename;
        // 删除同名文件
        if (File::isFile($file)){
            File::dFile($file);
        }
        switch ($info['extension'])
        {
            case 'gif' :
                imagegif($cut_img , $file);
                break;
            case 'jpg':
            case 'jpeg':
                imagejpeg($cut_img , $file);
                break;
            case 'png' :
                imagepng($cut_img , $file);
                break;
        }
        imagedestroy($cav);
        imagedestroy($cut_img);
        $res = '';
        if ($base64) {
            $content = file_get_contents($file);
            // 删除文件
            File::dFile($file);
            switch ($info['extension'])
            {
                case 'gif':
                    $res = sprintf('%s%s' , 'data:image/gif;base64,' , base64_encode($content));
                    break;
                case 'jpg':
                case 'jpeg':
                    $res = sprintf('%s%s' , 'data:image/jpg;base64,' , base64_encode($content));
                    break;
                case 'png':
                    $res = sprintf('%s%s' , 'data:image/png;base64,' , base64_encode($content));
                    break;
            }
        } else {
            $res = $file;
        }
        $this->powerReset();
        return $res;
    }

    public function batchCut(array $images = [] , array $option = null): array
    {
        $res = [];
        foreach ($images as $v)
        {
            $res[] = $this->cut($v , $option);
        }
        return $res;
    }

    // 水印合成（原图 + 水印图片）
    public function watermark(string $image = '' , string $watermark = '' , array $option = null , bool $base64 = false): string
    {
        if (!File::isFile($image)){
            throw new Exception('参数 1 错误，文件不存在');
        }
        if (!File::isFile($watermark)){
            throw new Exception('参数 2 错误，文件不存在');
        }
        $watermark_info = get_image_info($watermark);
        $image_info     = get_image_info($image);
        if (!in_array($image_info['extension'] , self::$extensionRange)) {
            throw new Exception('参数 1 文件类型错误');
        }
        if (!in_array($watermark_info['extension'] , self::$extensionRange)) {
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
                // 比例，仅在 mode = ratio 时有效
                'ratio' => 1 ,
                // 宽
                'width' => 100 ,
                // 高
                'height' => 100
            ] ,
            // 水印的透明度 范围：0 - 100 透明度逐渐增强 0-透明 100-完全不透明
            'opacity' => 100 ,
        ];
        if (empty($option)) {
            $option = $default;
        }
        $option['position'] = isset($option['size']) && in_array($option['position'] , $this->positionRange) ? $option['position'] : $default['position'];
        $option['opacity'] = isset($option['opacity']) && $option['opacity'] >= 0 && $option['opacity'] <= 100 ? $option['opacity'] : $default['opacity'];
        $option['size'] = $option['size'] ?? $default['size'];
        $option['size']['width']    = $option['width'] ?? $default['size']['width'];
        $option['size']['height']   = $option['height'] ?? $default['size']['height'];

        switch ($option['size']['mode'])
        {
            case 'ratio':
                $option['size']['width'] = $watermark_info['width'] * $option['size']['ratio'];
                $option['size']['height'] = $watermark_info['height'] * $option['size']['ratio'];
                break;
            case 'fix-width':
                $option['size']['height'] = $option['size']['width'] / $watermark_info['width'] * $watermark_info['height'];
                break;
            case 'fix-height':
                $option['size']['width'] = $option['size']['height'] / $watermark_info['height'] * $watermark_info['width'];
                break;
        }

        $this->powerUp();
        // 处理原图
        switch ($image_info['extension'])
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
        $this->transparent($image_cav);

        $watermark_cav  = imagecreatetruecolor($option['size']['width'] , $option['size']['height']);

        /**
         * 避免 png 图片背景为黑色
         *
         * 1. 生成颜色 白色
         * 2. 将颜色定义为透明色
         */
        $this->transparent($watermark_cav);

        switch ($watermark_info['extension'])
        {
            case 'gif':
                $watermark_src_cav = imagecreatefromgif($watermark);
                break;
            case 'jpg':
            case 'jpeg':
                $watermark_src_cav = imagecreatefromjpeg($watermark);
                break;
            case 'png':
                $watermark_src_cav = imagecreatefrompng($watermark);
                break;
        }
        $this->transparent($watermark_src_cav);

        imagecopyresampled($watermark_cav , $watermark_src_cav , 0 , 0 , 0 , 0 , $option['size']['width'] , $option['size']['height'] , $watermark_info['width'] , $watermark_info['height']);
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
        $filename = date('YmdHis') . random(6 , 'letter' , true) . '.' . $image_info['extension'];
        $watermark  = $this->dir . '/' . $filename;
        switch ($image_info['extension'])
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
        $res = '';
        if ($base64) {
            $content = file_get_contents($watermark);
            // 删除文件
            File::dFile($watermark);
            switch ($watermark_info['extension'])
            {
                case 'gif':
                    $res = sprintf('%s%s' , 'data:image/gif;base64,' , base64_encode($content));
                    break;
                case 'jpg':
                case 'jpeg':
                    $res = sprintf('%s%s' , 'data:image/jpg;base64,' , base64_encode($content));
                    break;
                case 'png':
                    $res = sprintf('%s%s' , 'data:image/png;base64,' , base64_encode($content));
                    break;
            }
        } else {
            $res = $watermark;
        }
        $this->powerReset();
        return $res;
    }

    public function batchWatermark(array $images , string $watermark = '' , array $option = null)
    {
        $res = [];
        foreach ($images as $v)
        {
            $res[] = $this->watermark($v , $watermark , $option);
        }
        return $res;
    }

    /*
    * 生成文字图片
    * @param Integer  $len			长度
    * @param String   $type        类型
    * @param Boolean  $is_format   是否格式化显示验证码
    * @param Array    $option         验证码相关设置
    */

    public function text(string $text , array $option = null , bool $base64 = true): string
    {
        $default = [
            // 画布：宽
            'cavW' => 150 ,
            // 画布：高
            'cavH' => 30 ,
            // 字体大小
            'size' => 16 ,
            // 字体角度（单位：deg，弧度）
            'angle' => 0 ,
            // 字体颜色(rgba)
            'color' => [1 , 1 , 1 , 0] ,
            // 背景颜色（rgba）
            'background' => 'transparent' ,
        ];
        if (empty($option)) {
            $option = $default;
        }

        $option['cavW'] = $option['cavW'] ?? $default['cavW'];
        $option['cavH'] = $option['cavH'] ?? $default['cavH'];
        $option['size'] = $option['size'] ?? $default['size'];
        $option['angle'] = $option['angle'] ?? $default['angle'];
        $option['color'] = $option['color'] ?? $default['color'];
        $option['background'] = $option['background'] ?? $default['background'];
        // 画布
        $cav = imagecreatetruecolor($option['cavW'] , $option['cavH']);
        // 字体颜色
        $color = imagecolorallocatealpha($cav , $option['color'][0] , $option['color'][1] , $option['color'][2] , $option['color'][3]);

        // 设置画布透明色
        if ($option['background'] === 'transparent') {
            $background = imagecolorallocatealpha($cav ,0 , 0 , 0 , 127);
            imagecolortransparent($cav , $background);
        } else {
            $background = imagecolorallocatealpha($cav , $option['background'][0] , $option['background'][1] , $option['background'][2] , $option['background'][3]);
        }
        imagefill($cav , 0 , 0 , $background);

        // 字体相关信息
        $font_info = imagettfbbox($option['size'] , $option['angle'] , $this->fontFile , $text);
        $font_w    = $font_info[2] - $font_info[0];
        $font_h    = $font_info[1] - $font_info[7];
        $x         = ($option['cavW'] - $font_w) / 2;
        $y         = ($option['cavH'] + $font_h) / 2;

        // 线条的位置
        $lx1        = ($option['cavW'] - $font_w) / 2;
        $ly1        = $option['cavH'] / 2;
        $lx2        = $lx1 + $font_w;
        $ly2        = $ly1;

        // 启用抗锯齿功能
        imageantialias($cav , true);
        // 写入字符串
        imagettftext($cav , $option['size'] , $option['angle'] , $x , $y , $color , $this->fontFile , $text);

        // 写入线条
//        imageline($cav , $lx1 , $ly1 , $lx2 , $ly2 , $alpha_white);

        $filename = date('YmdHis') . random(6 , 'letter' , true) . '.png';
        $file = $this->dir . '/' . $filename;

        imagepng($cav , $file);
        imagedestroy($cav);

        if ($base64) {
            $content = file_get_contents($file);
            $res     = sprintf('data:image/png;base64,%s' , $content);
            // 删除文件
            File::dFile($file);
        } else {
            $res = $file;
        }
        return $res;
    }

    // 原图压缩 - 会删除源文件 并且在相同位置生成压缩后的图片
    public static function originCompress(string $image , string $extension = 'webp' , int $quality = 75): string
    {
        if (!File::isFile($image)) {
            throw new Exception("未找到对应文件【{$image}】");
        }
        $range = ['webp' , 'jpeg' , 'jpg'];
        if (!in_array($extension , $range)) {
            throw new Exception("不支持的输出文件类型【{$extension}】，当前受支持的类型有：" . implode(',' , $range));
        }
        $info = get_image_info($image);
        self::powerUp();
        if (!in_array($info['extension'] , self::$extensionRange)) {
            throw new Exception("不支持的文件类型【{$info['extension']}】，当前支持的文件类型有：" . implode(',' , self::$extensionRange));
        }
        switch ($info['extension'])
        {
            case 'gif':
                $img = imagecreatefromgif($image);
                break;
            case 'jpg':
            case 'jpeg':
                $img = imagecreatefromjpeg($image);
                break;
            case 'png':
                $img = imagecreatefrompng($image);
                break;
            case 'webp':
                $img = imagecreatefromwebp($image);
                break;
        }
        $w = $info['width'];
        $h = $info['height'];
        $cav = imagecreatetruecolor($w , $h);
        self::transparent($cav);

        // 平滑缩小到指定大小
        imagecopyresampled($cav , $img , 0 , 0 , 0 , 0 , $w , $h , $info['width'] , $info['height']);

        $point_last_position = mb_strrpos($image , '.');
        $target = mb_substr($image , 0 , $point_last_position);
        $target .= '【' . random(6 , 'letter' , true) . '】.' . $extension;

        switch ($extension)
        {
            case 'jpg':
            case 'jpeg':
                imagejpeg($cav , $target , $quality);
                break;
            case 'webp':
                imagewebp($cav , $target , $quality);
                break;
        }
        imagedestroy($cav);
        imagedestroy($img);
        self::powerReset();
        return $target;
    }
}
