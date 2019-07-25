<?php
namespace app\mp\validate;

use think\Validate;

/**
 * 管理员验证器
 * Class AdminUser
 * @package app\mp\validate
 */
class System extends Validate
{
    protected $rule = [
        'name'         => 'require|unique:system',
        'title'         => 'require|unique:system',
    ];

    protected $message = [
        'name.require'         => '请输入配置常量',
        'name.unique'          => '配置常量已存在',
        'title.require'         => '请输入配置名称',
        'title.unique'          => '配置名称已经存在',
    ];
}