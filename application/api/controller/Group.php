<?php

namespace app\api\controller;

use app\api\model\ActivityGroupVisitRecord;
use app\common\controller\ApiBase;
use app\api\model\User as Us;
use app\api\model\ActivityBase;
use app\api\model\ActivityConfig;
use app\api\model\ActivityPage;
use app\api\model\ActivityShare;
use app\mp\model\ActivityGroupOrder;
use aes\Aes;
use app\mp\model\ActivityGroupRefuserRecord;
use qrcode\QrcodeImg;
use app\api\model\ActivityUserPosterRecord;

/**
 * @apiDefine Group 团购
 */
class Group extends ApiBase {

    protected $user_model;
    /** @var  ActivityBase */
    protected $activity_base_model;
    protected $activity_config_model;
    protected $activity_page_model;
    protected $activity_share_model;
    /** @var  ActivityGroupOrder */
    protected $activity_group_order_model;
    protected $activity_group_visit_record_model;
    /** @var  ActivityGroupRefuserRecord */
    protected $act_group_refuser_model;

    public function _initialize() {
        parent::_initialize();
        $this->user_model = new Us();
        $this->activity_base_model = new ActivityBase();
        $this->activity_config_model = new ActivityConfig();
        $this->activity_page_model = new ActivityPage();
        $this->activity_share_model = new ActivityShare();
        $this->activity_group_order_model = new ActivityGroupOrder();
        $this->activity_group_visit_record_model = new ActivityGroupVisitRecord();
        $this->act_group_refuser_model = new ActivityGroupRefuserRecord();
    }

    /**
     * @Notes: 获取推荐人的线下订单列表
     * @return string
     * @author: Baron
     */
    public function order_list()
    {
        if ($this->request->isPost()) {

            $data = $this->request->param();
            if (empty($data['activity_id'])) {
                return apiMsg([], '-1', '未知错误！');
            }
            if (empty($data['user_id'])) {
                return apiMsg([], '-1', '未知错误！');
            }
            $ret = $this->activity_base_model->getActivityInfo($data['activity_id']);
            if (empty($ret)) {
            }
            $ret = $this->act_group_refuser_model->checkAllow($data['activity_id'], $data['user_id']);
            if ($ret <= 0){
                return apiMsg([], '-1', '你没有权限');
            }
            if (isset($data['admin_id']) && $data['admin_id'] == 99){
                $where = ['activity_id'=> $data['activity_id']];
            }else{
                $where = ['activity_id'=> $data['activity_id'], 'ref_user_id' => $data['user_id']];
            }
            $result['cnt_all'] = $this->activity_group_order_model->where($where)->count('id');
            $where_not_pay = $where + ['status'=> 1];
            $result['cnt_not_pay'] = $this->activity_group_order_model->where($where_not_pay)->count('id');
            $order_list = $this->activity_group_order_model->where($where);
//                ->where('status', 'eq', 2)
            $order_list = $order_list->select()->toArray();

            if(!isset($data['admin_id']) && empty($order_list)){ // 如果没有订单记录,则也提示没有权限
                $result['auth'] = 1;
                return apiMsg($result);
            }
            //
            $order_list = array_map(function ($item) {
//                $mobile = $item['mobile'];
//                if (strlen($mobile) >= 11) {
//                    $item['mobile'] = substr($mobile, 0, 3) . "****" . substr($mobile, 7, 4);
//                }
                return $item;
            }, $order_list);
            $result['order_list'] = $order_list;

            return apiMsg($result);
        }
    }

