<?php

namespace app\mp\model;

use think\Model;

/**
 * 管理员模型
 * Class AdminUser
 * @package app\common\model
 */
class AdminUser extends Model {

    protected $insert = ['create_time'];

    public function getSexTextAttr($value, $data) {
        $sex = [1 => '男', 2 => '女', 3 => '保密'];
        return $sex[$data['sex']];
    }

    

    /**
     * 判断是否有员工
     */
    public function isAdminUser($name, $mobile) {
        $result = $this->where('name', $name)->where('mobile', $mobile)->value('status');
        if ($result) {
            if ($result == 1) {
                return true;
            }
            return $result;
        }
        return FALSE;
    }

}
