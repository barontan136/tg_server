<?php

error_reporting(E_ALL);

ini_set('display_errors', '1');

// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// 定义应用目录
/*防止跨域*/      
define('APP_PATH', __DIR__ . '/../application/');
//绑定模块
require __DIR__ . '/../thinkphp/start.php';
