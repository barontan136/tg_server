<?php

namespace app\utils\controller;

use app\common\controller\SockBase;
use app\common\model\WechatFansTags;
use app\utils\service\ConfigService;

class SendError extends SockBase {

    public function send() {
//        //查询所有开发者
//        $openids = (new WechatFansTags())->getOpenids('开发者',ConfigService::$APPID);
//        if (!empty($openids)) {
//            $this->request->post(['appid'=>ConfigService::$APPID]);
//            //给每个开发者发送信息
//            $errinfo = '';
//            foreach ($this->params['error'] as $error) {
//                    if(!empty($errinfo)){
//                        $errinfo.='\n';
//                    }else{
//                    $errinfo.=$error;
//                    }
//            }
//            foreach ($openids as $openid) {
//                    $array = [];
//                    $array[] = date('Y-m-d H:i:s');
//                    $array[] = '最高级别';
//                    $first = '错误位置：'.$this->params['error_url'];
//                    $remark = str_replace('\\', '/', $errinfo);
//                    send_templ_msg($openid, ConfigService::$ERRTPLID, $first, $array, $remark);
//            }
//        }
    }
}
