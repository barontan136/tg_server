<?php

namespace app\mp\controller;

use app\common\controller\MpBase;
use app\common\model\ActionLog As Alog;
/**
 * 操作日志
 * @package app\mp\controller
 */
class ActionLog extends MpBase {
    protected $model_action_log;
    public function _initialize() {
        parent::_initialize();
        $this->model_action_log = new Alog();
    }

    /**
     * 系统配置
     */
    public function index($page = 1) {
        $condition = $this->_where();
        $condition['l.type'] = 1;
        $_list = $this->model_action_log->getPage($page,$condition);
        return $this->fetch('', ['_list' => $_list]);
    }
    /**
     * 系统配置添加
     */
    public function del($id = 0, $ids = []) {
        $id = $ids ? $ids : $id;
        if ($id) {
            if ($this->model_action_log->destroy($id)) {
                $this->success('删除成功');
            } else {
                $this->error('删除失败');
            }
        } else {
            $this->error('请选择需要删除的日志');
        }
    }
}
