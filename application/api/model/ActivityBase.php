<?php

namespace app\api\model;

use think\Model;
use app\mp\model\ActivityGroupOrder;

class ActivityBase extends Model {

    public function getUpdateTimeTextAttr($value, $data) {
        return date('Y-m-d H:i:s', $data['update_time']);
    }

    public function getCreateTimeTextAttr($value, $data) {
        return date('Y-m-d H:i:s', $data['create_time']);
    }

    public function getActivityInfo($id, $field = 'activity_name,activity_content,activity_img,activity_telephone,start_time,end_time,status,activity_page_id,activity_share_id,activity_config_id') {
        $config = [];
        $page_config = [];
        $share = [];
        $info = $this->where('id', $id)->field($field)->find()->toArray();
//        dump($info);
        if (empty($info)) {
            return FALSE;
        }
        $info['activity_img']= getCover($info['activity_img']);
        $info['start_date'] = date('Y-m-d H:i:s', $info['start_time']);
        $info['end_date'] = date('Y-m-d H:i:s', $info['end_time']);
        if ($info['status'] == 1 && $info['start_time'] > time()) {
            $info['status'] = 2;  // 未开始 // 状态 默认 1-正常 0-禁用
        } else if ($info['status'] == 1 && $info['end_time'] <= time()) {
            $info['status'] = 3;    // 已结束
        }
        //页面配置
        if (!empty($info['activity_page_id'])) {
            $page_config = (new ActivityPage)->where('id', $info['activity_page_id'])
                            ->field('top_color,tg_color,group_color,brokerage_color,bg_music,dcrc_icon,order_icon,top_image,poster_image,bottom_content')
                            ->find()->toArray();
            if (!empty($page_config)) {
                $page_config['dcrc_icon'] = getCover($page_config['dcrc_icon']);
                $page_config['order_icon'] = getCover($page_config['order_icon']);
                $page_config['top_image'] = getCover($page_config['top_image']);
                $page_config['poster_image'] = getCover($page_config['poster_image']);
//                $page_config['bottom_content'] = getCover($page_config['bottom_content']);
            }
        }
        //分享配置
        if (!empty($info['activity_share_id'])) {
            $share = (new ActivityShare)->where('id', $info['activity_share_id'])
                            ->field('is_open,share_title_single,share_title_circle,share_icon')
                            ->find()->toArray();
            !empty($share) && $share['share_icon'] = getCover($share['share_icon']);
        }
        //活动配置
        if (!empty($info['activity_config_id'])) {
            $config = (new ActivityConfig)->where('id', $info['activity_config_id'])
                            ->field('create_amount,join_amount,group_number,group_amount,old_amount,max_number,sham_view,sham_group_number,sham_jion_group_number,sponsor,dcrc_name,dcrc_qrcode')
                            ->find()->toArray();
            if (!empty($config)) {
                $config = (new ActivityConfig)->_formatMoney2useSave($config, 1, 2, 2, 'string');
                $config['dcrc_qrcode'] = getCover($config['dcrc_qrcode']);
            }
        }
        $info['page_config'] = $page_config;
        $info['share_config'] = $share;
        $info['activity_config'] = $config;

        //活动动态信息
        $group_num = (new ActivityGroupOrder())->where('activity_id', $id)->where('order_type', 2)->where('status', 'not in', '1,4')->count('id');
        $jion_num = (new ActivityGroupOrder())->where('activity_id', $id)->where('order_type', 1)->where('status', 'not in', '1,4')->count('id');
        $view_num = (new ActivityGroupOrder())->where('activity_id', $id)->where('status', 'neq', '4')->count('id');

        $info['group_num'] = ($group_num ?? 0) + ($config['sham_group_number'] ?? 0);
        $info['jion_num'] = ($jion_num ?? 0) + ($config['sham_jion_group_number'] ?? 0);
        $info['view_num'] = ($view_num ?? 0) + ($config['sham_view'] ?? 0);
        return $info;
    }

}
