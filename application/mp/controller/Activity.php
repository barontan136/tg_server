<?php

namespace app\mp\controller;

use app\common\controller\MpBase;
use app\mp\model\ActivityBase;
use app\mp\model\ActivityConfig;
use app\mp\model\ActivityPage;
use app\mp\model\ActivityShare;
use app\mp\model\ActivityGroupOrder;
use app\mp\model\ActivityGroupVisitRecord;
use app\mp\service\PictureService;
use qrcode\QrcodeImg;
use think\Db;

/**
 * 活动管理
 * Class Card
 * @package app\mp\controller
 */
class Activity extends MpBase {

    protected $activity_base_model;
    protected $activity_config_model;
    protected $activity_page_model;
    protected $activity_share_model;
    protected $act_group_order_model;
    protected $act_group_visit_record_model;

    public function _initialize() {
        parent::_initialize();
        $this->activity_base_model = new ActivityBase();
        $this->activity_config_model = new ActivityConfig();
        $this->activity_page_model = new ActivityPage();
        $this->activity_share_model = new ActivityShare();
        $this->act_group_order_model = new ActivityGroupOrder();
        $this->act_group_visit_record_model = new ActivityGroupVisitRecord();
    }

    //活动管理
    public function index() {
        $where = $this->_where();
        $where['merchant_id'] = $this->merchant_id;
        $where['status'] =  ['egt', 0];
        $list = $this->activity_base_model->where($where)->order('update_time DESC')->paginate(8)->each(function($item, $key) {
            $item['leader_num'] = $this->act_group_order_model->where('order_type', 1)->where('activity_id', $item['id'])->count('id');
            $item['current_num'] = $this->act_group_order_model->where('order_type', 2)->where('activity_id', $item['id'])->count('id');
            $item['group_num'] = $this->act_group_order_model->where('activity_id', $item['id'])->where('order_type', 1)->where('status', 'in', '3')->count('id');
            $item['money_num'] = $this->act_group_order_model->where('activity_id', $item['id'])->where('status', 'in', '1,3')->sum('pay_amount');
            $item['visit_num'] = $this->act_group_visit_record_model->where('activity_id', $item['id'])->where('status', 1)->count('id');
            $item['act_name']=$item['act_name'];
//            $item['status_name'] = $item['status'] == 1 ? '正常' : '禁用';
            return $item;
        });
        $this->assign('list', $list);
        if (request()->isAjax()) {
            return $this->fetch('lstajax');
        } else {
            return $this->view->fetch();
        }
    }

