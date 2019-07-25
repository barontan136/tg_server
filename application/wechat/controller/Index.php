<?php

namespace app\wechat\controller;

use wechat\Wechat;
use app\utils\service\ConfigService;
use app\common\model\WechatKeys;
use app\common\model\Picture;
use app\common\model\WechatNewsArticle;
use app\common\model\WechatNews;
use app\common\model\File;
use app\common\model\User;
use app\common\model\WechatMenu;
use app\common\model\ComponentAppid;
use app\api\model\BonusRule;
use app\api\model\BonusRecord;

class Index {

    private $openid;
    private $model_user;
    private $model_keys;
    private $model_picture;
    private $model_news;
    private $model_article;
    private $model_file;
    private $model_menu;
    private $user;
    private $weObj;
    private $model_component_appid;
    private $merchant_id;
    private $appid;
    private $data;
    private $bonus_rule_model;
    private $bonus_record_model;

    public function __construct($weObj) {
        //关闭trace
        config('app_trace', false);
        $this->weObj = $weObj;
        //加载系统配置
        ConfigService::config();
        $this->model_user = new User();
        $this->model_keys = new WechatKeys();
        $this->model_picture = new Picture();
        $this->model_news = new WechatNews();
        $this->model_article = new WechatNewsArticle();
        $this->model_file = new File();
        $this->model_menu = new WechatMenu();
        $this->model_component_appid = new ComponentAppid();
        $this->bonus_rule_model = new BonusRule();
        $this->bonus_record_model = new BonusRecord();
    }

    /**
     * 微信消息接口入口
     * 所有发送到微信的消息都会推送到该操作
     * 所以，微信公众平台后台填写的api地址则为该操作的访问地址
     * 在mp.weixin.qq.com 开发者中心配置的 URL(服务器地址)  
     */
    public function index() {
        //获取微信发来的信息
        $this->data = $this->weObj->getRevData(); 
        empty($this->data)&&exit('');
        //获取merchant_id
        $this->merchant_id = $this->model_component_appid->getMerchantIdByUserName($this->data['ToUserName']);
        empty($this->merchant_id) && exit('');
        //获取appid
        $this->appid = $this->model_component_appid->getAppidByMerchantId($this->merchant_id);
        empty($this->appid) && exit('');
        
        $this->weObj->access_token = $this->weObj->getAuthorizerAccessToken($this->appid);
        $this->openid = $this->data['FromUserName'];
        //初始化用户    
        $this->user = $this->model_user->initUser(['merchant_id' => $this->merchant_id, 'openid' => $this->openid, 'last_login_time' => time()], $this->appid);
        $type = $this->data['MsgType'];
        //与微信交互的中控服务器逻辑可以自己定义，这里实现一个通用的
        switch ($type) {
            //事件
            case Wechat::MSGTYPE_EVENT:         //先处理事件型消息
                $event = $this->getRevEvent();
                switch ($event['event']) {
                    //关注
                    case Wechat::EVENT_SUBSCRIBE:
                        // $qrscene_id = empty(str_replace('qrscene_', '', $data['EventKey'])) ? '0' : str_replace('qrscene_', '', $data['EventKey']); //扫码场景值
                        if (isset($event['eventkey']) && isset($event['ticket'])) {
                            //二维码关注
                        } else {
                            //普通关注
                        }
                        $this->subscribe();
                        break;
                    //扫描二维码
                    case Wechat::EVENT_SCAN:
                        // $qrscene_id = empty($data['EventKey']) ? '0' : $data['EventKey']; //扫码场景值
                        $this->subscribe();
                        break;
                    //地理位置
                    case Wechat::EVENT_LOCATION:
                        break;
                    //自定义菜单 - 点击菜单拉取消息时的事件推送
                    case Wechat::EVENT_MENU_CLICK:
                        $this->clickBtn($event['key']);
                        break;
                    //自定义菜单 - 点击菜单跳转链接时的事件推送
                    case Wechat::EVENT_MENU_VIEW:
                        break;
                    //自定义菜单 - 扫码推事件的事件推送
                    case Wechat::EVENT_MENU_SCAN_PUSH:

                        break;
                    //自定义菜单 - 扫码推事件且弹出“消息接收中”提示框的事件推送
                    case Wechat::EVENT_MENU_SCAN_WAITMSG:

                        break;
                    //自定义菜单 - 弹出系统拍照发图的事件推送
                    case Wechat::EVENT_MENU_PIC_SYS:

                        break;
                    //自定义菜单 - 弹出拍照或者相册发图的事件推送
                    case Wechat::EVENT_MENU_PIC_PHOTO:

                        break;
                    //自定义菜单 - 弹出微信相册发图器的事件推送
                    case Wechat::EVENT_MENU_PIC_WEIXIN:

                        break;
                    //自定义菜单 - 弹出地理位置选择器的事件推送
                    case Wechat::EVENT_MENU_LOCATION:

                        break;
                    //取消关注
                    case Wechat::EVENT_UNSUBSCRIBE:
                        $this->unsubscribe();
                        break;
                    //群发接口完成后推送的结果
                    case Wechat::EVENT_SEND_MASS:

                        break;
                    //模板消息完成后推送的结果
                    case Wechat::EVENT_SEND_TEMPLATE:

                        break;
                    //用户领取卡券事件
                    case Wechat::EVENT_CARD_USER_GET:
//                        $this->card_user_get($data, $user);
                        break;
                    //用户进入卡券事件
                    case Wechat::EVENT_USER_VIEW_CARD:
//                        $this->user_view_card($weObj, $data, $user);
                        break;
                    //用户激活会员卡
                    case Wechat::EVENT_SUBMIT_MEMBERCARD_USER_INFO:
//                        $this->submit_membercard_user_info($weObj, $data, $user);
                        break;
                    //用户删除卡券事件
                    case Wechat::EVENT_CARD_USER_DEL:
//                        $this->card_user_del($data, $user);
                        break;
                    default:

                        break;
                }
                break;
            //文本
            case Wechat::MSGTYPE_TEXT :
                $this->reply_keys($this->data['Content']);
                break;
            //图像
            case Wechat::MSGTYPE_IMAGE :

                break;
            //语音
            case Wechat::MSGTYPE_VOICE :

                break;
            //视频
            case Wechat::MSGTYPE_VIDEO :

                break;
            //位置
            case Wechat::MSGTYPE_LOCATION :

                break;
            //链接
            case Wechat::MSGTYPE_LINK :

                break;
            default:

                break;
        }
    }

