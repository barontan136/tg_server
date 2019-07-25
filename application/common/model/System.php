<?php
namespace app\common\model;

use think\Model;

class System extends Model
{
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    /**
     * 获取分组
     */
    public function getGroup(){
        $group = $this->where(['group'=>['neq',0]])->group('`group`')->column('group');
        return $group;
    }
    /**
     * 根据分组获取配置项
     * @param type $value
     * @param type $data
     * @return type
     */
    public function getList($group){
        $map['group'] = ['neq',0];
        if(!empty($group)){
            $map['group'] = ['eq',$group];
        }
            $list = $this->where($map)->order('sort')->select();
            return $list;
    }
    
     public function getUpdateTimeTextAttr($value,$data){
        return date('Y-m-d H:i:s',$data['update_time']);
    }
    
    public function getCreateTimeTextAttr($value,$data){
        return date('Y-m-d H:i:s',$data['create_time']);
    }
    
    public function getTypeTextAttr($value,$data){
        $type = [1=>'输入',2=>'选择',3=>'数组'];
        return $type[$data['type']];
    }
    
    public function getExtraTextAttr($value,$data){
        if(empty($data['extra'])){
            return '';
        }else{
            return parse_config_attr($data['extra']);
        }
    }
    public function getValueTextAttr($value,$data){
        if($data['type']!=3){
            return $data['value'];
        }else{
            return parse_config_attr($data['value']);
        }
    }
    //获取配置参数
    public function getConfig(){
        $map['group'] = ['neq',0];
        $list = $this->where($map)->field('name,value,type')->select();
        foreach ($list as $v){
            //保证key不重复
            $config[$v['name']]=$v['value_text'];
        }
        return $config;
    }
    
}