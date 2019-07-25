<?php
namespace app\common\model;

use think\Model;
use think\Config;
/**
 * 管理员模型
 * Class AdminUser
 * @package app\common\model
 */
class AdminUser extends Model
{
    protected $insert = ['create_time'];
    
    
    public function getSexTextAttr($value,$data){
        $sex = [1=>'男',2=>'女',3=>'保密'];
        return $sex[$data['sex']];
    }
    
    public function setLockPasswordAttr($value){
        return empty($value)?$value:md5($value . Config::get('salt'));
    }
    
    public function setPasswordAttr($value){
        return empty($value)?$value:md5($value . Config::get('salt'));
    }
    
    /**
     * 获取最后登录时间 create 06-28
     * @param type $value
     * @param type $data
     */
    public function getLastLoginDateAttr($value, $data) {
        $last_login_time = $data['last_login_time'];
        if (empty($last_login_time)) {
            return '-';
        }
        return date('Y-m-d H:i', $last_login_time);
    }

    /**
     * 获取登录IP create 06-28
     * @param type $value
     * @param type $data
     */
    public function getLastLoginIpTextAttr($value, $data) {
        $last_login_ip = $data['last_login_ip'];
        if (empty($last_login_ip)) {
            return '-';
        }
        return $last_login_ip;
    }
}