<?php
namespace app\wxpay\model;
use think\Model;

/**
 * Description of GoodGoldCard
 *
 * @author ZHXB
 */
class GoodGoldCard extends Model{
    /**
     * 获取单值
     */
    public function getName(){
        $value = $this->where('merchant_id',0)->where('status',1)->value('name');
        return $value;
    }
    /**
     * 获取信息
     */
    public function getGoldInfo($gold_card_id){
        $info = $this->field('name,sku,number,merchant_id')->where('id',$gold_card_id)->where('status',1)->find()->toArray();
        if(empty($info['sku'])){
            return [];
        }
        $panrent = $this->field('name,number')->where('merchant_id',0)->where('status',1)->find()->toArray();
        $info['name'] = $panrent['name'];
        $info['number'] = $panrent['number'];
        $info['sku'] = json_decode($info['sku'],true);
        return $info;
    }
}
