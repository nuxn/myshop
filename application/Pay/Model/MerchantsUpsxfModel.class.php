<?php

namespace Pay\Model;

use Common\Model\CommonModel;

class MerchantsUpsxfModel extends CommonModel
{
    protected $tableName = 'merchants_upsxf';
    protected $trueTableName = 'ypt_merchants_upsxf';

    private $parameters = array();
    private $requestParams;
    private $private_key;
    private $public_key;

    private $microUrl;
    private $scanUrl;
    private $refundUrl;
    private $queryUrl;
    private $jspayUrl;

    private $path;

    public function __construct()
    {
        parent::__construct();
        $this->microUrl  = 'https://icm-test.suixingpay.com/management/qr/reverseScan';
        $this->scanUrl   = 'https://icm-test.suixingpay.com/management/qr/activeScan';
        $this->jspayUrl  = 'https://icm-test.suixingpay.com/management/qr/jsapiScan';
        $this->refundUrl = 'https://icm-test.suixingpay.com/management/qr/refund';
        $this->queryUrl  = 'https://icm-test.suixingpay.com/management/qr/query';

        $this->path = $_SERVER['DOCUMENT_ROOT'] . '/data/log/Banksxf/';
        $this->private_key = "MIICXAIBAAKBgQC+2v20Ci5VLz7r9si0AuYz3wFLWLE2Vucr1qTWpUY7smlDycOaa/WpasvKssg5lUdgK62JHvFQF2UTqZ2gBm3+atpCUvJFVC29OH4cah7qg0ryUgphEroDsas+zFjQf46EhkE37hem+UhNPcSnMahta+Jnusqftgj2fuHBUaXtzwIDAQABAoGAXC3e3ScRq7ju9f6yfybrUmBB+scyiCE+89BuuvEGU+zepIv9ekbsVtAq75Kb3Bv6ZjuSTCjyuhEik3WXmOOiGapaBmaXl9kkx0UtQsfjpV8dQIAGGskPkn5fkZGFzwmG5VB46B2a1kuR/OpNojIS7Z6Kd+32+KVfKcn1xLH1mykCQQDqBuBqPPwMk3wrXmPXrzZ7li3mO0K4SZTDKT2xfe6rGOprLGCxaXp01OOhWxmXEeW+I1P0b6qL8V+HgzjZjNtDAkEA0MZvoPyJC5Y09/ZSBM0S2izntJ4kGB39rASxpo0CXTDLCIz6k35/abwgCOmX9V8XXnx4og76FDdp3DTNp02shQJBAMUPj07GFXM9iZQ3QhlvN5BvkCzK/86QXwzLIGDh6uP18gbW8oDRkcTpMtg/DthPwMYPl3U/xjtav5crXuaJnmMCQCO6AXpMHOulrbTNKyX1Lge17YTEFyslXrakKv50XPYzllsFPRAmcolWjyjXSJDN0AL0S/R3maYCAZSUWKkLqr0CQGZ2zZVJn4NLv3r9uwJMUljQr+5CoToDp7eahWgN9vO389H0u0Kbhgg0326B4h7DwVl20w7qVwkpnWqTLwz/yqY=";

        $this->public_key = '';
        $this->requestParams = array(
<<<<<<< HEAD
            'orgId' => '07296653',           // 洋仆淘机构号唯一
=======
//            'orgId' => '07296653',           // 洋仆淘机构号唯一 测试
            'orgId' => '65554373',           // 洋仆淘机构号唯一 生产
>>>>>>> c75cd230b60e79e0047330d2828b8043f316747e
            'version' => '1.0',             // 版本
            'signType' => 'RSA',            // 签名方法
        );
    }

    // 签名
    private function getSign()
    {
<<<<<<< HEAD
        $string = get_sign_content($this->requestParams);
        $sign = rsa_sign($string, $this->private_key);
=======
        $string = $this->get_sign_content($this->requestParams);
        $sign = $this->rsa_sign($string, $this->private_key);
>>>>>>> c75cd230b60e79e0047330d2828b8043f316747e
        return $sign;
    }

    // 设置参数为空
    public function setNull()
    {
        $this->parameters = null;
        unset($this->requestParams['sign']);
    }

    private function requestPost($url, $data, $second = 60)
    {
        $header = array("Content-type:application/json;charset=utf-8");
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
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        //运行curl
        $res = curl_exec($curl);
        //返回结果
        if ($res) {
            curl_close($curl);
            return $res;
        } else {
            $error = curl_errno($curl);
            get_date_dir($this->path,'requestPost','请求错误码', $error);
//           "<a href='http://curl.haxx.se/libcurl/c/libcurl-errors.html'>错误原因查询</a></br>";
            curl_close($curl);
            return false;
        }
    }

    /**
     * 生成请求数据发起氢气球
     * @param $url
     * @param $file_name
     * @return mixed
     */
    private function send($url, $file_name)
    {
        $this->requestParams['reqId'] = md5(getOrderNumber());    // 请求唯一编号
        $this->requestParams['timestamp'] = date('YmdHis');    // 请求时间
        $this->requestParams['reqData'] = json_encode($this->parameters);
        $this->requestParams['sign'] = $this->getSign();
        $send = json_encode($this->requestParams);

        get_date_dir($this->path,$file_name,'请求数据', $send);

        $result = $this->requestPost($url,$send);

        get_date_dir($this->path,$file_name,'返回', $result);

        return json_decode($result, true);
    }

<<<<<<< HEAD
=======
    /**
     * 设置请求参数
     * @param $key
     * @param $val
     */
>>>>>>> c75cd230b60e79e0047330d2828b8043f316747e
    public function setParameters($key, $val)
    {
        $this->parameters[$key] = $val;
    }

