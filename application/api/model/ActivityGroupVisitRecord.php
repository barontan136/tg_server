<?php
/**
 * Created by PhpStorm.
 * User: haichang
 * Date: 2019-05-03
 * Time: 15:39
 */

namespace app\api\model;


class ActivityGroupVisitRecord extends BaseModel
{
    protected $table = 'ls_activity_group_visit_record';

    protected $insert = ['status' => 1];
}