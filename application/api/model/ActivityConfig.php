<?php

namespace app\api\model;

use think\Model;

class ActivityConfig extends Model {

    public function getUpdateTimeTextAttr($value, $data) {
        return date('Y-m-d H:i:s', $data['update_time']);
    }

    public function getCreateTimeTextAttr($value, $data) {
        return date('Y-m-d H:i:s', $data['create_time']);
    }

    public function getLastID() {
        return $this->id ?? false;
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

}
