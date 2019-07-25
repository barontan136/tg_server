<?php

namespace app\common\model;

use think\Model;
use think\db;

class WechatNewsTag extends Model {

    public function getTags($merchant_id = 0) {
        $list = $this->field('name as tag,id')->where('merchant_id', $merchant_id)->order('create_time desc')->select()->each(function(&$item) use ($merchant_id) {
                    $item['num'] = db('wechat_news')->where('tag_id',$item['id'])->where('merchant_id', $merchant_id)->count();
                })->toArray();
        $list[] = [
            'tag'=>'未分组','id'=>-1,
            'num'=>db('wechat_news')->where('tag_id',-1)->where('merchant_id', $merchant_id)->count()
        ];
        return $list;
    }
    //根据名获取分组id
    public function getTagId($name,$merchant_id){
        if(empty($name) || $name == "未分组"){
            return -1;
        }
        $id = $this->where('merchant_id',$merchant_id)->where('name',$name)->value('id');
        if(empty($id)){
            $this->save([
                'name'=>$name,
                'merchant_id'=>$merchant_id
            ]);
            $id = $this->id;
        }
        return $id;
    }

}
