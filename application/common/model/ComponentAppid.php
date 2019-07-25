<?php

namespace app\common\model;

use think\Model;
use think\Request;

class ComponentAppid extends Model {

    public function getUpdateTimeTextAttr($value, $data) {
        return date('Y-m-d H:i:s', $data['update_time']);
    }

    public function getCreateTimeTextAttr($value, $data) {
        return date('Y-m-d H:i:s', $data['create_time']);
    }

    //获取appid
    public function getAppidByMerchantId($merchant_id) {
        return $this->where('merchant_id', $merchant_id)->value('authorizer_appid');
    }

    //获取merchant_id
    public function getMerchantIdByUserName($user_name) {
        return $this->where('user_name', $user_name)->value('merchant_id');
    }

    //获取merchant_id
    public function getMerchantIdByAppid($appid) {
        return $this->where('authorizer_appid', $appid)->value('merchant_id');
    }

    //授权成功保存公众号信息
    public function saveInfo($code, $merchant_id) {
        $weObj = getCompWeObject();
        $component_appid = $weObj->api_query_auth($code);
        if ($component_appid) {
            $component_appid = $component_appid['authorization_info'];
            //下载二维码图片
            $map['authorizer_appid'] = $component_appid['authorizer_appid'];
            $component = $this->where($map)->find()->toArray();
            $time = time();
            if ($component) {
                $component['merchant_id'] = $merchant_id;
                $component['authorizer_appid'] = $component_appid['authorizer_appid'];
                $component['authorizer_access_token'] = $component_appid['authorizer_access_token'];
                $component['expires_in'] = $time;
                $component['status'] = 1;
                $component['authorizer_refresh_token'] = $component_appid['authorizer_refresh_token'];
                $component['func_info'] = json_encode($component_appid['func_info'], JSON_UNESCAPED_UNICODE);
                $this->save($component, ['id' => $component['id']]);
                $component = $this->api_get_authorizer_info($component, $weObj);
                if (!empty($component['merchant_id']) && $merchant_id != $component['merchant_id']) {
                    return 'isauth';
                }
            } else {
                $component['authorizer_appid'] = $component_appid['authorizer_appid'];
                $component['authorizer_access_token'] = $component_appid['authorizer_access_token'];
                $component['expires_in'] = $time;
                $component['merchant_id'] = $merchant_id;
                $component['authorizer_refresh_token'] = $component_appid['authorizer_refresh_token'];
                $component['func_info'] = json_encode($component_appid['func_info'], JSON_UNESCAPED_UNICODE);
                $component['status'] = 1;
                $this->save($component);
                $component['id'] = $this->id;
                $component = $this->api_get_authorizer_info($component, $weObj);
            }
            return $component;
        }
        return false;
    }

    /**
     * 获取授权公众号的信息
     */
    public function api_get_authorizer_info($component, $weObj) {
        $result = $weObj->api_get_authorizer_info($component['authorizer_appid']);
        if ($result) {
            if(!isset($result['authorizer_info']['head_img'])){
                $this->error('请先上传微信公众号头像');
            }
            if(!isset($result['authorizer_info']['nick_name']) || empty($result['authorizer_info']['nick_name'])){
                $this->error('请先设置微信公众号昵称');
            }
            $component['qrcode_local_url'] = $this->getQrcode($component['authorizer_appid'],$result['authorizer_info']['qrcode_url']);
            $component['nick_name'] = $result['authorizer_info']['nick_name'];
            $component['head_img'] = $result['authorizer_info']['head_img'];
            $component['user_name'] = $result['authorizer_info']['user_name'];
            $component['alias'] = $result['authorizer_info']['alias'];
            $component['qrcode_url'] = $result['authorizer_info']['qrcode_url'];
            $component['idc'] = $result['authorizer_info']['idc'];
            $component['service_type_info'] = json_encode($result['authorizer_info']['service_type_info']);
            $component['business_info'] = json_encode($result['authorizer_info']['business_info']);
            $component['verify_type_info'] = json_encode($result['authorizer_info']['verify_type_info']);
            $component['func_info'] = json_encode($result['authorization_info']['func_info']);
            $component['update_time'] = time();
            $res = $this->allowField(true)->save($component, ['id' => $component['id']]);
            return $component;
        }
    }

    /**
     * 根据appid获取公众号信息
     */
    public function getInfoByAppid($appid) {
        return $this->where('id', $appid)->find()->toArray();
    }
    /**
     * 根据merchant_id获取公众号信息
     */
    public function getInfoByMerchantId($merchant_id) {
        return $this->where('merchant_id', $merchant_id)->find()->toArray();
    }
    /**
     * 根据merchant_id获取公众号信息
     */
    public function getComponentNameByMerchantId($merchant_id) {
        $nick_name=$this->where('merchant_id', $merchant_id)->value('nick_name');
        if($nick_name){
             return $nick_name;
        }else{
           return'--'; 
        }
    }

    /**
     * 下载公众号二维码图片
     */
    public function getQrcode($appid = '',$url) {
        $path = "./uploads/wechatqrcodeimg/";
        if (!file_exists($path)) {
            @mkdir($path, 0777, true);
        }
        if(file_exists($path . $appid.".png")){
            unlink($path . $appid.".png");
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        $file = curl_exec($ch);
        curl_close($ch);
        $resource = fopen($path . $appid.".png", 'a');
        fwrite($resource, $file);
        fclose($resource);
        $path = "/uploads/wechatqrcodeimg/";
        return $path . $appid.".png";
    }

}
