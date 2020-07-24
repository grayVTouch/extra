<?php
namespace Core\Lib;

use function core\random;

class VerifyCode {
    // 验证码存放路径
    private $dir = '';

    // 验证码字体
    private $fontPath = '';

    function __construct($img_verify_code_dir = '' , $font_path = ''){
        if (!File::isDir($img_verify_code_dir)) {
            File::cDir($img_verify_code_dir);
        }

        if (File::isFile($font_path)) {
            $this->fontPath = format_path($font_path);
        } else {
            // 方正字体
            $this->fontPath = str_replace('\\' , '/' , __DIR__ . '/Fronts/simfang.ttf');

            if (!File::isFile($this->fontPath)) {
                throw new \Exception("验证码字体文件不存在！");
            }
        }
        $cur_date = date('Y-m-d' , time());

        $dir = format_path($img_verify_code_dir) . '/' . $cur_date;

        if (!File::isDir($dir)) {
            File::cDir($dir);
        }

        $this->dir = $dir;
    }

    /*
     * 生成文字图片
     * @param Integer  $len			长度
     * @param String   $type        类型
     * @param Boolean  $is_format   是否格式化显示验证码
     * @param Array    $option         验证码相关设置
     */

    public function makeText($len = 4 , $type = 'mixed' , array $option = array()): string
    {
        $len = is_int($len) ? $len : 4;
        $default = [
            'cavW' => 150 ,
            'cavH' => 30 ,
            'font_size' => 15 ,
            'angle' => 0 ,
            // 字体颜色(rgba)
            'color' => [0 , 0 , 0 , 0] ,
            // 背景颜色
            'background' => [0 , 0 , 0 , 0] ,
        ];
        if (empty($option)) {
            $option = array(

            );
        }

        // 相关参数
        $cavW		 = $option['cavW'];
        $cavH		 = $option['cavH'];
        $font_size   = $option['font_size'];
        $angle	     = $option['angle'];
        $verify_code = random($len , $type);
        $string	     = implode(' ' , $verify_code);

        // 画布
        $cav = imagecreatetruecolor($cavW , $cavH);

        // 画布背景颜色
        $black = imagecolorallocatealpha($cav , 0 , 0 , 0 , 1);

        // 字体颜色
        $white = imagecolorallocatealpha($cav , 255 , 255 , 255 , 0);

        // 线条通道颜色
        $alpha_white = imagecolorallocatealpha($cav , 255 , 255 , 255 , 80);

        // 填充画布颜色（背景颜色）
        imagefill($cav , 0 , 0 , $black);

        // 字体相关信息
        $font_info = imagettfbbox($font_size , $angle , $this->fontPath , $string);
        $font_w    = $font_info[2] - $font_info[0];
        $font_h    = $font_info[1] - $font_info[7];
        $x         = ($cavW - $font_w) / 2;
        $y         = ($cavH + $font_h) / 2;

        // 线条的位置
        $lx1        = ($cavW - $font_w) / 2;
        $ly1        = $cavH / 2;
        $lx2        = $lx1 + $font_w;
        $ly2        = $ly1;

        // 写入字符串
        imagettftext($cav , $font_size , $angle , $x , $y , $white , $this->fontPath , $string);

        // 写入线条
        imageline($cav , $lx1 , $ly1 , $lx2 , $ly2 , $alpha_white);

        // 保存处理后的图片文件


        $filename = date('YmdHis') . random(6 , 'letter' , true) . '.png';
        $file = $this->dir . '/' . $filename;

        imagejpeg($cav , gbk($file));
        imagedestroy($cav);

        return $file;
    }
}
