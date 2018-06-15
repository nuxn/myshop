<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/5/26
 * Time: 17:43
 */

namespace Post\Controller;
use Think\Controller;

/**
 * 银联支付 v0.1
 * @auther:Summer<dengwz7788@gmail.com>;
 * @date:20151202
 * **/
class NetPayController extends Controller{
//在类初始化方法中，引入相关类库
    public function _initialize() {
        header("Content-type:text/html;charset=utf-8");
        vendor('Netpay.util.common',"",".php"); //导入加密核心文件夹
        vendor('Netpay.util.SecssUtil',"",".class.php"); //导入加密核心文件夹
        vendor('Netpay.util.Settings_INI',"",".php"); //导入加密核心文件夹
        vendor('Netpay.util.Settings',"",".php"); //导入加密核心文件夹
        $this->securityPropFile= $_SERVER['DOCUMENT_ROOT'] . "/ThinkPHP/Extend/Vendor/Netpay/config/security.properties"; //谁知道这是啥，反正他们要我加的
        $this->b2cPaySend = __APP__."/Index/NetPay/b2cPaySend";
        $this->b2cRefundSend = __APP__."/Index/NetPay/b2cRefundSend";
        $this->b2cQuerySend = __APP__."/Index/NetPay/b2cQuerySend";
        $this->MerBgUrl = __APP__."/Index/NetPay/MerBgUrl";
        $this->MerPageUrl = __APP__."/Index/NetPay/MerPageUrl";
    }

