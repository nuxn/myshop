<?php
/**
 * Created by PhpStorm.
 * User: joan
 * Date: 2017/11/27
 * Time: 16:36
 */
namespace Pay\Controller;

use Common\Controller\HomebaseController;

/**
 * 恒丰银行支付
 * Class BarcodehfbankController
 * @package Pay\Controller
 */
class BarcodehfbankController extends HomebaseController
{

    public $path, $public_key, $expanderCd, $apikey, $httpUrl, $private_key, $acquirerType, $pay_style;

    private $pay_model;
    public static $arr_return = array(
        'code' => '300',
        'info' => array(
            'result_code' => 'FAIL',
            'msg' => '平台验签失败',
        ),
    );

    public static $api_return = array(
        'code' => 'success',
        'msg' => 'success'
    );

    function _initialize()
    {
        header("Content-type:text/html;charset=utf-8");
        $this->pay_model = M('pay');
        $this->httpUrl = 'https://fch.yiguanjinrong.com/flashchannel/';
        $this->RSA_MAX_ORIGINAL = 117;
        $this->RSA_MAX_CIPHER = 256;
        $this->expanderCd = '820170810145924543966';
        $this->apikey = '185891';
        $this->keystring = 'BD161A60C8933E7EC1D1B802376D6245';
        $this->path = $_SERVER['DOCUMENT_ROOT'] . "/data/log/hfbank/";
        $this->public_key = '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAg7pwBwcQWYEF72LAXZap
EgIfIQB5NY3RVcKLF7/mbClEt5x3QODh2ttCtL/SI2rdrvGcyqsMlTCX44TkqZaq
fP3KLxRjJ4qvURpWKxC7z/uIFC+lRumzxnhJqLIOC13kf42MUWgg5sKHnA3XQqlX
RPdX1ZJ/lK+a2d5F0H8tW9uJiqqpfC1qY/fkiPuBh0XgiCHZmqj7VcrLg4P+p0lD
moyXHFFDmQG22rj1TAzcn855Ebdt4vnXENH3fLP3rSE4bCKxkrmZ3AUr9cNhpx4t
FbiRl7Tzv3lLPquzHKu9gFdkImkcra0EYREZKw6kUUmXcpxvxSBt0hzpoqr1L5X6
JwIDAQAB
-----END PUBLIC KEY-----';
    }


    //  测试刷卡
    public function test_micropay()
    {
        $hfpayInfo = M('merchants_hfpay')->where(array("merchant_id" => 488))->find();
        $param = array(
            'pay_style' => '1',
            'wx_key' => $hfpayInfo['privatekey'],
            'ali_key' => $hfpayInfo['privatekey'],
            'wx_mchid' => $hfpayInfo['account'],
            'ali_mchid' => $hfpayInfo['account'],
            'totalAmount' => '0.01',
            'orderSubject' => "刷卡支付测试恒丰joan",
            'authCode' => I('authcode'),
            'public_key' => $this->public_key,
        );

        $rs = $this->micropay($param);
        print_r($rs);
        if ($rs['info']['transResult'] == '2') {

        } else if ($rs['info']['transResult'] == '1') {
            $queryInfo = $this->query($param, 6);
            if ($queryInfo['info']['result_code'] != 'SUCCESS') {
                self::$api_return['code'] = 'fail';
                self::$api_return['msg'] = 'fail';
            }
            print_r($queryInfo);
        } else {
            self::$api_return['code'] = 'fail';
            self::$api_return['msg'] = 'fail';
        }

        print_r(self::$api_return);
    }


    //  测试扫码
    public function test_precreate()
    {
        $hfpayInfo = M('merchants_hfpay')->where(array("merchant_id" => 488))->find();
        $param = array(
            'pay_style' => '1',
            'wx_key' => $hfpayInfo['privatekey'],
            'ali_key' => $hfpayInfo['privatekey'],
            'wx_mchid' => $hfpayInfo['account'],
            'ali_mchid' => $hfpayInfo['account'],
            'totalAmount' => '0.01',
            'orderSubject' => ' 扫码支付测试恒丰joan',
            'public_key' => $this->public_key,
        );
        $rs = $this->precreate($param);
        print_r($rs);
    }

