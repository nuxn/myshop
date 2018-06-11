<?php
namespace Xcx\Controller;
class BalanceController extends ApibaseController
{
	public function lists(){
			(I('page',0)) || err('page is empty');
			$data = D('Balance')->lists(UID,$page);
			succ($data);
	}
	public function log(){
			($page = I('page',0)) || err('page is empty');
			$data = D('BalanceLog')->lists(UID,$page,I('type',0));
			succ($data);
	}
   	public function create(){
				($price = I('price')) || err('price is empty');
				($type = I('type')) || err('type is empty');
				//生成订单 
				$data['mid'] = UID;
				$data['order_sn'] = date('YmdHis').UID.rand(1000000,9999999);
				$data['price'] = $price;
				$data['add_time'] = time();
				$order_id = M('balance')->add($data);
				//生成签名
				$sign = $this->get_sign($order_id,$type);
				succ(array('sign'=>$sign,'price'=>$price));
   	}
   public function get_sign($order_id,$type){
				//查询订单 
				$data = M('balance')->where(array('id'=>$order_id))->find();
				
				empty($data) && err('该订单不存在');
				($data['status']==1) && err('该订单已经支付');
				($data['status']==0) || err('该订单不能支付');
				switch($type){
						case 'wx':
						return $this->wx_pay($data['order_sn'],$data['price']);
						break;
						case 'zfb':
						return $this->zfb_pay($data['order_sn'],$data['price']);
						break;
						default:
						err('type is wrong');
						break;
				}
	}
	public function zfb_pay($order_sn,$price){
		 	// 支付宝合作者身份ID，以2088开头的16位纯数字
			$partner = "2017010704905089";
			// 支付宝账号
			$seller_id = 'guoweidong@hz41319.com';
			// 商品网址
			// 异步通知地址
			//$notify_url = 'http://sy.youngport.com.cn/index.php?s=/Pay/Barcode/ali_barcode_pay';
			$notify_url = 'http://sy.youngport.com.cn/notify/balance_notify.php';
			// 订单标题
			$subject = '余额充值';
			// 订单详情
			$body = '余额充值'; 
			// 订单号，示例代码使用时间值作为唯一的订单ID号
			$content = array();
			$content['timeout_express'] = '30m';
			$content['product_code'] = 'QUICK_MSECURITY_PAY';
			$content['total_amount'] = $price;
			$content['subject'] = $subject;
			$content['body'] = $body;
			$content['out_trade_no'] = $order_sn;
			//$orderinfo['order_amount'];
			$data = array();
			$data['app_id'] = $partner;
			$data['biz_content'] = json_encode($content);
			$data['charset'] = 'utf-8';
			$data['format'] = 'json';
			$data['method'] = 'alipay.trade.app.pay';
			$data['notify_url'] = $notify_url;
			$data['sign_type'] = 'RSA';
			$data['timestamp'] = date('Y-m-d H:i:s');
			$data['version'] = '1.0';
	 		$orderInfo = $this->createLinkstring($data);
	 		//$orderInfo = 'biz_content={"timeout_express":"30m","product_code":"QUICK_MSECURITY_PAY","total_amount":"0.01","subject":"1","body":"我是测试数据","out_trade_no":"0603181557-1017"}&method=alipay.trade.app.pay&charset=utf-8&version=1.0&app_id=2017010704905089&timestamp=2016-07-29 16:55:53&sign_type=RSA';
	 		//var_dump($orderInfo);
	 		$sign = $this->sign($orderInfo);
	 		//var_dump($sign);
	 		$data['sign'] = $sign;
	 		$orderInfo = $this->getSignContentUrlencode($data);
	 		//var_dump($orderInfo);
	 		//$orderInfo .= '&sign='.urlencode($sign);
	 		//$orderInfo = "biz_content=%7B%22timeout_express%22%3A%2230m%22%2C%22product_code%22%3A%22QUICK_MSECURITY_PAY%22%2C%22total_amount%22%3A%220.01%22%2C%22subject%22%3A%221%22%2C%22body%22%3A%22%E6%88%91%E6%98%AF%E6%B5%8B%E8%AF%95%E6%95%B0%E6%8D%AE%22%2C%22out_trade_no%22%3A%220603181557-1017%22%7D&method=alipay.trade.app.pay&charset=utf-8&version=1.0&app_id=2017010704905089&timestamp=2016-07-29+16%3A55%3A53&sign_type=RSA&sign=YZPNvZRrerdHsGrcWx9O3IimjMEXGvPeWQcOt8e71eZgo5xedgzn2wDH5nKAX9TEKWa9kDOT7DorsSfYpXST8AQkquzNTqyqzB%2BWmtD4D6Xk73emfJaokbqYNl560rZ01i2mCmdhksgBq2%2F9hgcmPU%2FBzsPlKbw2Zamd50ZWPKE%3D";
	 		
	 		return $orderInfo;
	}
	public function getSignContentUrlencode($params){
		$sign = $params['sign'];
		unset($params['sign']);
		ksort($params);
		$params['sign'] = $sign;
		$stringToBeSigned = "";
		$i = 0;
		foreach ($params as $k => $v) {
			if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {
				// 转换成目标字符集
				if ($i == 0) {
					$stringToBeSigned .= "$k" . "=" . urlencode($v);
				} else {
					$stringToBeSigned .= "&" . "$k" . "=" . urlencode($v);
				}
				$i++;
			}
		}
		unset ($k, $v);
		return $stringToBeSigned;
	}
	
