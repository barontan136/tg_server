<?php
/**
 * Created by PhpStorm.
 * User: haichang
 * Date: 2019-05-03
 * Time: 15:42
 */

namespace app\api\validate;


use think\Validate;

class ActivityGroupOrder extends Validate
{
    protected $rule = [
        'isGroup'  =>  'require|max:64',
        'userId'  =>  'require|max:64',
        'refUserId'  =>  'require|max:64',
        'activityId'  =>  'require|max:64',
        'username' =>  'require|max:10',
        'phone' =>  'require|length:11',
    ];

    protected $message  =   [
        'activityId.require' => '活动ID必须',
        'activityId.max'     => '名称最多不能超过64个字符',
        'userId.require' => '访问用户ID必须',
        'userId.max'     => '访问用户ID最多不能超过64个字符',
        'refUserId.require' => '邀请人ID必须',
        'refUserId.max'     => '邀请人ID最多不能超过64个字符',
        'isGroup.require' => 'isGroup必须',
        'isGroup.max'     => 'isGroup最多不能超过64个字符',
        'username.require' => '姓名必须',
        'username.max'     => '姓名最多不能超过10个字符',
        'phone.require' => '手机号必须',
        'phone.max'     => '手机号码必须为11位',
    ];
}