    public function index()
    {
        $paramArray=array (
            'MerId' => '商户号',
            'MerOrderNo' => '0000001944663232',
            'OrderAmt' => '1',
            'TranDate' => '20151219',
            'TranTime' =>'171248',
            'TranType' => '0001',
            'BusiType' =>'0001',
            'Version' => '20140728',
            'CurryNo' => 'CNY',
            'AccessType' => '0',
            'CommodityMsg' => '测试商品1号',
            'MerPageUrl' => $this->MerBgUrl,
            'MerBgUrl' =>$this->MerPageUrl,
            'MerResv' => 'MerResv',
            );
        print_r($paramArray);exit;
        if (count($paramArray) >0) {
        $dispatchUrl = $this->b2cPaySend;
        $transResvedJson = array();
        $cardInfoJson = array();
        $sendMap = array();
        foreach ($paramArray as $key => $value) {
            if (isEmpty($value)) {
                continue;
            }
            if (startWith($key, "trans_")) {
                $key = substr($key, strlen("trans_"));
                $transResvedJson[$key] = $value;
            } else
                if (startWith($key, "card_")) {
                    $key = substr($key, strlen("card_"));
                    $cardInfoJson[$key] = $value;
                } else {
                    $sendMap[$key] = $value;
                }
        }
        $transResvedStr = null;
        $cardResvedStr = null;
              if (count($transResvedJson) >0) {
                  $transResvedStr = json_encode($transResvedJson);
              }
              if (count($cardInfoJson) > 0) {
                  $cardResvedStr = json_encode($cardInfoJson);
              }
             $secssUtil = new SecssUtil();
             if (! isEmpty($transResvedStr)) {
                 $transResvedStr = $secssUtil->decryptData($transResvedStr);
                 $sendMap["TranReserved"] = $transResvedStr;
             }
             if (! isEmpty($cardResvedStr)) {
                 $cardResvedStr = $secssUtil->decryptData($cardResvedStr);
                 $sendMap["card_"] = $cardResvedStr;
             }
              $securityPropFile = $this>securityPropFile;
              $secssUtil->init($securityPropFile);
              $secssUtil->sign($sendMap);
              $sendMap["Signature"] = $secssUtil->getSign();
              $_SESSION = $sendMap;
              header("Location:" . $dispatchUrl);
             }
    }
    public function b2cPaySend(){
        layout(false);
        $settings = new Settings_INI();
        $settings->oad($this->securityPropFile);
        $pay_url = "https://payment.chinapay.com/CTITS/service/rest/page/nref/000000000017/0/0/0/0/0";
        $html = "<form name='payment' action='{$pay_url}' method='POST' target='_blank'>;";
        $params = "TranReserved;MerId;MerOrderNo;OrderAmt;CurryNo;TranDate;SplitMethod;BusiType;MerPageUrl;MerBgUrl;SplitType;MerSplitMsg;PayTimeOut;MerResv;Version;BankInstNo;CommodityMsg;Signature;AccessType;AcqCode;OrderExpiryTime;TranType;RemoteAddr;Referred;TranTime;TimeStamp;CardTranData";
        foreach ($_SESSION as $k =>$v) {
            if (strstr($params, $k)) {
                $html .= "<input type='hidden' name = '" . $k . "' value ='" . $v . "'/>";
            }
        }
        $html .= "<nput type='button' type='hidden' value='提交订单' >";
        $html .= "<;/from>";
        $this->html = $html;
        $this->display();
    }
    public function pgReturn(){
        if ($_POST) {
            if (count($_POST) > 0) {
                $secssUtil = new SecssUtil();
                $securityPropFile = $this>securityPropFile;
                $secssUtil->init($securityPropFile);
                $text = array();
                foreach($_POST as $key=>$value){
                    $text[$key] = urldecode($value);
                }
                if ($secssUtil->verify($text)) {
                    //支付成功
                    $_SESSION["VERIFY_KEY"] = "success";
                } else {
                    //支付失败
                    $_SESSION["VERIFY_KEY"] = "fail";
                }
            }
        }
    }

//支付
    public function zfb_pay($order_sn,$price){
        // 支付宝合作者身份ID，以2088开头的16位纯数字
        $partner = "2017010704905089";
        // 支付宝账号
        $seller_id = 'guoweidong@hz41319.com';
        // 商品网址
        // 异步通知地址
        //$notify_url = 'http://sy.youngport.com.cn/index.php?s=/Pay/Barcode/ali_barcode_pay';
        $notify_url = 'http://a.ypt5566.com/notify.php';
        // 订单标题
        $subject = '1';
        // 订单详情
        $body = '我是测试数据';
        // 订单号，示例代码使用时间值作为唯一的订单ID号
        $content = array();
        $content['timeout_express'] = '30m';
        $content['product_code'] = 'QUICK_MSECURITY_PAY';
        $content['total_amount'] = $price;
        $content['subject'] = $subject;
        $content['body'] = $body;
        $content['out_trade_no'] = $order_sn;
        //$orderinfo['order_amount'];
        $data = array();
        $data['app_id'] = $partner;
        $data['biz_content'] = json_encode($content);
        $data['charset'] = 'utf-8';
        $data['format'] = 'json';
        $data['method'] = 'alipay.trade.app.pay';
        $data['notify_url'] = $notify_url;
        $data['sign_type'] = 'RSA';
        $data['timestamp'] = date('Y-m-d H:i:s');
        $data['version'] = '1.0';
        $orderInfo = $this->createLinkstring($data);
        //$orderInfo = 'biz_content={"timeout_express":"30m","product_code":"QUICK_MSECURITY_PAY","total_amount":"0.01","subject":"1","body":"我是测试数据","out_trade_no":"0603181557-1017"}&method=alipay.trade.app.pay&charset=utf-8&version=1.0&app_id=2017010704905089&timestamp=2016-07-29 16:55:53&sign_type=RSA';
        //var_dump($orderInfo);
        $sign = $this->sign($orderInfo);
        //var_dump($sign);
        $data['sign'] = $sign;
        $orderInfo = $this->getSignContentUrlencode($data);

        return $orderInfo;
    }

