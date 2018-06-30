<?php

namespace Pay\Controller;

use Common\Controller\HomebaseController;
use Common\Lib\Subtable;

/**
 * 平安银行支付
 * Class BarcodepabankController
 * @package Pay\Controller
 */
class BarcodepabankController extends HomebaseController
{
    private $url;
    private $price;
    private $merchantId;
    private $platMerchantId;
    private $version = '1.0.0';
    private $charset = 'UTF-8';
    private $signMethod = 'SHA-256';
    private $orderCurrency = 'CNY';
    private $platMerchantId_key = '8c56bfb3d5914c319a5ef2ab61a67eae';
//    private $return_url = 'http://sy.youngport.com.cn/index.php?g=Pay&m=Barcode&a=weixipay_return000';
    private $pay_model;

    function __construct()
    {
        parent::__construct();
        //8c56bfb3d5914c319a5ef2ab61a67eae
        # STABLE：https://test-mapi.stg.1qianbao.com/revOrder
        # 生产:https://mapi.1qianbao.com/revOrder
        $this->frontEndUrl = 'https://sy.youngport.com.cn/public/returnurl.html';
        $this->url = 'https://mapi.1qianbao.com/revOrder';
        $this->notify_url = 'http://sy.youngport.com.cn/notify/pingan_notify.php';
//        $this->merchantId = '900000030302';// 900000030302
        $this->platMerchantId = '900000030293'; // 900000030293
//        $this->platMerchantId_key = '8c56bfb3d5914c319a5ef2ab61a67eae';

        $this->pay_model = M(Subtable::getSubTableName('pay'));
        # 测试账号信息
//        $this->url = 'https://test-mapi.stg.1qianbao.com/revOrder';
//        $this->merchantId = '900000112175';
//        $this->merchantId_key = 'aaa98927471a48e5a47d903547b0a031';
//        $this->platMerchantId = '900000112169';
//        $this->platMerchantId_key = '759321033d6843e4991c30cc996c4790';
    }

    //扫码支付
    public function index()
    {
    }

