<?php
namespace Pay\Model;
use Common\Model\CommonModel;
class XmmsModel extends CommonModel{
		//微信支付
		//
		protected $tableName = 'jj_xmms'; 
		public  $test_host        = "http://110.80.39.174:9013/nbp-smzf-hzf";
//        $this->test_host        = "https://sy.youngport.com.cn/index.php?s=Pay/Barcodexmmsbank/index";
        public $privatekeypath   = './data/xmms_key/pkcs8_rsa_private_key_2048.pem';
        public $publickeypath    = "./data/xmms_key/ms_rsa_public_key_2048.pem";
        public $notify  =  "https://sy.youngport.com.cn/notify/xmms_notify.php";
		public function zf($type){
						//var_dump($_SERVER["SERVER_NAME"]);
						$data = array(
							'merchantCode'=>'2017082308172870',
							'totalAmount'=>2.00,
							'subject'=>'测试',
							'desc'=>'测试',
							'expireTime'=>1140
						);
						$post_str = $this->sendRequest($data,'SMZF002');
					
						return $post_str['body']['qrCode'];
		}
		public function sendRequest($request,$code,$remark=''){
				$result = $this->arrayToXml($request);
				$post_str = $this->createPostData($result,$code);
				$result = $this->requestPost($this->test_host, $post_str);
				return	$this->decryptResponse($result);
		}
		/**
		 * 获取AES key
		 * @return string
		*/
		private function getAESKey()
		{
			$chars = "abcdefghijklmnopqrstuvwxyz0123456789";
			$str = "";
			for ($i = 0; $i < 16; $i++) {
				$str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
			}
			return "aaaabbbbb1111111";
		}
		  /**
		 * 请求数据
		 * @return arraySMZF002
		 */
		private function createPostData($data,$code,$remark = "")
		{
			$key = $this->getAESKey();
			if($remark == ""){
					$remark = date("YmdHis") . rand(100000, 999999);
			}
			$post_data = array(
				'encryptData'   => $this->encryptAES($data, $key),      // AES对称密钥加密请求报文
				'encryptKey'    => $this->encryptAESKey($key, $this->publickeypath),        // 公钥加密合作方AES对称密钥
				'signData'      => $this->encryptRSASign($data, $this->privatekeypath),     // RSA私钥签名请求报文
				'cooperator'    => "SMZF_HNBCX_T1",     // 合作方标识   T0直清的合作方标识 SMZF_HNBCX_T0，T1直清的合作方标识 SMZF_HNBCX_T1
				'tranCode'      => $code,           // 交易服务码
				'callBack'      => $this->notify,       // 回调地址
				'reqMsgId'      => "$remark",           // 请求流水号（即订单号）
	//            'ext'           => ''                   // 备用域
			);
			$post_str = $this->createStr($post_data);

			return $post_str;
		}
			
		
		private function arrayToXml($arr)
		{
			$reqDate = date("YmdHis");
			$xml = "<?xml version='1.0' encoding='UTF-8'?>
				<merchant><head><version>1.0.0</version><msgType>01</msgType><reqDate>{$reqDate}</reqDate></head><body>";
			foreach ($arr as $key => $val) {
				if (is_numeric($val)) {
					$xml .= "<" . $key . ">" . $val . "</" . $key . ">";

				} else
					$xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
			}
			$xml .= "</body></merchant>";

			return  $xml;
		}
		  /**
     * AES加密请求报文
     * @param $str
     * @param $screct_key
     * @return string
     */
    private function encryptAES($str, $screct_key)
    {
        $str = trim($str);
        $str = $this->addPKCS7Padding($str);
        $encrypt_str = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $screct_key, $str, MCRYPT_MODE_ECB);

