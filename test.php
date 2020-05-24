<?php

use Core\Lib\ZipHandler;

require_once __DIR__ . '/app.php';

ZipHandler::zip('./php.zip' , 'd:/dir_test' , true);

ZipHandler::unzip('./php.zip' , './phpzip');