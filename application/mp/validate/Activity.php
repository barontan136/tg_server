<?php
namespace app\mp\validate;

use think\Validate;

class Activity extends Validate
{
    protected $rule = [
        'title'  => 'require',
        'images'  => 'require',
        'url'  => 'require|regex:/http(s)?:\/\/([\w-]+\.)+[\w-]+(\/[\w- .\/?%&=]*)?/',
        'begin_time'  => 'require',
        'end_time'  => 'require',
        'sort'=>'require|egt:0'
    ];

    protected $message = [
        'title.require'   => '标题必须填写！',
        'images.require'  => '活动图必须上传！',
        'begin_time.require'  => '活动时间不能为空！',
        'url.require'  => '活动地址不能为空！',
        'url.regex'  => '活动地址格式不正确！',
        'end_time.require'  => '活动结束时间必须填写！',
        'sort.require'  => '请填写排序值！',
        'sort.egt'  => '请填写大于等于0的整数排序值！',
    ];
}