<?php
namespace app\mp\validate;

use think\Validate;

class ActivityBargain extends Validate
{
    protected $rule = [
        'name'                      => 'require',
        'begin_time'                => 'require',
        'end_time'                  => 'require',
        'in_hour'                   => 'require',
        'max_number'                => 'require|number',
        'good_id'                   => 'require',
        'amount'                    => 'require',
        'deal_price'                    => 'require',
        'explain_rule'              => 'require',
    ];

    protected $message = [
        'name.require'                      => '活动名称必须填写！',
        'begin_time.require'                => '请选择活动开始时间！',
        'end_time.require'                  => '请选择活动结束时间！',
        'in_hour.require'                   => '请输入砍价时间！',
        'max_number.require'                => '请输入最多砍价次数！',
        'max_number.number'                => '砍价次数为数字！',
        'good_id.require'                   => '请添加砍价商品',
        'amount.require'                    => '请输入商品数量',
        'deal_price.require'                => '请输入最低成交价',
        'explain_rule.require'              => '请输入活动规则说明',
    ];
}