    //  订单查询测试
    public function test_query()
    {
        $hfpayInfo = M('merchants_hfpay')->where(array("merchant_id" => 488))->find();
        $param = array(
            'pay_style' => '1',
            'orderId' => 'S0100115016841024',
            'wx_mchid' => $hfpayInfo['account'],
            'ali_mchid' => $hfpayInfo['account'],
            'public_key' => $this->public_key,
            'wx_key' => $hfpayInfo['privatekey'],
            'ali_key' => $hfpayInfo['privatekey'],
        );

        $rs = $this->query($param);
        print_r($rs);
    }


    /**
     * 订单查询
     * @param $param
     * @return array
     */
    public function query($param, $queryTimes = 0)
    {

        while ($queryTimes >= 0) {

            $this->pay_style = $param['pay_style'];
            get_date_dir($this->path, "query", "订单查询开始", json_encode($param));

            $this->get_diff_param($this->pay_style, $param);

            $msgBody = array(
                'orderId' => $param['orderId'],
                'CallbackFlag' => '1'
            );

            $post_data = array(
                'account' => $param['mch_id'],
                'orderCode' => 'tb_OrderConfirm',
                'msg' => json_encode($msgBody)
            );

            $send_data = array(
                'data' => $this->rsaPublicEncrypt(base64_encode(json_encode($post_data)), $param['public_key']),//RSA公钥加密数据,
                'signature' => $this->rsaDataSign($post_data['msg'], $this->private_key), //RSA签名
                'ChannelFlag' => 'MDX'
            );

            $res = $this->send_post_http($this->httpUrl . 'Kubei', json_encode($send_data));//发送http
            $res = json_decode($res, true);

            $original = $this->rsaPrivateDecrypt(base64_decode($res['data']), $this->private_key); //RSA私钥解密
            $original = json_decode($original, true);
            $res_msg = json_decode($original['msg'], true);

            //验证签名
            $valid = $this->isValid($original['msg'], base64_decode($res['signature']), $param['public_key']);

            if ($valid == 'success') {
                unset(self::$arr_return['info']['msg']);
                self::$arr_return['code'] = '200';
                if ($res_msg['respCode'] == '00000' && $res_msg['respInfo'] == 'SUCCESS') {
                    get_date_dir($this->path, "query", "支付成功返回_" . $this->pay_style, json_encode($res_msg));
                    self::$arr_return['info']['result_code'] = 'SUCCESS';
                    break;
                } else {
                    get_date_dir($this->path, "query", "支付失败返回_" . $this->pay_style, json_encode($res_msg));
                    self::$arr_return['info']['msg'] = $res_msg['respInfo'];

                    if ($queryTimes < 1) {
                        break;
                    } else {
                        sleep(5);
                        $queryTimes--;
                        continue;
                    }
                }
            } else {
                get_date_dir($this->path, "query", "订单查询签名错误_" . $this->pay_style, $valid);
                break;
            }


        }


        return self::$arr_return;
    }


