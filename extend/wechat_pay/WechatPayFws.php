<?php
namespace wechat_pay;
use CURLFile;
/**
 * 微信支付(服务商)SDK
 * @author zoujingli <zoujingli@qq.com>
 * @date 2015/05/13 12:12:00
 */
class WechatPayFws
{

    /** 支付接口基础地址 */
    const MCH_BASE_URL = 'https://api.mch.weixin.qq.com';

    /** 服务商appid */
    public $appid;

    /** 商户号 */
    public $mch_id;
    
    /** 商户支付密钥Key */
    public $partnerKey;

    /** 证书路径 */
    public $ssl_cer;
    public $ssl_key;

    /** 执行错误消息及代码 */
    public $errMsg;
    public $errCode;

    /**
     * WechatPay constructor.
     * @param array $options
     */
    public function __construct()
    {
        $this->appid = !empty(config('wechat_parent_appid')) ? config('wechat_parent_appid') : '';
        $this->mch_id = !empty(config('wechat_parent_mchid')) ? config('wechat_parent_mchid') : '';
        $this->partnerKey = !empty(config('wechat_partnerkey')) ? config('wechat_partnerkey') : '';
        $this->ssl_cer = EXTEND_PATH.'wechat_pay/cert_fws/apiclient_cert.pem';
        $this->ssl_key = EXTEND_PATH.'wechat_pay/cert_fws/apiclient_key.pem';
    }

    /**
     * 设置标配的请求参数，生成签名，生成接口参数xml
     * @param array $data
     * @return string
     */
    protected function createXml($data)
    {
        if (!isset($data['wxappid']) && !isset($data['mch_appid']) && !isset($data['appid'])) {
            $data['appid'] = $this->appid;
        }
        if (!isset($data['mchid']) && !isset($data['mch_id'])) {
            $data['mch_id'] = $this->mch_id;
        }
        isset($data['nonce_str']) || $data['nonce_str'] = WechatTools::createNoncestr();
        $data["sign"] = WechatTools::getPaySign($data, $this->partnerKey);
        return WechatTools::arr2xml($data);
    }

    /**
     * POST提交XML
     * @param array $data
     * @param string $url
     * @return mixed
     */
    public function postXml($data, $url)
    {
        return WechatTools::httpPost($url, $this->createXml($data));
    }

    /**
     * 使用证书post请求XML
     * @param array $data
     * @param string $url
     * @return mixed
     */
    function postXmlSSL($data, $url)
    {
        return WechatTools::httpsPost($url, $this->createXml($data), $this->ssl_cer, $this->ssl_key);
    }

    /**
     * POST提交获取Array结果
     * @param array $data 需要提交的数据
     * @param string $url
     * @param string $method
     * @return array
     */
    public function getArrayResult($data, $url, $method = 'postXml')
    {
        return WechatTools::xml2arr($this->$method($data, $url));
    }

    /**
     * 解析返回的结果
     * @param array $result
     * @return bool|array
     */
    protected function _parseResult($result)
    {
        if (empty($result)) {
            $this->errCode = 'result error';
            $this->errMsg = '解析返回结果失败';
            return false;
        }
        if ($result['return_code'] !== 'SUCCESS') {
            $this->errCode = $result['return_code'];
            $this->errMsg = $result['return_msg'];
            return false;
        }
        if (isset($result['err_code']) && $result['err_code'] !== 'SUCCESS') {
            $this->errMsg = $result['err_code_des'];
            $this->errCode = $result['err_code'];
            return false;
        }
        return $result;
    }

