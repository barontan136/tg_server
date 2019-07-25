<?php
/**
 * Created by PhpStorm.
 * User: haichang
 * Date: 2019-05-03
 * Time: 15:42
 */

namespace app\api\validate;


use think\Validate;

class ActivityGroupVisitRecord extends Validate
{
    protected $rule = [
        'activity_id'  =>  'require|max:64',
        'user_id' =>  'require|max:64',
        'ref_user_id' =>  'max:64',
    ];

    protected $message  =   [
        'activity_id.require' => '活动ID必须',
        'activity_id.max'     => '名称最多不能超过64个字符',
        'user_id.require' => '访问用户ID必须',
        'user_id.max'     => '访问用户ID最多不能超过64个字符',
        'ref_user_id.require' => '邀请人ID必须',
        'ref_user_id.max'     => '邀请人ID最多不能超过64个字符',
    ];
}