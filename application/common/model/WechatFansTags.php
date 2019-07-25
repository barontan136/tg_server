<?php
namespace app\common\model;

use think\Model;
use app\common\model\ComponentAppid;

class WechatFansTags extends Model
{
     public function getUpdateTimeTextAttr($value,$data){
        return date('Y-m-d H:i:s',$data['update_time']);
    }
    
    public function getCreateTimeTextAttr($value,$data){
        return date('Y-m-d H:i:s',$data['create_time']);
    }
    
    //获取指定分组下的openid
    public function getOpenids($tagName,$appid){
        $merchant_id = cache('merchant_id'.$appid);
        if(empty($merchant_id)){
            $merchant_id = (new ComponentAppid())->where('authorizer_appid',$appid)->value('merchant_id');
            cache('merchant_id'.$appid,$merchant_id);
        }
        $openids = $this
                ->alias('t')
                ->join('user u','find_in_set(t.id,u.tagid_list)')
                ->where('t.name',$tagName)
                ->where('t.merchant_id',$merchant_id)
                ->column('u.openid');
        return $openids;
    }
    
    /**
     * 
     * @param type $id
     * @param type $merchant_id
     * @param type $field
     * @return type
     */
    public function getFansValue($id,$merchant_id,$field,$where = []){
        $map = [
            'id' =>$id,
            'merchant_id' => $merchant_id,
        ];
        return $this->where($map)->where($where)->value($field);
    }
}