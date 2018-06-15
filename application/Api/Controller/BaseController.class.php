<?php

namespace Api\Controller;

use think\Controller;

class  BaseController extends Controller
{
    private $pay_model;

    public function _initialize()
    {
        $this->pay_model = M('pay');
        $this->url = "https://aop.koolyun.com:443/apmp/rest/v2";
        $this->apikey = "YPT17001P";
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

    //退款
    public function pay_back()
    {
        $this->pays = $this->pay_model;
        if (I('sign') == '5e022b44a15a90c01') {

        } else {
            echo 'fail';
            return false;
        }
        ($mid = I('mid')) || err('mid is empty');
        ($style = I("style")) || err('style is empty');
        ($remark = I("remark")) || err('remark is empty');
        ($price_back = I("price_back")) || err('price_back is empty');
        $price_back = $price_back / 100;

        $pay = $this->pays->where("remark='$remark' And merchant_id= $mid ")->find();
        //dump($pay);
        if (!$pay) $this->ajaxReturn(array("code" => "error", "msg" => "未找到订单"));
        $price = $pay['price'];
        if ($price_back > $price) $this->ajaxReturn(array("code" => "error", "msg" => "退款金额不能大于原有金额"));
        if ($style == 1) { //现金退款
            if ($pay) $this->pays->where("remark='$remark'")->save(array("status" => "2", "price_back" => $price_back, "back_status" => 1));
            $back_info = $this->add_pay_back($pay, 99, $price_back);
            $this->add_order_goods_number($remark);
            $this->ajaxReturn(array("code" => "success", "msg" => "退款成功", 'back_info' => $back_info));
        } else if ($style == 2) { //原路全额退款
            if ($pay['bank'] == 1) { //微众银行
                $pay_style = $pay['paystyle_id'];
                if ($pay_style == 1) {//微信原路退款
                    if ($price_back != $price) $this->ajaxReturn(array("code" => "error", "msg" => "该笔订单仅支持全额退款"));
                    $result = A("Pay/Barcode")->wx_pay_back($remark);
                    if ($result['code'] == "success") {
                        $result['back_info'] = $this->add_pay_back($pay, 98, $price_back);
                        $this->add_order_goods_number($remark);
                    }
                    $this->ajaxReturn($result);
                }
                if ($pay_style == 2) {//支付宝原路退款
                    if ($price_back != $price) $this->ajaxReturn(array("code" => "error", "msg" => "该笔订单仅支持全额退款"));
                    $result = A("Pay/Barcode")->ali_pay_back($remark);
                    if ($result['code'] == "success") {
                        $result['back_info'] = $this->add_pay_back($pay, 98, $price_back);
                        $this->add_order_goods_number($remark);
                    }
                    $this->ajaxReturn($result);
                }
            }
            if ($pay['bank'] == 2) {//民生银行
                //if ($price_back != $price) $this->ajaxReturn(array("code" => "error", "msg" => "退款金额必须等于原有金额"));
                if ($price_back > $price) $this->ajaxReturn(array("code" => "error", "msg" => "退款金额不能大于原有金额"));
                $result = A("Pay/Barcodembank")->pay_back($remark, $price_back);
                file_put_contents('./data/log/wz/weixin/ms.log', date("Y-m-d H:i:s") . json_encode($result) . "--3--" . PHP_EOL, FILE_APPEND | LOCK_EX);
                if ($result['code'] == "success") {
                    $result['back_info'] = $this->add_pay_back($pay, 98, $price_back);
                    $this->add_order_goods_number($remark);
                }
                file_put_contents('./data/log/wz/weixin/ms.log', date("Y-m-d H:i:s") . json_encode($result) . "--2--" . PHP_EOL, FILE_APPEND | LOCK_EX);
                $this->ajaxReturn($result);
            }
            // 微信支付
            if ($pay['bank'] == 3) {
                file_put_contents('./data/log/wz/weixin/ms.log', date("Y-m-d H:i:s") . $remark . '订单号' . '付款金额不一致' . PHP_EOL, FILE_APPEND | LOCK_EX);
                //if ($price_back != $price) $this->ajaxReturn(array("code" => "error", "msg" => "退款金额必须等于原有金额"));
                if ($price_back > $price) $this->ajaxReturn(array("code" => "error", "msg" => "退款金额不能大于原有金额"));
                $result = A("Pay/Wxpay")->pay_back($remark, $price_back);
                if ($result['code'] == "success") {
                    $result['back_info'] = $this->add_pay_back($pay, 98, $price_back);
                    $this->add_order_goods_number($remark);
                }
                $this->ajaxReturn($result);
            }
            if ($pay['bank'] == 4) {
                $pay_style = $pay['paystyle_id'];
                if ($pay_style == 1) {//微信原路退款
                    //if ($price_back != $price) $this->ajaxReturn(array("code" => "error", "msg" => "退款金额必须等于原有金额"));
                    if ($price_back > $price) $this->ajaxReturn(array("code" => "error", "msg" => "退款金额不能大于原有金额"));
                    $result = A("Pay/Barcodezsbank")->wx_pay_back($remark, $price_back);
                    if ($result['code'] == "success") {
                        $result['back_info'] = $this->add_pay_back($pay, 98, $price_back);
                        $this->add_order_goods_number($remark);
                    }
                    $this->ajaxReturn($result);
                }
                if ($pay_style == 2) {//支付宝原路退款
                    //if ($price_back != $price) $this->ajaxReturn(array("code" => "error", "msg" => "退款金额必须等于原有金额"));
                    if ($price_back > $price) $this->ajaxReturn(array("code" => "error", "msg" => "退款金额不能大于原有金额"));
                    $result = A("Pay/Barcodezsbank")->ali_pay_back($remark, $price_back);
                    if ($result['code'] == "success") {
                        $result['back_info'] = $this->add_pay_back($pay, 98, $price_back);
                        $this->add_order_goods_number($remark);
                    }
                    $this->ajaxReturn($result);
                }
            }
            // 钱方支付
            if ($pay['bank'] == 5) {
                file_put_contents('./data/log/wz/weixin/ms.log', date("Y-m-d H:i:s") . $remark . '订单号' . '付款金额不一致' . PHP_EOL, FILE_APPEND | LOCK_EX);
                //if ($price_back != $price) $this->ajaxReturn(array("code" => "error", "msg" => "退款金额必须等于原有金额"));
                if ($price_back > $price) $this->ajaxReturn(array("code" => "error", "msg" => "退款金额不能大于原有金额"));
                $result = A("Pay/QianFangPay")->pay_back($remark, $price_back);
                if ($result['code'] == "success") {
                    $result['back_info'] = $this->add_pay_back($pay, 98, $price_back);
                    $this->add_order_goods_number($remark);
                }
                $this->ajaxReturn($result);
            }
            if ($pay['bank'] == 6) {//济南民生银行
                //济南民生属于D0通道不能退款
                $this->ajaxReturn(array("code" => "error", "msg" => "D0通道不能退款"));
                /*
                if ($price_back != $price) $this->ajaxReturn(array("code" => "error", "msg" => "退款金额必须等于原有金额"));
                $result=A("Pay/Barcodemsday")->pay_back($remark);

                if($result['code'] =="success"){
                    $result['back_info'] = $this->add_pay_back($pay,98,$price_back);
                    $this->add_order_goods_number($remark);
                }
                $this->ajaxReturn($result);*/
            }
            // 兴业银行
            if ($pay['bank'] == 7) {
                $pay_style = $pay['paystyle_id'];
                if ($pay_style == 1) {//微信原路退款
                    $d = M('merchants_xypay')->where(array('merchant_id' => $mid))->getField('pay_style');
                    if ($d != '1') {
                        $this->ajaxReturn(array("code" => "error", "msg" => "D0通道不能退款"));
                    }
                    //if ($price_back != $price) $this->ajaxReturn(array("code" => "error", "msg" => "退款金额必须等于原有金额"));
                    if ($price_back > $price) $this->ajaxReturn(array("code" => "error", "msg" => "退款金额不能大于原有金额"));
                    $result = A("Pay/Barcodexybank")->wx_pay_back($remark, $price_back);
                    if ($result['code'] == "success") {
                        $result['back_info'] = $this->add_pay_back($pay, 98, $price_back);
                        $this->add_order_goods_number($remark);
                    }
                    $this->ajaxReturn($result);
                }
                if ($pay_style == 2) {//支付宝原路退款
                    $d = M('merchants_xypay')->where(array('merchant_id' => $mid))->getField('pay_style');
                    if ($d != '1') {
                        $this->ajaxReturn(array("code" => "error", "msg" => "D0通道不能退款"));
                    }
                    //if ($price_back != $price) $this->ajaxReturn(array("code" => "error", "msg" => "退款金额必须等于原有金额"));
                    if ($price_back > $price) $this->ajaxReturn(array("code" => "error", "msg" => "退款金额不能大于原有金额"));
                    $result = A("Pay/Barcodexybank")->ali_pay_back($remark, $price_back);
                    if ($result['code'] == "success") {
                        $result['back_info'] = $this->add_pay_back($pay, 98, $price_back);
                        $this->add_order_goods_number($remark);
                    }
                    $this->ajaxReturn($result);
                }
            }
            // 宿州李总微信支付
            if ($pay['bank'] == 9) {
                $pay_style = $pay['paystyle_id'];
                if ($pay_style == 1) {//微信原路退款
                    //if ($price_back != $price) $this->ajaxReturn(array("code" => "error", "msg" => "退款金额必须等于原有金额"));
                    if ($price_back > $price) $this->ajaxReturn(array("code" => "error", "msg" => "退款金额不能大于原有金额"));
                    $result = A("Pay/Szlzpay")->pay_back($remark, $price_back);
                    if ($result['code'] == "success") {
                        $result['back_info'] = $this->add_pay_back($pay, 98, $price_back);
                        //$this->add_order_goods_number($remark);
                    }
                    $this->ajaxReturn($result);
                }
                if ($pay_style == 2) {//支付宝原路退款
                    //if ($price_back != $price) $this->ajaxReturn(array("code" => "error", "msg" => "退款金额必须等于原有金额"));
                    if ($price_back > $price) $this->ajaxReturn(array("code" => "error", "msg" => "退款金额不能大于原有金额"));
                    $result = A("Pay/Szlzpay")->ali_pay_back($remark, $price_back);
                    if ($result['code'] == "success") {
                        $result['back_info'] = $this->add_pay_back($pay, 98, $price_back);
                        //$this->add_order_goods_number($remark);
                    }
                    $this->ajaxReturn($result);
                }
            }
            // 浦发银行
            if ($pay['bank'] == 10) {
                $pay_style = $pay['paystyle_id'];
                if ($pay_style == 1) {//微信原路退款
                    $d = M('merchants_pfpay')->where(array('merchant_id' => $mid))->getField('pay_style');
                    if ($d != '1') {
                        $this->ajaxReturn(array("code" => "error", "msg" => "D0通道不能退款"));
                    }
                    //if ($price_back != $price) $this->ajaxReturn(array("code" => "error", "msg" => "退款金额必须等于原有金额"));
                    if ($price_back > $price) $this->ajaxReturn(array("code" => "error", "msg" => "退款金额不能大于原有金额"));
                    $result = A("Pay/Barcodepfbank")->wx_pay_back($remark, $price_back);
                    if ($result['code'] == "success") {
                        $result['back_info'] = $this->add_pay_back($pay, 98, $price_back);
                        $this->add_order_goods_number($remark);
                    }
                    $this->ajaxReturn($result);
                }
                if ($pay_style == 2) {//支付宝原路退款
                    $d = M('merchants_pfpay')->where(array('merchant_id' => $mid))->getField('pay_style');
                    if ($d != '1') {
                        $this->ajaxReturn(array("code" => "error", "msg" => "D0通道不能退款"));
                    }
                    //if ($price_back != $price) $this->ajaxReturn(array("code" => "error", "msg" => "退款金额必须等于原有金额"));
                    if ($price_back > $price) $this->ajaxReturn(array("code" => "error", "msg" => "退款金额不能大于原有金额"));
                    $result = A("Pay/Barcodepfbank")->ali_pay_back($remark, $price_back);
                    if ($result['code'] == "success") {
                        $result['back_info'] = $this->add_pay_back($pay, 98, $price_back);
                        $this->add_order_goods_number($remark);
                    }
                    $this->ajaxReturn($result);
                }
            }
            //新大陆
            if ($pay['bank'] == 11) {
                //$pay_style = $pay['paystyle_id'];
                if ($price_back > $price) $this->ajaxReturn(array("code" => "error", "msg" => "退款金额不能大于原有金额"));
                $result = A("Pay/Barcodexdlbank")->pay_back($remark, $price_back);
                if ($result['code'] == "success") {
                    $result['back_info'] = $this->add_pay_back($pay, 98, $price_back);
                    $this->add_order_goods_number($remark);
                }
                $this->ajaxReturn($result);

            }
            //乐刷
            if ($pay['bank'] == 12) {
                if ($price_back != $price) $this->ajaxReturn(array("code" => "error", "msg" => "该笔订单只能全额退款(12)"));
                $result = A("Pay/Leshuabank")->refund($remark);
                if ($result['code'] == "success") {
                    $result['back_info'] = $this->add_pay_back($pay, 98, $price_back);
                    $this->add_order_goods_number($remark);
                }
                $this->ajaxReturn($result);

            }
            $this->ajaxReturn(array("code" => "error", "msg" => "EOF"));

        }
    }

    /**
     * 支付成功后更新库存
     * 传入订单唯一标识$order_sn
     * @param int $order_sn
     */
    private function add_order_goods_number($remark = 0)
    {
        if (!$remark) exit();
        $new_order_sn = $this->pays->where("remark='$remark'")->getField("new_order_sn");
        if ($new_order_sn) {
            $order_sn = $remark;
            $order_id = M("order")->where(array("order_sn" => $order_sn))->getField("order_id");
            $order_goods_list = M("order_goods")->where(array("order_id" => $order_id))->field("goods_id,goods_num")->select();
            if ($order_goods_list) {
                foreach ($order_goods_list as $k => $v) {
                    if ($v['goods_id'] && $v['goods_num']) M("goods")->where(array("goods_id" => $v['goods_id']))->setInc('goods_number', $v['goods_num']); //更新库存
                }
            }
        }
    }

    //退款成功添加到退款记录表
    protected function add_pay_back($pay_back, $mode, $price_back)
    {
        $this->payBack = M("pay_back");
        $pay_back['back_pid'] = $pay_back['id'];
        $pay_back['status'] = 5;
        $pay_back['price_back'] = $price_back;
        $pay_back['paytime'] = time();
        $pay_back['mode'] = $mode;
        $pay_back['bill_date'] = date('Ymd');
        $order = M('order')->where(array('order_sn' => $pay_back['remark']))->find();
        if ($order && $order['user_money']) {
            $card = M("screen_memcard_use")
                ->field('yue,card_id')
                ->where(array('card_code' => $order['card_code']))
                ->find();
            $ts["record_bonus"] = urlencode("退款返回储值");
            $ts['code'] = urlencode($order['card_code']);
            $ts['card_id'] = urlencode($card['card_id']);
            $ts['custom_field_value1'] = urlencode($card['yue'] + $order['user_money']);//会员卡余额
            $ts['notify_optional']['is_notify_custom_field1'] = true;
            $token = get_weixin_token();
            $msg = request_post('https://api.weixin.qq.com/card/membercard/updateuser?access_token=' . $token, urldecode(json_encode($ts)));
            $info = json_decode($msg, true);
            if ($info['errmsg'] == 'ok') {
                M('screen_memcard_use')->where(array('card_code' => $order['card_code']))->setField('yue', $card['yue'] + $order['user_money']);
            }
        }
        unset($pay_back['id']);
        $id = $this->payBack->add($pay_back);
        $back_info = $this->payBack->where("id=$id")->field('id,paystyle_id,checker_id,price_back as price,remark,status,paytime,bill_date,mode')->find();
        return $back_info;
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

    //微信快速购买
    public function quick_buy()
    {
        $this->add_log();
        ($openid = I('openid')) || $this->err('openid is empty');
        $card_id = I('card_id');
        ($encrypt_code = I('encrypt_code', '', 'trim')) || $this->err('encrypt_code is empty');
        $encrypt_code = str_replace(' ', '+', $encrypt_code);
        //查看用户id
        ($screen_memcard = M('screen_memcard')->where(array('card_id' => $card_id))->field('id,integral_dikou,max_reduce_bonus,credits_use,credits_discount,mid,discount_set,discount,level_set')->find());

        $card_code = $this->decrypt_code($encrypt_code);
        if ($card_code) {
            $memcard_use = M('screen_memcard_use')->where(array('card_code' => $card_code))->find();
        } else {
            ($mem = M('screen_mem')->where(array('openid' => $openid, 'userid' => $screen_memcard['mid']))->find()) || $this->err('screen_mem is not find');
            ($memcard_use = M('screen_memcard_use')->where(array('memcard_id' => $screen_memcard['id'], 'memid' => $mem['id']))->find()) || $this->err('screen_memcard_use is wrong');
        }
        $this->memcard_use = $memcard_use;
        //查看优惠券代码
        //查看积分
        //$this->memcard_use = M('screen_memcard_use')->where(array('card_id'=>$card_id,'memid'=>$mem['id']))->find();
        //$where1 = 'b.end_timestamp > '.time().' and b.begin_timestamp < '.time().' and b.total_price <= '.$price.' and b.mid = '.$merchants_id;
        $merchants = M('merchants')->where(array('uid' => $screen_memcard['mid']))->find();

        //我的优惠券
        $ids = M('screen_coupons')->where(array('card_type' => 'GENERAL_COUPON', 'mid' => $merchants['id'], 'status' => 3, 'end_timestamp' => array('gt', time()), 'begin_timestamp' => array('lt', time())))->getField('id', true);


        $user_coupons = array();
        if ($ids) {
            $user_coupons = M('screen_user_coupons')->alias('a')->field('a.id,b.title,b.de_price,b.total_price')->join('ypt_screen_coupons b ON a.coupon_id = b.id')->where(array('coupon_id' => array('in', $ids), 'fromname' => $openid, 'a.status' => 1, 'begin_timestamp' => array('lt', time()), 'end_timestamp' => array('gt', time())))->select();
        }
        $discount = 10;

        if ($screen_memcard['discount_set'] == 1) {
            if ($screen_memcard['level_set'] == 0) {
                if (!$screen_memcard['discount'] || $screen_memcard['discount'] == '0.00') {
                    $discount = 10;
                } else {
                    $discount = $screen_memcard['discount'];
                }
            } else {
                $discount = M('screen_memcard_level')->where(array('c_id' => $screen_memcard['id'], 'level' => $memcard_use['level']))->getField('level_discount');
            }
        }

        $this->discount = $discount;
        //	var_dump($screen_memcard['discount_set']);
        //	var_dump($user_coupons);
        $this->user_coupons = $user_coupons;
        //查看会员卡积分规则
        $this->have_integral = $memcard_use['card_balance'];
        $max_reduce_bonus = $screen_memcard['max_reduce_bonus'];
        if ($this->have_integral >= $max_reduce_bonus) {
            $this->have_integral = $max_reduce_bonus;
        }
        //查看会员卡
        $this->merchants = $merchants;
        $this->screen_memcard = $screen_memcard;
        $this->openid = $openid;
        $this->card_id = $card_id;
        $this->display();

    }

    public function quick_buy_ajax()
    {
        ($uid = I('uid')) || $this->err('uid is empty');
    }

    //生成订单
    public function create_order()
    {
        $this->add_log();
        //优惠券id
        $no_price = I('no_price');
        $order_price = $price = I('price');
        ($openid = I('openid')) || $this->err('openid is empty');
        ($card_id = I('card_id')) || $this->err('card_id is empty');
        $price >= $no_price || $this->err('不参与优惠价格不能大于消费金额');
        $is_discount = I('is_discount', 0);
        $coupons_id = I('coupons_id');
        $coupons_price = 0;
        $yue_price = 0;
        $credits_discount = $credits_discount_price = 0;

        //查会员卡信息
        ($screen_memcard = M('screen_memcard')->where(array('card_id' => $card_id))->field('id,integral_dikou,credits_use,credits_discount,mid,discount_set,level_set,discount')->find());
        ($mem = M('screen_mem')->where(array('openid' => $openid, 'userid' => $screen_memcard['mid']))->find()) || $this->err('screen_mem is not find');
        ($mid = M('merchants')->where(array('uid' => $screen_memcard['mid']))->getField('id')) || $this->err('快速买单目前仅支持商家会员卡,联名卡快速买单正在研发,敬请期待!');

        $screen_memcard_use = M('screen_memcard_use')->where(array('memcard_id' => $screen_memcard['id'], 'memid' => $mem['id']))->find();

        $yh_price = $price - $no_price;
        $discount_price = 0;
        $discount = 10;
        if ($yh_price) {
            //会员折扣
            if ($is_discount) {
                if ($screen_memcard['discount_set'] == 1) {
                    if ($screen_memcard['level_set'] == 0) {
                        $discount = $screen_memcard['discount'];
                    } else {
                        //var_dump($screen_memcard['id']);
                        //查看折扣
                        ($memcard_level = M('screen_memcard_level')->where(array('c_id' => $screen_memcard['id'], 'level' => $screen_memcard_use['level']))->order('level desc')->find()) || $this->err('memcard_level is wrong');
                        if ($memcard_level['level_discount'] > 0) {
                            $discount = $memcard_level['level_discount'];
                        } else {
                            $discount = 10;
                        }
                        //会员折扣
                    }
                    $discount_price = (10 - $discount) * $yh_price / 10;
                    $discount_price = sprintf("%.2f", $discount_price);
                    $yh_price -= $discount_price;
                }
            }

            //优惠券
            if ($coupons_id > 0) {
                if (!$coupons = M('screen_user_coupons')->where(array('id' => $coupons_id, 'fromname' => $openid))->find()) {
                    $this->err('没有找到该优惠券');
                }
                //查看coupons是否属于这个店铺
                $screen_coupons = M('screen_coupons')->where(array('id' => $coupons['coupon_id']))->find();
                if ($coupons['status'] == 0) {
                    $this->err('该优惠券已经使用');
                }
                if ($screen_coupons['begin_timestamp'] > time()) {
                    $this->err('该优惠券还没有到使用时间');
                }
                if ($screen_coupons['end_timestamp'] < time()) {
                    $this->err('该优惠券已经过期了');
                }
                if ($screen_coupons['mid'] !== $mid) {
                    $this->err('该优惠券不属于这个店铺');
                }
                if ($yh_price >= $screen_coupons['total_price']) {
                    $yh_price -= $screen_coupons['de_price'];
                    $coupons_price = $screen_coupons['de_price'];
                } else {
                    $this->err('不满足使用优惠券');
                }
            }
            //积分抵扣
            if (I('is_jifen', 0)) {
                if ($screen_memcard['credits_discount'] > 0 && $screen_memcard['credits_use'] > 0) {
                    //扣除积分
                    while (($yh_price - $screen_memcard['credits_discount']) >= 0 && ($screen_memcard_use['card_balance'] - $screen_memcard['credits_use']) >= 0) {
                        $yh_price -= $screen_memcard['credits_discount'];
                        $screen_memcard_use['card_balance'] -= $screen_memcard['credits_use'];
                        $credits_discount_price += $screen_memcard['credits_discount'];
                        $credits_discount += $screen_memcard['credits_use'];
                    }
                }
            }
        }

        $price = $yh_price + $no_price;
        //余额
        if (I('is_yue', 0) && $screen_memcard_use['yue'] > 0) {
            if ($price - $screen_memcard_use['yue'] > 0) {
                $yue_price = $screen_memcard_use['yue'];
                $price -= $screen_memcard_use['yue'];
            } else {
                $yue_price = $price;
                $price = 0;
            }
        }
        $data['discount_price'] = $discount_price;
        $data['discount'] = $discount;
        $data['order_sn'] = date('YmdHis') . rand(100000, 999999) . 'q';
        $data['uid'] = $mem['id'];
        $data['mid'] = $mid;
        $data['yue_price'] = $yue_price;
        $data['memcard_id'] = $screen_memcard_use['id'];
        $data['order_price'] = $order_price;
        $data['price'] = $price;
        //$data['no_price'] = $no_price;
        $data['add_time'] = $data['update_time'] = time();
        $data['coupons_id'] = $coupons_id;
        $data['coupons_price'] = $coupons_price;
        $data['credits'] = $credits_discount;
        $data['credits_price'] = $credits_discount_price;

        //查看积分是否足够
        $order_id = M('quick_pay')->add($data);

        //开始生成签名
        if ($data['price']) {
            $sign = $this->create_sign($order_id, $openid);
            $res['param'] = $sign;
            $res['order_sn'] = $data['order_sn'];
            $this->succ($res);
        } else {
            $data = $data['order_sn'];
            $this->succ($data, 'pay_pass');
        }
    }

    public function pay_succ()
    {
        ($order_sn = I('order_sn')) || $this->err('order_sn is empty');
        ($openid = I('openid')) || $this->err('openid is empty');
        ($info = M('quick_pay')->where(array('order_sn' => $order_sn))->find()) || $this->err('not find info');
        $merchant = M('merchants')->where(array('id' => $info['mid']))->field('merchant_name,base_url')->find();
        $merchant_name = $this->shortName($merchant['merchant_name']);
        $ad = $this->ad($info['mid']);
        if (!$ad) {
            $ad[0]['url'] = 'http://m.hz41319.com/wei/index.php';
            $ad[0]['thumb'] = "./themes/simplebootx/Public/pay/img/img1.jpg";
        }
        $coupon = $this->coupon($info['mid'], $info['price'] + $info['yue_price'], 5, $openid);
        $dePrice = $info['coupons_price'] + $info['credits_price'] + $info['discount_price'];
        $this->assign('price', $info['price'] + $info['yue_price']);
        $this->assign('wxprice', $info['price'] ?: 0);
        $this->assign('total_amount', $info['order_price']);
        $this->assign('openid', $openid);
        $this->assign('info', $info);
        $this->assign('merchant_name', $merchant_name);
        $this->assign('ad', $ad);
        $this->assign('pay_time', $info['update_time']);
        $this->assign('coupon', $coupon);
        $this->assign('dePrice', $dePrice);
        $this->assign('logo', $merchant['base_url']);
        $this->assign('mid', $info['mid']);
        $this->assign('yue', $info['yue_price'] ?: 0);
        $this->display();
    }

    //判断消费金额是否能够领取优惠券
    public function coupon($mid, $price, $count, $openid)
    {
        $now = time();
        $coupon = M('screen_coupons')
            ->where("card_type='GENERAL_COUPON' and mid=$mid and auto_price<=$price and status=3 and quantity>0 and is_auto=2 and begin_timestamp<=$now and end_timestamp>=$now")
            ->limit($count)
            ->select();
        if ($coupon) {
            //判断是否已经领取过
            foreach ($coupon as $k => $v) {
                $map['card_id'] = $v['card_id'];
                $map['fromname'] = $openid;
                $is_use = M('screen_user_coupons')->where($map)->count();
                if ($is_use > 0) {
                    unset($coupon[$k]);
                }
            }
            return count($coupon);
        } else {
            return 0;
        }
    }

    //获取商户广告
    public function ad($mid)
    {
        if ($mid != 0) {
            $agent_id = M('merchants_users mu')->join('__MERCHANTS__ m on mu.id=m.uid')->where("m.id=$mid")->getField('mu.agent_id');
            $ad = M('adver')->alias('a')
                ->field("a.url,a.thumb")
                ->join("join __MERCHANTS_USERS__ mu on a.muid=mu.id")
                ->where("mu.id != 1 and mu.id=$agent_id and a.status=1 and road=2 and callstyle=2")
                ->order("sort desc")
                ->limit(3)
                ->select();
        } else {
            $ad = array();
        }

        if (count($ad) < 3) {
            $limit = 3 - count($ad);
            $ypt_ad = M('adver')
                ->field("url,thumb,intro")
                ->where("is_ypt=1 and status=1 and road=2 and callstyle=2")
                ->order("sort desc")
                ->limit($limit)
                ->select();
            if ($ypt_ad) {
                foreach ($ypt_ad as $k => $v) {
                    array_push($ad, $ypt_ad[$k]);
                }
            }
        }
        return $ad;
    }

    //缩短商户名称
    public function shortName($merchant)
    {
        if (strpos($merchant, "镇")) {
            $merchant_name = substr(strstr($merchant, '镇'), 3);
        } elseif (strpos($merchant, "区")) {
            $merchant_name = substr(strstr($merchant, '区'), 3);
        } elseif (strpos($merchant, "县")) {
            $merchant_name = substr(strstr($merchant, '县'), 3);
        } elseif (strpos($merchant, "市")) {
            $merchant_name = substr(strstr($merchant, '市'), 3);
        } elseif (strpos($merchant, "省")) {
            $merchant_name = substr(strstr($merchant, '省'), 3);
        } else {
            $merchant_name = $merchant;
        }
        return $merchant_name;
    }

    public function pay_by_password()
    {
        $this->add_log();
        ($order_sn = I('order_sn')) || $this->err('order_sn is wrong');
        ($password = I('password')) || $this->err('password is empty');
        ($openid = I('openid')) || $this->err('openid is empty');
        ($card_id = I('card_id')) || $this->err('card_id is empty');
        //查询用户
        ($screen_memcard = M('screen_memcard')->where(array('card_id' => $card_id))->field('id,integral_dikou,credits_use,credits_discount,mid')->find()) || $this->err('card_id is empty');
        ($mem = M('screen_mem')->where(array('openid' => $openid, 'userid' => $screen_memcard['mid']))->getField('id')) || $this->err('screen_mem is not find');
        //查会员卡信息
        $screen_memcard_use = M('screen_memcard_use')->where(array('memcard_id' => $screen_memcard['id'], 'memid' => $mem))->find();

        (md5($password . 'tiancaijing') == $screen_memcard_use['pay_pass']) || $this->err('密码不对');

        ($data = M('quick_pay')->where(array('order_sn' => $order_sn, 'uid' => $mem))->find()) || $this->err('order is not find');

        if ($this->common($data['order_sn'], 0, '11111111', 10) == 'succ') {
            $this->succ();
        }

    }

    public function create_sign($order_id, $openid)
    {
        $order_id || $this->err('order_id is empty');
        ($order = M('quick_pay')->where(array('id' => $order_id))->find()) || $this->err('quick_pay is empty');
        //$pay_type = $param['pay_type'];
        ($res = M('merchants_cate')->where(array('merchant_id' => $order['mid'], 'status' => 1, 'checker_id' => 0))->find()) || $this->err('merchants is empty');
        //保存到订单中
        M('quick_pay')->where(array('id' => $order_id))->setField('cate_id', $res['id']);

        switch ($res['wx_bank']) {
            // 微众
            case 1:
                header("Content-type:text/html;charset=utf-8");
                vendor('Wzpay.Wzczpay');
                $wzPay = new \Wzczpay();
                $wzPay->setParameter('sub_openid', $openid);
                $wzPay->setParameter('mch_id', $res['wx_mchid']);
                $wzPay->setParameter('body', '充值');
                $wzPay->setParameter('out_trade_no', $order['order_sn']);
                $wzPay->setParameter('goods_tag', $order['order_sn']);
                $wzPay->setParameter('total_fee', $order['price'] * 100);
                $wzPay->setParameter('notify_url', 'https://sy.youngport.com.cn/index.php?g=api&m=base&a=wz_notify');
                $returnData = $wzPay->getParameters();
                return $returnData;
                break;
            //民生银行
            case 2:
                $pay['action'] = 'wallet/trans/jsSale';
                $pay['version'] = '2.0';
                $pay['reqTime'] = date("YmdHis");
                $pay['appId'] = 'wx3fa82ee7deaa4a21';
                $pay['uuid'] = $openid;
                $pay['orderId'] = $order['order_sn'];
                $pay['reqId'] = date("YmdHis") . rand(1000, 9999) . '251';
                $pay['deviceId'] = 'payuser';//终端号
                $pay['transTimeOut'] = '1440';
                $pay['orderSubject'] = '快速购买';
                $pay['orderDesc'] = '快速购买';//订单描述
                $pay['totalAmount'] = $order['price'] * 100;
                $pay['bankCardLimit'] = 2;
                $pay['currency'] = "CNY";
                $pay['acquirerType'] = 'wechat';
                $pay['operatorId'] = "POS 操作员";
                $pay['custId'] = $res['wx_mchid'];
                $pay['notifyUrl'] = 'http://sy.youngport.com.cn/notify/quick_pay_ms.php';
                $pay['cost_rate'] = $this->cost_rate_1($res['wx_mchid'], 1);
                $pay['orderDesc'] = '付款';
                $data = json_encode($pay);
                $data = "[" . $data . "]";
                $res = $this->rsaSign($data, $this->private_key);
                $result = $this->httpRequst($this->url, $data, $res, $this->apikey);
                $data = json_decode($result);
                $result = $data->body->payInfo;
                break;
            //围餐
            case 3:
                // 得到输入的金额和商户的ID
                header("Content-type:text/html;charset=utf-8");
                Vendor('WxPayPubHelper.WxPayPubHelper');
                $jsApi = new \JsApi_pub();
                $unifiedOrder = new \UnifiedOrder_pub();
                $unifiedOrder->setParameter("openid", $openid);//openid和sub_openid可以选传其中之一
                //$unifiedOrder->setParameter("sub_openid", "$sub_openid");//子商户appid下的唯一标识
                $unifiedOrder->setParameter("body", '充值');//商品描述
                //自定义订单号，
                $unifiedOrder->setParameter("out_trade_no", $order['order_sn']);//商户订单号
                $unifiedOrder->setParameter("total_fee", $order['price'] * 100);//总金额
                $unifiedOrder->setParameter("notify_url", 'https://sy.youngport.com.cn/notify/quick_pay_wc.php');//通知地址
                $unifiedOrder->setParameter("trade_type", "JSAPI");//交易类型
                $unifiedOrder->setParameter("sub_mch_id", $res['wx_mchid']);//子商户号服务商必填
                $prepay_id = $unifiedOrder->getPrepayId();
                $jsApi->setPrepayId($prepay_id);
                $jsApiParameters = $jsApi->getParameters();
                return $jsApiParameters;
                break;
            //招商
            case 4:
                $bank['mch_id'] = $res['alipay_partner'];
                $bank['sub_appid'] = 'wx3fa82ee7deaa4a21';
                $bank['nonce_str'] = time() . rand(10000, 99999) . '251';
                $bank['body'] = '测试下';
                $bank['reqId'] = date("YmdHis") . rand(1000, 9999) . '251';
                $bank['out_trade_no'] = $order['order_sn'];
                $bank['total_fee'] = $order['price'] * 100;
                $bank['spbill_create_ip'] = $_SERVER["REMOTE_ADDR"];
                $bank['mch_pay_key'] = $res['alipay_public_key'];
                $bank['notify_url'] = "http://sy.youngport.com.cn/notify/quick_pay_zs.php";
                $bank['time_start'] = date("YmdHis");
                $bank['trade_type'] = 'JSAPI';
                $bank['sub_openid'] = $openid;
                $res = $this->weixin_c_b_pay($bank);
                //xml 转数据
                $res = $this->xmlToArray($res);
                $result = $res['js_prepay_info'];
                break;
            //新业银行
            case 7:
                $param['service'] = 'pay.weixin.jspay';
                $param['charset'] = 'UTF-8';
                $param['mch_id'] = $res['wx_mchid'];
                $param['out_trade_no'] = $order['order_sn'];
                $param['body'] = '订单号：' . $order['order_sn'];
                $param['sub_openid'] = $openid;
                $param['sub_appid'] = 'wx3fa82ee7deaa4a21';
                //$param['mch_create_ip'] = $data['spbill_create_ip'] ? $data['spbill_create_ip'] : '127.0.0.1';
                $param['mch_create_ip'] = $_SERVER["REMOTE_ADDR"];
                $param['is_raw'] = "1";
                $param['total_fee'] = (int)($order['price'] * 100);
                $param['notify_url'] = 'http://sy.youngport.com.cn/notify/quick_pay_xy.php';
                $param['nonce_str'] = date("YmdHis") . rand(1000, 9999) . '251';
                $param['sign'] = $this->getSignVeryfy_pay($param, $res['wx_key']);
                $xmlData = $this->arrayToXml($param);
                $url = "https://pay.swiftpass.cn/pay/gateway";
                $res = $this->httpRequst_pay($url, $xmlData);
                $res = $this->xmlToArray($res);
                return $res['pay_info'];
                break;
            //东莞中信
            case 10:
                $param['service'] = 'pay.weixin.jspay';
                $param['charset'] = 'UTF-8';
                $param['mch_id'] = $res['wx_mchid'];
                $param['out_trade_no'] = $order['order_sn'];
                $param['body'] = '订单号：' . $order['order_sn'];
                $param['sub_openid'] = $openid;
                $param['sub_appid'] = 'wx3fa82ee7deaa4a21';
                //$param['mch_create_ip'] = $data['spbill_create_ip'] ? $data['spbill_create_ip'] : '127.0.0.1';
                $param['mch_create_ip'] = $_SERVER["REMOTE_ADDR"];
                $param['is_raw'] = "1";
                $param['total_fee'] = (int)($order['price'] * 100);
                $param['notify_url'] = 'http://sy.youngport.com.cn/notify/quick_pay_pf.php';
                $param['nonce_str'] = date("YmdHis") . rand(1000, 9999) . '251';
                $param['sign'] = $this->getSignVeryfy_pay($param, $res['wx_key']);
                $xmlData = $this->arrayToXml($param);
                $url = "https://pay.swiftpass.cn/pay/gateway";
                $res = $this->httpRequst_pay($url, $xmlData);
                $res = $this->xmlToArray($res);
                return $res['pay_info'];
                break;
            //乐刷
            case 12:
                $into_data = M('merchants_leshua')->where("m_id=" . $order['mid'])->find();

                $param['service'] = 'get_tdcode';
                $param['pay_way'] = 'WXZF';
                $param['merchant_id'] = $res['wx_mchid'];//商户号
                $param['third_order_id'] = $order['order_sn'];//商户订单号
                $param['amount'] = (int)($order['price'] * 100);//金额
                $param['jspay_flag'] = 1;
                $param['sub_openid'] = $openid;
                $param['client_ip'] = $into_data['ip_address'] ?: $_SERVER['REMOTE_ADDR'];//IP
//        $param['client_ip'] = '61.191.122.83';//IP 61.191.122.83,113.27.82.122,117.136.4.152
                $param['notify_url'] = 'https://sy.youngport.com.cn/notify/quick_pay_ls.php';//回调地址
                $param['t0'] = $into_data['is_t0'];
                $param['body'] = "向" . $res['jianchen'] . "支付￥{$order['price']}元";
                $param['nonce_str'] = $this->getNonceStr();//随机字符串
                $param['sign'] = $this->getSignVeryfy_pay($param, $into_data['key']);//签名
                $url = "https://mobilepos.yeahka.com/cgi-bin/lepos_pay_gateway.cgi";
                add_log(json_encode($param));
                $res = $this->httpRequst_pay($url, $param);

                $res = $this->xmlToArray($res);
                add_log(json_encode($res));
                return $res['jspay_info'];
                break;
            default:
                $this->error('不存在该支付方式');
                break;
        }
        //微众银行
        //return $result;
    }

    private function getNonceStr()
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < 32; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }

        return strtoupper($str);
    }

