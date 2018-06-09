<?php

class paymentControl extends BaseHomeControl {
		public function weixinUnifiedorderOp()
		{
			($orderid = $_REQUEST['orderid'])||$this->error(404,'不存在订单id');
			$model = Model('order');
			switch($_REQUEST['pay_type']){
				case 1://拼点货
					$orderinfo = $model->table('pdh_order')->where('id='.intval($orderid))->find();
					$orderinfo||$this->error('405','订单查询错误');
					if($orderinfo['order_state']!=10){
						$orderinfo['order_state']==0?$this->error(404,'订单已取消'):$this->error(404,'订单已支付');
					}
					$notify_url = BASE_SITE_URL.'/payment/wxpaypdhret';
					$order_sn = $orderinfo['order_id'];
					$body = '拼点货订单支付';
					$order_price =$orderinfo['order_price']*100;
				break;
				default://一般商品
				$orderinfo = $model->table('order')->where('order_id='.intval($orderid))->find();
				$orderinfo||$this->error('405','订单查询错误');
				
				if($orderinfo['order_state']!=10&&$orderinfo['order_state']!=60){
						$orderinfo['order_state']==0?$this->error(404,'订单已取消'):$this->error(404,'订单已支付');
				}
				
					$order_sn = $orderinfo['order_sn'];
					$body = '便利到家订单支付';
					$order_price =$orderinfo['order_amount']*100;
					$notify_url = BASE_SITE_URL.'/payment/wxpayret';
					break;
			}
			include BASE_CORE_PATH.'/framework/libraries/wechatAppPay.php';
			$appid = 'wxa5bf1d3152e7b5fe';
			$mch_id = '1392596702';
			$key = '9D3AA55A577FB8269B5E107BF3EF685A';//'asdf343234242j3h42k34hk2h34kj2h3';
		    //1.统一下单方法
		    $wechatAppPay = new wechatAppPay($appid, $mch_id, $notify_url, $key);
		    $params['body'] = $body;                 //商品描述
		    $params['out_trade_no'] = $order_sn;    //自定义的订单号"B".date('YmdHis').rand(1000,9999)
// 		    $params['total_fee'] = 1;
		    $params['total_fee'] = intval($order_price);//intval($orderinfo['order_amount']*100);                       //订单金额 只能为整数 单位为分
		    $params['trade_type'] = 'APP';                      //交易类型 JSAPI | NATIVE | APP | WAP 
		    $result = $wechatAppPay->unifiedOrder( $params );
		    if($result['result_code']=='SUCCESS')
		    {
		    	//2.创建APP端预支付参数
		    	/** @var TYPE_NAME $result */
		    	$data = @$wechatAppPay->getAppPayParams($result['prepay_id']);
		    	$json=array('statusCode'=>0);
        		$json['msg'] = 'SUCC';
		    	$json['data'] = $data; 
		    }
		    else 
		    {
		    	$json=array('statusCode'=>500);
		    	$json['msg'] = "统一下单失败";
		    	$json['data'] = $result;
		    	
		    }
		    $this->jsonout($json);		    
		   
}
		/**
		 * 微信的notify
		 *
		 */
		 
		public function wxpayretOp()
		{
			$model=Model('goods');
			include BASE_CORE_PATH.'/framework/libraries/wechatAppPay.php';
			
			$appid = 'wxa5bf1d3152e7b5fe';
			$mch_id = '1392596702';
			$notify_url = BASE_SITE_URL.'/payment/wxpayret';
			$key = 'asdf343234242j3h42k34hk2h34kj2h3';
			
		    //1.notify
		    $wechatAppPay = new wechatAppPay($appid, $mch_id, $notify_url, $key);
		    $data = $wechatAppPay->getNotifyData();
		    //测试用
// 		    if($data){
// 		        $params['ac_id'] = 3;
// 		        $params['article_content'] = json_encode($data);
// 		        db::insert('article',$params);
// 		    }
		    if($data['result_code'] == 'SUCCESS')
		    {
		    	$tradeno = $data['out_trade_no'];
		    	$orderinfo = $model->table('order')->where('order_sn="'.$tradeno.'"')->find();
		    	if($orderinfo && ($orderinfo['order_state']==10||$orderinfo['order_state']==60))
		    	{
					$this->ts_notify_common($tradeno,$orderinfo['order_amount'],'wxpay');
			    	if($ret)
			    	{
			    		$wechatAppPay->replyNotify();
			    	}
		    	}
		    }
		    else 
		    {
		    	
		    }
		    $data=json_decode($data);
		    Log::addLog("notify","succ CZ ".$data);
		}