    /**
     * 支付通知验证处理
     * @return bool|array
     */
    public function getNotify($xml="")
    {
        libxml_disable_entity_loader(true);
        if(empty($xml)){
            $xml = file_get_contents("php://input");
        }
        $notifyInfo = (array)simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        if (empty($notifyInfo)) {
            $this->errCode = '404';
            $this->errMsg = 'Payment notification forbidden access.';
            return false;
        }
        if (empty($notifyInfo['sign'])) {
            $this->errCode = '403';
            $this->errMsg = 'Payment notification signature is missing.';
            return false;
        }
//        $data = $notifyInfo;
//        unset($data['sign']);
//        if ($notifyInfo['sign'] !== WechatTools::getPaySign($data, $this->partnerKey)) {
//            $this->errCode = '403';
//            $this->errMsg = 'Payment signature verification failed.';
//            return false;
//        }
        $this->errCode = '0';
        $this->errMsg = '';
        return $notifyInfo;
    }


    /**
     * 支付XML统一回复
     * @param array $data 需要回复的XML内容数组
     * @param bool $isReturn 是否返回XML内容，默认不返回
     * @return string
     */
    public function replyXml(array $data, $isReturn = false)
    {
        $xml = WechatTools::arr2xml($data);
        if ($isReturn) {
            return $xml;
        }
        ob_clean();
        exit($xml);
    }
    
     /**
     * 创建刷卡支付参数包
     * @param string $sub_mchid 子商户号
     * @param string $auth_code 授权Code号
     * @param string $out_trade_no 商户订单号
     * @param int $total_fee 支付费用
     * @param string $body 订单标识
     * @param null $goods_tag 商品标签
     * @param null $sub_appid 子商户关联公众号appid
     * @return array|bool
     */
    public function createMicroPay($sub_mchid,$auth_code, $out_trade_no, $total_fee, $body, $goods_tag = null,$sub_appid='')
    {
        $data = array(
            "appid"            => $this->appid,
            "mch_id"           => $this->mch_id,
            "sub_mch_id"       => $sub_mchid,
            "body"             => $body,
            "out_trade_no"     => $out_trade_no,
            "total_fee"        => $total_fee,
            "auth_code"        => $auth_code,
            "spbill_create_ip" => WechatTools::getAddress()
        );
        empty($goods_tag) || $data['goods_tag'] = $goods_tag;
        empty($sub_appid) || $data['sub_appid'] = $sub_appid;
        $json = WechatTools::xml2arr($this->postXml($data, self::MCH_BASE_URL . '/pay/micropay'));
        if (!empty($json) && false === $this->_parseResult($json)) {
            return false;
        }
        return $json;
    }

    /**
     * 获取预支付ID
     * @param string $openid 用户openid，JSAPI必填
     * @param string $sub_mchid 子商户号
     * @param string $body 商品标题
     * @param string $out_trade_no 第三方订单号
     * @param int $total_fee 订单总价
     * @param string $notify_url 支付成功回调地址
     * @param string $trade_type 支付类型JSAPI|NATIVE|APP
     * @param string $goods_tag 商品标记，代金券或立减优惠功能的参数
     * @param string $fee_type 交易币种
     * @param string $sub_appid 子商户公众号appid
     * @param string $sub_openid 子商户openid
     * @return bool|string
     */
    public function getPrepayId($sub_openid,$sub_appid,$sub_mchid, $body, $out_trade_no, $total_fee, $notify_url, $trade_type = "JSAPI", $goods_tag = null, $fee_type = 'CNY')
    {
        $postdata = array(
            "sub_mch_id"       => $sub_mchid,
            "body"             => $body,
            "out_trade_no"     => $out_trade_no,
            "fee_type"         => $fee_type,
            "total_fee"        => $total_fee,
            "notify_url"       => $notify_url,
            "trade_type"       => $trade_type,
            "spbill_create_ip" => WechatTools::getAddress()
        );
        $trade_type == "MWEB"?$postdata['scene_info']=json_encode(['h5_info'=>['type'=>'wap','wap_url'=>WEB_PATH,'wap_name'=>config('site_title')]]):'';
        empty($goods_tag) || $postdata['goods_tag'] = $goods_tag;
        $postdata['sub_appid'] = $sub_appid;
        $postdata['sub_openid'] = $sub_openid;
        $result = $this->getArrayResult($postdata, self::MCH_BASE_URL . '/pay/unifiedorder');
        if (false === $this->_parseResult($result)) {
            return false;
        }
        return in_array($trade_type, array('JSAPI', 'APP')) ? $result['prepay_id'] : $result['mweb_url'];
    }

