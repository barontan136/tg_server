<?php

namespace org;

use think\Request;
use think\Image;
use app\common\model\Picture;
use app\common\model\File;

class UeditorUpload {

    private $model_picture; //图片模型
    private $model_file; //文件模型
    private $action; //文件类型
    private $fileField; //文件域名
    private $file; //文件上传对象
    private $base64; //文件上传对象
    private $config; //配置信息
    private $oriName; //原始文件名
    private $fileName; //新文件名
    private $fullName; //完整文件名,即从当前配置目录开始的URL
    private $filePath; //完整文件名,即从当前配置目录开始的URL
    private $fileSize; //文件大小
    private $fileType; //文件类型
    private $isnewfile = true;//是否是新文件
    private $stateInfo; //上传状态信息,
    private $cover_id = ''; //上传文件对应的数据库ID，图片对应picture表，文件对应file表
    private $thumb; //上传文件缩略图
    private $stateMap = [ //上传状态映射表，国际化用户需考虑此处数据的国际化
        "SUCCESS", //上传成功标记，在UEditor中内不可改变，否则flash判断会出错
        "文件大小超出 upload_max_filesize 限制",
        "文件大小超出 MAX_FILE_SIZE 限制",
        "文件未被完整上传",
        "没有文件被上传",
        "上传文件为空",
        "ERROR_TMP_FILE" => "临时文件错误",
        "ERROR_TMP_FILE_NOT_FOUND" => "找不到临时文件",
        "ERROR_SIZE_EXCEED" => "文件大小超出网站限制",
        "ERROR_TYPE_NOT_ALLOWED" => "文件类型不允许",
        "ERROR_CREATE_DIR" => "目录创建失败",
        "ERROR_DIR_NOT_WRITEABLE" => "目录没有写权限",
        "ERROR_FILE_MOVE" => "文件保存时出错",
        "ERROR_FILE_NOT_FOUND" => "找不到上传文件",
        "ERROR_WRITE_CONTENT" => "写入文件内容错误",
        "ERROR_UNKNOWN" => "未知错误",
        "ERROR_DEAD_LINK" => "链接不可用",
        "ERROR_HTTP_LINK" => "链接不是http链接",
        "ERROR_HTTP_CONTENTTYPE" => "链接contentType不正确",
        "INVALID_URL" => "非法 URL",
        "INVALID_IP" => "非法 IP"
    ];

    /**
     * 构造函数
     * Uploader constructor.
     * @param string $fileField 表单名称
     * @param array  $config    配置项
     * @param string $base64    是否解析base64编码，可省略。若开启，则$fileField代表的是base64编码的字符串表单名
     */
    public function __construct($fileField, $config, $base64 = "upload") {
        $this->action = $config['action'];
        $this->model_file = new File();
        $this->model_picture = new Picture();
        $this->fileField = $fileField;
        $this->config = $config;
        $this->base64 = $base64;
        if ($base64 == "remote") {
            $this->saveRemote();
        } else if ($base64 == "base64") {
            $this->upBase64();
        } else {
            $this->upFile();
        }
    }

