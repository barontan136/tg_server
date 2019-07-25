<?php

namespace app\mp\controller;

use app\common\controller\MpBase;
use app\common\model\ComponentAppid;
use app\common\model\WechatMenu;
use app\common\model\Picture;
use app\common\model\WechatKeys;
use app\utils\service\ConfigService;
use app\common\model\Template;
use app\common\model\GoldCard;

/**
 * 微信自定义菜单
 * Class Menu
 * @package app\mp\controller
 */
class Wechat extends MpBase {

    /**
     * 指定默认操作的数据表
     * @var string
     */
    public $model_component_appid;
    public $model_weichtMenu;
    public $model_picture;
    public $model_keys;
    public $model_template;
    public $model_gold_card;

    protected function _initialize() {
        parent::_initialize();
        $this->model_component_appid = new ComponentAppid();
        $this->model_weichtMenu = new WechatMenu();
        $this->model_picture = new Picture();
        $this->model_keys = new WechatKeys();
        $this->model_template = new Template();
        $this->model_gold_card = new GoldCard();
        $this->assign('self', $this);
    }

    /**
     * 获取公号信息
     * @return mixed
     */
    public function index() {
        $wechat = $this->model_component_appid->where('merchant_id', $this->merchant_id)->find();
        return $this->fetch('', ['wechat' => $wechat]);
    }

    public function auth() {
        $ocode = $this->_componentOauth();
        if ($ocode) {
            //更新预授权码
            db('token')->where('name', 'pre_auth_code')->update(['expire_time' => 0, 'value' => '']);
            $component = $this->model_component_appid->saveInfo($ocode, $this->merchant_id);
            //查询是否已经绑定
            if ($component) {
                $component == 'isauth' && $this->error('此公号已被其他商户使用！', url('index'));
                $appid = $component['authorizer_appid'];
                //注入appid，避免有些地方无法获取。
                \think\Request::instance()->bind('appid',$appid);
                $rst = $this->checkIndustry($appid);
                if ($rst === true) {
                    sleep(1);
                    $this->updateTpl($appid);
                    $this->updateMenu($appid);
                    $this->checkTpl($appid);
                } else {
                    $this->model_component_appid->save(['merchant_id' => ''], ['id' => $component['id']]);
                    $this->error('请先申请公众号模板消息', url('index'));
                }
            } else {
                $this->error('授权失败！', url('index'));
            }
            session('appid', $component['authorizer_appid']);
            //初始化金卡配置
            $this->model_gold_card->initData($this->merchant_id);
            $this->success('授权成功！', url('index'));
        } else {
            $this->error('授权失败,请重新授权！', url('auth'));
        }
    }

    //获取授权码
    public function _componentOauth() {
        $authorization_code = $this->request->param('auth_code', '');
        if ($authorization_code) {
            return $authorization_code;
        } else {
            $url = WEB_PATH . $_SERVER['REQUEST_URI'];
            $oauth_url = getCompWeObject()->getComponentLoginPage($url);
            $this->redirect($oauth_url);
            exit;
        }
    }

    /**
     * 数据处理
     */
    public function _data_fiter($list, $id = 'index', $pid = 'pindex', $son = 'sub') {
        $tree = $map = [];
        foreach ($list as $item) {
            $map[$item[$id]] = $item;
        }
        foreach ($list as $item) {
            if (isset($item[$pid]) && isset($map[$item[$pid]])) {
                $map[$item[$pid]][$son][] = &$map[$item[$id]];
            } else {
                $tree[] = &$map[$item[$id]];
            }
        }
        unset($map);
        return $tree;
    }

    //查看所属行业(如果非)IT科技 互联网|电子商务 其他 其他则进行修改
    public function checkIndustry($appid) {
        $weObj = getWeObject($appid);
        $result = $weObj->getTMIndustry();
        if ($result !== false) {
            if ($result['primary_industry']['first_class'] == "IT科技" && $result['primary_industry']['second_class'] == "互联网|电子商务" && $result['secondary_industry']['first_class'] == "其他" && $result['secondary_industry']['second_class'] == "其他") {
               return true;
            } else {
                 $res = $weObj->setTMIndustry(1, 41);
                if ($res !== FALSE) {
                    return true;
                }
            }
        }
        return $weObj->errMsg;
    }

    //第三方平台
//获取服务号类型
    function getservice_type($id) {
        switch ($id) {
            case 1:
                return "升级订阅号";
                break;
            case 2:
                return "服务号";
                break;
            case 0:
                return "订阅号";
                break;
        }
    }

//获取服务号认证状态
    public function getverfy_type($id) {
        switch ($id) {
            case -1:
                return "未认证";
                break;
            case 0:
                return "已微信认证";
                break;
            case 1:
                return "已新浪微博认证";
                break;
            case 2:
                return "已腾讯微博认证";
                break;
            case 3:
                return "已资质认证通过但还未通过名称认证";
                break;
            case 4:
                return "已资质认证通过、还未通过名称认证，但通过了新浪微博认证";
                break;
            case 5:
                return "已资质认证通过、还未通过名称认证，但通过了腾讯微博认证";
                break;
        }
    }

//获取功能开通状态
    public function getbusiness_info($arr, $business) {
        return $arr[$business] == 0 ? "<font color='#5f3c23'>未开通</font>" : "<font color='#f58220'>已开通</font>";
    }

//授权的权限集
    public function getfunc_info($id) {
        switch ($id) {
            case 1:
                return "消息管理权限";
                break;
            case 2:
                return "用户管理权限";
                break;
            case 3:
                return "帐号服务权限";
                break;
            case 4:
                return "网页服务权限";
                break;
            case 5:
                return "微信小店权限";
                break;
            case 6:
                return "微信多客服权限";
                break;
            case 7:
                return "群发与通知权限";
                break;
            case 8:
                return "微信卡券权限";
                break;
            case 9:
                return "微信扫一扫权限";
                break;
            case 10:
                return "微信连WIFI权限";
                break;
            case 11:
                return "素材管理权限";
                break;
            case 12:
                return "微信摇周边权限";
                break;
            case 13:
                return "微信门店权限";
                break;
            case 14:
                return "微信支付权限";
                break;
            case 15:
                return "自定义菜单权限";
                break;
        }
    }

}
