<?php
namespace wechat;
/**
 * Prpcrypt class
 *
 * 提供接收和推送给公众平台消息的加解密接口.
 */
class Prpcrypt {

    public $key;

    function __construct($k) {
        $this->key = base64_decode($k . "=");
    }

    /**
     * 兼容老版本php构造函数，不能在 __construct() 方法前边，否则报错
     */
    function Prpcrypt($k) {
        $this->key = base64_decode($k . "=");
    }

    /**
     * 对明文进行加密
     * @param string $text 需要加密的明文
     * @return string 加密后的密文
     */
    public function encrypt($text, $appid) {
        try {
            //获得16位随机字符串，填充到明文之前
            $random = $this->getRandomStr(); //"aaaabbbbccccdddd";
            $text = $random . pack("N", strlen($text)) . $text . $appid;
            $iv = substr($this->key, 0, 16);
            $pkc_encoder = new PKCS7Encoder;
            $text = $pkc_encoder->encode($text);
            $encrypted = openssl_encrypt($text, 'AES-256-CBC', substr($this->key, 0, 32), OPENSSL_ZERO_PADDING, $iv);
            return array(ErrorCode::$OK, $encrypted);
        } catch (Exception $e) {
            //print $e;
            return array(ErrorCode::$EncryptAESError, null);
        }
    }

    /**
     * 对密文进行解密
     * @param string $encrypted 需要解密的密文
     * @return string 解密得到的明文
     */
    public function decrypt($encrypted, $appid) {
        try {
            $iv = substr($this->key, 0, 16);
            $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', substr($this->key, 0, 32), OPENSSL_ZERO_PADDING, $iv);
        } catch (Exception $e) {
            return array(ErrorCode::$DecryptAESError, null);
        }
        try {
            //去除补位字符
            $pkc_encoder = new PKCS7Encoder;
            $result = $pkc_encoder->decode($decrypted);
            //去除16位随机字符串,网络字节序和AppId
            if (strlen($result) < 16)
                return "";
            $content = substr($result, 16, strlen($result));
            $len_list = unpack("N", substr($content, 0, 4));
            $xml_len = $len_list[1];
            $xml_content = substr($content, 4, $xml_len);
            $from_appid = substr($content, $xml_len + 4);
            if (!$appid)
                $appid = $from_appid;
            //如果传入的appid是空的，则认为是订阅号，使用数据中提取出来的appid
        } catch (Exception $e) {
            //print $e;
            return array(ErrorCode::$IllegalBuffer, null);
        }
        if ($from_appid != $appid)
            return array(ErrorCode::$ValidateAppidError, null);
        //不注释上边两行，避免传入appid是错误的情况
        return array(0, $xml_content, $from_appid);
        //增加appid，为了解决后面加密回复消息的时候没有appid的订阅号会无法回复
    }
    
    /**
     * 随机生成16位字符串
     * @return string 生成的字符串
     */
    function getRandomStr() {

        $str = "";
        $str_pol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $max = strlen($str_pol) - 1;
        for ($i = 0; $i < 16; $i++) {
            $str .= $str_pol[mt_rand(0, $max)];
        }
        return $str;
    }

}

/**
 * PKCS7Encoder class
 *
 * 提供基于PKCS7算法的加解密接口.
 */
class PKCS7Encoder {

    public static $block_size = 32;

    /**
     * 对需要加密的明文进行填充补位
     * @param $text 需要进行填充补位操作的明文
     * @return 补齐明文字符串
     */
    function encode($text) {
        $block_size = PKCS7Encoder::$block_size;
        $text_length = strlen($text);
        //计算需要填充的位数
        $amount_to_pad = PKCS7Encoder::$block_size - ($text_length % PKCS7Encoder::$block_size);
        if ($amount_to_pad == 0) {
            $amount_to_pad = PKCS7Encoder::block_size;
        }
        //获得补位所用的字符
        $pad_chr = chr($amount_to_pad);
        $tmp = "";
        for ($index = 0; $index < $amount_to_pad; $index++) {
            $tmp .= $pad_chr;
        }
        return $text . $tmp;
    }

    /**
     * 对解密后的明文进行补位删除
     * @param decrypted 解密后的明文
     * @return 删除填充补位后的明文
     */
    function decode($text) {

        $pad = ord(substr($text, -1));
        if ($pad < 1 || $pad > PKCS7Encoder::$block_size) {
            $pad = 0;
        }
        return substr($text, 0, (strlen($text) - $pad));
    }

}

/**
 * error code
 * 仅用作类内部使用，不用于官方API接口的errCode码
 */
class ErrorCode {

    public static $OK = 0;
    public static $ValidateSignatureError = 40001;
    public static $ParseXmlError = 40002;
    public static $ComputeSignatureError = 40003;
    public static $IllegalAesKey = 40004;
    public static $ValidateAppidError = 40005;
    public static $EncryptAESError = 40006;
    public static $DecryptAESError = 40007;
    public static $IllegalBuffer = 40008;
    public static $EncodeBase64Error = 40009;
    public static $DecodeBase64Error = 40010;
    public static $GenReturnXmlError = 40011;
    public static $errCode = array(
        '0' => '处理成功',
        '40001' => '校验签名失败',
        '40002' => '解析xml失败',
        '40003' => '计算签名失败',
        '40004' => '不合法的AESKey',
        '40005' => '校验AppID失败',
        '40006' => 'AES加密失败',
        '40007' => 'AES解密失败',
        '40008' => '公众平台发送的xml不合法',
        '40009' => 'Base64编码失败',
        '40010' => 'Base64解码失败',
        '40011' => '公众帐号生成回包xml失败'
    );

    public static function getErrText($err) {
        if (isset(self::$errCode[$err])) {
            return self::$errCode[$err];
        } else {
            return false;
        };
    }

}
