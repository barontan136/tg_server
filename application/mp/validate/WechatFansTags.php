<?php
namespace app\mp\validate;

use think\Validate;

class WechatFansTags extends Validate
{
    protected $rule = [
        'name|appid' => 'require|unique:wechat_fans_tags',
    ];

    protected $message = [
        'name.require' => '请输入标签名称',
        'name.unique' => '标签名称不能重复',
    ];
}