    //微信支付用户扫商家接口
    private function weixin_c_b_pay($data)
    {
        $param['mch_id'] = $data['mch_id'];//商户号，由UCHANG分配
        //否
        if (isset($data['sub_appid']) && !empty($data['sub_appid'])) {
            $param['sub_appid'] = $data['sub_appid'];//商户微信公众号appid,app支付时,为在微信开放平台上申请的APPID
        }
        //否
        if (isset($data['device_info']) && !empty($data['device_info'])) {
            $param['device_info'] = $data['device_info'];//终端设备号(门店号或收银设备ID)，注意：PC网页或公众号内支付请传“WEB”
        }
        //是
        $param['nonce_str'] = $data['nonce_str'];//随机字符串，不长于32位
        //是
        $param['body'] = $data['body'];//商品描述
        //否
        if (isset($data['detail']) && !empty($data['detail'])) {
            $param['detail'] = $data['detail'];//商品详细列表，使用Json格式，传输签名前请务必使用CDATA标签将JSON文本串保护起来。goods_detail 服务商必填 []：└ goods_id String 必填 32 商品的编号└ wxpay_goods_id String 可选 32 微信支付定义的统一商品编号└ goods_name String 必填 256 商品名称└ quantity Int 必填 商品数量└ price Int 必填 商品单价，单位为分└ goods_category String 可选 32 商品类目ID└ body String 可选 1000 商品描述信息
        }
        //否
        if (isset($data['attach']) && !empty($data['attach'])) {
            $param['attach'] = $data['attach'];//附加数据，在查询API和支付通知中原样返回，该字段主要用于商户携带订单的自定义数据
        }
        //是
        $param['out_trade_no'] = $data['out_trade_no'];//商户系统内部的订单号,32个字符内、可包含字母, 其他说明见商户订单号
        //是
        $param['fee_type'] = "CNY";//符合ISO 4217标准的三位字母代码，默认人民币：CNY
        //是
        $param['total_fee'] = $data['total_fee'];//总金额，以分为单位，不允许包含任何字、符号
        //是
        $param['spbill_create_ip'] = $data['spbill_create_ip'];//APP和网页支付提交用户端ip，Native支付填调用微信支付API的机器IP。
        //是
        // $param['time_start']=date("YmdHis");//订单生成时间，格式为yyyyMMddHHmmss，如2009年12月25日9点10分10秒表示为20091225091010
        // //是
        // $param['time_expire']=date("YmdHis");//如上
        //否
        if (isset($data['goods_tag']) && !empty($data['goods_tag'])) {
            $param['goods_tag'] = $data['goods_tag'];//商品标记，代金券或立减优惠功能的参数
        }
        //是
        $param['notify_url'] = $data['notify_url'];//接收微信支付异步通知回调地址，通知url必须为直接可访问的url，不能携带参数。
        //是
        $param['trade_type'] = $data['trade_type'];//取值如下：JSAPI，NATIVE，APP
        //否
        if (isset($data['product_id']) && !empty($data['product_id'])) {
            $param['product_id'] = $data['product_id'];//trade_type=NATIVE，此参数必传。此id为二维码中包含的商品ID，商户自行定义。
        }
        //否
        if (isset($data['limit_pay']) && !empty($data['limit_pay'])) {
            $param['limit_pay'] = $data['limit_pay'];//no_credit–指定不能使用信用卡支付
        }
        //否
        if (isset($data['sub_openid']) && !empty($data['sub_openid'])) {
            $param['sub_openid'] = $data['sub_openid'];//trade_type=JSAPI，此参数必传，用户在子商户appid下的唯一标识。openid和sub_openid可以选传其中之一，如果选择传sub_openid,则必须传sub_appid。
            $param['openid'] = $data['openid'];
        }
        if (isset($data['wxapp']) && !empty($data['wxapp'])) {
            //否
            $param['wxapp'] = $data['wxapp'];//true–小程序支付；此字段控制 js_prepay_info 的生成，为true时js_prepay_info返回小程序支付参数，否则返回公众号支付参数
        }
        //获取签名
        $param['sign'] = $this->getSignVeryfy_pay($param, $data['mch_pay_key']);
        //转换成xml格式post提交数据
        $xmlData = $this->arrayToXml($param);
        $url = "http://api.cmbxm.mbcloud.com/wechat/orders";
        $result = $this->httpRequst_pay($url, $xmlData);
        return $result;
    }

