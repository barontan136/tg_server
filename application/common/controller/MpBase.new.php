<?php

namespace app\common\controller;

use org\Auth;
use think\Controller;
use think\Db;
use think\Session;
use app\common\model\ActionLog;
use app\utils\service\ConfigService;
use app\mp\model\MerchantShop;
use think\Request;

/**
 * 商户端公用基础控制器
 * Class AdminBase
 * @package app\common\controller
 */
class MpBase extends Controller {

    protected function _initialize() {
        parent::_initialize();
        //$this->merch_admin_id = session('merch_admin_id');
        //$this->assign('pay_mch_id', $this->pay_mch_id);
        $module = $this->request->module();
        $controller = $this->request->controller();
        $action = $this->request->action();
        $this->assign('action', strtolower($action));
        //是否显示左边菜单
        $this->assign('notshowleftmenu','hide');
        if (!in_array(strtolower($module . '/' . $controller . '/' . $action), config('notshowleftmenu'))) {
            $this->assign('notshowleftmenu','show');
        }
        $this->assign('notshowleftmenu','show');
        //加载系统配置
        ConfigService::config();
        $this->getWeMenu();
    }


      /**
     * 获取微信主题导航
     */
    protected function getWeMenu()
    {
        $menu = [
            [
                'id' => 1,
                'pid' => 0,
                'title' => '微信公众号配置',
                'name' => 'mp/energy/index',
            ],
            [
                'id' => 11,
                'pid' => 1,
                'title' => '基础参数配置',
                'name' => 'mp/energy/index',
            ],
            [
                'id' => 12,
                'pid' => 1,
                'title' => '支付参数配置',
                'name' => 'mp/energy/history',
            ],
            [
                'id' => 13,
                'pid' => 1,
                'title' => '其他参数配置',
                'name' => 'mp/energy/control',
            ],
            [
                'id' => 2,
                'pid' => 0,
                'title' => '团购活动管理',
                'name' => 'mp/meter/index',
            ],
            [
                'id' => 21,
                'pid' => 2,
                'title' => '活动配置',
                'name' => 'mp/meter/index',
            ],
            [
                'id' => 22,
                'pid' => 2,
                'title' => '活动数据',
                'name' => 'mp/meter/index',
            ]
        ];
        $mainMenu = [
            [
                'title' => '微信活动管理平台',
                'name' => '',
            ],
            [
                'title' => '节目播放管理',
                'name' => '',
            ],
            [
                'title' => '其他',
                'name' => '',
            ],
        ];


        $request = Request::instance();
        $controller = $request->controller();
        $this->assign('currentTag', 'energy');
        if ($controller == 'Index'){
            $this->assign('currentTag', 'index');
        }

        $return = sub_heigh_light($menu);
        $this->assign('currentMenu', $return['currentMenu']);
        $menu = !empty($menu) ? array2tree($menu) : [];
        $this->assign('mainMenu', $mainMenu);
        $this->assign('menu', $menu);
        $this->assign('date_now', date('Y-m-d'));
        return $menu;
    }

    
      /**
     * 获取微信主题导航
     */
    protected function getWeMenu_old() {
        $menu = [];
        $mainMenu = [];
        $auth = new Auth();
        $auth_rule_list = Db::name('auth_rule')->where('status', 1)->where('type', 2)->order(['sort' => 'DESC', 'id' => 'ASC'])->select();
        //获取当前用户的所有权限ids
        $groups = $auth->getGroups($this->merch_admin_id);
        $ids    = []; //保存用户所属用户组设置的所有权限规则id
        foreach ($groups as $g) {
            $ids = array_merge($ids, explode(',', trim($g['rules'], ',')));
        }
        $ids = array_unique($ids);
        foreach ($auth_rule_list as $value) {
            if (Session::get('is_admin_' . $this->merch_admin_id) == 1 || $auth->check($value['name'], $this->merch_admin_id,2)) {
                $menu[] = $value;
                if ($value['pid'] == 0 && !empty($value['tag'])) {
                    $mainMenu[$value['tag']]['title'] = $value['tag'];
                    if (!isset($mainMenu[$value['tag']]['name']) || empty($mainMenu[$value['tag']]['name'])) {
                        if(Session::get('is_admin_' . $this->merch_admin_id) == 1){
                            $name = Db::name('auth_rule')->where('status', 1)->where('type', 2)->where('pid', $value['id'])->order(['sort' => 'DESC'])->value('name');
                        }else{
                        $name = Db::name('auth_rule')->where('id','in',$ids)->where('status', 1)->where('type', 2)->where('pid', $value['id'])->order(['sort' => 'DESC'])->value('name');
                        }
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

}