    /**
     * 上传文件的主处理方法
     * @return mixed
     */
    private function upFile() {
        if (!isset($_FILES[$this->fileField])) {
            $postMaxSize = ini_get('post_max_size');
            $uploadMaxFileSize = ini_get('upload_max_filesize');

            $this->stateInfo = '$_FILES数组未接收到上传文件数据，请检查上传文件大小是否超出PHP配置文件post_max_size、upload_max_filesize设置项，';
            $this->stateInfo .= '当前post_max_size限制大小为' . $postMaxSize;
            $this->stateInfo .= '，当前upload_max_filesize限制大小为' . $uploadMaxFileSize;

            return;
        }

        $file = $this->file = $_FILES[$this->fileField];
        if (!$file) {
            $this->stateInfo = $this->getStateInfo("ERROR_FILE_NOT_FOUND");

            return;
        }
        if ($this->file['error']) {
            $this->stateInfo = $this->getStateInfo($file['error']);

            return;
        } else if (!file_exists($file['tmp_name'])) {
            $this->stateInfo = $this->getStateInfo("ERROR_TMP_FILE_NOT_FOUND");

            return;
        } else if (!is_uploaded_file($file['tmp_name'])) {
            $this->stateInfo = $this->getStateInfo("ERROR_TMPFILE");

            return;
        }
        $this->oriName = $file['name'];
        $this->fileSize = $file['size'];
        $this->fileType = $this->getFileExt();
        $this->fullName = $this->getFullName();
        $this->filePath = $this->getFilePath();
        $this->fileName = $this->getFileName();
        $dirname = dirname($this->filePath);

        //检查文件大小是否超出限制
        if (!$this->checkSize()) {
            $this->stateInfo = $this->getStateInfo("ERROR_SIZE_EXCEED");

            return;
        }

        //检查是否不允许的文件格式
        if (!$this->checkType()) {
            $this->stateInfo = $this->getStateInfo("ERROR_TYPE_NOT_ALLOWED");
            return;
        }

        //创建目录失败
        if (!file_exists($dirname) && !mkdir($dirname, 0777, true)) {
            $this->stateInfo = $this->getStateInfo("ERROR_CREATE_DIR");

            return;
        } else if (!is_writeable($dirname)) {
            $this->stateInfo = $this->getStateInfo("ERROR_DIR_NOT_WRITEABLE");

            return;
        }

        switch ($this->action) {
            case "uploadimage":
                $img = $this->checkImg($file);
                if ($img!==FALSE) {
                    $this->stateInfo = "SUCCESS";
                    $this->fullName = $img['path'];
                    $this->cover_id = $img['id'];
                    $this->isnewfile = FALSE;
                    return;
                } else {
                    //移动文件
                    if ($this->move() === true)
                        $this->saveImage($this->file);
                }
                break;
            /* 上传视频 */
            case 'uploadvideo':
            /* 上传文件 */
            case 'uploadfile':
                $_file = $this->model_file->checkFile($file);
                if (!empty($_file)) {
                    $this->stateInfo = "SUCCESS";
                    $this->fullName = $_file['path'];
                    $this->cover_id = $_file['id'];
                    $this->thumb = $_file['thumb'];
                    $this->isnewfile = FALSE;
                    return;
                } else {
                    //移动文件
                    if ($this->move() === true){
                        $this->saveFile($this->file);
                    }
                }
                break;
        }
    }
    
    //获得视频文件的缩略图
    public function getVideoCover() {
        $thumb = $this->getVideoThumb().".jpg";
        $rootPath = $_SERVER['DOCUMENT_ROOT'];
        if (substr($thumb, 0, 1) != '/') {
            $format = '/' . $thumb;
        }
        $str = "ffmpeg -i ".$this->filePath." -y -f mjpeg -ss 3 -t 1 ".$rootPath.$thumb; 
        system($str); 
        return $thumb;
    }

    /**
     * 检查图片是否存在
     */
    public function checkImg() {
        $img_width = Request::instance()->param('img_width');
        $img_height = Request::instance()->param('img_height');
        if (empty($img_width) || empty($img_height) || $img_width == "undefined" || $img_height == "undefined") {
           $img = $this->model_picture->checkImg($this->file);
        }else{
         $image = Image::open($this->file['tmp_name']);
         $image->thumb($img_width, $img_height, Image::THUMB_FILLED)->save("." . $this->fullName);
         $img = $this->model_picture->checkImg(['tmp_name'=>"." . $this->fullName]);
         unlink("." . $this->fullName);
        }
        if ($img){
            return $img;
        }
        return FALSE;
    }