    //数组转xml
    private function arrayToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }

    //xml转数组
    private function xmlToArray($xml)
    {
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $values;
    }

    //支付接口统一签名
    private function getSignVeryfy_pay($para_temp, $paykey)
    {
        //除去待签名参数数组中的空值和签名参数
        $para_filter = $this->paraFilter($para_temp);
        //对待签名参数数组排序
        $para_sort = $this->argSort($para_filter);
        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $this->createLinkstring($para_sort);
        //拼接apikey
        $prestr = $prestr . "&key=" . $paykey;
        //MD5 转大写
        $prestr = strtoupper(md5($prestr));
        return $prestr;
    }

    private function paraFilter($para)
    {
        $para_filter = array();
        while (list ($key, $val) = each($para)) {
            if ($key == "sign" || $key == "sign_type" || $val == "") continue;
            else    $para_filter[$key] = $para[$key];
        }
        return $para_filter;
    }

    //数组排序
    private function argSort($para)
    {
        ksort($para);
        reset($para);
        return $para;
    }

    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     * @param $para 需要拼接的数组
     * return 拼接完成以后的字符串
     */
    private function createLinkstring($para)
    {
        $arg = "";
        while (list ($key, $val) = each($para)) {
            $arg .= $key . "=" . $val . "&";
        }
        //去掉最后一个&字符
        $arg = substr($arg, 0, count($arg) - 2);

        //如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }

        return $arg;
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

    public function wz_notify()
    {
        header("Content-type:text/html;charset=utf-8");
        vendor('Wzpay.Wzczpay');
        $wzPay = new \Wzczpay();
        // 获取json
        $json_str = file_get_contents('php://input', 'r');
        $this->add_log($json_str);
        // 转成php数组
        $data = json_decode($json_str, true);
        // 保存原sign
        $data_sign = $data['sign'];
        //获取用户key
        $wzPay->key = M('merchants_cate')->where(array('wx_mchid' => $data['mch_id']))->getField('wx_key');
        // sign不参与签名
        unset($data['sign']);
        $sign = $wzPay->getSign($data);
        $this->add_log($sign);
        // 判断签名是否正确  判断支付状态
        if ($sign === $data_sign && $data['status'] === '0' && $data['result_code'] === '0') {
            $this->common($data['out_trade_no'], $data['total_fee'] / 100, $data['transaction_id'], 1);
        } else {
            $this->add_log($sign);
        }
    }

    public function xy_notify()
    {
        $data = $this->xmlToArray(file_get_contents('php://input', 'r'));
        $this->add_log(json_encode($data));
        if ($data['status'] == 0) {
            $this->common($data['out_trade_no'], $data['total_fee'] / 100, $data['transaction_id'], 7);
        }
    }

    public function pf_notify()
    {
        $data = $this->xmlToArray(file_get_contents('php://input', 'r'));
        $this->add_log(json_encode($data));
        if ($data['status'] == 0) {
            $this->common($data['out_trade_no'], $data['total_fee'] / 100, $data['transaction_id'], 10);
        }
    }

    public function ls_notify()
    {
        $data = $this->xmlToArray(file_get_contents('php://input', 'r'));
        $this->add_log(json_encode($data));
        if ($data['error_code'] == '0' && $data['status'] == '2') {
            $this->common($data['third_order_id'], $data['amount'] / 100, $data['leshua_order_id'], 12);
        }
    }

    public function wc_notify()
    {
        Vendor('WxPayPubHelper.WxPayPubHelper');
        //使用通用通知接口
        $notify = new \Notify_pub();
        //存储微信的回调
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        $this->add_log($xml);
        $notify->saveData($xml);
        if ($notify->checkSign() == FALSE) {
            $return = array('return_code' => "FAIL", 'return_msg' => "签名失败");
            file_put_contents('./data/log/weixin/' . date("Y_m_") . 'weixin_pay.log', date("Y-m-d H:i:s") . '签名失败' . $xml . PHP_EOL, FILE_APPEND | LOCK_EX);
        } else {
            $data = $notify->data;
            $out_trade_no = $data["out_trade_no"];//回调的订单号
            if ($data['return_code'] == 'SUCCESS' && $data['result_code'] == 'SUCCESS') {
                //读取订单信息
                $this->common($data['out_trade_no'], $data['total_fee'] / 100, $data['transaction_id'], 3);
            } else {
                A("Pay/Barcode")->push_pay_message($out_trade_no);
                file_put_contents('./data/log/weixin/' . date("Y_m_") . 'weixin_pay.log', date("Y-m-d H:i:s") . '重复回调或不存在' . json_encode($data) . '重复回调或不存在' . PHP_EOL, FILE_APPEND | LOCK_EX);
                $return = array('return_code' => "FAIL");
            }
        }
        $returnXml = $notify->returnNotifyXml($return);
        echo $returnXml;
    }

    public function ms_notify()
    {
        //验签
        $this->add_log();
        //$post = M('log')->where(array('id'=>'6326'))->getField('post');

        //$post = json_decode($post,true);
        $post = $_POST;
        $data = json_decode($post['body'], true);

        //初步代表验证通过
        if (substr($data['reqId'], strlen($data['reqId']) - 3, 3) == '251') {
            $this->common($data['orderId'], $data['totalAmount'] / 100, $data['transId'], 2);
        }
    }

    public function zs_notify()
    {
        $this->add_log(file_get_contents('php://input', 'r'));
        $this->add_log(json_encode($this->xmlToArray(file_get_contents('php://input', 'r'))));
        $data = $this->xmlToArray(file_get_contents('php://input', 'r'));
        //暂时没有验证签名

        $this->common($data['out_trade_no'], $data['cash_fee'] / 100, $data['transaction_id'], 4);

        //初步代表验证通过
//				if(substr($data['reqId'],strlen($data['reqId'])-3,3) == '251'){
//					
//					
//				}

//				$json_str = file_get_contents('php://input', 'r');
//	        	$data=$this->xmlToArray($json_str);
    }

    //支付成功而且验证成功
    public function common($order_sn, $price, $transid, $bank)
    {
        //var_dump($order_sn,$price,$transid,$bank);
        /*if (!($order_sn && $price && $transid && $bank)) {
            return false;
        }*/
        $quick_pay = M('quick_pay');
        ($order = M('quick_pay')->where(array('order_sn' => $order_sn))->find()) || $this->err('quick_buy is not find');

        if ($order['status'] != 0) {
            $this->err('该订单已经支付');
        }
        $time = time();
        //开启事务
        M()->startTrans();
        //更新订单状态
        $quick_pay->where(array('id' => $order['id']))->save(array('status' => 1, 'update_time' => $time));
        $order['memcard_id'] || $this->err('memcard_id is empty');
        ($card_use = M('screen_memcard_use')->where(array('id' => $order['memcard_id']))->find()) || $this->err('该会员卡不存在');
        $card = M('screen_memcard')->field('credits_set,expense,expense_credits,expense_credits_max')->where(array('id' => $card_use['memcard_id']))->find();
        $cardset = M('screen_cardset')->where(array('c_id' => $card_use['memcard_id']))->find();

        //送积分
        //查看是否开启充值送积分
        /*if ($card['recharge_send_integral'] == 1) {
            $integral_price = $order['price'];
        } else {
            $integral_price = $order['price'] + $order['yue_price'];
        }*/

        //$integral_price && $integral = (int)($integral_price / $card['recharge']) * $card['recharge_send'];
        if ($card['credits_set'] == 1) {
            $integral = floor(($price + $order['yue_price']) / $card['expense']) * $card['expense_credits'];
            //如果送的积分大于最多可送的分，则赠送最大积分
            if ($integral > $card['expense_credits_max']) {
                $integral = $card['expense_credits_max'];
            }
        }
        $token = get_weixin_token();
        $screen_memcard_use['card_balance'] = $card_use['card_balance'];
        $screen_memcard_use['yue'] = $card_use['yue'];
        if ($integral > 0) {
            if ($integral > $card['expense_credits_max']) $integral = $card['expense_credits_max'];
            $screen_memcard_use['card_amount'] = $card_use['card_amount'] + $integral;
            $screen_memcard_use['card_balance'] += $integral;
            //记录推送信息
            $ts['add_bonus'] = $integral;
            $ts['code'] = $card_use['card_code'];
            $ts['card_id'] = $card_use['card_id'];
            $ts['record_bonus'] = urlencode('快速买单送积分');
            $ts_res = request_post('https://api.weixin.qq.com/card/membercard/updateuser?access_token=' . $token, urldecode(json_encode($ts)));
            $ts_result = json_decode($ts_res, true);
            $ts_result['errcode'] == 0 ? $ts_result_msg = 1 : $ts_result_msg = 0;
            //记录日志
            M('screen_memcard_log')->add(array('add_time' => $time, 'update_time' => $time, 'value' => $integral, 'balance' => $screen_memcard_use['card_balance'], 'ts' => json_encode($ts), 'msg' => $ts_res, 'ts_status' => $ts_result_msg, 'order_sn' => $order_sn, 'code' => $card_use['card_code'], 'record_bonus' => '快速买单送积分'));
        }

        //扣除积分
        if ($order['credits']) {
            $screen_memcard_use['card_balance'] -= $order['credits'];
            $ts['add_bonus'] = -$order['credits'];
            $ts['code'] = $card_use['card_code'];
            $ts['card_id'] = $card_use['card_id'];
            $ts['record_bonus'] = urlencode('快速买单使用积分');
            $ts_res = request_post('https://api.weixin.qq.com/card/membercard/updateuser?access_token=' . $token, urldecode(json_encode($ts)));
            $ts_result = json_decode($ts_res, true);
            $ts_result['errcode'] == 0 ? $ts_result_msg = 1 : $ts_result_msg = 0;
            M('screen_memcard_log')->add(array('add_time' => $time, 'update_time' => $time, 'value' => -$order['credits'], 'balance' => $screen_memcard_use['card_balance'], 'ts' => json_encode($ts), 'msg' => $ts_res, 'ts_status' => $ts_result_msg, 'order_sn' => $order_sn, 'code' => $card_use['card_code'], 'record_bonus' => '快速买单使用积分'));
        }

        //修改余额
        if ($order['yue_price']) {
            //记录日志
            $screen_memcard_use['yue'] -= $order['yue_price'];
            $yue_log['yue'] = $screen_memcard_use['yue'];
            $yue_log['add_time'] = $time;
            $yue_log['value'] = -$order['yue_price'];
            $yue_log['remark'] = '快速买单消费' . $order['yue_price'];
            $yue_log['uid'] = $order['uid'];
            $yue_ts['custom_field_value1'] = urldecode((string)$screen_memcard_use['yue']);
            $yue_ts['code'] = $card_use['card_code'];
            $yue_ts['card_id'] = $card_use['card_id'];

            $yue_ts = json_encode($yue_ts);
            $yue_log['ts'] = $yue_ts;
            M('user_yue_log')->add($yue_log);

        }

        //修改积分和一份
        M('screen_memcard_use')->where(array('id' => $order['memcard_id']))->save($screen_memcard_use);

        if ($order['coupons_id']) {
            //$openid = db::name('screen_mem')->where('id',$order->mid)->value('openid');
            //查看优惠券信息
            $coupons = M('screen_user_coupons')->where(array('id' => $order['coupons_id']))->find();
            if ($coupons) {
                //修改优惠券
                M('screen_user_coupons')->where(array('id' => $order['coupons_id']))->setField('status', 0);
                //获得小程序token
                $data['code'] = $coupons['usercard'];
                //开始核销优惠券
                $status = request_post('https://api.weixin.qq.com/card/code/consume?access_token=' . $token, json_encode($data));
                $this->add_log(json_encode($status));
            }
        }
        //记录流水
        if (!$this->pay_model->where(array('order_id' => $order['id'], 'mode' => 10))->find()) {
            $pay['merchant_id'] = $order['mid'];
            //查询openid
            $pay['customer_id'] = $order['uid'];
            $pay['paystyle_id'] = 1;
            $pay['order_id'] = $order['id'];
            $pay['mode'] = 10;
            $pay['price'] = $price;
            $pay['cate_id'] = $order['cate_id'];
            $pay['remark'] = $order_sn;
            $pay['add_time'] = $order['add_time'];
            $pay['paytime'] = time();
            $pay['bill_date'] = date('Ymd');
            $pay['new_order_sn'] = $order_sn;
            $pay['transId'] = $transid;
            $pay['status'] = 1;
            $pay['bank'] = $bank;
            $pay['cost_rate'] = get_rate($bank, $order['mid']);
            $this->pay_model->add($pay);
        }
        M()->commit();
        $this->ts();
        R('cz/mem_card1', array('card_id' => $order['memcard_id']));

        //request_post('http://sy.youngport.com.cn/index.php?s=api/base/ts');
        //开始推送
        //$msg = request_post('https://api.weixin.qq.com/card/membercard/updateuser?access_token='.$token,json_encode($ts));
        //$msg = request_post('https://api.weixin.qq.com/card/membercard/updateuser?access_token='.$token,json_encode($ts));
        return 'succ';
    }

    public function ts()
    {

        $token = get_weixin_token();
        //余额推送
        $yue_log = M('user_yue_log')->where(array('ts_status' => 0))->select();
        get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/weixin/', 'charge', 'cz_common3', json_encode($yue_log));
        foreach ($yue_log as $v) {

            $msg = request_post('https://api.weixin.qq.com/card/membercard/updateuser?access_token=' . $token, urldecode($v['ts']));

            $data['ts_msg'] = $msg;
            $msg = json_decode($msg, true);
            if ($msg['errcode'] == 0) {
                $data['ts_status'] = 1;
            } else {
                $data['ts_status'] = 0;
            }
            M('user_yue_log')->where(array('id' => $v['id']))->save($data);
        }
        //积分推送
        $memcard_log = M('screen_memcard_log')->where(array('ts_status' => 0))->select();

        foreach ($memcard_log as $v) {
            get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/weixin/', 'charge', 'cz_common4', json_encode($memcard_log));
            $msg = request_post('https://api.weixin.qq.com/card/membercard/updateuser?access_token=' . $token, urldecode($v['ts']));
            $data['msg'] = $msg;
            $msg = json_decode($msg, true);
            if ($msg['errcode'] == 0) {
                $data['ts_status'] = 1;
            } else {
                $data['ts_status'] = 0;
            }
            M('screen_memcard_log')->where(array('id' => $v['id']))->save($data);
        }
    }

    //开启快速购买
    public function open_quick_buy()
    {
        //$token = get_weixin_token();
        $token = '1pH2kBv9vBh-ECmK1AQGjMn0yDpG-a5vrpqd4s7yUhD4_t2a8pPecmmBGzdJJGztOReYC4na2yJBdEhJ1x7GWEm0MEsCJZPInOuE4bErLMlXatQ1HNdIte3Zbxhj4FuRAQFjAJAUKZ';
        $url = 'https://api.weixin.qq.com/card/paycell/set?access_token=' . $token;
        $data['card_id'] = 'pyaFdwKGX4LNil-Bcv5kbGByX3Ig';
        $data['is_open'] = true;
        $r = request_post($url, json_encode($data));
        p($r);
    }

    //创建门店
    public function create_location()
    {
        $token = get_weixin_token();
        $url = 'http://api.weixin.qq.com/cgi-bin/poi/addpoi?access_token=' . $token;
        $info['sid'] = '33788392';
        $info['business_name'] = '汪氏毛椒火辣';
        $info['branch_name'] = '西乡店';
        $info['province'] = '广东省';
        $info['city'] = '深圳市';
        $info['district'] = '宝安区';
        $info['address'] = '西乡街道共乐社区盐田1栋商业楼2-3楼';
        $info['telephone'] = '18823404165';
        $info['categories'] = array("美食,小吃快餐");

        $info['offset_type'] = '1';
        $info['longitude'] = '116.41637';
        $info['latitude'] = '39.92855';
        $data['business']['base_info'] = $info;
        $json = '{"business":{
									"base_info":{
										   "sid":"33788392",
										   "business_name":"汪氏毛椒火辣",
										   "branch_name":"西乡店",
										   "province":"广东省",
										   "city":"深圳市",
										   "district":"宝安区",
										   "address":"西乡街道共乐社区盐田1栋商业楼2-3楼",
										   "telephone":"18823404165",
										   "categories":["美食,小吃快餐"], 
										   "offset_type":1,
										   "longitude":113.863210,
										   "latitude":22.581540
							}}
							}';
        echo json_encode($data);
        $r = request_post($url, $json);
        p($r);
    }

    public function add_log($param = '')
    {
        $data['action'] = MODULE_NAME . '/' . CONTROLLER_NAME . '/' . ACTION_NAME;
        $data['add_time'] = date('Y-m-d H:i:s');
        $data['get'] = json_encode(I('get.'));
        $data['post'] = json_encode($_POST);
        $data['param'] = $param;
        M('log')->add($data);
    }

    //支付接口 curl
    private function httpRequst_pay($url, $post_data)
    {
        //初始化
        $curl = curl_init();
        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //设置post方式提交
        curl_setopt($curl, CURLOPT_POST, 1);
        //设置post数据
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
        //执行命令
        $data = curl_exec($curl);
        curl_close($curl);
        return $data;
        //显示获得的数据   
    }

    public function curl_post($url, $data)
    {
        $ch = curl_init();
        $headers[] = "Accept-Charset: utf-8";
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    public function err($msg = '', $code = 404)
    {
        header("Content-type: text/json");
        $array = array();
        $array['code'] = $code;
        $array['msg'] = $msg;
        echo json_encode($array);
        exit;
    }

    public function succ($data = array(), $msg = 'SUCC')
    {
        $array = array();
        $array['code'] = 0;
        $array['msg'] = $msg;
        $array['data'] = $data;
        $nums = func_num_args();
        $nums > 2 && $array = array_merge($array, func_get_arg(2));
        header("Content-type: text/json");
        echo json_encode($array);
        exit;
    }

    public function decrypt_code($encrypt_code)
    {
        $token = get_weixin_token();
        $data = json_encode(array('encrypt_code' => $encrypt_code));
        $msg = request_post('https://api.weixin.qq.com/card/code/decrypt?access_token=' . $token, $data);
        $res = json_decode($msg, true);
        if ($res['errcode'] == 0 && $res['errmsg'] == 'ok') {
            return $res['code'];
        } else {
            return false;
        }
    }

}
