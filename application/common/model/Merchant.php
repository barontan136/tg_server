<?php
namespace app\common\model;
use think\Model;

class Merchant extends Model
{
    
    
    public function getUpdateTimeTextAttr($value,$data){
        return date('Y-m-d H:i:s',$data['update_time']);
    }
    
    public function getCreateTimeTextAttr($value,$data){
        return date('Y-m-d H:i:s',$data['create_time']);
    }
    public function getAddressTextAttr($value,$data){
        return $data['province'].$data['city'].$data['district'].$data['town'].$data['address'];
    }
}