<?php

/**
 * XML转数组
 * @param $xml
 * @return mixed
 */
function xmlToArray($xml)
{
    $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    return $array_data;
}

/**
 * 数组转XML
 * @param $arr
 * @return string
 */
function arrayToXml($arr)
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
 * 获取随机字符串
 * @param int $len
 * @return string
 */
function getNonceStr($len = 32)
{
    $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
    $str = "";
    for ($i = 0; $i < $len; $i++) {
        $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }

    return strtoupper($str);
}

/**
 * 微信加签名
 * @param $arr
 * @param $key
 * @return string
 */
function getSign($arr, $key)
{
    //过滤null和空
    $Parameters = array_filter($arr, function ($v) {
        if ($v === null || $v === '') {
            return false;
        }
        return true;
    });

    //签名步骤一：按字典序排序参数
    ksort($Parameters);
    $String = formatBizQueryParaMap($Parameters, false);

    //签名步骤二：在string后加入KEY
    $String = $String . "&key=" . $key;
    //签名步骤三：MD5加密
    $String = md5($String);

    //签名步骤四：所有字符转为大写
    $result_ = strtoupper($String);

    return $result_;
}

/**
 * 拼接加签字符串
 * @param $paraMap
 * @param $urlencode
 * @return bool|string
 */
function formatBizQueryParaMap($paraMap, $urlencode)
{
    $buff = "";
    ksort($paraMap);
    foreach ($paraMap as $k => $v) {
        if ($urlencode) {
            $v = json_encode($v);
        }
        $buff .= $k . "=" . $v . "&";
    }
    $reqPar = '';
    if (strlen($buff) > 0) {
        $reqPar = substr($buff, 0, strlen($buff) - 1);
    }

    return $reqPar;
}

/**
 * 发送post请求
 * @param $url 请求地址
 * @param $post_data    请求数据
 * @param int $time     超时时间
 * @return bool|mixed
 */
function sendRequest($url, $post_data, $time = 10)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, $time);               //设置超时
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $data = curl_exec($ch);
    if ($data) {
        curl_close($ch);
        return $data;
    } else {
        $error = curl_errno($ch);
        echo $error;
        curl_close($ch);
        return false;
    }
    return $data;
}

/**
 * 返回订单号
 * @return string
 */
function getRemark()
{
    return date("YmdHis") . mt_rand(100000,999999);
}

function get_wx_openid($config = '')
{
    // 获取配置项
    if(!$config){
        $config = C('WEIXINPAY_CONFIG');
    }
    // 如果没有get参数没有code；则重定向去获取openid；
    if (!isset($_GET['code'])) {
        // 返回的url
        $redirect_uri = 'https://' . $_SERVER['HTTP_HOST'] . $_SESSION['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $redirect_uri = urlencode($redirect_uri);
        $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $config['APPID'] . '&redirect_uri=' . $redirect_uri . '&response_type=code&scope=snsapi_base#wechat_redirect';
        redirect($url);
    } else {
        //如果有code参数；则表示获取到openid
        $code = I('get.code');
        //组合获取openid的url
        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $config['APPID'] . '&secret=' . $config['APPSECRET'] . '&code=' . $code . '&grant_type=authorization_code';
        //curl获取openid
        $result = curl_get_openid($url);
        $result = json_decode($result, true);
        return $result['openid'];
    }
}

function curl_get_openid($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);                //设置访问的url地址
    // curl_setopt($ch,CURLOPT_HEADER,1);               //是否显示头部信息
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);               //设置超时
    curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);   //用户访问代理 User-Agent
    curl_setopt($ch, CURLOPT_REFERER, $_SERVER['HTTP_HOST']);        //设置 referer
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);          //跟踪301
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);        //返回结果
    $r = curl_exec($ch);
    curl_close($ch);

    return $r;
}

function get_cate_info($id)
{
    $res = M('merchants_cate')->field("merchant_id,checker_id,jianchen")->where(array('id'=>$id))->find();
    return $res;
}

