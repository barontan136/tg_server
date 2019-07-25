<?php

namespace lcsms;

use app\api\model\VerifyCode;
use lcsms\ChuanglanSmsHelper\ChuanglanSmsApi;
use app\api\service\SmsService;

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

    // 保存错误信息
    public $errCode;
    public $errMsg;
    private $clapi;

    public function __construct($templateCode = '', $signName = '') {
        $this->clapi = new ChuanglanSmsApi();
    }

    /**
     * 短信发送
     * @param unknown $telephone            
     * @param unknown $templateParam            
     *
     */
    public  function send_verify_code($telephone, $tpl, $code = '0000') {
        //限制每个ip每日能获取多少次验证码
        $limit = empty(config('ali_limit_num')) ? 10 : config('ali_limit_num');
        $limitResult = (new VerifyCode())->getIpLimit($limit);
        if ($limitResult === true) {
            $this->errMsg('短信发送超过上限！');
            return false;
        }
        return $this->formatResult($this->clapi->sendSMS($telephone, '【' . SmsService::SMS_TITLE . '】' . SmsService::replaceCode($tpl, $code)));
    }

    /**
     * 返回结果处理
     * @param type $result
     * @return boolean
     */
    public function formatResult($result) {
        if (!is_null(json_decode($result))) {
            $output = json_decode($result, true);
            $this->errMsg = $output['errorMsg'];
            $this->errCode = $output['code'];
            if (isset($output['code']) && $output['code'] == '0') {
                return true;
            } else {
                return false;
            }
        } else {
           return false;
        }
    }

}
