<?php

namespace app\utils\service;

/**
 * 配置参数
 * Class Upload
 * @package app\api\controller
 */
class ConfigService {

    //测试公众号appid
//    public static $APPID = "wx4611582462475d7e";
    public static $APPID = "wxe0deba39657a483b";
    //错误消息id
    public static $ERRTPLID = "OPENTM204628126";
    //模板消息短id
    public static $TMSID = [
        "OPENTM412375761", //购买成功通知
        "OPENTM414273950", //支付成功通知
        "OPENTM405776501", //兑换成功通知
        "OPENTM414274800", //发货提醒
        "OPENTM410086252", //取货完成通知
        "OPENTM411651133", //活动参与成功通知
        "OPENTM207452576", //积分变动通知
    ];
    
    public static $NO_CHECK_SHOP_LIST = [
        'index/index','merchant/index','merchant/add'
    ];

    //授权时候的菜单
    public static function authMenu() {
        return[
             /*********************************官方商城***********************************/
            [
                "name" => "官方商城",
                "type" => "system",
                "content" => WEB_PATH . "/index?appid=" . self::getAppid(),
                "index" => "1",
                "pindex" => "0",
                "sort" => "0",
            ],
             /*********************************福利活动***********************************/
            [
                "name" => "福利活动",
                "type" => "system",
                "content" => "https://www.baidu.com",
                "index" => "2",
                "pindex" => "0",
                "sort" => "1"
            ],
            [
                "name" => "限时秒杀",
                "type" => "system",
                "content" => WEB_PATH . "/seckill?appid=" . self::getAppid(),
                "index" => "21",
                "pindex" => "2",
                "sort" => "0"
            ],
            [
                "name" => "来砍价",
                "type" => "system",
                "content" => WEB_PATH . "/bargain_list?appid=" . self::getAppid(),
                "index" => "22",
                "pindex" => "2",
                "sort" => "1"
            ],
            [
                "name" => "幸运抽奖",
                "type" => "system",
                "content" => WEB_PATH . "/lucky?appid=" . self::getAppid(),
                "index" => "23",
                "pindex" => "2",
                "sort" => "2"
            ],
            /*********************************会员服务***********************************/
            [
                "name" => "会员服务",
                "type" => "system",
                "content" => WEB_PATH . "/index?appid=" . self::getAppid(),
                "index" => "3",
                "pindex" => "0",
                "sort" => "2"
            ],
            [
                "name" => "管理员专区",
                "type" => "system",
                "content" => WEB_PATH . "/mp_mplogin?appid=" . self::getAppid(),
                "index" => "31",
                "pindex" => "3",
                "sort" => "0"
            ],
            [
                "name" => "积分商城",
                "type" => "system",
                "content" => WEB_PATH . "/integral_mall?appid=" . self::getAppid(),
                "index" => "32",
                "pindex" => "3",
                "sort" => "1"
            ],
            [
                "name" => "个人中心",
                "type" => "system",
                "content" => WEB_PATH . "/center?appid=" . self::getAppid(),
                "index" => "33",
                "pindex" => "3",
                "sort" => "2"
            ],
            [
                "name" => "犒赏",
                "type" => "system",
                "content" => WEB_PATH . "/reward?appid=" . self::getAppid(),
                "index" => "34",
                "pindex" => "3",
                "sort" => "3"
            ],
        ];
    }

    //系统内置菜单 类型为url
    public static function systemMenu() {
        return[
            [
                "name" => "官方商城",
                "type" => "system",
                "content" => WEB_PATH . "/index?appid=" . self::getAppid(),
            ],
            [
                "name" => "限时秒杀",
                "type" => "system",
                "content" => WEB_PATH . "/seckill?appid=" . self::getAppid(),
            ],
            [
                "name" => "来砍价",
                "type" => "system",
                "content" => WEB_PATH . "/bargain_list?appid=" . self::getAppid(),
            ],
            [
                "name" => "幸运抽奖",
                "type" => "system",
                "content" => WEB_PATH . "/lucky?appid=" . self::getAppid(),
            ],
            [
                "name" => "管理员专区",
                "type" => "system",
                "content" => WEB_PATH . "/mp_mplogin?appid=" . self::getAppid(),
            ],
            [
                "name" => "积分商城",
                "type" => "system",
                "content" => WEB_PATH . "/integral_mall?appid=" . self::getAppid(),
            ],
            [
                "name" => "个人中心",
                "type" => "system",
                "content" => WEB_PATH . "/center?appid=" . self::getAppid(),
            ],
            [
                "name" => "犒赏",
                "type" => "system",
                "content" => WEB_PATH . "/reward?appid=" . self::getAppid(),
            ],
        ];
    }

    public static function getAppid($appid = "") {
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
        session('appid',$appid);
        return $appid;
    }

    /**
     * 合并配置参数
     */
    public static function config() {
        //系统配置
        $config = cache('system_config');
        if (empty($config)) {
            $config = model('System')->getConfig();
        }
        //网站配置
        if (cache('site_config')) {
            $site_config = cache('site_config');
        } else {
            $site_config = model('System')->field('value')->where('name', 'site_config')->find();
            $site_config = unserialize($site_config['value']);
            cache('site_config', $site_config);
        }
        if ($site_config != null){
            $config = array_merge($config, $site_config);
        }
        return config($config);
    }

    /**
     * 更新系统配置
     */
    public static function setConfig() {
        $config = model('System')->getConfig();
        cache('system_config', $config);
        $site_config = model('System')->field('value')->where('name', 'site_config')->find();
        $site_config = unserialize($site_config['value']);
        cache('site_config', $site_config);
        $config = array_merge($config, $site_config);
        return $config;
    }

}
