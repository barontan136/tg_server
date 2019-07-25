<?php

namespace app\mp\controller;
use app\common\controller\MpBase;
use app\mp\model\Customer as C;
use app\mp\model\CustomerAddress;
use app\mp\model\BonusRecord;
use app\mp\model\BonusRule;
use app\common\model\User;


/**
 * Description of Customer
 *
 * @author ZHXB
 */
class Customer extends MpBase{
    
    protected $customer_model;
    protected $customer_address_model;
    protected $bonus_record_model;
    protected $bonus_rule_model;
    protected $user_model;

    protected function _initialize() {
        parent::_initialize();
        $this->customer_model = new C();
        $this->customer_address_model = new CustomerAddress();
        $this->bonus_record_model = new BonusRecord();
        $this->bonus_rule_model = new BonusRule();
        $this->user_model = new User();
    }
    
    /**
     * 列表页
     */
    public function index($page=1){
        $where=[];
        $telephone=input('telephone');
        $name=input('name');
        $nickname=input('nickname');
        if (!empty($telephone)) {
            $where['c.telephone'] = ['LIKE', '%' . $telephone . '%'];
            $this->assign('telephone',$telephone);
        }
        if (!empty($name)) {
            $where['c.name'] = ['LIKE', '%' . $name. '%'];
            $this->assign('name',$name);
        }
        if (!empty($nickname)) {
            $where['u.nickname'] = ['LIKE', '%' . $nickname. '%'];
             $this->assign('nickname',$nickname);
        }
        if(request()->isAjax()){
            return $this->fetch('indexajax',['_list'=> $this->customer_model->getPage($page, $where)]);
        }else{
            return $this->fetch('',['_list'=> $this->customer_model->getPage($page, $where)]);
        }
    }
    
    public function add(){
       if ($this->request->isPost()) {
            $data = $this->request->param();
            $res = $this->validate($data, 'Customer');
            $data['merchant_id']=$this->merchant_id;
            $data['update_time']=  time();
            if(!empty($data['birthday'])){
                $data['birthday']=strtotime($data['birthday']." 00:00:00");
            }
            if($res === TRUE){
                $where['telephone']=$data['telephone'];
                $where['merchant_id']=$this->merchant_id;
                $info= $this->customer_model->getCustomerInfo($where);
                if($info){
                     $this->error('该用户已经存在，请到列表查找以后更新数据！',url('index'));
                }
                beginTransaction();
                $bonus=$data['bonus'];
                unset($data['bonus']);
                $result = $this->customer_model->initCustomer($data);
                if(!$result){
                    rollbackTransaction();
                    $this->error('添加失败！');
                }
                $bonus_record=[
                    'customer_id'=>$result,
                    'merchant_id'=>$this->merchant_id,
                    'source'=>2,
                    'bonus'=>$bonus,
                    'type'=>1,
                    'remark'=>'线下消费'
                    ];
                $rt=$this->bonus_record_model->initBonus($bonus_record);
                if($rt){
                    commitTransaction();
                    $this->success('添加成功！',url('index'));
                }
                rollbackTransaction();
                $this->error('添加失败！');
            } else {
                $this->error($res);
            }
        } else {
            return $this->fetch();
        } 
    }
    public function edit($id=0){
       if ($this->request->isPost()) {
            $data = $this->request->param();
            $res = $this->validate($data, 'Customer');
            $data['merchant_id']=$this->merchant_id;
            if(!empty($data['birthday'])){
                $data['birthday']=strtotime($data['birthday']." 00:00:00");
            }
            if($res === TRUE){
//                beginTransaction();
                $where['id']=$data['id'];
                $bonus=$data['bonus'];
                unset($data['bonus']);
                $result = $this->customer_model->updateCustomer($data,$where);
                if(!$result){
                    $this->error('更新失败！');
                }
                $this->success('更新成功！',url('index'));
            } else {
                $this->error($res);
            }
        } else {
            $where['id']=$id;
            $where['merchant_id']=$this->merchant_id;
            $info= $this->customer_model->getCustomerInfo($where);
            if($info['user_id']>0){
               $map['user_id']= $info['user_id'];
            }else{
               $map['customer_id']= $info['id'];
            }
            $map['merchant_id']= $this->merchant_id;
            $map['status']= 1;
            $info['bonus'] = $this->bonus_record_model->getCustomerBonus($map);
            return $this->fetch('',['info'=>$info]);
        } 
    }

