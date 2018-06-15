<?php

namespace Pay\Controller;

use Think\Controller;

/**
 * Class ApipayController
 * @package Pay\Controller
 */
class ApipayController extends Controller
{

    public function _initialize()
    {
        header("Content-Type: text/html;charset=utf-8");
    }

    /**
     * @return mixed
     */
    function get_openid()
    {
        header("content-type:text/html;charset=utf-8");
        //和众世纪
        $config['APPID'] = 'wx8b17740e4ea78bf5';
        $config['APPSECRET'] = 'bbd06a32bdefc1a00536760eddd1721d';
        //洋仆淘
        $config['APPID'] = 'wx3fa82ee7deaa4a21';
        $config['APPSECRET'] = '6b6a7b6994c220b5d2484e7735c0605a';

        $redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . $_SESSION['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $redirect_uri = urlencode($redirect_uri);
        $url = "https://sy.youngport.com.cn/redirect/get-weixin-code.html?appid=" . $config['APPID'] . "&scope=snsapi_base&state=hello-world&redirect_uri=" . $redirect_uri;
        // 如果没有get参数没有code；则重定向去获取openid；

        if (!isset($_GET['code'])) {
            S('api_mer_url', base64_decode($_REQUEST['url']), 600);
            header("location:$url");
            exit();
        } else {
            $code = $_GET['code'];
            $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $config['APPID'] . '&secret=' . $config['APPSECRET'] . '&code=' . $code . '&grant_type=authorization_code';
            $result = $this->curl_get_contents($url);
            echo '<pre/>';
            $result = json_decode($result, true);
            print_r($result);
            $token = get_weixin_token();
            $userInfoUrl = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token=' . $token . '&openid=' . $result['openid'] . '&lang=zh_CN';
            $userInfo = $this->curl_get_contents($userInfoUrl);
            $user_data = json_decode($userInfo, true);
            $return = array(
                'openid' => $user_data['openid'],
                'unionid' => $user_data['unionid']
            );
            $userInfo = json_encode($return);
            var_dump($userInfo);

            $mer_url = S('api_mer_url') . '?param=' . $userInfo;

            var_dump($mer_url);
            //sleep(5);
            header("location:$mer_url");
            return $result;
        }
    }

    function curl_get_contents($url)
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

    function index()
    {
        $this->get_openid();
    }
}


