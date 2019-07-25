<?php

namespace app\mp\model;

use think\Model;
use think\Db;

class ActivityGroupOrder extends Model {

    protected $dateFormat = 'Y-m-d H:i:s';


    /**
     * @Notes: 获取某个团购活动的订单列表
     * @param $act_id
     * @param int $order_type
     * @param int $status
     * @param int $keywords
     * @param int $page_num
     * @param int $per_page
     * @author: Baron
     */
    public function getOrderList(
        $act_id,
        $order_type = 0,    // 1-团长 2-团员
        $status = 0,        // 状态 1-未支付 2-已支付 3-已核销 4-已删除
        $keywords = '',
        $page = 1,
        $per_page = 5,
            $act_type=1
            ){

        if (empty($act_id)){
            return [];
        }

        $where['activity_id'] = $act_id;
        $where['status'] = ['not in', 4];
        if (!empty($order_type)){
            $where['order_type'] = $order_type;
        }
        if (!empty($status)){
            $where['status'] = $status;
        }
        if (!empty($keywords)){
            $where['nick_name'] = ['like', '%'.$keywords.'%'];
        }
        if (!empty($act_type)){
            $where['act_type'] = $act_type;
        }
        return $this->where($where)
            ->order('create_datetime desc')
            ->paginate($per_page, false, ['page' => $page])->each(function($item,$key){

                $item['status_name'] = $this->getStatusTextAttr('', $item);
                $item['order_type_name'] = $this->getOrderTypeTextAttr('', $item);
                $item['act_name']=$item['act_name'];
                return $item;
            });
    }
        
    public function getActNameAttr($data,$value)
    {
        $act_name=[1=>'团购',2=>'单独购买',3=>'兼职'];
        return $act_name[$value['act_type']]??'团购';
    }

    /**
     * @Notes: 删除订单
     * @param $order_id
     * @return $this
     * @author: Baron
     */
    public function delOrder($order_id){
        return $this->where('id', $order_id)->update(['status' => 4]);
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
            '1' => '未支付',
            '2' => '已支付',
            '3' => '已核销',
            '4' => '已删除',
        ];
        return isset($text[$data['status']]) ? $text[$data['status']] : '-';
    }


    public function getInfos($page) {
        return $this->paginate(8, false, ['page' => $page]);
    }

}
