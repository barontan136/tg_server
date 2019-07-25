<?php

namespace app\wxpay\controller;

use think\Controller;
use app\wxpay\model\Order;
use app\wxpay\model\OrderTurntable as OT;
use app\wxpay\model\ActivityTurntablePrizeRelation;
use app\common\model\User;

/**
 * Description of OrderTurntable
 * 抽奖活动订单支付
 */
class OrderTurntable extends Controller {

    protected $order_model;
    protected $user_model;
    protected $order_truntable_model;
    protected $activity_turntable_prize_relation;

    protected function _initialize() {
        parent::_initialize();
        $this->order_model = new Order();
        $this->user_model = new User();
        $this->order_truntable_model = new OT();
        $this->activity_turntable_prize_relation = new ActivityTurntablePrizeRelation();
    }

    /**
     * 支付回调
     */
    public function notify() {
        $pay_type = config('pay_type');
        if ($pay_type == "直连") {
            $payObj = getWePayObject();
        } else {
            $payObj = getWePayFwsObject();
        }
        $payResult = $payObj->getNotify();
        if ($payResult) {
            $order_id = $payResult['out_trade_no'];
            //获取订单详情
            $ordertruntable = $this->order_truntable_model->getInfo(['id' => $order_id]);
            $order = $this->order_model->getInfo(['from_type' => 3, 'order_id' => $order_id], 'id,status');
            //如果订单状态已经改变则终止执行
            if ($order['status'] != 1 && $ordertruntable['status'] != 1) {
                $payObj->replyXml(['return_code' => 'SUCCESS']);
            }
            //修改订单状态
            $updateOrder = ['status' => 3, 'pay_time' => time(), 'transaction_id' => $payResult['transaction_id']];
            $data = ['status' => 3, 'pay_time' => time(), 'transaction_id' => $payResult['transaction_id']];
            beginTransaction();
            $up_res_mall = $this->order_truntable_model->updateInfo($data, $ordertruntable['id'], ['status' => 1]);
            $up_res_order = $this->order_model->updateInfo($updateOrder, $order['id'], ['status' => 1]);
            if (!$up_res_mall || !$up_res_order) {
                rollbackTransaction();
            } else {
                commitTransaction();
                //给客户发送支付成功通知
                $ordertruntable['pay_time'] = $data['pay_time'];
                $this->sendPayResult($ordertruntable,$payResult['sub_appid']);
            }
            $payObj->replyXml(['return_code' => 'SUCCESS']);
        } else {
            \app\utils\service\MsgService::sendError($payObj->errMsg);
            $payObj->replyXml(['return_code' => 'FAIL']);
        }
    }

    /**
     * 给用户发送信息
     */
    public function sendPayResult($ordertruntable,$appid) {
        $openid = $this->user_model->getOpenid($ordertruntable['user_id']);
        if ($openid !== FALSE) {
                $pay_time_forment=date('Y-m-d H:i:s',$ordertruntable['pay_time']);
                $name = $this->activity_turntable_prize_relation->getValue(['activity_turntable_id' => $ordertruntable['activity_turntable_id'],'prize_from_id'=>$ordertruntable['good_id']], 'name');
            //组装商品名称
            $remark = '';
            \app\utils\service\TempMsgService::payExpress($openid, $ordertruntable['id'],$name,$pay_time_forment, sprintf("%.2f",$ordertruntable['true_money'] / 100). '元', 0,'',$remark,'',$appid);
        }
    }

}