		public function wxpaypdhretOp()
			$model=Model('goods');
			include BASE_CORE_PATH.'/framework/libraries/wechatAppPay.php';
			$appid = 'wxa5bf1d3152e7b5fe';
			$mch_id = '1392596702';
			$notify_url = BASE_SITE_URL.'/payment/wxpayret';
			$key = 'asdf343234242j3h42k34hk2h34kj2h3';
			
		    //1.notify
		    $wechatAppPay = new wechatAppPay($appid, $mch_id, $notify_url, $key);
		    $data = $wechatAppPay->getNotifyData();
		    
		    
		    if($data['result_code'] == 'SUCCESS')
		    {
		    	
		    	$tradeno = $data['out_trade_no'];
		    	$orderinfo = $model->table('pdh_order')->where('order_id="'.$tradeno.'"')->find();
				$order_goods = $model->table('pdh_order_good')->where('order_id="'.$tradeno.'"')->find();
		
		    	if($orderinfo && $orderinfo['order_state']==10)
		    	{
					$model->table('pdh_order')->where('order_id="'.$tradeno.'"')->update(array('order_state'=>20));
					$good = $model->table('pdh_good_info')->where('id='.$order_goods['good_id'])->find();
					$join_people =  $good['join_people'] + $order_goods['good_nums'];
					$model->table('pdh_good_info')->where('id='.$order_goods['good_id'])->update(array('join_people'=>$join_people));
			    
						if($ret)
							{
								$wechatAppPay->replyNotify();
							}
		    	}
		    }
		}
		
		/**
		 * 支付宝的notify
		 *
		 */
		public function alipayretOp()
		{
			//测试用
			if($_POST){
					 $params['ac_id'] = 3;
					 $params['article_content'] = json_encode($_REQUEST);
					 db::insert('article',$params);
			}
			//测试数据
			//$post = '{"total_amount":"0.01","buyer_id":"2088702133211466","trade_no":"2016101221001004460225363284","notify_time":"2016-10-12 14:59:19","subject":"u4fbfu5229u6613u8d2du8ba2u5355u652fu4ed8","sign_type":"RSA","notify_type":"trade_status_sync","out_trade_no":"1610120259035253","trade_status":"TRADE_SUCCESS","gmt_payment":"2016-10-12 14:59:19","sign":"OriHG7Eletb343I0awkSVsVOYBDFVkPKDLJ9hUlap1JDlnkDAhcSv2gM4L0jGWCx8qZgAj5CbLQ/PnNLuGps38/ZJJm74luw+9L+P/8AGHFqhwHKaQilXgDPrwSZBOXlW+eJfsdbxZBqxUzPx+0jNfHRw4ZdjvIxsJVqUrvzgl4=","gmt_create":"2016-10-12 14:59:18","app_id":"2016082401797990","seller_id":"2088421598774216","notify_id":"48d84d042360ff7f3bbf1c0f3b85838jju"}';
			//$post = json_decode($post,true);
			$post = $_POST;
			require_once(BASE_CORE_PATH.'/framework/libraries/alipay/config.php');		
			//require_once(BASE_CORE_PATH.'/framework/libraries/alipay/AopSdk.php');
			$sign = $post['sign'];
			unset($post['sign'],$post['sign_type']);
			ksort($post);
			reset($post);
			// $model=Model('goods');
			// $orderinfo = $model->table('order')->where('order_id="'.trim($tradeno).'"')->find();
			// $aop = new AopClient();
			// $aop->gatewayUrl = $alipayconfig['gatewayUrl'];
			// $aop->appId = $alipayconfig['appId'];
			// $aop->rsaPrivateKeyFilePath = $alipayconfig['rsaPrivateKeyFilePath'];
			// $aop->alipayPublicKey = $alipayconfig['alipayPublicKey'];
			// $aop->apiVersion = $alipayconfig['apiVersion'];
			// $aop->postCharset= $alipayconfig['postCharset'];
			// $aop->format= $alipayconfig['format'];
		//	var_dump($post);
			$ret = $this->rsaVerify(urldecode(http_build_query($post)),$alipayconfig['alipayPublicKey'],$sign);
			if($_POST){
					 $params['ac_id'] = 3;
					 $params['article_content'] = $ret;
					 db::insert('article',$params);
			}
			//有问题,验证不成功
			if($ret==0){
					$tradeno = $post['out_trade_no'];
					//获得便士		
					$this->ts_notify_common($tradeno,$post['total_amount'],'zfbpay');
					
			}
					//$params['ac_id'] = 3;
					//$params['article_content'] = $ret;
					//db::insert('article',$params);
			//$ret = $aop->rsaCheckV2($string,$sign,$alipayconfig['alipayPublicKey']);		
		}
		public function rsaVerify($data, $alipay_public_key, $sign){
					//以下为了初始化私钥，保证在您填写私钥时不管是带格式还是不带格式都可以通过验证。
					$alipay_public_key = file_get_contents($alipay_public_key);
					
					$res=openssl_get_publickey($alipay_public_key);
					if($_POST){
							 $params['ac_id'] = 3;
							 $params['article_content'] = $alipay_public_key;
							 db::insert('article',$params);
					}
					if($res)
					{
						$result = openssl_verify($data, base64_decode($sign), $res);
					
					}
					else {
						echo "您的支付宝公钥格式不正确!"."<br/>"."The format of your alipay_public_key is incorrect!";
						exit();
					}
					openssl_free_key($res);    
					return $result;
		}
		public function  pdhalipayretOp(){
					$post = $_POST;
					require_once(BASE_CORE_PATH.'/framework/libraries/alipay/config.php');			
					$sign = $post['sign'];
					unset($post['sign'],$post['sign_type']);
					ksort($post);
					reset($post);
					$ret = $this->rsaVerify(urldecode(http_build_query($post)),$alipayconfig['alipayPublicKey'],$sign);
					if($ret==0){
							$model = Model('goods');
							$tradeno = $post['out_trade_no'];
							//获得便士		
							$orderinfo = $model->table('pdh_order')->where('order_id="'.$tradeno.'"')->find();
							$order_goods = $model->table('pdh_order_good')->where('order_id="'.$tradeno.'"')->find();
							if($orderinfo && $orderinfo['order_state']==10)
							{
								$model->table('pdh_order')->where('order_id="'.$tradeno.'"')->update(array('order_state'=>20));
								$good = $model->table('pdh_good_info')->where('id='.$order_goods['good_id'])->find();
								$join_people =  $good['join_people'] + $order_goods['good_nums'];
								$model->table('pdh_good_info')->where('id='.$order_goods['good_id'])->update(array('join_people'=>$join_people));
							}
					}
		}
		/**
		 * 支付宝签名
		 *
		 */
		public function alipayUnifiedorderOp()
		{
			$tradeno = $_REQUEST['out_trade_no'];
			//$time = $_REQUEST['nowTime'];
			//$appversion = $_REQUEST['appversion'];
			require_once(BASE_CORE_PATH.'/framework/libraries/alipay/config.php');		
			require_once(BASE_CORE_PATH.'/framework/libraries/alipay/aop/AopClient.php');
			$model=Model('goods');
			switch($_REQUEST['pay_type']){
				case 1:
					$orderinfo = $model->table('pdh_order')->where('id='.intval($tradeno))->find();
					$orderinfo||$this->error('405','订单查询错误');
					if($orderinfo['order_state']!=10&&$orderinfo['order_state']!=60){
						$orderinfo['order_state']==0?$this->error(404,'订单已取消'):$this->error(404,'订单已支付');
					}
					$notify_url = BASE_SITE_URL.'/payment/pdhalipayret';
					$order_sn = $orderinfo['order_id'];
					$subject = '拼点货订单支付';
					$total_amount =$orderinfo['order_price'];
				break;
				default:
				$orderinfo = $model->table('order')->where('order_id="'.trim($tradeno).'"')->find();
				$orderinfo || $this->error(501,'订单查询错误');
				if($orderinfo['order_state']!=10&&$orderinfo['order_state']!=60){
					$json=array('statusCode'=>501);
					$json['msg'] = $orderinfo['order_state']==0?"订单已取消":"订单已支付";
					$this->jsonout($json);
					exit();
				}
				$order_sn = $orderinfo['order_sn'];
				$subject = '便利到家订单支付';
				$total_amount = $orderinfo['order_amount'];
				$notify_url = BASE_SITE_URL.'/payment/alipayret';
				break;
			}
			$content = array();
			$content['out_trade_no'] = $order_sn;
			$content['subject'] = $subject;
			$content['total_amount'] = $total_amount;//$orderinfo['order_amount'];
			$content['product_code'] = 'QUICK_MSECURITY_PAY';
			$content['body'] = '我是测试数据';
			$data = array();
			$data['app_id'] = $alipayconfig['appId'];
			$data['biz_content'] = json_encode($content);
			$data['charset'] = $alipayconfig['postCharset'];
			$data['format'] = $alipayconfig['format'];
			$data['method'] = 'alipay.trade.app.pay';
			$data['notify_url'] = $notify_url;
			$data['sign_type'] = 'RSA';
			$data['timestamp'] = date('Y-m-d H:i:s');
			$data['version'] = $alipayconfig['apiVersion'];
			$aop = new AopClient();
			$aop->gatewayUrl = $alipayconfig['gatewayUrl'];
			$aop->appId = $alipayconfig['appId'];
			$aop->rsaPrivateKeyFilePath = $alipayconfig['rsaPrivateKeyFilePath'];
			$aop->alipayPublicKey = $alipayconfig['alipayPublicKey'];
			$aop->format= 'json';
			
			//step 2
			$sign = $aop->generateSign($data);
			$data['sign'] = $sign;
			$signStr = array();
		
			$json=array('statusCode'=>0);
	    	$json['msg'] = '签名成功';
	    	$json['data']['signStr'] = http_build_query($data);
	    	$json['data']['signArr'] = $data;
	    	$this->jsonout($json);
			exit;
//			$aop->gatewayUrl = $alipayconfig['gatewayUrl'];
//			$aop->appId = $alipayconfig['appId'];
//			$aop->rsaPrivateKeyFilePath = $alipayconfig['rsaPrivateKeyFilePath'];
//			$aop->alipayPublicKey = $alipayconfig['alipayPublicKey'];
//			$aop->apiVersion = $alipayconfig['apiVersion'];
//			$aop->postCharset= $alipayconfig['postCharset'];
//			$aop->format= $alipayconfig['format'];

//			$request = new AlipayTradeCreateRequest ();		
//			$content = array();	
//			//充值
//			$info = array();
//			$content['out_trade_no'] = $orderinfo['order_sn'];
//			$content['subject'] = '便利易购订单支付';
//			$content['total_amount'] = $orderinfo['order_amount'];
//		
//			$request->setBizContent(json_encode($content));
//			
//			
//			$result = $aop->execute ( $request);  
//			$responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
//			$resultCode = $result->$responseNode->code;
//			var_dump($result->$responseNode);
//			if(!empty($resultCode)&&$resultCode == 10000)
//			{
//				echo "成功";
//			} else {
//				echo "失败";
//			}
		}
		public function huodao_payOp(){
					$model = Model();
					($order_id = $_REQUEST['order_sn'])||err('order_sn is wrong');
					$orderinfo = $model->table('order')->where('order_id="'.trim($order_id).'"')->find();
					$this->ts_notify_common($orderinfo['order_sn'],0,'hdpay');
		}
		public function notify_common($order_sn='',$total_amount=0,$payway){
				$model=Model('goods');
				$orderinfo = $model->table('order')->where('order_sn="'.trim($order_sn).'"')->find();
				$total_amount = $orderinfo['order_amount'];
				if(empty($orderinfo))
				{
					err('订单查询错误');
				}
				else 
				{
					if($orderinfo['order_state']!=10&&$orderinfo['order_state']!=60)
					{
						$json=array('statusCode'=>501);
						$json['msg'] = $orderinfo['order_state']==0?"订单已取消":"订单已支付";
						err($json['msg']);
					}
				}

					$info['payment_code'] = $payway;
			    	$info['payment_time'] = time();
			    	$info['order_state'] = 20;
					$ret = $model->table('order')->where('order_sn="'.$order_sn.'"')->update($info);
					
					//接单 创建物流信息
       	 			$m  = Model();
       	 			$arr = array();
       	 			$arr['member_id'] = $orderinfo['buyer_id'];
       	 			$arr['order_id'] = $orderinfo['order_id'];
       	 			$arr['order_sn'] = $order_sn;
       	 			$a = array(0=>array('AcceptStation'=>'您已经提交了订单，请等待商家接单','AcceptTime'=>date('Y-m-d H:i')));
       	 			$arr['info'] = serialize($a);
       	 			$m->table('expressage')->insert($arr);
					if($ret){
						succ();
					}else{
						err('失败');
					}
//				//消费获得便士
//					$member_id  = $orderinfo['buyer_id'];
//					$insertarr['order_sn'] = $order_sn;
//					$insertarr['orderprice'] = $total_amount;
//					$insertarr['pl_memberid'] = $member_id;
//					Model('points')->savePointsLog('order',$insertarr);
//					
//				//查看是否存在上级
//				$params['table'] = 'member';
//				$params['member_id'] = $member_id;
//				$params['where'] = 'member_id = '.$member_id;
//				$result = db::select($params);
//				if($result[0]['inviter_id']>0){
//					$insertarr['pl_memberid'] = $result[0]['inviter_id'];
//					$insertarr['pl_desc'] = '被邀请人'.$member_id.'消费';
//					$insertarr['rebate_amount'] =  @intval($total_amount*0.3);
//					if($insertarr['rebate_amount'] > 25){
//						$insertarr['rebate_amount'] = intval(25);
//					}
//					Model('points')->savePointsLog('rebate',$insertarr);
//					//查看是否存在上上级
//					$params['table'] = 'member';
//					$params['member_id'] = $member_id;
//					$params['where'] = 'member_id = '.$result[0]['inviter_id'];
//					$result1 = db::select($params);
//					if($result1[0]['inviter_id']>0){
//							$insertarr['pl_memberid'] = $result1[0]['inviter_id'];
//							$insertarr['pl_desc'] = '被被邀请人'.$member_id.'消费';
//							$insertarr['rebate_amount'] =  @intval($total_amount*0.2);
//							if($insertarr['rebate_amount'] > 25){
//								$insertarr['rebate_amount'] = intval(25);
//							}
//							 Model('points')->savePointsLog('rebate',$insertarr);
//					}
//				}		
		}
		public function ts_notify_commonOp(){
			$order_id = $_GET['order_id'];
			$this->ts_notify_common($order_id,0,'hdpay');
		}
		public function ts_notify_common($order_sn='',$total_amount=0,$payway){
					$model=Model();
					$orderinfo = $model->table('order')->where('order_sn='.trim($order_sn))->find();
					$order_id =  $orderinfo['order_id'];                                                                                                                                                                                                                                                                                                                                                                                                                                                     
					$store = db::getRow(['table'=>'store_info','field'=>'store_id','value'=>$orderinfo['store_id']]);
						if(empty($orderinfo))
						{
							err('订单查询错误');
						}
						else 
						{
							if($orderinfo['order_state']!=10&&$orderinfo['order_state']!=60)
							{
									$json['msg'] = $orderinfo['order_state']==0?"订单已取消":"订单已支付";
									err($json['msg']);
							}
					    }
					if($store['is_feature']==2){
						//分割订单
						$order_goods = $model->table('order_goods')->where('order_id='.trim($order_id))->select();
						$order_common = $model->table('order_common')->where('order_id='.trim($order_id))->find();
						foreach($order_goods as $v){
								$goods[$v['ck_id']][] = $v;
						}
						$model->beginTransaction();
						foreach($goods as $key => $v){
								$order = $orderinfo;
								//添加数据
								$order['order_sn'] =  date('ymdhis').rand(1000,9999).$key;
								$order['payment_code'] = $payway;
			    				$order['payment_time'] = time();
			    				$order['order_state'] = 25;
								$order['ck_id'] = $key;
								unset($order['order_id']);
								//添加订单信息
								$new_order_id = $model->table('order')->insert($order);
								if(!$new_order_id){
											$model->rollback();
											err('1');
								}
								//添加商品
								foreach($v as $vv){
										$vv['order_id'] = $new_order_id;
										unset($vv['rec_id']);
										$a1 = $model->table('order_goods')->insert($vv);
										if(!$a1){
												$model->rollback();
												err('1');
										}
								}
								//添加common_order
								$order_common['order_id'] = $new_order_id;
								$a2 = $model->table('order_common')->insert($order_common);
								if(!$a2){
											err('1');
											$model->rollback();		
								}
						}
						$model->table('order_common')->where('order_id='.trim($order_id))->delete();
						$model->table('order_goods')->where('order_id='.trim($order_id))->delete();
						$model->table('order')->where('order_id='.trim($order_id))->delete();
						$model->commit();
						succ('ok');
					}else{
										$info['payment_code'] = $payway;
								    	$info['payment_time'] = time();
								    	$info['order_state'] = 20;
										$ret = $model->table('order')->where('order_sn="'.$order_sn.'"')->update($info);
										
										//接单 创建物流信息
					       	 			$m  = Model();
					       	 			$arr = array();
					       	 			$arr['member_id'] = $orderinfo['buyer_id'];
					       	 			$arr['order_id'] = $orderinfo['order_id'];
					       	 			$arr['order_sn'] = $order_sn;
					       	 			$a = array(0=>array('AcceptStation'=>'您已经提交了订单，请等待商家接单','AcceptTime'=>date('Y-m-d H:i')));
					       	 			$arr['info'] = serialize($a);
					       	 			$m->table('expressage')->insert($arr);
										if($ret){
											succ();
										}else{
											err('失败');
										}	
					}
		}
		public function success($msg='',$data='',$status=0){
						$json=array('statusCode'=>$status,'msg'=>$msg,'data'=>$data);
						$this->jsonOut($json);
						exit();
		}
		public function error($code,$msg,$data=''){
				$json=array('statusCode'=>$code,'msg'=>$msg,'data'=>$data);
				$this->jsonOut($json);
				exit();
		}
		/**
		 * 支付宝生成订单号,生成充值记录表
		 */
		public function rechargecreateop(){
		    $model=Model('member');
		    $json=array('statusCode'=>0,'msg'=>'获取成功');
		    require_once(BASE_CORE_PATH.'/framework/libraries/alipay/config.php');
		    require_once(BASE_CORE_PATH.'/framework/libraries/alipay/aop/AopClient.php');
		    if(!$_SESSION['member_id']){
		        $json=array('statusCode'=>500,'msg'=>'暂未登录,请先登录');
		        $this->jsonOut($json);
		        exit();
		    }
// 		    $type=$_REQUEST['type'];
// 		    $total_fee=$_REQUEST['total_fee']?0.01:0;
		    $total_fee=$_REQUEST['total_fee'];
		    if($total_fee){
		        $info=array();
		        $info['out_trade_no']=$_REQUEST['out_trade_no']?$_REQUEST['out_trade_no']:'CZ'.date('ymdHis').rand(000,999);
		        
		        $content = array();
		        $content['out_trade_no'] = $info['out_trade_no'];
		        $subject = '便利到家充值';
		        $content['subject'] = $subject;
		        $content['total_amount'] = $total_fee;//$orderinfo['order_amount'];
		        $content['product_code'] = 'QUICK_MSECURITY_PAY';
		        $content['body'] = '我是测试数据';
		        $data = array();
		        $data['app_id'] = $alipayconfig['appId'];
		        $data['biz_content'] = json_encode($content);
		        $data['charset'] = $alipayconfig['postCharset'];
		        $data['format'] = $alipayconfig['format'];
		        $data['method'] = 'alipay.trade.app.pay';
		        $notify_url=BASE_SITE_URL.'/payment/notify';
		        $data['notify_url'] = $notify_url;
		        $data['sign_type'] = 'RSA';
		        $data['timestamp'] = date('Y-m-d H:i:s');
		        $data['version'] = $alipayconfig['apiVersion'];
		        $aop = new AopClient();
		        $aop->gatewayUrl = $alipayconfig['gatewayUrl'];
		        $aop->appId = $alipayconfig['appId'];
		        $aop->rsaPrivateKeyFilePath = $alipayconfig['rsaPrivateKeyFilePath'];
		        $aop->alipayPublicKey = $alipayconfig['alipayPublicKey'];
		        $aop->format= 'json';
		        //step 2
		        $sign = $aop->generateSign($data);
		        $data['sign'] = $sign;
		        $signStr = array();
		        
		        $json=array('statusCode'=>0);
		        $json['msg'] = '签名成功';
		        $json['data']['signStr'] = http_build_query($data);
		        $json['data']['signArr'] = $data;
		        
		        $info['body'] = '便利到家充值';
		        $info['total_fee'] = $total_fee*100;
		        $info['time_expire'] = time()+3600;
		        $info['addtime'] = time();
		        $info['status'] = 0;
		        $info['member_id'] = $_SESSION['member_id'];
		        $memberinfo = $model->table('member')->where('member_id='.$_SESSION['member_id'])->find();
		        $info['member_name'] = $memberinfo['member_name'];
		        $ret = $model->table('db_recharge')->insert($info);
		        
		        $this->jsonOut($json);
		    }else {
		        $json=array('statusCode'=>501);
		        $json['msg']='failure';
		        $json['data']['info']='参数错误';
		    }
		    $this->jsonOut($json);
		}
		
		/**
		 * 支付宝回调
		 */
		public function notifyOp(){
		    require_once(BASE_CORE_PATH.'/framework/libraries/alipay/config.php');
		    require_once(BASE_CORE_PATH.'/framework/libraries/alipay/AopSdk.php');
		    $notify=$_REQUEST;//接收支付宝服务器传递过来的参数
		    $aop= new AopClient();
		    $aop->gatewayUrl=$alipayconfig['gatewayUrl'];
		    $aop->appId = $alipayconfig['appId'];
		    $aop->rsaPrivateKeyFilePath = $alipayconfig['rsaPrivateKeyFilePath'];
		    $aop->alipayPublicKey = $alipayconfig['alipayPublicKey'];
		    $aop->apiVersion = $alipayconfig['apiVersion'];
		    $aop->postCharset= $alipayconfig['postCharset'];
		    $aop->format= $alipayconfig['format'];
		    
		    unset($notify['sign']);
		    unset($notify['sign_type']);
		    if($_POST){
		        $params['ac_id'] = 3;
		        $params['article_content'] = $ret;
		        db::insert('article',$params);
		    }
		    $request = new AlipayTradeQueryRequest ();
		    $request->setBizContent("{" .
		        "    \"out_trade_no\":\"".$notify['out_trade_no']."\"," .
		        "    \"trade_no\":\"".$notify['trade_no']."\"" .
		        "  }");
		    $result = $aop->execute ( $request);
		    if($result->alipay_trade_query_response->trade_status =='TRADE_SUCCESS'){
		        //检查订单
		        $out_trade_no = $result->alipay_trade_query_response->out_trade_no;
		        $trade_no = $result->alipay_trade_query_response->trade_no;
		        $total_amount = $result->alipay_trade_query_response->total_amount;
		        Log::addLog("notify","start update");
		        $this->checkAndUpdateOrder($out_trade_no,$total_amount,$trade_no,$notify);
		    }else {
		        Log::addLog("notify","failure:---".json_encode($_POST));
		        echo 'failure';
		    }
		}
		
		/**
		 * 支付宝,微信更新member和db_recharge数据表
		 */
		public function checkAndUpdateOrder($out_trade_no,$total_amount,$trade_no,$other){
		    $model=Model('member');
		    if(strstr($out_trade_no,'CZ')){
		        Log::addLog("notify","start CZ ".$out_trade_no);
		        $model->beginTransaction();//开始事务
		        $recharge = $model->table('db_recharge')->where(array('out_trade_no'=>$out_trade_no,'status'=>0))->lock(1)->find();
		        
		        //充值
		       $rechargeUp =array();
		       $rechargeUp['out_transaction_id']=$trade_no;
		       if($other=='wx'){
		           $rechargeUp['trade_type']='微信支付';
		       }else{
		           $rechargeUp['trade_type']='支付宝';
		       }
		       $rechargeUp['time_end']=time();
		       $rechargeUp['openid']=$trade_no;
		       $rechargeUp['status']=1;
		       $memberinfo=$model->table('member')->where(array('member_id'=>$recharge['member_id']))->lock(1)->find();
		       $memberinfoUp=array();
		       $memberinfoUp['money']=bcadd($memberinfo['money'],$recharge['total_fee']/100,2);
		       $memberinfoRet = $model->table('member')->where('member_id='.$recharge['member_id'])->update($memberinfoUp);
		       $rechargeRet=$model->table('db_recharge')->where(array('out_trade_no'=>$out_trade_no))->update($rechargeUp);
		       $aa=serialize($memberinfoUp);
		       $bb=serialize($memberinfo);
		       if($memberinfoRet && $rechargeRet && bccomp($total_amount*100,$recharge['total_fee'])==0){
		           Log::addLog("notify","succ CZ ".$out_trade_no.$aa.$bb.$memberinfoRet);
		           $model->commit();
		           
		           if ($rechargeRet){
// 		               $json['data']['allnotify']=BASE_SITE_URL.'/payment/notify';
// 		               $json['data']['out_trade_no']=$info['out_trade_no'];
// 		               $json['msg']='SUCC';
// 		               $json['data']['info']='生成充值记录成功';
		           
		               $tmp['message_title']='余额充值成功';
		               $tmp['message_body']='您已成功充值'.$total_fee.'元，开启便利生活！';
		               $tmp['message_time']=date('Y-m-d H:i:s',time());
		               $tmp['to_member_id']=$recharge['member_id'];
		               $tmp['message_type']=2;
		               $model->table('message')->insert($tmp);
		           
		               //推送
		             //  $info1=$model->table('db_member_jpush')->where(array('member_id'=>$recharge['member_id']))->order('updatetime desc')->find();
		           
		             //  $res=Model('jpush')->sendByRegop($info1['registration_id'],'hello',$info1['os'],'','','欢迎来到便利到家商店','便利到家','您已成功充值');
		           
// 		           }else {
// 		               $json['data']=array();
// 		               $json['statusCode']=502;
// 		               $Json['msg']='failure';
// 		               $json['data']['info']='生成充值记录失败';
		           }
		           
		           echo 'success';
		           exit();
		       }else {
		           Log::addLog("notify","error CZ ".$out_trade_no);
		           $model->rollback();
		           echo 'failure';
		           exit();
		       }
		    }
		}
		
		/**
		 * 检查支付宝或微信订单号是否支付成功
		 */
		
	 public function checkorderop(){
		$model = Model('member');
		$json = array('statusCode'=>0);
		$json['msg'] = '获取成功';
		$tradeno = $_REQUEST['out_trade_no'];
    		if(!empty($tradeno))
    		{
    			if(strstr($tradeno,'CZ'))
    			{
    				$recharge = $model->table('db_recharge')->where(array('out_trade_no'=>$tradeno))->find();
    				if($recharge && $recharge['status']==1) //支付成功
    				{
    					$json['statusCode'] = 0;
    					$json['msg'] = '订单支付成功';		
    				}
    				else 
    				{
    					$json['statusCode'] = 502;
    					$json['msg'] = '订单未支付';		
    				}
    			}
    	   }else{
    			    $json['statusCode'] = 501;
    			    $json['msg'] = '订单号错误';
    			}
    			$this->jsonOut($json);
    }
    
    /**
     * 微信充值支付生成订单号，并返回签名
     */
    
    public function wxrechargecreateOp(){
        $model=Model('member');
        $json=array('statusCode'=>0,'msg'=>'获取成功');
        require_once(BASE_CORE_PATH.'/framework/libraries/wechatAppPay.php');
        if(!$_SESSION['member_id']){
            $json=array('statusCode'=>500,'msg'=>'暂未登录,请先登录');
            $this->jsonOut($json);
            exit();
        }else {
            $member_id=$_SESSION['member_id'];
        }
//         $total_fee=$_REQUEST['total_fee']?0.01:0;
        $total_fee=$_REQUEST['total_fee'];
        if($total_fee){
            $info=array();
            $info['out_trade_no']=$_REQUEST['out_trade_no']?$_REQUEST['out_trade_no']:'CZ'.date('ymdHis').rand(000,999);
            $info['body'] = '便利到家充值';
            $info['total_fee'] = $total_fee*100;
            $info['time_expire'] = time()+3600;
            $info['addtime'] = time();
            $info['status'] = 0;
            $info['member_id'] = $_SESSION['member_id'];
            $memberinfo = $model->table('member')->where('member_id='.$_SESSION['member_id'])->find();
            $info['member_name'] = $memberinfo['member_name'];
            $ret = $model->table('db_recharge')->insert($info);
            
            $appid = 'wxa5bf1d3152e7b5fe';
            $mch_id = '1392596702';
            $key = '9D3AA55A577FB8269B5E107BF3EF685A';
            $notify_url = BASE_SITE_URL.'/payment/wxnotify';
            
            //1.统一下单方法
            $wechatAppPay = new wechatAppPay($appid, $mch_id, $notify_url, $key);
            $body='便利到家充值支付';
            $params['body'] = $body;                 //商品描述
            $params['out_trade_no'] = $info['out_trade_no'];    //自定义的订单号"B".date('YmdHis').rand(1000,9999)
            $params['total_fee'] = $info['total_fee'];//intval($orderinfo['order_amount']*100);                       //订单金额 只能为整数 单位为分
            $params['trade_type'] = 'APP';                      //交易类型 JSAPI | NATIVE | APP | WAP
            $result = $wechatAppPay->unifiedOrder( $params );
        }
        if($result['result_code']=='SUCCESS')
        {
            //2.创建APP端预支付参数
            /** @var TYPE_NAME $result */
            $data = @$wechatAppPay->getAppPayParams($result['prepay_id']);
            $json=array('statusCode'=>0);
            $json['msg'] = 'SUCC';
            $json['data'] = $data;
            $json['data']['wxnotify']=BASE_SITE_URL.'/payment/wxnotify';
        }
        else
        {
            $json=array('statusCode'=>500);
            $json['msg'] = "充值下单失败";
            $json['data'] = $result;
             
        }
        $this->jsonout($json);
    }
    
    /**
     * 微信的充值支付notify
     *
     */
    public function wxnotifyOp()
    {
        $model=Model('goods');
        require_once(BASE_CORE_PATH.'/framework/libraries/wechatAppPay.php');
        $appid = 'wxa5bf1d3152e7b5fe';
        $mch_id = '1392596702';
        $notify_url = BASE_SITE_URL.'/payment/wxnotify';
        $key = 'asdf343234242j3h42k34hk2h34kj2h3';
        	
        //1.notify
        $wechatAppPay = new wechatAppPay($appid, $mch_id, $notify_url, $key);
        
        $data = $wechatAppPay->getNotifyData();
//        测试用
//         		    if($data){
//         		        $params['ac_id'] = 3;
//         		        $params['article_content'] = json_encode($data);
//         		        db::insert('article',$params);
//         		    }
        if($data['result_code'] == 'SUCCESS')
        {
            $tradeno = $data['out_trade_no'];
            $orderinfo = $model->table('order')->where('order_sn="'.$tradeno.'"')->find();
//             if($orderinfo && $orderinfo['order_state']==10)
//             {
//                 $this->notify_common($tradeno,$data['total_fee'],'wxpay');
//                 if($ret)
//                 {
                    //$wechatAppPay->replyNotify();
                    
                    $out_trade_no=$data['out_trade_no'];
                    $total_amount=$data['total_fee']/100;
                    $trade_no=$data['transaction_id'];
                    $notify='wx';
//                  var_dump($data);exit();
                    $this->checkAndUpdateOrder($out_trade_no,$total_amount,$trade_no,$notify);
//                 }
//             }
        }
        else
        {
             
        }
    
    }
    
}