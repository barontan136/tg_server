<?php

namespace app\api\controller;

use app\common\controller\ApiBase;
use app\common\model\WechatFansTags;
use app\common\model\ComponentAppid;

/**
 * @apiDefine Wechat 微信
 */
class Wechat extends ApiBase {
    
    private $wechat_fans_tags_model;
    private $component_appid_model;


    public function _initialize() {
        parent::_initialize();
        $this->wechat_fans_tags_model = new WechatFansTags();
        $this->component_appid_model = new ComponentAppid();
    }

    /**
     * @api {POST} api.php?s=/wechat/jssdk jssdk
     * @apiGroup Wechat
     * @apiDescription 微信jssdk
     * @apiParam {String} url 获取skd的url 
     * @apiParam {String} appid 公众号appid 
     * @apiParamExample{object} 参数样例
     * {
     *  "url": http://aks.lawnson.com/#/scenic_spot
     *  "appid":wx4564564654654fc
     * }
     * @apiSuccess (200) {string} msg 信息,成功返回success
     * @apiSuccess (200) {int} code 0 代表无错误 
     * @apiSuccess (200) {object} data 返回数据
     * @apiSuccessExample {json} 返回样例:
     * {
      "code": 0,
      "msg": "success",
      "data": {
      "appId": "wx423b06570b614bf0",//公号appid
      "nonceStr": "tgYHcJK4X6Zxo3vg",
      "timestamp": 1518057462,
      "url": "http://aks.lawnson.com",//认证的url
      "signature": "f557c8ee318452a000bbd2963e6ee8d324301592"
      }
      }
     */
    public function jsSdk() {
        if ($this->request->isPost()) {
            $appid = $this->request->param('appid');
            if(empty($appid)){
                 return apiMsg([],1,'缺少appid');
            }
            $data = $this->request->param();
            $url = $data['url'];
//            $jsSkd = getWeObject()->getJsSignComponent($url,0,'',$appid);
            $jsSkd = getWeObject()->getJsSign($url,0,'',$appid);
            if ($jsSkd !== FALSE) {
                return apiMsg($jsSkd);
            } else {
                return apiMsg([], 103, 'jssdk验证失败');
            }
        }
    }

    /**
     * @api {POST} api.php?s=/wechat/shorturl 获取短连接
     * @apiGroup Wechat
     * @apiDescription 获取短连接
     * @apiParam {String} appid 公众号appid 
     * @apiParam {String} url 长链接url 
     * @apiParamExample{object} 参数样例
     * {
     *  "appid":wx4564564654654fc,
     *  "url": http://aks.lawnson.com/#/card_share?cid=1&coid=2
     * }
     * @apiSuccess (200) {string} msg 信息,成功返回success
     * @apiSuccess (200) {int} code 0 代表无错误 
     * @apiSuccess (200) {object} data 返回数据
     * @apiSuccessExample {json} 返回样例:
     * {
      "code": 0,
      "msg": "success",
      "data": https://xxxxxxxx
      }
     */
    public function shortUrl() {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $url = $data['url'];
            $short_url = cache($url . "_short_url");
            if (empty($short_url)) {
                $short_url = getWeObject()->getShortUrl($url);
                if ($short_url == FALSE) {
                    return apiMsg($url);
                } else {
                    cache($url . "_short_url", $short_url);
                }
            }
            return apiMsg($short_url);
        }
    }
    /**
     * @api {POST} api.php?s=/wechat/getqrcodeurl 获取公众号二维码图片
     * @apiGroup Wechat
     * @apiDescription 获取短连接
     * @apiParam {String} appid 公众号appid 
     * @apiParamExample{object} 参数样例
     * {
     *  "appid":wx4564564654654fc,
     * }
     * @apiSuccess (200) {string} msg 信息,成功返回success
     * @apiSuccess (200) {int} code 0 代表无错误 
     * @apiSuccess (200) {object} data 返回数据
     * @apiSuccessExample {json} 返回样例:
     * {
      "code": 0,
      "msg": "success",
      "data": https://xxxxxxxx
      }
     */
    public function getQrcodeUrl($appid = "") {
        if ($this->request->isPost()) {
            if(empty($appid)){
                return apiMsg([],1,'请输入appid');
            }
            //$qrcode_url = cache('qrcode_url_'.$appid);
            if(empty($qrcode_url)){
                $qrcode_url = getCover($this->component_appid_model->where('authorizer_appid',$appid)->value('qrcode_local_url'));
                cache('qrcode_url_'.$appid,$qrcode_url);
            }
            return apiMsg($qrcode_url);
        }
    }
}
