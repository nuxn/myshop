<?php

namespace Merchants\Model;

use Common\Model\CommonModel;

class MerchantsUpsxfModel extends CommonModel
{
    protected $tableName = 'merchants_upsxf';
    protected $trueTableName = 'ypt_merchants_upsxf';

    private $parameters = array();
    private $requestParams;
    private $private_key;
    private $public_key;

    private $getTaskCodeUrl;
    private $batchFeedInfo;

    private $path;

    public function __construct()
    {
        parent::__construct();
//        $this->getTaskCodeUrl  = 'https://icm-test.suixingpay.com/management/BatchFeed/getTaskCode';
        $this->getTaskCodeUrl  = 'https://icm-management.suixingpay.com/management/BatchFeed/getTaskCode';
//        $this->batchFeedInfo  = 'https://icm-test.suixingpay.com/management/BatchFeed/batchFeedInfo';
        $this->batchFeedInfo  = 'https://icm-management.suixingpay.com/management/BatchFeed/batchFeedInfo';

        $this->path = $_SERVER['DOCUMENT_ROOT'] . '/data/log/Banksxf/';
        $this->private_key = "MIICXAIBAAKBgQDf3b7jHPeK4lzFcxtEQOjPx6UZ6jjIQJADqvoS0Sg/7fr27H732zgxMCqTcMMrgfqAcb2cNu4pNpcN/vvtYZAvIQMahv5ymI/la00HOeNcZpw6GzeExHo7AL+W33lXs2OTTPcIm1m8M1KKcJn0XPF3js8wA12DiyCcQxMOA38FAwIDAQABAoGBAM5KoOTYhKRPA/5PnAjBZ8hQySugUsL1+7/lhpxgcR64RlPUiwwLzzRElndXqgIlvJkwNvIFDGKeE4SqO6z8AsgxdYudM6kSMKjROopKzFBx0Mjk6VGBi6c/Lgpdv/xDu7qN+Dzf8ovI23dGLnLAFGuzWPmJUM8Skx0N7Sq2nEoBAkEA+9KNldJ2REYbuGp6NBWUtUenyTrezzejlxqArfKHc4NYCknpCkD3xrDhOn+qAmFX29Hq5JVX0fD4VHBV7nZfIQJBAOOUd5DmPIiJ+Fp2dYh4yIxlLUXe+2//ts2PenRbVH1bpMllWps9I4hOgj1elbZDkaTLXXWNXbqZ4zUG7PfxE6MCQGL8O71VsjlaGZFfAVQx23d6iCCYbHallz9RIp29hLLKQTQiI2Ftcjf+1TmqbwhqfR+iHyPk9FVI1ERUt+J5UyECQFCzrVKs0np4sqEhsLwcWMGwf0VvtSoaO/DZGEt6t5NclCr2zhKOs7L6ZCTvDZf8jgEqPJIa90ncmD2NnyqtSpECQBQldmr+fMfYSP40PfoqQ4DxjFnq4HjbnzSr+8zi9tP/W7zUdEy+0Mi4Ufnpy4l4wIzqso4SV44V8UJe1rSFmWg=";

//        $this->private_key = "MIICXAIBAAKBgQC+2v20Ci5VLz7r9si0AuYz3wFLWLE2Vucr1qTWpUY7smlDycOaa/WpasvKssg5lUdgK62JHvFQF2UTqZ2gBm3+atpCUvJFVC29OH4cah7qg0ryUgphEroDsas+zFjQf46EhkE37hem+UhNPcSnMahta+Jnusqftgj2fuHBUaXtzwIDAQABAoGAXC3e3ScRq7ju9f6yfybrUmBB+scyiCE+89BuuvEGU+zepIv9ekbsVtAq75Kb3Bv6ZjuSTCjyuhEik3WXmOOiGapaBmaXl9kkx0UtQsfjpV8dQIAGGskPkn5fkZGFzwmG5VB46B2a1kuR/OpNojIS7Z6Kd+32+KVfKcn1xLH1mykCQQDqBuBqPPwMk3wrXmPXrzZ7li3mO0K4SZTDKT2xfe6rGOprLGCxaXp01OOhWxmXEeW+I1P0b6qL8V+HgzjZjNtDAkEA0MZvoPyJC5Y09/ZSBM0S2izntJ4kGB39rASxpo0CXTDLCIz6k35/abwgCOmX9V8XXnx4og76FDdp3DTNp02shQJBAMUPj07GFXM9iZQ3QhlvN5BvkCzK/86QXwzLIGDh6uP18gbW8oDRkcTpMtg/DthPwMYPl3U/xjtav5crXuaJnmMCQCO6AXpMHOulrbTNKyX1Lge17YTEFyslXrakKv50XPYzllsFPRAmcolWjyjXSJDN0AL0S/R3maYCAZSUWKkLqr0CQGZ2zZVJn4NLv3r9uwJMUljQr+5CoToDp7eahWgN9vO389H0u0Kbhgg0326B4h7DwVl20w7qVwkpnWqTLwz/yqY=";

        $this->public_key = '';
        $this->requestParams = array(
//            'orgId' => '07296653',           // 洋仆淘机构号唯一
            'orgId' => '65554373',           // 洋仆淘机构号唯一
            'version' => '1.0',             // 版本
            'signType' => 'RSA',            // 签名方法
        );
    }

