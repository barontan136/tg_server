<?php

namespace app\api\service;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of RestFullService
 *
 * @author Administrator
 */
class ApiService {

    public static $msg = [
        0             => "success",
        1             => "数据验证不通过",
        2             => "未关注公众号，请先关注！",
        100           => "未识别错误！",
        101           => '链接微信服务器失败',
        102           => '请在微信浏览器打开',
        103           => '获取微信数据失败',
        1001          => '手机号已注册',
        99998         => '对不起，您未取得该业务的操作权限，请联系管理员后再试',
        99999         => '请勿异常操作'
    ];

    /**
     * 获取错误消息
     */
    public static function getMsg($code) {
        if (isset(self::$msg[$code]))
            return self::$msg[$code];
        return self::getMsg(100);
    }

    /**
     * url地址组装
     */
    public static function urlParse($url, $array) {
        $pre = "?";
        strpos($url, '?') !== FALSE && $pre = "&";
        foreach ($array as $key => $v) {
                $url.= "$pre$key=$v";
        }
        return $url;
    }

}
