<?php

namespace app\common\model;

use think\Model;
use think\Request;
use think\Image;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class GoldCard extends Model {

    protected $table = 'ls_good_gold_card';
    protected $autoWriteTimestamp = true;
    //自动写入创建时间
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $deleteTime = 'delete_time';
    protected $dateFormat = 'Y/m/d';

    //获取列表信息
    public function getPage($page = 1, $where = [], $pagesize = 15) {
        $info = $this->getInfo(['merchant_id'=>0,'status'=>1]);
        if(empty($info)){
            return [];
        }
        $list = $this->where($where)
                        ->order('merchant_id,create_time DESC')
                        ->paginate($pagesize, false, ['page' => $page])->each(function($item, $key) use ($info) {
                            $sku_info = $info['sku'];
                            if($item['merchant_id'] == 0){
                                $item['merchant_name'] = '平台';
                            }else{
                                $item['merchant_name'] = model('merchant')->where('id', $item['merchant_id'])->value('name');
                                $sku_info = $this->where('merchant_id',$item['merchant_id'])->value('sku');
                            }
            $item['sku'] = json_decode($sku_info, true);
            $item['name'] = $info['name'];
            $sku = '';
            $item['number']=$info['number'];
            $item['weight']=$info['weight'];
            $item['size']  =$info['size'];
            $item['express_price'] = sprintf("%.2f",$info['express_price'] / 100);
            foreach ($item['sku'] as $key => $value) {
                $value['price'] = sprintf("%.2f",$value['price'] / 100);
                $sku .= $value['name'] . ':' . $value['price'] . '<br>';
            }
            $sku = substr($sku, 0, -4);
            $item['sku'] = $sku;
            return $item;
        });
        return $list;
    }

    public function getInfo($condition) {
        return $this->where($condition)->find()->toArray();
    }
    
    /**
     * 初始化金卡配置
     */
    public function initData($merchant_id = 0){
        if(empty($merchant_id)) return FALSE;
        $info = $this->where('merchant_id',$merchant_id)->find()->toArray();
        if(!empty($info)) return true;
        $pt_info = $this->where('merchant_id',0)->where('status',1)->find()->toArray();
        if(empty($pt_info)) return false;
        unset($pt_info['id']);
        unset($pt_info['create_time']);
        unset($pt_info['update_time']);
        unset($pt_info['delete_time']);
        $pt_info['post_type'] = 'to_shop';
        $pt_info['merchant_id'] = $merchant_id;
        $res = $this->allowField(TRUE)->save($pt_info);
        return $res === false?false:true;
    }
}
