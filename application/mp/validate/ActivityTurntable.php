<?php
namespace app\mp\validate;

use think\Validate;

class ActivityTurntable extends Validate
{
    protected $rule = [
        'name'  => 'require',
        'begin_time'  => 'require',
        'end_time'  => 'require',
        'total_day'  => 'require',
        'share_number'  => 'require',
        'share_bonus'  => 'require',
        'distribution_type'  => 'require',
        'explain_rule'  => 'require',
    ];

    protected $message = [
        'name.require'   => '活动名称需要填写！',
        'begin_time.require'  => '开始时间需要填写！',
        'end_time.require'  => '结束时间需要填写！',
        'total_day.require'  => '每天没人获得机会数需要填写！',
        'share_number.require'  => '每次分享获得机会数需要填写！',
        'share_bonus.require'  => '分享最多获得机会次数需要填写！',
        'distribution_type.require'  => '提货方式必须选择！',
        'explain_rule.require'  => '活动规则说明需要填写！',
    ];
}