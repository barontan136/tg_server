<?php

namespace app\common\behavior;
use app\utils\service\ConfigService;
class SendError {
    
    public function run(&$params) {
       ConfigService::config();
       //推送错误信息给开发者
       $switch = config('error_send');
       if($switch=="on"){
           if(isset($params['error'])){
               $url = 'utils/send_error/send';
               $sendP = [
                  'error' => $params['error'],
                  'error_url' => \think\Request::instance()->url()
               ];
               sock_post($url,$sendP);
           }
       }
    }

}
