<?php

namespace app\mp\model;

use think\Model;
use think\Db;

class ActivityGroupRpRecord extends Model {

    /**
     * @Notes: 获取某个团购活动的红包列表
     * @param $act_id
     * @param int $status
     * @param int $keywords
     * @param int $page
     * @param int $per_page
     * @author: Baron
     */
    public function getRecordList(
        $act_id,
        $status = 0,        // 状态 1-未支付 2-已支付 4-已删除
        $keywords = '',
        $page = 1,
        $per_page = 10){

        if (empty($act_id)){
            return [];
        }

        $where['activity_id'] = $act_id;
        $where['status'] = ['neq', 4];
        if (!empty($status)){
            $where['status'] = $status;
        }
        if (!empty($keywords)){
            $where['nick_name'] = ['like', '%'.$keywords.'%'];
        }
        return $this->where($where)
            ->order('create_datetime desc')
            ->paginate($per_page, false, ['page' => $page])->each(function($item,$key){

                $item['status_name'] = $this->getStatusTextAttr('', $item);
                return $item;
            });
    }

    /**
     * @Notes: 删除红包记录
     * @param $record_id
     * @return $this
     * @author: Baron
     */
    public function delRecord($record_id){
        if(!empty($record_id)){
            return $this->where('id', $record_id)->update(['status' => 4]);
        }
    }

    /*
     * 状态转文本
     */
    public function getOrderTypeTextAttr($value, $data) {

        $text = [
            '1' => '团长',
            '2' => '团员',
        ];
        return isset($text[$data['order_type']]) ? $text[$data['order_type']] : '-';
    }
    public function getStatusTextAttr($value, $data) {

        $text = [
            '1' => '未发放',
            '2' => '已发放',
            '3' => '已核销',
            '4' => '已删除',
        ];
        return isset($text[$data['status']]) ? $text[$data['status']] : '-';
    }


    public function getInfos($page) {
        return $this->paginate(8, false, ['page' => $page]);
    }

}
