<?php
namespace app\mp\validate;

use think\Validate;

/**
 * 管理员验证器
 * Class AdminUser
 * @package app\mp\validate
 */
class AdminUser extends Validate
{
    protected $rule = [
        'username'         => 'require|unique:admin_user',
        'mobile'         => 'require',
        'openid'         => 'unique:admin_user',
        'password'         => 'confirm:confirm_password',
        'confirm_password' => 'confirm:password',
        'status'           => 'require',
        'group_ids'         => 'require'
    ];

    protected $message = [
        'openid.unique'         => '此微信已绑定过账号，请更换',
        'username.require'         => '请输入用户名',
        'username.unique'          => '用户名已存在',
        'mobile.require'         => '请输入电话号码',
        'password.confirm'         => '两次输入密码不一致',
        'confirm_password.confirm' => '两次输入密码不一致',
        'status.require'           => '请选择状态',
        'group_ids.require'         => '请选择所属权限组'
    ];
    protected $scene = [
        'info' =>['openid']
    ];
}