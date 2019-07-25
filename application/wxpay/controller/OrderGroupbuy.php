<?php

namespace app\wxpay\controller;

use think\Controller;
use app\wxpay\model\Order;
use app\wxpay\model\OrderTurntable as OT;
use app\wxpay\model\ActivityTurntablePrizeRelation;
use app\common\model\User;
use app\api\model\ActivityGroupOrder;

/**
 * Description of OrderGroupbuy
 * 团购活动
 */
class OrderGroupbuy extends Controller {

    protected $order_model;
    protected $user_model;
    protected $order_truntable_model;
    /** @var  ActivityGroupOrder */
    protected $activity_group_order_model;
    protected $activity_turntable_prize_relation;

    protected function _initialize() {
        parent::_initialize();
        $this->order_model = new Order();
        $this->user_model = new User();
        $this->order_truntable_model = new OT();
        $this->activity_group_order_model = new ActivityGroupOrder();
        $this->activity_turntable_prize_relation = new ActivityTurntablePrizeRelation();
    }

    /**
     * 支付回调
     */
    public function notify() {
        $payObj = getWePayObject();
        $payResult = $payObj->getNotify();
        writerLog('input');
        writerLog($payResult);
//        $payResult = '{"appid":"wxe0deba39657a483b","bank_type":"CMB_CREDIT","cash_fee":"1","fee_type":"CNY","is_subscribe":"Y","mch_id":"1536014871","nonce_str":"pmwri9tq86wepenmyddp8y05fzqiil9l","openid":"ojYBk1t1uCAneJzxQ6dGDdELAZ3s","out_trade_no":"2019052020160188","result_code":"SUCCESS","return_code":"SUCCESS","sign":"25FAC615126C2220CA8D2E80B8762E41","time_end":"20190520201608","total_fee":"1","trade_type":"JSAPI","transaction_id":"4200000335201905200404122442"}';
        if ($payResult) {
            $out_trade_no = $payResult['out_trade_no'];
            writerLog('out_trade_no');
            writerLog($out_trade_no);
            beginTransaction();
            //获取订单详情
            $ordertruntable = $this->activity_group_order_model->where('out_trade_no', $out_trade_no)->find()->toArray();
            writerLog($ordertruntable);
            //如果订单状态已经改变则终止执行
            if ($ordertruntable['status'] != 1) {
                $payObj->replyXml(['return_code' => 'SUCCESS']);
            }
            //修改订单状态
            $updateOrder = ['status' => 2, 'pay_time' => date('Y-m-d H:i:s'), 'transaction_id' => $payResult['transaction_id']];
            $up_res_order = $this->activity_group_order_model->updateInfobyOutTradeNo($updateOrder, $out_trade_no, ['status' => 1]);
            writerLog($up_res_order);

            // 如果是团员,更新上线的团员数量
            if(isset($ordertruntable['p_id']) && $ordertruntable['p_id'] > 0){
                $ref_where = ['user_id' => $ordertruntable['p_id'], 'activity_id' => $ordertruntable['activity_id']];
                $this->activity_group_order_model->where($ref_where)->setInc('current_num');
            }
            if (!$up_res_order) {
                writerLog('支付回调错误');
                writerLog($updateOrder);
                rollbackTransaction();
            } else {
                writerLog('支付成功');
                commitTransaction();
            }
            $payObj->replyXml(['return_code' => 'SUCCESS']);
        } else {
            writerLog('支付成功错误');
            writerLog($payObj->errMsg);
            $payObj->replyXml(['return_code' => 'FAIL']);
        }
    }

    /**
     * 给用户发送信息
     */
    public function sendPayResult($ordertruntable, $appid) {
        $openid = $this->user_model->getOpenid($ordertruntable['user_id']);
        if ($openid !== FALSE) {
            $pay_time_forment = date('Y-m-d H:i:s', $ordertruntable['pay_time']);
            $name = $this->activity_turntable_prize_relation->getValue(['activity_turntable_id' => $ordertruntable['activity_turntable_id'], 'prize_from_id' => $ordertruntable['good_id']], 'name');
            //组装商品名称
            $remark = '';
            \app\utils\service\TempMsgService::payExpress($openid, $ordertruntable['id'], $name, $pay_time_forment, sprintf("%.2f", $ordertruntable['true_money'] / 100) . '元', 0, '', $remark, '', $appid);
        }
    }

}
