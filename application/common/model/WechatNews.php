<?php

namespace app\common\model;

use think\Model;

class WechatNews extends Model {

    public function getUpdateTimeTextAttr($value, $data) {
        return date('Y-m-d H:i:s', $data['update_time']);
    }

    public function getCreateTimeTextAttr($value, $data) {
        return date('Y-m-d H:i:s', $data['create_time']);
    }


    /**
     * 通过图文ID读取图文信息
     * @param int $id 本地图文ID
     * @param array $where 额外的查询条件
     * @return array
     */
    public  function getNewsById($id, $where = []) {
        $data = $this->where('id', $id)->where($where)->find();
        if (empty($data))
            return [];
        $data = $data->toArray();
        $article_ids = explode(',', $data['article_id']);
        $articles = model('WechatNewsArticle')->where('id', 'in', $article_ids)->select();
        if (empty($articles))
            return [];
        $articles = $articles->toArray();
        $data['articles'] = [];
        foreach ($article_ids as $article_id) {
            foreach ($articles as $article) {
                if (intval($article['id']) === intval($article_id)) {
                    unset($article['create_by'], $article['create_at']);
                    $data['articles'][] = $article;
                }
            }
        }
        unset($articles);
        return $data;
    }

}
