<?php

namespace app\common\model;

use think\Model;
use think\Request;

class FileTag extends Model {
     public function getTags($merchant_id = 0) {
        //查询没在tag表中的记录
        $left = $this->alias('ft')
                        ->join('file f', 'f.tag = ft.id', 'right')
                        ->where('f.merchant_id', $merchant_id)
                        ->field('count(f.id) as num,ft.name as tag,ft.id')
                        ->group('f.tag')
                        ->select()->each(function(&$item) {
                    if (empty($item['id'])) {
                        $item['tag'] = "未分组";
                        $item['id'] = -1;
                    }
                })->toArray();
        //查询没在picture中的记录
        $right = $this->alias('ft')
                        ->join('file f', 'f.tag = ft.id', 'left')
                        ->where('ft.merchant_id', $merchant_id)
                        ->field('count(f.id) as num,ft.name as tag,ft.id')
                        ->group('ft.id')
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
}
