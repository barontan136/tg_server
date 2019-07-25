<?php

namespace app\wechat\controller;

use think\Controller;
use app\utils\service\ConfigService;
use app\mp\model\Merchant;
use app\common\model\AdminUserWechat;
use app\common\model\AdminUser;

/**
 * 微信交互控制器，中控服务器
 * 主要获取和反馈微信平台的数据，分析用户交互和系统消息分发。
 */
class Qrcode extends Controller {
    public $merchant_model;
    public $admin_user_wechat_model;
    public $admin_user_model;
    

    public function _initialize() {
        ConfigService::config();
        $this->merchant_model = new Merchant();
        $this->admin_user_wechat_model = new AdminUserWechat();
        $this->admin_user_model = new AdminUser();
    }

    /*     * **************************************************************************************************************************************************** */
    /*     * *************************                             授权事件接收                                                  ********************************* */
    /*     * **************************************************************************************************************************************************** */

    public function bindAdmin($merchant_id) {
        if ($this->request->isPost()) {
            $wxUser = $this->getWxUser();
            if(!$wxUser){
                return ajaxMsg('异常请刷新重试！');
            }
            $data = [
              'openid'=>$wxUser['openid'],
              'nickname'=>$wxUser['nickname'],
              'headimgurl'=>$wxUser['headimgurl'],
              'merchant_id'=>  $this->request->param('merchant_id')  
            ];
            $validate_result = $this->validate($data, 'AdminUserWechat');
            if ($validate_result !== true) {
                return ajaxMsg($validate_result);
            }
            if($this->admin_user_wechat_model->save($data)){
                return ajaxMsg('绑定成功！',1);
            }
            return ajaxMsg($this->admin_user_wechat_model->getError());
        } else {
            $wxUser = $this->getWxUser();
            if ($wxUser !== false) {
                $merchant = $this->merchant_model->getInfo($merchant_id);
                empty($merchant)&&alert('请勿异常操作');
                return $this->fetch('',['wxUser'=>$wxUser,'merchant'=>$merchant]);
            } else {
                alert('授权失败，请稍后重试！');
            }
        }
    }
    
    //扫码登录
    public function loginScan($scan_id = 0){
            $wxUser = $this->getWxUser();
            if($this->request->isPost()){
                $scan_record = cache('admin_user_qrcode_recode_'.$scan_id);
                $type = $this->request->param('type');
                $scan_record['status'] = $type;
                $scan_record['openid'] = $wxUser['openid'];
                cache('admin_user_qrcode_recode_'.$scan_id,$scan_record);
                return ajaxMsg($type==4?'登录成功！':'您已取消登录！',1);
            }else{
            $scan_record = cache('admin_user_qrcode_recode_'.$scan_id);
            if($scan_record['status']!==0){
                alert('此二维码已过期！');
            }
            if($scan_record){
                $merchant_id = $this->admin_user_model->where('openid',$wxUser['openid'])->value('merchant_id');
                if(empty($merchant_id)){
                    $scan_record['status'] = 2;
                    cache('admin_user_qrcode_recode_'.$scan_id,$scan_record);
                    alert('您未绑定微信管理员！');
                }else{
                $scan_record['status'] = 1;
                cache('admin_user_qrcode_recode_'.$scan_id,$scan_record);
                //获取绑定的用户
                $merchant = $this->merchant_model->getInfo($merchant_id);
                $this->assign('scan_record',$scan_record);
                $this->assign('merchant',$merchant);
                $this->assign('wxUser',$wxUser);
                }
            }else{
                alert('此二维码已过期！');
            }
            return $this->fetch();
          }
    }
    
    //获取用户信息
    public function getWxUser() {
        if (isWeixinBrowser()) {
            $wxuser = session('pt_wxuser');
            if (!empty($wxuser)) {
                return $wxuser;
            } else {
                $wxuser = getWeObject(ConfigService::$APPID)->wxOAuth2Component(ConfigService::$APPID, 'snsapi_userinfo');
                if ($wxuser !== FALSE) {
                    session('pt_wxuser', $wxuser);
                    return $wxuser;
                } else {
                    return false;
                }
            }
        } else {
            alert('请在微信端打开！');
        }
    }

}