    // 获取公众号支付参数
    public function getPayInfo()
    {
        $result = $this->send($this->jspayUrl,'getPayInfo');
        if($result['code'] == 'SXF0000'){
            $return = $result['respData'];
            if($return['bizCode'] == '0000'){
                $pay_info['timeStamp'] = $return['payTimeStamp'];
                $pay_info['package'] = $return['payPackage'];
                $pay_info['paySign'] = $return['paySign'];
                $pay_info['appId'] = $return['payAppId'];
                $pay_info['signType'] = $return['paySignType'];
                $pay_info['nonceStr'] = $return['paynonceStr'];
                return array('code'=>'0000','pay_info'=>json_encode($pay_info));
            } else {
                return array('code'=>'0001','msg'=>$return['bizMsg']);
            }
        } else {
            return array('code'=>'0001','msg'=>$result['msg']);
        }
    }

    // 获取支付宝扫码参数
    public function getPayUrl()
    {
        $result = $this->send($this->scanUrl,'getPayUrl');
        if($result['code ']== 'SXF0000'){
            $return = $result['respData'];
            if($return['bizCode'] == '0000'){
                return array('code'=>'0000','url'=>$return['payUrl']);
            } else {
                return array('code'=>'0001','msg'=>$return['bizMsg']);
            }
        } else {
            return array('code'=>'0001','msg'=>$result['msg']);
        }
    }

    // 付款码支付
    public function micropay()
    {
        $result = $this->send($this->microUrl,'micropay');
        if($result['code'] == 'SXF0000'){
            $return = $result['respData'];
            if($return['bizCode'] == '0000'){
                return array('code'=>'0000','transId'=>$return['uuid']);
            } else if($return['bizCode'] == '2002' or $return['bizCode'] == '1005'){
                return $this->password();
            } else {
                return array('code'=>'0001','msg'=>$return['bizMsg']);
            }
        } else {
            return array('code'=>'0001','msg'=>$result['msg']);
        }
    }

    // 用户输入密码查询订单
    public function password()
    {
        $mno = $this->parameters['mno'];
        $ordNo = $this->parameters['ordNo'];
        // 查询6次订单
        $while = 6;
        do {
            sleep(5);
            $while--;
            $this->setNull();
            $this->setParameters('ordNo', $ordNo);
            $this->setParameters('mno', $mno);
            $result = $this->send($this->queryUrl, 'query_password');
            if($result['code'] == 'SXF0000'){
                $return = $result['respData'];
                if($return['bizCode'] == '0000'){
                    if($return['tranSts'] == 'SUCCESS'){ // 支付成功
                        return array('code'=>'0000','transId'=>$return['uuid']);
                    } else if($return['tranSts'] == 'PAYING') { // 支付中
                        continue;
                    } else {
                        return array('code'=>'0001','msg'=>$return['bizMsg']);
                    }
                }  else {
                    return array('code'=>'0001','msg'=>$return['bizMsg']);
                }
            } else {
                return array('code'=>'0001','msg'=>$result['msg']);
            }
        } while ($while);

    }

    // 退款
    public function refund()
    {
        $result = $this->send($this->refundUrl,'refund');
        if($result['code'] == 'SXF0000'){
            $return = $result['respData'];
            if($return['bizCode'] == '0000'){
                return array('code'=>'0000');
            } else {
                return array('code'=>'0001','msg'=>$return['bizMsg']);
            }
        } else {
            return array('code'=>'0001','msg'=>$result['msg']);
        }
    }

    // 查询订单
    public function query()
    {
        $result = $this->send($this->queryUrl,'query');
        if($result['code'] == 'SXF0000'){
            $return = $result['respData'];
            if($return['bizCode'] == '0000'){
                if($return['tranSts'] == 'SUCCESS'){
                    return array('code'=>'0000','msg'=>'订单支付成功');
                } else {
                    return array('code'=>'0002','msg'=>'订单未支付:'.$return['tranSts']);
                }
            } else {
                return array('code'=>'0003','msg'=>$return['bizMsg']);
            }
        } else {
            return array('code'=>'0001','msg'=>$result['msg']);
        }
    }

<<<<<<< HEAD

=======
    // 获取签名字符串
    public function get_sign_content($para)
    {
        $res = $this->argSort($para);
        return $this->createLinkstring($res);
    }

    // 拼接参数
    function createLinkstring($para) {

        $params = array();

        foreach($para as $key => $value){

            if(is_array($value)){

                $value=stripslashes(urldecode(json_encode($value)));

            }

            $params[] = $key .'='. $value ;

        }

        $data = implode("&", $params);


        get_date_dir($this->path,'sign','字符串', $data);

        return $data;

    }

    /**
     * 对数组排序
     * @param $para 排序前的数组
     * @return mixed 排序后的数组
     */
    function argSort($para) {
        ksort($para);

        return $para;
    }

    // RSA签名
    function rsa_sign($data, $privatekey)
    {
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($privatekey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";

        $pkeyid = openssl_get_privatekey($res);
        if (empty($pkeyid)) {
            echo "private key resource identifier False!";
            return False;
        }
        openssl_sign($data, $sign, $pkeyid);
        openssl_free_key($pkeyid);
        $sign = base64_encode($sign);
        return $sign;
    }
>>>>>>> c75cd230b60e79e0047330d2828b8043f316747e
}