<?php

namespace Pay\Controller;

use Common\Controller\HomebaseController;

/**支付
 * Class BarcodeController
 * @package Pay\Controller
 */
class BarcodembankController extends HomebaseController
{

    private $pay_model;
    /**
     * 支付成功展示页面
     */
    function _initialize()
    {
        parent::_initialize();
        $this->pay_model = M('pay');
        //$this->url = "https://aop.koolyun.com:443/apmp/rest/v2";
        $this->url = "http://aop.koolyun.cn:8080/apmp/rest/v2";
        $this->apikey = "YPT17002";
        $this->notify = "http://sy.youngport.com.cn/notify/msbank.php";
        $this->private_key = '-----BEGIN RSA PRIVATE KEY-----
MIICXQIBAAKBgQCbexvFt/rOGUOVDPbT99wWt3ChnmcqRc+lmJkEDHP98c8rd+Ih
V34VfjeA2+bhaJ66ZlN+sxJG871GIA6X9o7MOFjFsdAkXYAK+EyHiRZx4drhoaiM
LqxP+ygH3BlvvEEHUUT+ZW0lg2wgcRrzcUDHKZ0u112cQkZgo+Skivm6QQIDAQAB
AoGAS2g8wvsE9/pGzb5Y49sdciMLzEbQEC+FkvHcnJsRkoM5kAJ3uOX/L5tkfemp
I3+jJBJGwndFEQZbsOwRR+B7xoywgJ5+dlyneXEoNfbOJ4J3tP/IVoIDHr2ax8uW
3/IizcgcL8Wc6AyryaQfFb9nEBMUdTt3k3VUEZC4Ef/xccECQQDJ0dj5e3vYbS7F
yIsNlv5HBVzSK++qbxmefT0ZTrvgYPp/g+tFhY8blzOxhbJj3Cp+FxPqL9GOLg1P
hVNMYYj5AkEAxTian96ke9hQY5FjJ/e6q1fe8KzQG79/aC4q4j7rS5Z35kSuDA/Y
Pko47ta2AI5otCdQVXsvNBhFHaO3FKMViQJBAJcNK+NWS9Qpq9c2iPTL7VcEqXtY
jRG4A6m+vKsjZbTDgNlNyBqJoxmYaoVUtrbNAzTKWwptbd+HkkjRVg4V9ikCQQCX
KFkqqwQ6f4KtraLn4TFLXh/bKzid69oEyU3I9hx1ZLAk5wLW79X3d//G3v3D02Jg
obkqqy10qh1fKDmMMaqxAkB+h+DHSA3k4AmRtuKA+fQ9PoLRSbGqYiKEmGLaZvuE
WBDdsn6coSK8qlh4Jxv9dquCaymS9Y+lGzBh2o4n0jOF
-----END RSA PRIVATE KEY-----';
    }

    public function err()
    {
        $this->display("error");
    }
    //微信支付界面跳转
    public function qr_weixipay()
    {
//        这里直接获得openid;
//        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
        $id = I("id");
        $merchant = M("merchants_cate")->where("merchant_id=$id")->find();
        $openid = 'oyaFdwCeMYLJd7r8WRrXIBqKSGWI';
        $this->getOffer($merchant, $openid);
//            $this->assign('discount', $this->discount);
//            $this->assign('credits', $this->credits);
//            $this->assign('credits_use', $this->credits_use);
//            $this->assign('credits_discount', $this->credits_discount);
//            $this->assign('yue', $this->yue);
//            $this->assign('flag', $this->flag);
//            $this->assign('card_code', $this->card_code);
        $this->assign('openid', $openid);
        $this->assign("merchant", $merchant);
        $this->assign('seller_id', $id);
//        }
        $this->display();
    }

    public function getOffer($merchant, $openid)
    {
        $merchant_id = $merchant['merchant_id'];
        $uid = M('merchants')->where(array('id' => $merchant_id))->getField('uid');
        // 获取联名卡数据
        $agent_data = M('screen_memcard')
            ->field('c.*')
            ->join('c left join ypt_merchants_users u on c.mid=u.agent_id')
            ->where(array('u.id' => $uid))
            ->find();
        //判断联名卡参数
        if (empty($agent_data)) {
            $this->agent_discount = '';
            $this->agent_credits = '';
            $this->agent_credits_discount = '';
        } else {
            # 参与代理异业联盟的商户
            $use_merchants = M("screen_cardset")->where(array('c_id' => $agent_data['id']))->getField("use_merchants");
            # 判断该商户是否参与代理的异业联盟
            $inarray = in_array($uid,explode(',',$use_merchants));
            $this->agent_card_code = M('screen_memcard_use')->where(array('memcard_id' => $agent_data['id'], 'fromname' => $openid))->getField("card_code");
            $agent_card_id = $agent_data['id'];
            $agent_mem_info = M('screen_memcard_use')->where(array('fromname' => $openid, 'memcard_id' => $agent_card_id,'status'=>1))->find();
            # 是否有该用户的会员信息
            if (empty($agent_mem_info) || !$inarray) {
                $this->agent_discount = '';
                $this->agent_credits = 0;
                $this->agent_credits_use = 0;
                $this->agent_credits_discount = 0;
                $this->agent_yue = '';
            } else {
                $this->agent_discount = 10;
                $this->agent_credits = 0;
                $this->agent_credits_use = 0;
                $this->agent_credits_discount = 0;
                $this->agent_yue = 0;
                $this->agent_yue = (float)$agent_mem_info['yue'];
                # 是否开启打折优惠
                if ($agent_data['discount_set'] == 1) {
                    if ($agent_data['level_set'] == 1) {
                        $agent_user_integral = $agent_mem_info['card_amount'];
                        $agent_discount_data = M('screen_memcard_level')->where(array('c_id' => $agent_card_id))->select();
                        for ($i = 0; $i < count($agent_discount_data); $i++) {
                            if ($agent_user_integral >= $agent_discount_data[$i]['level_integral']) {
                                $this->agent_discount = $agent_discount_data[$i]['level_discount'];
                            }
                        }
                    } else {
                        $this->agent_discount = $agent_data['discount'];
                    }
                    if ($this->agent_discount == 0) {
                        $this->agent_discount = 10;
                    }
                }
                # 是否开启积分抵扣金额优惠
                if ($agent_data['integral_dikou'] == 1) {
                    $agent_max = $agent_data['max_reduce_bonus'];
                    $agent_have = $agent_mem_info['card_balance'];
                    $this->agent_credits_use = $agent_data['credits_use'];
                    if ($agent_have >= $agent_max) {
                        $this->agent_credits = $agent_max;
                    } else {
                        $this->agent_credits = $agent_have;
                    }
                    $this->agent_credits_discount = $agent_data['credits_discount'];
                }
                $this->agent_card_data = $agent_data;
                $this->agent_mem_info = $agent_mem_info;
            }
        }

        // 获取会员卡数据
        $card_data = M('screen_memcard')
            ->field('c.*')
            ->join('c left join ypt_merchants m on m.uid=c.mid')
            ->where(array('m.id' => $merchant_id))
            ->find();
        // 判断会员卡的参数
        if (empty($card_data)) {
            $this->discount = '';   // 折扣
            $this->credits = '';    // 本次可用积分
            $this->credits_discount = '';//积分可抵扣的金额
        } else {
            $this->card_code = M('screen_memcard_use')->where(array('memcard_id' => $card_data['id'], 'fromname' => $openid))->getField("card_code");
            $card_id = $card_data['id'];
            $mem_info = M('screen_memcard_use')->where(array('fromname' => $openid, 'memcard_id' => $card_id,'status'=>1))->find();
            # 是否有改用户会员信息
            if (empty($mem_info)) {
                $this->discount = '';
                $this->credits = 0;
                $this->credits_use = 0;
                $this->credits_discount = 0;
                $this->yue = '';
            } else {
                $this->discount = 10;
                $this->credits = 0;
                $this->credits_use = 0;
                $this->credits_discount = 0;
                $this->yue = 0;
                $this->yue = (float)$mem_info['yue'];
                # 是否开启打折优惠
                if ($card_data['discount_set'] == 1) {
                    if ($card_data['level_set'] == 1) {
                        $user_integral = $mem_info['card_amount'];
                        $discount_data = M('screen_memcard_level')->where(array('c_id' => $card_id))->select();
                        for ($i = 0; $i < count($discount_data); $i++) {
                            if ($user_integral >= $discount_data[$i]['level_integral']) {
                                $this->discount = $discount_data[$i]['level_discount'];
                            }
                        }
                    } else {
                        $this->discount = $card_data['discount'];
                    }
                    if ($this->discount == 0) {
                        $this->discount = 10;
                    }
                }
                # 是否开启使用积分抵扣优惠
                if ($card_data['integral_dikou'] == 1) {
                    $max = $card_data['max_reduce_bonus'];
                    $have = $mem_info['card_balance'];
                    $this->credits_use = $card_data['credits_use']; // 使用多少积分抵扣的金额
                    if ($have >= $max) {
                        $this->credits = $max;
                    } else {
                        if ($have < 0) $have = 0;
                        $this->credits = $have;
                    }
                    $this->credits_discount = $card_data['credits_discount'];
                }
                $this->card_data = $card_data;
                $this->mem_info = $mem_info;
            }
        }
        // 获取用户优惠券
        $coupon_data = M('screen_user_coupons')
            ->field('c.usercard,s.total_price,s.de_price')
            ->join('c left join ypt_screen_coupons s on c.coupon_id=s.id')
            ->where(array('c.fromname' => $openid, 's.mid' => $merchant_id, 'c.status' => '1', 's.begin_timestamp' => array('LT', time()), 's.end_timestamp' => array('GT', time())))
            ->order('de_price')
            ->select();
        $this->coupon_data = $coupon_data;
        // 判断是否存在优惠
        if (empty($this->discount) && empty($this->agent_discount) && empty($this->credits) && empty($this->agent_credits) && empty($this->coupon_data) && empty($this->agent_yue)) {
            $this->flag = 0;
        } else {
            $this->flag = 1;
        }
    }

