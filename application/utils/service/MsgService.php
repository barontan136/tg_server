<?php
namespace app\utils\service;
/**
 * 信息类服务
 * Class Upload
 * @package app\api\controller
 */
class MsgService
{    
    /**
     * 推送报警信息给开发者
     * 
     */
    public static function sendError($err=[]){
         if(!empty($err)){
               $err = is_array($err)?$err:[$err];
               $params['error'] = $err;
               $url = 'utils/send_error/send';
               $params['error_url'] = \think\Request::instance()->url();
               sock_post($url,$params);
         }
    }
    
}