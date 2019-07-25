<?php

namespace app\mp\controller;

use app\common\controller\MpBase;
use app\mp\model\ActivityBase;
use app\mp\model\ActivityGroupOrder;
use app\mp\model\ActivityGroupRefuserRecord;
use app\mp\model\ActivityGroupRpRecord;
use app\mp\model\ActivityGroupVisitRecord;
use php_excel\csvexport;
use app\api\model\User;
use think\Db;

/**
 * 活动数据
 * Class ActivityData
 * @package app\mp\controller
 */
class ActivityData extends MpBase {

    protected $act_group_order_model;

    public function _initialize() {
        parent::_initialize();
        $this->act_group_order_model = new ActivityGroupOrder();
    }

    //活动管理
    public function index() {

        return $this->orderList();
//        return $this->rpList();
//        return $this->visitList();
    }

    /**
     * @Notes: 获取订单列表
     * @return mixed|string
     * @author: Baron
     */
    public function orderList() {

        $input_data = $this->request->param();
        $page = $input_data['page'] ?? 1;
        $act_id = $input_data['act_id'] ?? 1;
        $order_type = $input_data['order_type'] ?? 0;
        $status = $input_data['status'] ?? 0;
        $act_type = $input_data['act_type'] ?? '';
        $keywords = $input_data['keywords'] ?? '';
        $act_group_order_model = new ActivityGroupOrder();
        $list = $act_group_order_model->getOrderList($act_id, $order_type, $status, $keywords, $page,5,$act_type);
        $this->assign('list', $list);
        if (request()->isAjax()) {
            return $this->fetch('orderlstajax');
        } else {
//            return $this->view->fetch();
            $input_data = [
                'act_id' => $act_id,
                'order_type' => $order_type,
                'status' => $status,
                'keywords' => $keywords,
                'act_type' => $act_type,
            ];
            $_SESSION['act_id'] = $act_id;
            $this->assign('input_data', $input_data);
            return $this->view->fetch('orderlist');
        }
    }

    /**
     * @Notes: 删除订单
     * @author: Baron
     */
    public function deleteOrder() {

        if (request()->isAjax()) {
            $input_data = $this->request->param();
            $order_id = $input_data['id'] ?? 1;

            $act_group_order_model = new ActivityGroupOrder();
            $ret = $act_group_order_model->delOrder($order_id);

            if ($ret > 0) {
                $this->success('删除成功！');
            }
            $this->error('删除失败！');
        }
    }

    /**
     * @Notes: 导出订单数据
     * @author: Baron
     */
    public function exportOrder() {
        $input_data = $this->request->param();
        $page = $input_data['page'] ?? 1;
        $act_id = $input_data['act_id'] ?? 1;
        $order_type = $input_data['order_type'] ?? 0;
        $status = $input_data['status'] ?? 0;
        $keywords = $input_data['keywords'] ?? '';

        $act_group_order_model = new ActivityGroupOrder();
        $list = $act_group_order_model->getOrderList($act_id, $order_type, $status, $keywords, 1, 10000);
        $csvctrl = new csvexport();
        $csvctrl->putCsvOne($list, date('Y-m-d H:i:s'));
    }

    /**
     * @Notes: 导出订单数据
     * @author: Baron
     */
    public function exportOrderCsv() {
        $input_data = $this->request->param();
        $id = $input_data['id'] ?? '';
        empty($id) && exit();
        $act_group_order_model = new ActivityGroupOrder();
        //导入CSV的表头信息
        $header[] = ['记录ID', '身份', '客户姓名', '手机', '微信昵称', '付款金额', '推荐来源', '订单/拼团状态',
            '时间', 'Openid'];
        $data = $act_group_order_model->getOrderList($id, 0, 0, '', 1, 100000);
        $list = [];
        //内容顺序需要和表头顺序一致，不然会导致数据错乱
        foreach ($data as $k => $v) {
            $new_single = [];
            $status_etc='';
            if (!empty($v)) {
                $new_single[] = $v['id'] ?? '';
                if ($v['order_type'] == 1) {
                    $new_single[] = '团长';
                    $status_etc = $v['total_num'] == $v['current_num'] ? '已成功' : '拼团中';
                } else {
                    $new_single[] = '团员';
                }
                $new_single[] = $v['user_name'] ?? '';
                $new_single[] = $v['mobile'] ?? '';
                $new_single[] = $v['nick_name'] ?? '';
                $new_single[] = $v['pay_amount'] ?? '';
                $new_single[] = $v['ref_nick_name'] ?? '';
                $new_single[] = ($v['status_name'] ?? '') .'；'. ($status_etc ?? '');
                $new_single[] = $v['create_datetime'] ?? '';
                $openid = @(new User())->where('id', $v['user_id'])->value('openid');
                $new_single[] = $openid ?? '';
            }
            !empty($new_single) && $list[] = $new_single;
        }
        $csv_data = array_merge($header, $list);
        $csvctrl = new csvexport();
        $csvctrl->putCsvOne($csv_data, date('Y-m-d H:i:s'));
    }

