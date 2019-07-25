<?php

namespace app\mp\model;
use think\Model;
use think\Db;

class TravelRoute extends Model{
      /**
     * 获取数据
     */
    public function getTravelRouteList($where = [], $page = 1) {
        $list=$this->alias('tr')
               ->join('merchant_travel_agency mt','mt.id=tr.merchant_travel_agency_id')
                ->field('mt.name,mt.short_name,tr.*');
        if (isset($where['short_name'])) {
            $short_name = $where['short_name'][1];
            $list=$list->where('mt.name|mt.short_name|mt.keys', 'like', "%$short_name%");
        }
        if (isset($where['title'])) {
            $title = $where['title'][1];
            $list=$list->where('tr.title|tr.title_sub', 'like', "%$title%");
        }
        $list= $list->where(['tr.status'=>['gt',0]])->order(['create_time' => 'DESC'])->paginate(10, false, ['page' => $page]);
        return $list;
    }
    
      /**
     * 存储数据
     */
    public function initTravelRoute($data) {
        if (isset($data['images']) && !empty($data['images'])) {
            $data['images'] = implode(",", $data['images']);
        } else {
            $data['images'] = "";
        }
        if(!isset($data['outline_price']) ||empty($data['outline_price'])){
            $data['outline_price']=0;
        }
        $data['online_price'] = $data['online_price'] * 100;
        $data['outline_price'] =$data['outline_price'] * 100;
        return $this->allowField(true)->save($data);
    }
    
       /**
     * 修改数据
     */
    public function updateTravelRoute($data) {
        if (isset($data['images']) && !empty($data['images'])) {
            $data['images'] = implode(",", $data['images']);
        } else {
            $data['images'] = "";
        }
        if(!isset($data['outline_price']) ||empty($data['outline_price'])){
            $data['outline_price']=0;
        }
        $data['online_price'] = $data['online_price'] * 100;
        $data['outline_price'] = $data['outline_price'] * 100;
        return $this->allowField(true)->save($data, ['id' => $data['id']]);
    }
    
     /**
     * 获取修改数据
     */
    public function getEditDate($id) {
        $info = $this->where(['id' => $id])->find()->toArray();
        if (!empty($info['images'])) {
            $images = explode(',', $info['images']);
        }
        $img = [];
        foreach ($images as $va) {
            $arr['id'] = $va;
            $arr['path'] = getCover($va);
            $img[] = $arr;
        }
        $info['thumbpath']= getCover($info['thumb']);
        $info['online_price'] = $info['online_price'] / 100;
        $info['outline_price'] = $info['outline_price'] / 100;
        $info['images'] = $img;
        return $info;
    }
    
     //启用或停用
    public function editTravelRouteStatus($id, $keyword) {
        switch ($keyword) {
            case 'open'://启用
                $status = 1;
                break;
            case 'ban'://禁用
                $status = 2;
                break;
            case 'del'://删除
                $status = -1;
                break;
        }
        $updata['status'] = $status;
        $updata['update_time'] = time();
        $result = ['code' => 0, 'msg' => '操作失败！'];
        beginTransaction();
        $re = $this->where(['id' => $id])->update($updata);
        if ($re === false) {
            rollbackTransaction();
        } else {
            $flag = $this->updateMerchantMinPrice($id);
            if ($flag) {
                $result = ['code' => 1, 'msg' => '操作成功！'];
                commitTransaction();
            } else {
                rollbackTransaction();
            }
        }
        return $result;
    }

    /**
     * 更新最低价格
     */
    public function updateMerchantMinPrice($id) {
        $info = $this->where('id', $id)->find()->toArray();
        $min_price = $this->where('merchant_travel_agency_id', $info['merchant_travel_agency_id'])->where('status', 1)->min('online_price'); //该类型商家线上最低价
        $falg = (new MerchantTravelAgency())->updateMinPrice($info['merchant_travel_agency_id'], $min_price);
        return $falg;
    }
    /**
     * 获取商家下所有可用路线
     */
    public function getShelfList($merchant_travel_agency_id){
        $list = $this->field('id,title')->where('merchant_travel_agency_id',$merchant_travel_agency_id)->where('status',1)->order('sort desc')->select()->toArray();
        return $list;
    }
     /**
     * 获取状态可用路线
     */
    public function getRecommond(){
        $list = $this->alias('tr')->field('tr.id,tr.title as name,tr.title_sub as short_name,m.lat,m.lng')
                ->join('merchant_travel_agency m','m.id = tr.merchant_travel_agency_id')
                ->where('tr.status',1)->select()->toArray();
        return $list;
    }
}
