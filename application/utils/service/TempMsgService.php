<?php

namespace app\utils\service;

class TempMsgService {

    const JFBD_TPL = 'OPENTM207452576'; //积分变动通知
    const PAY_TOSHOP_TPL = 'OPENTM414273950'; //支付成功通知，到店自提
    const EXCHANGE_TOSHOP_TPL = 'OPENTM405776501'; //兑换成功通知，到店自提
    const PAY_EXPRESS_TPL = 'OPENTM412375761'; //购买支付成功通知，快递邮寄
    const SHIP_TPL = 'OPENTM414274800'; //发货提醒
    const PICK_TPL = 'OPENTM410086252'; //取货提醒，自提成功
    const ATTEND_ACTIVITY_TPL = 'OPENTM411651133'; //活动参与（奖品领取）

    /**
     * 积分变动通知
     * @param type $openid 发送的用户
     * @param type $userName 用户名
     * @param type $time 时间
     * @param type $change 积分变动
     * @param type $bonusBalance 积分余额
     * @param type $changRemark 变动原因
     * @param type $first 头部
     * @param type $remark 尾部
     * @param type $url 跳转链接
     */
    public static function bonusChange($openid, $userName, $time, $change, $bonusBalance, $changRemark, $first = '', $remark = '', $url = '',$appid='') {
        $array = [
            $userName,
            $time,
            $change,
            $bonusBalance,
            $changRemark,
        ];
        return send_templ_msg($openid, self::JFBD_TPL, $first, $array, $remark, $url,$appid);
    }
    /**
     * 支付成功，到店自提
     * @param type $openid 用户openid
     * @param type $order_id 订单编号
     * @param type $true_money 支付金额
     * @param type $shop_name 提货门店
     * @param type $take_code 提货码
     * @param type $take_time 提货时间
     * @param type $first 消息头
     * @param type $remark 消息尾
     * @param type $url 详情链接
     * @return type
     */
    public static function payToShop($openid, $order_id, $true_money, $shop_name, $take_code,$take_time, $first = '', $remark = '', $url = '',$appid='') {
        $array = [
            $order_id,
            $true_money,
            $shop_name,
            $take_code,
            $take_time,
        ];
        return send_templ_msg($openid, self::PAY_TOSHOP_TPL, $first, $array, $remark, $url,$appid);
    }
    /**
     * 支付成功，到店自提
     * @param type $openid 用户openid
     * @param type $good_info 商品信息（商品名称等）
     * @param type $bonus 支付积分
     * @param type $order_id 订单编号
     * @param type $pay_time 支付时间（date）
     * @param type $content 交易信息（提货有效期）
     * @param type $first 消息头
     * @param type $remark 消息尾（需包含提货码以及提货门店等信息）
     * @param type $url 详情链接
     * @return type
     */
    public static function exchangeToShop($openid, $good_info, $bonus, $order_id,$pay_time,$content,$first = '', $remark = '', $url = '',$appid='') {
        $array = [
            $good_info,
            $bonus,
            $order_id,
            $pay_time,
            $content,
        ];
        return send_templ_msg($openid, self::EXCHANGE_TOSHOP_TPL, $first, $array, $remark, $url,$appid);
    }
    /**
     * 购买支付成功，到店自提
     * @param type $openid 用户openid
     * @param type $order_id 订单编号
     * @param type $good_name 商品名称
     * @param type $pay_time 支付时间
     * @param type $true_money 支付金额
     * @param type $user_bonus 用户积分数量
     * @param type $first 消息头
     * @param type $remark 消息尾
     * @param type $url 详情链接
     * @return type
     */
    public static function payExpress($openid, $order_id, $good_name, $pay_time, $true_money, $user_bonus, $first = '', $remark = '', $url = '',$appid='') {
        $array = [
            $order_id,
            $good_name,
            $pay_time,
            $true_money,
            $user_bonus,
        ];
        return send_templ_msg($openid, self::PAY_EXPRESS_TPL, $first, $array, $remark, $url,$appid);
    }
    /**
     * 发货通知
     * @param type $openid 用户openid
     * @param type $good_name 商品名称
     * @param type $status_text 状态文本
     * @param type $express_name 物流名称
     * @param type $express_id 快递单号
     * @param type $first 头
     * @param type $remark 尾
     * @param type $url 详情链接
     * @param type $appid
     * @return type
     */
    public static function ship($openid, $good_name, $status_text, $express_name, $express_id, $first = '', $remark = '', $url = '',$appid='') {
        $array = [
            $good_name,
            $status_text,
            $express_name,
            $express_id
        ];
        return send_templ_msg($openid, self::SHIP_TPL, $first, $array, $remark, $url,$appid);
    }
    /**
     * 发货通知
     * @param type $openid 用户openid
     * @param type $good_name 商品名称
     * @param type $pick_time 取货时间
     * @param type $pick_address 取货地点
     * @param type $first 头
     * @param type $remark 尾
     * @param type $url 详情链接
     * @param type $appid
     * @return type
     */
    public static function pick($openid, $good_name, $pick_time, $pick_address, $first = '', $remark = '', $url = '',$appid='') {
        $array = [
            $good_name,
            $pick_time,
            $pick_address
        ];
        return send_templ_msg($openid, self::PICK_TPL, $first, $array, $remark, $url,$appid);
    }
    /**
     * 发货通知
     * @param type $openid 用户openid
     * @param type $activity_name 活动名称
     * @param type $attend_time 参与时间
     * @param type $first 头
     * @param type $remark 尾
     * @param type $url 详情链接
     * @param type $appid
     * @return type
     */
    public static function attendActivity($openid, $activity_name, $attend_time, $first = '', $remark = '', $url = '',$appid='') {
        $array = [
            $activity_name,
            $attend_time,
        ];
        return send_templ_msg($openid, self::ATTEND_ACTIVITY_TPL, $first, $array, $remark, $url,$appid);
    }

}
