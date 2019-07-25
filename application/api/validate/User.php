<?php
namespace app\api\validate;
use think\Validate;

/**
 */
class User extends Validate{
    
    protected $rule = [
        'name'=>'require',
        'telephone'=>'require|checkTelephone:thinkphp',
        'birthday'=>'require',
        'xaddress'=>'require',
    ];
    
    protected $message = [
        'name.require'=>'姓名需要填写！',
        'telephone.require'=>'电话需要填写！',
        'birthday.require'=>'生日需要填写！',
        'xaddress.require'=>'地址需要填写！',
    ];
    
    protected $scene = [
        'update_info'  =>  ['name','telephone','xaddress','birthday'],

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
        return "手机号格式错误！";
    }
}