    public function qr_weixipay()
    {
        //这里直接获得openid;
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            $id = I("id");
            $merchant = M("merchants_cate")->where("id=$id")->find();
            $openid = $this->get_openid();
//            $this->getOffer($merchant, $openid);
            $this->assign('openid', $openid);
            $this->assign("merchant", $merchant);
            $this->assign("checker_id", $merchant['checker_id']);
            $this->assign('seller_id', I('id'));
            $this->display();
        }
    }

    private function get_cate_info($id)
    {
        $res = M('merchants_cate')->where(array('id'=>$id))->find();
        return $res;
    }

    public function wx_pay()
    {
        $sub_openid = I('openid');
        $id = I('seller_id');
        $price = I('price');
        $this->price = $price;
        $cate_info = $this->get_cate_info($id);
        $into_data = M('merchants_pingan')->where(array('mid'=>$cate_info['merchant_id']))->find();
        $this->merchantId = $into_data['sub_mchid'];
        $checker_id = $cate_info['checker_id'];
        $remark = $this->get_remark();
        $data = array(
            'merchant_id' => $cate_info['merchant_id'],
            'customer_id' =>  D("Api/ScreenMem")->add_member("$sub_openid", $cate_info['merchant_id']),
            'buyers_account' => '',
            'phone_info' => '',
            'wx_remark' => '',
            'wz_remark' => '',
            'new_order_sn' => '',
            'no_number' => '',
            'transId' => '',
            'la_ka_la' => 0,
            'add_time' => time(),
            'paytime' => time(),
            'bill_date' => date('Ymd'),
            'checker_id' => $checker_id,
            'paystyle_id' => 1,
            'price' => $price,
            'remark' => $remark,
            'status' => 0,
            'cate_id' => $cate_info['id'],
            'mode' => 0,
            'bank' => 13,
            'cost_rate' => $into_data['cost_rate'],
            'subject' => $cate_info['jianchen'],
            'remark_mer' => '',
        );
        $data['jmt_remark'] = I('memo','')?:I("jmt_remark",'');
//        $frontEndUrl = "https://sy.youngport.com.cn/index.php?s=/Pay/Barcode/weixipay_return000/price/{$price}/openid/{$sub_openid}/remark/{$remark}/mid/".$cate_info['merchant_id'];
        $db_res =  $this->pay_model->add($data);
        if($db_res){
            $this->fileName = 'wx_js_pay.log';
            $post_data['version'] = $this->version; // 版本号 固定1.0.0
            $post_data['charset'] = $this->charset; // 字符编码 UTF-8
            $post_data['transType'] = '001';    // 交易类型 001
            $post_data['transCode'] = '0071';   // 交易代码 0071
            $post_data['bizType'] = '000001';   // 业务类型 000001
            $post_data['merchantId'] = $this->merchantId;  // 平安付分配给旺小宝 子商户号
            $post_data['platMerchantId'] = $this->platMerchantId;  // 平安付分配给旺小宝的商户号
            $post_data['backEndUrl'] = $this->notify_url;   // 异步通知地址
            $post_data['frontEndUrl'] = $this->frontEndUrl; // 支付跳转地址
            $post_data['orderTime'] = date('YmdHis');    // 交易开始日期时间
            $post_data['sameOrderFlag'] = 'N';    // Y 重复订单  N 非重复
            $post_data['mercOrderNo'] = $data['remark'];    // 商户订单/流水号
            $post_data['orderAmount'] =$price * 100;  // 交易金额 单位分
            $post_data['orderCurrency'] = $this->orderCurrency; // 交易币种 CNY
            $post_data['riskInfo'] = $this->getriskinfo(); // 风控信息
            $post_data['merReserved'] = $this->getwxmerReserved(); // 商户保留域
            $post_data['signature'] = $this->get_sign($post_data,$this->platMerchantId_key);   //签名
            $post_data['signMethod'] = $this->signMethod; // 加密方式 SHA-256

            $this->writeLog('wx_js_pay.log','地址',$this->url,0);
            $this->writeLog('wx_js_pay.log','参数',($post_data));
            $this->assign('data',$post_data);
            $this->assign('url',$this->url);
            $this->display('wx_pay');
        } else {
            $this->alert_err();
        }
    }

    public function qr_alipay()
    {
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient') !== false) {
            $id = I('id');
            $merchant = M("merchants_cate")->where("id=$id")->find();
            $this->assign("checker_id", I('checker_id'));
            $this->assign("merchant", $merchant);
            $this->assign('seller_id', $id);
            $this->display();
        }
    }

    public function qr_to_alipay()
    {
        $id = I("seller_id");
        $price = I("price");
        $this->price = $price;
        $cate_info = $this->get_cate_info($id);
        $into_data = M('merchants_pingan')->where(array('mid'=>$cate_info['merchant_id']))->find();
        $this->merchantId = $into_data['sub_mchid'];
        $checker_id = $cate_info['checker_id'];
        $remark = $this->get_remark();
        $data = array(
            'merchant_id' => $cate_info['merchant_id'],
            'customer_id' =>  '',
            'buyers_account' => '',
            'phone_info' => '',
            'wx_remark' => '',
            'wz_remark' => '',
            'new_order_sn' => '',
            'no_number' => '',
            'transId' => '',
            'la_ka_la' => 0,
            'add_time' => time(),
            'paytime' => time(),
            'bill_date' => date('Ymd'),
            'checker_id' => $checker_id,
            'paystyle_id' => 2,
            'price' => $price,
            'remark' => $remark,
            'status' => 0,
            'cate_id' => $cate_info['id'],
            'mode' => 0,
            'bank' => 13,
            'cost_rate' => $into_data['cost_rate'],
            'subject' => $cate_info['jianchen'],
            'remark_mer' => '',
        );
        $data['jmt_remark'] = I('memo','')?:I("jmt_remark",'');
//        $frontEndUrl =$this->return_url."/price/{$price}/openid/{$sub_openid}/remark/{$remark}/mid/".$cate_info['merchant_id'];
        
        $db_res = $this->pay_model->add($data);
        if($db_res){
            $this->fileName = 'ali_js_pay.log';
            $post_data['version'] = $this->version; // 版本号 固定1.0.0
            $post_data['charset'] = $this->charset; // 字符编码 UTF-8
            $post_data['transType'] = '001';    // 交易类型 001
            $post_data['transCode'] = '0071';   // 交易代码 0071
            $post_data['bizType'] = '000001';   // 业务类型 000001
            $post_data['merchantId'] = $this->merchantId;  // 平安付分配给旺小宝 子商户号
            $post_data['platMerchantId'] = $this->platMerchantId;  // 平安付分配给旺小宝的商户号
            $post_data['backEndUrl'] = $this->notify_url;   // 异步通知地址
            $post_data['frontEndUrl'] = $this->frontEndUrl; // 支付跳转地址
            $post_data['orderTime'] = date('YmdHis');    // 交易开始日期时间
            $post_data['sameOrderFlag'] = 'N';    // Y 重复订单  N 非重复
            $post_data['mercOrderNo'] = $data['remark'];    // 商户订单/流水号
            $post_data['orderAmount'] =$price * 100;  // 交易金额 单位分
            $post_data['orderCurrency'] = $this->orderCurrency; // 交易币种 CNY
//            $post_data['riskInfo'] = $this->getriskinfo(); // 风控信息
            $post_data['merReserved'] = $this->getalimerReserved(); // 商户保留域
            $post_data['signature'] = $this->get_sign($post_data,$this->platMerchantId_key);   //签名
            $post_data['signMethod'] = $this->signMethod; // 加密方式 SHA-256

            $this->writeLog('ali_js_pay.log','参数',$post_data);
            $this->assign('data',$post_data);
            $this->assign('url',$this->url);
            $this->display('wx_pay');
        } else {
            $this->alert_err();
        }
    }

    public function getriskinfo()
    {
        $arr = array(
            'DEV_IP' => $_SERVER['REMOTE_ADDR'],
        );
        return json_encode($arr);
    }

    public function getwxmerReserved()
    {
        $arr = array(
            'transparentInfo' => array(
                'cashierType' =>'W-100014_AGG01',
                'topDisplay' =>'N',
            ),
//            'TC' => array(
//                'fncp_wechat_appId' =>'wx25599367a4bd6f39',
//                'fncp_alipay_appId' =>'',
//                'fncp_wechat_openId' =>'9771C4A5C3353AA89DC69AD45BDCE373F36205848744993EA6F1A30703B0A066',
//            ),
            'ts_fncp_convergePay' => array(
                'description' =>"支付金额{$this->price}元",
            ),
        );

        return json_encode($arr);
    }

    public function getalimerReserved()
    {
        $arr = array(
            "transparentInfo" => array(
                "cashierType" => "W-100014_AGG01",
                'topDisplay' =>'N',
            ),
//            "TC" => array(
//                "fncp_wechat_appId" => "",
//                "fncp_alipay_appId" => "zfb25599367a4bd6f39",
//                "fncp_alipay_buyerId" => "9771C4A5C3353AA89DC69AD45BDCE373F36205848744993EA6F1A30703B0A066"
//            ),
            'ts_fncp_convergePay' => array(
                'description' =>"{$this->price}",
            ),
        );

        return json_encode($arr);
    }

    public function tqu()
    {
        $this->query('20180102104144962435');
    }

    public function query($remark)
    {
        $this->fileName = 'query.log';
        $post_data['version'] = $this->version; // 版本号 固定1.0.0
        $post_data['charset'] = $this->charset; // 字符编码 UTF-8
        $post_data['transType'] = '005';    // 交易类型 001
        $post_data['merchantId'] = $this->merchantId;  // 平安付分配给旺小宝 子商户号
        $post_data['platMerchantId'] = $this->platMerchantId;  // 平安付分配给旺小宝的商户号
        $post_data['mercOrderNo'] = $remark;  // 平安付分配给旺小宝的商户号
        $post_data['signature'] = $this->get_sign($post_data,$this->platMerchantId_key);   //签名
        $post_data['signMethod'] = $this->signMethod; // 加密方式 SHA-256
        $res = $this->requestPost($this->url, http_build_query($post_data));
        parse_str($res,$res_arr);
        $this->writeLog('query.log', '查询结果',$res_arr);
        if($res_arr['respCode'] == '0000'){
            if($res_arr['orderStatus'] == '00'){
                #  支付成功
                $data['status'] = '1';
                $data['paytime'] = time();
                $res = $this->pay_model->where(array('remark' => $remark))->save($data);
                if($res){
                    return array("code" => "success", "msg" => "成功");
                } else {
                    return array('code' => 'error', 'msg' => '失败');
                }
            } else {
                # 其他状态
                return array('code' => 'error', 'msg' => '失败');
            }
        }
    }

    public function pay_back($remark, $price)
    {
        // 查找交易记录表获取相关信息
        $pay = $this->pay_model->where("remark='$remark' And status = 1")->find();
        // 获取子商户号
        $this->merchantId = M('merchants_pingan')
            ->where(array('mid' => $pay['merchant_id']))
            ->getField('sub_mchid');
        $this->fileName = 'pay_back.log';
        $post_data['version'] = $this->version; // 版本号 固定1.0.0
        $post_data['charset'] = $this->charset; // 字符编码 UTF-8
        $post_data['transType'] = '002';    // 交易类型
        $post_data['bizType'] = '000003';   // 业务类型
        $post_data['merchantId'] = $this->merchantId;  // 平安付分配给旺小宝 子商户号
        $post_data['platMerchantId'] = $this->platMerchantId;  // 平安付分配给旺小宝的商户号
        $post_data['mercOrderNo'] = $this->get_remark();  // 新流水号，非元交易单号
        $post_data['origMercOrderNo'] = $remark;  // 原商户订单号
        $post_data['orderAmount'] = $price*100;  // 退款金额
        $post_data['orderCurrency'] = $this->orderCurrency;  // 交易币种
        $post_data['signature'] = $this->get_sign($post_data,$this->platMerchantId_key);   //签名
        $post_data['signMethod'] = $this->signMethod; // 加密方式 SHA-256
        $this->writeLog('pay_back.log','参数',($post_data));
        $res = $this->requestPost($this->url, http_build_query($post_data));
        parse_str($res,$res_arr);
        $this->writeLog('pay_back.log', '退款结果',$res_arr);
        if($res_arr['respCode'] == '0000'){
            $data['status'] = '2';
            $data['back_status'] = '1';
            $data['price_back'] = $price;
            $res = $this->pay_model->where(array('remark' => $remark))->save($data);
            if($res){
                return array("code" => "success", "msg" => "成功", "data" => "退款成功");
            } else {
                return array('code' => 'error', 'msg' => '退款失败');
            }
        } else {
            return array('code' => 'error', 'msg' => '退款失败');
        }
    }

    public function notify()
    {
        $this->fileName = 'notify.log';
        $notify_data = I('post.');
        $sign = $notify_data['signature'];
        unset($notify_data['signature']);
        unset($notify_data['signMethod']);
        $new_sign = $this->get_sign($notify_data,$this->platMerchantId_key);
        if($sign == $new_sign){
            $order_sn = $notify_data['mercOrderNo'];
            $transId = $notify_data['orderTraceNo'];
            $orderData = $this->pay_model->where(array('remark' => $order_sn))->find();
            if($notify_data['notifyType'] == '00'){
                if ($orderData['status'] == 0) {
                    $save['transId'] = $transId;
                    $save['paytime'] = time();
                    $save['status'] = 1;
                    $this->pay_model->where(array('id'=>$orderData['id']))->save($save);
                    $this->writeLog('notify.log', '支付成功',$notify_data);
                    $post_data = $this->notify_success();
                    echo $post_data;
                    A("App/PushMsg")->push_pay_message($order_sn);
                } else if($orderData['status'] == 1){
                    $this->writeLog('notify.log', '返回_二次',$notify_data);
                    $post_data = $this->notify_success();
                    echo $post_data;
                } else {
                    $this->writeLog('notify.log', '订单状态',$notify_data);
                    $post_data = $this->notify_success();
                    echo $post_data;
                }
            } elseif($notify_data['notifyType'] == '01') {
                $this->writeLog('notify.log', '订单创建成功',$notify_data);
                $post_data = $this->notify_success();
                echo $post_data;
            } else{
                $this->writeLog('notify.log', '其他状态',$notify_data);
                $post_data = $this->notify_success();
                echo $post_data;
            }
        } else {
            $this->writeLog('notify.log', '签名错误' , $_POST);
            $post_data = $this->notify_success();
            echo $post_data;
        }
    }

    public function notify_success()
    {
        $post_data['version'] = $this->version; // 版本号 固定1.0.0
        $post_data['charset'] = $this->charset; // 字符编码 UTF-8
        $post_data['successLable'] = 'S'; // 字符编码 UTF-8
        $post_data['signature'] = $this->get_sign($post_data,$this->platMerchantId_key);   //签名
        $post_data['signMethod'] = $this->signMethod; // 加密方式 SHA-256

        return http_build_query($post_data);
    }

    public function get_sign($post_data,$key)
    {
        ksort($post_data);
        $str = '';
        foreach ($post_data as $k=>$v) {
            $str .= $k.'='.$v . '&';
        }
        $str1 = substr($str,0,-1);
        $sign_str = $str1.$key;
        if($this->fileName <> 'notify.log'){
            $this->writeLog($this->fileName,'签名参数',$sign_str,0);
        }
        return hash('sha256',$sign_str);
    }

    public function get_remark()
    {
        return date('YmdHis').rand(100000,999999);
    }

