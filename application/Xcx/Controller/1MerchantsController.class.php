<?php
namespace Xcx\Controller;

use Xcx\Controller\ApibaseController;
use Think\Controller;

class  MerchantsController extends  ApibaseController
{
	
    public function info(){
    			$Merchants = D('Merchants');
    			if($data = $Merchants->info(UID)){
    					succ($data);
    			}else{
    					err($Merchants->getError());
    			}
	}
	public function update(){
		   //配送距离,配送方式
		   
	}
	public function types(){
				$level = D('Level');
				$data['lists'] = $level->lists(UID);
				$data['cash'] = D('UserCash')->lists(UID);
				$data['yue'] = 0;
				succ($data);
	}
	//员工
	public function staff(){
				$data = D('MerchantsUsers')->lists(UID);
				succ($data);
	}
	//商户二维码海报
	public function show_m_qrcode(){
			if(!$token = M('config')->where(array('name'=>'access_token'))->getField('value')){
						$token = $this->get_token();
			}
			$users = M('Merchants_users')->where('id',UID)->find();
			$users || err('不存在商户');
			if($user['qrcode']){
						succ($user['qrcode']);
			}else{
						//生成二维码
						$url = 'https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?access_token='.$token;
						$param = '{"path": "pages/index?tg='.UID.'", "width": 200}';
						$data = curl_post($url,$param);
						$data = json_decode($data);
						if($data->errcode==42001){
										$token = $this->get_token();
										$url = 'https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?access_token='.$token;
										$data = curl_post($url,$param);
						}
				
						$fileName = './public/equity/'.UID.'.png';
					    $fp=fopen($fileName,'w');
					    $a = fwrite($fp,$data);
					    fclose($fp);
					    $path = ltrim($fileName,'.');
					    $data = M('Merchants_users')->where(array('id'=>UID))->setField('qrcode',$path);
					  	$data!==false?succ($path):err('生成失败');
			}			
			 	
	}
	//生成二维码 
	public function show_qrcode(){
				($id = I('id')) || err('id is empty');
				$users = M('Merchants_users')->where(array('id'=>$id,'pid'=>UID))->find();
				$users || err('不存在该员工');
				if($user['qrcode']){
						return $user['qrcode'];
				}else{
						//生成二维码
						$path = $this->build_qrcode($id);
						$data = M('Merchants_users')->where(array('id'=>$id))->setField('qrcode',$path);
						$data!==false?succ($path):err('生成失败');
				}
	}
	//生成token
	public function get_token(){
						$url = 'https://api.weixin.qq.com/cgi-bin/token';
						$param['appid'] = 'wx7aa4b28fb4fae496';
						$param['secret'] = '630bca8d32860f0ba682ac05a2184123';
						$param['grant_type'] = 'client_credential';
						$data = file_get_contents($url.'?'.http_build_query($param));
						$data = json_decode($data);
						$token = $data->access_token;
						M('config')->add(array('name'=>'access_token','add_time'=>time(),'value'=>$token));
						return $token;
	}
	//生成二维码
	public function build_qrcode($id){
			if(!$token = M('config')->where(array('name'=>'access_token'))->getField('value')){
						$token = $this->get_token();
			}
			//生成二维码
			$url = 'https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?access_token='.$token;
			$param = '{"path": "pages/index?tg='.$id.'", "width": 200}';
			$data = curl_post($url,$param);
			$data = json_decode($data);
			if($data->errcode==42001){
						$token = $this->get_token();
						$url = 'https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?access_token='.$token;
						$data = curl_post($url,$param);
			}
			$size=strlen($data);
		    $fileName = './public/equity/'.$id.'.png';
		    $fp=fopen($fileName,'w');
		    $result = fwrite($fp,$data);
		    fclose($fp);
			$len = strlen($id)*10;
			$font = './public/fonts/simsun.ttc';//
			list($width,$height) = getimagesize($fileName);
			$img = imagecreatetruecolor($width, $height+40);  
			//$cornflowerblue = imagecolorallocate($img, 48,112,185);  
			$black = imagecolorallocate($img,  84,84,84);  
			$white = imagecolorallocate($img,  255, 255, 255);  
			//imagefill($img, 0, 0, $cornflowerblue);  //填充背景色  
			ImageFilledRectangle($img, 0, 0, 325, 40 ,$black);
			$a = imagefttext($img, 14, 0, (325-$len)/2, 27, $white,$font,$id);
			
			$src = imagecreatefromstring(file_get_contents($fileName));
			imagecopyresized($img, $src, 0, 40, 0, 0,$width,$height,$width,$height);
			//header("Content-type: image/jpg");
			//imagepng($img);
			imagepng($img,$fileName); 
			imagedestroy($img);
		    return	$result?ltrim($fileName,'.'):false;
	}
	
	//批量生成二维码
	public function build_qrcodes(){
				($ids = I('ids')) || err('ids is empty');
				$ids = explode(',',$ids);
				foreach($ids as $id){
						$users = M('Merchants_users')->where(array('id'=>$id,'pid'=>UID))->find();
						$users || err('不存在该员工');
						if($user['qrcode']){
								$qrcode[] = $user['qrcode'];
								continue;
						}
						//生成二维码
						$path = $this->build_qrcode($id);
						$path!==false || err('生成二维码失败');
						$data = M('Merchants_users')->where(array('id'=>$id))->setField('qrcode',$path);
						$data!==false || err('更新失败');
						$qrcode[] = $path;
				}
				succ($qrcode);
	}
	//计算价格
	public function buy_systems(){
				($id = I('id')) || err('id is empty');
				($type = I('type')) || err('type is empty');
				
				//查询是否存在
				($merchantsLevel = M('merchants_level')->where(array('id'=>$id))->find()) || err('不存在该类型的小程序');
				//查看是否已经购买其他的
				$m_type = M('merchants')->where('uid',UID)->getField('type');
				if($m_type){
					$m_type==$merchantsLevel['type'] || err('你已经购买了其他小程序！');
				}
				if($cash_id = I("cash_id")){
						//查询兑换券
						$UserCash = D('UserCash');
						if(!$cash = $UserCash->info($cash_id,UID)){
								err($UserCash->getError());
						}
				}
				//生成订单 
				$data['mid'] = UID;
				$data['order_sn'] = date('YmdHis').UID.rand(1000000,9999999);
				$data['type'] = $merchantsLevel['type'];
				$data['level'] = $merchantsLevel['level'];
				$data['goods_price'] = $merchantsLevel['price'];
				$data['cash_id'] = $cash_id;
				$data['cash_price'] = isset($cash['price'])?$cash['price']:0;
				$data['order_price'] = $merchantsLevel['price']-$data['cash_price'];
				$data['add_time'] = time();
				$order_id = M('miniapp')->add($data);
				//生成签名
				$sign = $this->get_sign($order_id,$type);
				succ(array('sign'=>$sign,'price'=>$data['order_price']));
				
	}
	
	public function get_sign($order_id,$type){
				//查询订单 
				$data = M('miniapp')->where(array('id'=>$order_id))->find();
				empty($data) && err('该订单不存在');
				($data['status']==1) && err('该订单已经支付');
				($data['status']==0) || err('该订单不能支付');
				switch($type){
						case 'wx':
						break;
						case 'zfb':
						return $this->zfb_pay($data['order_sn'],$data['order_price']);
						break;
						case 'yue':
						break;
						default:
						err('type is wrong');
						break;
				}
	}
	public function zfb_pay($order_sn,$price){
			$a = vendor('alipay.aop.AopClient');
			p($a);			
	}
 
}