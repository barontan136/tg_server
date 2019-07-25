<?php

namespace app\api\model;

use think\Log;
use think\Model;
use app\api\model\Customer as Cus;
use app\common\model\ComponentAppid;
use app\common\model\Merchant;

class User extends Model {

    public $ref_user_id = 0;

    public function getUpdateTimeTextAttr($value, $data) {
        return date('Y-m-d H:i:s', $data['update_time']);
    }

    public function getNicknameAttr($value) {
        return emojiDecode($value);
    }

    public function setNicknameAttr($value) {
        return emojiEncode($value);
    }
     public function setPasswordAttr($value){
        return md5($value.config('salt'));
    }
    public function getCreateTimeTextAttr($value, $data) {
        return date('Y-m-d H:i:s', $data['create_time']);
    }

    public function getSexTextAttr($value, $data) {
        $sex = [1 => '男', 2 => '女', 0 => '未知'];
        return $sex[$data['sex']];
    }

    public function getSubscribeTextAttr($value, $data) {
        $sub = [1 => true, 0 => FALSE];
        return $sub[$data['subscribe']];
    }

    public function getIsBackTextAttr($value, $data) {
        $back = [1 => '<span style="color:#F7B824">是</span>', 0 => '否'];
        return $back[$data['is_back']];
    }

    public function getAreaTextAttr($value, $data) {
        if (empty($data['country'])) {
            return '未设置区域信息';
        } else {
            return $data['country'] . $data['city'];
        }
    }

    public function initUser($data = []) {
        $user = $this->where('openid', $data['openid'])
            ->field('id,name,telephone,birthday,xaddress,create_time,is_bind,subscribe')->find()->toArray();
        if ($user) {
            $data = array_merge($user, $data);
            $this->allowField(true)->save($data, ['id' => $user['id']]);
        } else {
            $data['create_time'] = time();
            $this->allowField(true)->save($data);
            $data['id'] = $this->id;
        }
        return $data;
    }

    /**
     * 获取用户的openid
     */
    public function getOpenid($user_id) {
        return $this->where(['id' => $user_id])->value('openid');
    }
    /**
     * 获取用户信息
     */
    public function getInfo($user_id) {
        return $this->where(['id' => $user_id])->find()->toArray();
    }

    /**
     * 获取用户的openid
     */
    public function getUserId($openid) {
        return $this->where('openid', $openid)->value('id');
    }

    /**
     * 通过code获取user
     */
    public function getUserByCode($code) {
        $weObj = getWeObject();
        $json = $weObj->getOauthAccessTokenComponent($weObj->appid,$code);
        if (empty($json))
            return false;
        $openid = $json['openid'];
        $userinfo = $weObj->getComponentUserInfo($weObj->appid,$openid);
        $merchant_id = (new ComponentAppid())->getMerchantIdByAppid($weObj->appid);
        $name = (new Merchant())->where('id',$merchant_id)->value('name');
        if ($userinfo) {
            $wxuser = ['openid' => $openid,'merchant_id'=>$merchant_id,'merchant_name'=>$name];
            if (!empty($userinfo['subscribe'])) {
                $wxuser = array_merge($wxuser, [
                    'nickname' => $userinfo['nickname'],
                    'sex' => intval($userinfo['sex']),
                    'subscribe' => intval($userinfo['subscribe']),
                    'province' => $userinfo['province'],
                    'city' => $userinfo['city'],
                    'country' => $userinfo['country'],
                    'headimgurl' => $userinfo['headimgurl']
                ]);
            }
            $user = $this->initUser($wxuser);
            return $this->formatUser($user);
        } else {
            return false;
        }
    }

    //通过openid获取user
    public function getUserByOpenid($openid) {
        $user = $this->where('openid', $openid)->find()->toArray();
        return empty($user) ? [] : $this->formatUser($user);
    }

