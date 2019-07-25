<?php

namespace app\api\controller;

use aes\Aes;
use app\common\controller\ApiBase;
use \app\api\model\Merchant;
use app\api\model\ActivityGroupOrder;

/**
 * @apiDefine WxPay 微信支付
 */
class WxPay extends ApiBase {

    public $merchant_model;

    public function _initialize() {
        parent::_initialize();
    }

    /**
     * @api {POST} api.php?s=/wx_pay/getjsapiparameters 统一下单
     * @apiGroup WxPay
     * @apiDescription 获取统一下单数据
     * @apiParam {String} merchant_id 商户id
     * @apiParam {String} openid 支付用户的openid
     * @apiParam {String} appid 商户公众号appid
     * @apiParam {String} body 商品标题
     * @apiParam {String} out_trade_no 商户订单号(最多28位)
     * @apiParam {String} total_fee 支付金额（元）
     * @apiParam {String} notify_url 支付结果通知url(必须为pathinfo的模式)
     * @apiParamExample{object} 参数样例
     * {
     *  "merchant_id": 1,
     *  "openid": xxxxxxxxxxxxxxxx,
     *  "appid": wx456465214545fc,
     *  "body": 丽江一日游,
     *  "out_trade_no": '20180224143528951321',
     *  "total_fee": '0.01',
     *  "notify_url": 'http://aks.lawnson.com/index.php/wxpay/notify/notify',
     * }
     * @apiSuccess (200) {string} msg 信息,成功返回success
     * @apiSuccess (200) {int} code 0 代表无错误 
     * @apiSuccess (200) {object} data 返回数据
     * @apiSuccessExample {json} 返回样例:
     * {
     *   "code": 0,
     *   "msg": "success",
     *   "data": {
     *      "appId": "wx948f5****801d14745",
     *      "timeStamp": "15808544477",
     *      "nonceStr": "9jecask6edeq65o8jv728ymcdtetqne0",
     *      "package": "prepay_id=wx20180224144226043b015e4f0079291256",
     *      "signType": "MD5",
     *      "paySign": "1E5B894317441311580E28C2D7AF74A5",
     *    }
     * }
     */
    public function getJsApiParameters() {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $ticket = $this->request->param('lawnson_rsa_ticket_002');
            if (!empty($ticket)) {
                $data = json_decode(Aes::decrypt($ticket), true);
            }
            $payObj = getWePayObject();
            $new_out_trade_no = date('YmdHis') . $data['out_trade_no'];
            $prepay_id = $payObj->getPrepayId($data['openid'], $data['body'], $new_out_trade_no, intval($data['total_fee'] * 100), $data['notify_url']);
            writerLog('getJsApiParameters');
            writerLog($prepay_id);
            if ($prepay_id) {
                $res = (new ActivityGroupOrder())->updateOutTradeNo($data['out_trade_no'], $new_out_trade_no);
                writerLog($res);
                writerLog($data['out_trade_no']);
                writerLog($new_out_trade_no);
                if ($res) {
                    $ret = $payObj->createMchPay($prepay_id);
                    return apiMsg($ret);
                } else {
                    return apiMsg([], -1, '订单错误，请刷新后重试');
                }
            } else {
                return apiMsg([], $payObj->errCode, $payObj->errMsg);
            }
        }
    }

    /**
     * @api {POST} api.php?s=/wx_pay/sendredpack 红包发送接口
     * @apiGroup WxPay
     * @apiDescription 红包发送，此红包需要子商户授权到服务商。
     * @apiParam {String} merchant_id 商户id
     * @apiParam {String} openid 支付用户的openid
     * @apiParam {String} appid 商户公众号appid
     * @apiParam {Float} total_amount 红包金额（元）
     * @apiParam {String} mch_billno 商户订单号(最多28位)
     * @apiParam {String} wishing 红包祝福语
     * @apiParam {String} act_name 活动名称
     * @apiParam {String} remark 活动备注
     * @apiParam {String} [scene_id] 场景id 如果金额小于1元或者大于200元时候必填
     * @apiParamExample{object} 参数样例
     * {
     *  "merchant_id": 1,
     *  "openid": xxxxxxxxxxxxxxxx,
     *  "appid": wx456465214545fc,
     *  "total_amount": 1,
     *  "mch_billno": '20180224143528951321',
     *  "wishing": '祝福语',
     *  "act_name": '活动名称',
     *  "remark": '活动备注',
     *  "scene_id": 1,
     * }
     * @apiSuccess (200) {string} msg 信息,成功返回success
     * @apiSuccess (200) {int} code 0 代表无错误 
     * @apiSuccess (200) {object} data 返回数据
     * @apiSuccessExample {json} 返回样例:
     * {
     *   "code": 0,
     *   "msg": "success",
     *   "data": {
      mch_billno："20180224143528951323"
      mch_id:"1463515502"
      re_openid:"oflMXuN80BeGSM4dNrf4Me60atNA"
      send_listid:"1000041701201806213000300612325"
      total_amount:"1"
      wxappid:"wx5dc3dfafed071cb8"
     *    }
     * }
     */
    public function sendRedPack() {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            if (empty($data['merchant_id'])) {
                return apiMsg([], 1, '收款商家不能为空');
            }
            $payObj = getWePayFwsObject();
            $mch = $this->merchant_model->getMchIdName($data['merchant_id']);
            //服务商模式且是商户付款模式
            if ($mch === FALSE) {
                return apiMsg([], 1, '商家未开通微信支付');
            }
            $scene_id = null;
            if (!empty($data['scene_id'])) {
                $scene_id = $data['scene_id'];
            }
            $result = $payObj->sendRedPack($mch['mch_id'], $data['openid'], $data['appid'], intval($data['total_amount'] * 100), $data['mch_billno'], $mch['name'], $data['wishing'], $data['act_name'], $data['remark'], 1, $scene_id);
            if ($result) {
                return apiMsg([
                    "mch_billno" => $result['mch_billno'],
                    "mch_id" => $result['mch_id'],
                    "wxappid" => $result['wxappid'],
                    "re_openid" => $result['re_openid'],
                    "total_amount" => $result['total_amount'] / 100,
                    "send_listid" => $result['send_listid'],
                ]);
            } else {
                return apiMsg([], $payObj->errCode, $payObj->errMsg);
            }
        }
    }

    /**
     * @api {POST} api.php?s=/wx_pay/refund 支付退款
     * @apiGroup WxPay
     * @apiDescription 微信支付退款接口。
     * @apiParam {String} merchant_id 商户id
     * @apiParam {Float} total_fee 订单总金额（元）
     * @apiParam {Float} refund_fee 退款金额（元）
     * @apiParam {String} out_trade_no 商户订单号
     * @apiParam {String} out_refund_no 退款订单号
     * @apiParamExample{object} 参数样例
     * {
     *  "merchant_id": 1,
     *  "total_fee": 1,
     *  "refund_fee": 1,
     *  "out_trade_no": '20180224143528951321',
     *  "out_refund_no": '20184512454512345412',
     * }
     * @apiSuccess (200) {string} msg 信息,成功返回success
     * @apiSuccess (200) {int} code 0 代表无错误 
     * @apiSuccess (200) {object} data 返回数据
     * @apiSuccessExample {json} 返回样例:
     * {
     *   "code": 0,
     *   "msg": "success",
     *   "data": {
      appid:"wx5dc3dfafed071cb8"
      cash_fee:"1"
      cash_refund_fee:"1"
      coupon_refund_count:"0"
      coupon_refund_fee:"0"
      mch_id:"1463515502"
      nonce_str:"WsI7S9dwgi6tZWiW"
      out_refund_no:"nfHW1529632764739"
      out_trade_no:"9Y0106212225041312"
      refund_channel:[]
      refund_fee:"1"
      refund_id:"50000007162018062205134117062"
      result_code:"SUCCESS"
      return_code:"SUCCESS"
      return_msg:"OK"
      sign:"70289290C8D31F39027F44DF68C8B69E"
      sub_mch_id:"1482330912"
      total_fee:"1"
      transaction_id:"4200000127201806214512998200"
     *    }
     * }
     */
    public function refund() {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            if (empty($data['merchant_id'])) {
                return apiMsg([], 1, '收款商家不能为空');
            }
            $payObj = getWePayFwsObject();
            $sub_mch_id = $this->merchant_model->getMchId($data['merchant_id']);
            //服务商模式且是商户付款模式
            if ($sub_mch_id === FALSE) {
                return apiMsg([], 1, '商家未开通微信支付');
            }
            $result = $payObj->refund($sub_mch_id, $data['out_trade_no'], $data['out_refund_no'], intval($data['total_fee'] * 100), intval($data['refund_fee'] * 100));
            if ($result) {
                return apiMsg($result);
            } else {
                return apiMsg([], $payObj->errCode, $payObj->errMsg);
            }
        }
    }

    public function tt() {
        sleep(2);
        return apiMsg();
    }

}
