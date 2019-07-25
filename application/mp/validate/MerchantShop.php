<?php
namespace app\mp\validate;

use think\Validate;

class MerchantShop extends Validate
{
    protected $rule = [
        'name'=>'require',
        'logo'=>'require',
        'xaddress'=>'require',
        'address'=>'require',
        'photos'=>'require',
        'shop_phone'=>'require',
        'shop_phone'  => 'require|checkTelephone:thinkphp',
    ];

    protected $message = [
        'name.require'   => '请填写门店名称',
        'logo.require'   => '请上传门店logo图',
        'xaddress.require'   => '请选择城市',
        'address.require'   => '请输入详细地址',
        'photos.require'  => '请上传门店图片集',
        'shop_phone.require'   => '门店联系方式必填',
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
        return "门店联系方式格式错误！";
    }
}