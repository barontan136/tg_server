<?php

namespace app\api\controller;

use app\common\controller\ApiBase;
use app\api\model\User as Us;
use app\api\model\Customer as Cs;
use app\api\model\BonusRecord;
use app\api\model\Policy;
use app\api\model\CardCode;
use app\api\model\ActivityTurntableRecord;
use app\api\model\VerifyCode;
use app\api\model\Order;
use app\api\model\EarningsAll;
use app\api\model\MerchantShop;

/**
 * @apiDefine User 用户
 */
class User extends ApiBase
{

    protected $user_model;
    protected $customer_model;
    public function _initialize()
    {
        parent::_initialize();
        $this->user_model = new Us();
//        $this->customer_model = new Cs();
    }

    /**
     * @api {POST} api.php?s=/user/user 用户信息
     * @apiGroup User
     * @apiDescription 获取客户的微信信息。
     * @apiParam {String} code 微信用户授权凭证
     * @apiParamExample{object} 参数样例
     * {
     *  "code": "4e239941c94c147389e731289fa17285",
     * }
     * @apiSuccess (200) {string} msg 信息,成功返回success
     * @apiSuccess (200) {int} code 0 代表无错误
     * @apiSuccess (200) {object} data 返回数据
     * @apiSuccessExample {json} 返回样例:
     * {
     *   "code": 0,
     *   "msg": "success",
     *   "data": {
     *      "user_id":"1",//用户id
     *      "merchant_id":"1",//对应商家id
     *      "openid":"oEw3wv7ebIkv11aX-wlpb94vPHOk",//用户openid
     *      "headimgurl": "https://www.qq.com/skdu/skdji",//用户头像
     *      "nickname": "Simon",//用户昵称
     *      "sex": '男', //用户性别
     *      "area": '云南 昆明',//用户区域
     *      "isCustomer": true,//是否是会员
     *      "isSubscribe": true,//是否关注公众号
     *      "register_time": '2017-11-12 12:13:00', //用户首次访问系统时间
     *    }
     * }
     */
    public function user()
    {
        if ($this->request->isPost()) {
            $code = $this->request->post('code', '');
            if (empty($code)) return apiMsg([], 1, '授权失败');
            $user = $this->user_model->getUserByCode($code);
            if ($user === FALSE) {
                return apiMsg([], 1, '授权失败');
            }
            $res = ['user' => $user];
            if ($user !== FALSE) {
                //获取该用户所属商户的主店信息(地址和电话)
                $shop = $this->merchant_shop_model->getShopAddressAndPhone($user['merchant_id']);
                //判断是否已经注册会员
                $result = $this->customer_model->getCustomerByOpenid($user['openid']);
                if ($result !== FALSE) {
                    $res['customer'] = $result;
                }
                $res['shop'] = $shop;
                return apiMsg($res);
            } else {
                return apiMsg([]);
            }
        }
    }

