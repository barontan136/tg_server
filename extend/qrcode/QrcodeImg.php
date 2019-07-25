<?php

namespace qrcode;

//use app\api\model\VerifyCode;
//use CURLFile;

use org\QiniuUpload;
use qrcode\QRcode;
use config\Globalconfig;

/**
 *
 * 使用说明
 * use sms\Sms;//引入sdk
 *
 * 
 */
class QrcodeImg {

    const DOMAIN = "https://cli.im/api?text=%s&mhid=4UeSCw3pyJ8hMHcrKtJTMKI";
    const QRCODE_SAVE_FILE = '/alidata/tmp/qrcode';

    public function __construct($templateCode = '', $signName = '') {
        // 配置参数
    }

    /**
     * 生成二维码 并 上传
     * @param $path 除去根域名的链接 即 uri  例如:"/center_empty?title=2323232"
     * @return string
     */
    public static function autoCreateQrcodeAndUpload($path) {

        $file_name = self::autoCreateQrcode($path);
//        if ($file_name) {
//            $qiniuUpload = new QiniuUpload();
//            writerLog('upload:'.$file_name);
//            $ret_file = $qiniuUpload->uploadFile($file_name);
//            writerLog($ret_file);
//            writerLog('-end-');
//            if (isset($ret_file['key'])) {
//                return $ret_file['key'];
//            }
//        }
        return $file_name;
    }

    /**
     * 生成二维码
     * @param Request $request
     * @return string
     */
    public static function autoCreateQrcode($path) {

//        $url = "http://m.avicks.cn/activity/worldcup/index?user_id=" . $user_id;
        $url = $path;
        $value = $url;                  //二维码内容
        $errorCorrectionLevel = 'H';    //容错级别
        $matrixPointSize = 3.5;           //生成图片大小
        //生成二维码图片
        $rand = rand(0,1000);
        $filename = self::QRCODE_SAVE_FILE . '/' . time() . $rand . '.png';
        QRcode::png($value, $filename, $errorCorrectionLevel, $matrixPointSize, 2);
        $QR = imagecreatefromstring(file_get_contents($filename));        //目标图象连接资源。
        $QR_width = imagesx($QR);           //二维码图片宽度
        $qrcode_name = self::QRCODE_SAVE_FILE . '/qrcode_' . time() . $rand . '.png';
        imagepng($QR, $qrcode_name);
        imagedestroy($QR);
        exec("rm -f " . $filename);

        writerLog('QRcode:'.$qrcode_name);
        $data = [
            'qrcode_name' => $qrcode_name,
            'QR_width' => $QR_width,
        ];
        return $data;
    }

    public static function getQrcode($path) {
        //过滤所有的img
        $url = "https://cli.im/api?text=www.avicks.com&mhid=4UeSCw3pyJ8hMHcrKtJTMKI";
        $url = sprintf($url, $path);

        $content = @file_get_contents($url);  //屏蔽warning错误
        //匹配img标签src属性
        $img_pattern = '/<img.*?src=\"(.*?)\".*?>/is';
        preg_match_all($img_pattern, $content, $img_out, PREG_SET_ORDER);
        //$photo_num = count($img_out);
        if (isset($img_out[0][0])) {
            return $img_out[0][0];
        }
        return false;
    }

    public static function getErrorMessage($status) {
        
    }

}
