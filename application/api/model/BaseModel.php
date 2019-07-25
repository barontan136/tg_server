<?php
/**
 * Created by PhpStorm.
 * User: haichang
 * Date: 2019-05-03
 * Time: 18:06
 */

namespace app\api\model;


use app\api\traits\FieldsMapTrait;
use app\api\traits\UncamelizeTrait;
use think\Model;

class BaseModel extends Model
{
    use FieldsMapTrait, UncamelizeTrait;
    protected $createTime = 'create_datetime';
    // 更新时间字段
    protected $updateTime = 'update_datetime';

    protected $type = [
        'create_datetime'  =>  'datetime',
        'update_datetime'  =>  'datetime',
    ];

    protected $dateFormat = 'Y-m-d H:i:s';
}