    /**
     * @api {POST} api.php?s=/user/commonly 用户信息(客户端调用)
     * @apiGroup User
     * @apiDescription 获取客户的微信信息。
     * @apiParam {Int} user_id 用户id
     * @apiParam {Int} merchant_id 商户id
     * @apiParamExample{object} 参数样例
     * {
     *  "user_id": 14,
     *  "merchant_id": 1,
     * }
     * @apiSuccess (200) {string} msg 信息,成功返回success
     * @apiSuccess (200) {int} code 0 代表无错误
     * @apiSuccess (200) {object} data 返回数据
     * @apiSuccessExample {json} 返回样例:
     * {
     *   "code": 0,
     *   "msg": "success",
     *   "data": {
     *       "user": {
     *           "user_id": 11,
     *           "merchant_id": 1,
     *           "merchant_name": "测试商户",
     *           "openid": "oPkBUtwfaEsItedlWdqcxRLbA1E0",
     *           "isCustomer": false,
     *           "isSubscribe": true,
     *           "register_time": "2018-08-15 18:50:10",
     *           "telephone": "18976638037",
     *           "birthday": "",
     *           "name": "林冲",
     *           "xaddress": "",
     *           "is_bind": 1,
     *           "subscribe": 1,
     *           "headimgurl": "http:\/\/thirdwx.qlogo.cn\/mmopen\/qURtICNlvEhhmtpPBIAo68bXGQQ6OsBbzUf6dRmt4TMqjeflibY5b7kGhq6JNZKLbic76JO87aicaon20V3ftibLJNnnSS6eOMqQ\/132",
     *           "nickname": "林冲",
     *           "sex": "男",
     *           "area": "中国深圳"
     *       },
     *       "shop": {
     *           "address": "广东深圳市福田区下沙",
     *           "phone": "13418547378"
     *       }
     *    }
     * }
     */
    public function commonly()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            if ($data['user_id'] && $data['merchant_id']) {
                $user = $this->user_model->getUserByIdAndMerchantId($data['user_id'], $data['merchant_id']);
                $res = ['user' => $user];
                if ($user) {
                    //获取该用户所属商户的主店信息(地址和电话)
                    $shop = $this->merchant_shop_model->getShopAddressAndPhone($user['merchant_id']);
                    //判断是否已经注册会员
                    $result = $this->customer_model->getCustomerByOpenid($user['openid']);
                    if ($result !== FALSE) {
                        $res['customer'] = $result;
                    }
                    $res['shop'] = $shop;
                    return apiMsg($res);
                } else {
                    return apiMsg([]);
                }
            }
        }
    }

    /**
     *测试环境调用此方法获取openid
     */
    public function wxUser()
    {
        if ($this->request->isPost()) {
            $authKey = $this->request->param('authKey');
            $user = $this->user_model->customerAuthKey($authKey);
            $res = ['user' => $user];
            if ($user !== FALSE) {
                return apiMsg($res);
            } else {
                return apiMsg([]);
            }
        } else {
            $authKey = $this->request->param('authKey');
            $ref_user_id = $this->request->param('ref_user_id');
            $this->user_model->ref_user_id = $ref_user_id ?? 0;
            if (empty($authKey)) {
                alert('authKey不能为空');
            }
            empty($backUrl = urldecode(base64_decode($this->request->param('backUrl')))) && alert('回调地址不能为空！');
            if ($this->user_model->wxAuth($authKey)) {
                writerLog('wxUser');
                writerLog($backUrl);
                echo "<script>location.replace('" . $backUrl . "')</script>";
                exit;
            } else {
                alert('系统错误');
            }
        }
    }

    /**
     * @api {POST} api.php?s=/user/userCenterData 获取个人中心首页数据
     * @apiGroup User
     * @apiDescription 获取个人中心首页数据
     * @apiParam {Int} merchant_id 商户id
     * @apiParam {Int} user_id 会员user id
     * @apiParam {String} telephone 会员手机号，只有绑定手机号才能查看自已的收益，否则默认返回0值
     * @apiParamExample{object} 参数样例
     * {
     *  "merchant_id": 4,
     *  "user_id": 36,
     *  "telephone": '13530128354'
     * }
     * @apiSuccess (200) {string} msg 信息,成功返回success
     * @apiSuccess (200) {int} code 0 代表无错误
     * @apiSuccessExample {json} 返回样例:
     * {
     *   "code": 0,
     *   "msg": "success",
     *   "data": {
     *       "earnings_amount":"246.00元"
     *       "bonus": 0,//可用积分
     *       "card": 12,//可用卡券
     *       "policy": 0,//保单数量
     *       "prize": 3,//奖品数量
     *       "order": 520,//订单数量
     *   }
     * }
     */
    public function userCenterData()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            if (empty($data['user_id']) || empty($data['merchant_id'])) {
                return apiMsg([], 1, '数据不正确！');
            }
            $re_data = [];
            return apiMsg($re_data);
        } else {
            alert('请勿异常操作');
        }
    }

    /**
     * @api {POST} api.php?s=/user/updateuserinfo 个人信息更新
     * @apiGroup User
     * @apiDescription 个人信息更新
     * @apiParam {Int} merchant_id 商户id
     * @apiParam {Int} user_id 用户user id
     * @apiParam {string} name 姓名
     * @apiParam {string} telephone 手机号
     * @apiParam {string} birthday 生日
     * @apiParam {array} xaddress 地址
     * @apiParamExample{object} 参数样例
     * {
     *  "merchant_id": 4,
     *  "user_id": 36,
     *  "name": "王二",
     *  "telephone": "13312344321",
     *  "birthday": "2018-06-07",
     *  "xaddress": ["北京市","市辖区"],
     * }
     * @apiSuccess (200) {string} msg 信息,成功返回success
     * @apiSuccess (200) {int} code 0 代表无错误
     * @apiSuccessExample {json} 返回样例:
     * {
     *   "code": 0,
     *   "msg": "success",
     *   "data": {
     * "telephone": "13312344321",
     * "nickname": "王二麻子",
     * "openid": "oflMXuBwIID4xAYjrL0hmjFDD_1Y",
     * "name": "王二",
     * "birthday": "2018-06-07",
     * "xaddress": [
     * "北京市",
     * "市辖区",
     * ]
     * }
     * }
     */
    public function updateUserInfo()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            if (empty($data['user_id']) || empty($data['merchant_id'])) {
                return apiMsg([], 1, '数据不正确！');
            }
            $res = $this->validate($data, 'User.update_info');
            if ($res === true) {
                $re = $this->user_model->userInfoUpdate($data);
                if ($re) {
                    return apiMsg($re);
                } else {
                    return apiMsg(1, '保存失败，请重试！');
                }
            } else {
                return apiMsg([], 1, $res);
            }
        } else {
            alert('请勿异常操作');
        }
    }

    /**
     * @api {POST} api.php?s=/user/getuserInfo 获取个人信息
     * @apiGroup User
     * @apiDescription 获取个人信息
     * @apiParam {Int} user_id 用户user id
     * @apiParamExample{object} 参数样例
     * {
     *  "user_id": 36,
     * }
     * @apiSuccess (200) {string} msg 信息,成功返回success
     * @apiSuccess (200) {int} code 0 代表无错误
     * @apiSuccessExample {json} 返回样例:
     * {
     *   "code": 0,
     *   "msg": "success",
     *   "data": {
     * "nickname": "王二麻子",//昵称
     * "openid": "oflMXuBwIID4xAYjrL0hmjFDD_1Y",//openid
     * "name": "王二",//姓名
     * "birthday": "1993-05-05",//生日
     * "xaddress": [
     * "北京市",//省市
     * "市辖区",//市、区
     * ],
     * "telephone": "15912907983",//电话
     * "telephone_readonly": "no"//电话是否只读；yes只读；no可编辑
     * }
     * }
     */
    public function getUserInfo()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            if (empty($data['user_id'])) {
                return apiMsg([], 1, '数据不正确！');
            }
            $re = $this->user_model->getUserInfo($data['user_id']);
            if ($re) {
                return apiMsg($re);
            } else {
                return apiMsg(1, '获取不到数据，请重试！');
            }
        } else {
            alert('请勿异常操作');
        }
    }

    /**
     * @api {POST} api.php?s=/user/getishavepassword 判断密码状态
     * @apiGroup User
     * @apiDescription 判断密码状态
     * @apiParam {Int} user_id 用户user id
     * @apiParamExample{object} 参数样例
     * {
     *  "user_id": 36,
     * }
     * @apiSuccess (200) {string} msg 信息,成功返回success
     * @apiSuccess (200) {int} code 0 代表无错误
     * @apiSuccessExample {json} 返回样例:
     * {
     *   "code": 0,
     *   "msg": "success",
     *   "data": {
     *      "is_password":"ok"//ok:存在密码，no:没有密码；ben:密码输入错误过多（不可输入）
     *      "msg":'',//提示信息，当is_password值为ben时，msg为"输入错误过多，请在XXXX时间后重试！"
     *   }
     * }
     */
    public function getIsHavePassword()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            if (empty($data['user_id'])) {
                return apiMsg([], 1, '数据不正确！');
            }
            $re = $this->user_model->getIsHavePassword($data['user_id']);
            if ($re) {
                return apiMsg($re);
            } else {
                return apiMsg([], 1, '数据异常！');
            }
        } else {
            alert('请勿异常操作');
        }
    }

}