    public function zfb_notify_url(){
        $data = $_POST;
        $sign = $data['sign'];
        $data['sign_type'] = null;
        $data['sign'] = null;
        $data = $this->getSignContent($data);
        $pubKey='MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDDI6d306Q8fIfCOaTXyiUeJHkrIvYISRcc73s3vF1ZT7XN8RNPwJxo8pWaJMmvyTn9N4HQ632qJBVHf8sxHi/fEsraprwCtzvzQETrNRwVxLO5jVmRGi60j8Ue1efIlzPXV9je9mkjzOmdssymZkh2QhUrCmZYI/FCEa3/cNMW0QIDAQAB';
        $res = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($pubKey, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";
        $result = (bool)openssl_verify($data,base64_decode($sign), $res);
        if($result&&$_POST['trade_status']=='TRADE_SUCCESS'){
            //$_POST['out_trade_no'],$_POST['trade_no'],$_POST['total_amount']
        }
    }

    public function createLinkstring($params){
        ksort($params);
        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {

                // 转换成目标字符集
                $v = $this->characet($v, 'utf-8');

                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . "$v";
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . "$v";
                }
                $i++;
            }
        }
        unset ($k, $v);
        return $stringToBeSigned;
    }

    protected function checkEmpty($value) {
        if (!isset($value))
            return true;
        if ($value === null)
            return true;
        if (trim($value) === "")
            return true;

        return false;
    }

    public function getSignContentUrlencode($params) {
        $sign = $params['sign'];
        unset($params['sign']);
        ksort($params);
        $params['sign'] = $sign;
        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {
                // 转换成目标字符集
                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . urlencode($v);
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . urlencode($v);
                }
                $i++;
            }
        }
        unset ($k, $v);
        return $stringToBeSigned;
    }

    protected function sign($data, $signType = "RSA") {
        $priKey="MIICXAIBAAKBgQC/UIMSw0mWKRp3wP3v0tbKKqtQO80iL3gBkceCE41KRtpE8+ljXzH16jXs5Alj3cPNZlAh+2SApLBv9sVY9nGU8rd6d5294HHH8APDdrHBtdUTpgZnKGNATFhCeiZPuLD76DJslWtSM4a8kW9EkBUDe3mFLtDQwu+ZtbUYf0k8eQIDAQABAoGALzKFo5NaDBmH1hNeklPJeYadTOXz7YMYcAqu1YBNUw23u1sRMNrDUI+/TfnT9zc2nu2mxztlx/bZMEYI2bGyw0Y/3oYl4GHDmeSyqq9o6SjL9S6GRtj+ngG8CX9QuVj7lTqcvHJrBR8E48EiyH4VK9ouySIHE9Ukf71VQVPd0AECQQD5uCTXuCd6aGbs2XqTfX/tfaUio5lFsdvTrccnFdTvDJ/EWwuMXgceJvehsOmNvZK8NabruGkyAk25ABM9bxq5AkEAxCBR2xvTVSBf/ohpB1/y94Imx1pb5OLvrRMvxq3LEcQORDzbSQdY27UZ1i3tC8CLvPF68KE985j75xgY//9PwQJBALx10LhM7t5etG7DotJ0wHtHe70sopwKotCaMda5jz3p1RmnbIu+2rhSyEhq75hdHcSU6Si0wA9R1b5s5BhVJWECQClVdmrrLlree9y0+w2m1xn6wBl7napbeY/MX2FL92RDIY6YFM8LUVrcjBlrjG3RoqGrcvGLyfaw40YM+hfZwwECQFNwNJ6wB5INtt+CeJkJSiaHuhevFmr3w8UD00dOXxTMz2m86cmV+ZNl5srCkeunKdwVCMwNhUHFfLfCno3XyEk=";
        //$priKey="MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBAL6EhsF9ufhXqx5ZJwGy5MLP5AcoFsp1I3hWpJgWwLSXKSRM5mkKmp/OOLltJtIF+ViKk1nOgE99J3C9yFjoXV9PWtNhClZmvOk+qAGweC4rzkjumhNC5vTnYf11Hp2+oes5vWMm7DAFFx/owNecNrlQl9cHQCj96pcElWFrhYhNAgMBAAECgYEAln5nWEbxdWwDHwj7mArxS7YegUy4nBrl9vQyNnWaqczSUftw8r7On7et9UN0q+jOK5Pji8hkcOYDFrrDnP+IaRX6KVMYjL4sHltoj+XlEWnUdz5B9MIlKg6ops1aEd4d5PFD+ixw5yvbEsc9nXaKz+8ttm2w+7LWkUTEGres6t0CQQD+paORxMv7APKSlKtzyOw0m6Xr7cydwtJqWexzOI8whfud7ODJV2VEmsJMfsh7HCxpeJET/9Rt5jq9P51ZicbrAkEAv4epQ3xaNUFfkFgYn94V8gGP0K11LrFhB30/MvWGHEuPt+/2ZiF9hXmyeIIktW3QDTcwfd0hfHAzkwgrurcPpwJAUUsbztteq0EAL59apNoN3jWaYJlH601Y0y7l91qlC76aNy56DIzj/WTSho0q/3JdE0a0OghADt2i/uuiFgWQBQJAVFnr6uPWWsP60XhrB+VoZtfXPcFW7YSDRigb8FZ/hPCmUAznyJ0RSfqJ5lby0dCWI2vd+GCuQb6siCG+GJJM2wJATROJfcSEWwNahKNCykUeN8eDd8Iv4Ko1uixynvnMdZZB8YgVQ4C0Y09RBtzi7Dt1StF1aYlAqn9T/ryhFMoP3A=="
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($priKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";

        ($res) or die('您使用的私钥格式错误，请检查RSA私钥配置');

        if ("RSA2" == $signType) {
            openssl_sign($data, $sign, $res, OPENSSL_ALGO_SHA256);
        } else {
            openssl_sign($data, $sign, $res);
        }
        openssl_free_key($res);
        $sign = base64_encode($sign);
        return $sign;
    }

    public function rsaSign($data){
        $rsaPrivateKey='MIICXgIBAAKBgQDktchFUfoxjoaGGTOY5/S9cpWWon6Gc2AmmwgEwmqCHMnUIPRMa9nYfJEJo0lnJsJTUfmjwvuyWmKLyBdLjvEIbvvNMHtddKev5WfO4SEU24E2OPeGAQdxFcM89mBHxgcYoGIU7W8WUKTwY2oTjNoFuHG4SFsSBr8FdB0K0E9T/QIDAQABAoGBAMgZz5XuymKvWz1aMU2XrAZQiVZY5zBFI5vDSjm3y634+BCzoGp4dgm1usPe7Crmu2BguXSw9Lwv3kaEEvWVo4VgmOmpUTPBRxBOkSlbEH3zxsy2JtzcSV0dGLzkWlsmWymOCktk7XwKj9KCNLcfyiygIvWlI/sJRLg6fz+X2PN1AkEA/O+1LbRYLP7IvF6KO8Q0+G4/FdNThreoTwuO7ve32mUMBQOFrej08mbt9XAFuB+t7L3FKqEa7Tqq4uoiFpxSRwJBAOd69Hm4sE5bi59qO3hUCxlyLUaw5+PnmWv90wlGCNxgNYZWQyNJES74ifD4Jo3Ya2vJI9azF+MbaxXkLt1w5ZsCQQCPw6eVPMZJfN+XwZyMb+8zrWYJ/72f+s+dbhJl3UMQzRJR3ziiKqDfDoX+VRfLGaZ/wzVID54AbLIom6+Ybm2NAkEAhKZdOvOvdPfZBz2lXssqoSZg88Wk3WF29f+60/GiWkd35MfCKZJRmo0q30AVN+vxgw78zqhK5AZuU1kz0gpESQJAboDgERMcwS5e9ib9ax5lPzsAQ5F5JVUHH0VogEzpx6GjAzPp3/94zoCMEHTBpGy2ABpiyjTJvVM2Aaa0qAfmvw==';
        $priKey=$rsaPrivateKey;
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($priKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";
        $res = openssl_get_privatekey($res);

        ($res) or die('您使用的私钥格式错误，请检查RSA私钥配置');

        openssl_sign($data, $sign, $res);
        openssl_free_key($res);
        $sign = base64_encode($sign);
        $sign = urlencode($sign);
        return $sign;
    }

    public function getSignContent($params) {
        ksort($params);
        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {
                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . stripslashes($v);
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . stripslashes($v);
                }
                $i++;
            }
        }

        unset ($k, $v);
        return $stringToBeSigned;
    }
}