	public function createLinkstring($params){
					  ksort($params);
					$stringToBeSigned = "";
					$i = 0;
					foreach ($params as $k => $v) {
						if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {
			
							// 转换成目标字符集
							$v = $this->characet($v, 'utf-8');
			
							if ($i == 0) {
								$stringToBeSigned .= "$k" . "=" . "$v";
							} else {
								$stringToBeSigned .= "&" . "$k" . "=" . "$v";
							}
							$i++;
						}
					}
					unset ($k, $v);
					return $stringToBeSigned;
   	}
   	protected function checkEmpty($value){
		if (!isset($value))
			return true;
		if ($value === null)
			return true;
		if (trim($value) === "")
			return true;

		return false;
	}
	protected function sign($data, $signType = "RSA") {
		$priKey="MIICXAIBAAKBgQC/UIMSw0mWKRp3wP3v0tbKKqtQO80iL3gBkceCE41KRtpE8+ljXzH16jXs5Alj3cPNZlAh+2SApLBv9sVY9nGU8rd6d5294HHH8APDdrHBtdUTpgZnKGNATFhCeiZPuLD76DJslWtSM4a8kW9EkBUDe3mFLtDQwu+ZtbUYf0k8eQIDAQABAoGALzKFo5NaDBmH1hNeklPJeYadTOXz7YMYcAqu1YBNUw23u1sRMNrDUI+/TfnT9zc2nu2mxztlx/bZMEYI2bGyw0Y/3oYl4GHDmeSyqq9o6SjL9S6GRtj+ngG8CX9QuVj7lTqcvHJrBR8E48EiyH4VK9ouySIHE9Ukf71VQVPd0AECQQD5uCTXuCd6aGbs2XqTfX/tfaUio5lFsdvTrccnFdTvDJ/EWwuMXgceJvehsOmNvZK8NabruGkyAk25ABM9bxq5AkEAxCBR2xvTVSBf/ohpB1/y94Imx1pb5OLvrRMvxq3LEcQORDzbSQdY27UZ1i3tC8CLvPF68KE985j75xgY//9PwQJBALx10LhM7t5etG7DotJ0wHtHe70sopwKotCaMda5jz3p1RmnbIu+2rhSyEhq75hdHcSU6Si0wA9R1b5s5BhVJWECQClVdmrrLlree9y0+w2m1xn6wBl7napbeY/MX2FL92RDIY6YFM8LUVrcjBlrjG3RoqGrcvGLyfaw40YM+hfZwwECQFNwNJ6wB5INtt+CeJkJSiaHuhevFmr3w8UD00dOXxTMz2m86cmV+ZNl5srCkeunKdwVCMwNhUHFfLfCno3XyEk=";
		//$priKey="MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBAL6EhsF9ufhXqx5ZJwGy5MLP5AcoFsp1I3hWpJgWwLSXKSRM5mkKmp/OOLltJtIF+ViKk1nOgE99J3C9yFjoXV9PWtNhClZmvOk+qAGweC4rzkjumhNC5vTnYf11Hp2+oes5vWMm7DAFFx/owNecNrlQl9cHQCj96pcElWFrhYhNAgMBAAECgYEAln5nWEbxdWwDHwj7mArxS7YegUy4nBrl9vQyNnWaqczSUftw8r7On7et9UN0q+jOK5Pji8hkcOYDFrrDnP+IaRX6KVMYjL4sHltoj+XlEWnUdz5B9MIlKg6ops1aEd4d5PFD+ixw5yvbEsc9nXaKz+8ttm2w+7LWkUTEGres6t0CQQD+paORxMv7APKSlKtzyOw0m6Xr7cydwtJqWexzOI8whfud7ODJV2VEmsJMfsh7HCxpeJET/9Rt5jq9P51ZicbrAkEAv4epQ3xaNUFfkFgYn94V8gGP0K11LrFhB30/MvWGHEuPt+/2ZiF9hXmyeIIktW3QDTcwfd0hfHAzkwgrurcPpwJAUUsbztteq0EAL59apNoN3jWaYJlH601Y0y7l91qlC76aNy56DIzj/WTSho0q/3JdE0a0OghADt2i/uuiFgWQBQJAVFnr6uPWWsP60XhrB+VoZtfXPcFW7YSDRigb8FZ/hPCmUAznyJ0RSfqJ5lby0dCWI2vd+GCuQb6siCG+GJJM2wJATROJfcSEWwNahKNCykUeN8eDd8Iv4Ko1uixynvnMdZZB8YgVQ4C0Y09RBtzi7Dt1StF1aYlAqn9T/ryhFMoP3A=="
		$res = "-----BEGIN RSA PRIVATE KEY-----\n" .
				wordwrap($priKey, 64, "\n", true) .
				"\n-----END RSA PRIVATE KEY-----";
	
		($res) or die('您使用的私钥格式错误，请检查RSA私钥配置'); 

		if ("RSA2" == $signType) {
			openssl_sign($data, $sign, $res, OPENSSL_ALGO_SHA256);
		} else {
			openssl_sign($data, $sign, $res);
		}
		openssl_free_key($res);
		$sign = base64_encode($sign);
		return $sign;
	}
	/**
	 * 转换字符集编码
	 * @param $data
	 * @param $targetCharset
	 * @return string
	 */
	public function characet($data, $targetCharset) {
		
		if (!empty($data)) {
			$fileType = $this->fileCharset;
			if (strcasecmp($fileType, $targetCharset) != 0) {
				$data = mb_convert_encoding($data, $targetCharset, $fileType);
				//				$data = iconv($fileType, $targetCharset.'//IGNORE', $data);
			}
		}
		return $data;
	}
	// 签名生成订单信息
	public function rsaSign($data){
		   $rsaPrivateKey='MIICXgIBAAKBgQDktchFUfoxjoaGGTOY5/S9cpWWon6Gc2AmmwgEwmqCHMnUIPRMa9nYfJEJo0lnJsJTUfmjwvuyWmKLyBdLjvEIbvvNMHtddKev5WfO4SEU24E2OPeGAQdxFcM89mBHxgcYoGIU7W8WUKTwY2oTjNoFuHG4SFsSBr8FdB0K0E9T/QIDAQABAoGBAMgZz5XuymKvWz1aMU2XrAZQiVZY5zBFI5vDSjm3y634+BCzoGp4dgm1usPe7Crmu2BguXSw9Lwv3kaEEvWVo4VgmOmpUTPBRxBOkSlbEH3zxsy2JtzcSV0dGLzkWlsmWymOCktk7XwKj9KCNLcfyiygIvWlI/sJRLg6fz+X2PN1AkEA/O+1LbRYLP7IvF6KO8Q0+G4/FdNThreoTwuO7ve32mUMBQOFrej08mbt9XAFuB+t7L3FKqEa7Tqq4uoiFpxSRwJBAOd69Hm4sE5bi59qO3hUCxlyLUaw5+PnmWv90wlGCNxgNYZWQyNJES74ifD4Jo3Ya2vJI9azF+MbaxXkLt1w5ZsCQQCPw6eVPMZJfN+XwZyMb+8zrWYJ/72f+s+dbhJl3UMQzRJR3ziiKqDfDoX+VRfLGaZ/wzVID54AbLIom6+Ybm2NAkEAhKZdOvOvdPfZBz2lXssqoSZg88Wk3WF29f+60/GiWkd35MfCKZJRmo0q30AVN+vxgw78zqhK5AZuU1kz0gpESQJAboDgERMcwS5e9ib9ax5lPzsAQ5F5JVUHH0VogEzpx6GjAzPp3/94zoCMEHTBpGy2ABpiyjTJvVM2Aaa0qAfmvw==';
		   $priKey=$rsaPrivateKey;
		   $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
		   wordwrap($priKey, 64, "\n", true) .
				"\n-----END RSA PRIVATE KEY-----";
		    $res = openssl_get_privatekey($res);
		    
		   	($res) or die('您使用的私钥格式错误，请检查RSA私钥配置');
		   
		    openssl_sign($data, $sign, $res);
		    openssl_free_key($res);
		    $sign = base64_encode($sign);
		    $sign = urlencode($sign);
		    return $sign;
	}
	public function zfb_notify_url(){
				//$data= '{"total_amount":"0.01","buyer_id":"2088702133211466","trade_no":"2017060521001004460255924689","body":"\u6211\u662f\u6d4b\u8bd5\u6570\u636e","notify_time":"2017-06-05 14:41:58","subject":"1","sign_type":"RSA","buyer_logon_id":"188****4165","auth_app_id":"2017010704905089","charset":"utf-8","notify_type":"trade_status_sync","invoice_amount":"0.01","out_trade_no":"20170605144154469765694","trade_status":"TRADE_SUCCESS","gmt_payment":"2017-06-05 14:41:58","version":"1.0","point_amount":"0.00","sign":"okwV2yR7Lgv3Mir+wy17ZzZUlM4qheAIWkKdkBokfDGC5POZKnZXBLM+CpNEPBLtnvX\/NckkaOYf3McUJtP1UPe1I1LBFzwz46hYVIXekSrz1RcBGTMhYm53rMChO8b0KNbFvtANtvdTqgG0cHtzST2quR4c++BmDkc5PHVLblM=","gmt_create":"2017-06-05 14:41:58","buyer_pay_amount":"0.01","receipt_amount":"0.01","fund_bill_list":"[{\\"amount\\":\\"0.01\\",\\"fundChannel\\":\\"ALIPAYACCOUNT\\"}]","app_id":"2017010704905089","seller_id":"2088421497824441","notify_id":"77f9d898eed7cd97279b28eb55c6fc8jju","seller_email":"guoweidong@hz41319.com"}';
//				$data = M('log')->where(array('id'=>77))->getField('post');
//				$data = json_decode($data,true);
				add_log();
				$data = $_POST;
				$sign = $data['sign'];
				$data['sign_type'] = null;
				$data['sign'] = null;
				$data = $this->getSignContent($data);
				$pubKey='MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDDI6d306Q8fIfCOaTXyiUeJHkrIvYISRcc73s3vF1ZT7XN8RNPwJxo8pWaJMmvyTn9N4HQ632qJBVHf8sxHi/fEsraprwCtzvzQETrNRwVxLO5jVmRGi60j8Ue1efIlzPXV9je9mkjzOmdssymZkh2QhUrCmZYI/FCEa3/cNMW0QIDAQAB';
				$res = "-----BEGIN PUBLIC KEY-----\n" .
				wordwrap($pubKey, 64, "\n", true) .
				"\n-----END PUBLIC KEY-----";
				$result = (bool)openssl_verify($data,base64_decode($sign), $res);
				add_log(1111);
				add_log($result?111:2222);
				if($result&&$_POST['trade_status']=='TRADE_SUCCESS'){
							add_log($_POST['out_trade_no'].' '.$_POST['trade_no'].' '.$_POST['total_amount'].' '.'zfb');
							$this->common($_POST['out_trade_no'],$_POST['trade_no'],$_POST['total_amount'],'zfb');
				}
	}
	public function getSignContent($params) {
		ksort($params);
		$stringToBeSigned = "";
		$i = 0;
		foreach ($params as $k => $v) {
			if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {
				if ($i == 0) {
					$stringToBeSigned .= "$k" . "=" . stripslashes($v);
				} else {
					$stringToBeSigned .= "&" . "$k" . "=" . stripslashes($v);
				}
				$i++;
			}
		}

		unset ($k, $v);
		return $stringToBeSigned;
	}
	public function common($order_sn='',$transaction='',$price=0,$pay_type){
			add_log($order_sn.' '.$transaction.' '.$price);
			($order_sn&&$transaction&&$price&&$pay_type) || err('is empty');
			
				//查看是否已经支付成功
			$balance = M('balance')->where(array('order_sn'=>$order_sn))->find();
		
			if(empty($balance)||$balance['status']!=0){
					//记录日志
					err('该订单已经支付了');
			}
			//开启事务
			M()->startTrans();
			//修改订单状态
			$data['real_pay'] = $price;
			$data['transaction'] = $transaction;
			$data['pay_type'] = $pay_type;
			$data['status'] = 1;
			if(false === M('balance')->where(array('id'=>$balance['id']))->save($data)){
						M()->rollback();
						err('修改订单信息');
			}
			//修改用户余额
			if(false === M('merchants_users')->where(array('id'=>$balance['mid']))->setInc('balance',$price)){
						err('yue is wrong');
			}
			//添加日志
            yue_log($price,'充值',$balance['mid'],null,$order_sn);
			
			M()->commit();
			
			die;
	}
}