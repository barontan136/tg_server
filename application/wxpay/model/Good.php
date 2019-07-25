<?php
namespace app\wxpay\model;
use think\Model;

/**
 * Description of Good
 *
 * @author ZHXB
 */
class Good extends Model{
    
    /**
     * 获取名称
     */
    public function getValue($where=[],$field='id'){
        $value = $this->where($where)->value($field);
        return $value;
    }
    /**
     * 获取订单数据
     */
    public function getInfo($where=[],$filed=true){
        $info = $this->field($filed)->where($where)->find()->toArray();
        return $info;
    }
}