    /**
     * @api {POST} api.php?s=/group/activity_info 读取活动信息
     * @apiGroup User
     * @apiDescription 获取客户的微信信息。
     * @apiParam {String} code 微信用户授权凭证
     * @apiParamExample{object} 参数样例
     * {
     *  "activity_id": "2",//活动ID
     *  "user_id": "1",//用户ID
     *  "order_id": "",//订单ID成团ID
     *  "ref_user_id": "",//推荐人ID
     * 
     * }
     * @apiSuccess (200) {string} msg 信息,成功返回success
     * @apiSuccess (200) {int} code 0 代表无错误
     * @apiSuccess (200) {object} data 返回数据
     * @apiSuccessExample {json} 返回样例:
     * {
     *   "code": 0,
     *   "msg": "success",
     *   "data": {
     *      "user_status":1, //当前用户参与状态 1-已参加 0-未参加
     *      "order_id":2, //当前用户的订单ID
     *      "status":1, //活动状态 1-活动进行中 0-活动结束 2-活动未开始
     *      "start_time":'1.9', //活动开始时间
     *      "end_time":'9.9',//活动结束时间
     *      "activity_name":'996教育活动',//活动名称
     *      "activity_content":'开展XXXXXXXX96966',//活动描述
     *      "activity_img":'\public\XXX.png',//活动图标
     *      "activity_telephone":'0871-1000',//活动电话
     *      "group_num":'1000',//团长人数
     *      "jion_num":'2000',//参团人数
     *      "view_num":'10000',//浏览人数
     *      "page_config":{
     *              "top_color":'',//顶部背景色
     *              "tg_color":'',//团购背景色
     *              "group_color":'',//成团背景色
     *              "brokerage_color":'',//佣金背景色
     *              "bg_music":'',//背景音乐
     *              "dcrc_icon":'',//客服图标
     *              "order_icon":'',//订单图标
     *              "top_image":'',//顶部图片
     *              "poster_image":'',//海报图片
     *              "bottom_content":'',//下方图文编辑
     *              },
     *      "share_config":{//活动页面设置
     *              "is_open":'1', //是否启用分享
     *              "share_title_single":'1',//转发标题1 未参加人员转发
     *              "share_title_circle":'1',//转发表2 已参加人员转发
     *              "share_icon":'\public\XXX.png',//分享图标
     *              },
     *      "activity_config":{//活动设置
     *              "create_amount":'1.9', //开团金额
     *              "join_amount":'9.9',//参团金额
     *              "group_number":'3',//成团人数
     *              "group_amount":'250.00',//成团价格
     *              "old_amount":'963.21',//产品原价
     *              "max_number":'600',//开团总数
     *              "sham_view":'255',//虚拟浏览
     *              "sham_group_number":'30',//虚拟开团数
     *              "sham_jion_group_number":'100',//虚拟参加数
     *              "sponsor":'XXX000XXXXX教育',//主办方
     *              "dcrc_name":'Mr.Z',//客服名称
     *              "dcrc_qrcode":'\public\XXX.png',//客服二维码
     *              },
     *      }
     * }
     */
    public function activity_info() {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            if (empty($data['activity_id'])) {
                return apiMsg([], '-1', '未知错误！');
            }
            if (empty($data['user_id'])) {
                return apiMsg([], '-1', '未知错误！');
            }
            $result = $this->activity_base_model->getActivityInfo($data['activity_id']);
            if (empty($result)) {
                return apiMsg([], '-1', '无活动进行');
            }
            $order_list = $this->activity_group_order_model->where('activity_id', $data['activity_id'])
                            ->where('status', 'eq', 2)->select()->toArray();
            $order_list = array_map(function($item) {

                $mobile = $item['mobile'];
                if (strlen($mobile) >= 11) {
                    $item['mobile'] = substr($mobile, 0, 3) . "****" . substr($mobile, 7, 4);
                }
                return $item;
            }, $order_list);
            $result['order_list'] = $order_list;

            //判断是否已经参加过
            $order_info = $this->activity_group_order_model->where('activity_id', $data['activity_id'])
                ->where('user_id', $data['user_id'])
                ->where('status', 'neq', '4')
                ->find()->toArray();
            $result['order_id'] = !empty($order_info['id']) ? $order_info['id'] : '';
            $result['order_info'] = $order_info;

            // 团长,需要显示团员
            if (isset($order_info['order_type']) && $order_info['order_type'] == 1){
                $sub_order_list[] = $order_info;
                $ret_order_list = $this->activity_group_order_model
                    ->where('activity_id', $data['activity_id'])
                    ->where('p_id', $data['user_id'])
                    ->where('status', 'neq', '4')->select()->toArray();
                $sub_order_list = array_merge( $sub_order_list, $ret_order_list);
                $result['sub_order_list'] = $sub_order_list;
            }
            // 状态 1-未支付 2-已支付 3-已核销 4-已删除
            //判断是参加团还是自由
//            $result['is_join'] = 0;
//            if (!empty($data['order_id'])) {
//                $p_order = $this->activity_group_order_model->where('activity_id', $data['activity_id'])->where('id', $data['order_id'])
//                                ->where('status', 'neq', '4')->find()->toArray();
//                if ($p_order['status'] >= 2) {
//                    $result['is_join'] = 1;
//                }
//            }
            //订单成功后生成二维码
            if (empty($data['long_share_url'])) {
                $data['long_share_url'] = sprintf("%s/index?id=%d&ref_user_id=%d&appid=%s", config("share_url"), $data['activity_id'], $data['user_id'], $data['appid']);
            }

            // 如果上线ID为0,则为老师,提交申请权限
            if (isset($data['ref_user_id']) && $data['ref_user_id'] == 0){
                $act_group_refuser_record_model = new ActivityGroupRefuserRecord();
                $is_exist = $act_group_refuser_record_model
                    ->where('activity_id', $data['activity_id'])
                    ->where('user_id', $data['user_id'])->count('id');
                if (!$is_exist){
                    $data['create_datetime'] = $data['update_datetime'] = date('Y-m-d H:i:s');
                    $act_group_refuser_record_model->allowField(true)->isUpdate(false)->save($data);
                    $result['first_auth'] = 1;  // 老师第一次进链接,表示审核
                }
            }

            //判断是否人满 1-已满 0-未满
            $result['enough'] = 0;
            if (!empty($data['ref_user_id']) && !empty($data['order_id'])) {
                $team_num = $this->activity_group_order_model->where('p_id', $data['ref_user_id'])
                                ->where('activity_id', $data['activity_id'])
                                ->where('order_type', 2)->count('id');
                $max = $result['activity_config']['group_number'] ?? 0;
                if ($team_num >= $max) {
                    $result['enough'] = 1;
                }
            }

            // 添加访问记录
            $visit_record = $this->activity_group_visit_record_model->where('activity_id', $data['activity_id'])
                ->where('user_id', $data['user_id'])->find()->toArray();
            if (empty($visit_record)){
                $this->activity_group_visit_record_model->allowField(true)->isUpdate(false)->save($data);
            }
            return apiMsg($result);
        }
    }

    public function generate_poster($poster_path, $user_url, $qrcode_url) {
//        $poster_path = '';
//        $user_url = 'http://www.tg_server.com/uploads/ueditor/images/20190507/1557190443697190.jpg';
//        $qrcode_url = 'http://www.tg_server.com/index?id=34&ref_user_id=11&appid=wxe0deba39657a483b';

        if (empty($poster_path)) {
//            return '';
            $poster_path = 'https://qn-cdn.avicks.com/1528857594117.jpg'; //原始海报地址
        }
        writerLog($poster_path);
        writerLog($user_url);
        writerLog($qrcode_url);
//        echo 'start:' . date('H:i:s') . "\n";
        //处理二维码
        $qrcode = new QrcodeImg();
        $qr_url = $qrcode->autoCreateQrcodeAndUpload($qrcode_url);
        writerLog('qrcode:' . date('H:i:s'));
        $qr_size = 200;
        $local_size = ($qr_size - $qr_url['QR_width']) / 2;
        writerLog("qr_size:" . $qr_url['QR_width']);
        $thumb = imagecreate($qr_size, $qr_size); //创建一个300x300图片，返回生成的资源句柄
        writerLog("thumb:");
        writerLog($thumb);
        //获取源文件资源句柄。接收参数为图片流，返回句柄
        $source = imagecreatefromstring(file_get_contents($qr_url['qrcode_name']));
        //将源文件剪切全部域并缩小放到目标图片上，前两个为资源句柄
        imagecopyresampled($thumb, $source, 0, 0, 0, 0, $qr_size, $qr_size, $qr_size, $qr_size);

        $thumb_name_user = 'user_log.jpg';
        // 获取微信图片,不能用file_get_content,用curl
        $ch = curl_init($user_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $file_content = curl_exec($ch);
//        $fil_content = file_get_contents($user_url);
        file_put_contents($thumb_name_user, $file_content);
        $thumb_user = @\imagecreatefromjpeg($thumb_name_user);
        $thumb_user_size = getimagesize($thumb_name_user);

        //创建图片的实例，接收参数为图片
        //        $dst_qr = @imagecreatefromstring(file_get_contents($poster_path));
        $dst_qr = @\imagecreatefromjpeg($poster_path);
        $dst_qr_size = getimagesize($poster_path);
        writerLog($dst_qr);
        //加水印1
        imagecopy($dst_qr, $thumb, 0, 0, 0, 0, $qr_size, $qr_size);
        //加水印2
        imagecopyresized($dst_qr, $thumb_user, $dst_qr_size[0] - $qr_size, 0, 0, 0,
                $qr_size, $qr_size, $thumb_user_size[0], $thumb_user_size[1]);
        //imagecopy($dst_qr, $thumb_user, $dst_qr_size[0]-$thumb_user_size[0], 0, 0, 0,
        //    $thumb_user_size[0], $thumb_user_size[1]);
        //生成图片
        list($usec, $sec) = explode(" ", microtime());
        $time = (float) $usec + (float) $sec;
        $poseter_name = '/alidata/tmp/poster_' . $time . '.jpg';
        imagejpeg($dst_qr, $poseter_name, 80); //输出
//        echo 'poseter_name:' . date('H:i:s') . "\n";
        //销毁
        imagedestroy($thumb);
        imagedestroy($thumb_user);
        imagedestroy($dst_qr);
        exec("rm -f " . $qr_url['qrcode_name']);
        //        var_dump($poster_url);exit;
        return $poseter_name;
    }

    /**
     * 根据图片类型创建画布
     * 传入图片路径已.png .jpg .jpeg .gif 结尾的图片。
     * 可以为网址路径或本地路径
     * @param varchar $image_url 
     * @return object|bool false
     */
    public function _create_image($image_url) {
        $res = FALSE;
        $file_type = explode('.', $image_url);
        try {

            switch (array_pop($file_type)) {
                case 'png':
                    $res = imagecreatefrompng($image_url);
                    break;
                case 'jpg':
                case 'jpeg':
                    $res = imagecreatefromjpeg($image_url);
                    break;
                case 'gif':
                    $res = imagecreatefromgif($image_url);
                    break;
                default :
                    break;
            }
        } catch (\Exception $ex) {
            $notfound_image = ROOT_PATH . "public/static/images/imgnotfound.jpg";
            $res = imagecreatefromjpeg($notfound_image);
        }
        return $res;
    }

    //下单前检查
    public function checkActivityTime() {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            if (empty($data['activity_id'])) {
                return apiMsg([], '-1', '未知错误！');
            }
            if (empty($data['user_id'])) {
                return apiMsg([], '-1', '未知错误！');
            }
            $result = $this->activity_base_model->getActivityInfo($data['activity_id']);
            if (empty($result)) {
                return apiMsg([], '-1', '无活动进行');
            }
            //检查活动时间
            $activity_info = $this->activity_base_model->where('id', $data['activity_id'])->find()->toArray();
            $start_time = $activity_info['start_time'];
            $end_time = $activity_info['end_time'];
            $time_res = $this->_checkTime($start_time, $end_time);
            if ($time_res['code'] != 0) {
                return apiMsg($time_res);
            }
            //检查是否已经参加过
            $order_res = $this->_checkOrder($data['activity_id'], $data['user_id']);
            if (!$order_res) {
                return apiMsg([], '-1', '你已经参加过了，请勿重复参加');
            }
            return apiMsg($time_res);
        }
    }

    //校验订单相关内容
    public function _checkOrder($activity_id, $user_id, $ref_user_id = '', $ref_order_id = '') {
        $res['code'] = TRUE;
        $res['msg'] = 'success';
        //判断是否是自己开的团        
        if ($user_id == $ref_user_id) {
            $res['code'] = FALSE;
            $res['msg'] = '你已经开团啦！快去邀请朋友吧';
            return $res;
        }
        //先判断是否已经参加过活动 or 订单超时
        $order_info = $this->activity_group_order_model->where('activity_id', $activity_id)
                        ->where('user_id', $user_id)
                        ->where('status', 'neq', 4)->find()->toArray();
        if (!empty($order_info)) {
            if ($order_info['status'] == 1) {
                $out_time = 60 * 10;
                $out_line = strtotime($order_info['create_datetime']) + $out_time;
                if ($out_line <= time()) {
                    $res['code'] = FALSE;
                    $res['msg'] = '支付超时！请重新填写信息并支付！';
                    return $res;
                }
            } else {
                $res['code'] = FALSE;
                $res['msg'] = '您已经参加过此活动！';
                return $res;
            }
        }
        //再判断是否团已经满员
        if (!empty($ref_user_id) && !empty($ref_order_id)) {
            $ref_order_info = $this->activity_group_order_model->where('activity_id', $activity_id)
                            ->where('user_id', $ref_user_id)->where('id', $ref_order_id)
                            ->where('status', 'neq', '1,4')->find()->toArray();
            if (empty($ref_order_info)) { //团长订单错误或有误
                $res['code'] = FALSE;
                $res['msg'] = '参加团已经满了！请自建团或寻找其他团加入！！';
                return $res;
            } else {
                $join_num = $this->activity_config_model->where('id', $activity_id)->value('group_number');
                $order_num = $this->activity_group_order_model->where('p_id', $ref_user_id)->where('status', 'neq', 4)->count('id');
                if ($order_info >= $join_num) {
                    $res['code'] = FALSE;
                    $res['msg'] = '参加团已经满了！请自建团或寻找其他团加入！';
                    return $res;
                }
            }
        }
        return $res;
    }

    //校验时间相关内容
    public function _checkTime($start_time, $end_time, $time = '') {
        $res['code'] = 0;
        $res['orror_type'] = 1; // 1-正常 2-活动未开始 3-活动结束
        $res['msg'] = '';
        $time = !empty($time) ? $time : time();
        if ($time < $start_time) {
            $res['code'] = 1;
            $res['error_type'] = 2;
            $res['msg'] = '活动未开始';
        } elseif ($time > $end_time) {
            $res['code'] = 1;
            $res['error_type'] = 3;
            $res['msg'] = '活动已开始';
        } else {
            $res['error_type'] = 1;
            $res['msg'] = '活动开始';
        }
        return $res;
    }

    /**
     * @api {POST} api.php?s=/group/access 获取访问用户信息
     * @apiGroup Group
     * @apiDescription 获取正在访问的用户信息
     * @apiParam {string} activityId 活动ID
     * @apiParam {string} userId 访客openid
     * @apiParam {string} refUserId 邀请人openid
     * @apiParamExample{object} 参数样例
     * {
     *  "activityId": "3333e", "userId": "sdf3d22," "refUserId": "dd99djl33j"
     * }
     * @apiSuccess (200) {string} msg 信息,成功返回success
     * @apiSuccess (200) {int} code 0 代表无错误
     * @apiSuccess (200) {object} data 返回数据
     * @apiSuccessExample {json} 返回样例:
     * {
     *   "code": 0,
     *   "msg": "success",
     *   "data": []
     * }
     */
    public function access() {
        $data = $this->request->post();
        $result = $this->validate($data, 'ActivityGroupVisitRecord');
        if (true !== $result) {
            // 验证失败 输出错误信息
            return apiErrorMsg($result);
        }

        $user = Us::get($data['userId']);
        if (!$user->toArray()) {
            return apiErrorMsg('用户不存在');
        }
        $data['nick_name'] = $user->nickname;
        $data['head_img'] = $user->headimgurl;

        $user = Us::get($data['refUserId']);
        if ($user->toArray()) {
            $data['ref_nick_name'] = $user->nickname;
            $data['ref_head_img'] = $user->headimgurl;
        }

        $activityGroupVisitRecord = model('ActivityGroupVisitRecord');
        $activityGroupVisitRecord->data($data)->uncamelize()
                ->allowField(true)
                ->save();

        return apiMsg();
    }

    /**
     * @api {POST} api.php?s=/group/readyToPay 准备支付
     * @apiGroup Group
     * @apiDescription 提交支付相关信息，准备调起支付。
     * @apiParam {string} order_id 订单ID
     * @apiParam {string} activity_id 活动ID
     * @apiParam {int} is_group 是否组团
     * @apiParam {String} user_id 用户ID
     * @apiParam {String} ref_user_id 邀请人ID
     * @apiParam {String} user_name 用户姓名
     * @apiParam{string} mobile 用户手机号码
     * @apiParamExample{object} 参数样例
     * {
     *  "isGroup": 1, "username": "习大大", "phone": "123344889"
     * }
     * @apiSuccess (200) {string} msg 信息,成功返回success
     * @apiSuccess (200) {int} code 0 代表无错误
     * @apiSuccess (200) {object} data 返回数据
     * @apiSuccessExample {json} 返回样例:
     * {
     *   "code": 0,
     *   "msg": "success",
     *   "data": {
     *    }
     * }
     */
    public function readyToPay() {
        $data = $this->request->post();
        $result = $this->validate($data, 'ActivityGroupVisitRecord');
        $error_msg = '未知错误';
        if (true !== $result) {
            // 验证失败 输出错误信息
            return apiErrorMsg($result);
        }
        if (empty($data['username'])) {
            return apiMsg([], '-1', '姓名必须填写哦！');
        }
        if (empty($data['phone'])) {
            return apiMsg([], '-1', '手机号码必须填写！');
        }
        
        //校验活动是否存在
        $activity_base_info = $this->activity_base_model->where('id', $data['activity_id'])->find()->toArray();
        $save_res = [];
        if (!empty($activity_base_info)) {
            //校验活动时间
            $time_res = $this->_checkTime($activity_base_info['start_time'], $activity_base_info['end_time']);
            if ($time_res['code'] === 0) {
                $save_res = $this->_check2format_savedata($data, $data['activity_id']);
            }
        }
        if (!empty($save_res['code'])) {
            $save_data = $save_res['data'];
            if (empty($save_res['save_id'])) {
                $res = $this->activity_group_order_model->allowField(TRUE)->isUpdate(false)->save($save_res['data']);
                $order_id = $this->activity_group_order_model->getLastInsID();
            } else {
                $res = $this->activity_group_order_model->allowField(TRUE)->isUpdate(true)->save($save_res['data'], ['id' => $save_res['save_id']]);
                $order_id = $save_res['save_id'];
            }
            if ($res) {
                $ret_result['code'] = 0;
                $ret_result['activity_name'] = $activity_base_info['activity_name'];
                $ret_result['order_id'] = $order_id;
                $ret_result['pay_amount'] = $save_data['pay_amount_pay'];
                $ret_result['create_time'] = strtotime($save_data['create_datetime']);
                return apiMsg($ret_result);
            }
        } else {
            $error_msg = !empty($save_res['msg']) ? $save_res['msg'] : '数据校验错误';
        }
        return apiErrorMsg($error_msg);
    }

    public function _check2format_savedata($data, $activity_id) {
        $res['code'] = false;
        $res['msg'] = '活动信息错误';
        $newdata = [];
        $activity_info = $this->activity_config_model->where('base_id', $activity_id)->find()->toArray();
        $order_info = [];
        $current_num = 0;
        if (!empty($data) && !empty($activity_info)) {
            if (!isset($data['order_id']) || $data['order_id']=='undefined'){
                $data['order_id']='';
            }
            //判断是否已经购买 已经购买跳出
            $is_old = $this->activity_group_order_model->where('user_id', $data['user_id'])->where('activity_id', $activity_id)->where('status', 'neq', 4)->find()->toArray();
            if (empty($is_old)) {
                $newdata['activity_id'] = $activity_id;
                $newdata['user_id'] = $data['user_id'];
                if ($data['isGroup'] == 1) {
                    $newdata['act_type'] = 2; //单独购买
                    $newdata['pay_amount_pay'] = $activity_info['old_amount'];
                    $newdata['pay_amount'] = format_price($activity_info['old_amount']);
                } else if ($data['isGroup'] == 5) {
                    $newdata['act_type'] = 3;
                    $newdata['pay_amount_pay'] = 0;
                    $newdata['pay_amount'] = 0;
                } else {
                    $newdata['act_type'] = 1; //参团
                    //判断是加团还是自己开团
                    if ($data['isGroup'] != 3) {
                        $data['order_id'] = '';
                    }
                    if (!empty($data['order_id']) && !empty($data['ref_user_id'])) {
                        $leader = $this->activity_group_order_model->where('id', $data['order_id'])->where('user_id', $data['ref_user_id'])
                                        ->where('activity_id', $activity_id)
                                        ->where('order_type', 1)->find()->toArray();
                        $team_num = $this->activity_group_order_model->where('p_id', $data['ref_user_id'])
                                        ->where('activity_id', $activity_id)
                                        ->where('order_type', 2)->count('id');
                        $team_num += 1;
                        $current_num = $team_num;
                        if ($team_num > $activity_info['group_number']) {
                            $data['order_id'] = '';
                            $current_num = 0;
                            $newdata['order_type'] = 1; //团长
                        } else {
                            $newdata['order_type'] = 2; //团员
                            $newdata['p_id'] = $leader['user_id'] ?? '';
                            $newdata['p_name'] = $leader['nick_name'] ?? '';
                        }
                    } else {
                        $newdata['order_type'] = 1; //团长
                    }
                    //判断是团长还是团员
                    $newdata['pay_amount_pay'] = $activity_info['group_amount'];
                    $newdata['pay_amount'] = format_price($activity_info['group_amount']);
                }
                $user_info = $this->user_model->where('id', $data['user_id'])->find()->toArray();
                if (empty($user_info)) {
                    $res['code'] = FALSE;
                    $res['msg'] = '用户信息错误';
                }
                $newdata['nick_name'] = $user_info['nickname'];
                $newdata['head_img'] = $user_info['headimgurl'];
                $newdata['user_name'] = $data['username'];
                $newdata['mobile'] = $data['phone'];
                if (!empty($data['ref_user_id'])) {
                    $ref_user = $this->user_model->where('id', $data['ref_user_id'])->find()->toArray();
                    if (!empty($ref_user)) {
                        $newdata['ref_nick_name'] = $ref_user['nickname'];
                        $newdata['ref_head_img'] = $ref_user['headimgurl'];
                        $newdata['ref_user_name'] = $ref_user['name'];
                    }
                }
                $newdata['ref_user_id'] = $data['ref_user_id'] ?? ''; //目前默认为 2
                $newdata['order_id'] = $data['order_id'] ?? '';
                $newdata['total_num'] = $activity_info['group_number'] ?? 0;
                $newdata['current_num'] = $current_num;
                $newdata['status'] = 1;
                $newdata['create_datetime'] = date('Y-m-d H:i:s');
                $res['code'] = true;
                $res['data'] = $newdata;
                $res['save_id'] = '';
                $res['msg'] = '';
            } else {
                if ($data['isGroup'] == 1) {
                    $updata['act_type'] = 2; //单独购买
                    $updata['pay_amount_pay'] = $activity_info['old_amount'];
                    $updata['pay_amount'] = format_price($activity_info['old_amount']);
                } else if ($data['isGroup'] == 5) {
                    $updata['act_type'] = 3;
                    $updata['pay_amount_pay'] = 0;
                    $updata['pay_amount'] = 0;
                } else {
                    $updata['act_type'] = 1; //单独购买
                    //判断是加团还是自己开团
                    if ($data['isGroup'] != 3) {
                        $data['order_id'] = '';
                    }
                    if (!empty($data['order_id']) && !empty($data['ref_user_id'])) {
                        $leader = $this->activity_group_order_model->where('id', $data['order_id'])->where('user_id', $data['ref_user_id'])
                                        ->where('activity_id', $activity_id)
                                        ->where('order_type', 1)->find()->toArray();
                        $team_num = $this->activity_group_order_model->where('p_id', $data['ref_user_id'])
                                        ->where('activity_id', $activity_id)
                                        ->where('order_type', 2)->count('id');
                        $team_num += 1;
                        $current_num = $team_num;
                        if ($team_num > $activity_info['group_number']) {
                            $data['order_id'] = '';
                            $current_num = 0;
                        }
                        $updata['order_type'] = 2; //团员
                        $updata['p_id'] = $leader['user_id'] ?? '';
                        $updata['p_name'] = $leader['nick_name'] ?? '';
                    } else {
                        $updata['order_type'] = 1; //团长
                    }
                    //判断是团长还是团员
                    $updata['pay_amount_pay'] = $activity_info['group_amount'];
                    $updata['pay_amount'] = format_price($activity_info['group_amount']);
                }
                //已经下单的重新更新订单信息
                $updata['user_name'] = $data['username'];
                $updata['mobile'] = $data['phone'];
                $updata['create_datetime'] = date('Y-m-d H:i:s');
                $updata['update_datetime'] = date('Y-m-d H:i:s');
                $updata = array_merge($is_old, $updata);
                unset($updata['id']);
                $res['code'] = true;
                $res['data'] = $updata;
                $res['save_id'] = $is_old['id'];
                $res['msg'] = '';
            }
        }
        return $res;
    }

    public function reserveOrder() {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            if (empty($data['order_id'])) {
                return apiMsg([], '-1', '未知错误！');
            }
            if ($this->activity_group_order_model->where('id', $data['order_id'])->delete()) {
                return apiMsg();
            }
        }
    }

    /**
     * @api {POST} api.php?s=/group/wxpayCallback 微信支付回调
     * @apiGroup Group
     * @apiDescription 提交支付相关信息，准备调起支付。
     * @apiParam {int} isGroup 是否组团
     * @apiParam {String} username 用户姓名
     * @apiParam{string} phone 用户手机号码
     * @apiParamExample{object} 参数样例
     * {
     *  "isGroup": 1, "username": "习大大", "phone": "123344889"
     * }
     * @apiSuccess (200) {string} msg 信息,成功返回success
     * @apiSuccess (200) {int} code 0 代表无错误
     * @apiSuccess (200) {object} data 返回数据
     * @apiSuccessExample {json} 返回样例:
     * {
     *   "code": 0,
     *   "msg": "success",
     *   "data": {
     *    }
     * }
     */
    public function wxpayCallback() {
        
    }

    /**
     * 零元支付
     * @return type
     */
    public function zeroPay() {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $ticket = $this->request->param('lawnson_rsa_ticket_002');
            if (!empty($ticket)) {
                $data = json_decode(Aes::decrypt($ticket), true);
            }
            if (empty($data['order_id'])) {
                return apiMsg([], '-1', '未知错误！');
            }
            if (empty($data['user_id'])) {
                return apiMsg([], '-1', '未知错误！!');
            }
            if (empty($data['activity_id'])) {
                return apiMsg([], '-1', '未知错误！!!');
            }
            $order_info = $this->activity_group_order_model->where('id', $data['order_id'])->where('user_id', $data['user_id'])
                            ->where('status', 1)->where('activity_id', $data['activity_id'])
                            ->find()->toArray();
            if (!empty($order_info)) {
                if ($order_info['pay_amount'] <= 0) {
                    $update_data['pay_time'] = date('Y-m-d H:i:s');
                    $update_data['status'] = 2;
                    $update_data['update_datetime'] = date('Y-m-d H:i:s');
                    $update_res = $this->activity_group_order_model->where('id', $data['order_id'])->where('user_id', $data['user_id'])
                                    ->where('status', 1)->where('activity_id', $data['activity_id'])->update($update_data);
                    if ($update_res) {
                        return apiMsg([], 0, '支付成功');
                    }
                }
            }
            return apiMsg([], '-1', '未知错误！！！！!');
        }
    }

    public function makePosterImage() {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $src = '';
            if (empty($data['user_id']) || empty($data['activity_id']) || empty($data['poster_image']) || empty($data['long_share_url'])) {
                return apiMsg([], '-1', '未知错误！');
            }
            $img_src = (new ActivityUserPosterRecord())->where('user_id', $data['user_id'])->where('activity_id', $data['activity_id'])->value('src');
//            $img_src = 'http://thirdwx.qlogo.cn/mmopen/rFyvcl3A4Jic66OBtqeLLSSffaNfZ8AuiboTN6Fl4qicWiaUzEaf9nrpbiaSkp7aJOBIZO8VAy9wSEcbiaWNdWYsVgKw8o3OQ1a4f4/132';
            if (empty($img_src)) {
                //海报图片
//                $poster_image = strtr($data['poster_image'], [WEB_PATH=>'']);
                //用户头像图片
                $headimgurl = $this->user_model->where('id', $data['user_id'])->value('headimgurl');
//                dump($headimgurl);
//                dump($data);
//                $src = '/alidata/tmp/poster_1558442699.6884.jpg';
//                exit();
                $src = $this->generate_poster($data['poster_image'], $headimgurl, $data['long_share_url']);
                if (!empty($src)) {
                    $src_arr = explode('/', $src);
                    $file_name = array_pop($src_arr);
                    $url = 'public' . DS . 'poster' . DS . $file_name;
                    $src_url = DS . 'poster' . DS . $file_name;
                    $dst = ROOT_PATH . $url;
//                    if (!is_dir($dst)) {
//                        mkdir(dirname($dst), 0777, true);
//                    }
                    $res = rename($src, $dst);
                    // 然后移动
                    if ($res) {
                        //将文件移动至public
                        $save_data['user_id'] = $data['user_id'];
                        $save_data['activity_id'] = $data['activity_id'];
                        $save_data['src'] = $src_url;
                        (new ActivityUserPosterRecord())->isUpdate(FALSE)->save($save_data);
                    } else {
                        return apiMsg([], '-1', '生成失败！');
                    }
                }
            } else {
                $src_url = $img_src;
            }
            if (!empty($src_url)) {
                return apiMsg(['src' => $src_url], 0, 'success');
            }
        }
    }

}