    public function bonusRecord($page=1){
       $where=[];
        $telephone=input('telephone');
        $name=input('name');
        $nickname=input('nickname');
        $source=input('source');
        if (!empty($telephone)) {
            $where['c.telephone|u.telephone'] = ['LIKE', '%' . $telephone . '%'];
            $this->assign('telephone',$telephone);
        }
        if (!empty($nickname)) {
            $where['u.nickname'] = ['LIKE', '%' . $nickname . '%'];
            $this->assign('nickname',$nickname);
        }
        if (!empty($name)) {
            $where['c.name'] = ['LIKE', '%' . $name. '%'];
            $this->assign('name',$name);
        }
        if (!empty($source)) {
            $where['br.source'] = $source;
            $this->assign('source',$source);
        }
       $lt=$this->bonus_record_model->getPage($page,$where);
        $sourceList = $this->bonus_record_model->sourceList();
        if(request()->isAjax()){
            return $this->fetch('bonus_record_ajax',['_list'=>$lt,'sourceList'=>$sourceList ]);  
        }
        return $this->fetch('',['_list'=>$lt,'sourceList'=>$sourceList ]);  
    }
    
     //更新积分
    public function updateBonus(){
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $arr=['2'=>'购买产品名称','3'=>'购买产品名称','4'=>'活动名称','5'=>'商品名称','6'=>'原因说明'];
            if(empty($data['name'])){
                $msg=$arr[$data['change_type']].'必须填写！';
              return ['code'=>0,'msg'=>$msg];   
            }
            if($data['change_type']!=6){
                $resss = $this->validate($data, 'BonusRecord.other'.$data['change_type']);
                if($resss !== TRUE){
                     return ['code'=>0,'msg'=>$resss]; 
                }
            }
            $remark='';
            $remark_title='';
            $remark_sub='';
            switch ($data['change_type']){
                case '2':
                    $remark='线下消费--购买'.$data['name'].'；支付金额'.$data['price'].'元；获得积分'.$data['bonus'].'，支付时间'.$data['pay_time'];
                    $remark_title='线下消费';
                    $remark_sub='购买'.$data['name'].'，支付金额'.$data['price'].'元，获得积分'.$data['bonus'].'！';
                    break;
                case '3':
                    $remark='线下消费--购买'.$data['name'].'支付金额'.$data['price'].'元；扣除积分'.$data['bonus'].'；支付时间'.$data['pay_time'];
                    $remark_title='线下消费';
                    $remark_sub='购买'.$data['name'].'支付金额'.$data['price'].'元，扣除积分'.$data['bonus'].'！';
                    break;
                case '4':
                    $remark='活动赠送--参与'.$data['name'].'；获得积分'.$data['bonus'].'；参与时间'.$data['pay_time'];
                    $remark_title='线下消费';
                    $remark_sub='参与'.$data['name'].'，获得积分'.$data['bonus'].'！';
                    break;
                case '5':
                    $remark='线下消费--兑换'.$data['name'].'；扣除积分'.$data['bonus'].'；兑换时间'.$data['pay_time'];
                    $remark_title='线下消费';
                    $remark_sub='兑换'.$data['name'].'，扣除积分'.$data['bonus'].'！';
                    break;
                case '6':
                    $remark='其他原因--'.$data['name'];
                    $remark_title=$data['name'];
                    break;
            }
            $bonus_record=['customer_id'=>$data['customer_id'],'user_id'=>$data['user_id'],'merchant_id'=>$this->merchant_id,'source'=>$data['change_type'],'bonus'=>$data['bonus'], 'type'=>$data['type'],'remark'=>$remark];
            beginTransaction();
            $rt=$this->bonus_record_model->initBonus($bonus_record);
            $where['id']=$data['customer_id'];
            if($rt){
                 if($data['user_id']>0){
                    $map['user_id']= $data['user_id'];
                 }else{
                    $map['customer_id']= $data['customer_id'];
                 }
                $map['merchant_id']= $this->merchant_id;
                $map['status']= 1;
                $info['bonus'] = $this->bonus_record_model->getCustomerBonus($map);
                commitTransaction();
                if($data['user_id']>0){
                    $info_user=$this->user_model->where(['id'=>$data['user_id']])->find()->toArray();
                    if($data['type']==1){
                       $change= '+'.$data['bonus'];
                    }else{
                       $change= 0-$data['bonus'];
                    }
                    $userName=  empty($info_user['name'])?$info_user['nickname']:$info_user['name'];
                    \app\utils\service\TempMsgService::bonusChange($info_user['openid'], $userName, date('Y-m-d H:i:s',time()), $change, $info['bonus'], $remark_title, '',$remark_sub, '',$this->appid);
                }
                return ['code'=>1,'msg'=>'积分更新成功！','data'=>$info];   
            }else{
                rollbackTransaction();
                return ['code'=>0,'msg'=>'积分更新失败！'];   
            }
        }
    }
    
    //根据输入金额，计算可获得积分
    public function getBonus(){
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $rule_info= $this->bonus_rule_model->getInfoWorking($this->merchant_id);
            if($rule_info){
                $getBonus= get_bonus_pay_order_bonus($rule_info, $data['price']*100);//最终价格，单位分
                if($getBonus<0){
                  $getBonus=0;  
                }
                return ['code'=>1,'msg'=>'根据支付金额与积分规则，应获得积分已经计算完成！','data'=>$getBonus];
            }else{
               return ['code'=>0,'msg'=>'当前没有开启的积分规则，如需添加积分，请更换添加模式！'];  
            }
        }
    }
}
