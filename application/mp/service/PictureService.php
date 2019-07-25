<?php

namespace app\mp\service;

class PictureService {

    const PUBLICK = ROOT_PATH . 'public/';//公共路径
    const WXDIR = 'uploads/official_seal/'; //前端图片基础路径
    const AudioDIR = 'uploads/mp3/'; //
    public static $errMsg ='';

    //判断文件夹是否存在如不存在则生成

    public static function initDir($dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0777);
        }
    }

    public static function uploadBase64($base64) {
         $date = date('Y-m-d')."/";
         $basePath = self::PUBLICK.self::WXDIR.$date;
         self::initDir($basePath);
         
         if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64, $result)) {
                $type = $result[2];
                if (in_array($type, array('pjpeg', 'jpeg', 'jpg', 'gif', 'bmp', 'png'))) {
                    $filePath = date('YmdHis_').  rand(100000,999999) . '.' . $type;
                    $new_file = $basePath.$filePath; 
                    if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $base64)))) {
                         return $rearr=['type'=>$type,'path'=>'/'.self::WXDIR.$date.$filePath,'name'=>$filePath];
                         self::$errMsg = "图片保存成功";
                    } else {
                        self::$errMsg = "图片保存失败";
                       return FALSE;
                    }
                } else {
                    //文件类型错误
                    self::$errMsg = "文件类型错误";
                    return FALSE;
                }
          }else{
               self::$errMsg = "图片文件base64编码错误";
               return FALSE;
          }
    }


    public static function uploadAudioBase64($base64) {
//        $date = date('Y-m-d')."/";
        $basePath = self::PUBLICK.self::AudioDIR;
        self::initDir($basePath);

//        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64, $result))
        {
                $filePath = date('YmdHis_').  rand(100000,999999) . '.mp3';
                $new_file = $basePath.$filePath;
                if ($base64 && file_put_contents($new_file, $base64)) {
                    return $rearr=['type'=>'mp3', 'path'=>'/'.self::AudioDIR.$filePath,'name'=>$filePath];
                    self::$errMsg = "保存成功";
                } else {
                    self::$errMsg = "保存失败";
                    return FALSE;
                }

        }
    }

}
