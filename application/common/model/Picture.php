<?php

namespace app\common\model;
use think\Exception;
use think\Model;
use think\Request;
use think\Image;
use app\common\model\PictureTag;

class Picture extends Model {

    public function getMerchantId() {
        $merchant_id = 0;
        if (!empty(session('merchant_id')) && empty(session('admin_id'))) {
            $merchant_id = session('merchant_id');
        }
        return $merchant_id;
    }

    /**
     * 添加图片
     */
    public function initImg($path = "", $url = "", $file) {
        $data = $file;
        $data['path'] = $path;
        $data['url'] = $url;
        $data['merchant_id'] = $this->getMerchantId();
        $data['md5'] = md5_file("." . $path);
        $data['sha1'] = sha1_file("." . $path);
        $this->data($data, true)->allowField(true)->isUpdate(false)->save();
        $id = $this->id;
        if ($id) {
            $data['id'] = $id;
            return $data;
        } else {
            return false;
        }
    }

    /**
     * 判断图片是否已经上传
     */
    public function checkImg($file) {
        if (empty($file['tmp_name']))
            return false;
        $img = $this->where(['md5' => md5_file($file['tmp_name'])])->where('merchant_id', $this->getMerchantId())->field('id,path,url,width,height,media_id,media_url')->find()->toArray();
        if (empty($img))
            return false;
        return $img;
    }

    /**
     * 判断图片是否已经上传
     */
    public function checkImgByFile($file) {
        if (empty($file))
            return false;
        $img = $this->where(['md5' => md5($file)])->where('merchant_id', $this->getMerchantId())->field('id,path,url,width,height')->find()->toArray();
        if (empty($img))
            return false;
        return $img;
    }

    /**
     * 上传图片永久素材
     * @param string $local_url 文件URL地址
     * @param string $type 文件类型
     * @param bool $is_video 是否为视频文件
     * @param array $video_info 视频信息
     * @return string|null
     */
    public function uploadForeverMedia($local_url = '', $where = [], $type = 'image', $is_video = false, $video_info = [],$appid='') {
        if(empty($where) || empty($where['id']) || !isset($where['id'])){
            return null;
        }
        $cover_id = $where['id'];
        $media_id = model('Picture')->where($where)->value('media_id');
        if (!empty($media_id))
            return $media_id;
        # 检测文件上否已经上传过了
        $wechat = getWeObject($appid);
        $file = "@" . $_SERVER['DOCUMENT_ROOT'] . $local_url;
        # 上传图片到微信服务器
        if (false !== ($result = $wechat->uploadForeverMedia(['media' => $file], $type, $is_video, $video_info))) {
            if (isset($result['media_id'])) {
                $data = ['media_id' => $result['media_id']];
                isset($result['url']) && $data['media_url'] = $result['url'];
                $this->where(['id' => $cover_id])->update($data);
                return $data['media_id'];
            } else {
                return null;
            }
        }
        return null;
    }

    /**
     * 上传图片到微信服务器,临时素材
     * @param string $local_url
     * @return string|null
     */
    public function uploadImage($local_url,$appid='') {
        if (strpos($local_url, 'https') === 0 || strpos($local_url, 'http') === 0) {
            # 下载文件到本地
            $save_path = '/uploads/picture/' . date('Ymd') . '/';
            try {
                $file = $this->getImage($local_url, "." . $save_path);
            } catch (Exception $ex) {
                throw $ex;
            }
            $wechat = getWeObject($appid);
            $info = $wechat->uploadImg(['media' => "@" . $_SERVER['DOCUMENT_ROOT'] . substr($file['save_path'], 1)]);
            @unlink($file['save_path']);
            if (!empty($info)) {
                return $info['url'];
            }
        } else {
            # 检测文件上否已经上传过了
            if (($media_url = $this->where(['md5' => md5_file("." . $local_url)])->value('media_url')) && !empty($media_url)) {
                return $media_url;
            }
            $path = $local_url;
            $cover_id = $this->where(['md5' => md5_file("." . $path)])->value('id');
            if (empty($cover_id))
                return null;
            # 上传图片到微信服务器
            $wechat = getWeObject($appid);
            $info = $wechat->uploadImg(['media' => "@" . $_SERVER['DOCUMENT_ROOT'] . $path]);
            writerLog(['111',$info]);
            if (!empty($info)) {
                $data = ['media_url' => $info['url']];
                $this->where(['id' => $cover_id])->update($data);
                return $info['url'];
            }
        }
    }

    /**
     * 下载远程图片
     * @param type $url
     * @param type $save_dir
     * @param string $filename
     * @param type $type
     * @return type
     */
    function getImage($url, $save_dir = '', $filename = '', $type = 0) {
        $url = explode('?', $url);
        $url = $url[0];
        if (trim($url) == '') {
            return array('file_name' => '', 'save_path' => '', 'error' => 1);
        }
        if (trim($save_dir) == '') {
            $save_dir = './';
        }
        if (trim($filename) == '') {//保存文件名
            $ext = strrchr($url, '.');
            if ($ext != '.gif' && $ext != '.jpg'&& $ext != '.png'&& $ext != '.bmp') {
                return array('file_name' => '', 'save_path' => '', 'error' => 3);
            }
            $filename = time() . $ext;
        }
        //创建保存目录
        if (!file_exists($save_dir) && !mkdir($save_dir, 0777, true)) {
            return array('file_name' => '', 'save_path' => '', 'error' => 5);
        }
        //获取远程文件所采用的方法
        if ($type) {
            $ch = curl_init();
            $timeout = 5;
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            $img = curl_exec($ch);
            curl_close($ch);
        } else {
                ob_start();
            try {
                readfile($url);
                $img = ob_get_contents();
                ob_end_clean();
            } catch (\think\exception\ErrorException $exc) {
                new Exception();
            }
        }
        //$size=strlen($img);
        //文件大小
        $fp2 = @fopen($save_dir . $filename, 'a');
        try {
            fwrite($fp2, $img);
            fclose($fp2);
            unset($img, $url);
        } catch (Exception $exc) {
            return array('file_name' => '', 'save_path' => '', 'error' => 5);
        }
        return array('file_name' => $filename, 'save_path' => $save_dir . $filename, 'error' => 0);
    }

}