    /**
     * 移动文件
     * @return boolean
     */
    public function move() {
        if (!(move_uploaded_file($this->file["tmp_name"], $this->filePath) && file_exists($this->filePath))) { //移动失败
            $this->stateInfo = $this->getStateInfo("ERROR_FILE_MOVE");
            return false;
        } else { //移动成功
            $this->stateInfo = $this->stateMap[0];
            return true;
        }
    }

    /**
     * 保存图片
     */
    public function saveImage($filearray) {
        $img_width = Request::instance()->param('img_width');
        $img_height = Request::instance()->param('img_height');
        $image = Image::open("." . $this->fullName);
        if (empty($img_width) || empty($img_height) || $img_height == "undefined" || $img_height == "undefined") {
            $filearray['width'] = $image->width();
            $filearray['height'] = $image->height();
            $filearray['size'] = $this->fileSize;
        } else {
            $filearray['width'] = $img_width;
            $filearray['height'] = $img_height;
            $image->thumb($img_width, $img_height, Image::THUMB_FILLED)->save("." . $this->fullName);
            $filearray['size'] = ceil(filesize("." . $this->fullName));
        }
        $filearray['ext'] = $this->fileType;
        $img = $this->model_picture->initImg($this->fullName, '', $filearray);
        $this->cover_id = $img['id'];
    }

    /**
     * 保存文件
     */
    public function saveFile($filearray) {
        $filearray['name'] = $this->oriName;
        $filearray['ext'] = $this->fileType;
        $filearray['size'] = $this->fileSize;
        if($this->fileType == '.mp4'){
         $filearray['thumb'] = $this->getVideoCover();
        }
        $_file = $this->model_file->initFile($this->fullName, '', $filearray);
        $this->cover_id = $_file['id'];
        $this->thumb = $_file['thumb'];
    }

    /**
     * 处理base64编码的图片上传
     * @return mixed
     */
    private function upBase64() {
        $base64Data = $_POST[$this->fileField];
        $img = base64_decode($base64Data);

        $this->oriName = $this->config['oriName'];
        $this->fileSize = strlen($img);
        $this->fileType = $this->getFileExt();
        $this->fullName = $this->getFullName();
        $this->filePath = $this->getFilePath();
        $this->fileName = $this->getFileName();
        $dirname = dirname($this->filePath);

        //检查文件大小是否超出限制
        if (!$this->checkSize()) {
            $this->stateInfo = $this->getStateInfo("ERROR_SIZE_EXCEED");

            return;
        }

        //创建目录失败
        if (!file_exists($dirname) && !mkdir($dirname, 0777, true)) {
            $this->stateInfo = $this->getStateInfo("ERROR_CREATE_DIR");

            return;
        } else if (!is_writeable($dirname)) {
            $this->stateInfo = $this->getStateInfo("ERROR_DIR_NOT_WRITEABLE");

            return;
        }
        //检查图片是否存在
        $imgs = $this->model_picture->checkImgByFile($img);
        if (!empty($imgs)) {
            $this->stateInfo = "SUCCESS";
            $this->oriName = "";
            $this->fileType = "";
            $this->fileName = "";
            $this->fileSize = "";
            $this->fullName = $imgs['path'];
            $this->cover_id = $imgs['id'];
            return;
        } else {
            //移动文件
            if (!(file_put_contents($this->filePath, $img) && file_exists($this->filePath))) { //移动失败
                $this->stateInfo = $this->getStateInfo("ERROR_WRITE_CONTENT");
            } else { //移动成功
                //保存图片到数据库
                $this->saveImage([]);
                $this->stateInfo = $this->stateMap[0];
            }
        }
    }

