<?php
namespace sms;
use app\api\model\VerifyCode;
use CURLFile;

/**
 * 阿里云短信接口
 * 使用说明
 * use sms\Sms;//引入sdk
 * 
 * $sms = new Sms();
 * $mobile = '15808866631,15198897320,.....';//单个或多个电话号码，使用，号分割多个号码
 * $templateParam = ['code'=>6526];//短信模板需要传入的值（此模板验证码有效期5分钟，请在代码逻辑里面控制好时间）
 * $result = $sms->send_verify($mbile,$templateParam);
 * if($result===false){
 *     dump($sms->errMsg);
 * }else{
 *   dump($result)
 * }
 * 
 */
class Sms {

    const DOMAIN = "http://dysmsapi.aliyuncs.com/?";

    // 保存错误信息
    public  $errCode;
    public  $errMsg;
    // Access Key ID
    private $accessKeyId = '';
    // Access Access Key Secret
    private $accessKeySecret = '';
    // 签名
    private $signName = '';
    // 模版ID
    private $templateCode = '';
    

    public function __construct($templateCode='',$signName='') {
        // 配置参数
        $this->accessKeyId = config('accessKeyId');
        $this->accessKeySecret = config('accessKeySecret');
        $this->signName = empty($signName)?config('ali_sign'):$signName;
        $this->templateCode = $templateCode;
    }

    /**
     * 短信发送
     * @param unknown $telephone            
     * @param unknown $templateParam            
     *
     */
    public function send_verify($telephone,$templateParam) {
        //限制每个ip每日能获取多少次验证码
        $limit = empty(config('ali_limit_num'))?10:config('ali_limit_num');
        $limitResult = (new VerifyCode())->getIpLimit($limit);
        if($limitResult===true){
            $this->errMsg('短信发送超过上限！');
            return false;
        }
        $params = [   //此处作了修改
            'SignName' => $this->signName,
            'Format' => 'JSON',
            'Version' => '2017-05-25',
            'AccessKeyId' => $this->accessKeyId,
            'SignatureVersion' => '1.0',
            'SignatureMethod' => 'HMAC-SHA1',
            'SignatureNonce' => uniqid(),
            'Timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'Action' => 'SendSms',
            'TemplateCode' => $this->templateCode,
            'PhoneNumbers' => $telephone,
            'TemplateParam' => json_encode($templateParam) //短信模板
        ];
        $params ['Signature'] = SmsTools::computeSignature($params, $this->accessKeySecret);
        return $this->formatResult(SmsTools::httpGet(self::DOMAIN . http_build_query($params)));
    }
    
    /**
     * 返回结果处理
     * @param type $result
     * @return boolean
     */
    public  function formatResult($result) {
        if (empty($result))
            return false;
        $json = json_decode($result, true);
        if (isset($json['Code'])) {
            if ($json['Code'] != "OK") {
               $errMsg = SmsTools::getErrorMessage($json['Code']);
               $this->errCode = $json['Code'];
               $this->errMsg = $errMsg===FALSE?$json['Message']:$errMsg;
                return false;
            }
            return $json;
        }
        return false;
    }
   
}

class SmsTools{
     private static function percentEncode($string) {
        $string = urlencode($string);
        $string = preg_replace('/\+/', '%20', $string);
        $string = preg_replace('/\*/', '%2A', $string);
        $string = preg_replace('/%7E/', '~', $string);
        return $string;
    }

    /**
     * 签名
     *
     * @param unknown $parameters            
     * @param unknown $accessKeySecret            
     * @return string
     */
    public static function computeSignature($parameters, $accessKeySecret) {
        ksort($parameters);
        $canonicalizedQueryString = '';
        foreach ($parameters as $key => $value) {
            $canonicalizedQueryString .= '&' . self::percentEncode($key) . '=' . self::percentEncode($value);
        }
        $stringToSign = 'GET&%2F&' . self::percentencode(substr($canonicalizedQueryString, 1));
        $signature = base64_encode(hash_hmac('sha1', $stringToSign, $accessKeySecret . '&', true));
        return $signature;
    }

    /**
     * 以get方式提交请求
     * @param $url
     * @return bool|mixed
     */
    static public function httpGet($url) {
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
        return $sContent;
    }

    /**
     * 以post方式提交请求
     * @param string $url
     * @param array|string $data
     * @return bool|mixed
     */
    static public function httpPost($url, $data) {
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
    
      public static function getErrorMessage($status) {
        // 阿里云的短信 乱八七糟的(其实是用的阿里大于)
        // https://api.alidayu.com/doc2/apiDetail?spm=a3142.7629140.1.19.SmdYoA&apiId=25450
        $message = array (
                'InvalidDayuStatus.Malformed' => '账户短信开通状态不正确',
                'InvalidSignName.Malformed' => '短信签名不正确或签名状态不正确',
                'InvalidTemplateCode.MalFormed' => '短信模板Code不正确或者模板状态不正确',
                'InvalidRecNum.Malformed' => '目标手机号不正确，单次发送数量不能超过100',
                'InvalidParamString.MalFormed' => '短信模板中变量不是json格式',
                'InvalidParamStringTemplate.Malformed' => '短信模板中变量与模板内容不匹配',
                'InvalidSendSms' => '触发业务流控',
                'InvalidDayu.Malformed' => '变量不能是url，可以将变量固化在模板中' 
        );
        if (isset ( $message [$status] )) {
            return $message [$status];
        }
        return FALSE;
    }
}
