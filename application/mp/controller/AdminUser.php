<?php
namespace app\mp\controller;

use app\common\model\AdminUser as AdminUserModel;
use app\common\model\AuthGroup as AuthGroupModel;
use app\common\model\PhoneAuthGroup as PhoneAuthGroupModel;
use app\common\model\AuthGroupAccess as AuthGroupAccessModel;
use app\common\model\PhoneAuthGroupAccess as PhoneAuthGroupAccessModel;
use app\common\model\AdminUserWechat;
use app\common\controller\MpBase;

/**
 * 管理员管理
 * Class AdminUser
 * @package app\mp\controller
 */
class AdminUser extends MpBase
{
    protected $admin_user_model;
    protected $auth_group_model;
    protected $auth_group_access_model;
    protected $phone_auth_group_model;
    protected $phone_auth_group_access_model;
    protected $admin_user_wechat_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->admin_user_model        = new AdminUserModel();
        $this->auth_group_model        = new AuthGroupModel();
        $this->auth_group_access_model = new AuthGroupAccessModel();
        $this->phone_auth_group_model        = new PhoneAuthGroupModel();
        $this->phone_auth_group_access_model = new PhoneAuthGroupAccessModel();
        $this->admin_user_wechat_model = new AdminUserWechat();
    }

    /**
     * 管理员管理
     * @return mixed
     */
    public function index()
    {
        $admin_user_list = $this->admin_user_model->where('merchant_id',  $this->merchant_id)->select();

        return $this->fetch('index', ['admin_user_list' => $admin_user_list]);
    }

    /**
     * 添加管理员
     * @return mixed
     */
    public function add()
    {
        $role_list = $this->auth_group_model->getMpAuthGroup();
        $phone_role_list = $this->phone_auth_group_model->getMpAuthGroup();
        return $this->fetch('add', ['role_list'=>$role_list,'phone_role_list'=>$phone_role_list]);
    }

    /**
     * 保存管理员
     * @param $group_id
     */
    public function _save($group_ids =[],$phone_group_ids=[])
    {
        if ($this->request->isPost()) {
            $data            = $this->request->param();
            $validate_result = $this->validate($data, 'AdminUser');
             if(preg_match("/[\x7f-\xff]/", $data['password'])){
                   $this->error('密码不能包含中文字符！'); 
                }
            if ($validate_result !== true) {
                $this->error($validate_result);
            } else {
                $data['merchant_id'] = $this->merchant_id;
                if ($this->admin_user_model->allowField(true)->save($data)) {
                     //添加pc端新的权限
                    $auth_group_access = [];
                    foreach ($group_ids as $group_id){
                        $auth = [];
                        $auth['uid']      = $this->admin_user_model->id;
                        $auth['group_id'] = $group_id;
                        $auth_group_access[] = $auth;
                    }
                    $this->auth_group_access_model->saveAll($auth_group_access);
                     //添加移动端新的权限
                    $phone_auth_group_access = [];
                    foreach ($phone_group_ids as $group_id){
                        $auth = [];
                        $auth['uid']      = $this->admin_user_model->id;
                        $auth['group_id'] = $group_id;
                        $phone_auth_group_access[] = $auth;
                    }
                    $this->phone_auth_group_access_model->saveAll($phone_auth_group_access);
                    $this->addLog('新增员工',  json_encode($data,JSON_UNESCAPED_UNICODE));
                    $this->success('保存成功',  url('index'));
                } else {
                    $this->error('保存失败');
                }
            }
        }
    }

    /**
     * 编辑管理员
     * @param $id
     * @return mixed
     */
    public function edit($id)
    {
        $admin_user             = $this->admin_user_model->find($id);
        $auth_group_access      = $this->auth_group_access_model->where('uid', $id)->select()->toArray();
        $admin_user['group_ids'] = array_column($auth_group_access, 'group_id');
        $phone_auth_group_access      = $this->phone_auth_group_access_model->where('uid', $id)->select()->toArray();
        $admin_user['phone_group_ids'] = array_column($phone_auth_group_access, 'group_id');
        $role_list = $this->auth_group_model->getMpAuthGroup();
        $phone_role_list = $this->phone_auth_group_model->getMpAuthGroup();
        return $this->fetch('edit', ['admin_user' => $admin_user,'role_list'=>$role_list,'phone_role_list'=>$phone_role_list]);
    }

    /**
     * 更新管理员
     * @param $id
     * @param $group_id
     */
    public function _update($id, $group_ids=[],$phone_group_ids=[])
    {
        if ($this->request->isPost()) {
            $data            = $this->request->param();
            $validate_result = $this->validate($data, 'AdminUser');
            if ($validate_result !== true) {
                $this->error($validate_result);
            } else {
                if (empty($data['password'])) {
                    unset($data['password']);
                }else{
                    if(preg_match("/[\x7f-\xff]/", $data['password'])){
                        $this->error('密码不能包含中文字符！'); 
                    }
                }
                if ($this->admin_user_model->allowField(true)->save($data,['id'=>$id]) !== false) {
                    //先删除原来的权限
                    $this->auth_group_access_model->where(['uid'=>$id])->delete();
                    //先删除原来的权限
                    $this->phone_auth_group_access_model->where(['uid'=>$id])->delete();
                    //添加pc端新的权限
                    $auth_group_access = [];
                    foreach ($group_ids as $group_id){
                        $auth = [];
                        $auth['uid']      = $id;
                        $auth['group_id'] = $group_id;
                        $auth_group_access[] = $auth;
                    }
                    $this->auth_group_access_model->saveAll($auth_group_access);
                    //添加移动端新的权限
                    $phone_auth_group_access = [];
                    foreach ($phone_group_ids as $group_id){
                        $auth = [];
                        $auth['uid']      = $id;
                        $auth['group_id'] = $group_id;
                        $phone_auth_group_access[] = $auth;
                    }
                    $this->phone_auth_group_access_model->saveAll($phone_auth_group_access);
                    $this->addLog('修改员工',  json_encode($data,JSON_UNESCAPED_UNICODE));
                    $this->success('更新成功',  url('index'));
                } else {
                    $this->error('更新失败');
                }
            }
        }
    }

    /**
     * 删除管理员
     * @param $id
     */
    public function delete($id)
    {
        if ($id == 1) {
            $this->error('默认管理员不可删除');
        }
        $admin_user = $this->admin_user_model->where('id',$id)->field('name,mobile')->find()->toArray();
        if ($this->admin_user_model->destroy($id)) {
            $this->auth_group_access_model->where('uid', $id)->delete();
            $this->addLog('删除员工',"被删除员工id:$id<br>被删除员工姓名：".$admin_user['name']."<br>被删除员工电话：".$admin_user['mobile']);
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }
    
    /**
     * 微信管理员
     * @return array|void
     */
    public function wechantUser() {
        # 获取将要推送的粉丝列表
        switch (strtolower($this->request->get('action', ''))) {
            case 'all':
                $map['merchant_id'] = $this->merchant_id;
                return ['code' => "SUCCESS", 'data' => $this->admin_user_wechat_model->where($map)->limit(200)->field('headimgurl,nickname,id,openid')->select()];
                break;
            case 'nickname':
                if ('' === ($params = $this->request->post('nickname', ''))) {
                    return ['code' => 'SUCCESS', 'data' => []];
                }
                $map['merchant_id'] = $this->merchant_id;
                $map['nickname'] = ['like',"%$params%"];
                return ['code' => "SUCCESS", 'data' => $this->admin_user_wechat_model->where($map)->limit(200)->field('headimgurl,nickname,id,openid')->select()];
            default :
                    return $this->fetch('wechat_user');
        }
    }
}