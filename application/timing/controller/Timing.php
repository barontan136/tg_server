<?php
namespace app\timing\controller;
use think\Controller;
use app\utils\service\ConfigService;

/**
 * Description of Timing
 *
 * @author ZHXB
 */
class Timing extends Controller{
    
    protected function _initialize(){
        parent::_initialize();
        //加载配置
        ConfigService::config();
    }
}
