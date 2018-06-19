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
            'orgId' => '07296653',           // 洋仆淘机构号唯一
            'version' => '1.0',             // 版本
            'signType' => 'RSA',            // 签名方法
        );
    }

    private function getSign()
    {
        $string = get_sign_content($this->requestParams);
        $sign = rsa_sign($string, $this->private_key);
        return $sign;
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
//            echo "<a href='http://curl.haxx.se/libcurl/c/libcurl-errors.html'>错误原因查询</a></br>";
            curl_close($curl);
            return false;
        }
    }

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

    public function setParameters($key, $val)
    {
        $this->parameters[$key] = $val;
    }

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

    public function micropay()
    {
        $result = $this->send($this->microUrl,'micropay');
        if($result['code'] == 'SXF0000'){
            $return = $result['respData'];
            if($return['bizCode'] == '0000'){
                return array('code'=>'0000','transId'=>$return['uuid']);
            } else if($return['bizCode'] == '2002'){
                $this->password();
            } else {
                return array('code'=>'0001','msg'=>$return['bizMsg']);
            }
        } else {
            return array('code'=>'0001','msg'=>$result['msg']);
        }
    }

    public function password()
    {
        return array('code'=>'0001','msg'=>'交易异常');
//        $mno = $this->parameters['mno'];
//        $ordNo = $this->parameters['ordNo'];
//        $this->parameters = array();
//        $this->setParameters('ordNo', $ordNo);
//        $this->setParameters('mno', $mno);
//        $while = 6;
//        do {
//            $result = $this->send($this->queryUrl, 'query_password');
//            sleep(5);
//            $while--;
//        } while ($while);

    }

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

    public function query()
    {
        $result = $this->send($this->queryUrl,'query');
        if($result['code'] == 'SXF0000'){
            $return = $result['respData'];
            if($return['bizCode'] == '0000'){
                return array('code'=>'0000','msg'=>'订单已支付');
            } else {
                return array('code'=>'0001','msg'=>$return['bizMsg']);
            }
        } else {
            return array('code'=>'0001','msg'=>$result['msg']);
        }
    }


}