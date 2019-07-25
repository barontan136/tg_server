<?php

namespace app\mp\model;

use think\Model;
use app\mp\model\ActivityGoodRelation;
use app\mp\model\ActivitySpike;
use app\mp\model\ActivityBargain;
use app\mp\model\ActivityTurntable;
use app\mp\model\ActivityTurntablePrizeRelation;
use app\mp\model\ActivityTurntableRecord;
use app\mp\model\Good;
use app\mp\model\Card;
use app\mp\model\GoodExchange;
use think\Db;

class Activity extends Model {

    protected $dateFormat = 'Y-m-d H:i:s';

    /*
     * 状态转文本 create 2018-05-30
     */

    public function getStatusTextAttr($value, $data) {

        $text = [
            '-1' => '已结束',
            '1' => '正常',
                // '2' => '已启用'
        ];
        return isset($text[$data['status']]) ? $text[$data['status']] : '-';
    }

    public function getBeginTimeDateAttr($value, $data) {
        return date('Y-m-d H:i:s', $data['begin_time']);
    }

    public function getEndTimeDateAttr($value, $data) {
        return date('Y-m-d H:i:s', $data['end_time']);
    }

    public function getIsSubscribeTextAttr($value, $data) {

        $text = [
            '0' => '否',
            '1' => '是',
                // '2' => '已启用'
        ];
        return isset($text[$data['is_subscribe']]) ? $text[$data['is_subscribe']] : '-';
    }

    public function getTimeAttr($value, $data) {
        $time = time();
        if ($data['begin_time'] < $time && $time < $data['end_time']) {
            return 1;
        }
        if ($time < $data['begin_time']) {
            return 2;
        } else {
            $id = input('id');
            $changestatus = $this->where('id', $id)->update(['status' => '-1']);
        }
    }

    /*
     * 添加活动数据检查验证 create 2018-05-30
     * @param array $data
     * @param $type(默认1) 1、抽奖 2、砍价 3、秒杀
     * @param $keyword 关键字
     * @return array
     */

    public function checkActivityData($data, $type = 1, $keyword = '抽奖') {
        $arr = [];
        $arr['type'] = $type;
        $arr['from_id'] = $data['from_id'];
        $arr['is_subscribe'] = $data['is_friends_subscribe'];
        $arr['name'] = $data['name'];
        $arr['keyword'] = $keyword;
        $arr['begin_time'] = $data['begin_time'];
        $arr['end_time'] = $data['end_time'];
        return $arr;
    }

    /*
     * 获取参与人数
     */

    public function getJoinActivityNum($id, $type) {
        $num = 0;
        switch ($type) {
            case 1:
                $num = (new ActivityTurntableRecord())->activityNumber($id);
                break;
            case 2:
                $num = (new ActivityBargain())->getParticipantsNumberAttr('', ['id' => $id, 'merchant_id' => session('merchant_id')]);
                break;
            case 3:
                $num = (new ActivitySpike())->getParticipantsNumberAttr('', ['id' => $id, 'merchant_id' => session('merchant_id')]);
                break;
        }
        return $num;
    }

    /*
     * 添加活动数据 create 2018-05-30
     * @param array $data
     * @return $id|false
     */

    public function addActivityData($data) {
        $data['merchant_id'] = session('merchant_id');
        $data['status'] = 1;
        $data['create_time'] = time();
        $res = $this->allowField(true)->save($data);
        if ($res === false) {
            return false;
        }
        return $this->id;
    }

    //活动活动信息
    public function getInfo($id) {
        return $this->where('id', $id)->find();
    }

    public function getInfos($page) {
        return $this->paginate(8, false, ['page' => $page]);
    }

