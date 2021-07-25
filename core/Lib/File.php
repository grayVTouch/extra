<?php
/*
 * ****************************
 * author 陈学龙 2016-10-10
 * 文件/目录 操作类
 * ****************************
 */

namespace Core\Lib;

use Exception;

use function core\format_path;

class File {
	private static $_instance = null;

	function __construct(){
	    if (self::$_instance instanceof self) {
            throw new Exception("不允许重复实例化");
        }
        self::getInstance();
    }

    // 获取实例
	public static function getInstance(){
		if (!self::$_instance instanceof self) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/*
	 * 获取文件绝对路径
	 * @param  String $path 文件路径（相对 | 绝对路径）
	 * @return String
	 */
	public static function getRealPath($path = '')
    {
		$path = format_path($path);
		$path = realpath($path);
		if (!$path) {
			return false;
		}
		return $path;
	}

	/*
	 * 获取路径指向的地址的类型
	 * @param String $file  文件路径
	 * @return String
	 */
	public static function getType($path = '')
    {
        $path = format_path($path);
		if (!file_exists($path)) {
			return false;
		}
		if (is_dir($path)) {
			return 'dir';
		}
		return 'file';
	}

	/*
	 * 检查文件是否存在
	 * @param String $file 文件路径
	 * @return true | false
	 */
	public static function isFile($path = '')
    {
        $path = format_path($path);
		if (!file_exists($path)) {
			return false;
		}
		if (is_dir($path)) {
			return false;
		}
		return true;
	}

	/*
	 * 检查目录
	 * @param String             $dir       目录路径
	 * @return true | false
	 */
	public static function isDir($path = ''): bool
    {
        $path = format_path($path);
        if (!file_exists($path)) {
            return false;
        }
        if (is_dir($path)) {
			return true;
		}
		return false;
	}

	/*
	 * 创建目录
	 * @param String   $dir          目录路径
	 * @param Integer  $permission    目录权限
	 * @return true | false
	 */
	public static function cDir($dir = '' , $permission = 0755 , $recursive = false): bool
    {
		if (!self::isDir($dir)) {
			$dir = format_path($dir);
			$mk  = mkdir($dir , $permission , $recursive);
			if (!$mk) {
				$err_msg  = "创建目录失败\n";
				$err_msg .= "待创建的目录路径： " . $dir . "\n";
				throw new Exception($err_msg);
			}
		}

		return true;
	}

	/*
	 * 直接根据路径创建文件
	 * @param String   $dir          文件路径
	 * @param Integer  $permission    文件权限
	 * @return true | false
	 */
	public static function cFile($file = '' , $permission = 0755): bool
    {
		if (!self::isFile($file)) {
			$file   = format_path($file);
			$res    = fopen($file , 'x');
			if (!$res) {
				$err_msg  = "创建文件失败！" . "\n";
				$err_msg .= "待创建的文件路径： " . $file . "\n";
				throw new Exception($err_msg);
			}
			if (!chmod($file , $permission)) {
				$err_msg  = "设置文件权限失败" . "\n";
				$err_msg .= "待设置权限的文件路径： " . $file . "\n";
				throw new Exception($err_msg);
			}
			fclose($res);
		}

		return true;
	}

	/*
	 * 写入数据
	 * @param	String       $file       待写入数据的文件路径
	 * @param	$content     $contents   待写入的数据
	 * @param   $write_type  $write_type 写入类型
	 */
	public static function write($file = '' , $content = '' , $type = null): void
    {
		if (!self::isFile($file)) {
			self::cFile($file);
		}
		$file   = format_path($file);
		$range  = ['a' , 'w'];
		$type   = in_array($type , $range) ? $type : 'a';
		$res    = fopen($file , $type);
		if (!$res) {
			$err_msg  = "打开文件失败\n";
			$err_msg .= "待写入的文件路径：" . $file . "\n";
			throw new Exception($err_msg);
		}
		if (!flock($res , LOCK_EX)) {
            $err_msg  = "文件已被占用\n";
            $err_msg .= "待锁定的文件路径：" . $file . "\n";
            throw new Exception($err_msg);
		}
        fwrite($res , $content);
        flock($res , LOCK_UN);
        fclose($res);
	}

	// 获取文件 & 目录
	public static function get($dir , bool $is_recursive = true , bool $save_structure = true): array
    {
		if (!self::isDir($dir)){
			throw new Exception("参数 1 不是目录： " . $dir);
		}

		$is_recursive = is_bool($is_recursive) ? $is_recursive : true;
        $get = function($dir = '' , array &$result = [] , array &$res_with_structure = []) use(&$get , $is_recursive , $save_structure) {
			$dir = format_path($dir);
			$d   = dir($dir);
			if (empty($d)) {
				throw new Exception("无法打开当前目录：" . $dir);
			}
			while ($fname = $d->read())
            {
                if ($fname == '.' || $fname == '..'){
                    continue ;
                }
                $fname = $dir . '/' . $fname;
                if ($save_structure) {
                    if (!$is_recursive) {
                        $res_with_structure[] = $fname;
                    } else {
                        $res_with_structure[] = [
                            'file'  => $fname ,
                            'child' => []
                        ];
                        if (self::isDir($fname)) {
                            $get($fname , $result , $res_with_structure[count($res_with_structure) - 1]['child']);

                        }
                    }
                    continue ;
                }
                if (self::isDir($fname)) {
                    $result['dir'][] = $fname;
                    if ($is_recursive){
                        $get($fname , $result);
                    }
                } else {
                    $result['file'][] = $fname;
                }
            }
		};
		$res = [
			'dir'  => [] ,
			'file' => []
		];
		$res_with_structure = [];
        $get($dir , $res , $res_with_structure);
        if ($save_structure) {
            return $res_with_structure;
        }
		return $res;
	}

	// 获取文件
	public static function getFiles($dir = '' , $is_recursive = true)
    {
		$is_recursive = is_bool($is_recursive) ? $is_recursive : true;
		$res = self::get($dir , $is_recursive , false);
		return $res['file'];
	}

	// 获取目录
	public static function getDirs($dir , $is_recursive = true)
    {
        $is_recursive = is_bool($is_recursive) ? $is_recursive : true;
        $res = self::get($dir, $is_recursive , false);
        return $res['dir'];
    }


	// 删除单个文件
	public static function dFile($file = '')
    {
		if (!self::isFile($file)) {
            return ;
		}
        $file = format_path($file);
        if (!unlink($file)) {
            throw new Exception('删除文件失败： ' . $file);
        }
	}

	// 删除多个文件
	public static function dFiles(array $files = [])
    {
        foreach ($files as $v)
        {
            self::dFile($v);
        }
	}

	// 删除单个目录
    public static function dDir($dir): void
    {
	    if (!self::isDir($dir)) {
	        return ;
        }
        $dir = format_path($dir);
        if (!rmdir($dir)) {
            throw new Exception('删除目录失败：' . $dir);
        }
    }

    // 删除多个目录
    public static function dDirs(array $dirs = [])
    {
        foreach ($dirs as $v)
        {
            self::dDir($v);
        }
    }

	final public function __clone(){
		throw new Exception('不允许克隆');
	}

	public static function move(string $file , string $target)
    {
        return rename($file , $target);
    }

    public static function rename(string $file , string $target)
    {
        return rename($file , $target);
    }

    // 删除文件 或 目录
    public static function delete(string $path)
    {
        if (self::isFile($path)) {
            File::dFile($path);
            return ;
        }
        $res = self::get($path , true , false);
        self::dFiles($res['file']);
        self::dDirs($res['dir']);
        self::dDir($path);
    }

    // 创建目录
    public static function mkdir($dir , int $mode = 0777 , $recursive = false)
    {
        return self::cDir($dir , $mode , $recursive);
    }

    // 移动上传文件
    public static function moveUploadedFile(string $source , string $target): bool
    {
        return move_uploaded_file($source , $target);
    }

    public static function exists(string $path): bool
    {
        return file_exists($path);
    }

    public static function isEmpty(string $dir): bool
    {
        $res = self::get($dir , false , true);
        return empty($res);
    }
}




