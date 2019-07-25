<?php
namespace app\mp\controller;

use app\common\controller\MpBase;
use app\common\model\User;
use app\mp\model\Customer;
use app\mp\model\Statistics;
use app\mp\model\Order;

/**
 * 后台首页
 * Class Index
 * @package app\mp\controller
 */
class Index extends MpBase
{
    protected $user_model;
    protected $customer_model;
    protected $statistics_model;
    protected $order_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->user_model = new User();
    }

    /**
     * 首页
     * @return mixed
     */
    public function index(){
        $list = [];
       return $this->fetch('info', ['info'=>$list]);
    }
    
    /**
     * 数据统计
     */
    public function _getdataAll(){
        if($this->request->isAjax()){
            $data = $this->request->param();
            $result = [];

            return ajaxMsg('获取成功！',1,'',$result);
        }
    }
    /**
     * 数据统计
     */
    public function _getdata(){
        if($this->request->isAjax()){

            $result = [];
            return ajaxMsg('获取成功！',1,'',$result);
        }
    }
}