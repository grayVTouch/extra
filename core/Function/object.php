<?php
/**
 * Created by PhpStorm.
 * User: grayVTouch
 * Date: 2019/5/4
 * Time: 18:41
 */

namespace core;

use stdClass;

function array_to_object(array $array)
{
    return json_decode(json_encode($array));
}

function convert_object($value)
{
    return json_decode(json_encode($value));
}


function empty_object(): stdClass
{
    return array_to_object([]);
}
