<?php
namespace app\api\service;
use app\api\model\Merchant;
use app\api\model\MerchantScenicSpot;
use app\api\model\MerchantHotel;
use app\api\model\MerchantRecreation;
use app\api\model\MerchantCater;
class MicroService {

    const KEY = "zdwSuYqgAqqr6goC4IPQJvgntKTkW3bs";

    private $data;
    private $merchant_model;

    public function __construct($action_name) {
        $data = json_decode(file_get_contents("php://input"), true);
        if ($action_name == "scancode") {
            empty($data['nonce_str']) && exit($this->formatResult(['msg' => '缺少随机字符串']));
            empty($data['sign']) && exit($this->formatResult(['msg' => '缺少签名']));
            empty($data['device_no']) && exit($this->formatResult(['msg' => '缺少设备号']));
            empty($data['auth_code']) && exit($this->formatResult(['msg' => '缺少授权码']));
            strlen($data['auth_code']) < 16 && exit($this->formatResult(['msg' => '授权码不正确']));
            empty($data['total_fee']) && exit($this->formatResult(['msg' => '缺少支付金额']));
            empty($data['pp_trade_no']) && exit($this->formatResult(['msg' => '缺少派派订单号']));
        } elseif ($action_name == "queryorder") {
            empty($data['device_no']) && exit($this->formatResult(['msg' => '缺少设备好']));
            empty($data['pp_trade_no']) && exit($this->formatResult(['msg' => '缺少派派订单号']));
        } elseif ($action_name == "refund") {
            empty($data['device_no']) && exit($this->formatResult(['msg' => '缺少设备好']));
            empty($data['pp_trade_no']) && exit($this->formatResult(['msg' => '缺少派派订单号']));
            empty($data['refund_code']) && exit($this->formatResult(['msg' => '缺少退款订单号']));
            empty($data['refund_fee']) && exit($this->formatResult(['msg' => '缺少退款金额']));
        } elseif ($action_name == "billquery") {
            empty($data['device_no']) && exit($this->formatResult(['msg' => '缺少设备号']));
            empty($data['bill_begin_time']) && exit($this->formatResult(['msg' => '缺少开始时间']));
            empty($data['bill_end_time']) && exit($this->formatResult(['msg' => '缺少结束时间']));
            empty($data['bill_create_ip']) && exit($this->formatResult(['msg' => '缺少终端ip']));
        } elseif ($action_name == "cancelorder") {
            empty($data['device_no']) && exit($this->formatResult(['msg' => '缺少设备号']));
            empty($data['pay_type']) && exit($this->formatResult(['msg' => '缺少交易类型']));
            empty($data['nonce_str']) && exit($this->formatResult(['msg' => '缺少随机字符串']));
            empty($data['pp_trade_no']) && exit($this->formatResult(['msg' => '缺少派派订单号']));
            empty($data['sign']) && exit($this->formatResult(['msg' => '缺少签名']));
        }elseif ($action_name == "index") {//核销
            empty($data['device_no']) && exit($this->formatResult(['msg' => '缺少设备号']));
            empty($data['auth_code']) && exit($this->formatResult(['msg' => '缺少授权码']));
        }
        $this->data = $data;
        $this->merchant_model = new Merchant();
        $this->merchant_scenic_spot_model = new MerchantScenicSpot();
        $this->merchant_hotel_model = new MerchantHotel();
        $this->merchant_recreation_model = new MerchantRecreation();
        $this->merchant_cater_model = new MerchantCater();
    }

    //数据签名
    public function sign() {
        if (!empty($this->data['sign'])) {
            $signArr = $this->data;
            unset($signArr['sign']);
            array_filter($signArr);
            ksort($signArr);
            $signArr['key'] = self::KEY;
            $string = "";
            $c = 0;
            foreach ($signArr as $key => $val) {
                if ($c == 0) {
                    $string.="$key=$val";
                } else {
                    $string.="&$key=$val";
                }
                $c++;
            }
            if (strtoupper(md5($string)) != $this->data['sign']) {
                return FALSE;
            }
        }
        return true;
    }

    //获取所有的值
    public function getValues() {
        return $this->data;
    }

    //返回数据格式化
    public function formatResult($data) {
        $result = [
            'code' => $data['msg'] != "ok" ? 'FAIL' : 'SUCCESS',
            'msg' => $data['msg']
        ];
        unset($data['msg']);
        foreach ($data as $k=>$v){
            $result[$k] = $v;
        }
        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }
    
     /**
     * 检查设备是否已被绑定
     * @return boolean
     */
    public function checkMicroSn(){
        $smicr = 0;
        switch ($this->data['auth_code']) {
            case 'scenicSpot':
                $smicr = $this->merchant_scenic_spot_model->where('','exp',"find_in_set(device_no,".$this->data['device_no'].")")->value('id');
                break;
            case 'hotel':
                $smicr = $this->merchant_hotel_model->where('','exp',"find_in_set(device_no,".$this->data['device_no'].")")->value('id');
                break;
            case 'recreation':
                $smicr = $this->merchant_recreation_model->where('','exp',"find_in_set(device_no,".$this->data['device_no'].")")->value('id');
                break;
            case 'cater':
                $smicr = $this->merchant_cater_model->where('','exp',"find_in_set(device_no,".$this->data['device_no'].")")->value('id');
                break;
            default:
                break;
        }
        if($smicr){
            return $smicr;
        }
        return FALSE;
    }
}
