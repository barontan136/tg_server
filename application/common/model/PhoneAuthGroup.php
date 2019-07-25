<?php
namespace app\common\model;

use think\Model;

class PhoneAuthGroup extends Model
{
  protected $autoWriteTimestamp = false;
      /**
       * 查询指定用户的所属权限
       */
      public function authGroupAccess(){
           return $this->hasMany('PhoneAuthGroupAccess','group_id');
      }
      /**
       * 获取角色范围
       */
      public function getAreaTextAttr($value,$data) {
          $area = [
              1=>'金价修改权限',
              2=>'图片上传权限',
              3=>'电子保单权限',
              4=>'提货查询权限',
          ];
          return $area[$data['area']];
      }
      /**
       * 获取商户端的所有角色
       */
      public function getMpAuthGroup(){
          $list = $this->where('status',1)->select()->toArray();
          $result = [];
          foreach ($list as $vo){
             $result[$this->getAreaTextAttr('', $vo)][] = $vo;
          }
          return $result;
      }
}