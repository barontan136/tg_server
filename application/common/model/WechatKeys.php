<?php
namespace app\common\model;

use think\Model;

class WechatKeys extends Model
{
    protected $auto = ['appid'];
    public function getUpdateTimeTextAttr($value,$data){
        return date('Y-m-d H:i:s',$data['update_time']);
    }
    
    public function getCreateTimeTextAttr($value,$data){
        return date('Y-m-d H:i:s',$data['create_time']);
    }
    
    public function setAppidAttr(){
        return config('wechat_appid');
    }
    
    public function getTypeTextAttr($value,$data){
        $types = ['keys' => '关键字', 'image' => '图片', 'news' => '图文', 'music' => '音乐', 'text' => '文字', 'video' => '视频', 'voice' => '语音'];
        return $types[$data['type']];
    }
}