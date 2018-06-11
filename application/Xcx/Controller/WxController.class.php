<?php
namespace Xcx\Controller;
use Think\Controller;

class  WxController extends Controller
{
			public function create_store(){
				$token = get_weixin_token();
				$url = 'http://api.weixin.qq.com/cgi-bin/poi/addpoi';
				
				$json = '{"business":{
							   "base_info":{
											 
											   "business_name":"不错哦",
											   "branch_name":"不超过10个字，不能含有括号和特殊字符",
											   "province":"不超过10个字",
											   "city":"不超过30个字",
											   "district":"不超过10个字",
											   "address":"门店所在的详细街道地址（不要填写省市信息）：不超过80个字",
											   "telephone":18823404165,
											   "categories":["美食"], 
											   "offset_type":1,
											   "longitude":115.32375,
											   "latitude":25.097486
											}
								}
				}';
				
				$data['business_name'] = '不错哦';
				$data['branch_name'] = '不错哦';
				$data['province'] = '广东省';
				$data['city'] = '深圳市';
				$data['district'] = '宝安区';
				$data['address'] = '测试';
				$data['telephone'] = '18823404165';
				$data['categories'] = array("美食,小吃快餐");
				$data['offset_type'] = 1;
				$data['longitude'] = 115.32375;
				$data['latitude'] = 25.097486;
				
			    $params = array(
					'business' => array('base_info'=>$data),
                );
				
				$p['buffer'] = json_encode($params);
				$p['access_token'] = $token;
				var_dump($p);
			   $msg = curl_post($url,$p);
			   p($msg);
						
							
			}
}
		