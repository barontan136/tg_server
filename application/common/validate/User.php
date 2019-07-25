<?php
namespace app\common\validate;

use think\Validate;
class User extends Validate
{
    protected $rule = [
        'username'         => 'unique:user',
        'mobile'           => 'number|length:11',
        'email'            => 'email',
        'openid'           => 'require|unique:user',
    ];

    protected $message = [
        'username.unique'          => '用户名已存在',
        'mobile.number'            => '手机号格式错误',
        'mobile.length'            => '手机号长度错误',
        'email.email'              => '邮箱格式错误',
        'openid.require'           => 'openid不能为空',
        'openid.unique'           => 'openid已经存在'
    ];
}