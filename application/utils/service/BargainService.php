<?php

namespace app\utils\service;

/**
 * 抽奖类
 */
class BargainService {

    /**
     * 砍价规则：
     * 1.所有人的砍价金额之和等于设置的价差，不能超过，也不能少于。
     * 2.每个人至少砍到一分钱。
     * 3.要保证所有人砍到金额的几率相等。
     */
    /*
     * 砍价模拟测试
     * $price 现价 单位：分
     * $min_price 最低成交价 单位：分
     * $num 可砍价最高次数
     */
    public static function bargain($price, $min_price, $num) {
        $res = false;
        if ($price > $min_price) {
            $margin = $price - $min_price;
            $maxnum = $num;
            for ($i = 0; $i < $num - 1; $i++) {
                $res[$i] = $this->doubleaverage($margin, $maxnum);
                $maxnum-=1;
                $margin-=$res[$i];
            }
            $res[$i + 1] = $margin;
        }
        return $res;
    }

    /*
     * 
     * 
      $test = new service\BargainService();
      for ($i = 0; $i < 30; $i++) {
      $maxmax = mt_rand(1000, 9000);
      $minmin = mt_rand(1000, 9000);
      $numnum = mt_rand(10, 40);
      while ($maxmax < $minmin) {
      $maxmax = mt_rand(1000, 9000);
      $minmin = mt_rand(1000, 9000);
      }
      dump('现价'.$maxmax);
      dump('最低成交价'.$minmin);
      dump('人数:'.$numnum);
      dump('差值:'.($maxmax-$minmin));
      $test1 = ($test::bargain($maxmax, $minmin, $numnum));
      dump(implode(',', $test1));
      $sum = 0;
      foreach ($test1 as $v) {
      $sum+=$v;
      }
      dump('砍价总和:'.$sum);
      dump('over-'.$i.'-');
      }
     * 
     * 
     * 
     */

    /* 方法一 (使用此方法进行砍价)
     * 二倍均值法
     * 剩余砍价金额为M，剩余砍价次数为N，那么有如下公式：
     * 每次砍价的金额 = 随机区间 （0， M / N X 2）
     * 特点：每次随机金额的平均值是相等的，不会因为砍价的先后顺序而造成不公平
     * $price 可砍价金额 单位：分 现价-最低成交价-已砍价金额=可砍价金额
     * $num 目前可砍价次数
     */

    public static function doubleaverage($price, $num) {
        $result = FALSE;
        if (!empty($price) && !empty($num)) {
            if($price>$num)
            {
                $result = mt_rand(1, $price / $num * 2);
            }else{
                $result=1;
            }
        } else {
            $result = $price;
        }
        return $result;
    }

    /* 方法二 (不使用)
     * 设置安全数法
     * 每次砍价的金额在0至安全数之间
     * 缺点最后一刀可以得到很高的分配比例(数值大)
     */

    public static function bargain2($price, $min_price, $num) {
        $res = false;
        $margin = $price - $min_price;
        $maxnum = $num;
        $use_price = 0;
        for ($i = 0; $i < $num; $i++) {
            $res[$i] = self::cut_one($num, $i, $price, $min_price, $use_price);
            $use_price+=$res[$i];
        }
        return $res;
    }

    /**
     * 砍价算法---安全数值法
     */
    private static function cut_one($max_num, $use_num, $price, $min_price, $use_price) { {
            $res = [];
            //查询剩下还能砍的金额
            $zhukan_sum = $price - $min_price - $use_price;
            if ($zhukan_sum == 0) {
                return false;
            }

            //5.判断砍价次数是否还能再砍
            if ($max_num - $use_num == 0) {   //砍价的数量超过了并且不是不限
                return false;
            }
            //6.算法算出来这一刀多少钱
            $total = $price - $min_price; //砍价总额
            $num = $max_num;                // 需要砍价人数
            if (($max_num - $use_num) > 1) {
                $min = 1;     //每个人最少能收到10元
                $safe_total = ($total - ($num - 1) * $min) / ($num - 1); //随机安全上限
                $total = mt_rand($min, $safe_total);
            } else {
                $total = $zhukan_sum;
            }
            return $total;
        }
    }

}
