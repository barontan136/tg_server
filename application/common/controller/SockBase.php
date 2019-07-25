<?php
namespace app\common\controller;
use think\Controller;
use app\utils\service\ConfigService;
class SockBase extends Controller
{
    public $params;
    protected function _initialize()
    {
        parent::_initialize();
    }

}