        return base64_encode($encrypt_str);
    }

    /**
     * AES解密请求报文
     * @param $str
     * @param $screct_key
     * @return string
     */
    private function decryptAES($str, $screct_key)
    {
        $str = base64_decode($str);
        $encrypt_str = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $screct_key, $str, MCRYPT_MODE_ECB);
        $encrypt_str = trim($encrypt_str);
        $encrypt_str = $this->stripPKSC7Padding($encrypt_str);

        return $encrypt_str;
    }
	   /**
     * 填充算法
     * 在AES 的使用中，pkcs#5填充和pkcs#7填充没有任何区别
     * @param $source
     * @return string
     */
    private function addPKCS7Padding($source)
    {
        $source = trim($source);
        $block = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
        $pad = $block - (strlen($source) % $block);
        if ($pad <= $block) {
            $char = chr($pad);
            $source .= str_repeat($char, $pad);
        }

        return $source;
    }
	 /**
     * 公钥加密AES对称密钥
     * @param $data
     * @param $keyPath
     * @return string
     */
    private function encryptAESKey($data, $keyPath)
    {
        $key = openssl_pkey_get_public(file_get_contents($keyPath));
        $r = openssl_public_encrypt($data, $encryptData, $key, OPENSSL_PKCS1_PADDING);
        if ($r) {
            $ciphertext = $encryptData;
        }

        return base64_encode($ciphertext);
    }
	/**
     * RSA请求报文签名
     * @param $data
     * @param $keyPath
     * @return bool|string
     */
    private function encryptRSASign($data, $keyPath)
    {
        if (empty($data)) {
            return False;
        }

        $private_key = file_get_contents($keyPath);
        if (empty($private_key)) {
            echo "Private Key error!";
            return False;
        }

        $pkeyid = openssl_get_privatekey($private_key);
        if (empty($pkeyid)) {
            echo "private key resource identifier False!";
            return False;
        }

        openssl_sign($data, $signature, $pkeyid);
        openssl_free_key($pkeyid);

        return base64_encode($signature);
    }
  private function createStr($param)
    {
        $str = "";
        foreach($param as $k => $v) {
            $str .= $k . '=' . urlencode($v) . '&';
        }
        $str = substr($str, 0, -1);

        return $str;
    }
	 private function requestPost($url, $data, $second = 30)
    {
        $header = array("Content-type:application/x-www-form-urlencoded");
        //初始化curl
        $curl = curl_init();
        //设置超时
        curl_setopt($curl, CURLOPT_TIMEOUT, $second);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        //post提交方式
        curl_setopt($curl, CURLOPT_POST, TRUE);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
//        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        //运行curl
        $res = curl_exec($curl);
        //返回结果
        if ($res) {
            curl_close($curl);
            return $res;
        } else {
            $error = curl_errno($curl);
            echo "curl出错，错误码:$error" . "<br>";
            echo "<a href='http://curl.haxx.se/libcurl/c/libcurl-errors.html'>错误原因查询</a></br>";
            curl_close($curl);
            return false;
        }
    }
	  public function decryptResponse($result)
    {
    
		$arr = json_decode($result, true);
        $key = $this->decryptAESKey($arr['encryptKey'], $this->privatekeypath);
        $str = $this->decryptAES($arr['encryptData'], $key);
        $sign = $this->decryptRSASign($str, base64_decode($arr['signData']), $this->publickeypath);
        if(!$sign) die("RSA sign error");
        $response = $this->xmlToArray($str);
		//	var_dump($response);
        return $response;
    }
	  /**
     * 私钥解密AES对称密钥
     * @param $encrypted
     * @param $keyPath
     * @return string
     */
    private function decryptAESKey($encrypted, $keyPath)
    {
        $key = openssl_get_privatekey(file_get_contents($keyPath));
        openssl_private_decrypt(base64_decode($encrypted), $decrypted, $key);//私钥解密

        return $decrypted;
    }
	    /**
     * 移去填充算法
     * @param $source
     * @return bool|string
     */
    private function stripPKSC7Padding($source)
    {
        $source = trim($source);
        $char = substr($source, -1);
        $num = ord($char);
        if ($num == 62) return $source;
        $source = substr($source, 0, -$num);

        return $source;
    }
	   /**
     * RSA验证签名
     * @param string $data 数据
     * @param string $signature 签名
     * @param $keyPath 公钥路径
     * @return bool
     */
    private function decryptRSASign($data = '', $signature = '', $keyPath)
    {
        if (empty($data) || empty($signature)) {
            return False;
        }

        $public_key = file_get_contents($keyPath);
        if (empty($public_key)) {
            echo "Public Key error!</br>";
            return False;
        }

        $pkeyid = openssl_get_publickey($public_key);
        if (empty($pkeyid)) {
            echo "public key resource identifier False!</br>";
            return False;
        }

        $ret = openssl_verify($data, $signature, $pkeyid);
        openssl_free_key($pkeyid);
        if ($ret == 1) {
            return true;
        } else {
            return false;
        }
    }
	
    /**
     * 将xml转为array
     * @param  string $xml xml字符串
     * @return array       转换得到的数组
     */
    private function xmlToArray($xml)
    {
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $result = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);

        return $result;
    }
}



