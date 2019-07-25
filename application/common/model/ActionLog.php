<?php
namespace app\common\model;
use think\Model;
use think\Request;

class ActionLog extends Model
{
    
    
    public function getUpdateTimeTextAttr($value,$data){
        return date('Y-m-d H:i:s',$data['update_time']);
    }
    
    public function getCreateTimeTextAttr($value,$data){
        return date('Y-m-d H:i:s',$data['create_time']);
    }
    
    //获取操作记录
    public function getPage($page,$condition){
            return $this
                 ->alias('l')
                 ->join('admin_user au','au.id = l.admin_user_id')
                 ->order(['l.create_time' => 'DESC'])
                 ->field('l.*,au.username')
                 ->where($condition)
                 ->paginate(15, false, ['page' => $page]);
    }
    
    //添加操作日志
    public function addLog($type=1,$title='系统操作',$remark=""){
        $request = Request::instance();
        $data = [
            'title'                => $title,
            'admin_user_id'        => !empty(session('merchant_id'))?session('merch_admin_id'):session('admin_id'),
            'merchant_id'          => session('merchant_id'),
            'url'                  => $request->url(),
            'type'                 => $type,
            'ip'                   => $request->ip(),
            'remark'               => empty($remark)?$title:$remark
        ];
        $this->save($data);
    }
    
}