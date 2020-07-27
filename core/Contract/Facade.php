<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/6/1
 * Time: 10:43
 */

namespace Core\Contract;

interface Facade
{
    static function getFacadeAccessor() :string;
}
