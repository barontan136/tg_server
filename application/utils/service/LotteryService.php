<?php

namespace app\utils\service;

/**
 * 抽奖类
 */
class LotteryService {

    /**
     * @param array $prizeList 
     * @param string $v 
     * $prizeList 为备选抽奖列表;
     * $v 为抽奖列表中，中奖概率的字段名
     * 注意中奖概率，如果全为整数，则总和为100，如果其中有小数且位数最高为一位则中奖概率转为整数，总和为1000，以此类推，两位小数，总和为10000
     * @return $prize //放回抽中结果集
     */
    public static function lottery($prizeList = [], $v = 'v') {
        if (!empty($prizeList)) {
            $proArr = array_column($prizeList, $v);
            $resultKey = self::getRand($proArr);
            return $prizeList[$resultKey];
        }
        return false;
    }

    /**
     * 假设：有一个二维数组，记录了所有本次抽奖的奖项信息： 
      $test_arr =array('a'=>20,'b'=>30,'c'=>50);
      a奖概率20%，b奖概率30%，c奖概率50%

      模拟函数执行过程：
      总概率精度为20+30+50=100

      第一次数组循环，$procur=20
      假设抽取的随机数rand(1,100)，假设抽到$randNum=55
      如果$randNum<=20,则result=a
      否则进入下一循环，总概率精度变为100-20=80

      第二次数组循环，$procur=30
      假设抽取的随机数rand(1,80)，假设抽到$randNum=33
      如果$randNum<=30,则result=b
      否则进入下一循环，总概率精度变为80-30=50

      第三次数组循环，$prosur=50;
      假设抽取的随机数rand(1,50)，不管怎么抽，随机数都会<或=50，
      那么得出result=c;

      因为样本没有改变，虽然可能抽取的随机数不止一个，但是概率是不变的。
     */
    private static function getRand($proArr) {
        $result = '';
        //概率数组的总概率精度
        $proSum = array_sum($proArr);
        //概率数组循环
        foreach ($proArr as $key => $proCur) {
            $randNum = mt_rand(1, $proSum);
            if ($randNum <= $proCur) {
                $result = $key;
                break;
            } else {
                $proSum -= $proCur;
            }
        }
        return $result;
    }

}
