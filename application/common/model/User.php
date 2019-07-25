<?php
namespace app\common\model;

use think\Model;
use app\common\model\WechatFansTags;
class User extends Model
{
    
    public function getUpdateTimeTextAttr($value,$data){
        return date('Y-m-d H:i:s',$data['update_time']);
    }
    public function getNicknameAttr($value){
        return emojiDecode($value);
    }
    public function setNicknameAttr($value){
        return emojiEncode($value);
    }
    
    public function getCreateTimeTextAttr($value,$data){
        return date('Y-m-d H:i:s',$data['create_time']);
    }
    public function getSexTextAttr($value,$data){
        $sex = [1=>'男',2=>'女',0=>'未知'];
        return $sex[$data['sex']];
    }
     public function getSubscribeTextAttr($value, $data) {
        $sub = [1 => '<span style="color:#e90d24">已关注</span>',0 => '已取关'];
        return $sub[$data['subscribe']];
    }
     public function getIsBackTextAttr($value, $data) {
        $back = [1 => '<span style="color:#F7B824">是</span>',0 => '否'];
        return $back[$data['is_back']];
    }
     public function getAreaTextAttr($value, $data){
        if(empty($data['country'])){
            return '<span style="color:#999">未设置区域信息</span>';
        }else{
            $adr=empty($data['city'])?$data['country'].$data['province']:$data['country'].$data['city'];
            return $adr;
        }
    }
    
    /**
     * create 07-06
     * @param type $value
     * @param type $data
     */
    public function getSubscribeTimeDateAttr($value,$data){
        $subscribe_time = $data['subscribe_time'];
        if(empty($subscribe_time)){
            return '--';
        }
        return date('Y-m-d H:i:s',$subscribe_time);
    }
    
    /**
     * create 07-06 是否取消关注 先决条件是subscribe==1
     * @param type $value
     * @param type $data
     */
    public function getUnsubscribeTextAttr($value, $data){
        if($data['subscribe']==0 && !empty($data['unsubscribe_time'])){
            return '<span style="color:#F7B824">是</span>';
        }
        return '否';
        
    }
    
    /**
     * create 07-06
     * @param type $value
     * @param type $data
     */
    public function getUnsubscribeTimeDateAttr($value,$data){
        if($data['subscribe']){
            return '--';
        }
        $unsubscribe_time = $data['unsubscribe_time'];
        if(!empty($unsubscribe_time) && $data['subscribe'] != 1){
            return date('Y-m-d H:i:s',$unsubscribe_time);
        }
        return '--';
    }
    public function setStatusAttr(){
        return 1;
    }
    
    public function initUser($data=[],$appid=""){
       $user = $this->where('openid',$data['openid'])->field('id,create_time,subscribe_num')->find()->toArray();
        if($user){
            $data = array_merge($user,$data);
            $userinfo = getWeObject($appid)->getUserInfo($data['openid']);
            if(!empty($userinfo['subscribe'])){
            $data = array_merge($data,
                    [
                        'nickname'          => $userinfo['nickname'],
                        'sex'               => intval($userinfo['sex']),
                        'subscribe'         => intval($userinfo['subscribe']),
                        'subscribe_time'    => $userinfo['subscribe_time'],
                        'province'          => $userinfo['province'],
                        'city'              => $userinfo['city'],
                        'country'           => $userinfo['country'],
                        'tagid_list'        => array2string($userinfo['tagid_list']),
                        'headimgurl'        => $userinfo['headimgurl']
                    ]);
            }
            $this->save($data,['id'=>$user['id']]);
        }else{
            $data['create_time'] = time();
            $userinfo = getWeObject($appid)->getUserInfo($data['openid']);
            if(!empty($userinfo['subscribe'])){
            $data = array_merge($data,
                    [
                        'nickname'          => $userinfo['nickname'],
                        'sex'               => intval($userinfo['sex']),
                        'subscribe'         => intval($userinfo['subscribe']),
                        'subscribe_time'    => $userinfo['subscribe_time'],
                        'province'          => $userinfo['province'],
                        'city'              => $userinfo['city'],
                        'country'           => $userinfo['country'],
                        'tagid_list'        => array2string($userinfo['tagid_list']),
                        'headimgurl'        => $userinfo['headimgurl']
                    ]);
            }
            $this->allowField(true)->save($data);
            $data['id'] = $this->id;
        }
        return $data;
    }
    
    public function getTagTextAttr($value,$data){
        if(empty($data['tagid_list']))return '<span style="color:#999">未分组</span>';
        $names = (new WechatFansTags())->where('id','in',  string2array($data['tagid_list']))->column('name');
        return array2string($names);
    }
    
    /**
     * 获取用户的openid
     */
    public function getOpenid($user_id){
        return $this->where(['id'=>$user_id])->value('openid');
    }
    /**
     * 获取用户的openid
     */
    public function getUserId($openid){
        return $this->where(['openid'=>$openid])->value('id');
    }
}