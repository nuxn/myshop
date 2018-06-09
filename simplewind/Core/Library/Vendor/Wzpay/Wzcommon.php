<?php

/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/1/6
 * Time: 10:55
 */
/**
 *        trimString()，设置参数时需要用到的字符处理函数
 *        createNoncestr()，产生随机字符串，不长于32位
 *        formatBizQueryParaMap(),格式化参数，签名过程需要用到
 *        getSign(),生成签名
 *        arrayToXml(),array转xml
 *        xmlToArray(),xml转 array
 *        postXmlCurl(),以post方式提交xml到对应的接口url
 *        postXmlSSLCurl(),使用证书，以post方式提交xml到对应的接口url
 **/
include_once("WzPay.pub.config.php");

class Wzcommon
{
    //请求参数
    private $parameters;
    //交易加密字符串的key
    private $key;
    public $wx_path;//打印日志路径

    public function __construct()
    {
        $this->wx_path = $_SERVER['DOCUMENT_ROOT'] . '/data/log/wz/weixin/';
    }

    /**
     *    作用：array转xml
     */
    private function arrayToXml($arr)
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

    /**
     * @param $arr 要加密的数组
     * @param $sign 当前使用的key
     * @return string 生成签名
     */
    private function getSign($arr)
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
        $String = $this->formatBizQueryParaMap($Parameters, false);
//        echo '【string1】' . $String . '</br>';
        //签名步骤二：在string后加入KEY
        $key = $this->key ? $this->key : \WzPayConf_pub::APPLY_KEY;
        $String = $String . "&key=" . $key;
//        echo "【string2】" . $String . "</br>";
        //签名步骤三：MD5加密
        $String = md5($String);
//        echo "【string3】 " . $String . "</br>";
        //签名步骤四：所有字符转为大写
        $result_ = strtoupper($String);
//        echo "【result】 " . $result_ . "</br>";
        return $result_;
    }


    /**
     *    作用：格式化参数，签名过程需要使用
     */
    private function formatBizQueryParaMap($paraMap, $urlencode)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if ($urlencode) {
                $v = json_encode($v);
            }
            //$buff .= strtolower($k) . "=" . $v . "&";
            $buff .= $k . "=" . $v . "&";
        }
        $reqPar = '';
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }

        return $reqPar;
    }

    /**
     *    作用：产生随机字符串，不长于32位
     */
    private function createNoncestr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }

        return $str;
    }

    /**
     *    作用：以post方式提交xml到对应的接口url
     */
    private function postXmlCurl($xml, $url, $second = 30)
    {
        //初始化curl
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        //这里设置代理，如果有的话
        //curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
        //curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        //curl_close($ch);
        //返回结果
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

    /**
     *    作用：使用证书，以post方式提交xml到对应的接口url
     */
    private function postXmlSSLCurl($xml, $url, $second = 30)
    {
        file_put_contents(get_date_dir($this->wx_path). date("Y_m_d_") .'curl.log', date("Y-m-d H:i:s").'curl请求url' . $url. PHP_EOL, FILE_APPEND | LOCK_EX);
        file_put_contents(get_date_dir($this->wx_path). date("Y_m_d_") .'curl.log', date("Y-m-d H:i:s").'curl请求参数' . $xml. PHP_EOL, FILE_APPEND | LOCK_EX);

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
        $header = array("Content-Type: application/json");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //设置证书
        //使用证书：cert 与 key 分别属于两个.pem文件
        //默认格式为PEM，可以注释
        curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLCERT, WzPayConf_pub::SSLCERT_PATH);
        //默认格式为PEM，可以注释
        curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLKEY, WzPayConf_pub::SSLKEY_PATH);
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $data = curl_exec($ch);
        file_put_contents(get_date_dir($this->wx_path). date("Y_m_d_") .'curl.log', date("Y-m-d H:i:s").'curl请求结果' . $data. PHP_EOL.PHP_EOL, FILE_APPEND | LOCK_EX);
        //返回结果
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



    /**
     *    作用：设置请求参数
     */
    public function setParameter($parameter, $parameterValue)
    {
        $this->parameters[$parameter] = $parameterValue;
    }


    /**
     * @param  key请求 1. APPLY_KEY 商户进件 2. ORDER_KEY 交易key
     * @return string
     */
    private function createjson()
    {
        $this->parameters["sign"] = $this->getSign($this->parameters);//签名
        return json_encode($this->parameters);
    }

    /**
     * @return string 服务器IP
     */
    private function get_server_ip() {
        if (isset($_SERVER)) {
            if($_SERVER['SERVER_ADDR']) {
                $server_ip = $_SERVER['SERVER_ADDR'];
            } else {
                $server_ip = $_SERVER['LOCAL_ADDR'];
            }
        } else {
            $server_ip = getenv('SERVER_ADDR');
        }
        return $server_ip;
    }

    /**
     * 获取jssdk需要用到的数据
     * @return array jssdk需要用到的数据
     */
    public function getParameters($url,$mch_id)
    {
        //获取用户key
        $this->key = $this->getWzKey($mch_id);
        $json = $this->createjson();
        $returnData = $this->postXmlSSLCurl($json, $url);
        $returnjson=json_decode($returnData, true);
        $result=$returnjson['result'];
        // 显示错误信息
        if ($result['errmsg'] != 'OK') {
            file_put_contents('./data/log/wz/weixin/common.log', date("Y-m-d H:i:s") . '公共信息' . $returnData . PHP_EOL, FILE_APPEND | LOCK_EX);
            if($result['message']=="签名错误"){
                die("系统正在维护,请稍后再试");
            }
            return $result['errmsg'];
        }

        return $result['errmsg'];

    }

    private function getWzKey($mch_id)
    {
        return M('merchants_cate')->where(array('wx_mchid' => $mch_id))->getField('wx_key');
    }

}