    /**
     * 关注之后添加用户
     */
    private function subscribe() {
        //关注者信息
        $user = $this->user;
        //增加关注次数和设置关注标志位
        if (!isset($user['subscribe']) || empty($user['subscribe'])) {
            $user['subscribe'] = 1;
        }
        isset($user['subscribe_num']) ? $user['subscribe_num']+=1 : $user['subscribe_num'] = 1;
        $this->model_user->allowField(true)->save($user, ['id' => $user['id']]);
        //查看是否配置关注后自动回复
        if ($user['subscribe_num'] == 1) {
            //赠送积分
            $keys = $this->model_keys->where('merchant_id', $this->merchant_id)->where(['keys' => 'first_subcribe', 'status' => 0])->find()->toArray();
            empty($keys) && $keys = $this->model_keys->where('merchant_id', $this->merchant_id)->where(['keys' => 'subcribe', 'status' => 0])->find()->toArray();
        } else {
            $keys = $this->model_keys->where('merchant_id', $this->merchant_id)->where(['keys' => 'subcribe', 'status' => 0])->find()->toArray();
        }
        if (!empty($keys)) {
            $this->_keys($keys);
        }
        if($user['subscribe_num'] == 1){
            $this->giftBonus();
        }
//        else {
//            $this->weObj->text("欢迎关注我们！")->replyComponent();
//        }
    }

    /**
     * 取消关注
     */
    private function unsubscribe() {
        $user = $this->user;
        $user["subscribe"] = 0;     //取消关注设置关注状态为取消
        $user["unsubscribe_time"] = time();     //取消关注设置关注状态为取消
        $this->model_user->allowField(true)->save($user, ['id' => $user['id']]);
    }

    private function clickBtn($value) {
        if ($value == "固定按钮关键字") {
            $this->weObj->text('请问有什么可以帮您的？')->replyComponent();
        } else {
            //先匹配菜单是否独立配置了文本信息
            $content = $this->model_menu->where('merchant_id', $this->merchant_id)->where('type', 'text')->where('name', $value)->value('content');
            if (!empty($content)) {
                $this->reply_text($content);
            } else {
                $keys = $this->model_keys->where('merchant_id', $this->merchant_id)->where(['keys' => $value])->find()->toArray();
                if (!empty($keys)) {
                    $this->_keys($keys);
                }
            }
        }
    }

    /**
     * 关键字回复
     */
    private function reply_keys($content) {
        if ($content == "固定关键字") {
            $this->weObj->text('请问有什么可以帮您的？')->replyComponent();
        } else {
            $keys = $this->model_keys->where('merchant_id', $this->merchant_id)->where(['keys' => $content])->find()->toArray();
            if (!empty($keys)) {
                $this->_keys($keys);
            }
        }
    }

    /*     * *********************************************************消息回复*********************************************************************** */

    public function _keys($keys) {
        $ktype = $keys['type'];
        switch ($ktype) {
            case 'text':
                $this->reply_text($keys['content']);
                break;
            case 'news':
                $this->reply_news([], $keys['news_id']);
                break;
            case 'music':
                $this->reply_music($keys['music_title'], $keys['music_desc'], $keys['music_file_cover_id'], $keys['music_picture_cover_id']);
                break;
            case 'image':
                $this->reply_image($keys['image_picture_cover_id']);
                break;
            case 'video':
                $this->reply_video($keys['video_title'], $keys['video_desc'], $keys['video_file_cover_id']);
                break;
            case 'voice':
                $this->reply_voice($keys['voice_file_cover_id']);
                break;
            default:
                break;
        }
    }

