<?php
namespace Core\Lib;
/*
 * @title 图片裁切工具库
 * @author 陈学龙 2016-10-06
 *
 */

use Exception;
use function core\gbk;
use function core\get_extension;
use function core\get_filename;
use function core\get_image_info;
use function core\random;
use function core\utf8;

class Cropping {

	private $dir  = '';

	public function __construct(string $dir = ''){
		if (!File::isDir($dir)) {
			File::cDir($dir);
		}

		$this->dir = $dir;
	}

}
