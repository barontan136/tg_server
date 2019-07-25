<?php

namespace app\common\model;

use think\Model;
use think\Request;

class File extends Model {
   public function getMerchantId(){
          $merchant_id = 0;
         if(!empty(session('merchant_id'))&&empty(session('admin_id'))){
             $merchant_id = session('merchant_id');
        }
        return $merchant_id;
    }
    /**
     * 添加文件
     */
    public function initFile($path = "", $url = "",$file) {
        $data = $file;
        $data['path'] = $path;
        $data['url'] = $url;
        $data['md5'] = md5_file(".".$path);
        $data['sha1'] = sha1_file(".".$path);
        $data['merchant_id'] = $this->getMerchantId();
        $this->allowField(true)->save($data);
        $id = $this->id;
        if($id){
          $data['id'] = $id;
          return $data;
        }
        else{
            return false;
        }
    }
    /**
     * 判断图片是否已经上传
     */
    public function checkFile($file) {
        if(empty($file['tmp_name'])) return false;
        $img = $this->where(['md5' => md5_file($file['tmp_name'])])->where('merchant_id',  $this->getMerchantId())->field('id,path,url,ext,thumb')->find()->toArray();
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
    public function uploadForeverMedia($local_url = '', $where = [], $type = 'video', $is_video = false, $video_info = []) {
        if(empty($where) || empty($where['id']) || !isset($where['id'])){
            return null;
        }
        $cover_id = $where['id'];
        $media_id = $this->where($where)->value('media_id');
        if (!empty($media_id))
            return $media_id;
        # 检测文件上否已经上传过了
        $wechat = getWeObject();
        $file = "@" . $_SERVER['DOCUMENT_ROOT'] . $local_url;
        # 上传图片到微信服务器
        $result = $wechat->uploadForeverMedia(['media' => $file], $type, $is_video, $video_info);
        if (false !== $result) {
            if(isset($result['media_id'])){
            $data = ['media_id' => $result['media_id']];
            isset($result['url']) && $data['media_url'] = $result['url'];
            $this->where(['id' => $cover_id])->update($data);
            return $data['media_id'];
            }else{
                return null;
            }
        }
        return null;
    }

}
