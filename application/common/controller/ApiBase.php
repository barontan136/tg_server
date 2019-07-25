<?php

namespace app\common\controller;

use think\Controller;
use app\utils\service\ConfigService;
use aes\Aes;

class ApiBase extends Controller {


    protected function _initialize() {
        parent::_initialize();
    }
}
