<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/5/4
 * Time: 18:41
 */

namespace core;

function array_to_object(array $arr){
    return json_decode(json_encode($arr));
}

function convert_object($value)
{
    return json_decode(json_encode($value));
}
