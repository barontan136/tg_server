<?php
namespace app\api\service;
class SmsService {
    const SMS_TITLE = '';
    const BIND_TPL  = '您的绑定验证码为：$code，请勿泄露给他人';//绑定模板
    const ADMINUSERLOGIN_TPL  = '您的管理员登录验证码为：$code，请勿泄露给他人';//管理员登录模板
    const FORGETPASSWORD_TPL  = '您的忘记密码验证码为：$code，请勿泄露给他人';//忘记密码模板
    
    public static function replaceCode($str,$code){
        return str_replace('$code', $code, $str);
    } 
}
