<?php

namespace app\mp\model;

use think\Model;

class WechatBaseConfig extends Model {

    public function getUpdateTimeTextAttr($value, $data) {
        return date('Y-m-d H:i:s', $data['update_time']);
    }

    public function getCreateTimeTextAttr($value, $data) {
        return date('Y-m-d H:i:s', $data['create_time']);
    }

    public function getInfo($where, $field) {
        $info = $this->where($where)->field($field)->find()->toArray();
        !empty($info['create_time']) && $info['create_time'] = $this->getCreateTimeTextAttr([], $info);
//        !empty($info['qrcode_url']) && $info['qrcode_url_cover'] = getCover($info['qrcode_url']);
        return $info;
    }

}
