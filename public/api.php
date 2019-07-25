<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
/*防止跨域*/      
//header('Access-Control-Allow-Origin: '.$_SERVER['HTTP_ORIGIN']);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, authKey, sessionId");
if(strtoupper($_SERVER['REQUEST_METHOD'])== 'OPTIONS'){
  exit;
}
// 定义应用目录
define('APP_PATH', __DIR__ . '/../application/');
//绑定模块
define('BIND_MODULE','api');
// 加载框架引导文件
require __DIR__ . '/../thinkphp/start.php';
