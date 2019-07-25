<?php

namespace app\common\model;

use think\Model;

class NewsPushRecord extends Model {

    protected $autoWriteTimestamp = false;

    /**
     * create 06-24
     * @param type $value
     * @param type $data
     * @return string
     */
    public function getCreateTimeDateAttr($value, $data) {
        if (empty($data['create_time'])) {
            return '-';
        }
        return date('Y-m-d H:i', $data['create_time']);
    }

    /**
     * 群发类型 create 06-12
     */
    public function getTypeTextAttr($value, $data) {
       
        if (!$data['type']) {
            return '--';
        }
        $text = [
            '1' => '系统群发',
            '2' => '自定义群发',
        ];
        return isset($text[$data['type']]) ? $text[$data['type']] : '--';
    }

    /**
     * 获取发送粉丝类型
     * @param type $value
     * @param type $data
     */
    public function getArticleNameAttr($value, $data) {
        if (empty($data['news_id'])) {
            return '--';
        }
        $item = model('WechatNews')->getNewsById($data['news_id']);
        //dump($item);
        return $item['articles'][0]['title'];
    }

    /**
     * 
     * @param type $value
     * @param type $data
     * @return string
     */
    public function getFansTagsNameAttr($value, $data) {
        if(!isset($data['fans_tags_id'])){
            return '--';
        }
       
        if ($data['fans_tags_id'] == 0) {
            return '全部';
        } else {
            return model('WechatFansTags')->getFansValue($data['fans_tags_id'], session('merchant_id'),'name');
        }
    }

}
