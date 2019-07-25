<?php
namespace app\mp\validate;

use think\Validate;

class Merchant extends Validate
{
    protected $rule = [
        'is_shop_gold_today'  => 'require',
        'is_shop_card'  => 'require',
        'is_shop_recommend'  => 'require',
        'is_shop_hot_recommend'  => 'require',
        'shop_id'=>'checkShop:thinkphp',
        'card_ids'=>'checkCard:thinkphp',
        'good_ids'=>'checkGood:thinkphp',
        'good_hot_ids'=>'checkGoodHot:thinkphp',
    ];

    protected $message = [
        'is_shop_gold_today.require'  => '请选择是否开启今日金价',
        'is_shop_card.require'  => '请选择是否开启展示卡券',
        'is_shop_recommend.require'  => '请选择是否开启店长推荐',
        'is_shop_hot_recommend.require'  => '请选择是否开启热销精品',
    ];
    
    protected $scene = [
        'confighome'  =>  ['is_shop_gold_today','is_shop_card','is_shop_recommend','is_shop_hot_recommend','shop_id','card_ids','good_ids','good_hot_ids'],//配置店面商城展示
    ];
    
    /**
     * 门店检测
     */
    protected function checkShop($value,$rule,$data){
        return empty($value)?'请添加门店信息':true;
    }
    /**
     * 卡券检测
     */
    protected function checkCard($value,$rule,$data){
        if($data['is_shop_card'] == 1){
            if(empty($value)){
                return '请添加卡券！';
            }
        }
        return true;
    }
    /**
     * 店长推荐检测
     */
    protected function checkGood($value,$rule,$data){
        if($data['is_shop_recommend'] == 1){
            if(empty($value)){
                return '请添加店长推荐商品！';
            }
            if(count($value) < 2){
                return '至少添加2个店长推荐商品！';
            }
            if(count($value) > 4){
                return '最多添加4个店长推荐商品！';
            }
        }
        return true;
    }
    /**
     * 热销精品检测
     */
    protected function checkGoodHot($value,$rule,$data){
        if($data['is_shop_hot_recommend'] == 1){
            if(empty($value)){
                return '请添加热销精品商品！';
            }
            if(count($value) < 2){
                return '至少2个热销精品商品！';
            }
            if(count($value) > 6){
                return '最多添加6个热销精品商品！';
            }
            if(!empty(array_intersect($data['good_ids'], $value))){
                return '商品不能同时在“店长推荐”和“热销精品”中，请检查！';
            }
        }
        return true;
    }
}