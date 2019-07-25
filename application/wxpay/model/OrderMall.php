<?php

namespace app\wxpay\model;
use think\Model;
/**
 * Description of OrderMall
 *
 * @author ZHXB
 */
class OrderMall extends Model{
    /**
     * 获取订单数据
     */
    public function getInfo($where=[],$filed=true){
        $info = $this->field($filed)->where($where)->find()->toArray();
        return $info;
    }
    /**
     * 更新数据
     */
    public function updateInfo($data,$id,$where=[]){
        $data['update_time'] = time();
        $res = $this->allowField(true)->where('id',$id)->where($where)->update($data);
        if($res === false){
            return false;
        }
        return true;
    }
}
