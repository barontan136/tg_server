<?php

namespace app\common\controller;

use org\Auth;
use think\Controller;
use think\Db;
use think\Session;
use app\common\model\ActionLog;
use app\utils\service\ConfigService;

/**
 * 商户端公用基础控制器
 * Class AdminBase
 * @package app\common\controller
 */
class MpBase extends Controller {

    public $merch_admin_id;
    public $merchant_id;
    public $appid;
    public $pay_mch_id;
    private $merchant_shop_model;

    protected function _initialize() {
        parent::_initialize();
        $this->merch_admin_id = session('merch_admin_id');
        $this->merchant_id = session('merchant_id');
        $this->appid = session('appid');
        $this->pay_mch_id = session('merchant.mch_id');
        $this->assign('appid', $this->appid);
        $this->assign('pay_mch_id', $this->pay_mch_id);
        $module = $this->request->module();
        $controller = $this->request->controller();
        $action = $this->request->action();
        $this->assign('action', strtolower($action));
        //是否显示左边菜单
        $this->assign('notshowleftmenu','show');
//        if (!in_array(strtolower($module . '/' . $controller . '/' . $action), config('notshowleftmenu'))) {
//            $this->assign('notshowleftmenu','show');
//        }
        //加载系统配置
        ConfigService::config();
        $this->getWeMenu();
        $this->checkAuth();
//        //输出trace
//        if (config('app_trace')) {
//            trace('服务器端口:' . $_SERVER['SERVER_PORT'], 'user');
//            trace('服务器环境:' . $_SERVER['SERVER_SOFTWARE'], 'user');
//            trace('PHP版本:' . PHP_VERSION, 'user');
//            $version = Db::query('SELECT VERSION() AS ver');
//            trace('MySQL版本:' . $version[0]['ver'], 'user');
//            trace('最大上传限度:' . ini_get('upload_max_filesize'), 'user');
//        }
    }

    /**
     * 权限检查
     * @return bool
     */
    protected function checkAuth() {
        if (!$this->merch_admin_id) {
            $this->redirect('login/index');
        }
        return true;
        if (Session::get('is_admin_' . $this->merch_admin_id) == 1)
            return true;
        $module = $this->request->module();
        $controller = $this->request->controller();
        $action = $this->request->action();
        //优先过滤魔法方法
        if (strpos($action, "_") === 0) {
            return true;
        }
        // 排除权限
        $not_check = json_decode(strtolower(json_encode(config('allow_list'))), true);
        if (!in_array(strtolower($module . '/' . $controller . '/' . $action), $not_check)) {
            $auth = new Auth();
            if (!$auth->check($module . '/' . $controller . '/' . $action, $this->merch_admin_id,2) && Session::get('is_admin_' . $this->merch_admin_id) != 1) {
                $this->error('没有权限');
            }
        }
    }

    /**
     * 获取侧边栏菜单
     */
    protected function getMenu() {
        $menu = [];
        $auth = new Auth();
        $auth_rule_list = Db::name('auth_rule')->where('status', 1)->where('type', 2)->order(['sort' => 'DESC', 'id' => 'ASC'])->select();
        foreach ($auth_rule_list as $value) {
            if ($auth->check($value['name'], $this->merch_admin_id,2) || Session::get('is_admin_' . $this->merch_admin_id) == 1) {
                $menu[] = $value;
            }
        }
        $menu = !empty($menu) ? array2tree($menu) : [];
        tree_sort($menu);
        $this->assign('menu', $menu);
    }

      /**
     * 获取微信主题导航
     */
    protected function getWeMenu() {
        $menu = [];
        $mainMenu = [];
        $auth = new Auth();
        $auth_rule_list = Db::name('auth_rule')->where('status', 1)->where('type', 2)->order(['sort' => 'DESC', 'id' => 'ASC'])->select();
        //获取当前用户的所有权限ids
//        $groups = $auth->getGroups($this->merch_admin_id);
//        $ids    = []; //保存用户所属用户组设置的所有权限规则id
//        foreach ($groups as $g) {
//            $ids = array_merge($ids, explode(',', trim($g['rules'], ',')));
//        }
//        $ids = array_unique($ids);
        foreach ($auth_rule_list as $value) {
            if (1){//(Session::get('is_admin_' . $this->merch_admin_id) == 1 || $auth->check($value['name'], $this->merch_admin_id,2)) {
                $menu[] = $value;
                if ($value['pid'] == 0 && !empty($value['tag'])) {
                    $mainMenu[$value['tag']]['title'] = $value['tag'];
                    if (!isset($mainMenu[$value['tag']]['name']) || empty($mainMenu[$value['tag']]['name'])) {
//                        if(Session::get('is_admin_' . $this->merch_admin_id) == 1){
//                            $name = Db::name('auth_rule')->where('status', 1)->where('type', 2)->where('pid', $value['id'])->order(['sort' => 'DESC'])->value('name');
//                        }else{
//                        $name = Db::name('auth_rule')->where('id','in',$ids)->where('status', 1)->where('type', 2)->where('pid', $value['id'])->order(['sort' => 'DESC'])->value('name');
//                        }
                        $name = Db::name('auth_rule')->where('status', 1)->where('type', 2)->where('pid', $value['id'])->order(['sort' => 'DESC'])->value('name');
                        $mainMenu[$value['tag']]['name'] = empty($name) ? '' : $name;
                    }
                }
            }
        }
        foreach ($mainMenu as $key=>$value){
            if(empty($value['name'])){
                unset($mainMenu[$key]);
            }
        }
        $this->assign('mainMenu', $mainMenu);
        $return = sub_heigh_light($menu);
        $this->assign('currentMenu', $return['currentMenu']);
        $this->assign('currentTag', $return['tag']);
        $menu = !empty($menu) ? array2tree($menu) : [];
        tree_sort($menu);
        $menu = array_filter($menu, function($v) use ($return) {
            return $v['tag'] == $return['tag'];
        });
        $this->assign('menu', $menu);
        //获取主菜单
        return $menu;
    }