    /**
     * 关闭活动并返回库存
     * 检测活动若超时关闭活动并返回库存
     * $type 必填 活动类型  1- 转盘 2-砍价 3-秒杀
     * $from_id 必填 活动附表ID根据不同类型对应不同表的ID 1- ActivityTurntable表 2-ActivityBargain表 3-ActivitySpike表
     * $activity_id 选填 [活动总表ID]
     */
    public function closeActivity($type, $from_id, $activity_id = '') {
        if (empty($type)) {
            return false;
        }
        if (empty($from_id)) {
            return false;
        }
        $activity_info = $this->where('from_id', $from_id)->where('type', $type)->find()->toArray();
        if (empty($activity_info)) {
            return false;
        }
        if (empty($activity_id)) {
            $activity_id = $activity_info['id'];
        }
        beginTransaction();
        //更新活动总表数据
        $del = $this->where('id', $activity_id)->update(['status' => '-1']);
        if (!$del) {
            rollbackTransaction();
            return false;
        }
        $res = TRUE;
        switch ($type) {
            //转盘
            case 1:
                //查询奖品数据
                $good_list = (new ActivityTurntablePrizeRelation)->where('activity_id', $activity_id)->where(['prize_from' => ['in', [1, 4, 6]]])->select()->toArray();
                $re = $this->updateSkuInc($good_list, 1,'turntable'); //还原奖品库存
                if (!$re) {
                    rollbackTransaction();
                    $res = false;
                }
                //更新奖品数据
                $delete_tpr = (new ActivityTurntablePrizeRelation)->where('activity_id', $activity_id)->update(['status' => '-1']);
                if (!$delete_tpr) {
                    rollbackTransaction();
                    $res = false;
                }
                //更新分表活动数据
                $delete_tp = (new ActivityTurntable)->where('id', $from_id)->update(['status' => '-1']);
                if (!$delete_tp) {
                    rollbackTransaction();
                    $res = false;
                }
                //如果活动是到结束时间关闭，停止所有兑奖操作
                $info_atr = (new ActivityTurntableRecord)->where(['activity_turntable_id' => $from_id, 'awards_status' => 2, 'prize_from' => ['neq', 5]])->find()->toArray();
                if ($info_atr) {
                    $atr_stop = (new ActivityTurntableRecord)->where(['activity_turntable_id' => $from_id, 'awards_status' => 2, 'prize_from' => ['neq', 5]])->update(['awards_status' => '-2', 'update_time' => time()]);
                    if (!$atr_stop) {
                        rollbackTransaction();
                        $res = false;
                    }
                }
                break;
            //砍价
            case 2:
                $good_list = (new ActivityGoodRelation)->where('activity_id', $activity_id)->select()->toArray();
                $re = $this->updateSkuInc($good_list, 2,'bargain'); //还原奖品库存
                if (!$re) {
                    rollbackTransaction();
                    $res = false;
                }
                //更新奖品数据
                $delete_gt = (new ActivityGoodRelation)->where('activity_type', 'bargain')->where('activity_id', $activity_id)->update(['status' => '-1','update_time'=>time()]);
                if (!$delete_gt) {
                    rollbackTransaction();
                    $res = false;
                }
                $delete_gt = (new ActivityBargain)->where('id', $from_id)->update(['status' => '-1','update_time'=>time()]);
                if (!$delete_gt) {
                    rollbackTransaction();
                    $res = false;
                }
                break;
            //秒杀
            case 3:
                $good_list = (new ActivityGoodRelation)->where('activity_id', $activity_id)->select()->toArray();
                $re = $this->updateSkuInc($good_list, 3,'spike'); //还原奖品库存
                if (!$re) {
                    rollbackTransaction();
                    $res = false;
                }
                //更新奖品数据
                $delete_gr = (new ActivityGoodRelation)->where('activity_type', 'spike')->where('activity_id', $activity_id)->update(['status' => '-1','update_time'=>time()]);
                if (!$re) {
                    rollbackTransaction();
                    $res = false;
                }
                $delete_gt = (new ActivitySpike)->where('id', $from_id)->update(['status' => '-1','update_time'=>time()]);
                if (!$delete_gr) {
                    rollbackTransaction();
                    $res = false;
                }
                break;
            default :
                $res = false;
                break;
        }
        if ($res) {
            commitTransaction();
        }
        return $res;
    }

    /*
     * 还原商品库存
     */

    public function updateSkuInc($list, $type,$in_area) {
        if ($type == 1) {//抽奖奖品
            foreach ($list as $key => $val) {
                if ($val['leave_sku'] > 0) {
                    if ($val['prize_from'] == 1) {
                        if (!(new Good)->returnInareaQuantity($val['merchant_id'], $val['prize_from_id'], $in_area , $val['leave_sku'])) {
                            return false;
                            break;
                        }
                    }
                    if ($val['prize_from'] == 6) {
                        if (!(new GoodExchange)->updateSkuInc(['merchant_id' => $val['merchant_id'], 'id' => $val['prize_from_id']], $val['leave_sku'])) {
                            return false;
                            break;
                        }
                    }
                    if ($val['prize_from'] == 4) {
                        if (!(new Card)->updateSkuInc(['merchant_id' => $val['merchant_id'], 'id' => $val['prize_from_id']], $val['leave_sku'])) {
                            return false;
                            break;
                        }
                    }
                }
            }
        } else {
            
            foreach ($list as $key => $val) {
                if ($val['stock_num'] > 0) {
                    if (!(new Good)->returnInareaQuantity($val['merchant_id'], $val['good_id'], $in_area, $val['stock_num'])) {
                        return false;
                        break;
                    }
                }
            }
        }
        return true;
    }

}
