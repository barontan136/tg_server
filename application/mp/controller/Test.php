<?php

namespace app\mp\controller;

use app\common\controller\MpBase;
use app\mp\model\OrderExchange as O;


/*
 * 线上支付和退款临时---控制器 create 5-25
 */

class Test extends MpBase {

    protected $order_exchange;

    protected function _initialize() {
        parent::_initialize();
         $this->activity = model('Activity');
        $this->activity_bargain = new Abar();
        $this->activity_prize_model = new AP();
         $this->activityspike_db = new AS_db();
    }
        public function bardetail($page = 1) {
        $info= $this->activity_bargain->getInfos($page);
        $list=$info;
        
        return $this->view->fetch();
    }
    public function pridetail($page = 1){
        $info= $this->activity_prize->getInfos($page);
        $list=$info;
        
        return $this->view->fetch();
       
    }
     public function spidetail($page = 1) {
         $info= $this->activity_spike->getInfos($page);
        $list=$info;
        
        return $this->view->fetch();

        
    }

   

}