    /**
     * 获取二维码预支付ID
     * @param string $sub_mchid 子商户号
     * @param string $openid 用户openid，JSAPI必填
     * @param string $body 商品标题
     * @param string $out_trade_no 第三方订单号
     * @param int $total_fee 订单总价
     * @param string $notify_url 支付成功回调地址
     * @param string $goods_tag 商品标记，代金券或立减优惠功能的参数
     * @param string $fee_type 交易币种
     * @param string $sub_appid 子商户公众号appid
     * @param string $sub_openid 子商户openid
     * @return bool|string
     */
    public function getQrcPrepayId($sub_mchid,$openid,$body, $out_trade_no, $total_fee, $notify_url, $goods_tag = null, $fee_type = 'CNY',$sub_appid='',$sub_openid='')
    {
        $postdata = array(
            "sub_mch_id"       => $sub_mchid,
            "body"             => $body,
            "out_trade_no"     => $out_trade_no,
            "fee_type"         => $fee_type,
            "total_fee"        => $total_fee,
            "notify_url"       => $notify_url,
            "trade_type"       => 'NATIVE',
            "spbill_create_ip" => WechatTools::getAddress()
        );
        empty($goods_tag) || $postdata['goods_tag'] = $goods_tag;
        empty($openid) || $postdata['openid'] = $openid;
        !empty($sub_appid) && $postdata['sub_appid'] = $sub_appid;
        !empty($sub_openid) && $postdata['sub_openid'] = $sub_openid;
        $result = $this->getArrayResult($postdata, self::MCH_BASE_URL . '/pay/unifiedorder');
        if (false === $this->_parseResult($result) || empty($result['prepay_id'])) {
            return false;
        }
        return $result['prepay_id'];
    }

    /**
     * 获取支付规二维码
     * @param string $product_id 商户定义的商品id 或者订单号
     * @return string
     */
    public function getQrcPayUrl($product_id)
    {
        $data = array(
            'appid'      => $this->appid,
            'mch_id'     => $this->mch_id,
            'time_stamp' => (string)time(),
            'nonce_str'  => WechatTools::createNoncestr(),
            'product_id' => (string)$product_id,
        );
        $data['sign'] = WechatTools::getPaySign($data, $this->partnerKey);
        return "weixin://wxpay/bizpayurl?" . http_build_query($data);
    }


    /**
     * 创建JSAPI支付参数包
     * @param string $prepay_id
     * @return array
     */
    public function createMchPay($prepay_id)
    {
        $option = array();
        $option["appId"] = $this->appid;
        $option["timeStamp"] = (string)time();
        $option["nonceStr"] = WechatTools::createNoncestr();
        $option["package"] = "prepay_id={$prepay_id}";
        $option["signType"] = "MD5";
        $option["paySign"] = WechatTools::getPaySign($option, $this->partnerKey);
        return $option;
    }

    /**
     * 关闭订单
     * @param string $sub_mchid
     * @param string $out_trade_no
     * @return bool
     */
    public function closeOrder($sub_mchid,$out_trade_no)
    {
        $data = array('sub_mch_id'=>$sub_mchid,'out_trade_no' => $out_trade_no);
        $result = $this->getArrayResult($data, self::MCH_BASE_URL . '/pay/closeorder');
        if (false === $this->_parseResult($result)) {
            return false;
        }
        return ($result['return_code'] === 'SUCCESS');
    }

    /**
     * 查询订单详情
     * @param $sub_mchid
     * @param $out_trade_no
     * @return bool|array
     */
    public function queryOrder($sub_mchid,$out_trade_no)
    {
        $data = array('sub_mch_id'=>$sub_mchid,'out_trade_no' => $out_trade_no);
        $result = $this->getArrayResult($data, self::MCH_BASE_URL . '/pay/orderquery');
        if (false === $this->_parseResult($result)) {
            return false;
        }
        return $result;
    }