//    # 发送请求
//    private function requestPost($url, $data, $second = 30)
//    {
//        $header = array("Content-type:application/x-www-form-urlencoded");
//        //初始化curl
//        $curl = curl_init();
//        //设置超时
//        curl_setopt($curl, CURLOPT_TIMEOUT, $second);
//        curl_setopt($curl, CURLOPT_URL, $url);
//        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
//        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
//        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
//        //post提交方式
//        curl_setopt($curl, CURLOPT_POST, TRUE);
//        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
////        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
//        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
//        //运行curl
//        $res = curl_exec($curl);
//        //返回结果
//        if ($res) {
//            curl_close($curl);
//            return $res;
//        } else {
//            $error = curl_errno($curl);
//            $this->writeLog('request.log', 'ERROR：' , "curl出错，错误码:$error--$res",0);
//            curl_close($curl);
//            return false;
//        }
//    }

    private function alert_err($msg = '网络异常，请重试！')
    {
        $this->assign('err_msg',"$msg");
        $this->display(":Barcodexybank/error");
        exit;
    }

    # 获取微信openID
    private function get_openid()
    {
        // 获取配置项
        $config = C('WEIXINPAY_CONFIG');
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
            $result = $this->curl_get_contents($url);
            $result = json_decode($result, true);
            return $result['openid'];

        }
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
            $this->writeLog('post.log', 'curl出错',"错误码:$error",0);
//            echo "<a href='http://curl.haxx.se/libcurl/c/libcurl-errors.html'>错误原因查询</a></br>";
            curl_close($curl);
            return false;
        }
    }

    private function curl_get_contents($url)
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

    private function writeLog($file_name, $title, $param, $json=true)
    {
        $path = $this->get_date_dir();
        if($json){
            $param = json_encode($param);
        }
        file_put_contents($path . $file_name, date("-d H:i:s") . $title.':'. $param . PHP_EOL . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    private function get_date_dir($path = '/data/log/pinganbank/')
    {
        $Y = $_SERVER['DOCUMENT_ROOT'] . $path . date("Y-m");
        $d = $Y . '/' . date("m-d");
        if (!file_exists($Y)) mkdir($Y, 0777, true);
        if (!file_exists($d)) mkdir($d, 0777, true);

        return $d . '/';
    }

}