    //活动管理-新增活动
    public function add($id = '', $type = '') {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            writerLog($data);
            //活动信息
            $config_id = '';
            $page_id = '';
            $share_id = '';
            beginTransaction();
            if (!empty($data['config'])) {
                $confg_where = [];
                $config_data = $data['config'];
                //客户二维码
                $config_data['dcrc_qrcode'] = !empty($data['dcrc_qrcode']) ? $data['dcrc_qrcode'][0] : '';
                if (empty($config_data['id'])) {
                    unset($config_data['id']);
                } else {
                    $confg_where['id'] = $config_data['id'];
                }
                $config_data['merchant_id'] = $this->merchant_id;
                $config_data = $this->_formatMoney2useSave($config_data, 2, 1, 2);
                $config_data['status'] = 1;
                if ($this->activity_config_model->allowField(TRUE)->save($config_data, $confg_where)) {
                    $config_id = $this->activity_config_model->getLastID();
                } else {
                    rollbackTransaction();
                    $this->error('保存错误！请检查信息后重置');
                }
            }
            //页面设置
            if (!empty($data['page_config'])) {
                $page_where = [];
                $page_data = $data['page_config'];
                //客服图标
                $page_data['dcrc_icon'] = !empty($data['dcrc_icon']) ? $data['dcrc_icon'][0] : '';
                //订单图标
                $page_data['order_icon'] = !empty($data['order_icon']) ? $data['order_icon'][0] : '';
                //海报图片
                $page_data['poster_image'] = !empty($data['poster_image']) ? $data['poster_image'][0] : '';
                //背景音乐
                $page_data['bg_music'] = !empty($data['bg_music']) ? $data['bg_music'][1] : '';
                //顶部图片
                $page_data['top_image'] = !empty($data['top_image']) ? $data['top_image'][0] : '';
                if (empty($page_data['id'])) {
                    unset($page_data['id']);
                } else {
                    $page_where['id'] = $page_data['id'];
                }
                $page_data['merchant_id'] = $this->merchant_id;
//                $page_data['bottom_content'] = $page_data['content'];
                $page_data['status'] = 1;
                if ($this->activity_page_model->allowField(TRUE)->save($page_data, $page_where)) {
                    $page_id = $this->activity_page_model->getLastID();
                } else {
                    rollbackTransaction();
                    $this->error('保存错误！请检查信息后重置');
                }
            }
            //分享设置
            if (!empty($data['share'])) {
                $share_where = [];
                $share_data = $data['share'];
                //分享图片
                $share_data['share_icon'] = !empty($data['activity_img']) ? $data['activity_img'][0] : '';
                if (empty($share_data['id'])) {
                    unset($share_data['id']);
                } else {
                    $share_where['id'] = $share_data['id'];
                }
                $share_data['merchant_id'] = $this->merchant_id;
                $share_data['status'] = 1;
                if ($this->activity_share_model->allowField(TRUE)->save($share_data, $share_where)) {
                    $share_id = $this->activity_share_model->getLastID();
                } else {
                    rollbackTransaction();
                    $this->error('保存错误！请检查信息后重置');
                }
            }
            //基本信息
            if (!empty($data['base'])) {
                $base_where = [];
                $base_data = $data['base'];
                //分享图标
                $base_data['activity_img'] = !empty($data['activity_img']) ? $data['activity_img'][0] : '';
                //订单图标
                $base_data['activity_config_id'] = $config_id;
                //海报图片
                $base_data['activity_page_id'] = $page_id;
                //顶部图片
                $base_data['activity_share_id'] = $share_id;
                //处理活动时间
                if (!empty($base_data['activity_time'])) {
                    $time_arr = explode(' - ', $base_data['activity_time']);
                    $base_data['start_time'] = strtotime($time_arr[0]);
                    $base_data['end_time'] = strtotime($time_arr[1]);
                }
                if (empty($base_data['id'])) {
                    unset($base_data['id']);
                    //活动类型 订单类型 1-团购 2-单独购买 3-兼职
                    $base_data['act_type'] = $base_data['act_type'] ?? 1;
                } else {
                    $base_where['id'] = $base_data['id']; //母链接
                    $uri_type = 'index';
                    if ($base_data['act_type'] == 3){
                        $uri_type = 'parttime';
                    }elseif ($base_data['act_type'] == 2){
                        $uri_type = 'buy';
                    }
                    $base_data['base_url'] = $_SERVER['REQUEST_SCHEME']."://".$_SERVER['HTTP_HOST']."/"
                        .$uri_type."?id=" . $base_where['id'] . "&ref_user_id=0&appid=wxe0deba39657a483b"; // . 'act_id=';
                    $base_data['base_url_qrcode'] = $this->getUrlQRCode($base_data['base_url']);

                    $base_data['order_url'] = $_SERVER['REQUEST_SCHEME']."://".$_SERVER['HTTP_HOST']."/"
                        . "orderlist?id=" . $base_where['id'] . "&ref_user_id=0&appid=wxe0deba39657a483b";

                    unset($base_data['act_type']);
                }
                $base_data['merchant_id'] = $this->merchant_id;
                if ($this->activity_base_model->allowField(TRUE)->save($base_data, $base_where)) {
                    $base_id['base_id'] = $this->activity_base_model->getLastID();
                    $this->activity_config_model->save($base_id, ['id' => $config_id]);
                    $this->activity_page_model->save($base_id, ['id' => $page_id]);
                    $this->activity_share_model->save($base_id, ['id' => $share_id]);
                    commitTransaction();
                    $this->success('保存成功', 'index');
                } else {
                    rollbackTransaction();
                    $this->error('保存错误！请检查信息后重置');
                }
            }
            rollbackTransaction();
            $this->error('保存错误！请检查信息后重置', '', $data);
        } else {
            if (!empty($id) && !empty($type)) {
                $base = [];
                $config = [];
                $page_config = [];
                $share = [];
                $base = $this->activity_base_model->where('id', $id)->find()->toArray();
                $base['act_type_name'] = $base['act_type'] == 3 ? '兼职' : ($base['act_type'] == 2 ? '单独购买' : '团购');

                $uri_type = 'index';
                if ($base['act_type'] == 3){
                    $uri_type = 'parttime';
                }elseif ($base['act_type'] == 2){
                    $uri_type = 'buy';
                }
                $base['base_url'] = $_SERVER['REQUEST_SCHEME']."://".$_SERVER['HTTP_HOST']."/"
                    .$uri_type."?id=" . $base['id'] . "&ref_user_id=0&appid=wxe0deba39657a483b"; // . 'act_id=';
                $base['base_url_qrcode'] = $this->getUrlQRCode($base['base_url']);

                $base['order_url'] = $_SERVER['REQUEST_SCHEME']."://".$_SERVER['HTTP_HOST']."/"
                    . "orderlist?id=" . $base['id'] . "appid=wxe0deba39657a483b";
                $base['order_url_qrcode'] = $this->getUrlQRCode($base['order_url']);

                $base['order_url_admin'] = $_SERVER['REQUEST_SCHEME']."://".$_SERVER['HTTP_HOST']."/"
                    . "orderlist?id=" . $base['id'] . "&admin_id=99&appid=wxe0deba39657a483b";
                $base['order_url_admin_qrcode'] = $this->getUrlQRCode($base['order_url_admin']);

                !empty($base['activity_config_id']) && $config = $this->activity_config_model->where('id', $base['activity_config_id'])->find()->toArray();
                !empty($base['activity_page_id']) && $page_config = $this->activity_page_model->where('id', $base['activity_page_id'])->find()->toArray();
                !empty($base['activity_share_id']) && $share = $this->activity_share_model->where('id', $base['activity_share_id'])->find()->toArray();
                if ($type == 'copy') {
                    unset($base['id']);
                    unset($config['id']);
                    unset($page_config['id']);
                    unset($share['id']);
                }
                $this->assign('base', $base);
                $this->assign('config', !empty($config) ? $this->_formatMoney2useSave($config, 1, 2, 2, 'string') : []);
                $this->assign('page_config', $page_config);
                $this->assign('share', $share);

            }
            $this->assign('type', $type);
            return $this->fetch();
        }
    }

    public function delete()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();

            $base_data['status'] = -1;
            $base_where['id'] = $data['id'];
            if ($this->activity_base_model->allowField(TRUE)->save($base_data, $base_where)) {
                $this->success('删除成功');
            }
        }
    }

    private function getUrlQRCode($url){
        //处理二维码
        $qrcode = new QrcodeImg();
        $qr_url = $qrcode->autoCreateQrcodeAndUpload($url);
        $src = $qr_url['qrcode_name'];
        $src_arr = explode('/', $src);
        $file_name = array_pop($src_arr);
        $url = 'public' . DS . 'poster' . DS . $file_name;
        $src_url = DS . 'poster' . DS . $file_name;
        $dst = ROOT_PATH . $url;
        $res = rename($src, $dst);
        if ($res) {
            return $src_url;
        }
        return '';
    }

    public function _formatMoney2useSave($data, $from = 1, $to = 2, $decimals = 2, $returntype = 'numeric') {
        if (!empty($data)) {
            foreach ($data as $k => $v) {
                switch ($k) {
                    case 'create_amount':
                    case 'join_amount':
                    case 'group_amount':
                    case 'old_amount':
                        $data[$k] = format_price($v, $from, $to, $decimals, $returntype);
                        break;
                    default:
                        break;
                }
            }
        }
        return $data;
    }

    public function music_upload() {
//        $data = $this->request->param();
//        $data = file_get_contents('php://input')
//            ? file_get_contents('php://input')
//            : gzuncompress($GLOBALS ['HTTP_RAW_POST_DATA']);
        $data = $this->request->file('file');
        $data = base64_decode($data);

        $ret = PictureService::uploadAudioBase64($data);
        if (!empty($ret)){
            $bg_music= $ret['path'];
        }

        return ajaxMsg('', 0, '', $ret);
    }


}