    /**
     * 订单退款接口
     * @param string $sub_mchid 子商户商户号
     * @param string $out_trade_no 商户订单号
     * @param string $out_refund_no 商户退款订单号
     * @param int $total_fee 商户订单总金额
     * @param int $refund_fee 退款金额
     * @param int|null $op_user_id 操作员ID，默认商户ID
     * @param string $refund_account 退款资金来源
     *      仅针对老资金流商户使用
     *          REFUND_SOURCE_UNSETTLED_FUNDS --- 未结算资金退款（默认使用未结算资金退款）
     *          REFUND_SOURCE_RECHARGE_FUNDS --- 可用余额退款
     * @return bool
     */
    public function refund($sub_mchid,$out_trade_no, $out_refund_no, $total_fee, $refund_fee, $op_user_id = null, $refund_account = '')
    {
        $data = array();
        $data['out_trade_no'] = $out_trade_no;
        $data['sub_mch_id'] = $sub_mchid;
        $data['out_refund_no'] = $out_refund_no;
        $data['total_fee'] = $total_fee;
        $data['refund_fee'] = $refund_fee;
        $data['op_user_id'] = empty($op_user_id) ? $this->mch_id : $op_user_id;
        !empty($refund_account) && $data['refund_account'] = $refund_account;
        $result = $this->getArrayResult($data, self::MCH_BASE_URL . '/secapi/pay/refund', 'postXmlSSL');
        $result = $this->_parseResult($result);
        if (false === $result) {
            return false;
        }
        return $result;
    }

    /**
     * 退款查询接口
     * @param string $sub_mchid
     * @param string $out_trade_no
     * @return bool|array
     */
    public function refundQuery($sub_mchid,$out_trade_no)
    {
        $data = array();
        $data['out_trade_no'] = $out_trade_no;
        $data['sub_mch_id'] = $sub_mchid;
        $result = $this->getArrayResult($data, self::MCH_BASE_URL . '/pay/refundquery');
        if (false === $this->_parseResult($result)) {
            return false;
        }
        return $result;
    }

    /**
     * 获取对账单
     * @param string $sub_mchid 子商户号
     * @param string $bill_date 账单日期，如 20141110
     * @param string $bill_type ALL|SUCCESS|REFUND|REVOKED
     * @return bool|array
     */
    public function getBill($sub_mchid,$bill_date, $bill_type = 'ALL')
    {
        $data = array();
        $data['sub_mch_id'] = $sub_mchid;
        $data['bill_date'] = $bill_date;
        $data['bill_type'] = $bill_type;
        $result = $this->postXml($data, self::MCH_BASE_URL . '/pay/downloadbill');
        $json = WechatTools::xml2arr($result);
        if (!empty($json) && false === $this->_parseResult($json)) {
            return false;
        }
        return $json;
    }