    private function rsaSign($data, $private_key)
    {
        //以下为了初始化私钥，保证在您填写私钥时不管是带格式还是不带格式都可以通过验证。
        $res = openssl_get_privatekey($private_key);

        if ($res) {
            openssl_sign($data, $sign, $res);
        } else {
            echo "您的私钥格式不正确!" . "<br/>" . "The format of your private_key is incorrect!";
            exit();
        }
        openssl_free_key($res);
        $sign = strtoupper(bin2hex($sign));
        return $sign;
    }

    //http 请求
    private function httpRequst($url, $data, $res, $appkey)
    {
        $post_data = 'params=' . $data;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 4);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type:application/x-www-form-Urlencoded;charset=utf-8",
            "Accept-Language:zh-cn",
            "x-apsignature:" . $res,
            "x-appkey:" . $appkey
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);

        curl_setopt($ch, CURLOPT_HEADER, TRUE);
        curl_setopt($ch, CURLOPT_TIMEOUT, 180);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $output = curl_exec($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $response_header = substr($output, 0, $header_size);
        $response_body = substr($output, $header_size);
        curl_close($ch);
        $response_body = trim($response_body, '[');
        $response_body = trim($response_body, ']');

        $response_body = json_decode($response_body, 1);

        $response_header_arr = array();
        $response_header_arr = explode(': ', $response_header);
        if ((json_last_error() != JSON_ERROR_NONE) or empty($response_header_arr)) {
            throw new QrcodePayException("Analyze return json error.");
        }
        $response_header_return = array();
        if (!empty($response_header_arr[4])) {
            $response_header_return['x_apsignature'] = str_replace(array("\r\n", "\r", "\n", "Content-Type"), "", $response_header_arr[4]);
        }
        return json_encode(array('header' => $response_header_return, 'body' => $response_body));
    }

    private function C_b_pay($param)
    {
        if (isset($param['pay_type']) && !empty($param['pay_type'])) {
            $pay_type = $param['pay_type'];
            if ($pay_type == 1) {
                $acquirerType = 'wechat';
            } elseif ($pay_type == 2) {
                $acquirerType = 'alipay';
            } elseif ($pay_type == 3) {
                $acquirerType = 'qq';
            }
            $custId = $param['custId'];
        } else {
            $reslut['responseCode'] = "1112";
            $reslut['resultMsg'] = "pay_type不能为空";
            return json_encode($reslut);
        }
        $data['action'] = 'wallet/trans/csbSale';
        $data['version'] = '2.0';
        $data['reqTime'] = date("YmdHis");
        $data['orderId'] = $param['order_id'];
        $data['reqId'] = date("YmdHis") . rand(1000, 9999);
        $data['deviceId'] = 'payuser';//终端号
        $data['transTimeOut'] = '1440';
        $data['orderSubject'] = $param['orderSubject'];//订单抬头
        $data['orderDesc'] = $param['orderDesc'];//订单描述
        //$data['totalAmount']=$totalAmount*100;//交易金额
        $data['totalAmount'] = $param['totalAmount'] * 100;
        $data['bankCardLimit'] = 2;//银行卡限定类型，1 借记卡，2 借记卡和贷记卡，默认为 2
        $data['currency'] = "CNY";
        $data['notifyUrl'] = $this->notify;
        $data['acquirerType'] = $acquirerType;
        $data['operatorId'] = "POS 操作员";
        $data['custId'] = $custId;
        $data = json_encode($data);
        $data = "[" . $data . "]";
        $res = $this->rsaSign($data, $this->private_key);
        $result = $this->httpRequst($this->url, $data, $res, $this->apikey);
        $QrcodeArr = json_decode($result, true);
        $QrcodeArr = $QrcodeArr['body'];
        $url = $QrcodeArr['qrCode'];
        redirect($url);
        //header($url);
    }

    private function B_c_pay($list)
    {
        if (isset($list['walletAuthCode']) && !empty($list['walletAuthCode'])) {
            $walletAuthCode = $list['walletAuthCode'];
        } else {
            $reslut['responseCode'] = "1115";
            $reslut['resultMsg'] = "付款码不能为空";
            return json_encode($reslut);
        }
        if (isset($list['pay_type']) && !empty($list['pay_type'])) {
            $pay_type = $list['pay_type'];
            $custId = $list['custId'];
            if ($pay_type == 1) {
                $acquirerType = 'wechat';
            } elseif ($pay_type == 2) {
                $acquirerType = 'alipay';
            } elseif ($pay_type == 3) {
                $acquirerType = 'qq';
            }
        } else {
            $reslut['responseCode'] = "1112";
            $reslut['resultMsg'] = "pay_type不能为空";
            return json_encode($reslut);
        }
        $data['action'] = 'wallet/trans/bscSale';
        $data['version'] = '2.0';
        $data['reqTime'] = date("YmdHis");
        $data['orderId'] = $list['order_sn'];
        $data['reqId'] = date("YmdHis") . rand(1000, 9999);
        $data['deviceId'] = 'payuser';//终端号
        $data['transTimeOut'] = '1440';
        $data['orderSubject'] = '洋仆淘商城订单';//订单抬头
        $data['orderDesc'] = $list['orderDesc'];//订单描述
        $data['totalAmount'] = $list['totalAmount'] * 100;
        $data['bankCardLimit'] = 2;//银行卡限定类型，1 借记卡，2 借记卡和贷记卡，默认为 2
        $data['currency'] = "CNY";
        $data['walletAuthCode'] = $list['walletAuthCode'];//钱包付款吗
        $data['acquirerType'] = $acquirerType;
        $data['operatorId'] = "POS 操作员";
        $data['custId'] = $custId;
        $data = json_encode($data);
        $data = "[" . $data . "]";
        $res = $this->rsaSign($data, $this->private_key);
        $result = $this->httpRequst($this->url, $data, $res, $this->apikey);
        $fileName = "./data/log/msbank/shuaka/" . date("Y-m-d", time()) . ".logs";
        if (!file_exists($fileName)) {
            @fopen($fileName, "w");
        }
        file_put_contents($fileName, date("Y-m-d H:i:s", time()) . "--" . $result . "--" . $data . "\r\n", FILE_APPEND | LOCK_EX);
        return $result;
    }

    public function js_pay($param)
    {
        if (!isset($param['pay_type']) && empty($param['pay_type'])) {
            $reslut['responseCode'] = "1111";
            $reslut['resultMsg'] = "pay_type不能为空";
            return json_encode($reslut);
        }
        $pay_type = $param['pay_type'];
        if (!isset($param['order_id']) && empty($param['order_id'])) {
            $reslut['responseCode'] = "1111";
            $reslut['resultMsg'] = "pay_type不能为空";
            return json_encode($reslut);
        }
        $order_sn = $param['order_id'];
        if (!isset($param['openid']) && empty($param['openid'])) {
            $reslut['responseCode'] = "1111";
            $reslut['resultMsg'] = "pay_type不能为空";
            return json_encode($reslut);
        }
        $uuid = $param['openid'];
        if (!isset($param['totalAmount']) && empty($param['totalAmount'])) {
            $reslut['responseCode'] = "1111";
            $reslut['resultMsg'] = "totalAmount不能为空";
            return json_encode($reslut);
        }
        $totalAmount = $param['totalAmount'];
        $uuid = $param['openid'];
        if ($pay_type == 1) {
            $acquirerType = 'wechat';
            $custId = $param['custId'];
        } elseif ($pay_type == 2) {
            $acquirerType = 'alipay';
            $custId = $param['custId'];
        }
        $data['action'] = 'wallet/trans/jsSale';
        $data['version'] = '2.0';
        $data['reqTime'] = date("YmdHis");
        $data['appId'] = 'wx3fa82ee7deaa4a21';
        $data['uuid'] = $uuid;
        $data['orderId'] = $order_sn;
        $data['reqId'] = date("YmdHis") . rand(1000, 9999);
        $data['deviceId'] = 'payuser';//终端号
        $data['transTimeOut'] = '1440';
        $data['orderSubject'] = $param['orderSubject'];//订单抬头
        $data['orderDesc'] = $param['orderDesc'];//订单描述
        $data['totalAmount'] = $param['totalAmount'] * 100;//交易金额
        $data['bankCardLimit'] = 2;//银行卡限定类型，1 借记卡，2 借记卡和贷记卡，默认为 2
        $data['currency'] = "CNY";
        $data['notifyUrl'] = $this->notify;
        $data['acquirerType'] = $acquirerType;
        $data['operatorId'] = "POS 操作员";
        $data['custId'] = $custId;
        $data = json_encode($data);
        $data = "[" . $data . "]";
        $res = $this->rsaSign($data, $this->private_key);
        $result = $this->httpRequst($this->url, $data, $res, $this->apikey);
        $fileName = "./data/log/msbank/shuaka/" . date("Y-m-d", time()) . ".logs";
        if (!file_exists($fileName)) {
            @fopen($fileName, "w");
        }
        file_put_contents($fileName, date("Y-m-d H:i:s", time()) . "--" . $result . "--" . $data . "\r\n", FILE_APPEND | LOCK_EX);
        return $result;
    }

    public function js_pay_member($param)
    {
        if (!isset($param['pay_type']) && empty($param['pay_type'])) {
            $reslut['responseCode'] = "1111";
            $reslut['resultMsg'] = "pay_type不能为空";
            return json_encode($reslut);
        }
        $pay_type = $param['pay_type'];
        if (!isset($param['order_id']) && empty($param['order_id'])) {
            $reslut['responseCode'] = "1111";
            $reslut['resultMsg'] = "pay_type不能为空";
            return json_encode($reslut);
        }
        $order_sn = $param['order_id'];
        if (!isset($param['openid']) && empty($param['openid'])) {
            $reslut['responseCode'] = "1111";
            $reslut['resultMsg'] = "pay_type不能为空";
            return json_encode($reslut);
        }
        $uuid = $param['openid'];
        if (!isset($param['totalAmount']) && empty($param['totalAmount'])) {
            $reslut['responseCode'] = "1111";
            $reslut['resultMsg'] = "totalAmount不能为空";
            return json_encode($reslut);
        }
        $totalAmount = $param['totalAmount'];
        $uuid = $param['openid'];
        if ($pay_type == 1) {
            $acquirerType = 'wechat';
            $custId = $param['custId'];
        } elseif ($pay_type == 2) {
            $acquirerType = 'alipay';
            $custId = $param['custId'];
        }
        $data['action'] = 'wallet/trans/jsSale';
        $data['version'] = '2.0';
        $data['reqTime'] = date("YmdHis");
        $data['appId'] = 'wx3fa82ee7deaa4a21';
        $data['uuid'] = $uuid;
        $data['orderId'] = $order_sn;
        $data['reqId'] = date("YmdHis") . rand(1000, 9999);
        $data['deviceId'] = 'payuser';//终端号
        $data['transTimeOut'] = '1440';
        $data['orderSubject'] = $param['orderSubject'];//订单抬头
        $data['orderDesc'] = $param['orderDesc'];//订单描述
        $data['totalAmount'] = $param['totalAmount'] * 100;//交易金额
        $data['bankCardLimit'] = 2;//银行卡限定类型，1 借记卡，2 借记卡和贷记卡，默认为 2
        $data['currency'] = "CNY";
        $data['notifyUrl'] = "http://sy.youngport.com.cn/notify/msbank_member.php";
        $data['acquirerType'] = $acquirerType;
        $data['operatorId'] = "POS 操作员";
        $data['custId'] = $custId;
        $data = json_encode($data);
        $data = "[" . $data . "]";
        $res = $this->rsaSign($data, $this->private_key);
        $result = $this->httpRequst($this->url, $data, $res, $this->apikey);
        $fileName = "./data/log/msbank/shuaka/" . date("Y-m-d", time()) . ".logs";
        if (!file_exists($fileName)) {
            @fopen($fileName, "w");
        }
        file_put_contents($fileName, date("Y-m-d H:i:s", time()) . "--" . $result . "--" . $data . "\r\n", FILE_APPEND | LOCK_EX);
        return $result;
    }

    private function alifun($list)
    {
        $data['action'] = 'query/trans/detail';
        $data['version'] = '2.0';
        $data['reqTime'] = date("YmdHis");
        $data['orderId'] = $list['orderId'];
        $data['transId'] = $list['orgTransId'];
        $data['custId'] = $list['custId'];
        $data['reqId'] = $list['orgReqId'];
        $data = json_encode($data);
        $data = "[" . $data . "]";
        $res = $this->rsaSign($data, $this->private_key);
        $result = $this->httpRequst($this->url, $data, $res, $this->apikey);
        return $result;
    }

    private function check($list)
    {
        $data['action'] = 'wallet/trans/saleVoid';
        $data['version'] = '2.0';
        $data['reqTime'] = date("YmdHis");
        $data['reqId'] = date("YmdHis") . rand(1000, 9999);
        $data['orderId'] = $list['orderId'];
        $data['orgTransId'] = $list['orgTransId'];
        $data['custId'] = $list['custId'];
        $data['orgReqId'] = $list['orgReqId'];
        $data['deviceId'] = 'payuser';
        $data['operatorId'] = "POS 操作员";
        $data = json_encode($data);
        $data = "[" . $data . "]";
        $res = $this->rsaSign($data, $this->private_key);
        $result = $this->httpRequst($this->url, $data, $res, $this->apikey);
        return $result;
    }

    private function refund($order_id)
    {
        if (isset($order_id) && !empty($order_id)) {
            $order_id = $order_id['order_id'];
            $orderData = M('')->query("select * from ypt_pay where remark='$order_id'");
            if ($orderData) {
                $merchant_id = $orderData['merchant_id'];
                $cate_id = $orderData[0]['cate_id'];
                $cateData = M('')->query("select * from ypt_merchants_cate where id= '$cate_id'");
                $pay_type = $orderData[0]['paystyle_id'];
                if ($pay_type == 1) {
                    $acquirerType = 'wechat';
                    $custId = $cateData[0]['wx_mchid'];
                } elseif ($pay_type == 2) {
                    $acquirerType = 'alipay';
                    $custId = $cateData[0]['alipay_partner'];
                }
                $order_sn = $order_id;
                $orgTransId = $orderData[0]['transId'];
                $totalAmount = $orderData[0]['price'];
            } else {
                $reslut['responseCode'] = "1114";
                $reslut['errorMsg'] = "订单号不存在";
                return json_encode($reslut);
            }
        } else {
            $reslut['responseCode'] = "1113";
            $reslut['errorMsg'] = "order_id不能为空";
            return json_encode($reslut);
        }
        $data['action'] = 'wallet/trans/refund';
        $data['version'] = '2.0';
        $data['reqTime'] = date("YmdHis");
        $data['orderId'] = $order_id;
        $data['refundOrderId'] = date("YmdHis") . rand(1000, 9999);
        $data['reqId'] = date("YmdHis") . rand(1000, 9999);
        $data['deviceId'] = 'payuser';//终端号
        $data['totalAmount'] = $totalAmount * 100;
        $data['operatorId'] = "POS 操作员";
        $data['custId'] = $custId;
        $data['orgReqId'] = $orgTransId;
        $data['orgTransId'] = $orgTransId;
        $data = json_encode($data);
        $data = "[" . $data . "]";
        $fileName = "./data/log/msbank/tuikuan/" . date("Y-m-d", time()) . ".logs";
        if (!file_exists($fileName)) {
            @fopen($fileName, "w");
        }
        file_put_contents($fileName, date("Y-m-d H:i:s", time()) . '--报文--' . $data . "--" . "\r\n", FILE_APPEND | LOCK_EX);
        $res = $this->rsaSign($data, $this->private_key);
        $result = $this->httpRequst($this->url, $data, $res, $this->apikey);
        $result = json_decode($result, true);
        $sql = "update ypt_pay set back_status=1,price_back='$totalAmount',status=2 where remark='$order_sn'";
        if ($result['body']['responseCode'] == '00') {
            M('')->query($sql);
            file_put_contents($fileName, date("Y-m-d H:i:s", time()) . '--成功--' . json_encode($result) . "--" . $sql . "\r\n", FILE_APPEND | LOCK_EX);
            return json_encode(array("code" => "success", "msg" => "成功", "data" => "退款成功"));
        } else {
            file_put_contents($fileName, date("Y-m-d H:i:s", time()) . '--失败--' . json_encode($result) . "--" . $sql . "\r\n", FILE_APPEND | LOCK_EX);
            return json_encode(array("code" => "error", "msg" => "失败", "data" => $result['body']['errorMsg']));
        }
    }

    //回调地址
    public function notify()
    {
        $fileName = "/alidata/www/youngshop/data/log/msbank/notify/" . date("Y-m-d", time()) . ".logs";
        if (!file_exists($fileName)) {
            @fopen($fileName, "w");
        }
        file_put_contents($fileName, date("Y-m-d H:i:s", time()) . "--" . $_POST['body'] . "--" . "\r\n", FILE_APPEND | LOCK_EX);
        $str = stripslashes($_POST['body']);
        if ($_POST) {
            $data = json_decode($str, true);
            $order_sn = $data['orderId'];
            $transId = $data['transId'];
            $orderData = M('')->query("select * from ypt_pay where remark='$order_sn'");
            if ($orderData[0]['status'] == 0) {
                $sql = "update ypt_pay set status=1,transId='$transId' where remark='" . $order_sn . "' AND status='0'";
                M('')->query($sql);
                A("App/PushMsg")->push_pay_message($order_sn);
            }
        }
    }

    public function into()
    {
        $list = $_POST['data'];
        $list = json_decode($list, true);
        $uid = $list['uid'];
        $arr = M('merchants_mpay')->where(array('uid' => $uid))->find();
        if (empty($arr)) {
            $reslut['responseCode'] = "1112";
            $reslut['resultMsg'] = "非法调用";
            $this->ajaxReturn($reslut);
        }
        $data['action'] = 'mcht/info/enter';
        $data['version'] = '2.0';
        $data['expanderCd'] = '0310500000';
        $data['coopMchtId'] = time() . rand(10000, 99999);
        $data['mchtName'] = $arr['mchtName'];
        $data['mchtShortName'] = $arr['mchtShortName'];
        $data['mchtType'] = $arr['mchtType'];//上级商户
        $data['parentMchtId'] = '888880000007726';
        $data['gszcName'] = $arr['gszcName'];
        $data['bizLicense'] = $arr['bizLicense'];//营业执照有效期
        $data['legalIdExpiredTime'] = $arr['legalIdExpiredTime'];
        $data['IdNo'] = $arr['IdNo'];
        $data['mchtAddr'] = $arr['mchtAddr'];
        $data['province'] = $arr['province'];//省代码
        $data['city'] = $arr['city'];//城市代码
        $data['area'] = $arr['area'];//区县代码
        $data['accountType'] = $arr['accountType'];//0-公户、1-私户
        $data['account'] = $arr['account'];//银行账号
        $data['accountName'] = $arr['accountName'];//账号名
        $data['bankCode'] = $arr['bankCode'];//开户行号
        $data['bankName'] = $arr['bankName'];//开户行名
        $data['openBranch'] = $arr['openBranch'];//开户网点（具体参考字典 6.12）
        $data['contactName'] = $arr['contactName'];//联系人名称
        $data['contactMobile'] = $arr['contactMobile'];//联系人手机
        $data['contactEmail'] = $arr['contactEmail'];//联系人邮箱
        $data['mchtLevel'] = $arr['mchtLevel'];//1-分店（上送父级商户号时，必须选择该级别）、2-商户
        $data['openType'] = $arr['openType'];// 1-个人、C - 企业
        $data['notifyUrl']=$this->notify;
        $arr1['acquirerType'] = 'wechat';
        $arr1['scale'] = $arr['weicodefen'];
        $arr1['countRole'] = '0';
        $arr1['tradeType'] = $arr['weicode'];
        $arr2['acquirerType'] = 'alipay';
        $arr2['scale'] = $arr['alipaycodefen'];
        $arr2['countRole'] = '0';
        $arr2['tradeType'] = $arr['alipaycode'];
        $arr3['acquirerType'] = 'qq';
        $arr3['scale'] = $arr['qqcodefen'];
        $arr3['countRole'] = '0';
        $arr3['tradeType'] = $arr['qqcode'];
        $data['acquirerTypes'] = json_encode(array($arr1, $arr2, $arr3));
        $data = json_encode($data);
        $data = "[" . $data . "]";
        $res = $this->rsaSign($data, $this->private_key);
        $result = $this->httpRequst($this->url, $data, $res, $this->apikey);
        $row = json_decode($result, true);
        $body = $row['body'];
        file_put_contents("./data/log/msbank/into/into.logs", date("Y-m-d H:i:s", time()) . "--" . $data . "--" . $reslut . "\r\n", FILE_APPEND | LOCK_EX);
        if ($body['responseCode'] == '00') {

            if (isset($body['bankMchtId']) && !empty($body['bankMchtId'])) {
                $bankMchtId = $body['bankMchtId'];
                $acq = $body['acquirerTypes'];
                $acq = json_decode($acq, true);
                foreach ($acq as $key => $value) {
                    if ($acq[$key]['acquirerType'] == 'wechat') {
                        $wechat = $acq[$key]['custId'];
                    } elseif ($acq[$key]['acquirerType'] == 'alipay') {
                        $alipay = $acq[$key]['custId'];
                    } elseif ($acq[$key]['acquirerType'] == 'qq') {
                        $qq = $acq[$key]['custId'];
                    }
                }
                file_put_contents("./data/log/msbank/into/success.logs", date("Y-m-d H:i:s", time()) . "--" . $data . "--" . $reslut . "\r\n", FILE_APPEND | LOCK_EX);
                $paysql = "UPDATE ypt_merchants_mpay SET wechat='$wechat',alipay='$alipay',qq='$qq',into_type='2',bankMchtId='$bankMchtId' WHERE uid='$uid'";
                $re = M()->query($paysql);
            } else {
                $acq = $body['acquirerTypes'];
                $acq = json_decode($acq, true);
                foreach ($acq as $key => $value) {
                    if ($acq[$key]['acquirerType'] == 'wechat') {
                        $wechat = $acq[$key]['custId'];
                    } elseif ($acq[$key]['acquirerType'] == 'alipay') {
                        $alipay = $acq[$key]['custId'];
                    } elseif ($acq[$key]['acquirerType'] == 'qq') {
                        $qq = $acq[$key]['custId'];
                    }
                }
                $paysql = "UPDATE ypt_merchants_mpay SET wechat='$wechat',alipay='$alipay',qq='$qq',into_type='2' WHERE uid='$uid'";
                $re = M()->query($paysql);
            }
        } else {
            $paysql = "UPDATE ypt_merchants_mpay SET into_type='1' WHERE uid='$uid'";
            $re = M()->query($paysql);
        }
        $this->ajaxReturn($row);
    }

    public function notify_member()
    {
        $fileName = "/alidata/www/youngshop/data/log/msbank/notify/" . date("Y-m-d", time()) . ".logs";
        if (!file_exists($fileName)) {
            @fopen($fileName, "w");
        }
        file_put_contents($fileName, date("Y-m-d H:i:s", time()) . "--" . $_POST['body'] . "--" . "\r\n", FILE_APPEND | LOCK_EX);
        $str = stripslashes($_POST['body']);
        if ($_POST) {
            $data = json_decode($str, true);
            $order_sn = $data['orderId'];
            $transId = $data['transId'];
            $orderData = M('')->query("select * from ypt_pay_member where order_sn='$order_sn'");
            if ($orderData[0]['status'] == 1) {
                $pay_time = time();
                $sql = "update ypt_pay_member set status=2,order_roof='$transId',pay_time='$pay_time' where order_sn='" . $order_sn . "' AND status='1'";
                M('')->query($sql);
                A("App/PushMsg")->push_pay_message($order_sn);
            }

        }
    }


    //支付宝支付界面跳转
    public function qr_alipay()
    {
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient') !== false) {
            $id = I('id');
            $merchant = M("merchants_cate")->where("id=$id")->find();
            $this->assign("merchant", $merchant);
            $this->assign('seller_id', $id);
            $this->assign('redirect_url', 'Pay/Barcodezsbank/qr_to_alipay');
            $this->display();
        }
    }


    /**
     * 支付宝扫码支付，调起支付宝支付,生成订单
     */
    public function qr_to_alipay()
    {
        header("Content-type:text/html;charset=utf-8");
        dump($_GET);die;
        $seller_id = I('seller_id');//二维码对应的id
        $checker_id = I('checker_id', 0, 'intval');
        if (!$seller_id) exit('seller_id不能为空!');
        $type = I("type");

        $res = M('merchants_cate')->where('id=' . $seller_id)->find();
        if (!$res) exit('二维码信息不存在!');
        $res['checker_id'] = $checker_id ? $checker_id : intval($res['checker_id']);
        $price = I('price');
        $res['price'] = $price ? $price : '0.01';
        if ($type || $type == '0') $res['mode'] = '1';
        else $res['mode'] = '0';
        if (I("jmt_remark")) $data['jmt_remark'] = I("jmt_remark");
        $remark = I('order_sn',date('YmdHis') . rand(100000, 999999));
        $price = I('price') ? I('price') : '0.01';
        $good_name = "向" . $res['jianchen'] . "支付" . $price . "元";
        $alipay_partner = $res['alipay_partner'];
        $data['merchant_id'] = $res['merchant_id'];
        $data['paystyle_id'] = 2;
        $data['price'] = $res['price'];
        $data['remark'] = $remark;
        $data['status'] = 0;
        $data['mode'] = $res['mode'];
        $data['paytime'] = time();
        $data['no_number'] = $res['no_number'];
        $data['cate_id'] = $res['id'];
        $data['cost_rate'] = $this->cost_rate_1($alipay_partner, 2);
        $data['bank'] = 2;

        $this->pay_model->add($data);
        $bank['custId'] = $alipay_partner;
        $bank['pay_type'] = 2;
        $bank['order_id'] = $remark;
        $bank['orderSubject'] = $good_name;
        $bank['orderDesc'] = '付款';
        $bank['totalAmount'] = $price;
        $this->C_b_pay($bank);

    }

    public function query_order()
    {
        header("Content-type:text/html;charset=utf-8");

        Vendor('QRcodeAlipay.Wz_pay');

        $wzPay = new \Wz_pay();
        $order_id = I("order_id");
        $res = $wzPay->queryOrder($order_id);
        print_r($res);
    }

    /**
     * 支付宝条码支付
     */
    public function ali_barcode_pay($id, $price, $auth_code, $checker_id, $jmt_remark,$order_sn,$mode = 2)
    {
        header('Content-Type:text/html; charset=utf-8');
        $payModel = $this->pay_model;

        //接收参数
        $id = $id ? $id : I('id', 0);
        $price = $price ? $price : I("price", 0);
        $auth_code = $auth_code ? $auth_code : I("auth_code");
        $checker_id = $checker_id ? $checker_id : I("checker_id");

        if (!$auth_code || !$id || $price < 0.01) $this->ajaxReturn(array("code" => "error", "msg" => "参数错误"));

        $res = M('merchants_cate')->where("merchant_id=$id")->find();
        $data = $payModel->where("customer_id=$auth_code")->find();
        if (!$data) {
            $remark = $order_sn?:date('YmdHis') . rand(100000, 999999);//订单号
            //插入数据库的数据
            $data['merchant_id'] = (int)$res['merchant_id'];//商户ID
            $data['customer_id'] = $auth_code;//买方账号ID
            $data['checker_id'] = $checker_id;//收银员的ID
            $data['paystyle_id'] = 2;//支付方式 1是微信 2是支付宝
            $data['price'] = $price;
            $data['remark'] = $remark;//订单号
            $data['status'] = 0;//待付款
            $data['cate_id'] = $res['id'];//支付样式,台签类别
            $data['mode'] = $mode;//0 为台签支付 1为扫码支付  2刷卡支付
            $data['add_time'] = time();//下单时间
            $data['cost_rate'] = $this->cost_rate_1($res['alipay_partner'], 2);
            $data['subject'] = "向" . $res['jianchen'] . "支付" . $price . "元";
            $data['bank'] = 2;
            if ($jmt_remark) $data['jmt_remark'] = $jmt_remark;
            $payModel->add($data);
        } else
            $remark = $data['remark'];

        $data['alipay_partner'] = $res['alipay_partner'];//支付宝商户号

        //调起条码支付
        $bank['custId'] = $res['alipay_partner'];
        $bank['pay_type'] = 2;
        $bank['order_sn'] = $remark;
        $bank['orderSubject'] = $data['good_name'];
        $bank['orderDesc'] = '洋仆淘';
        $bank['totalAmount'] = $price;
        $bank['walletAuthCode'] = $auth_code;
//       支付订单提交的数据交互
        $re = $this->B_c_pay($bank);
        $re = json_decode($re, true);
        $body = $re['body'];
        if ($body['responseCode'] == '00') {
            if ($body['transResult'] == 2) {
                $payModel->where(array("remark" => $remark))->save(array("status" => "1", "paytime" => time(), "buyers_account" => $body['buyerId'], 'transId' => $body['transId'], 'new_order_sn' => $body['reqId'], 'wz_remark' => $body['walletTransId'], 'bank' => 2));
                A("App/PushMsg")->push_pay_message($remark);
                return array("code" => "success", "msg" => "成功", "data" => '支付成功');
            } else {
                $body['custId'] = $remark;
                $body['orderId'] = $remark;
                $this->bs_pay($body);
            }
        } else {
            return array("code" => "error", "msg" => "失败", "data" => $body['errorMsg']);
        }
    }

    /**
     * 支付宝条码支付[pos机]
     */
    public function ali_barcode_pays($id, $price, $auth_code, $checker_id, $order_sn)
    {
        header('Content-Type:text/html; charset=utf-8');
        $payModel = $this->pay_model;

        //接收参数
        $id = $id ? $id : I('id', 0);
        $price = $price ? $price : I("price", 0);
        $auth_code = $auth_code ? $auth_code : I("auth_code");
        $checker_id = $checker_id ? $checker_id : I("checker_id");

        if (!$auth_code || !$id || $price < 0.01) $this->ajaxReturn(array("code" => "error", "msg" => "参数错误"));

        $res = M('merchants_cate')->where("merchant_id=$id")->find();
        $data = $payModel->where("customer_id=$auth_code")->find();
        if (!$data) {
            //$remark = date('YmdHis') . rand(100000, 999999);//订单号
            $remark = $order_sn;//订单号
            //插入数据库的数据
            $data['merchant_id'] = (int)$res['merchant_id'];//商户ID
            $data['customer_id'] = $auth_code;//买方账号ID
            $data['checker_id'] = $checker_id;//收银员的ID
            $data['paystyle_id'] = 2;//支付方式 1是微信 2是支付宝
            $data['price'] = $price;
            $data['remark'] = $remark;//订单号
            $data['status'] = 0;//待付款
            $data['cate_id'] = $res['id'];//支付样式,台签类别
            $data['mode'] = 2;//0 为台签支付 1为扫码支付  2刷卡支付
            $data['add_time'] = time();//下单时间
            $data['cost_rate'] = $this->cost_rate_1($res['alipay_partner'], 2);
            $data['subject'] = "向" . $res['jianchen'] . "支付" . $price . "元";
            $data['bank'] = 2;
            $payModel->add($data);
        } else
            $remark = $data['remark'];

        $data['alipay_partner'] = $res['alipay_partner'];//支付宝商户号

        //调起条码支付
        $bank['custId'] = $res['alipay_partner'];
        $bank['pay_type'] = 2;
        $bank['order_sn'] = $remark;
        $bank['orderSubject'] = $data['good_name'];
        $bank['orderDesc'] = '洋仆淘';
        $bank['totalAmount'] = $price;
        $bank['walletAuthCode'] = $auth_code;
//       支付订单提交的数据交互
        $re = $this->B_c_pay($bank);
        $re = json_decode($re, true);
        $body = $re['body'];
        if ($body['responseCode'] == '00') {
            if ($body['transResult'] == 2) {
                $payModel->where(array("remark" => $remark))->save(array("status" => "1", "paytime" => time(), "buyers_account" => $body['buyerId'], 'transId' => $body['transId'], 'new_order_sn' => $body['reqId'], 'wz_remark' => $body['walletTransId'], 'bank' => 2));
                A("App/PushMsg")->push_pay_message($remark);
                return array("code" => "success", "msg" => "成功", "data" => '支付成功');
            } else {
                $body['custId'] = $remark;
                $body['orderId'] = $remark;
                $this->bs_pay($body);
            }
        } else {
            return array("code" => "error", "msg" => "失败", "data" => $body['errorMsg']);
        }
    }

    /**调用微众--支付宝扫码支付【双屏主扫】
     *
     */
    public function screen_wz_alipay()
    {

        header("Content-type:text/html;charset=utf-8");
        $seller_id = I('seller_id');//二维码对应的id
        $order_id = I('order_id');
        $checker_id = I('checker_id', 0, 'intval');

        if (!$seller_id) exit('seller_id不能为空!');
        if (!$order_id) exit('订单号不能为空!');

        $res = M('merchants_cate')->where(array("id" => $seller_id))->find();
        $alipay_partner = $res['alipay_partner'];
        if (!$res) exit('二维码信息不存在!');
        $checker_id = $checker_id ? $checker_id : intval($res['checker_id']);
        $orderModel = M("order");
        $order_info = $orderModel->where(array("order_id" => $order_id))->find();
        if (!$order_info['order_sn']) exit('订单不存在!');

        $pay_info = $this->pay_model->where(array("remark" => $order_info['order_sn']))->find();
        if ($pay_info) {
            $cost_rate = $this->cost_rate_1($alipay_partner, 2);
            $data = array(
                "merchant_id" => $pay_info['merchant_id'],
                "price" => $pay_info['price'] ? $pay_info['price'] : '0.01',
                "remark" => $pay_info['remark'],
                "subject" => $pay_info['subject'] ? $pay_info['subject'] : "向" . $res['jianchen'] . "支付" . $order_info['order_amount'] . "元",
                "checker_id" => $checker_id,
                "bank" => 2,
                "cost_rate" => $cost_rate
            );
            $this->pay_model->where(array("remark" => $order_info['order_sn']))->save(array("paystyle_id" => 2));
        } else {
            $cost_rate = $this->cost_rate_1($alipay_partner, 2);
            $data = array(
                "merchant_id" => $res['merchant_id'],
                "price" => $order_info['order_amount'] ? $order_info['order_amount'] : '0.01',
                "subject" => "向" . $res['jianchen'] . "支付" . $order_info['order_amount'] . "元",
                "mode" => "3",//双屏扫码
                "paystyle_id" => "2",//支付宝
                "order_id" => $order_id,//订单编号
                "remark" => $order_info['order_sn'],//订单号唯一
                "status" => "0",//未付款
                "paytime" => time(),
                "add_time" => time(),
                "cost_rate" => $cost_rate,
                "cate_id" => $res['id'],
                "checker_id" => $checker_id,
                "bank" => 2
            );
            $this->pay_model->add($data);
        }
        $bank['custId'] = $alipay_partner;
        $bank['pay_type'] = 2;
        $bank['order_id'] = $order_info['order_sn'];
        $bank['orderSubject'] = $data['subject'];
        $bank['orderDesc'] = '付款';
        $bank['totalAmount'] = $data['price'];
        $this->C_b_pay($bank);
    }

    /**
     * 微众支付
     * 公账号支付
     */
    public function pay_cash()
    {
        header("Content-type:text/html;charset=utf-8");
        $wx_mchid = I('wx_mchid');
        $price = I('price');
        $account_id = I('account_id');
        $mid = I('mid');
        $data['card_id'] = I('id');
        $data['mid'] = $mid;
        $data['price'] = $price;
        $data['account_id'] = $account_id;
        $data['order_sn'] = date('YmdHis') . rand(100000, 999999);
        $data['public_time'] = time();
        $data['status'] = 1;
        $data['pay_type'] = 2;
        $bank['custId'] = $wx_mchid;
        $bank['pay_type'] = 1;
        $bank['order_id'] = $data['order_sn'];
        $bank['openid'] = $this->_get_openid();
        $bank['orderSubject'] = "充值" . $price . "元";
        $bank['orderDesc'] = '付款';
        $bank['totalAmount'] = $price;
        $re = $this->js_pay_member($bank);
        M('pay_member')->add($data);
        $re = json_decode($re, true);
        $body = $re['body'];
        if ($body['responseCode'] == '00') {
            $this->assign('body', $body['payInfo']);
            $this->assign('price', $price);
            $this->assign('openid', $bank['openid']);
            $this->assign('remark', $data['order_sn']);
            $this->assign('mid', $mid);
            $this->display("wz_pay");
        } else {
            echo '<script type="text/javascript">alert("' . $body['errorMsg'] . '")</script>';
        }
    }

    public function wz_pay()
    {
        header("Content-type:text/html;charset=utf-8");
        dump($_GET);
        //echo '<script type="text/javascript">alert("服务器失误！")</script>';
        die;
//        先获取openid防止 回调
        if (I("seller_id") == "") {
            $openid = $this->_get_openid();
            $sub_openid = $openid;
            $id = I("id");
            $res = M('merchants_cate')->where("id=$id")->find();
            $price = I("price");
            $data['mode'] = 1;
            $data['checker_id'] = I("checker_id");
        }
        if (I('seller_id') !== "") {
            $sub_openid = I('openid');
            $id = I('seller_id');
            $res = M('merchants_cate')->where("id=$id")->find();
            $price = I('price');
            $data['checker_id'] = $res['checker_id'];
            $data['mode'] = 0;
        }
        if (I("jmt_remark")) { //金木堂定单号
            $data['jmt_remark'] = I("jmt_remark");
        }
        $data['bank'] = 2;
        if (I("checker_id")) {
            $data['checker_id'] = I("checker_id");
        } //app上的台签带上收银员的信息
        $wx_mchid = $res['wx_mchid'];
        $data['bill_date'] = date("Ymd", time());
        $payModel = $this->pay_model;
        $remark = I('order_sn',date('YmdHis') . rand(100000, 999999));
        //            插入数据库的数据
        $data['merchant_id'] = (int)$res['merchant_id'];
        //$data['customer_id'] = $sub_openid;
        $data['customer_id'] = D("Api/ScreenMem")->add_member("$sub_openid", $res['merchant_id']);
        $data['paystyle_id'] = 1;
        $data['cost_rate'] = $this->cost_rate_1($wx_mchid, 1);
        $data['price'] = $price;
        $data['remark'] = $remark;
        $data['status'] = 0;
        $data['cate_id'] = $res['id'];
        $data['phone_info'] = $_SERVER['HTTP_USER_AGENT'];
        $data['paytime'] = time();
        $good_name = "向" . $res['jianchen'] . "支付" . $price . "元";
        $data['subject'] = $good_name;
        $payModel->add($data);
        $bank['custId'] = $wx_mchid;
        $bank['pay_type'] = 1;
        $bank['order_id'] = $remark;
        $bank['openid'] = $sub_openid;
        $bank['orderSubject'] = $good_name;
        $bank['orderDesc'] = '付款';
        $bank['totalAmount'] = $price;
        $re = $this->js_pay($bank);
        $re = json_decode($re, true);
        $body = $re['body'];
        if ($body['responseCode'] == '00') {
            $this->assign('body', $body['payInfo']);
            $this->assign('price', $price);
            $this->assign('openid', $sub_openid);
            $this->assign('remark', $remark);
            $this->assign('mid', $res['merchant_id']);
            $this->display("wz_pay");
        } else {
            echo '<script type="text/javascript">alert("' . $body['errorMsg'] . '")</script>';
        }

    }

    /**
     * 双屏扫码支付
     */
    public function two_wz_pay()
    {
        header("Content-type:text/html;charset=utf-8");
        dump($_GET);
DIE;
        vendor('Wzpay.Wzpay');
        $wzPay = new \Wzpay();
//        先获取openid防止 回调
        $order_id = I("order_id");
        $id = I("id");
        if ($order_id != "") {
            $openid = $this->_get_openid();
            $order = M("order");
            $remark = $order->where("order_id='$order_id'")->getField("order_sn");
            $sub_openid = $openid;
            $data['order_id'] = $order_id;
            $data['mode'] = 3;
            $data['checker_id'] = I("checker_id");
            $order = $order->where("order_id='$order_id'")->find();
            $price = $order['order_amount'];
            $res = M('merchants_cate')->where("id=$id")->find();
//            插入数据库的数据
            $data['merchant_id'] = (int)$res['merchant_id'];
            //$data['customer_id'] = $sub_openid;
            $data['customer_id'] = D("Api/ScreenMem")->add_member("$sub_openid", $res['merchant_id']);
            $data['paystyle_id'] = 1;
            $data['price'] = $price;
            $data['remark'] = $remark;
            $data['status'] = 0;
            $data['cate_id'] = $res['id'];
            $data['paytime'] = time();
            $order_sn = $remark . rand(1000, 9999);
            $data['new_order_sn'] = $order_sn;
            $data['bank'] = 2;
            $data['cost_rate'] = $this->cost_rate_1($res['wx_mchid'], 1);
            //预防pay表订单重复 
            $remark_exists = $this->pay_model->where(array('remark' => $remark))->find();
            if (!$remark_exists) {
                $this->pay_model->add($data);
            }
            //由于回调地址的原因，将id存入session中
            $good_name = "向" . $res['jianchen'] . "支付" . $price . "元";
            $mchid = $res['wx_mchid'];
        }
        $wx_mchid = $res['wx_mchid'];
        //使用统一支付接口()
        $bank['custId'] = $wx_mchid;
        $bank['pay_type'] = 1;
        $bank['order_id'] = $remark;
        $bank['openid'] = $openid;
        $bank['orderSubject'] = $good_name;
        $bank['orderDesc'] = '付款';
        $bank['totalAmount'] = $price;
//       支付订单提交的数据交互
        $re = $this->js_pay($bank);
        $re = json_decode($re, true);
        $body = $re['body'];
        if ($body['responseCode'] == '00') {
            $this->assign('body', $body['payInfo']);
            $this->assign('price', $price);
            $this->assign('openid', $sub_openid);
            $this->assign('remark', $remark);
            $this->assign('mid', $data['merchant_id']);
            $this->display("wz_pay");
        } else {
            echo '<script type="text/javascript">alert("' . $body['errorMsg'] . '")</script>';
        }
    }


    /**
     * 微众支付  全额退款
     */
    public function pay_back($remark)
    {
        header("Content-type:text/html;charset=utf-8");
        $pay = $this->pay_model->where("remark='$remark' and status = 1")->find();
        if (!$pay) return array("code" => "error", "msg" => "失败", "data" => "未找到订单");
        $bank['order_id'] = $remark;

        $re = $this->refund($bank);
        $re = json_decode($re, true);
        return $re;
    }


    /**
     * 支付成功后更新库存
     * 传入订单唯一标识$order_sn
     * @param int $order_sn
     */
    private function update_order_goods_number($order_sn = 0)
    {
        if (!$order_sn) exit();
        $order_id = M("order")->where(array("order_sn" => $order_sn))->getField("order_id");
        $order_goods_list = M("order_goods")->where(array("order_id" => $order_id))->field("goods_id,goods_num")->select();
        if (!$order_goods_list) exit();
        foreach ($order_goods_list as $k => $v) {
            if ($v['goods_id'] && $v['goods_num']) M("goods")->where(array("goods_id" => $v['goods_id']))->setDec('goods_number', $v['goods_num']); //更新库存
        }
    }


    public function push_pay_message($remark)
    {
        $pay = $this->pay_model->where("remark='$remark'")->find();
        if (!$pay) return;
        //声明推送消息日志路径
        $path = get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/message/');
        if (!$pay) {
            return;
        }
        $mid = $pay['merchant_id'];
        $checker = $pay['checker_id'];

        $status = $pay['status'];
        $price = $pay['price'];
        $mode = $pay['mode'];
        switch ($mode) {
            case 0:
                $mode = "台签";
                break;
            case 1:
                $mode = "商业扫码支付";
                break;
            case 2:
                $mode = "商业刷卡支付";
                break;
            case 3:
                $mode = "收银扫码支付";
                break;
            case 4:
                $mode = "收银现金支付";
                break;
            default:
                $mode = "其他支付";
                break;
        }
        if ($status == 0) {
            $massage = "收款失败";
        } else if ($status == 1) {
            $massage = "[" . $mode . "]" . "来钱啦,收款" . $price . "元！";
        } else
            $massage = '';

        //有收银员的情况下,将信息发给收银员
        if ($checker) {
            $check_phone = M("merchants_users")->where("id=$checker")->getField("user_phone");
        }

        //当前商户
        $merchants_info = M("merchants")->where("id=$mid")->field("uid,mid")->find();
        $uid = $merchants_info['uid'];
        $user_phone = M("merchants_users")->where(array('id' => $uid))->getField("user_phone");

        //多门店大商户
        if ($merchants_info['mid'] > 0) {
            $big_uid = M("merchants")->where(array('id' => $merchants_info['mid']))->getField("uid");
            $big_user_phone = M("merchants_users")->where(array('id' => $big_uid))->getField("user_phone");
        }
        file_put_contents($path . date("Y_m_d_") . 'pay_message.log', date("Y-m-d H:i:s") . '未发送1' . "$remark" . PHP_EOL, FILE_APPEND | LOCK_EX);
        /***推送给大商户****/
        if (isset($big_user_phone) && isset($big_uid) && M("token")->where(array('uid' => $big_uid))->getField("uid")) {
            A("Message/adminpush")->adminpush("$massage", "$remark", "$status", "$big_user_phone");
            file_put_contents($path . date("Y_m_d_") . 'pay_message.log', date("Y-m-d H:i:s") . '发送信息给多门店大商户: ' . $big_user_phone . "___" . $status . "____" . $massage . ",订单号:" . "$remark" . PHP_EOL, FILE_APPEND | LOCK_EX);
        } else
            file_put_contents($path . date("Y_m_d_") . 'pay_message.log', date("Y-m-d H:i:s") . '未发送' . $user_phone . "的上级门店未登录____订单号:  " . "$remark" . PHP_EOL, FILE_APPEND | LOCK_EX);
        file_put_contents($path . date("Y_m_d_") . 'pay_message.log', date("Y-m-d H:i:s") . '未发送2' . "$remark" . PHP_EOL, FILE_APPEND | LOCK_EX);
        /***推送给收银员****/
        if (isset($check_phone) && M("token")->where(array('uid' => $checker))->getField("uid")) {
            A("Message/adminpush")->adminpush("$massage", "$remark", "$status", "$check_phone");
            file_put_contents($path . date("Y_m_d_") . 'pay_message.log', date("Y-m-d H:i:s") . '发送信息给收银员: ' . $check_phone . "___" . $status . "____" . $massage . ",订单号:" . "$remark" . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
        file_put_contents($path . date("Y_m_d_") . 'pay_message.log', date("Y-m-d H:i:s") . '未发送3' . "$remark" . PHP_EOL, FILE_APPEND | LOCK_EX);
        /***推送给商户****/
        if ($user_phone && M("token")->where(array('uid' => $uid))->getField("uid")) {
            A("Message/adminpush")->adminpush("$massage", "$remark", "$status", "$user_phone");
            file_put_contents($path . date("Y_m_d_") . 'pay_message.log', date("Y-m-d H:i:s") . '发送信息给商户: ' . $user_phone . "___" . $status . "____" . $massage . ",订单号:" . "$remark" . PHP_EOL, FILE_APPEND | LOCK_EX);
        } else
            file_put_contents($path . date("Y_m_d_") . 'pay_message.log', date("Y-m-d H:i:s") . '未发送' . $user_phone . "未登录____订单号:  " . "$remark" . PHP_EOL, FILE_APPEND | LOCK_EX);


    }

//    支付成功  卡券信息更新
    public function _change_coupon($order_one)
    {
        $order_id = $order_one['order_id'];
        M("order")->where("order_id='$order_id'")->save(array("pay_time" => time(), "pay_status" => 1, "paystyle" => 3));
        $this->update_order_goods_number($order_one['order_sn']);
        $code = $order_one['coupon_code'];
        $coupon_user_one = M("screen_user_coupons")->where("usercard='$code'")->find();
        if ($coupon_user_one) {
            $url = "https://api.weixin.qq.com/card/code/consume?access_token=SYhLiZvX5XZzXWGGqva0zFaW1fgKK2RxIIEOLS0Go5_iwf6Mt1j03ZSiBRIeXyw5Hwk6x1ClF-tXUF8cabEC5QX9NcvfLsTGKUz63VTjr-8tP4zWNd63uY4ioc8yw9OmYLHdACAPCY";
            $data['code'] = $code;
            $use_coupon = request_post($url, json_encode($data));
            $use_coupon = json_decode($use_coupon);
            file_put_contents('./data/log/wz/weixin/weixin.log', date("Y-m-d H:i:s") . '用户使用优惠券' . json_encode($use_coupon) . PHP_EOL, FILE_APPEND | LOCK_EX);
            if ($use_coupon->errmsg != "ok") {
                file_put_contents('./data/log/wz/weixin/weixin.log', date("Y-m-d H:i:s") . '用户使用优惠券失败' . $order_id . PHP_EOL, FILE_APPEND | LOCK_EX);
            }

            if ($use_coupon->errmsg == "ok") {
                M("screen_user_coupons")->where("usercard='$code'")->save(array("satus" => 0));
            }
        }
    }



    /**
     * 微众支付
     * 公众号支付订单查询
     */


    /**
     * 获取jssdk需要用到的数据
     * @return array jssdk需要用到的数据
     */
    private function _get_openid()
    {
        // 获取配置项
        $config = C('WEIXINPAY_CONFIG');
        // 如果没有get参数没有code；则重定向去获取openid；
        if (!isset($_GET['code'])) {
            // 返回的url
//            $redirect_uri = U('Pay/Barcode/qr_weixipay', '', '', true);'http://' . $_SERVER['HTTP_HOST']
            $redirect_uri = 'https://' . $_SERVER['HTTP_HOST'] . $_SESSION['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            $redirect_uri = urlencode($redirect_uri);
            $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $config['APPID'] . '&redirect_uri=' . $redirect_uri . '&response_type=code&scope=snsapi_base#wechat_redirect';
            redirect($url);
        } else {
            //如果有code参数；则表示获取到openid
            $code = I('get.code');
            //组合获取prepay_id的url
            $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $config['APPID'] . '&secret=' . $config['APPSECRET'] . '&code=' . $code . '&grant_type=authorization_code';
            //curl获取prepay_id
            $result = $this->_curl_get_contents($url);
            $result = json_decode($result, true);
            return $result['openid'];

        }
    }

    /**
     * 使用curl获取远程数据
     * @param  string $url url连接
     * @return string      获取到的数据
     */
    private function _curl_get_contents($url)
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


    /**
     * 微众支付
     * 刷卡支付
     */
    public function wz_micropay($id, $price, $auth_code, $checker_id, $jmt_remark,$order_sn,$mode)
    {
        header('Content-Type:application/json; charset=utf-8');
//        if (IS_POST) {
//        $auth_code = I('post.auth_code', 0);
        if (!$auth_code) {
            $this->error('参数错误!');
        }
        if (!$id) {
            return array("code" => "error", "msg" => "失败", "data" => "支付失败");
        }
        $res = M('merchants_cate')->where("merchant_id=$id")->find();
        if ((int)$res['merchant_id'] == 0) {
            return array("code" => "error", "msg" => "失败", "data" => "还未申请支付业务");
        }
        $wx_mchid = $res['wx_mchid'];
        $remark = $order_sn?:date('YmdHis') . rand(100000, 999999);
//            插入数据库的数据
        $data['merchant_id'] = (int)$res['merchant_id'];
        $data['checker_id'] = $checker_id;
        $data['paystyle_id'] = 1;
        $data['price'] = $price;
        $data['remark'] = $remark;
        $data['status'] = 0;
        $data['cate_id'] = $res['id'];
        if ($mode) {
            $data['mode'] = $mode;
        }else{
            $data['mode'] = 2;  
        }
        $data['paytime'] = time();
        $data['bank'] = 2;
        if ($jmt_remark) { //金木堂定单号
            $data['jmt_remark'] = $jmt_remark;
        }
        $data['cost_rate'] = $this->cost_rate_1($wx_mchid, 1);
        $merchant_code = $res["wx_mchid"];
        $key = $res["wx_key"];
        $product = "向" . $res['jianchen'] . "支付" . $price . "元";
        $this->pay_model->add($data);
        $bank['custId'] = $wx_mchid;
        $bank['pay_type'] = 1;
        $bank['order_sn'] = time();
        $bank['orderSubject'] = $product;
        $bank['orderDesc'] = '付款';
        $bank['totalAmount'] = $price;
        $bank['walletAuthCode'] = $auth_code;
//       支付订单提交的数据交互
        $re = $this->B_c_pay($bank);
        $re = json_decode($re, true);
        $fileName = "./data/log/msbank/shuaka/" . date("Y-m-d", time()) . ".logs";
        if (!file_exists($fileName)) {
            @fopen($fileName, "w");
        }
        $body = $re['body'];
        if ($body['responseCode'] == '00') {
            if ($body['transResult'] == 2) {
                $brr = array("status" => "1", "paytime" => time(), "customer_id" => $body['uuid'], 'transId' => $body['transId'], 'new_order_sn' => $body['reqId'], 'wz_remark' => $body['walletTransId'], 'bank' => 2);
                $customer_id = D("Api/ScreenMem")->add_member($body['uuid'], $res['merchant_id']);
                $this->pay_model->where(array("remark" => $remark))->save(array("status" => "1", "paytime" => time(), "customer_id" => $customer_id, 'transId' => $body['transId'], 'new_order_sn' => $body['reqId'], 'wz_remark' => $body['walletTransId'], 'bank' => 2));

                $fileName = "./data/log/msbank/shuaka/" . date("Y-m-d", time()) . ".logs";
                if (!file_exists($fileName)) {
                    @fopen($fileName, "w");
                }
                A("App/PushMsg")->push_pay_message($remark);
                file_put_contents($fileName, date("Y-m-d H:i:s", time()) . "-成功-" . json_encode($body) . "\r\n", FILE_APPEND | LOCK_EX);
                return array("code" => "success", "msg" => "成功", "data" => '支付成功');
            } else {

                $body['custId'] = $wx_mchid;
                $body['orderId'] = $remark;
                file_put_contents($fileName, date("Y-m-d H:i:s", time()) . "-成功333-" . json_encode($body) . "\r\n", FILE_APPEND | LOCK_EX);
                $this->bs_pay($body);
            }
        } else {
            $fileName = "./data/log/msbank/shuaka/" . date("Y-m-d", time()) . ".logs";
            if (!file_exists($fileName)) {
                @fopen($fileName, "w");
            }
            file_put_contents($fileName, date("Y-m-d H:i:s", time()) . "-失败-" . json_encode($body) . "\r\n", FILE_APPEND | LOCK_EX);
            A("App/PushMsg")->push_pay_message($remark);
            return array("code" => "error", "msg" => "失败", "data" => $body['errorMsg']);
        }
    }

    public function pos_wz_micropay($id, $price, $auth_code, $checker_id, $order_sn)
    {
        header('Content-Type:application/json; charset=utf-8');
//        if (IS_POST) {
//        $auth_code = I('post.auth_code', 0);
        if (!$auth_code) {
            $this->error('参数错误!');
        }
        if (!$id) {
            return array("code" => "error", "msg" => "失败", "data" => "支付失败");
        }
        $res = M('merchants_cate')->where("merchant_id=$id")->find();
        if ((int)$res['merchant_id'] == 0) {
            return array("code" => "error", "msg" => "失败", "data" => "还未申请支付业务");
        }
        $wx_mchid = $res['wx_mchid'];
        //$remark = date('YmdHis') . rand(100000, 999999);
        $remark = $order_sn;
//            插入数据库的数据
        $data['merchant_id'] = (int)$res['merchant_id'];
        $data['checker_id'] = $checker_id;
        $data['paystyle_id'] = 1;
        $data['price'] = $price;
        $data['remark'] = $remark;
        $data['status'] = 0;
        $data['cate_id'] = $res['id'];
        $data['mode'] = 2;
        $data['paytime'] = time();
        $data['bank'] = 2;
        $data['cost_rate'] = $this->cost_rate_1($wx_mchid, 1);
        $merchant_code = $res["wx_mchid"];
        $key = $res["wx_key"];
        $product = "向" . $res['jianchen'] . "支付" . $price . "元";
        $this->pay_model->add($data);
        $bank['custId'] = $wx_mchid;
        $bank['pay_type'] = 1;
        $bank['order_sn'] = time();
        $bank['orderSubject'] = $product;
        $bank['orderDesc'] = '付款';
        $bank['totalAmount'] = $price;
        $bank['walletAuthCode'] = $auth_code;
//       支付订单提交的数据交互
        $re = $this->B_c_pay($bank);
        $re = json_decode($re, true);
        $body = $re['body'];
        if ($body['responseCode'] == '00') {
            if ($body['transResult'] == 2) {
                $brr = array("status" => "1", "paytime" => time(), "customer_id" => $body['uuid'], 'transId' => $body['transId'], 'new_order_sn' => $body['reqId'], 'wz_remark' => $body['walletTransId'], 'bank' => 2);
                $customer_id = D("Api/ScreenMem")->add_member($body['uuid'], $res['merchant_id']);
                $this->pay_model->where(array("remark" => $remark))->save(array("status" => "1", "paytime" => time(), "customer_id" => $customer_id, 'transId' => $body['transId'], 'new_order_sn' => $body['reqId'], 'wz_remark' => $body['walletTransId'], 'bank' => 2));

                $fileName = "./data/log/msbank/shuaka/" . date("Y-m-d", time()) . ".logs";
                if (!file_exists($fileName)) {
                    @fopen($fileName, "w");
                }
                A("App/PushMsg")->push_pay_message($remark);
                file_put_contents($fileName, date("Y-m-d H:i:s", time()) . "-成功-" . json_encode($body) . "\r\n", FILE_APPEND | LOCK_EX);
                $pay = $this->pay_model->where(array('remark' => $order_sn))->order("paytime desc")->field("price,paystyle_id,mode,remark,paytime,status,jmt_remark")->find();
                $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "success", "msg" => "成功", "data" => $pay)));
            } else {
                $body['custId'] = $wx_mchid;
                $body['orderId'] = $remark;
                $this->bs_pay($body);
            }
        } else {
            $fileName = "./data/log/msbank/shuaka/" . date("Y-m-d", time()) . ".logs";
            if (!file_exists($fileName)) {
                @fopen($fileName, "w");
            }
            file_put_contents($fileName, date("Y-m-d H:i:s", time()) . "-失败-" . json_encode($body) . "\r\n", FILE_APPEND | LOCK_EX);
            A("App/PushMsg")->push_pay_message($remark);
            return array("code" => "error", "msg" => "失败", "data" => $body['errorMsg']);
        }
    }

    private function bs_pay($data)
    {
        file_put_contents('./data/log/wz/weixin/shuaka.log', date("Y-m-d H:i:s") . '--支付查询111111--' . PHP_EOL, FILE_APPEND | LOCK_EX);
        if ($data['transResult'] == 0) {
            $bank['orderId'] = $data['orderId'];
            $bank['reqId'] = $data['reqId'];
            $bank['transId'] = $data['transId'];
            $bank['custId'] = $data['custId'];
            $re = $this->check($bank);

        } else {
            $queryTimes = 6;
            while ($queryTimes > 0) {
                $bank['orderId'] = $data['orderId'];
                $bank['orgReqId'] = $data['reqId'];
                $bank['orgTransId'] = $data['transId'];
                $bank['custId'] = $data['custId'];
                $re = $this->alifun($bank);
                file_put_contents('./data/log/wz/weixin/shuaka.log', date("Y-m-d H:i:s") . '--支付查询--' . json_encode($re) . PHP_EOL, FILE_APPEND | LOCK_EX);
                $re = json_decode($re, true);
                $body = $re['body'];
                $succResult = $body['transStatus'];
                //如果需要等待5s后继续
                if ($succResult == 2) {
                    $brr = array("status" => "1", "paytime" => time(), 'transId' => $body['transId'], 'new_order_sn' => $body['reqId'], 'bank' => 2);
                    $this->pay_model->where(array("remark" => $bank['orderId']))->save(array("status" => "1", "paytime" => time(), 'transId' => $body['transId'], 'new_order_sn' => $body['reqId'], 'bank' => 2));
                    A("App/PushMsg")->push_pay_message($bank['orderId']);
                    file_put_contents("./data/log/wz/weixin/shuaka.log", date("Y-m-d H:i:s", time()) . "-成功-" . json_encode($body) . "--" . json_encode($brr) . "\r\n", FILE_APPEND | LOCK_EX);
                    $pay = $this->pay_model->where(array('remark' => $bank['orderId']))->order("paytime desc")->field("price,paystyle_id,mode,remark,paytime,status,jmt_remark")->find();
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "success", "msg" => "成功", "data" => $pay)));
                } else if ($succResult == 0) {
                    file_put_contents("./data/log/wz/weixin/shuaka.log", date("Y-m-d H:i:s", time()) . "-支付失败-" . json_encode($body) . "\r\n", FILE_APPEND | LOCK_EX);
                    $this->pay_model->where(array("remark" => $body['orderId']))->save(array("status" => "-2"));
                    return array("code" => "error", "msg" => "失败", "data" => '支付失败');
                } else {
                    sleep(5);
                    $queryTimes--;
                    continue;
                }
            }
        }
        $bank['orderId'] = $data['orderId'];
        $bank['orgReqId'] = date("YmdHis") . rand(1000, 9999);
        $bank['orgTransId'] = $data['transId'];
        $bank['custId'] = $data['custId'];
        $re = $this->check($bank);
        file_put_contents("./data/log/wz/weixin/shuaka.log", date("Y-m-d H:i:s", time()) . json_encode($bank) . "--支付失败,输入密码时间过长--" . $re . "\r\n", FILE_APPEND | LOCK_EX);
        $re = json_decode($re, true);
        $body = $re['body'];
        if ($body['responseCode'] == '00') {
            $this->pay_model->where(array("remark" => $body['orderId']))->save(array("status" => "-2"));
            return array("code" => "error", "msg" => "失败", "data" => '交易时间过长,支付失败');;
        }
    }


    /**
     * 订单通知
     */
    private function insert_bill($str)
    {
        $preg = preg_match_all("#[^\n].*#", $str, $match);
        if (!$preg) {
            return false;
        }
        $new_str = array_map(function ($item) {
            return preg_replace('#\s*#', '', $item);
        }, $match[0]);
        $arr = array();
        array_map(function ($item) use (&$arr) {
            $arr[] = explode(',', $item);
        }, $new_str);
        $length = count($arr);
        //日期,代理商编号,代理商名称,商户编号,商户名称,交易类型,商户订单号,微众订单号,原商户订单号,交易金额,手续费,代理商手续费,清算金额,添加时间
        $arr[0] = array('bill_date', 'agency_no', 'anency_name', 'merchant_no', 'merchant_name', 'deal_type', 'merchant_order_sn', 'wz_order_sn', 'deal_money', 'poundage', 'agency_poundage', 'clearing_money', 'add_time');
        //日期,交易总笔数,消费交易笔数,退货交易笔数,冲正交易笔数,交易总金额,手续费总额,代理商手续费总额,清算总金额,添加时间
        $arr[$length - 2] = array('bill_date', 'total_deal', 'consume_deal', 'return_deal', 'reverse_deal', 'total_money', 'poundage', 'anency_poudage', 'pay_money', 'add_time');
        foreach ($arr as $k => $v) {
            if ($k != 0 && $k < $length - 2) {
                unset($v[8]);
                array_push($v, time());
                $detail_arr[] = array_combine($arr[0], $v);
            }
            if ($k == $length - 1) {
                array_push($v, time());
                $count_arr[] = array_combine($arr[$length - 2], $v);
            }

        }

        $billRecordModel = M('bill_record');
        $everydayBillCountModel = M('everyday_bill_count');
        array_map(function ($item) use ($billRecordModel) {
            $billRecordModel->add($item);
        }, $detail_arr);
        array_map(function ($item) use ($everydayBillCountModel) {
            $everydayBillCountModel->add($item);
        }, $count_arr);
    }

    /**
     *    作用：使用证书，以GET方式提交参数
     */
    private function _getXmlSSLCurl($url, $second = 30)
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
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //设置证书
        //使用证书：cert 与 key 分别属于两个.pem文件
        //默认格式为PEM，可以注释
        curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLCERT, '/alidata/www/hzsj/store/simplewind/Core/Library/Vendor/Bbshop/youngPort/cert/apiclient_cert.pem');
        //默认格式为PEM，可以注释
        curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLKEY, '/alidata/www/hzsj/store/simplewind/Core/Library/Vendor/Bbshop/youngPort/cert/apiclient_key.pem');
        $data = curl_exec($ch);
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

    private function cost_rate_1($wx_mchid, $paytype)
    {
        if ($paytype == 1) {
            $re = M('merchants_mpay')->where(array('wechat' => $wx_mchid))->find();
            return '0.' . $re['weicodefen'];
        } elseif ($paytype == 2) {
            $re = M('merchants_mpay')->where(array('alipay' => $wx_mchid))->find();
            return '0.' . $re['alipaycodefen'];
        }
    }

    public function bill_down()
    {
        exit('Repealed!');
        $data['action'] = 'mcht/bill/download';
        $data['version'] = '2.0';
        $data['coopId'] = 'APPKEY';
        $data['billDate'] = date("Ymd", strtotime("-1 day"));
        $data = json_encode($data);
        $data = "[" . $data . "]";
        $res = $this->rsaSign($data, $this->private_key);
        $result = $this->httpRequst($this->url, $data, $res, $this->apikey);
        var_dump($result);
        exit();
       
    }
}



