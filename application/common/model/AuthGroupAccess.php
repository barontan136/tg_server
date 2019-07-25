<?php
namespace app\common\model;

use think\Model;

class AuthGroupAccess extends Model
{
   protected $autoWriteTimestamp = false;
   
   public function getAuthGroupAccessByGroupId($where){
     return  $this->where($where)->column('uid'); 
   }
}