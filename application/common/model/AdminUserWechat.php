<?php
namespace app\common\model;

use think\Model;
use think\Config;
/**
 * 管理员模型
 * Class AdminUser
 * @package app\common\model
 */
class AdminUserWechat extends Model
{
    protected $insert = ['create_time'];
    
    public function getCreateTimeAttr($value,$data){
        return date('Y-m-d H:i:s',$data['create_time']);
    }
    public function getUsernameAttr($value){
        return empty($value)?'未绑定':$value;
    }
    
    public function getAllWechatUser($merchant_id){
        return $this->alias('w')
              ->join('admin_user u','u.openid = w.openid','left')
              ->where('w.merchant_id',$merchant_id)
              ->field('w.id,w.openid,w.nickname,w.headimgurl,w.create_time,u.username')
              ->select()->toArray();
    }
}