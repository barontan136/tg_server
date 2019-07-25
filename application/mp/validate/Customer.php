<?php
namespace app\mp\validate;

use think\Validate;

class Customer extends Validate
{
    protected $rule = [
        'name'  => 'require',
        'telephone'  => 'require|checkTelephone:thinkphp',
        'bonus'  => 'require',
    ];

    protected $message = [
        'name.require'   => '客户名称必须填写!',
        'telephone.require'  => '客户电话必须填写!',
        'bonus.require'  => '客户积分必须填写！',
    ];
     /**
     * 检测联系电话
     */
    protected function checkTelephone($value,$rule,$data){
        $regexs = "/^1[3-9]{1}[0-9]{9}$/";
        $regex = "/^([0-9]{3,4}-)?[0-9]{7,8}$/";
        if(preg_match($regex,$value) || preg_match($regexs,$value)){
            return true;
        }
        return "联系方式格式错误！";
    }
}