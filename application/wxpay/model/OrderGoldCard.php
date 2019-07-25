<?php

namespace app\wxpay\model;
use think\Model;
/**
 * Description of OrderMall
 *
 * @author ZHXB
 */
class OrderGoldCard extends Model{
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
        $res = $this->allowField(true)->where($where)->where('id',$id)->update($data);
        if($res === false){
            return false;
        }
        return true;
    }
}
