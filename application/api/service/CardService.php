<?php
namespace app\api\service;

class CardService {
     
    //生成16位编号
    public static function createCode(){
        $code = self::randCode();
        while ((db('card_code')->where('code',$code)->count('id'))>0){
            $code = self::randCode();
        }
        return $code;
    }
    
    public static function randCode(){
        $chars = '123567891235678912356789123567891235678912356789';
        $chars = str_shuffle($chars);
        return substr($chars, 3, 16);
    }

}