    private function getSign()
    {
        $string = $this->get_sign_content($this->requestParams);
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
            $this->get_date_log($this->path,'requestPost','请求错误码', $error);
//            echo "<a href='http://curl.haxx.se/libcurl/c/libcurl-errors.html'>错误原因查询</a></br>";
            curl_close($curl);
            return false;
        }
    }

    private function send($url, $file_name = '')
    {
        $this->requestParams['reqId'] = md5(getOrderNumber());    // 请求唯一编号
        $this->requestParams['timestamp'] = date('YmdHis');    // 请求时间
        $this->requestParams['reqData'] = $this->parameters;
        $this->requestParams['sign'] = $this->getSign();
        $this->requestParams['reqData'] = array_map(function ($val){ return urldecode($val);}, $this->parameters);
        $send = json_encode($this->requestParams);

        $result = $this->requestPost($url,$send);
        if($file_name){
            $this->get_date_log($file_name,'请求地址', $url);
            $this->get_date_log($file_name,'请求数据', $send);
            $this->get_date_log($file_name,'返回', $result);
        }
        return json_decode($result, true);
    }

    public function setParameters($key, $val)
    {
        $this->parameters[$key] = $val;
    }

    public function setInfoParams($val)
    {
        foreach ($val as $key => $va) {
            $this->parameters[$key] = urlencode($va);
        }
    }

    public function batchFeedInfo()
    {
        return $this->send($this->batchFeedInfo, 'into');
    }

    public function setNull()
    {
        $this->parameters = null;
    }


    public function getTaskCode()
    {
        return $this->sendFile($this->getTaskCodeUrl);
    }

    private function sendFile($url)
    {
        $send = $this->parameters;
        $this->get_date_log('sendFile','请求地址', $url);
        $this->get_date_log('sendFile','参数', json_encode($send));
        $result = $this->request_post($url,$send);
        $this->get_date_log('sendFile','结果', $result);
        return json_decode($result, true);
    }

    private function request_post($url, $data = '',$time=30)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SAFE_UPLOAD, false);
        curl_setopt($curl, CURLOPT_TIMEOUT, $time);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        if ($output) {
            curl_close($curl);
            return $output;
        } else {
            $error = curl_errno($curl);
            echo "curl出错，错误码:$error" . "<br>";
            echo "<a href='http://curl.haxx.se/libcurl/c/libcurl-errors.html'>错误原因查询</a></br>";
            curl_close($curl);
            return false;
        }
    }

    private function get_date_log($file_name,$title,$param)
    {
        $Y = $this->path . date("Y-m");
        if (!file_exists($Y)) mkdir($Y, 0777, true);
        file_put_contents($Y.'/' . "$file_name.log", date("Y-m-d H:i:s") . $title.':'. $param . PHP_EOL . PHP_EOL, FILE_APPEND | LOCK_EX);
    }


    public function get_sign_content($para)
    {
        return $this->createLinkstring($para);
    }


    function createLinkstring($para) {

        ksort($para);
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
}