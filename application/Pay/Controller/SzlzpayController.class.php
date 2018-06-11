<?php

namespace Pay\Controller;

use Common\Controller\HomebaseController;

class SzlzpayController extends HomebaseController
{
    private $ali_public_key = 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDDI6d306Q8fIfCOaTXyiUeJHkrIvYISRcc73s3vF1ZT7XN8RNPwJxo8pWaJMmvyTn9N4HQ632qJBVHf8sxHi/fEsraprwCtzvzQETrNRwVxLO5jVmRGi60j8Ue1efIlzPXV9je9mkjzOmdssymZkh2QhUrCmZYI/FCEa3/cNMW0QIDAQAB';
    private $ali_private_key = 'MIICXAIBAAKBgQCtV+QoWwH8BpmfKfBglWUAMdKe2g+NeD0ajVxLKahKRHidU3SuXdu3Zy9E98k8R7E8mr0/EvFnshLaEUxaxc8QalEvbSx71s5NCYaHG3/aTSbjL1StFMSPvIh64DJx5o2jz66ppmGX4RkLV9/Xs58BF/oT3qDTZXTqQfEaiAsK9wIDAQABAoGALbvxs4AHbwIix+6dwC3KXxnGEyk/Tzj5DidbwWz1PNsB46hgMZ0L2kC8JPsnOeNEbNP6uEh8Lrq55JUJyy1Dawcn4a9IYYxaxxXh5cU/ucHtIJYsmjEcA4V/PdZCQv4McPPv0vERC/zVCpJ2MdXx26sNZvcT1NT8z38lhqgGb1ECQQDUxXu06+Vl/WD/SnsIx6u116kZwcS2cG+jja/20b9vBDh8eYKKIU3FfbTR6sskLyGxJEkgBgO8crNmYGm0MBAJAkEA0I+zrEJK87DgbDtP4TAmZN6FSot0hUOE6iskX4m/rv3KIhYug8F7AweHoBPPbUGCBoKdcqBhHGwFcBt6uHMC/wJAEnacmIOL4YDORPj6mjVxchMnymNlJYu2NFQcO+fRm9ma6TpGGKRxMj0JTtn4DMjGPK/wZIYBFv5BERY2tfshuQJAB7EVDkhPnVcrn7I8SvDMqbGvNsWX4YZQ85XtvHxHDnwbpVAuHPvYvo7biKLSZpQg6H6OsfiKPFMbjDvnNcBAHwJBALoTwUecjj5N6mgioBKuhgy3IXMMg1cclpMWhhxjbMo51tQ0IrpVW1IHl10zuOBQk3EpiFRm9YpQ5KWUGSlRdlY=';
    private $aes = 'TOSFY0vpISFEZe28/TVB8Q==';
    private $appid = '2017071207730667'; // 支付宝APPID
    private $ali_notify_url = 'https://sy.youngport.com.cn/notify/szlzalipay_notify.php'; // 支付宝回调地址
    private $url = 'https://openapi.alipay.com/gateway.do';
    private $pay_model;

    public function _initialize()
    {
        parent::_initialize();
        $this->pay_model = M('pay');
    }

    /**
     * 获取openid
     * @param $request_url
     * @return mixed
     */
    public function _get_openid($request_url)
    {
        //全局引入微信支付类
        Vendor('SzWxPayPubHelper.WxPayPubHelper');
        //使用jsapi接口
        $jsApi = new \JsApi_pub();
        if (!isset($_GET['code'])) {
            //触发微信返回code码
            $url = $jsApi->createOauthUrlForCode($request_url);
            Header("Location: $url");
        } else {
            //获取code码，以获取openid
            $code = $_GET['code'];
            $jsApi->setCode($code);
            $openid = $jsApi->getOpenId();
        }

        return $openid;
    }

