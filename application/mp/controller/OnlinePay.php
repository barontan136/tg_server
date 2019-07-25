<?php

namespace app\mp\controller;
use app\common\controller\MpBase;
use app\mp\model\Order;

class OnlinePay extends MpBase{
    
    protected $order_model;
    
    protected function _initialize() {
        parent::_initialize();
        $this->order_model  = new Order();
    }
    /**
     * 列表页
     * @param type $page
     * @return type
     */
    public function index($page=1,$data=[],$is_export = false) {
        empty($data) && $data = $this->request->param();
        foreach ($data as $key => $value) {
            $str = trim($value,' ');
            $data[$key] = $str;
            $this->assign($key,$str);
        }
        $where = [
            'merchant_id'=> $this->merchant_id,
            'from_type'=>['IN',[1,3,4,5,6]],
            'status'=>['IN',[-5,-2,3,4,6,9]],
            'true_money'=>['GT',0]
        ];
        //时间
        if(!empty($data['start_time'])){
            $where['create_time'] = ['GT', strtotime($data['start_time'])];
        }
        if(!empty($data['end_time'])){
            $where['create_time'] = ['LT', strtotime($data['end_time'])+86399];
        }
        if(!empty($data['start_time']) && !empty($data['end_time'])){
            $where['create_time'] = ['BETWEEN',[strtotime($data['start_time']),strtotime($data['end_time'])+86399]];
        }
        $where_string = '';
        if(!empty($data['name'])){//用户姓名、电话
            $where_string .= '((`name` LIKE "%'.$data['name'].'%") OR (`telephone` LIKE "%'.$data['name'].'%"))';
        }
        if(!empty($data['order_id'])){//订单编号
            $where['order_id'] = ['LIKE', "%".$data['order_id']."%"];
        }
        empty($data['status']) && $data['status'] = '';
        switch ($data['status']) {
            case 1://已付款
                $where['status'] = ['IN',[-5,3,4,6]];
                break;
            case 2://已关闭
                $where['status'] = ['IN',[-2,9]];
                break;
            default:
                break;
        }
        if($is_export){
            $export_list = $this->order_model->getPayExportData($where,$where_string);
            return $export_list;
        }else{
            if(empty($data['start_time'])){
                $this->assign('start_time','');
            }
            if(empty($data['end_time'])){
                $this->assign('end_time','');
            }
            $pay_where = $where;
            $pay_where['status'] = ['IN',[3,4,5,6]];
            $this->assign('status',$data['status']);
            $all_money = $this->order_model->where($pay_where)->sum('true_money'); 
            $this->assign('all_money', sprintf('%.2f',$all_money/100));
            $all_use_bonus = $this->order_model->where($pay_where)->sum('use_bonus'); 
            $this->assign('all_use_bonus',$all_use_bonus);
            $all_get_bonus = $this->order_model->where($pay_where)->sum('get_bonus'); 
            $this->assign('all_get_bonus',$all_get_bonus);
            $list = $this->order_model->getPayPage($page, $where,true,$where_string);
            if($this->request->isAjax()){
                return $this->fetch('form_table',['_list' => $list]);
            }
            return $this->fetch('', ['_list' => $list]);
        }
    }
    /**
     * 导出,同index
     */
    public function export(){
        $list = $this->index(1, $this->request->param(), true);
        exprot_csv('线上支付列表'.date('YmdHis'), $list); 
    }
}
