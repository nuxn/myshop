<?php
function err($msg='未知错误',$status='error'){
		header("Content-type: text/json");
		echo json_encode(array('code' => $status, 'msg' =>$msg));
		die;
}
function succ($data='',$msg='请求成功'){
		header("Content-type: text/json");
		$nums = func_num_args();
		$data = array('code' => 'success', 'msg' =>$msg,'data'=>$data);
		$nums>2&&$data = array_merge($data,func_get_arg(2));
		echo json_encode($data);
		die;
}
function curl_post($url,$data){
	$ch = curl_init();
	$headers[] = "Accept-Charset: utf-8";
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$result = curl_exec($ch);
	curl_close($ch);
	return $result;
}
function arrtoxml($arr,$dom=0,$item=0){
    if (!$dom){
        $dom = new DOMDocument("1.0");
    }
    if(!$item){
        $item = $dom->createElement("xml"); 
        $dom->appendChild($item);
    }
    foreach ($arr as $key=>$val){
        $itemx = $dom->createElement(is_string($key)?$key:"item");
        $item->appendChild($itemx);
        if (!is_array($val)){
            $text = $dom->createTextNode($val);
            $itemx->appendChild($text);
            
        }else {
            arrtoxml($val,$dom,$itemx);
        }
    }
    return $dom->saveXML();
}
function add_log($param=''){
			$data['action'] = MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME;
			$data['add_time'] = date('Y-m-d H:i:s');
			$data['get'] = json_encode(I('get.'));
			$data['post'] = json_encode($_POST);
			$data['param'] = $param;
			M('log')->add($data);
} 
function yue_log($price,$remark,$uid,$balance=0,$order_sn){
		   $data['balance'] = $balance?$balance:M('merchants_users')->where(array('id'=>$uid))->getField('balance');
		   $data['mid'] = $uid;
		   $data['price'] = $price;
		   $data['remark'] = $remark;
		   $data['order_sn'] = $order_sn;
		   $data['add_time'] = time();
		   M('balance_log')->add($data);
}
