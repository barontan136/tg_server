<?php

/**
 * Created by PhpStorm.
 * User: haichang
 * Date: 2019-05-03
 * Time: 15:39
 */

namespace app\api\model;

class ActivityGroupOrder extends BaseModel {

    protected $table = 'ls_activity_group_order';
    protected $insert = ['status' => 1];

    /**
     * 更新数据
     */
    public function updateInfo($data, $id, $where) {
        $data['update_time'] = date('Y-m-d H:i:s');
        $res = $this->allowField(true)->where('id', $id)->where($where)->update($data);
        if ($res === false) {
            return false;
        }
        return true;
    }

    /**
     * 更新数据
     */
    public function updateInfobyOutTradeNo($data, $out_trade_no, $where) {
        $data['update_datetime'] = date('Y-m-d H:i:s');
        writerLog('model');
        $res = $this->allowField(true)->where('out_trade_no', $out_trade_no)->where($where)->update($data);
        writerLog($res);
        if ($res === false) {
            return false;
        }
        return true;
    }

    public function updateOutTradeNo($id, $out_trade_no) {
        $res = $this->allowField(true)->where('id', $id)->update(['out_trade_no' => $out_trade_no]);
        if ($res === false) {
            return false;
        }
        return true;
    }

}