    //添加日志
    public function addLog($title = "", $remakr = "") {
        (new ActionLog())->addLog(2, $title, $remakr);
    }

    /**
     * 获取表单查询条件
     * name 命名为字段名称/条件
     * 条件为 [eq] [neq] [like] [in] [not in] [gt] [lt] [egt] [elt]
     */
    protected function _where() {
        //接收参数
        $data = $this->request->get();
        $_where = [];
        if (empty($data))
            return $_where;
        foreach ($data as $key_condition => $val) {
            $keyArr = explode('/', $key_condition);
            $key = current($keyArr);
            $condition = "eq";
            $this->assign($key, $val);
            if (count($keyArr) > 2) {
                $key = end($keyArr) . ".$key";
                $condition = $keyArr[1];
            } else {
                count($keyArr) > 1 && $condition = end($keyArr);
            }
            if (!empty($val)) {
                switch ($condition) {
                    case 'eq':
                        $_where[$key] = ['eq', $val];
                        break;
                    case 'neq':
                        $_where[$key] = ['neq', $val];
                        break;
                    case 'like':
                        $_where[$key] = ['like', "%$val%"];
                        break;
                    case 'in':
                        $_where[$key] = ['in', (array) $val];
                        break;
                    case 'not in':
                        $_where[$key] = ['not in', (array) $val];
                        break;
                    case 'gt':
                        $_where[$key] = ['gt', $val];
                        break;
                    case 'egt':
                        $_where[$key] = ['egt', $val];
                        break;
                    case 'lt':
                        $_where[$key] = ['lt', $val];
                        break;
                    case 'elt':
                        $_where[$key] = ['elt', $val];
                        break;
                }
            }
        }
        unset($_where['page']);
        return $_where;
    }

    /**
     * 创建时间范围条件组合 create 06-24
     */
    protected function _createTimeMap(){
        //接收参数
        $data = $this->request->get();
        if (empty($data)){
            return false;
        }
        
        $start_time = isset($data['start_time'])? $data['start_time']:'';
        $end_time = isset($data['end_time'])? $data['end_time']:'';
        
        $this->assign('start_time', $start_time);
        $this->assign('end_time', $end_time);
        
        $create_time = '';
        //开始时间
        if (isset($start_time) && !empty($start_time)) {
            $start = strtotime($start_time);
            $create_time = array('EGT', $start);
        }

        //结束时间
        if (isset($end_time) && !empty($end_time)) {

            if (!empty($start)) {
                $create_time = array('between', array($start, strtotime($end_time . " 23:59:59")));
            } else {
                $create_time = array('ELT', strtotime($end_time . " 23:59:59"));
            }
        }
        return $create_time;
    }
    
    /**
     * 所有订单的状态转文本 create06-24
     * @author:lgc
     * @param type $status 状态值
     * @param type $picking_method 1自提2邮寄
     * @return type
     */
    public function _status($status=1,$picking_method=1){
        $status = (string)$status;
        $arr = [
            '-5' => '逾期未提',
            '-4' => '已删除',
            '-3' => '已删除',
            '-2' => '已关闭',
            '-1' => '已取消',
            '1' => '待支付',
            '2' => '已支付',
            '6' => '已完成',
            '7' => '退款中',
            '8' => '退款中',
            '9' => '已取消'
        ];
        if($picking_method == 1){
            $arr ['3'] = '代发货';
            $arr ['4'] = '待提货';
            $arr ['5'] = '待提货';
        }else{
            $arr ['3'] = '待发货';
            $arr ['4'] = '待确认';
            $arr ['5'] = '待确认';
        }
        return (empty($arr[$status]) || !isset($arr[$status]))?'已关闭':$arr[$status];
    }
}