    /**
     * 发送现金红包
     * @param string $sub_mchid 子商户号
     * @param string $appid 子商appid
     * @param string $openid 子商户OPENID
     * @param int $total_amount 红包总金额
     * @param string $mch_billno 商户订单号
     * @param string $sendname 商户名称
     * @param string $wishing 红包祝福语
     * @param string $act_name 活动名称
     * @param string $remark 备注信息
     * @param null|int $total_num 红包发放总人数（大于1为裂变红包）
     * @param null|string $scene_id 场景id
     * @param string $risk_info 活动信息
     * @param null|string $consume_mch_id 资金授权商户号
     * @return array|bool
     * @link  https://pay.weixin.qq.com/wiki/doc/api/tools/cash_coupon.php?chapter=13_5
     */
    public function sendRedPack($sub_mchid,$openid,$appid,$total_amount, $mch_billno, $sendname, $wishing, $act_name, $remark, $total_num = 1, $scene_id = null, $risk_info = '', $consume_mch_id = null)
    {
        $data = array();
        $data['sub_mch_id'] = $sub_mchid; 
        $data['msgappid'] = $appid; 
        $data['mch_billno'] = $mch_billno; // 商户订单号 mch_id+yyyymmdd+10位一天内不能重复的数字
        $data['wxappid'] = $this->appid;
        $data['send_name'] = $sendname; //商户名称
        $data['re_openid'] = $openid; //红包接收者
        $data['total_amount'] = $total_amount; //红包总金额
        $data['wishing'] = $wishing; //红包祝福语
        $data['client_ip'] = WechatTools::getAddress(); //调用接口的机器Ip地址
        $data['act_name'] = $act_name; //活动名称
        $data['remark'] = $remark; //备注信息
        $data['total_num'] = $total_num;
        !empty($scene_id) && $data['scene_id'] = $scene_id;
        !empty($risk_info) && $data['risk_info'] = $risk_info;
        !empty($consume_mch_id) && $data['consume_mch_id'] = $consume_mch_id;
        if ($total_num > 1) {
            $data['amt_type'] = 'ALL_RAND';
            $api = self::MCH_BASE_URL . '/mmpaymkttransfers/sendgroupredpack';
        } else {
            $api = self::MCH_BASE_URL . '/mmpaymkttransfers/sendredpack';
        }
        $result = $this->postXmlSSL($data, $api);
        $json = WechatTools::xml2arr($result);
        if (!empty($json) && false === $this->_parseResult($json)) {
            return false;
        }
        return $json;
    }


    /**
     * 现金红包状态查询
     * @param string $billno
     * @return bool|array
     * @link https://pay.weixin.qq.com/wiki/doc/api/tools/cash_coupon.php?chapter=13_7&index=6
     */
    public function queryRedPack($billno)
    {
        $data['mch_billno'] = $billno;
        $data['bill_type'] = 'MCHT';
        $result = $this->postXmlSSL($data, self::MCH_BASE_URL . '/mmpaymkttransfers/gethbinfo');
        $json = WechatTools::xml2arr($result);
        if (!empty($json) && false === $this->_parseResult($json)) {
            return false;
        }
        return $json;
    }

    /**
     * 企业付款
     * @param string $openid 红包接收者OPENID
     * @param int $amount 红包总金额
     * @param string $billno 商户订单号
     * @param string $desc 备注信息
     * @return bool|array
     * @link https://pay.weixin.qq.com/wiki/doc/api/tools/mch_pay.php?chapter=14_2
     */
    public function transfers($openid, $amount, $billno, $desc)
    {
        $data = array();
        $data['mchid'] = $this->mch_id;
        $data['mch_appid'] = $this->appid;
        $data['partner_trade_no'] = $billno;
        $data['openid'] = $openid;
        $data['amount'] = $amount;
        $data['check_name'] = 'NO_CHECK'; #不验证姓名
        $data['spbill_create_ip'] = WechatTools::getAddress(); //调用接口的机器Ip地址
        $data['desc'] = $desc; //备注信息
        $result = $this->postXmlSSL($data, self::MCH_BASE_URL . '/mmpaymkttransfers/promotion/transfers');
        $json = WechatTools::xml2arr($result);
        if (!empty($json) && false === $this->_parseResult($json)) {
            return false;
        }
        return $json;
    }

    /**
     * 企业付款查询
     * @param string $billno
     * @return bool|array
     * @link https://pay.weixin.qq.com/wiki/doc/api/tools/mch_pay.php?chapter=14_3
     */
    public function queryTransfers($billno)
    {
        $data['appid'] = $this->appid;
        $data['mch_id'] = $this->mch_id;
        $data['partner_trade_no'] = $billno;
        $result = $this->postXmlSSL($data, self::MCH_BASE_URL . '/mmpaymkttransfers/gettransferinfo');
        $json = WechatTools::xml2arr($result);
        if (!empty($json) && false === $this->_parseResult($json)) {
            return false;
        }
        return $json;
    }

