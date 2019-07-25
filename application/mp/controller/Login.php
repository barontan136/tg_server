<?php
namespace app\mp\controller;

use think\Config;
use think\Controller;
use think\Db;
use think\Session;
use app\common\model\ActionLog;
use app\common\model\AdminUser;
use app\utils\service\ConfigService;
use app\common\model\ComponentAppid;
use app\common\model\Merchant;
/**
 * 后台登录
 * Class Login
 * @package app\mp\controller
 */
class Login extends Controller
{
    public function _initialize() {
        parent::_initialize();
          //加载系统配置
          ConfigService::config();
    }

    /**
     * 后台登录
     * @return mixed
     */
    public function index()
    {
        if(!empty(session('admin_id'))){
            $this->error('平台端已登录，请更换浏览器登录商户端');
        }
        //创建扫码记录到缓存中
        //status:0初始化，1：扫码成功,待确认，2扫码失败（未绑定微信管理员），3用户不同意登录,4用户已同意
        $scan_record = [
            "id" => time().rand(10000,99999),
            "status"=>0,
            "openid"=>''
        ];
        cache('admin_user_qrcode_recode_'.$scan_record['id'],$scan_record,1800);
        return $this->fetch('',['scan_record'=>$scan_record]);
    }

    /**
     * 登录验证
     * @return string
     */
    public function login()
    {
        if ($this->request->isPost()) {
            $data = $this->request->only(['username', 'password', 'verify']);
            $validate_result = $this->validate($data, 'Login');

            if ($validate_result !== true) {
                return ajaxMsg($validate_result);
            } else {
                $where['username'] = $data['username'];
                $where['merchant_id'] = ['neq',0];
//                $where['password'] = md5($data['password'] . Config::get('salt'));
//                $where['is_platform'] = -1;
                $admin_user = (new AdminUser())->field('id,username,status,headimg,name,mobile,sex,is_admin,merchant_id,headimgurl,nickname,openid')->where($where)->find()->toArray();
                if (!empty($admin_user)) {
                    if ($admin_user['status'] != 1) {
                        return ajaxMsg('当前用户已禁用!');
                    } else {
                        $appid = '';
                        $merchant = (new Merchant())->find($admin_user['merchant_id'])->toArray();
                        Session::set('merch_admin_id', $admin_user['id']);
                        Session::set('is_admin_'.$admin_user['id'], $admin_user['is_admin']);
                        Session::set('merchant_id', $admin_user['merchant_id']);
                        Session::set('merch_admin_name', $admin_user['username']);
                        Session::set('appid',$appid);
                        Session::set('merch_admin_user', $admin_user);
                        Session::set('merchant', $merchant);
                        Db::name('admin_user')->update(
                            [
                                'last_login_time' => time(),
                                'last_login_ip'   => $this->request->ip(),
                                'id'              => $admin_user['id']
                            ]
                        );
                        //添加日志
                        (new ActionLog())->addLog(1,'登录');
                        return ajaxMsg('登录成功!',1,url('index/index'));
                    }
                } else {
                    return ajaxMsg('用户名或密码错误!');
                }
            }
        }
    }


    /**
     * 退出登录
     */
    public function logout()
    {
        Session::delete('merch_appid');
        Session::delete('merch_admin_id');
        Session::delete('merch_admin_name');
        Session::delete('merch_admin_user');
        Session::delete('merchant');
        Session::delete('appid');
        $this->success('退出成功', 'login/index');
    }
}
