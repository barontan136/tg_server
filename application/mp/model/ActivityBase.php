<?php

namespace app\mp\model;

use think\Model;

class ActivityBase extends Model {

    public function getUpdateTimeTextAttr($value, $data) {
        return date('Y-m-d H:i:s', $data['update_time']);
    }

    public function getCreateTimeTextAttr($value, $data) {
        return date('Y-m-d H:i:s', $data['create_time']);
    }

    public function getInfo($where, $field) {
        $info = $this->where($where)->field($field)->find()->toArray();
        !empty($info['create_time']) && $info['create_time'] = $this->getCreateTimeTextAttr([], $info);
        !empty($info['qrcode_url']) && $info['qrcode_url'] = getCover($info['qrcode_url']);
        return $info;
    }

    public function getLastID() {
        return $this->id ?? false;
    }

    public function getStatusNameAttr($data, $value) {
        $status = [
            '1' => '正常',
            '0' => '禁用'
        ];
        return isset($status[$value['status']]) ? $status[$value['status']] : '禁用';
    }
    
    public function getActNameAttr($data,$value)
    {
        $act_name=[1=>'团购',2=>'单独购买',3=>'兼职'];
        return $act_name[$value['act_type']]??'团购';
    }

}
