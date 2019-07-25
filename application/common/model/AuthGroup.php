<?php
namespace app\common\model;

use think\Model;

class AuthGroup extends Model
{
  protected $autoWriteTimestamp = false;
      /**
       * 查询指定用户的所属权限
       */
      public function authGroupAccess(){
           return $this->hasMany('AuthGroupAccess','group_id');
      }
      /**
       * 获取角色范围
       */
      public function getAreaTextAttr($value,$data) {
          $area = [
              -1=>'平台',
              1=>'微信商城权限',
              2=>'兑换商城权限',
              3=>'安全权限',
              4=>'其他权限',
          ];
          return $area[$data['area']];
      }
      /**
       * 获取商户端的所有角色
       */
      public function getMpAuthGroup(){
          $list = $this->where('merchant_id',-1)->where('status',1)->select()->toArray();
          $result = [];
          foreach ($list as $vo){
             $result[$this->getAreaTextAttr('', $vo)][] = $vo;
          }
          return $result;
      }
      
      //获取新增保单的分组id
      public function getAddPolicyAuthGroup(){
         return $this->where(['title'=>'新增保单'])->value('id'); 
      }
}