<?php
namespace app\mp\validate;

use think\Validate;

class ActivityPrize extends Validate
{
    protected $rule = [
        'name'  => 'require',
        'image'  => 'require',
        'worth'  => 'require|regex:/^\+?[1-9][0-9]*$/',
        'description'  => 'require',
    ];

    protected $message = [
        'name.require'   => '奖品名称必须填写！',
        'image.require'  => '奖品图必须上传！',
        'description.require'  => '奖品说明必须填写！',
        'worth.require'  => '奖品价值必须填写！',
        'worth.regex'  => '奖品价值数据填写错误！',
    ];
}