    /**
     * 微信支付界面跳转
     */
    public function qr_weixipay()
    {
        //这里直接获得openid;
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            $id = I("id");
            $merchant = M("merchants_cate")->where("id=$id")->find();
            $openid = $this->_get_openid();
            $this->getOffer($merchant, $openid);

            $this->assign('openid', $openid);
            $this->assign("merchant", $merchant);
            $this->assign('seller_id', I('id'));
            $this->display();
        }
    }


    /**
     *公账号支付
     */
    public function wx_jsapi()
    {
        $hfpayInfo = M('merchants_hfpay')->where(array("merchant_id" => 488))->find();
        $param = array(
            'pay_style' => '1',
            'wx_key' => $hfpayInfo['privatekey'],
            'ali_key' => $hfpayInfo['privatekey'],
            'wx_mchid' => $hfpayInfo['account'],
            'ali_mchid' => $hfpayInfo['account'],
            'totalAmount' => '0.01',
            'orderSubject' => '刷卡支付测试恒丰',
            'public_key' => $this->public_key,
            'open_id' => 'oyaFdwGG6w5U-RGyeh1yWOMoj5fM',
            'sub_appid' => 'wx3fa82ee7deaa4a21',
        );

        $rs = $this->jsapi($param);
        print_r($rs);
        exit;
        $mid = I('mid') / 100;
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

        $data['bank'] = 1;
        if (I("checker_id")) {
            $data['checker_id'] = I("checker_id");
        } //app上的台签带上收银员的信息
        if (I("jmt_remark")) { //金木堂定单号
            $data['jmt_remark'] = I("jmt_remark");
        } else {
            $data['jmt_remark'] = I('memo', '');
        }
        $wzcost_rate = M("merchants_upwz")->where("mid=" . $res['merchant_id'])->getField("WxCostRate");
        if ($wzcost_rate) {
            $data['cost_rate'] = $wzcost_rate;
        };
        $data['bill_date'] = date("Ymd", time());
//        判断是否为客户点多了,如果点多了不添加到数据库
//        $start_time = time() - 3;
//        $end_time = time();
//        $where = array(
//            "merchant_id" => (int)$res['merchant_id'],
//            "paystyle_id" => "1",
//            "price" => $price,
//            "status" => "0",
//            "mode" => $sub_openid,
//            "customer_id" => $data['mode'],
//            'paytime' => array(array('EGT', $start_time), array('ELT', $end_time))
//        );
        $payModel = $this->pay_model;
//        $agin = $payModel->where($where)->find();
//        if ($agin) {
//            file_put_contents('./data/log/wz/weixin/' . date("Y_m_") . 'xiaoxi.log', date("Y-m-d H:i:s") . 11 . PHP_EOL, FILE_APPEND | LOCK_EX);
//            file_put_contents('./data/log/wz/weixin/' . date("Y_m_") . 'xiaoxi.log', date("Y-m-d H:i:s") . json_encode($agin) . PHP_EOL, FILE_APPEND | LOCK_EX);
//            $remark = date('YmdHis') . rand(100000, 999999);
//            $payModel->where(array("id" => $agin['id']))->save(array("paytime" => time(), "remark" => $remark));
//            $good_name = $agin['subject'];
//        } else {
        $remark = I('order_sn', date('YmdHis') . rand(100000, 999999));
        //            插入数据库的数据
        $data['merchant_id'] = (int)$res['merchant_id'];
        //$data['customer_id'] = $sub_openid;
        $data['customer_id'] = D("Api/ScreenMem")->add_member("$sub_openid", $res['merchant_id']);
        $data['paystyle_id'] = 1;
        $data['price'] = $price;
        $data['remark'] = $remark;
        $data['status'] = 0;
        $data['cate_id'] = $res['id'];
        $data['phone_info'] = $_SERVER['HTTP_USER_AGENT'];
        $data['paytime'] = time();
        $good_name = "向" . $res['jianchen'] . "支付" . $price . "元";
        $data['subject'] = $good_name;
        $payModel->add($data);
//        }
        //由于回调地址的原因，将id存入session中
//       支付订单提交的数据交互
        $mchid = $res['wx_mchid'];
        //使用统一支付接口()
        $wzPay->setParameter('sub_openid', $sub_openid);
        $wzPay->setParameter('mch_id', $mchid);
        $wzPay->setParameter('body', $good_name);
        $wzPay->setParameter('out_trade_no', $remark);
        $wzPay->setParameter('goods_tag', 1213);
        $wzPay->setParameter('total_fee', $price * 100);
        $returnData = $wzPay->getParameters();
        $this->assign('jsApiParameters', $returnData);
        $this->assign('price', $price);
        $this->assign('remark', $remark);
        $this->assign('mid', $mid);
        $this->assign('openid', $sub_openid);
        $this->display();

    }

    /**jsapi支付
     * @param $param
     * @return mixed
     */
    public function jsapi($param)
    {
        get_date_dir($this->path, "jsapi", "jsapi支付开始", json_encode($param));
        $pay_title = '';
        $private_key = '';
        if ($param['pay_style'] == 1) {
            $private_key = $param['wx_key'];
            $param['mch_id'] = $param['wx_mchid'];
            $pay_title = '微信';
        } else if ($param['pay_style'] == 2) {
            $private_key = $param['ali_key'];
            $param['mch_id'] = $param['ali_mchid'];
            $pay_title = '支付宝';
        }

        $post_data = array(
            'account' => $param['mch_id'],
            'orderCode' => 'native_pay'
        );

        $msgBody = array(
            'amount' => $param['totalAmount'] * 100,
            'cbzid' => $this->expanderCd,
            'code' => $this->apikey,
            'type' => $param['pay_style'] == 1 ? 'WX_HM' : 'ALIPAY',
            'open_id' => $param['open_id'],
            'sub_appid' => $param['sub_appid'],
        );
        print_r($post_data);
        print_r($msgBody);
        $post_data['msg'] = base64_encode(json_encode($msgBody));
        $sign = $this->rsaDataSign($post_data['msg'], $private_key); //RSA签名

        $data = base64_encode(json_encode($post_data));
        $decrypt = $this->rsaPublicEncrypt($data, $param['public_key']); //数据RSA公钥加密

        $send_data = array(
            'data' => $decrypt,
            'signature' => $sign
        );

        $res = $this->send_post_http($this->httpUrl . 'Kubei', json_encode($send_data));
        get_date_dir($this->path, "jsapi", $pay_title . "_支付返回解密前", $res);
        $res = json_decode($res, true);
        $res_data = $res['data'];
        $res_sign = $res['signature'];

        $original = $this->rsaPrivateDecrypt(base64_decode($res_data), $private_key); //RSA私钥解密
        get_date_dir($this->path, "precreate", $pay_title . "支付返回解密后", $original);
        $original = json_decode($original, true);
        $res_msg = json_decode($original['msg'], true);
        get_date_dir($this->path, "jsapi", $pay_title . "支付返回msg", json_encode($res_msg));
        //验证签名
        $valid = $this->isValid($original['msg'], base64_decode($res_sign), $param['public_key']);
        print_r($res_msg);
        if ($valid == 'success') {
            if ($res_msg['respCode'] == '00000') {
                get_date_dir($this->path, "success", $pay_title . "支付正确返回", json_encode($res_msg));
                $return['code'] = '200';
                $return['info']['result_code'] = 'SUCCESS';
                $return['info']['out_trade_no'] = $res_msg['orderId'];
                //二维码链接
                $return['info']['qrCode'] = $res_msg['QRcodeURL'];
            } else {
                get_date_dir($this->path, "error", $pay_title . "支付错误返回", json_encode($res_msg));
                $return['code'] = '200';
                $return['info']['result_code'] = 'FAIL';
                $return['info']['msg'] = $res_msg['respInfo'];
            }
        } else {
            get_date_dir($this->path, "error", $pay_title . "支付签名错误", $valid);
            $return['code'] = '300';
            $return['info']['result_code'] = 'FAIL';
            $return['info']['msg'] = '平台验签失败';
        }
        return $return;
    }


    /**
     * 双屏扫码支付
     */
    public function two_wz_pay()
    {
        header("Content-type:text/html;charset=utf-8");
        vendor('Wzpay.Wzpay');
        $wzPay = new \Wzpay();
//        先获取openid防止 回调
        $order_id = I("order_id");
        $id = I("id");
        $mode = I('mode', 3);
        //file_put_contents('./data/log/test.log', date("Y-m-d H:i:s"). '='. json_encode($_GET) .  PHP_EOL, FILE_APPEND | LOCK_EX);
        if ($order_id != "") {
            //$code = M('order')->where("order_id='$order_id'")->getField('coupon_code');
            //if($code){A("Apiscreen/Twocoupon")->use_card($code);}
            $openid = I('openid', false);
            if (!$openid) {
                $openid = $this->_get_openid();
            }
            $order = M("order");
            $remark = $order->where("order_id='$order_id'")->getField("order_sn");
//            $pay = $this->pay_model->where("remark='$remark'")->find();
//            if ($pay) {
//                $openid = $this->_get_openid();
//                $order_sn = $remark . rand(1000, 9999);
//                $this->pay_model->where("remark='$remark'")->save(array("customer_id" => $openid, "new_order_sn" => $order_sn));
//                $price = $pay['price'];
////                $order_sn = $pay['new_order_sn'];
//                $res = M('merchants_cate')->where("id=$id")->find();
//                $mchid = $res['wx_mchid'];
//                $good_name = "向" . $res['jianchen'] . "支付" . $price . "元";
//                $sub_openid = $openid;
//            } else {
            $sub_openid = $openid;
            $data['order_id'] = $order_id;
            $data['mode'] = $mode;
            $data['checker_id'] = I("checker_id");
            $order = $order->where("order_id='$order_id'")->find();
            $price = $order['order_amount'];
            $res = M('merchants_cate')->where("id=$id")->find();
//            插入数据库的数据
            $data['merchant_id'] = (int)$res['merchant_id'];
            //$data['customer_id'] = $sub_openid;
            $data['customer_id'] = D("Api/ScreenMem")->add_member("$openid", $res['merchant_id']);
            $data['paystyle_id'] = 1;
            $data['price'] = $price;
            $data['remark'] = $remark;
            $data['status'] = 0;
            $data['cate_id'] = $res['id'];
            $data['bank'] = 1;
            if (I("jmt_remark")) { //金木堂定单号
                $data['jmt_remark'] = I("jmt_remark");
            } else {
                $data['jmt_remark'] = I('memo', '');
            }
            $wzcost_rate = M("merchants_upwz")->where("mid=" . $res['merchant_id'])->getField("WxCostRate");
            if ($wzcost_rate) {
                $data['cost_rate'] = $wzcost_rate;
            };
            $data['paytime'] = time();
            $data['bill_date'] = date("Ymd", time());
            $order_sn = $remark . rand(1000, 9999);
            $data['new_order_sn'] = $order_sn;
            //预防pay表订单重复
            $remark_exists = $this->pay_model->where(array('remark' => $remark))->find();
            if (!$remark_exists) {
                $this->pay_model->add($data);
            }
            //由于回调地址的原因，将id存入session中

            $good_name = "向" . $res['jianchen'] . "支付" . $price . "元";
//       支付订单提交的数据交互
            $mchid = $res['wx_mchid'];
            //file_put_contents('./data/log/test.log', date("Y-m-d H:i:s"). $order_id . '---444---' .  PHP_EOL, FILE_APPEND | LOCK_EX);
            //使用统一支付接口()
            $wzPay->setParameter('sub_openid', $sub_openid);
            $wzPay->setParameter('mch_id', $mchid);
            $wzPay->setParameter('body', $good_name);
            $wzPay->setParameter('out_trade_no', $remark);
            $wzPay->setParameter('goods_tag', 1213);
            $wzPay->setParameter('total_fee', $price * 100);
            $returnData = $wzPay->getParameters();
            $this->assign('jsApiParameters', $returnData);
            $this->assign('price', $price);
            $this->assign('remark', $order_sn);
            $this->assign('mid', $res['merchant_id']);
            $this->assign('openid', $sub_openid);
            $this->display("wz_pay");
        }
    }

    public function precreate($param)
    {
        $this->pay_style = $param['pay_style'];
        get_date_dir($this->path, "precreate", "扫码支付开始_" . $this->pay_style, json_encode($param));

        $get_diff_param = $this->get_diff_param($this->pay_style, $param);
        $msgDate = array(
            'amount' => $param['totalAmount'] * 100,
            'info' => base64_encode($param['orderSubject']),
            'channel_code' => $get_diff_param['channel_code']
        );

        $post_data = array(
            'account' => $param['mch_id'],
            'orderCode' => 'tb_WeixinPay',
            'msg' => base64_encode(json_encode($msgDate))
        );

        $send_data = array(
            'data' => $this->rsaPublicEncrypt(base64_encode(json_encode($post_data)), $param['public_key']),//RSA公钥加密数据,
            'signature' => $this->rsaDataSign($post_data['msg'], $this->private_key), //RSA签名
        );

        $res = $this->send_post_http($this->httpUrl . 'Kubei', json_encode($send_data));//发送http
        $res = json_decode($res, true);

        $original = $this->rsaPrivateDecrypt(base64_decode($res['data']), $this->private_key); //RSA私钥解密
        $original = json_decode($original, true);
        $res_msg = json_decode($original['msg'], true);

        //验证签名
        $valid = $this->isValid($original['msg'], base64_decode($res['signature']), $param['public_key']);

        if ($valid == 'success') {
            unset(self::$arr_return['info']['msg']);
            self::$arr_return['code'] = '200';
            self::$arr_return['info']['out_trade_no'] = $res_msg['orderId'];
            self::$arr_return['info']['totalAmount'] = $param['totalAmount'];
            self::$arr_return['info']['transAmount'] = $param['totalAmount'];
            self::$arr_return['info']['acquirerType'] = $this->acquirerType;
            if ($res_msg['respCode'] == '00000') {
                get_date_dir($this->path, "success", "恒丰银行扫码支付正确返回_" . $this->pay_style, json_encode($res_msg));
                self::$arr_return['info']['result_code'] = 'SUCCESS';
                self::$arr_return['info']['qrCode'] = $res_msg['QRcodeURL'];
            } else {
                get_date_dir($this->path, "error", "恒丰银行扫码支付错误返回_" . $this->pay_style, json_encode($res_msg));
                self::$arr_return['info']['msg'] = $res_msg['respInfo'];
            }
        } else
            get_date_dir($this->path, "error", "恒丰银行扫码支付签名错误_" . $this->pay_style, $valid);
        return self::$arr_return;
    }


    /**
     * 根据支付工具获取相应配置信息
     * @param $paySyle
     * @param $param
     * @return array
     */
    private function get_diff_param($paySyle, &$param)
    {
        $arr = array();
        switch ($paySyle) {
            case "1":
                $arr['channel_code'] = 'WXPAY';
                $this->private_key = $param['wx_key'];
                $this->acquirerType = 'wechat';
                $param['mch_id'] = $param['wx_mchid'];
                break;
            case "2":
                $arr['channel_code'] = 'ALIPAY';
                $this->private_key = $param['ali_key'];
                $this->acquirerType = 'alipay';
                $param['mch_id'] = $param['ali_mchid'];
                break;
            default:
                break;
        }
        return $arr;
    }


    /**
     * 刷卡支付
     * @param $param
     * @return mixed
     */
    public function micropay($param)
    {
        $this->pay_style = $param['pay_style'];
        get_date_dir($this->path, "micropay", "刷卡支付开始_" . $this->pay_style, json_encode($param));

        $get_diff_param = $this->get_diff_param($this->pay_style, $param);
        $msgDate = array(
            'tran_amount' => $param['totalAmount'] * 100,
            'product_name' => base64_encode($param['orderSubject']),
            'product_detail' => base64_encode($param['orderSubject']),
            'auth_code' => $param['authCode'],
            'channel_code' => $get_diff_param['channel_code']
        );

        $post_data = array(
            'account' => $param['mch_id'],
            'orderCode' => 'tb_wxscanpay',
            'msg' => base64_encode(json_encode($msgDate))
        );

        $send_data = array(
            'data' => $this->rsaPublicEncrypt(base64_encode(json_encode($post_data)), $param['public_key']),//RSA公钥加密数据,
            'signature' => $this->rsaDataSign($post_data['msg'], $this->private_key), //RSA签名
        );

        $res = $this->send_post_http($this->httpUrl . 'Kubei', json_encode($send_data));//发送http
        $res = json_decode($res, true);

        $original = $this->rsaPrivateDecrypt(base64_decode($res['data']), $this->private_key); //RSA私钥解密
        $original = json_decode($original, true);
        $res_msg = json_decode($original['msg'], true);

        //验证签名
        $valid = $this->isValid($original['msg'], base64_decode($res['signature']), $param['public_key']);

        if ($valid == 'success') {
            unset(self::$arr_return['info']['msg']);
            self::$arr_return['code'] = '200';
            self::$arr_return['info']['out_trade_no'] = $res_msg['orderId'];
            self::$arr_return['info']['totalAmount'] = $param['totalAmount'];
            self::$arr_return['info']['transAmount'] = $param['totalAmount'];
            self::$arr_return['info']['acquirerType'] = $this->acquirerType;
            if ($res_msg['respCode'] == '00000') {
                get_date_dir($this->path, "success", "恒丰银行刷卡支付正确返回_" . $this->pay_style, json_encode($res_msg));
                self::$arr_return['info']['result_code'] = 'SUCCESS';
                self::$arr_return['info']['transResult'] = '2';

            } else if ($res_msg['respCode'] == '500004') {
                get_date_dir($this->path, "passowrd", "恒丰银行刷卡支付密码输入_" . $this->pay_style, json_encode($res_msg));
                self::$arr_return['info']['transResult'] = '1';
                self::$arr_return['info']['msg'] = '刷卡支付用户需要输入支付密码';

            } else {
                get_date_dir($this->path, "error", "恒丰银行刷卡支付错误返回_" . $this->pay_style, json_encode($res_msg));
                self::$arr_return['info']['msg'] = $res_msg['respInfo'];
            }
        } else
            get_date_dir($this->path, "error", "恒丰银行扫码支付签名错误_" . $this->pay_style, $valid);
        return self::$arr_return;
    }


    private function send_post_http($url = '', $post_data = '')
    {
        if (empty($url) || empty($post_data)) {
            return false;
        }
        $postUrl = $url;
        $curlPost = $post_data;
        $ch = curl_init(); //初始化curl
        curl_setopt($ch, CURLOPT_URL, $postUrl); //抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0); //设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1); //post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        $data = curl_exec($ch); //运行curl
        curl_close($ch);
        return $data;
    }

    private function rsaPublicEncrypt($data, $keyPath)
    {
        $key = openssl_pkey_get_public($keyPath);
        $ciphertext = null;
        $cipher_len = strlen($data);
        if ($cipher_len - $this->RSA_MAX_ORIGINAL > 0) {
            $flag = 0;
            for ($i = ceil($cipher_len / $this->RSA_MAX_ORIGINAL); $i > 0; $i--) {
                $temp = substr($data, $flag, $this->RSA_MAX_ORIGINAL);
                $r = openssl_public_encrypt($temp, $encryptData, $key);
                $ciphertext .= $encryptData;
                if ($r) {
                    $flag += $this->RSA_MAX_ORIGINAL;
                }
            }
        } else {
            $r = openssl_public_encrypt($data, $encryptData, $key);
            if ($r) {
                $ciphertext = $encryptData;
            }
        }
        return base64_encode($ciphertext);
    }

    /**
     * RSA私钥解密(分段解密)
     * @param $data
     * @param $keyPath
     * @return bool|string
     */
    private function rsaPrivateDecrypt($data, $keyPath)
    {
        $key = openssl_pkey_get_private($keyPath);
        $originalText = null;
        $original_len = strlen($data);
        if ($original_len - $this->RSA_MAX_CIPHER > 0) {
            $flag = 0;
            for ($i = ceil($original_len / $this->RSA_MAX_CIPHER); $i > 0; $i--) {
                $temp = substr($data, $flag, $this->RSA_MAX_CIPHER);
                $r = openssl_private_decrypt($temp, $decrypted, $key);
                $originalText .= $decrypted;
                if ($r) {
                    $flag += $this->RSA_MAX_CIPHER;
                }
            }
        } else {
            $r = openssl_private_decrypt($data, $decrypted, $key);
            if ($r) {
                $originalText = $decrypted;
            }
        }
        return base64_decode($originalText);
    }

    /**
     * 数据签名
     * @param type $data
     * @param type $keyPath
     * @return boolean
     */
    private function rsaDataSign($data, $keyPath)
    {

        if (empty($data)) {
            return False;
        }

        $private_key = $keyPath;
        if (empty($private_key)) {
            return False;
        }

        $pkeyid = openssl_get_privatekey($private_key);
        if (empty($pkeyid)) {
            return False;
        }

        $verify = openssl_sign($data, $signature, $pkeyid, OPENSSL_ALGO_MD5);
        openssl_free_key($pkeyid);
        return base64_encode($signature);
    }

    /**
     * 数据验签
     * @param string $data
     * @param string $signature
     * @param $keyPath
     * @return bool
     */
    private function isValid($data = '', $signature = '', $keyPath)
    {
        if (empty($data) || empty($signature)) {
            return False;
        }

        $public_key = $keyPath;
        if (empty($public_key)) {

            return False;
        }

        $pkeyid = openssl_get_publickey($public_key);
        if (empty($pkeyid)) {
            return False;
        }

        $ret = openssl_verify($data, $signature, $pkeyid, OPENSSL_ALGO_MD5);
        if ($ret == 1) {
            return 'success';
        } else {
            return 'error';
        }
    }

    private function hexStrToBytes($str, $length = null)
    {
        $ret = array('c*');
        for ($i = 0, $l = strlen($str) / 2; $i < $l; ++$i) {
            $x = intval(substr($str, 2 * $i, 2), 16);
            if ($x > 128)
                $x -= 256;
            $ret[] = $x;
        }
        //补全24位
        if (isset($length)) {
            for ($i = count($ret), $j = 1; $i <= $length; ++$i, ++$j)
                $ret[] = $ret[$j];
        }
        return call_user_func_array('pack', $ret);
    }

}
