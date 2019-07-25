<?php

namespace app\mp\model;

use think\Model;

class WechatPayConfig extends Model {

    public function getUpdateTimeTextAttr($value, $data) {
        return date('Y-m-d H:i:s', $data['update_time']);
    }

    public function getCreateTimeTextAttr($value, $data) {
        return date('Y-m-d H:i:s', $data['create_time']);
    }

    public function getInfo($where, $field) {
        $info = $this->where($where)->field($field)->find()->toArray();
        return $info;
    }

}
