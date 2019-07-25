<?php

namespace app\api\model;

use think\Model;

class ActivityUserPosterRecord extends Model {

    public function getUpdateTimeTextAttr($value, $data) {
        return date('Y-m-d H:i:s', $data['update_time']);
    }

    public function getCreateTimeTextAttr($value, $data) {
        return date('Y-m-d H:i:s', $data['create_time']);
    }

    public function getLastID() {
        return $this->id ?? false;
    }

}
