<?php
namespace app\mp\model;
use think\Model;
use app\mp\model\MerchantCater;
use app\mp\model\MerchantHotel;
use app\mp\model\MerchantMall;
use app\mp\model\MerchantScenicSpot;

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
    public function getStatusTextAttr($value,$data){
        $status = [1=>'正常',2=>'未审核',3=>'未通过',-1=>'已禁用'];
        return $status[$data['status']];
    }
    
    public function getCertificateTextAttr($value,$data){
        return getCover($data['certificate']);
    }
    public function getThumbTextAttr($value,$data){
        return getCover($data['thumb']);
    }
    public function getXaddress($value,$data){
        $xaddress[] = $data['province'];
        $xaddress[] = $data['city'];
        $xaddress[] = $data['district'];
        $xaddress[] = $data['town'];
        return implode('/', array_filter($xaddress));
    }
    //初始化商户基本表
    public function initMerchant($data){
        $data['status'] = 1;
        $result = $this->allowField(true)->save($data);
        if($result){
            return $this->id;
        }else{
            return FALSE;
        }
    }
    //编辑商户基本表
    public function updateMerchant($data,$id){
        $data['status'] = 1;
        $result = $this->allowField(true)->save($data,['id'=>$id]);
        if($result){
            return true;
        }else{
            return FALSE;
        }
    }
    //编辑商户基本表
    public function updateInfo($data,$id){
        $data['update_time'] = time();
        $result = $this->allowField(true)->where('id',$id)->update($data);
        return $result== FALSE?false:true;
    }
    
    //禁用商户
    public function delMerchant($id){

       $this->save(['status'=>-1],['id'=>$id]);
       return true;
    }
    /**
     * 获取单条记录
     * @param type $id ID编号
     * @return type 返回数据
     */
    public function getInfo($id){
        return $this->where('id',$id)->find()->toArray();
    }
    /**
     * 获取所有可用商户
     */
    public function getMerchantList(){
        $list = $this->field('id,name')->where('status',1)->select()->toArray();
        return $list;
    }
}