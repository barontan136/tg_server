<?php
namespace app\mp\validate;

use think\Validate;

class ActivityTurntablePrizeRelation extends Validate
{
    protected $rule = [
//        'prize_from'  => 'require',
//        'prize_from_id'  => 'require',
        'prize_value'  => 'require',
        'name'  => 'require',
        'probability'  => 'require',
        'amount'  => 'require',
    ];

    protected $message = [
        'prize_value.require'   => '奖品价值不能为空！',
        'name.require'   => '奖品名称不能为空！',
        'probability.require'  => '概率需要填写！',
        'amount.require'  => '奖品数量需要填写！',
    ];
}