    /**
     * 公众号支付扫码支付金额界面
     */
    public function wxpay()
    {
        Vendor('SzWxPayPubHelper.WxPayPubHelper');
        // 这里直接获得openid;
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            $id = I("id");
            $checker_id = I("checker_id");
            $merchant = M("merchants_cate")->where(array('id' => $id))->find();
            $url = \WxPayConf_pub::JS_API_CALL_URL . "/id/{$id}/checker_id/{$checker_id}";
            $openid = $this->_get_openid($url);
            $this->assign("checker_id", $checker_id);
            $this->assign('openid', $openid);
            $this->assign("merchant", $merchant);
            $this->assign('seller_id', I("id"));
            $this->display();
        }
    }

    /**
     * 公众号支付扫码支付收款
     */
    public function wx_pay()
    {
        // 得到输入的金额和商户的ID
        header("Content-type:text/html;charset=utf-8");
        Vendor('SzWxPayPubHelper.WxPayPubHelper');
        $jsApi = new \JsApi_pub();
        // 先获取openid防止 回调
        $remark = I('order_sn', date('YmdHis') . rand(100000, 999999));
        $mode = I('mode');
        if (I("seller_id") == "") {
            $id = I("id");
            $price = I("price");
            $checker_id = I("checker_id");
            $url = \WxPayConf_pub::PHONE_API_CALL_URL . "/id/{$id}/price/{$price}/checker_id/{$checker_id}/order_sn/$remark/mode/$mode";
            $sub_openid = $this->_get_openid($url);
            $res = M('merchants_cate')->where("id=$id")->find();
            $data['mode'] = I('mode', 1);
            $data['checker_id'] = $checker_id;
        }
        if (I('seller_id') !== "") {
            $sub_openid = I('openid');
            $id = I('seller_id');
            $res = M('merchants_cate')->where("id=$id")->find();
            $price = I('price');
            $data['checker_id'] = $res['checker_id'];
            $data['mode'] = I('mode', 0);
        }
        if (!$sub_openid) {
            exit;
        }
        $data['bank'] = 9;
        if (I("checker_id")) {
            $data['checker_id'] = I("checker_id");
        } //app上的台签带上收银员的信息
        $wx_mch_data = M("merchants_szlzwx")->where("mid=" . $res['merchant_id'])->field("mch_id,rate")->find();
        $wx_cost_rate = $wx_mch_data['rate'];
        if ($wx_cost_rate) {
            $data['cost_rate'] = $wx_cost_rate;
        };
        $data['bill_date'] = date("Ymd", time());
        $payModel = $this->pay_model;
        //$remark = $this->getRemark();
        // 插入数据库的数据
        $data['merchant_id'] = (int)$res['merchant_id'];
        $data['customer_id'] = $sub_openid;
        $data['paystyle_id'] = 1;
        $data['price'] = $price;
        $data['remark'] = $remark;
        $data['status'] = 0;
        $data['cate_id'] = $res['id'];
        $data['phone_info'] = $_SERVER['HTTP_USER_AGENT'];
        $data['paytime'] = time();
        $data['jmt_remark'] = I('memo', '');
        $good_name = '付款' . $price . "元";
        $data['subject'] = $good_name;
        $sql_res = $payModel->add($data);
        if (!$sql_res) {
            echo '<script type="text/javascript">alert("网络异常，请稍后再试!")</script>';
            exit;
        }
        // 微信围餐分配的商户id
//        $mchid = $wx_mch_data['mch_id'];
        $mchid = $res['wx_mchid'];
//        $mchid = '1486561492';

        //使用统一支付接口
        $unifiedOrder = new \UnifiedOrder_pub();

        //设置统一支付接口参数
        //设置必填参数
        //appid已填,商户无需重复填写
        //mch_id已填,商户无需重复填写
        //noncestr已填,商户无需重复填写
        //spbill_create_ip已填,商户无需重复填写
        //sign已填,商户无需重复填写
        $unifiedOrder->setParameter("openid", "$sub_openid");//openid和sub_openid可以选传其中之一
//        $unifiedOrder->setParameter("sub_openid", "$sub_openid");//子商户appid下的唯一标识
        $unifiedOrder->setParameter("body", $good_name);//商品描述
        //自定义订单号，
        $unifiedOrder->setParameter("out_trade_no", "$remark");//商户订单号
        $unifiedOrder->setParameter("total_fee", $price * 100);//总金额
        $unifiedOrder->setParameter("notify_url", \WxPayConf_pub::NOTIFY_URL);//通知地址
        $unifiedOrder->setParameter("trade_type", "JSAPI");//交易类型
        $unifiedOrder->setParameter("sub_mch_id", $mchid);//子商户号服务商必填

        $prepay_id = $unifiedOrder->getPrepayId();

//=========步骤3：使用jsapi调起支付============
        $jsApi->setPrepayId($prepay_id);
        $jsApiParameters = $jsApi->getParameters();
        $this->assign('jsApiParameters', $jsApiParameters);
        $this->assign('price', $price);
        $this->assign('remark', $remark);
        $this->assign('openid', $sub_openid);
        $this->assign('mid', $data['merchant_id']);
        $this->display();

    }

    # 支付宝支付界面跳转
    public function qr_alipay()
    {
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient') !== false) {
            $cate_id = I('id');
            $merchant = M("merchants_cate")->where("id=$cate_id")->find();
            $this->assign("checker_id", I('checker_id'));
            $this->assign("merchant", $merchant);
            $this->assign('cate_id', $cate_id);
            $this->display();
        }
    }

    # 支付宝手机扫码支付
    public function qr_to_alipay()
    {
        $cate_id = I('id');//二维码对应的id
        $checker_id = I('checker_id', 0, 'intval');
        if (!$cate_id) exit('cate_id不能为空!');
        $res = M('merchants_cate')->where('id=' . $cate_id)->find();
        $this->apikey = $res['alipay_public_key'];
        if (!$res) exit('二维码信息不存在!');
        $res['checker_id'] = $checker_id ? $checker_id : intval($res['checker_id']);
        $price = I('price');
        $res['price'] = $price ? $price : '0.01';
        $res['order_sn'] = I('order_sn');
        $this->alipay($res);

    }

    public function screen_alipay()
    {
        header("Content-type:text/html;charset=utf-8");

        $cate_id = I('id');//二维码对应的id
        $order_id = I('order_id');
        $checker_id = I('checker_id', 0, 'intval');
        $mode = I('mode', 6);
        $jmt_remark = I('jmt_remark', '');
        if (!$cate_id) exit('cate_id不能为空!');
        if (!$order_id) exit('订单号不能为空!');

        $res = M('merchants_cate')->where(array("id" => $cate_id))->find();
        $ali_mchid = $res['alipay_partner'];
        $token = $res['alipay_public_key'];
        $appid = $res['alipay_partner'];
        if (!$res) exit('二维码信息不存在!');
        $checker_id = $checker_id ? $checker_id : intval($res['checker_id']);
        $orderModel = M("order");
        $order_info = $orderModel->where(array("order_id" => $order_id))->find();
        if (!$order_info['order_sn']) exit('订单不存在!');

        $pay_info = $this->pay_model->where(array("remark" => $order_info['order_sn']))->find();
        if ($pay_info) {
            $data = array(
                "merchant_id" => $pay_info['merchant_id'],
                "price" => $pay_info['price'] ? $pay_info['price'] : '0.01',
                "remark" => $pay_info['remark'],
                "subject" => $pay_info['subject'] ? $pay_info['subject'] : '付款' . $order_info['order_amount'] . "元",
                "checker_id" => $checker_id,
            );
            $this->pay_model->where(array("remark" => $order_info['order_sn']))->save(array("paystyle_id" => 2));
        } else {
            $wzcost_rate = $this->getRate($ali_mchid);
            $data = array(
                "merchant_id" => $res['merchant_id'],
                "price" => $order_info['order_amount'] ? $order_info['order_amount'] : '0.01',
                "subject" => '付款' . $order_info['order_amount'] . "元",
                "mode" => $mode,//双屏扫码
                "paystyle_id" => "2",//支付宝
                "order_id" => $order_id,//订单编号
                "remark" => $order_info['order_sn'],//订单号唯一
                "status" => "0",//未付款
                "paytime" => time(),
                "add_time" => time(),
                "cate_id" => $res['id'],
                "checker_id" => $checker_id,
                "bank" => 9,
                "jmt_remark" => $jmt_remark,
                "cost_rate" => $wzcost_rate ? $wzcost_rate : '',
                'phone_info' => $_SERVER['HTTP_USER_AGENT'],
            );
            $this->pay_model->add($data);
        }

        $this->pay_model->where(array("remark" => $order_info['order_sn']))->save(array("new_order_sn" => $data['remark']));
        //构造要请求的参数数组，无需改动
        $content = array(
            'out_trade_no' => $data['remark'],
            'seller_id' => $ali_mchid,
            'total_amount' => $data['price'],
            'subject' => $data['subject'],
            'extend_params' => array(
                'sys_service_provider_id' => '2088721521881652'
            )
        );
        $request_arr = array(
            'app_id' => $this->appid,
            'method' => 'alipay.trade.precreate',
            'charset' => 'utf-8',
            'sign_type' => 'RSA',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'notify_url' => $this->ali_notify_url,
            'app_auth_token' => $token,
            'biz_content' => json_encode($content),
        );
        $string = $this->getSignContent($request_arr);
        $sign = $this->rsaSign($string, $this->ali_private_key);

        $request_arr['sign'] = $sign;
        $this->writlog('alipay_js.log', '下单数据2：' . json_encode($request_arr), 'ali/');

        $res_str = $this->curl($this->url, $request_arr);
        $results = json_decode($res_str, true);
        $result = $results['alipay_trade_precreate_response'];
        if ($result['code'] === '10000') {
            $this->writlog('alipay_js.log', '下单成功：' . json_encode($results), 'ali/');
            header("Location: $result[qr_code]");
        } else {
            echo '<script type="text/javascript">alert("网络异常，请稍后再试！")</script>';
            $this->writlog('alipay_js.log', '下单失败：' . json_encode($results), 'ali/');
        }
    }

    public function alipay($res)
    {
        header("Content-type:text/html;charset=utf-8");

        $ali_mchid = $res['alipay_partner'];
        $price = $res['price'];
        $token = $res['alipay_public_key'];
        $appid = $res['alipay_partner'];
        if (empty($token)) {
            echo '<script type="text/javascript">alert("网络异常，请稍后再试！")</script>';
        }
        $payModel = $this->pay_model;
        $where = array(
            "merchant_id" => $res['merchant_id'],
            "paystyle_id" => "2",
            "price" => $price,
            "status" => "0",
            "mode" => 1,
            "cate_id" => $res['id'],
        );
        $subject = '付款' . $res['price'] . "元";
        $where['subject'] = $subject;
        $remark = $res['order_sn'] ?: $this->getRemark();
        $where['remark'] = $remark;
        $where['jmt_remark'] = I('memo', '');
        $where['paytime'] = time();
        $where['checker_id'] = $res['checker_id'];
        $where['bank'] = 9;
        $where['phone_info'] = $_SERVER['HTTP_USER_AGENT'];
        $wzcost_rate = $this->getRate($ali_mchid);
        if ($wzcost_rate) $where['cost_rate'] = $wzcost_rate;
        $sql_res = $payModel->add($where);
        if (!$sql_res) {
            echo '<script type="text/javascript">alert("网络异常，请稍后再试!")</script>';
            exit;
        }
        //构造要请求的参数数组，无需改动
        $content = array(
            'out_trade_no' => $remark,
            'seller_id' => $ali_mchid,
            'total_amount' => $price,
            'subject' => $subject,
            'extend_params' => array(
                'sys_service_provider_id' => '2088721521881652'
            )
        );
        $request = array(
            'app_id' => $this->appid,
            'method' => 'alipay.trade.precreate',
            'charset' => 'utf-8',
            'sign_type' => 'RSA',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'notify_url' => $this->ali_notify_url,
            'app_auth_token' => $token,
            'biz_content' => json_encode($content),
        );
        $string = $this->getSignContent($request);
        $sign = $this->rsaSign($string, $this->ali_private_key);

        $request['sign'] = $sign;
        $this->writlog('alipay_js.log', '下单数据：' . json_encode($request), 'ali/');

        $res_str = $this->curl($this->url, $request);
        $results = json_decode($res_str, true);
        $result = $results['alipay_trade_precreate_response'];
        if ($result['code'] === '10000') {
            $this->writlog('alipay_js.log', '下单成功：' . json_encode($results), 'ali/');
            header("Location: $result[qr_code]");
        } else {
            echo '<script type="text/javascript">alert("网络异常，请稍后再试！")</script>';
            $this->writlog('alipay_js.log', '下单失败：' . json_encode($results), 'ali/');
        }
    }

    public function getRate($mch_id)
    {
        return M('merchants_szlzwx')->where(array('ali_mchid' => $mch_id))->getField('rate');
    }

    /**
     * JSAPI支付通知,通用通知接口
     */
    public function notify()
    {
        Vendor('SzWxPayPubHelper.WxPayPubHelper');
        //使用通用通知接口
        $notify = new \Notify_pub();

        //存储微信的回调
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        $notify->saveData($xml);

        //验证签名，并回应微信。
        //对后台通知交互时，如果微信收到商户的应答不是成功或超时，微信认为通知失败
        //微信会通过一定的策略（如30分钟共8次）定期重新发起通知，
        //尽可能提高通知的成功率，但微信不保证通知最终能成功。
        if ($notify->checkSign() == FALSE) {
            $return = array('return_code' => "FAIL", 'return_msg' => "签名失败");
            file_put_contents('../data/log/szlz/' . date("Y_m_") . 'wxpay_notify.log', date("Y-m-d H:i:s") . '签名失败' . $xml . PHP_EOL . PHP_EOL, FILE_APPEND | LOCK_EX);
        } else {
            $data = $notify->data;
            $out_trade_no = $data["out_trade_no"];//回调的订单号
            if ($data['return_code'] == 'SUCCESS' && $data['result_code'] == 'SUCCESS') {
                // 读取订单信息
                $pay_info = $this->pay_model->where("remark='$out_trade_no'")->find();
                // 如果订单已支付返回成功
                if ($pay_info['status'] == 1) {
                    $return = array('return_code' => "SUCCESS", 'return_msg' => "");
                    $returnXml = $notify->returnnotifyXml($return);
                    echo $returnXml;
                    exit;
                }
                $orderPrice = $pay_info['price'];
                $id = $pay_info['id'];
                // 比较订单价格是否一致
                if (bccomp($orderPrice * 100, $data['total_fee'], 3) === 0) {
                    // 更改订单状态
                    $save_data['paytime'] = time();
                    $save_data['status'] = 1;
                    $save_data['price_back'] = $data['cash_fee'] / 100;
                    $save_data['price_gold'] = (isset($data['coupon_fee']) ? $data['coupon_fee'] : 0) / 100;
                    $save_data['wx_remark'] = $data['transaction_id'];
                    $this->pay_model->where(array('id' => $id))->save($save_data);
                    file_put_contents('../data/log/szlz/' . date("Y_m_") . 'wxpay_notify.log', date("Y-m-d H:i:s") . '支付成功' . json_encode($data) . PHP_EOL . PHP_EOL, FILE_APPEND | LOCK_EX);
                    // 手机app推送消息
                    A("App/PushMsg")->push_pay_message($out_trade_no);
//                    $notify->setReturnParameter("return_code", "SUCCESS");//设置返回码
                    $return = array('return_code' => "SUCCESS", 'return_msg' => "");
                } else {
                    file_put_contents('../data/log/szlz/' . date("Y_m_") . 'wxpay_notify.log', date("Y-m-d H:i:s") . '金额效验失败' . json_encode($data) . PHP_EOL . PHP_EOL, FILE_APPEND | LOCK_EX);
                    A("App/PushMsg")->push_pay_message($out_trade_no);
                    $return = array('return_code' => "FAIL");
                }
            } else {
                file_put_contents('../data/log/szlz/' . date("Y_m_") . 'wxpay_notify.log', date("Y-m-d H:i:s") . '支付失败' . json_encode($data) . PHP_EOL . PHP_EOL, FILE_APPEND | LOCK_EX);
                A("App/PushMsg")->push_pay_message($out_trade_no);
                $return = array('return_code' => "FAIL");
            }
        }

        $returnXml = $notify->returnNotifyXml($return);
        echo $returnXml;
    }

    public function ali_notify()
    {
        $post = $_POST;
        if ($post['trade_status'] === 'TRADE_SUCCESS') {
            $remark = $post['out_trade_no'];
            $pay_info = $this->pay_model->where("remark='$remark'")->find();
            // 如果订单已支付返回成功
            if ($pay_info['status'] == 1) {
                echo 'success';
                exit;
            }
            $orderPrice = $pay_info['price'];
            $id = $pay_info['id'];
            // 比较订单价格是否一致
            if (bccomp($orderPrice * 100, $post['total_amount'] * 100, 3) === 0) {
                // 更改订单状态
                $save_data['paytime'] = time();
                $save_data['status'] = 1;
                $save_data['price_back'] = $post['buyer_pay_amount'];
                $save_data['remark_mer'] = $post['trade_no'];
                $this->pay_model->where(array('id' => $id))->save($save_data);
                file_put_contents('../data/log/szlz/' . date("Y_m_") . 'alipay_notify.log', date("Y-m-d H:i:s") . '支付成功' . json_encode($post) . PHP_EOL . PHP_EOL, FILE_APPEND | LOCK_EX);
                // 手机app推送消息
                A("App/PushMsg")->push_pay_message($remark);
//                    $notify->setReturnParameter("return_code", "SUCCESS");//设置返回码
                echo 'success';
            } else {
                file_put_contents('../data/log/szlz/' . date("Y_m_") . 'alipay_notify.log', date("Y-m-d H:i:s") . '金额效验失败' . json_encode($post) . PHP_EOL . PHP_EOL, FILE_APPEND | LOCK_EX);
                A("App/PushMsg")->push_pay_message($remark);
                echo 'fail';
            }
        } else {
            file_put_contents('../data/log/szlz/' . date("Y_m_") . 'alipay_notify.log', date("Y-m-d H:i:s") . '支付失败' . json_encode($post) . PHP_EOL . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
    }

    /**
     * 双屏扫码支付
     */
    public function two_wxpay()
    {
        header("Content-type:text/html;charset=utf-8");
        Vendor('SzWxPayPubHelper.WxPayPubHelper');
        $jsApi = new \JsApi_pub();
//        先获取openid防止 回调
        $order_id = I("order_id");  // 订单id
        $checker_id = I("checker_id");
        $price = I("price");
        $id = I("id");
        $mode = I('mode', 6);
        $url = \WxPayConf_pub::TWO_API_CALL_URL . "/id/{$id}/order_id/{$order_id}/checker_id/{$checker_id}/price/{$price}";
        $openid = $this->_get_openid($url);
        if ($order_id != "") {
            $order = M("order");
//            $sub_openid = $openid;
            $data['order_id'] = $order_id;
            $data['mode'] = $mode;
            $data['checker_id'] = I("checker_id");
            $orders = $order->where("order_id='$order_id'")->find();
            $price = $orders['order_amount'];
            $remark = $orders['order_sn'];
            $res = M('merchants_cate')->where("id=$id")->find();
//            插入数据库的数据
            $data['merchant_id'] = (int)$res['merchant_id'];
            $data['customer_id'] = $openid;
            $data['paystyle_id'] = 1;
            $data['price'] = $price;
            $data['remark'] = $remark;
            $data['status'] = 0;
            $data['cate_id'] = $res['id'];
            $data['add_time'] = time();
            $data['new_order_sn'] = $remark;
            $data['bank'] = 9;
            $data['phone_info'] = $_SERVER['HTTP_USER_AGENT'];
            $data['jmt_remark'] = I('jmt_remark') ? I('jmt_remark') : I('memo', '');

            $good_name = '付款' . $price . "元";
            $data['subject'] = $good_name;
            if ($this->pay_model->where(array('remark' => $remark))->find()) {
                $this->pay_model->where(array("remark" => $remark))->save(array("paystyle_id" => 1));
            } else {
                $this->pay_model->add($data);
            }

            // 微信围餐分配的商户id
            $mchid = $res['wx_mchid'];
        } else {
            $this->ajaxReturn(array('code' => 'error', 'msg' => '订单号为空'));
        }
        //使用统一支付接口
        $unifiedOrder = new \UnifiedOrder_pub();

        //设置统一支付接口参数
        //设置必填参数
        //appid已填,商户无需重复填写
        //mch_id已填,商户无需重复填写
        //noncestr已填,商户无需重复填写
        //spbill_create_ip已填,商户无需重复填写
        //sign已填,商户无需重复填写
        $unifiedOrder->setParameter("openid", "$openid");//openid和sub_openid可以选传其中之一
        // $unifiedOrder->setParameter("sub_openid", "$sub_openid");//子商户appid下的唯一标识
        $unifiedOrder->setParameter("body", $good_name);//商品描述
        //自定义订单号，
        $unifiedOrder->setParameter("out_trade_no", "$remark");//商户订单号
        $unifiedOrder->setParameter("total_fee", $price * 100);//总金额
        $unifiedOrder->setParameter("notify_url", \WxPayConf_pub::NOTIFY_URL);//通知地址
        $unifiedOrder->setParameter("trade_type", "JSAPI");//交易类型
        $unifiedOrder->setParameter("sub_mch_id", $mchid);//子商户号服务商必填

        $prepay_id = $unifiedOrder->getPrepayId();
//=========步骤3：使用jsapi调起支付============
        $jsApi->setPrepayId($prepay_id);
        $jsApiParameters = $jsApi->getParameters();
        $this->assign('jsApiParameters', $jsApiParameters);
        $this->assign('price', $price);
        $this->assign('remark', $remark);
        $this->assign('openid', $openid);
        $this->assign('mid', $data['merchant_id']);
        $this->display("wx_pay");
    }

    /**
     * 刷卡支付
     */
    public function micropay($id, $price, $auth_code, $checker_id, $order_sn, $mode)
    {
        Vendor('SzWxPayPubHelper.WxPayPubHelper');

        if (!$auth_code) {
            $this->error('参数错误!');
        }
//            支付信息
        if (!$id) {
            return array("code" => "error", "msg" => "失败", "data" => "支付失败");
        }
        $res = M('merchants_cate')->where("merchant_id=$id")->find();
        if ((int)$res['merchant_id'] == 0) {
            return array("code" => "error", "msg" => "失败", "data" => "还未申请支付业务");
        }
        $remark = $order_sn ?: date('YmdHis') . rand(100000, 999999);
//            插入数据库的数据
        $data['merchant_id'] = (int)$res['merchant_id'];
        $data['checker_id'] = $checker_id;
        $data['paystyle_id'] = 1;
        $data['price'] = $price;
        $data['remark'] = $remark;
        $data['status'] = 0;
        $data['cate_id'] = $res['id'];;
        if ($mode) {
            $data['mode'] = $mode;
        } else {
            $data['mode'] = 2;
        }
        $data['paytime'] = time();
        $data['bank'] = 9;
//        添加的数据
        $wx_cost_rate = M("merchants_szlzwx")->where("mid=" . $res['merchant_id'])->getField("mch_id,rate");
        if ($wx_cost_rate) {
            $data['cost_rate'] = $wx_cost_rate['rate'];
        };
        $data['bill_date'] = date("Ymd", time());

        $data['phone_info'] = $_SERVER['HTTP_USER_AGENT'];
        // 微信围餐分配的商户id
//        $mchid = $wx_cost_rate["mch_id"];
        $mchid = $res['wx_mchid'];

        $key = $res["wx_key"];
        $product = '付款' . $price . "元";
        $this->pay_model->add($data);

        $data = array('pay_money' => $price, 'auth_code' => $auth_code, 'remark' => $remark, 'merchant_code' => $mchid, 'product' => $product, 'key' => $key);

        $input = new \WxPayMicroPay();
        $input->setParameter("auth_code", "$auth_code");    // 授权码
        $input->setParameter("body", "$product");  // 商品描述
        $input->setParameter("total_fee", $price * 100); // 总金额
        $input->setParameter("out_trade_no", "$remark");  // 商户订单号
        $input->setParameter("sub_mch_id", $mchid);    // 子商户号

        $result = $input->pay();

        if ($result['flag'] == false) {
            $this->writlog('wxpay_micro.log', '失败：' . json_encode($result), 'weixin/');
            return array("code" => "error", "msg" => "失败", "data" => $result['message']);
        } else {
            $pay_change = $this->pay_model;
            $data['paytime'] = time();
            $data['status'] = 1;
            if ($pay_change->where("remark=$remark")->find()) $pay_change->where("remark=$remark")->save($data);
            $this->writlog('wxpay_micro.log', '成功：' . json_encode($result), 'weixin/');
            A("App/PushMsg")->push_pay_message($remark);
            return array("code" => "success", "msg" => "成功", "data" => $result['message']);
        }

    }

    /**
     * 刷卡支付
     */
    public function pos_micropay($id, $price, $auth_code, $checker_id, $remark)
    {
        Vendor('SzWxPayPubHelper.WxPayPubHelper');

        if (!$auth_code) {
            $this->error('参数错误!');
        }
//            支付信息
        if (!$id) {
            return array("code" => "error", "msg" => "失败", "data" => "支付失败");
        }
        $res = M('merchants_cate')->where("merchant_id=$id")->find();
        if ((int)$res['merchant_id'] == 0) {
            return array("code" => "error", "msg" => "失败", "data" => "还未申请支付业务");
        }
//            插入数据库的数据
        $data['merchant_id'] = (int)$res['merchant_id'];
        $data['checker_id'] = $checker_id;
        $data['paystyle_id'] = 1;
        $data['price'] = $price;
        $data['remark'] = $remark;
        $data['status'] = 0;
        $data['cate_id'] = $res['id'];
        $data['mode'] = 8;
        $data['paytime'] = time();
        $data['bank'] = 9;
//        添加的数据
        $wx_cost_rate = M("merchants_szlzwx")->where("mid=" . $res['merchant_id'])->getField("mch_id,rate");
        if ($wx_cost_rate) {
            $data['cost_rate'] = $wx_cost_rate['rate'];
        };
        $data['bill_date'] = date("Ymd", time());

        $data['phone_info'] = $_SERVER['HTTP_USER_AGENT'];
        // 微信围餐分配的商户id
//        $mchid = $wx_cost_rate["mch_id"];
        $mchid = $res['wx_mchid'];

        $key = $res["wx_key"];
        $product = '付款' . $price . "元";
        $this->pay_model->add($data);

        $data = array('pay_money' => $price, 'auth_code' => $auth_code, 'remark' => $remark, 'merchant_code' => $mchid, 'product' => $product, 'key' => $key);

        $input = new \WxPayMicroPay();
        $input->setParameter("auth_code", "$auth_code");    // 授权码
        $input->setParameter("body", "$product");  // 商品描述
        $input->setParameter("total_fee", $price * 100); // 总金额
        $input->setParameter("out_trade_no", "$remark");  // 商户订单号
        $input->setParameter("sub_mch_id", $mchid);    // 子商户号
        $this->writlog('wxpay_pos.log', '订单号：' . $remark, 'weixin/');

        $result = $input->pay();

        if ($result['flag'] == false) {
            $this->writlog('wxpay_pos.log', '失败：' . json_encode($result), 'weixin/');
            return array("code" => "error", "msg" => "失败", "data" => $result['message']);
        } else {
            $pay_change = $this->pay_model;
            $data['paytime'] = time();
            $data['status'] = 1;
            if ($pay_change->where("remark=$remark")->find()) $pay_change->where("remark=$remark")->save($data);
            $this->writlog('wxpay_pos.log', '成功：' . json_encode($result), 'weixin/');
            A("App/PushMsg")->push_pay_message($remark);
            return array("code" => "success", "msg" => "成功", "data" => $result['message']);
        }

    }

    /**
     * 刷卡支付 $id, $price, $auth_code, $checker_id
     */
    public function ali_micropay($id, $price, $auth_code, $checker_id, $order_sn, $mode = 2)
    {
        if (!$auth_code) {
            $this->error('参数错误!');
        }
//            支付信息
        if (!$id) {
            return array("code" => "error", "msg" => "失败", "data" => "支付失败");
        }
        $res = M('merchants_cate')->where("merchant_id=$id")->find();
        if ((int)$res['merchant_id'] == 0) {
            return array("code" => "error", "msg" => "失败", "data" => "还未申请支付业务");
        }
        $remark = $order_sn ?: $this->getRemark();
//            插入数据库的数据
        $data['merchant_id'] = (int)$res['merchant_id'];
        $data['checker_id'] = $checker_id;
        $data['paystyle_id'] = 2;
        $data['price'] = $price;
        $data['remark'] = $remark;
        $data['status'] = 0;
        $data['cate_id'] = $res['id'];;
        $data['mode'] = $mode;
        $data['paytime'] = time();
        $data['bank'] = 9;
//        添加的数据
        $wx_cost_rate = M("merchants_szlzwx")->where("mid=" . $res['merchant_id'])->find();
        $token = $wx_cost_rate['ali_token'];
        $appid = $wx_cost_rate['ali_mchid'];
        if ($wx_cost_rate) {
            $data['cost_rate'] = $wx_cost_rate['rate'];
        };
        $data['bill_date'] = date("Ymd", time());

        $data['phone_info'] = $_SERVER['HTTP_USER_AGENT'];
        // 微信围餐分配的商户id
//        $mchid = $wx_cost_rate["mch_id"];
//        $mchid = $res['alipay_partner'];

        $product = '付款' . $price . "元";
        $this->pay_model->add($data);

        $content = array(
            'out_trade_no' => $remark,
            'scene' => 'bar_code',
            'auth_code' => $auth_code,
            'subject' => $product,
            'seller_id' => $wx_cost_rate['ali_mchid'],
            'total_amount' => $price,
            'extend_params' => array(
                'sys_service_provider_id' => '2088721521881652'
            )
        );
        $request = array(
            'app_id' => $this->appid,
            'method' => 'alipay.trade.pay',
            'charset' => 'utf-8',
            'sign_type' => 'RSA',
            'app_auth_token' => $token,
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'biz_content' => json_encode($content),
        );
        $string = $this->getSignContent($request);
        $sign = $this->rsaSign($string, $this->ali_private_key);
        $request['sign'] = $sign;

        $result = $this->curl($this->url, $request);

        $result = json_decode($result, true);
        $result = $result['alipay_trade_pay_response'];

        if ($result['code'] == '10000') {
            $pay_change = $this->pay_model;
            $data['paytime'] = time();
            $data['remark_mer'] = $result['trade_no'];
            $data['status'] = 1;
            if ($pay_change->where("remark=$remark")->find()) $pay_change->where("remark=$remark")->save($data);
            A("App/PushMsg")->push_pay_message($remark);
            $this->writlog('alipay_micro.log', '支付成功：' . json_encode($result), 'ali/');
            return array("code" => "success", "msg" => "成功", "data" => $result['message']);
        } else if ($result['code'] == '10003') {
            return $this->password($request, $remark);
        } else {
            A("App/PushMsg")->push_pay_message($remark);
            $this->writlog('alipay_micro.log', '支付失败：' . json_encode($result), 'ali/');
            return array("code" => "error", "msg" => "失败", "data" => $result['message']);
        }

    }

    /**
     * 刷卡支付 $id, $price, $auth_code, $checker_id
     */
    public function pos_ali_micropay($id, $price, $auth_code, $checker_id, $remark)
    {
        if (!$auth_code) {
            $this->error('参数错误!');
        }
//            支付信息
        if (!$id) {
            return array("code" => "error", "msg" => "失败", "data" => "支付失败");
        }
        $res = M('merchants_cate')->where("merchant_id=$id")->find();
        if ((int)$res['merchant_id'] == 0) {
            return array("code" => "error", "msg" => "失败", "data" => "还未申请支付业务");
        }
//            插入数据库的数据
        $data['merchant_id'] = (int)$res['merchant_id'];
        $data['checker_id'] = $checker_id;
        $data['paystyle_id'] = 2;
        $data['price'] = $price;
        $data['remark'] = $remark;
        $data['status'] = 0;
        $data['cate_id'] = $res['id'];;
        $data['mode'] = 8;
        $data['paytime'] = time();
        $data['bank'] = 9;
//        添加的数据
        $wx_cost_rate = M("merchants_szlzwx")->where("mid=" . $res['merchant_id'])->find();
        $token = $wx_cost_rate['ali_token'];
        $appid = $wx_cost_rate['ali_mchid'];
        if ($wx_cost_rate) {
            $data['cost_rate'] = $wx_cost_rate['rate'];
        };
        $data['bill_date'] = date("Ymd", time());

        $data['phone_info'] = $_SERVER['HTTP_USER_AGENT'];
        // 微信围餐分配的商户id
//        $mchid = $wx_cost_rate["mch_id"];
//        $mchid = $res['alipay_partner'];

        $product = '付款' . $price . "元";
        $this->pay_model->add($data);

        $content = array(
            'out_trade_no' => $remark,
            'scene' => 'bar_code',
            'auth_code' => $auth_code,
            'subject' => $product,
            'seller_id' => $wx_cost_rate['ali_mchid'],
            'total_amount' => $price,
            'extend_params' => array(
                'sys_service_provider_id' => '2088721521881652'
            )
        );
        $request = array(
            'app_id' => $this->appid,
            'method' => 'alipay.trade.pay',
            'charset' => 'utf-8',
            'sign_type' => 'RSA',
            'app_auth_token' => $token,
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'biz_content' => json_encode($content),
        );
        $string = $this->getSignContent($request);
        $sign = $this->rsaSign($string, $this->ali_private_key);
        $request['sign'] = $sign;

        $result = $this->curl($this->url, $request);

        $result = json_decode($result, true);
        $result = $result['alipay_trade_pay_response'];

        if ($result['code'] == '10000') {
            $pay_change = $this->pay_model;
            $data['paytime'] = time();
            $data['remark_mer'] = $result['trade_no'];
            $data['status'] = 1;
            if ($pay_change->where("remark=$remark")->find()) $pay_change->where("remark=$remark")->save($data);
            A("App/PushMsg")->push_pay_message($remark);
            $this->writlog('alipay_pos.log', '支付成功：' . json_encode($result), 'ali/');
            return array("code" => "success", "msg" => "成功", "data" => $result['message']);
        } else if ($result['code'] == '10003') {
            return $this->password($request, $remark);
        } else {
            A("App/PushMsg")->push_pay_message($remark);
            $this->writlog('alipay_pos.log', '支付失败：' . json_encode($result), 'ali/');
            return array("code" => "error", "msg" => "失败", "data" => $result['message']);
        }

    }

    public function password($data, $remark)
    {
        $pay_change = $this->pay_model;
//③、确认支付是否成功
        $queryTimes = 0;
        while ($queryTimes < 30) {
            $succResult = 0;
            $queryResult = $this->ali_query($data, $succResult, $remark);
            //如果需要等待2s后继续
            if ($succResult == 2) {
                sleep(5);
                $queryTimes += 5;
                continue;
            } else if ($succResult == 1) {//查询成功
                $data['remark_mer'] = $queryResult['trade_no'];
                $data['status'] = 1;
                if ($pay_change->where("remark=$remark")->find()) {
                    $pay_change->where("remark=$remark")->save($data);
                }
                return array("code" => "success", "msg" => "成功", "data" => '成功');
            } else {//订单交易失败
                return array("code" => "error", "msg" => "失败", "data" => '支付失败');
            }
        }

        $this->ali_cancel($data, $remark);
        return array("code" => "error", "msg" => "失败", "data" => '订单交易时间过长，已撤销订单');
    }

    private function ali_query($data, &$succResult, $remark)
    {
        $content = array(
            'out_trade_no' => $remark,
        );
        $request = array(
            'app_id' => $this->appid,
            'method' => 'alipay.trade.query',
            'charset' => 'utf-8',
            'sign_type' => 'RSA',
            'app_auth_token' => $data['app_auth_token'],
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'biz_content' => json_encode($content),
        );
        $string = $this->getSignContent($request);
        $sign = $this->rsaSign($string, $this->ali_private_key);
        $request['sign'] = $sign;

        $return = $this->curl($this->url, $request);

        $results = json_decode($return, true);
        $result = $results['alipay_trade_query_response'];
        $this->writlog('alipay_micro.log', 'QUERY：' . json_encode($result), 'ali/');

        if ($result['code'] == '10000' && $result['trade_status'] == 'TRADE_SUCCESS') {
            $succResult = 1;
            return $result;
        } else if ($result['code'] == '10000' && $result['trade_status'] == 'WAIT_BUYER_PAY') {
            $succResult = 2;
        } else {
            $succResult = 3;
        }
    }

    private function ali_cancel($data, $remark)
    {
        $content = array(
            'out_trade_no' => $remark,
        );
        $request = array(
            'app_id' => $this->appid,
            'method' => 'alipay.trade.cancel',
            'charset' => 'utf-8',
            'sign_type' => 'RSA',
            'app_auth_token' => $data['app_auth_token'],
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'biz_content' => json_encode($content),
        );
        $string = $this->getSignContent($request);
        $sign = $this->rsaSign($string, $this->ali_private_key);
        $request['sign'] = $sign;

        $return = $this->curl($this->url, $request);
        $this->writlog('alipay_micro.log', 'CANCEL：' . $return, 'ali/');

        $results = json_decode($return, true);
        $result = $results['alipay_trade_cancel_response'];
        if ($result['code'] == '10000') {
            return true;
        } else {
            return false;
        }
    }

//    public function bhpayback()
//    {
//        $type = I('type');
//        $sign = I('sign');
//        if($sign != '5e022b44a15a90c0'){
//            exit('！');
//        }
//        $remark = I('remark');
//        if($type == 1){
//            $res = $this->pay_back($remark);
//            if($res['code'] == 'success'){
//                exit('success');
//            }
//        }
//        if($type == 2){
//            $res = $this->ali_pay_back($remark);
//            if($res['code'] == 'success'){
//                exit('success');
//            }
//        }
//        exit('error');
//    }

    /**
     * 退款 https://sy.youngport.com.cn/index.php?g=Pay&m=Wxpay&a=pay_back
     * @param $remark 系统订单号
     * @param $price_back 退款金额
     * @return array
     */
    public function pay_back($remark, $price_back)
    {
        Vendor('SzWxPayPubHelper.WxPayPubHelper');
        $payBack = new \Refund_pub();
        // 查找交易记录表获取相关信息
        $pay = $this->pay_model->where("remark='$remark' And status = 1")->find();
        // 获取微信子商户ID
        $wx_mchid = M('merchants_szlzwx')
            ->where(array('mid' => $pay['merchant_id']))
            ->getField('mch_id');
        $payBack->setParameter('sub_mch_id', $wx_mchid);  //子商户号
//        $payBack->setParameter('transaction_id', $pay['wx_remark']);  //微信订单号 商户订单号只需一个，优先使用微信单号
        $payBack->setParameter('out_trade_no', $pay['remark']);  //商户订单号
        $payBack->setParameter('total_fee', $pay['price'] * 100);  //订单金额
        $payBack->setParameter('refund_fee', $price_back * 100);  //申请退款金额
        $payBack->setParameter('out_refund_no', 'tk' . $remark);  //商户退款单号
        $result = $payBack->payBack();
        if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
            $data['status'] = '2';
            $data['back_status'] = '1';
            $data['price_back'] = $result['cash_refund_fee'] / 100;
            $this->pay_model->where(array('remark' => $remark))->save($data);
            $this->writlog('wxpay_back.log', '成功退款:单号：' . $remark . '，返回数据：' . json_encode($result), 'weixin/');
            return array("code" => "success", "msg" => "成功", "data" => "退款成功");
        } else {
            $this->writlog('wxpay_back.log', '退款失败:单号：' . $remark . '，返回数据：' . json_encode($result), 'weixin/');
            return array('code' => 'error', 'msg' => '退款失败', 'data' => '失败');
        }
    }

    public function ali_pay_back($remark, $price_back)
    {
        $pay_data = $this->pay_model->where("remark='$remark' And status = 1")->find();
        if (empty($pay_data)) {
            return array('code' => 'error', 'msg' => '退款失败', 'data' => '失败');
        }
        $token = M('merchants_szlzwx')->where(array('mid' => $pay_data['merchant_id']))->getField('ali_token');
        $content = array(
            'out_trade_no' => $remark,
            //'refund_amount' => $pay_data['price'],
            'refund_amount' => $price_back,
            'out_request_no' => $this->getRemark(),
        );
        $request = array(
            'app_id' => $this->appid,
            'method' => 'alipay.trade.refund',
            'charset' => 'utf-8',
            'sign_type' => 'RSA',
            'app_auth_token' => $token,
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'biz_content' => json_encode($content),
        );
        $this->writlog('alipay_back.log', 'REFUND-PARAMS：' . json_encode($request), 'ali/');
        $string = $this->getSignContent($request);
        $sign = $this->rsaSign($string, $this->ali_private_key);
        $request['sign'] = $sign;

        $return = $this->curl($this->url, $request);

        $results = json_decode($return, true);
        $result = $results['alipay_trade_refund_response'];
        if ($result['code'] == '10000') {
            $data['status'] = '2';
            $data['back_status'] = '1';
            $data['price_back'] = $result['refund_fee'];
            $this->pay_model->where(array('remark' => $remark))->save($data);
            $this->writlog('alipay_back.log', 'REFUND-SUCC：' . $return, 'ali/');
            return array("code" => "success", "msg" => "成功", "data" => "退款成功");
        } else {
            $this->writlog('alipay_back.log', 'REFUND-FAIL：' . $return, 'ali/');
            return array('code' => 'error', 'msg' => '退款失败', 'data' => '失败');
        }

    }

    /**
     * 对账单下载
     */
    public function check_order()
    {
        $filename = 'wxpay_bill.log';
//        if(IS_POST){
        $time = I('time', '');
        $time = !empty($time) ? $time : date("Ymd", strtotime("-1 day"));
        $check = M('everyday_wx_bill')->where(array('bill_date' => $time))->find();
        if ($check) {
            exit('已获取');
        }
        Vendor('SzWxPayPubHelper.WxPayPubHelper');
        $download = new \Wxpay_client_pub;
//            $download->setParameter('sub_mch_id', '');// 微信支付分配的子商户号，如需下载指定的子商户号对账单，则此参数必传。
        $download->setParameter('bill_date', $time);// 下载对账单的日期，格式：20140603
        $download->setParameter('bill_type', 'ALL');// ALL，返回当日所有订单信息,默认值。SUCCESS,返回当日成功支付的订单。REFUND,返回当日退款订单RECHARGE_REFUND，返回当日充值退款订单（相比其他对账单多一栏“返还手续费”）
//            $download->setParameter('tar_type', '');// 非必传参数，固定值：GZIP，返回格式为.gzip的压缩包账单。不传则默认为数据流形式。
        $download->url = \WxPayConf_pub::BILL_URL;
        $download->curl_timeout = 5;
        $response = $download->getBillResult();
        if (substr($response, 1, 3) == 'xml') {
            $response = $download->xmlToArray($response);
            $this->writlog($filename, '获取账单失败:' . json_encode($response), 'weixin/');
            exit;
        }
        $this->writlog($filename, '获取账单成功:' . $time, 'weixin/');
        $this->insert_bill($response);

//            $this->success('已成功获取！');exit;
//        }
//        $this->display();
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
        //交易时间,公众账号ID,商户号,子商户号,设备号,微信订单号,商户订单号,用户标识,交易类型,交易状态,付款银行,货币种类,总金额, 代金券或立减优惠金额,微信退款单号,商户退款单号,退款金额, 代金券或立减优惠退款金额，退款类型，退款状态,商品名称,商户数据包,手续费,费率
        $arr[0] = array('bill_date', 'app_id', 'mchid', 'sub_mchid', 'device_info', 'wx_order_sn', 'mch_order_sn', 'openid', 'deal_type', 'deal_status', 'pay_bank', 'currency_type', 'deal_money', 'discount', 'wx_pay_back_sn', 'mch_pay_back_sn', 'pay_back_money', 'pay_back_discount', 'pay_back_type', 'pay_back_status', 'goods_name', 'goods_detail', 'poundage', 'cost_cate', 'add_time');
        //日期,交易总笔数,消费交易笔数,退货交易笔数,冲正交易笔数,交易总金额,手续费总额,代理商手续费总额,清算总金额,添加时间
        $arr[$length - 2] = array('bill_date', 'total_deal', 'consume_deal', 'return_deal', 'reverse_deal', 'total_money', 'poundage', 'anency_poudage', 'pay_money', 'add_time');
        //总交易单数,总交易额,总退款金额,总代金券或立减优惠退款金额,手续费总金额
        $arr[$length - 2] = array('total_deal', 'total_money', 'total_pay_back_money', 'total_pay_back_discount', 'poundage', 'bill_date', 'add_time');
        $bill_time = '';
        foreach ($arr as $k => $v) {
            if ($k != 0 && $k < $length - 2) {
                $array = array();
                array_map(function ($item) use (&$array) {
                    $array[] = substr($item, 1);
                }, $v);
                array_push($array, time());
                $array[0] = strtotime($array[0]);
                $bill_time = $array[0];
                $detail_arr[] = array_combine($arr[0], $array);
            }
            if ($k == $length - 1) {
                $array = array();
                array_map(function ($item) use (&$array) {
                    $array[] = substr($item, 1);
                }, $v);
                array_push($array, date('Ymd', $bill_time));
                array_push($array, time());
                $count_arr[] = array_combine($arr[$length - 2], $array);
            }

        }
        $billRecordModel = M('bill_wx');
        $everydayBillCountModel = M('everyday_wx_bill');
        array_map(function ($item) use ($billRecordModel) {
            $billRecordModel->add($item);
        }, $detail_arr);
        array_map(function ($item) use ($everydayBillCountModel) {
            $everydayBillCountModel->add($item);
        }, $count_arr);
    }

    public function rsaSign($data, $privatekey)
    {
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($privatekey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";

        ($res) or die('您使用的私钥格式错误，请检查RSA私钥配置');
        openssl_sign($data, $sign, $res);
        $sign = base64_encode($sign);
        return $sign;
    }

    public function getSignContent($params)
    {
        ksort($params);

        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if ($i == 0) {
                $stringToBeSigned .= "$k" . "=" . "$v";
            } else {
                $stringToBeSigned .= "&" . "$k" . "=" . "$v";
            }
            $i++;
        }
        unset ($k, $v);
        return $stringToBeSigned;
    }

    protected function curl($url, $postFields = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $postBodyString = "";

        if (is_array($postFields) && 0 < count($postFields)) {
            foreach ($postFields as $k => $v) {
                if ("@" != substr($v, 0, 1)) //判断是不是文件上传
                {

                    $postBodyString .= "$k=" . urlencode($v) . "&";
                }
            }
            unset ($k, $v);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, substr($postBodyString, 0, -1));
        }
        $headers = array('content-type: application/x-www-form-urlencoded;charset=UTF-8');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $reponse = curl_exec($ch);
        if ($reponse) {
            curl_close($ch);
            return $reponse;
        } else {
            $error = curl_errno($ch);
            echo "curl出错，错误码:$error" . "<br>";
            echo "<a href='http://curl.haxx.se/libcurl/c/libcurl-errors.html'>错误原因查询</a></br>";
            curl_close($ch);
            return false;
        }
    }

    private function getRemark()
    {
        return date('YmdHis') . rand(100000, 999999);
    }

    public function getoken()
    {
        $mid = I('mid');
        $filename = 'alipay_token.log';
        $auth_code = I('app_auth_code');
        $content = array(
            'grant_type' => 'authorization_code',
            'code' => $auth_code,
        );
        $request = array(
            'app_id' => $this->appid,
            'method' => 'alipay.open.auth.token.app',
            'charset' => 'utf-8',
            'sign_type' => 'RSA',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'biz_content' => json_encode($content),
        );
        $string = $this->getSignContent($request);
        $sign = $this->rsaSign($string, $this->ali_private_key);
        $request['sign'] = $sign;
        $this->writlog($filename, '接收参数：' . json_encode($_REQUEST), 'ali/');
        $this->writlog($filename, '请求参数：' . json_encode($request), 'ali/');
        $return = $this->curl($this->url, $request);
        $this->writlog($filename, '返回结果:' . $return, 'ali/');

        header("Content-type:text/html;charset=utf-8");
        $results = json_decode($return, true);
        $result = $results['alipay_open_auth_token_app_response'];
        M("merchants_szlzwx")->where(array('mid' => $mid))->save(array('ali_token' => $result['app_auth_token']));
        if ($result['code'] == '10000' && $result['msg'] == 'Success') {
            echo '<script type="text/javascript">alert("授权成功")</script>';
        }
    }

    private function writlog($file_name, $data, $load)
    {
        file_put_contents('./data/log/szlz/' . $load . date("Y_m_") . $file_name, date("Y-m-d H:i:s") . $data . PHP_EOL . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

}