    /**
     * 用户格式化
     */
    public function formatUser($user) {
        $format = [
            'user_id' => $user['id'] ?? '',
            'openid' => $user['openid']??'',
            'isSubscribe' => false,
            'register_time' => $this->getCreateTimeTextAttr('', $user)
        ];
        empty($user['telephone'])?$format['telephone']='':$format['telephone']=$user['telephone'];
        empty($user['birthday'])?$format['birthday']='':$format['birthday']=date('Y-m-d',$user['birthday']);
        empty($user['name'])?$format['name']='':$format['name']=$user['name'];
        empty($user['xaddress'])?$format['xaddress']='':$format['xaddress']=  json_decode($user['xaddress'],true);
        empty($user['is_bind'])?$format['is_bind']=0:$format['is_bind']= $user['is_bind'];
        empty($user['subscribe'])?$format['subscribe']=0:$format['subscribe']= $user['subscribe'];
        if (!empty($user['nickname']) || !empty($user['headimgurl'])) {
            $format = array_merge($format, [
                'headimgurl' => $user['headimgurl'],
                'nickname' => $user['nickname'],
                'isSubscribe' => $this->getSubscribeTextAttr('', $user),
                'sex' => $this->getSexTextAttr('', $user),
                'area' => $this->getAreaTextAttr('', $user),
            ]);
        }
        return $format;
    }
    
    /*****************************************测试环境用此方法获取openid**************************************************/
    /**
     * 从微信获取customer
     */
    public function wxAuth($authKey, $scope = '') {
        $wxuser = session($authKey . "_wxuser");
        $weObj = getWeObject();
//        $wxuser = empty($wxuser) ? $weObj->wxOAuth2Component($weObj->appid,$scope) : $wxuser;
        $wxuser = empty($wxuser) ? $weObj->wxOAuth2($scope) : $wxuser;
        writerLog($wxuser);
        if ($wxuser) {
            $wxuser['ref_user_id'] = $this->ref_user_id;
            session($authKey . '_wxuser', $wxuser);
            $wxuser['last_login_time'] = time();
            $user = $this->initUser($wxuser);
            $result = $this->formatUser($user);
            cache($authKey . '_user', $result);
            return $result;
        } else {
            return false;
        }
    }
    
     /**
     * 根据authKey获取用户信息
     */
    public function customerAuthKey($authKey) {
        if (empty($authKey))
            return false;
        $user = cache($authKey . '_user');
        if (empty($user))
            return FALSE;
        //获取之后立即清除
        cache($authKey . '_user', null);
        return $user;
    }

     /**
     * 绑定手机号时更新用户数据
     */
    public function bindTelephoneUpdate($user_id,$telephone,$name) {
       return $this->where(['id'=>$user_id])->update(['telephone'=>$telephone,'is_bind'=>1,'name'=>$name,'update_time'=>  time()]);
    }
    
    /*
     * 用户更新信息
     */
    public function userInfoUpdate($data){
        $user=$this->where(['id'=>$data['user_id']])->find()->toArray();
        if($user){
            $update['name']=$data['name'];
            $update['birthday']= strtotime($data['birthday']." 00:00:00");
            $update['xaddress']=  json_encode($data['xaddress']);
            $update['update_time']= time();
           //判断是否绑定
           beginTransaction();
//            $customer_info=(new Customer())->where(['user_id'=>$data['user_id']])->find()->toArray();
            if($user['is_bind']==0){
              $update['telephone']= $data['telephone']; 
              $re['telephone']=  $data['telephone'];
            }else{
                $updtat_cu['birthday']= strtotime($data['birthday']." 00:00:00");
                $updtat_cu['name']=$data['name'];
                $updtat_cu['update_time']=  time();
               if(!(new Customer())->where(['user_id'=>$data['user_id']])->update($updtat_cu)){
                   rollbackTransaction(); 
                    return false;
               }
            }
            if($this->where(['id'=>$data['user_id']])->update($update)){
                $re['nickname']=$user['nickname'];
                $re['openid']=$user['openid'];
                $re['name']=$data['name'];
                $re['birthday']=$data['birthday'];
                $re['xaddress']= json_decode($update['xaddress'],true);
                commitTransaction();
                return  $re; 
            }else{
                rollbackTransaction();
                return false; 
            }
           
        }else{
           return false;  
        }
    }
    /*
     * 获取用户更新信息
     */
    public function getUserInfo($user_id){
        $user=$this->where(['id'=>$user_id])->find()->toArray();
        if($user){
           //判断是否绑定
            $re['nickname']=$user['nickname'];
            $re['is_bind']=$user['is_bind'];
            $re['openid']=$user['openid'];
            $re['name']=$user['name'];
            $re['birthday']=date('Y-m-d',$user['birthday']);
            $re['xaddress']= json_decode($user['xaddress'],true);
            $re['telephone']=  $user['telephone'];
            return  $re; 
        }else{
           return false;  
        }
    }


    public function getUserInfoById($user_id) {
        return $this->where('id', $user_id)->find()->toArray();
    }

}
