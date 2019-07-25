<?php

namespace app\common\model;

use think\Model;
use think\Request;
use think\Image;

class PictureTag extends Model {

    public function getTags($merchant_id = 0) {
        //查询没在tag表中的记录
        $left = $this->alias('pt')
                        ->join('picture p', 'p.tag = pt.id', 'right')
                        ->where('p.merchant_id', $merchant_id)
                        ->field('count(p.id) as num,pt.name as tag,pt.id')
                        ->group('p.tag')
                        ->select()->each(function(&$item) {
                    if (empty($item['id'])) {
                        $item['tag'] = "未分组";
                        $item['id'] = -1;
                    }
                })->toArray();
        //查询没在picture中的记录
        $right = $this->alias('pt')
                        ->join('picture p', 'p.tag = pt.id', 'left')
                        ->where('pt.merchant_id', $merchant_id)
                        ->field('count(p.id) as num,pt.name as tag,pt.id')
                        ->group('pt.id')
                        ->select()->toArray();

        foreach ($left as $l) {
            if (!current(array_filter($right, function($v) use ($l) {
                                return $v['id'] == $l['id'];
                            }))) {
                $right[] = $l;
            };
        }
        return $right;
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