    //公众号回复文本
    private function reply_text($content) {
        $this->weObj->text($content);
        $this->weObj->replyComponent();
    }

    //公众号回复图片
    private function reply_image($cover_id) {
        $image = $this->model_picture->where(['id' => $cover_id])->field('media_id,path')->find()->toArray();
        if (empty($image)) {
            $this->weObj->text('欢迎您！')->replyComponent();
        } else {
            $media_id = $image['media_id'];
            empty($media_id) && exit;
            $this->weObj->image($media_id);
            $this->weObj->replyComponent();
        }
    }

    //公众号图文消息回复
    private function reply_news($newsdata, $news_id) {
        if (empty($newsdata)) {
            $news = $this->model_news->where('id', $news_id)->field('article_id,media_id')->find()->toArray();
            if (!empty($news['media_id'])) {
                // 数据拼装
                $data = [
                    "touser" => $this->openid,
                    "msgtype" => "mpnews",
                    "mpnews" =>
                    [
                        "media_id" => $news['media_id']
                    ]
                ];
                $wechat = getWeObject();
                $wechat->sendCustomMessage($data);
                $this->weObj->replyComponent();
            } else {
                $artic_ids = (array) string2array($news['article_id']);
                $newsinfo = $this->model_article->where(['id' => ['in', $artic_ids]])->field('title,digest,local_url,id')->select()->toArray();
                $newsdata = [];
                foreach ($newsinfo as $vo) {
                    $newsdata[] = [
                        'Title' => $vo['title'],
                        'Description' => $vo['digest'],
                        'PicUrl' => WEB_PATH . $vo['local_url'],
                        'Url' => url("@admin/Review/_index", '', true, true) . "?content={$vo['id']}&type=article",
                    ];
                }
            }
            $this->weObj->news($newsdata);
        }
        $this->weObj->replyComponent();
    }

    //公众号回复音乐
    private function reply_music($title, $desc, $file_cover_id, $picture_cover_id) {
        $file = $this->model_file->where(['id' => $file_cover_id])->value('path');
        $media_id = '';
        if (!empty($picture_cover_id)) {
            $image = $this->model_picture->where(['id' => $picture_cover_id])->field('media_id,path')->find()->toArray();
            $media_id = $image['media_id'];
            empty($media_id) && exit('');
        }
        if (empty($file)) {
            $this->weObj->text('欢迎您！')->replyComponent();
        } else {
            $this->weObj->music($title, $desc, WEB_PATH . $file, '', $media_id);
            $this->weObj->replyComponent();
        }
    }

    //公众号回复视频
    private function reply_video($title, $desc, $file_cover_id) {
        $file = $this->model_file->where(['id' => $file_cover_id])->field('id,media_id,path')->find()->toArray();
        if (empty($file)) {
            $this->weObj->text('欢迎您！')->replyComponent();
        } else {
            $media_id = $file['media_id'];
            empty($media_id) && exit('');
            $this->weObj->video($media_id, $title, $desc);
            $this->weObj->replyComponent();
        }
    }

    //公众号语音回复
    private function reply_voice($file_cover_id) {
        $file = $this->model_file->where(['id' => $file_cover_id])->field('id,media_id,path')->find()->toArray();
        if (empty($file)) {
            $this->weObj->text('欢迎您！')->replyComponent();
        } else {
            $media_id = $file['media_id'];
            empty($media_id) && exit('');
            $this->weObj->voice($media_id);
            $this->weObj->replyComponent();
        }
    }

    /**
     * 获取接收事件推送
     */
    public function getRevEvent() {
        $array = [];
        if (isset($this->data['Event'])) {
            $array['event'] = $this->data['Event'];
        }
        if (isset($this->data['EventKey'])) {
            $array['key'] = $this->data['EventKey'];
        }
        if (isset($array) && count($array) > 0) {
            return $array;
        } else {
            return false;
        }
    }
    
    /**
     * 赠送积分
     */
    public function giftBonus(){
        $user = $this->user;
        $rule_info = $this->bonus_rule_model->getInfo($user['merchant_id']);
        if(!empty($rule_info) && $rule_info['init_increase_bonus']>0){
            $bonus_record = [
                'merchant_id'=>$user['merchant_id'],'customer_id'=>0,'user_id'=>$user['id'],'source'=>1,
                'bonus'=>$rule_info['init_increase_bonus'],'type'=>1,'remark'=>'首次关注获得积分','status'=>1,'create_time'=> time()
            ];
            $res = $this->bonus_record_model->createInfo($bonus_record);
            if($res){
                $user_name = $this->model_user->where('id',$user['id'])->value('nickname');
                $user_name = empty($user_name)?'':$user_name;
                \app\utils\service\TempMsgService::bonusChange($user['openid'],$user_name, date('Y-m-d H:i:s',$bonus_record['create_time']), '+'.$rule_info['init_increase_bonus'],$rule_info['init_increase_bonus'],$bonus_record['remark'],'','','',$this->appid);
            }
        }
    }
}
