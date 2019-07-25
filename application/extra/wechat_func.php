<?php

use think\Config;
use app\utils\service\ConfigService;

/* * ***********************网站根目录***************************** */
if (isset($_SERVER ['HTTPS']) && $_SERVER ['HTTPS'] == 'on') {
    define('WEB_PATH', 'https://' . $_SERVER['HTTP_HOST']);
} else {
    define('WEB_PATH', 'http://' . $_SERVER['HTTP_HOST']);
}
/* * ***********************网站根目录***************************** */

//错误页面
if (!Config::get('app_debug')) {
    Config::set('exception_tmpl', ROOT_PATH . DS . 'public/404/404.tpl');
}
/* * ***********************写日志***************************** */

function writerLog($log = '', $type = 'log', $path = '') {
    \log\Log::writer($log, $type, $path);
}

/**
 * 获取微信工具类实例
 */
function getWeObject($appid = '') {
    //是否有绑定
    empty($appid) && $appid = \think\Request::instance()->appid;
    //是否有注入
    empty($appid) && $appid = \think\Request::instance()->param('appid', '');
    empty($appid) && $appid = session('appid');
    if (empty($appid)) {
        if (\think\Request::instance()->isPost()) {
            echo apiMsg([], 1, '缺少appid参数');
            exit;
        }
        throw new Exception('请传入appid,错误位置：' . \think\Request::instance()->url());
    }
    $obj = getCompWeObject();
    $obj->appid = $appid;
//    $obj->access_token = $obj->getAuthorizerAccessToken($appid);
    return $obj;
}

/**
 * 第三方平台获取微信实例
 */
function getCompWeObject() {
    $options = [
        'component_token' => config('wechat_token'),
        'component_appid' => config('wechat_appid'),
        'component_appsecret' => config('wechat_secret'),
        'component_encodingaeskey' => config('wechat_encodingaeskey'),
//        'appid' => 'wx4611582462475d7e',
        'appid' => 'wxe0deba39657a483b',
        'appsecret' => 'cfcf03e05f5763c87169dbbe32f0bd61',
    ];
    return new \wechat\Wechat($options);
}

/**
 * 获取微信工具类实例(服务商)
 */
function getWePayFwsObject() {
    return new \wechat_pay\WechatPayFws();
}

/**
 * 获取微信工具类实例(直连)
 */
function getWePayObject() {
    return new \wechat_pay\WechatPay();
}

/**
 * 获取快递鸟对象
 * @return boolean
 */
function getKdnObject() {
    return new \express\Express();
}

// 判断是否是在微信浏览器里
function isWeixinBrowser() {
    $agent = $_SERVER ['HTTP_USER_AGENT'];
    if (!strpos($agent, "icroMessenger")) {
        return false;
    }
    return true;
}



//获取分享参数
function get_share($url = "") {
    if (empty($url)) {
        // 注意 URL 一定要动态获取，不能 hardcode.
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    }
    $weObject = getWeObject();
    $signPackage = $weObject->getJsSign($url);
    return $signPackage;
}