    /**
     * 二维码链接转成短链接
     * @param string $url 需要处理的长链接
     * @return bool|string
     */
    public function shortUrl($url)
    {
        $data = array();
        $data['long_url'] = $url;
        $result = $this->getArrayResult($data, self::MCH_BASE_URL . '/tools/shorturl');
        if (!$result || $result['return_code'] !== 'SUCCESS') {
            $this->errCode = $result['return_code'];
            $this->errMsg = $result['return_msg'];
            return false;
        }
        if (isset($result['err_code']) && $result['err_code'] !== 'SUCCESS') {
            $this->errMsg = $result['err_code_des'];
            $this->errCode = $result['err_code'];
            return false;
        }
        return $result['short_url'];
    }

    /**
     * 发放代金券
     * @param int $coupon_stock_id 代金券批次id
     * @param string $partner_trade_no 商户此次发放凭据号（格式：商户id+日期+流水号），商户侧需保持唯一性
     * @param string $openid Openid信息
     * @param string $op_user_id 操作员帐号, 默认为商户号 可在商户平台配置操作员对应的api权限
     * @return bool|array
     * @link  https://pay.weixin.qq.com/wiki/doc/api/tools/sp_coupon.php?chapter=12_3
     */
    public function sendCoupon($coupon_stock_id, $partner_trade_no, $openid, $op_user_id = null)
    {
        $data = array();
        $data['appid'] = $this->appid;
        $data['coupon_stock_id'] = $coupon_stock_id;
        $data['openid_count'] = 1;
        $data['partner_trade_no'] = $partner_trade_no;
        $data['openid'] = $openid;
        $data['op_user_id'] = empty($op_user_id) ? $this->mch_id : $op_user_id;
        $result = $this->postXmlSSL($data, self::MCH_BASE_URL . '/mmpaymkttransfers/send_coupon');
        $json = WechatTools::xml2arr($result);
        if (!empty($json) && false === $this->_parseResult($json)) {
            return false;
        }
        return $json;
    }
}
/**
 * 微信接口通用类
 *
 * @category WechatSDK
 * @subpackage library
 * @author Anyon <zoujingli@qq.com>
 * @date 2016/05/28 11:55
 */
class WechatTools
{

    /**
     * 产生随机字符串
     * @param int $length
     * @param string $str
     * @return string
     */
    static public function createNoncestr($length = 32, $str = "")
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * 获取签名
     * @param array $arrdata 签名数组
     * @param string $method 签名方法
     * @return bool|string 签名值
     */
    static public function getSignature($arrdata, $method = "sha1")
    {
        if (!function_exists($method)) {
            return false;
        }
        ksort($arrdata);
        $params = array();
        foreach ($arrdata as $key => $value) {
            $params[] = "{$key}={$value}";
        }
        return $method(join('&', $params));
    }

    /**
     * 生成支付签名
     * @param array $option
     * @param string $partnerKey
     * @return string
     */
    static public function getPaySign($option, $partnerKey)
    {
        ksort($option);
        $buff = '';
        foreach ($option as $k => $v) {
            $buff .= "{$k}={$v}&";
        }
        return strtoupper(md5("{$buff}key={$partnerKey}"));
    }

    /**
     * XML编码
     * @param mixed $data 数据
     * @param string $root 根节点名
     * @param string $item 数字索引的子节点名
     * @param string $id 数字索引子节点key转换的属性名
     * @return string
     */
    static public function arr2xml($data, $root = 'xml', $item = 'item', $id = 'id')
    {
        return "<{$root}>" . self::_data_to_xml($data, $item, $id) . "</{$root}>";
    }

