<?php
namespace Xcx\Controller;
use Think\Controller;
class  WzpayController extends  Controller{

   		public function  create_sign(){
   			
	   		header("Content-type:text/html;charset=utf-8");
	        vendor('Xcxwzpay.Wzpay');
	        $wzPay = new \Wzpay();
	   		$wzPay->setParameter('sub_openid', 'opBrr0CIz6_PZ5n23H2fqhNrcfZc');
	        $wzPay->setParameter('mch_id', '107161002040001');
	        $wzPay->setParameter('body', '11');

	        $wzPay->setParameter('out_trade_no', time());
	        $wzPay->setParameter('goods_tag', 1213);
	        $wzPay->setParameter('total_fee', 100);
	        $returnData = $wzPay->getParameters();
	       	var_dump($returnData);
   		} 
}