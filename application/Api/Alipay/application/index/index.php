<?php
session_start();
date_default_timezone_set('Asia/Shanghai'); 
class index
{
	var $db;
	public function  __construct($_db)
    {
        $this->db = $_db;
    }
    public function C_b_pay($param)
	{
        $_POST=$_GET['data'];
        $_POST=json_decode(stripslashes($_POST),true);
      
        if(isset($_POST['pay_type']) && !empty($_POST['pay_type'])){
            $pay_type=$_POST['pay_type'];
            if($pay_type==1){
                $acquirerType='wechat';
            }elseif ($pay_type==2) {
                $acquirerType='alipay';
            }elseif($pay_type==3){
                $acquirerType='qq';
            }
            $custId=$_POST['custId'];
        }else{
            $reslut['responseCode']="1112";
            $reslut['resultMsg']="pay_type不能为空";
            return json_encode($reslut);
        }
		$data['action']='wallet/trans/csbSale';
        $data['version']='2.0';
        $data['reqTime']= date("YmdHis");
        $data['orderId']=$_POST['order_id'];
        $data['reqId']=date("YmdHis").rand(1000,9999);
        $data['deviceId']='payuser';//终端号
        $data['transTimeOut']='1440';
        $data['orderSubject']=$_POST['orderSubject'];//订单抬头
        $data['orderDesc']=$_POST['orderDesc'];//订单描述
        //$data['totalAmount']=$totalAmount*100;//交易金额
        $data['totalAmount']=$_POST['totalAmount']*100;
        $data['bankCardLimit']=2;//银行卡限定类型，1 借记卡，2 借记卡和贷记卡，默认为 2
        $data['currency']="CNY";
        $data['notifyUrl']=HTTP_URL;
        $data['acquirerType']=$acquirerType;
        $data['operatorId']="POS 操作员";
        $data['custId']=$custId;
        $data=json_encode($data);
        $data="[".$data."]";
        file_put_contents("1.txt",date("Y-m-d H:i:s",time())."--".$data."\r\n",FILE_APPEND | LOCK_EX);
        $res=rsaSign($data,PRIVATE_KEY);
        $result=httpRequst(URL,$data,$res,APPKEY);
        file_put_contents("/nasdata/www/youngshop/application/Alipay/application/paylog/1.txt",date("Y-m-d H:i:s",time())."--".$data."--".$result."\r\n",FILE_APPEND | LOCK_EX);
        $QrcodeArr=json_decode($result,true);
        $QrcodeArr=$QrcodeArr['body'];
        $url="Location:".$QrcodeArr['qrCode'];
        header($url);exit;
        // echo '<img src="http://a.ypt5566.com/paytest/QrcodePaydemo/Qrcode.php?data='.urlencode($QrcodeArr['qrCode']).'"/>';
        // return $result;
	}
    public function B_c_pay($param){
        $list=$_POST;
        // $param['order_id']="1855";
        // $param['pay_type']=1;
        // $param['walletAuthCode']='130314188818780402';
        if(isset($list['walletAuthCode']) && !empty($list['walletAuthCode'])){
            $walletAuthCode=$list['walletAuthCode'];
        }else{
            $reslut['responseCode']="1115";
            $reslut['resultMsg']="付款码不能为空";
            return json_encode($reslut);
        }
        if(isset($list['pay_type']) && !empty($list['pay_type'])){
            $pay_type=$_POST['pay_type'];
            $custId=$_POST['custId'];
            if($pay_type==1){
                $acquirerType='wechat';
            }elseif ($pay_type==2) {
                $acquirerType='alipay';
            }elseif($pay_type==3){
                $acquirerType='qq';
            }
        }else{
            $reslut['responseCode']="1112";
            $reslut['resultMsg']="pay_type不能为空";
            return json_encode($reslut);
        }
        $data['action']='wallet/trans/bscSale';
        $data['version']='2.0';
        $data['reqTime']= date("YmdHis");
        $data['orderId']=$list['order_sn'];
        $data['reqId']=date("YmdHis").rand(1000,9999);
        $data['deviceId']='payuser';//终端号
        $data['transTimeOut']='1440';
        $data['orderSubject']='洋仆淘商城订单';//订单抬头
        $data['orderDesc']=$list['orderDesc'];//订单描述
        //$data['totalAmount']=$totalAmount*100;//交易金额
        $data['totalAmount']=$list['totalAmount']*100;
        $data['bankCardLimit']=2;//银行卡限定类型，1 借记卡，2 借记卡和贷记卡，默认为 2
        $data['currency']="CNY";
        $data['walletAuthCode']=$list['walletAuthCode'];//钱包付款吗
        $data['acquirerType']=$acquirerType;
        $data['operatorId']="POS 操作员";
        $data['custId']=$custId;
        $data=json_encode($data);
        $data="[".$data."]";
       
        $res=rsaSign($data,PRIVATE_KEY);
        $result=httpRequst(URL,$data,$res,APPKEY);
        file_put_contents("/nasdata/www/youngshop/application/Alipay/application/paylog/1.txt",date("Y-m-d H:i:s",time())."--".$data."--".$result."\r\n",FILE_APPEND | LOCK_EX);
        return $result;
    }
    public function js_pay($param){
        if(!isset($_POST['pay_type']) && empty($_POST['pay_type'])){
            $reslut['responseCode']="1111";
            $reslut['resultMsg']="pay_type不能为空";
            return json_encode($reslut);
        }
        $pay_type=$_POST['pay_type'];
        if(!isset($_POST['order_id']) && empty($_POST['order_id'])){
            $reslut['responseCode']="1111";
            $reslut['resultMsg']="pay_type不能为空";
            return json_encode($reslut);
        }
        $order_sn=$_POST['order_id'];
        if(!isset($_POST['openid']) && empty($_POST['openid'])){
            $reslut['responseCode']="1111";
            $reslut['resultMsg']="pay_type不能为空";
            return json_encode($reslut);
        }
        $uuid=$_POST['openid'];
         if(!isset($_POST['totalAmount']) && empty($_POST['totalAmount'])){
            $reslut['responseCode']="1111";
            $reslut['resultMsg']="totalAmount不能为空";
            return json_encode($reslut);
        }
        $totalAmount=$_POST['totalAmount'];
        $uuid=$_POST['openid'];
        if($pay_type==1){
            $acquirerType='wechat';
            $custId=$_POST['custId'];
        }elseif ($pay_type==2) {
            $acquirerType='alipay';
            $custId=$_POST['custId'];
        }
        
        $data['action']='wallet/trans/jsSale';
        $data['version']='2.0';
        $data['reqTime']= date("YmdHis");
        $data['appId']='wx3fa82ee7deaa4a21';
        $data['uuid']=$uuid;
        $data['orderId']=$order_sn;
        $data['reqId']=date("YmdHis").rand(1000,9999);
        $data['deviceId']='payuser';//终端号
        $data['transTimeOut']='1440';
        $data['orderSubject']=$_POST['orderSubject'];//订单抬头
        $data['orderDesc']=$_POST['orderDesc'];//订单描述
        $data['totalAmount']=$_POST['totalAmount']*100;//交易金额
        $data['bankCardLimit']=2;//银行卡限定类型，1 借记卡，2 借记卡和贷记卡，默认为 2
        $data['currency']="CNY";
        $data['notifyUrl']=HTTP_URL;
        $data['acquirerType']=$acquirerType;
        $data['operatorId']="POS 操作员";
        $data['custId']=$custId;
        $data=json_encode($data);
        $data="[".$data."]";
        
        $res=rsaSign($data,PRIVATE_KEY);
        $result=httpRequst(URL,$data,$res,APPKEY);
        file_put_contents("/nasdata/www/youngshop/application/Alipay/application/paylog/1.txt",date("Y-m-d H:i:s",time())."--".$data."--".$result."\r\n",FILE_APPEND | LOCK_EX);
        //return json_encode(array('data'=>$data,'result'=>$result));
        return $result;
    }
    public function alifun(){
       
        $data['action']='query/trans/detail';
        $data['version']='2.0';
        $data['reqTime']= date("YmdHis");
        $data['orderId']=$_POST['orderId'];
        $data['reqId']=$_POST['reqId'];
        $data['transId']=$_POST['transId'];
        $data['custId']=$_POST['custId'];
        $data=json_encode($data);
        $data="[".$data."]";
        $res=rsaSign($data,PRIVATE_KEY);
        $result=httpRequst(URL,$data,$res,APPKEY);
        return $result;
    }
    public function check(){
        $data['action']='wallet/trans/saleVoid';
        $data['version']='2.0';
        $data['reqTime']= date("YmdHis");
        $data['orderId']=$_POST['orderId'];
        $data['orgTransId']=$_POST['orgTransId'];
        $data['custId']=$_POST['custId'];
        $data['orgReqId']=$_POST['orgReqId'];
        $data['deviceId']='payuser';
        $data['operatorId']="POS 操作员";
        $data=json_encode($data);
        $data="[".$data."]";
        $res=rsaSign($data,PRIVATE_KEY);
        $result=httpRequst(URL,$data,$res,APPKEY);
        return $result;
    }
}
?>