    static private function _data_to_xml($data, $item = 'item', $id = 'id', $content = '')
    {
        foreach ($data as $key => $val) {
            is_numeric($key) && $key = "{$item} {$id}=\"{$key}\"";
            $content .= "<{$key}>";
            if (is_array($val) || is_object($val)) {
                $content .= self::_data_to_xml($val);
            } elseif (is_numeric($val)) {
                $content .= $val;
            } else {
                $content .= '<![CDATA[' . preg_replace("/[\\x00-\\x08\\x0b-\\x0c\\x0e-\\x1f]/", '', $val) . ']]>';
            }
            list($_key,) = explode(' ', $key . ' ');
            $content .= "</$_key>";
        }
        return $content;
    }


    /**
     * 将xml转为array
     * @param string $xml
     * @return array
     */
    static public function xml2arr($xml)
    {
        libxml_disable_entity_loader(true);
        return json_decode(WechatTools::json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    }

    /**
     * 生成安全JSON数据
     * @param array $array
     * @return string
     */
    static public function json_encode($array)
    {
        return preg_replace_callback('/\\\\u([0-9a-f]{4})/i', create_function('$matches', 'return mb_convert_encoding(pack("H*", $matches[1]), "UTF-8", "UCS-2BE");'), json_encode($array));
    }

    /**
     * 以get方式提交请求
     * @param $url
     * @return bool|mixed
     */
    static public function httpGet($url)
    {
        $oCurl = curl_init();
        if (stripos($url, "https://") !== false) {
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1);
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);
        if (intval($aStatus["http_code"]) == 200) {
            return $sContent;
        } else {
            return false;
        }
    }

    /**
     * 以post方式提交请求
     * @param string $url
     * @param array|string $data
     * @return bool|mixed
     */
    static public function httpPost($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        if (is_array($data)) {
            foreach ($data as &$value) {
                if (is_string($value) && stripos($value, '@') === 0 && class_exists('CURLFile', false)) {
                    $value = new CURLFile(realpath(trim($value, '@')));
                }
            }
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $data = curl_exec($ch);
        curl_close($ch);
        if ($data) {
            return $data;
        }
        return false;
    }

    /**
     * 使用证书，以post方式提交xml到对应的接口url
     * @param string $url POST提交的内容
     * @param array $postdata 请求的地址
     * @param string $ssl_cer 证书Cer路径 | 证书内容
     * @param string $ssl_key 证书Key路径 | 证书内容
     * @param int $second 设置请求超时时间
     * @return bool|mixed
     */
    static public function httpsPost($url, $postdata, $ssl_cer = null, $ssl_key = null, $second = 30)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        /* 要求结果为字符串且输出到屏幕上 */
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        /* 设置证书 */
        if (!is_null($ssl_cer) && file_exists($ssl_cer) && is_file($ssl_cer)) {
            curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
            curl_setopt($ch, CURLOPT_SSLCERT, $ssl_cer);
        }
        if (!is_null($ssl_key) && file_exists($ssl_key) && is_file($ssl_key)) {
            curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
            curl_setopt($ch, CURLOPT_SSLKEY, $ssl_key);
        }
        curl_setopt($ch, CURLOPT_POST, true);
        if (is_array($postdata)) {
            foreach ($postdata as &$data) {
                if (is_string($data) && stripos($data, '@') === 0 && class_exists('CURLFile', false)) {
                    $data = new CURLFile(realpath(trim($data, '@')));
                }
            }
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        $result = curl_exec($ch);
        curl_close($ch);
        if ($result) {
            return $result;
        } else {
            return false;
        }
    }

    /**
     * 读取微信客户端IP
     * @return null|string
     */
    static public function getAddress()
    {
        foreach (array('HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'HTTP_X_CLIENT_IP', 'HTTP_X_CLUSTER_CLIENT_IP', 'REMOTE_ADDR') as $header) {
            if (!isset($_SERVER[$header]) || ($spoof = $_SERVER[$header]) === null) {
                continue;
            }
            sscanf($spoof, '%[^,]', $spoof);
            if (!filter_var($spoof, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $spoof = null;
            } else {
                return $spoof;
            }
        }
        return '0.0.0.0';
    }
}