    /**
     * 拉取远程图片
     * @return mixed
     */
    private function saveRemote() {
        $imgUrl = htmlspecialchars($this->fileField);
        $imgUrl = str_replace("&amp;", "&", $imgUrl);

        //http开头验证
        if (strpos($imgUrl, "http") !== 0) {
            $this->stateInfo = $this->getStateInfo("ERROR_HTTP_LINK");

            return;
        }

        preg_match('/(^https*:\/\/[^:\/]+)/', $imgUrl, $matches);
        $host_with_protocol = count($matches) > 1 ? $matches[1] : '';

        // 判断是否是合法 url
        if (!filter_var($host_with_protocol, FILTER_VALIDATE_URL)) {
            $this->stateInfo = $this->getStateInfo("INVALID_URL");

            return;
        }

        preg_match('/^https*:\/\/(.+)/', $host_with_protocol, $matches);
        $host_without_protocol = count($matches) > 1 ? $matches[1] : '';

        // 此时提取出来的可能是 ip 也有可能是域名，先获取 ip
        $ip = gethostbyname($host_without_protocol);
        // 判断是否是私有 ip
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE)) {
            $this->stateInfo = $this->getStateInfo("INVALID_IP");

            return;
        }

        //获取请求头并检测死链
        $heads = get_headers($imgUrl, 1);
        if (!(stristr($heads[0], "200") && stristr($heads[0], "OK"))) {
            $this->stateInfo = $this->getStateInfo("ERROR_DEAD_LINK");

            return;
        }
        //格式验证(扩展名验证和Content-Type验证)
        $fileType = strtolower(strrchr($imgUrl, '.'));
        if (!in_array($fileType, $this->config['allowFiles']) || !isset($heads['Content-Type']) || !stristr($heads['Content-Type'], "image")) {
            $this->stateInfo = $this->getStateInfo("ERROR_HTTP_CONTENTTYPE");

            return;
        }

        //打开输出缓冲区并获取远程图片
        ob_start();
        $context = stream_context_create(
                ['http' => [
                        'follow_location' => false // don't follow redirects
                    ]]
        );
        readfile($imgUrl, false, $context);
        $img = ob_get_contents();
        ob_end_clean();
        preg_match("/[\/]([^\/]*)[\.]?[^\.\/]*$/", $imgUrl, $m);

        $this->oriName = $m ? $m[1] : "";
        $this->fileSize = strlen($img);
        $this->fileType = $this->getFileExt();
        $this->fullName = $this->getFullName();
        $this->filePath = $this->getFilePath();
        $this->fileName = $this->getFileName();
        $dirname = dirname($this->filePath);

        //检查文件大小是否超出限制
        if (!$this->checkSize()) {
            $this->stateInfo = $this->getStateInfo("ERROR_SIZE_EXCEED");

            return;
        }

        //创建目录失败
        if (!file_exists($dirname) && !mkdir($dirname, 0777, true)) {
            $this->stateInfo = $this->getStateInfo("ERROR_CREATE_DIR");

            return;
        } else if (!is_writeable($dirname)) {
            $this->stateInfo = $this->getStateInfo("ERROR_DIR_NOT_WRITEABLE");

            return;
        }

        //移动文件
        if (!(file_put_contents($this->filePath, $img) && file_exists($this->filePath))) { //移动失败
            $this->stateInfo = $this->getStateInfo("ERROR_WRITE_CONTENT");
        } else { //移动成功
            $this->stateInfo = $this->stateMap[0];
        }
    }

    /**
     * 上传错误检查
     * @param $errCode
     * @return string
     */
    private function getStateInfo($errCode) {
        return !$this->stateMap[$errCode] ? $this->stateMap["ERROR_UNKNOWN"] : $this->stateMap[$errCode];
    }

    /**
     * 获取文件扩展名
     * @return string
     */
    private function getFileExt() {
        return strtolower(strrchr($this->oriName, '.'));
    }

    /**
     * 重命名文件
     * @return string
     */
    private function getFullName() {
        //替换日期事件
        $t = time();
        $d = explode('-', date("Y-y-m-d-H-i-s"));
        $format = $this->config["pathFormat"];
        $format = str_replace("{yyyy}", $d[0], $format);
        $format = str_replace("{yy}", $d[1], $format);
        $format = str_replace("{mm}", $d[2], $format);
        $format = str_replace("{dd}", $d[3], $format);
        $format = str_replace("{hh}", $d[4], $format);
        $format = str_replace("{ii}", $d[5], $format);
        $format = str_replace("{ss}", $d[6], $format);
        $format = str_replace("{time}", $t, $format);

        //过滤文件名的非法自负,并替换文件名
        $oriName = substr($this->oriName, 0, strrpos($this->oriName, '.'));
        $oriName = preg_replace("/[\|\?\"\<\>\/\*\\\\]+/", '', $oriName);
        $format = str_replace("{filename}", $oriName, $format);

        //替换随机字符串
        $randNum = rand(1, 10000000000) . rand(1, 10000000000);
        if (preg_match("/\{rand\:([\d]*)\}/i", $format, $matches)) {
            $format = preg_replace("/\{rand\:[\d]*\}/i", substr($randNum, 0, $matches[1]), $format);
        }

        $ext = $this->getFileExt();

        return $format . $ext;
    }
    /**
     * 获取文件所在文件夹
     * @return string
     */
    private function getVideoThumb() {
        //替换日期事件
        $t = time();
        $d = explode('-', date("Y-y-m-d-H-i-s"));
        $format = $this->config["pathFormat"];
        $format = str_replace("{yyyy}", $d[0], $format);
        $format = str_replace("{yy}", $d[1], $format);
        $format = str_replace("{mm}", $d[2], $format);
        $format = str_replace("{dd}", $d[3], $format);
        $format = str_replace("{hh}", $d[4], $format);
        $format = str_replace("{ii}", $d[5], $format);
        $format = str_replace("{ss}", $d[6], $format);
        $format = str_replace("{time}", $t, $format);
        
        //过滤文件名的非法自负,并替换文件名
        $oriName = substr($this->oriName, 0, strrpos($this->oriName, '.'));
        $oriName = preg_replace("/[\|\?\"\<\>\/\*\\\\]+/", '', $oriName);
        $format = str_replace("{filename}", $oriName, $format);

        //替换随机字符串
        $randNum = rand(1, 10000000000) . rand(1, 10000000000);
        if (preg_match("/\{rand\:([\d]*)\}/i", $format, $matches)) {
            $format = preg_replace("/\{rand\:[\d]*\}/i", substr($randNum, 0, $matches[1]), $format);
        }
        return $format;
    }

    /**
     * 获取文件名
     * @return string
     */
    private function getFileName() {
        return substr($this->filePath, strrpos($this->filePath, '/') + 1);
    }

    /**
     * 获取文件完整路径
     * @return string
     */
    private function getFilePath() {
        $fullname = $this->fullName;
        $rootPath = $_SERVER['DOCUMENT_ROOT'];

        if (substr($fullname, 0, 1) != '/') {
            $fullname = '/' . $fullname;
        }

        return $rootPath . $fullname;
    }

    /**
     * 文件类型检测
     * @return bool
     */
    private function checkType() {
        return in_array($this->getFileExt(), $this->config["allowFiles"]);
    }

    /**
     * 文件大小检测
     * @return bool
     */
    private function checkSize() {
        return $this->fileSize <= ($this->config["maxSize"]);
    }

    /**
     * 获取当前上传成功文件的各项信息
     * @return array
     */
    public function getFileInfo() {
        return [
            "state" => $this->stateInfo,
            "path" => $this->fullName,
            "url" => $this->fullName,
            "title" => $this->fileName,
            "original" => $this->oriName,
            "name" => $this->oriName,
            "type" => $this->fileType,
            "size" => $this->fileSize,
            "cover_id" => $this->cover_id,
            "id" => $this->cover_id,
            "isnewfile"=>  $this->isnewfile,
            "thumb" => $this->thumb
        ];
    }

}