    /**
     * @Notes: 获取红包记录
     * @return mixed|string
     * @author: Baron
     */
    public function rpList() {

        $input_data = $this->request->param();
        $page = $input_data['page'] ?? 1;
        $act_id = $input_data['act_id'] ?? 1;
        $status = $input_data['status'] ?? 0;
        $keywords = $input_data['keywords'] ?? '';

        $act_group_rp_record_model = new ActivityGroupRpRecord();
        $list = $act_group_rp_record_model->getRecordList($act_id, $status, $keywords, $page);
        $this->assign('list', $list);
        if (request()->isAjax()) {
            return $this->fetch('rplstajax');
        } else {
//            return $this->view->fetch();
            $input_data = [
                'act_id' => $act_id,
                'status' => $status,
                'keywords' => $keywords,
            ];
            $this->assign('input_data', $input_data);
            return $this->view->fetch('rplist');
        }
    }

    /**
     * @Notes: 删除红包记录
     * @author: Baron
     */
    public function deleteRpRecord() {

        if (request()->isAjax()) {
            $input_data = $this->request->param();
            $record_id = $input_data['id'] ?? '';

            $act_group_rp_record_model = new ActivityGroupRpRecord();
            $ret = $act_group_rp_record_model->delRecord($record_id);

            if ($ret > 0) {
                $this->success('删除成功！');
            }
            $this->error('删除失败！');
        }
    }

    /**
     * @Notes: 获取访问记录
     * @return mixed|string
     * @author: Baron
     */
    public function visitList() {

        $input_data = $this->request->param();
        $page = $input_data['page'] ?? 1;
        $act_id = $input_data['act_id'] ?? 1;
        $keywords = $input_data['keywords'] ?? '';
        $search_time = $input_data['search_time'] ?? '';
        if (!empty($search_time)) {
            list($start_date, $end_date) = explode(' - ', $search_time);
        }

        $act_group_visit_record_model = new ActivityGroupVisitRecord();
        $list = $act_group_visit_record_model->getRecordList($act_id, $keywords, $start_date ?? '', $end_date ?? '', $page);
        $this->assign('list', $list);
        if (request()->isAjax()) {
            return $this->fetch('visitlstajax');
        } else {
//            return $this->view->fetch();
            $input_data = [
                'act_id' => $act_id,
                'search_time' => $search_time,
                'keywords' => $keywords,
            ];
            $this->assign('input_data', $input_data);
            return $this->view->fetch('visitlist');
        }
    }

    /**
     * @Notes: 删除访问记录
     * @author: Baron
     */
    public function deleteVisitRecord() {

        if (request()->isAjax()) {
            $input_data = $this->request->param();
            $record_id = $input_data['id'] ?? '';

            $act_group_visit_record_model = new ActivityGroupVisitRecord();
            $ret = $act_group_visit_record_model->delRecord($record_id);

            if ($ret > 0) {
                $this->success('删除成功！');
            }
            $this->error('删除失败！');
        }
    }


    /**
     * @Notes: 一级代理人列表
     */
    public function refuserList(){
        $input_data = $this->request->param();
        $page = $input_data['page'] ?? 1;
        $act_id = $input_data['act_id'] ?? 1;
        $keywords = $input_data['keywords'] ?? '';
        $search_time = $input_data['search_time'] ?? '';
        if (!empty($search_time)) {
            list($start_date, $end_date) = explode(' - ', $search_time);
        }

        $act_group_refuser_record_model = new ActivityGroupRefuserRecord();
        $list = $act_group_refuser_record_model->getRecordList($act_id, $keywords, $start_date ?? '', $end_date ?? '', $page);
        $this->assign('list', $list);
        if (request()->isAjax()) {
            return $this->fetch('refuserlstajax');
        } else {
//            return $this->view->fetch();
            $input_data = [
                'act_id' => $act_id,
                'search_time' => $search_time,
                'keywords' => $keywords,
            ];
            $this->assign('input_data', $input_data);
            return $this->view->fetch('refuserlist');
        }
    }


    /**
     * @Notes: 更新状态
     * @author: Baron
     */
    public function updateRefuserRecord() {

        if (request()->isAjax()) {
            $input_data = $this->request->param();
            $record_id = $input_data['id'] ?? '';
            $status = $input_data['status'] ?? '1';

            $act_group_refuser_record_model = new ActivityGroupRefuserRecord();
            $ret = $act_group_refuser_record_model->updateRecord($record_id, $status);

            if ($ret > 0) {
                $this->success('更新成功！');
            }
            $this->error('更新失败！');
        }
    }

}
