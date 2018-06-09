<?php

namespace Api\Controller;

use Think\Controller;

/**支付
 * Class BarcodeController
 * @package Pay\Controller
 */
class WzbankController extends Controller
{
	//微信扫码测试  wechat_qrcode_pay
	//微信app支付  wechat_app_pay
	//微信H5支付1	wechat_H_pay
	//微信公众号支付	wechat_oa_pa
	//微信小程序支付  wechat_miniapps_pay

	//微信小程序支付
   

    //获取后台发送的进件数据,并将进件结果反回给博彩后台
    public function wzinto(){
        //接受后台进件数据
        $data=I('post.');
        //发起进件请求
        $xml=$this->arraytoxml($data);
        $url='https://svrapi.webank.com/wbap-bbfront/ImportMrch';
        $returnData=$this->postXmlSSLCurl($data,$url);
        echo $returnData;
        //获取微众反回的xml数据
        //发起curl请求传递xml数据给后台
    }

	public function wechat_miniapps_pay(){

		$data['mch_id']="";  //商户入驻微众提供
		$data['is_raw']="4";	//小程序支付上送“4” 
		$data['out_trade_no']=date("YmdHis") . rand(10000, 90000);  //内部订单号用于查询
		$data['body ']=""; //商品描述
		$data['product ']="微信扫码测试";
		$data['sub_openid']="";	 //微信用户关注商户公众号的openid
		$data['sub_appid']="";	//商户向微信申请的小程序appid
		$data['total_fee']="";	//总金额
		$data['mch_create_ip']="";	//终端ID
		$data['notify_url']="";	//代理商回调地址绝对路径
		$data['nonce_str']=$this->createNoncestr();	//随机字符串不长于32位
		//产生签名
		$data['sign']=$this->getSign($data);	//签名
		//数据均已产生完毕
		$xml=$this->arrayToXml($data);
		//post发送请求
		$returnData = $this->postXmlSSLCurl($xml, 'https://test-svrapi.webank.com/l/wbap-bbfront/AppletPay');
		//将反回的xml数据转为array
		$result = $this->xmlToArray($returnData);
		var_dump($result);exit;
		//判断返回状态
		if ($result['status'] !== '0') {
            die($result['message']);
        }
        echo $result;
	}

	/**
     *    作用：产生随机字符串，不长于32位
     */
    public function createNoncestr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }

        return $str;
    }

    public function getSign($arr)
    {
        //过滤null和空
        $Parameters = array_filter($arr,function($v){
            if($v === null || $v === ''){
                return false;
            }
            return true;
        });
        //签名步骤一：按字典序排序参数
        ksort($Parameters);
        //格式化数组转为string
        $String = $this->formatBizQueryParaMap($Parameters, false);
        //签名步骤二：在string后加入交易key
        $key = 'youngPort4a21';
        $String = $String . "&key=" . $key;
        //签名步骤三：MD5加密
        $String = md5($String);
        //签名步骤四：所有字符转为大写
        $result_ = strtoupper($String);
        return $result_;
    }

    public function formatBizQueryParaMap($paraMap, $urlencode)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            $buff .= $k . "=" . $v . "&";
        }
        $reqPar = '';
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }

        return $reqPar;
    }

    public function arrayToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";

            } else
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
        }
        $xml .= "</xml>";
        return $xml;
    }


    //post携带证书发送xml请求
    public function postXmlSSLCurl($xml, $url, $second = 30)
    {
        $ch = curl_init();
        //超时时间
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        //这里设置代理，如果有的话
        //curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
        //curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        $header = array("Content-Type: application/xml");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //设置证书
        //使用证书：cert 与 key 分别属于两个.pem文件
        //默认格式为PEM，可以注释
        curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLCERT,'/youngshop/simplewind/Core/Library/Vendor/Wzpay/cert/apiclient_cert.pem');
        //默认格式为PEM，可以注释
        curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLKEY, '/youngshop/simplewind/Core/Library/Vendor/Wzpay/cert/apiclient_key.pem');
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $data = curl_exec($ch);
        //返回结果有回执结果反回回执结果
        if ($data) {
            curl_close($ch);

            return $data;
        } else {
            $error = curl_errno($ch);
            echo "curl出错，错误码:$error" . "<br>";
            echo "<a href='http://curl.haxx.se/libcurl/c/libcurl-errors.html'>错误原因查询</a></br>";
            curl_close($ch);

            return false;
        }
    }


	//微信H5支付
	public function wechat_H_pay(){

	}

	//微信扫码支付
	public function wechat_qrcode_pay(){
		$data['merchant_code']="107100000420001";  //商户入驻微众提供
		$data['terminal_code']="web";  //终端号
		$data['terminal_serialno']=date("YmdHis").rand(10000, 90000);  //商户订单号
		$data['amount']="0.01";  //金额
		$data['product']="一分也是爱";  //订单信息
        $data['sign']=$this->getSign($data);  //商户入驻微众银行提供
		$json=json_encode($data);
		$url="https://svrapi.webank.com/wbap-bbfront/nao";
        // $returnData=$this->postJsonSSLCurl($json, $url, $second = 30);
		$returnData=$this->postJsonCurl($json, $url);
		$array=json_decode($returnData,true);
	}

	////post不携带证书发送json请求
	public function postJsonCurl($json, $url, $second = 30){
		$ch = curl_init();
        //超时时间
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        //设置请求路径
        curl_setopt($ch, CURLOPT_URL, $url);
        //设置header
        $header = array("Content-Type: application/json");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //设置post请求
        curl_setopt($ch, CURLOPT_POST, true);
        //设置发送json数据
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        $data = curl_exec($ch);
        //返回结果有回执结果反回回执结果
        var_dump($data);echo '-----1------'.'<br/>';
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            echo "curl出错，错误码:$error" . "<br>";
            echo "<a href='http://curl.haxx.se/libcurl/c/libcurl-errors.html'>错误原因查询</a></br>";
            curl_close($ch);
            return false;
        }
	}

    public function postXmlCurl($json, $url, $second = 30){
        $ch = curl_init();
        //超时时间
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch, CURLOPT_URL, $url);
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        $header = array("Content-Type: application/xml");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //设置证书
        //使用证书：cert 与 key 分别属于两个.pem文件
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $data = curl_exec($ch);
        //返回结果有回执结果反回回执结果
        curl_close($ch);
    }

	//微信app支付
	public function wechat_app_pay(){

	}

	public function wechat_oa_pa(){

	}
}