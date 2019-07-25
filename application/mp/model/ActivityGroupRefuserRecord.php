<?php

namespace app\mp\model;

use think\Model;
use think\Db;

class ActivityGroupRefuserRecord extends Model {


    /**
     * @Notes: 检查是否有权限
     * @param $user_id
     * @author: Baron
     */
    public function checkAllow($act_id, $user_id){
//        $auth_allow = $this->where(['activity_id'=> $act_id, 'user_id' => $user_id, 'status' => 2])->count("id");
        $auth_allow = $this->where(['activity_id'=> $act_id, 'user_id' => $user_id])->count("id");

        return $auth_allow;
    }

    /**
     * @Notes: 获取列表
     * @param $act_id
     * @param int $status
     * @param int $keywords
     * @param int $page
     * @param int $per_page
     * @author: Baron
     */
    public function getRecordList(
        $act_id,
        $status = 0,        // 状态 1-审核中 2-已通过 3-已拒绝 4-已删除
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
     * @Notes: 删除记录
     * @param $record_id
     * @return $this
     * @author: Baron
     */
    public function delRecord($record_id){
        if(!empty($record_id)){
            return $this->where('id', $record_id)->update(['status' => 4]);
        }
    }

    /**
     * @Notes: 更新审核状态
     * @param $record_id
     * @param $status 2-通过 3-拒绝
     * @return $this
     * @author: Baron
     */
    public function updateRecord($record_id, $status){
        if(!empty($record_id)){
            return $this->where('id', $record_id)->update(['status' => $status]);
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
            '1' => '审核中',
            '2' => '已通过',
            '3' => '已拒绝',
            '4' => '已删除',
        ];
        return isset($text[$data['status']]) ? $text[$data['status']] : '-';
    }


    public function getInfos($page) {
        return $this->paginate(8, false, ['page' => $page]);
    }

}
