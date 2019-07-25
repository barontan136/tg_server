<?php

namespace app\mp\controller;

use app\common\controller\MpBase;
use app\mp\model\WechatBaseConfig;
use app\mp\model\ActivityBase;
use app\mp\model\WechatPayConfig;

/**
 * 公众号设置
 * Class Menu
 * @package app\admin\controller
 */
class WechatConfig extends MpBase {

    protected $wechat_base_config_model;
    protected $wechat_pay_config_model;
    protected $activity_base_model;

    protected function _initialize() {
        parent::_initialize();
        $this->activity_base_model = new ActivityBase();
        $this->wechat_base_config_model = new WechatBaseConfig();
        $this->wechat_pay_config_model = new WechatPayConfig();
    }

    //基本参数设置
    public function base_config() {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            //多张图片只选择第一张
            if (!empty($data['image'])) {
                $data['qrcode_url'] = $data['image'][0];
            } else {
                $data['qrcode_url'] = '';
            }
            $where = [];
            if (empty($data['id'])) {
                unset($data['id']);
            } else {
                $where['id'] = $data['id'];
            }
            $data['merchant_id'] = $this->merchant_id;
            $data['status'] = 1;
            if ($this->wechat_base_config_model->allowField(TRUE)->save($data, $where)) {
                $this->success('保存成功', 'base_config', $data);
            } else {
                $this->error('保存失败！请检查信息并重试');
            }
        } else {
            $info = $this->wechat_base_config_model->getInfo(['merchant_id' => $this->merchant_id], true);
            return $this->fetch('', ['info' => $info]);
        }
    }

    //支付参数设置
    public function pay_config() {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $where = [];
            if (empty($data['id'])) {
                unset($data['id']);
            } else {
                $where['id'] = $data['id'];
            }
            $data['merchant_id'] = $this->merchant_id;
            $data['status'] = 1;
            if ($this->wechat_pay_config_model->allowField(TRUE)->save($data, $where)) {
                $this->success('保存成功', 'pay_config');
            } else {
                $this->error('保存失败！请检查信息并重试');
            }
        } else {
            $info = $this->wechat_pay_config_model->getInfo(['merchant_id' => $this->merchant_id], true);
            return $this->fetch('', ['info' => $info]);
        }
    }

    //红包参数设置
    public function redenvelopes_config() {
        dump(3);
    }

}
