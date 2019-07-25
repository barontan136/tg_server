<?php
namespace app\wxpay\model;

use think\Model;
use think\Db;

class ActivityTurntablePrizeRelation extends Model
{
    protected $dateFormat = 'Y-m-d H:i:s';
    
    /*
     * 状态转文本 create 2018-05-30
     */
    public function getStatusTextAttr($value,$data){
        
        $text = ['-1' => '停止','1' => '正常','2' => '已启用'];
        return isset($text[$data['status']]) ? $text[$data['status']] : '未知';
    }
    
     /**
     * 获取名称
     */
    public function getValue($where=[],$field='id'){
        $value = $this->where($where)->value($field);
        return $value;
    }
   
}
