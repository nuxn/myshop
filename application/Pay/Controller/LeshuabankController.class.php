<?php

namespace Pay\Controller;

use Common\Controller\HomebaseController;

/**
 * Class LeshuabankController
 * @package Pay\Controller
 */
class LeshuabankController extends HomebaseController
{
    private $url;
    private $notify_url;
    private $remark;
    private $mch_id;
    private $key;
    private $subject = '';
    private $order_id = 0;
    private $customer_id = 0;
    private $bank = 12;
    private $paystyle_id;
    private $cate_id = 0;
    private $mode;
    private $rate;
    private $is_t = 0;
    private $client_ip;
    private $app_flag = 0;
    private $methodis = '-';
    private $pay_model;

    function __construct()
    {
        parent::__construct();
        $this->url = "https://mobilepos.yeahka.com/cgi-bin/lepos_pay_gateway.cgi";
        $this->notify_url = "https://sy.youngport.com.cn/notify/leshua_notify.php";
//        $this->mch_notify_url = "https://sy.youngport.com.cn/notify/ls_mch_notify.php";
//        $this->mch_id = '0000000018';
//        $this->key = 'a1613a0e7cb9d3a51e33784ee4d212ac';
        $this->mch_id = '9307002285';
        $this->key = 'FBF50AD4E24183AD42DD5F259200FDB7';
        $this->pay_model = M('pay');
    }

    // 扫码支付
    public function precreate($data)
    {
        $pay_style = $data['pay_style'];
        switch ($pay_style) {
            case 1:
                $pay_way = 'WXZF';
                break;
            case 2:
                $pay_way = 'ZFBZF';
                break;
            case 3:
                return array('code' => '1000', 'msg' => '暂未开放');
                $pay_way = 'QQZF';
                break;
            case 5:
                return array('code' => '1000', 'msg' => '暂未开放');
                $pay_way = 'UPSMZF';
                break;
            default:
                $pay_way = 'WXZF';
                break;
        }
        $notify_url = 'https://sy.youngport.com.cn/Pay/Notify/leshua_notify';
        $res = $this->getIntoInfo($data['mch_id'], $pay_style);
        if (!$res) return array('code' => '1001', 'msg' => '未进件，请联系客服。');
        $param['service'] = 'get_tdcode';
        $param['pay_way'] = $pay_way;
        $param['merchant_id'] = $this->mch_id;       //商户号
        $param['third_order_id'] = $data['remark'];         //商户订单号
        $param['amount'] = ($data['amount'] * 100);       //金额
        $param['jspay_flag'] = 0;
        $param['t0'] = $this->is_t;
        $param['body'] = $data['body'];
        $param['client_ip'] = $this->client_ip;//ip
        $param['notify_url'] = $notify_url; //回调地址
        $param['nonce_str'] = $this->getNonceStr();//随机字符串
        $param['sign'] = $this->getSignVeryfy($param, 'FBF50AD4E24183AD42DD5F259200FDB7');//签名
        $this->writeLog('precreate.log', ':参数', $param);
        $post_data = http_build_query($param);

        $url = $this->url;
        $res = $this->httpRequst($url, $post_data);
        $res_arr = $this->xmlToArray($res);
        $this->writeLog('precreate.log', ':结果', $res_arr);
        // 判断返回结果
        if ($this->check_sign($res_arr)) {
            if ($res_arr['resp_code'] == 0 && $res_arr['result_code'] == 0) {
                $url = $res_arr['td_code'];
                return array('code' => '0000', 'url' => $url, 'rate' => $this->rate);
            } else {
                return array('code' => '1001', 'msg' => $res_arr['error_msg']);
            }
        } else {
            return array('code' => '1000', 'msg' => '通道签名失败');
        }
    }

    public function qr_weixipay()
    {
        //这里直接获得openid;
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            $id = I("id");
            $merchant = M("merchants_cate")->where("id=$id")->find();
            if ($merchant['use_other_appid']) {
                $this->app_flag = 1;
                $this->merch_id = $merchant['merchant_id'];
            } else {
                $this->app_flag = 0;
            }
            $openid = $this->get_openid();
            if (!$this->app_flag) {
                $this->getOffer($merchant, $openid);
            }
            $this->assign('openid', $openid);
            $this->assign("merchant", $merchant);
            $this->assign('seller_id', I('id'));
            $this->display();
            die;
        }
    }

    // 固定支付金额支付
    public function wx_pay()
    {
        header("Content-type:text/html;charset=utf-8");
        // 先获取openid
        if (I("seller_id") == "") {
            $id = I("id");
            $cate_info = $this->get_cate_info($id);
            if ($cate_info['use_other_appid']) {
                $this->app_flag = 1;
                $this->merch_id = $cate_info['merchant_id'];
            } else {
                $this->app_flag = 0;
            }
            $sub_openid = $this->get_openid();
            $this->mode = 1;
            $checker_id = I("checker_id");
        }
        if (I('seller_id') !== "") {
            $sub_openid = I('openid');
            $id = I('seller_id');
            $cate_info = $this->get_cate_info($id);
            $checker_id = $cate_info['checker_id'];
            $this->mode = 0;
        }
        $this->cate_info = $cate_info;
        $this->cate_id = $cate_info['id'];
        $price = I('price');
        $jmt_remark = I('memo', '') ?: I("jmt_remark", '');
        $this->getIntoInfo($cate_info['merchant_id'], 1);
        // 插入数据库的数据
        $this->customer_id = D("Api/ScreenMem")->add_member("$sub_openid", $cate_info['merchant_id']);
        $this->remark = I('order_sn', date('YmdHis') . rand(100000, 999999));
        if ($this->pay_model->where(array('remark' => $this->remark))->find()) {
            $this->writeLog('wxJSpay.log', ":订单已存在", '订单已存在', 0);
            $this->alert_err("订单已存在");
        }
        $this->subject = $cate_info['jianchen'];
        $this->paystyle_id = 1;
        $db_res = $this->add_db($cate_info['merchant_id'], $price, $checker_id, $jmt_remark);
        if ($db_res) {
            // 请求服务器获取js支付参数
            $res_arr = $this->js_pay($sub_openid, $price);
            // 判断返回结果
            if ($this->check_sign($res_arr)) {
                if ($res_arr['resp_code'] == 0 && $res_arr['result_code'] == 0) {
                    $this->writeLog('wxJSpay.log', ":请求成功", $res_arr);
                    $body = $res_arr['jspay_info'];
                    $this->assign('body', $body);
                    $this->assign('price', $price);
                    $this->assign('openid', $sub_openid);
                    $this->assign('remark', $this->remark);
                    $this->assign('mid', $cate_info['merchant_id']);
                    $this->display();
                } else {
                    $this->writeLog('wxJSpay.log', ":失败", $res_arr);
                    $this->alert_err($res_arr['error_msg']);
                }
            } else {
                $this->writeLog('wxJSpay.log', ":签名错误", $res_arr);
                $this->alert_err();
            }
        } else {
            $this->alert_err();
        }
    }

    // 有订单号的支付
    public function two_wx_pay()
    {
        header("Content-type:text/html;charset=utf-8");
        $order_id = I("order_id");
        $this->mode = I("mode", 3);
        $id = I("id");
        $sub_openid = I("openid", '');
        if ($order_id) {
            $cate_info = $this->get_cate_info($id);
            if (!$sub_openid) {
                if ($cate_info['use_other_appid']) {
                    $this->app_flag = 1;
                    $this->merch_id = $cate_info['merchant_id'];
                } else {
                    $this->app_flag = 0;
                }
                $sub_openid = $this->get_openid();
            }
            $order_info = M("order")->where("order_id='$order_id'")->find();
            if ($this->pay_model->where("order_id='$order_id'")->getField('id')) {
                $this->alert_err('订单已存在');
            }
            $this->cate_info = $cate_info;
            $this->cate_id = $cate_info['id'];
            $remark = $order_info['order_sn'];
            $price = $order_info['order_amount'];
            $jmt_remark = I('memo', '') ?: I("jmt_remark", '');
            $this->getIntoInfo($cate_info['merchant_id'], 1);
            // 插入数据库的数据
            $this->customer_id = D("Api/ScreenMem")->add_member("$sub_openid", $cate_info['merchant_id']);
            $this->remark = $remark;
            $this->subject = $cate_info['jianchen'];
            $this->paystyle_id = 1;
            $this->order_id = $order_id;
            $db_res = $this->add_db($cate_info['merchant_id'], $price, 0, $jmt_remark);
            if ($db_res) {
                // 请求服务器获取js支付参数
                $res_arr = $this->js_pay($sub_openid, $price);
                // 判断返回结果
                if ($this->check_sign($res_arr)) {
                    if ($res_arr['resp_code'] == 0 && $res_arr['result_code'] == 0) {
                        $this->writeLog('wxJSpay.log', ":请求成功", $res_arr);
                        $body = $res_arr['jspay_info'];
                        $this->assign('body', $body);
                        $this->assign('price', $price);
                        $this->assign('openid', $sub_openid);
                        $this->assign('remark', $this->remark);
                        $this->assign('mid', $cate_info['merchant_id']);
                        $this->display("wx_pay");
                        die;
                    } else {
                        $this->writeLog('wxJSpay.log', ":失败", $res_arr);
                        $this->alert_err();
                    }
                } else {
                    $this->writeLog('wxJSpay.log', ":签名错误", $res_arr);
                    $this->alert_err();
                }
            } else {
                $this->alert_err();
            }
        } else {
            $this->alert_err('订单号为空');
        }
    }

    public function wx_micropay($id, $price, $auth_code, $checker_id, $jmt_remark, $order_sn, $mode)
    {
        $this->paystyle_id = 1;
        $this->pay_way = 'WXZF';
        $this->mode = $mode ?: 2;
        $this->subject = "支付{$price}元";
        $this->getIntoInfo($id, 1);
        if ($order_sn) {
            $this->remark = $order_sn;
        } else {
            $this->remark = date('YmdHis') . rand(100000, 999999);
        }
//        if($jmt_remark == 'ypttest'){
//            $result = D('Pay/Pay')->card_off($auth_code, $id, $price, $this->remark, $checker_id, $jmt_remark);
//            if($result){
//                if($result['status'] === 1){
//                    A('Pay/Barcode')->cardOff($result['order_id']);
//                    return array('code'=>'success');
//                } else {
//                    $this->order_id = $result['order_id'];
//                    $this->customer_id = $result['customer_id'];
//                    $price = $result['price'];
//                }
//            }
//        }
        return $this->micropay($id, $price, $auth_code, $checker_id, $jmt_remark);
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
            die;
        }
    }

    public function qr_to_alipay()
    {
        get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/test/', 'lehusa', 'qr_to_alipay', json_encode($_GET));
        get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/test/', 'lehusa', 'qr_to_alipay', I("checker_id"));
        header("Content-type:text/html;charset=utf-8");
        $id = I("seller_id");
        $checker_id = I("checker_id");
        $price = I("price");
        $cate_info = $this->get_cate_info($id);
        $this->cate_id = $cate_info['id'];
        $this->getIntoInfo($cate_info['merchant_id'], 2);
        $jmt_remark = I('memo', '') ?: I("jmt_remark", '');
        // 插入数据库的数据
        $this->mode = 0;
        $this->remark = I('order_sn', date('YmdHis') . rand(100000, 999999));
        if ($this->pay_model->where(array('remark' => $this->remark))->find()) {
            $this->writeLog('wxJSpay.log', ":订单已存在", '订单已存在', 0);
            $this->alert_err("订单已存在");
        }
        $this->subject = $cate_info['jianchen'];
        $this->paystyle_id = 2;
        $db_res = $this->add_db($cate_info['merchant_id'], $price, $checker_id, $jmt_remark);
        if ($db_res) {
            // 请求服务器获取js支付参数
            $res_arr = $this->ali_pay($price);
            // 判断返回结果
            if ($this->check_sign($res_arr)) {
                if ($res_arr['resp_code'] == 0 && $res_arr['result_code'] == 0) {
                    $url = $res_arr['td_code'];
                    header("Location: $url");
                } else {
                    $this->alert_err($res_arr['error_msg']);
                }
            } else {
                $this->alert_err();
            }
        } else {
            $this->alert_err();
        }
    }

    public function two_alipay()
    {
        header("Content-type:text/html;charset=utf-8");
        $order_id = I("order_id");
        $this->mode = I("mode", 3);
        $checker_id = I("checker_id", 0);
        $id = I("seller_id");
        if ($order_id) {
            if ($this->pay_model->where("order_id='$order_id'")->getField('id')) {
                $this->alert_err('订单已存在');
            }
            $order_info = M("order")->where("order_id='$order_id'")->find();
            $cate_info = $this->get_cate_info($id);
            $this->cate_id = $cate_info['id'];
            $remark = $order_info['order_sn'];
            $price = $order_info['order_amount'];
            $jmt_remark = I("jmt_remark", '');
            $this->getIntoInfo($cate_info['merchant_id'], 2);
            // 插入数据库的数据
            $this->remark = $remark;
            $this->subject = $cate_info['jianchen'];
            $this->paystyle_id = 2;
            $this->order_id = $order_id;
            $db_res = $this->add_db($cate_info['merchant_id'], $price, $checker_id, $jmt_remark);
            if ($db_res) {
                // 请求服务器获取js支付参数
                $res_arr = $this->ali_pay($price);
                // 判断返回结果
                if ($this->check_sign($res_arr)) {
                    if ($res_arr['resp_code'] == 0 && $res_arr['result_code'] == 0) {
                        $url = $res_arr['td_code'];
                        header("Location: $url");
                    } else {
                        $this->alert_err();
                    }
                } else {
                    $this->alert_err();
                }
            } else {
                $this->alert_err();
            }
        } else {
            $this->alert_err('订单号为空');
        }
    }

    public function ali_micropay($id, $price, $auth_code, $checker_id, $jmt_remark, $order_sn, $mode)
    {
        $this->paystyle_id = 2;
        $this->pay_way = 'ZFBZF';
        $this->mode = $mode ?: 2;
        $this->subject = "支付{$price}元";
        $this->getIntoInfo($id, 2);
        if ($order_sn) {
            $this->remark = $order_sn;
        } else {
            $this->remark = date('YmdHis') . rand(100000, 999999);
        }

        return $this->micropay($id, $price, $auth_code, $checker_id, $jmt_remark);
    }

    public function notify()
    {
        $data = file_get_contents('php://input');
        $result_arr = $this->xmlToArray($data);
        if ($result_arr['error_code'] == '0' && $result_arr['status'] == '2') {
            $order_sn = $result_arr['third_order_id'];
            $transId = $result_arr['leshua_order_id'];
            $orderData = $this->pay_model->where(array('remark' => $order_sn))->find();
            if ($orderData) {
                if ($orderData['status'] == 0) {
                    $save['transId'] = $transId;
                    $save['paytime'] = time();
                    $save['status'] = 1;
                    if (bccomp($orderData['price'] * 100, $result_arr['amount'], 3) === 0) {
                        $this->pay_model->where(array('id' => $orderData['id']))->save($save);
                        $this->writeLog('notify.log', ':支付成功', $result_arr);
                        echo '000000';
                        if ($result_arr['pay_way'] == 'WXZF' && $orderData['order_id'] != 0) {
                            A('Barcode')->cardOff($orderData['order_id']);
                        }
                        A("App/PushMsg")->push_pay_message($order_sn);
                    } else {
                        $this->writeLog('notify.log', ':金额不等', $result_arr);
                    }
                } else if ($orderData['status'] == 1) {
                    $this->writeLog('notify.log', ':二次通知', $result_arr);
                    exit("000000");
                } else {
                    $this->writeLog('notify.log', ':订单状态异常', $result_arr);
                    echo "error";
                }
            } else {
                $this->writeLog('notify.log', ':订单不存在', $result_arr);
                exit("000000");
            }
        } else {
            $this->writeLog('notify.log', ':支付失败', $data);
            echo "error";
        }
    }

    public function mch_notify()
    {
        header("Content-Type: application/json");
        $post = $_POST;
        $get = $_GET;
        $this->writeLogA('mch_notify.log', ':通知数据GET', $get);
        $this->writeLogA('mch_notify.log', ':通知数据POST', $post);
        $input = $post;
        $sParam = $input['sParam'] ?: $_GET['sParam'];
        if (empty($sParam)) {
            $return['bResult'] = false;
            $return['errMsg'] = 'data is null';
            $this->writeLogA('mch_notify.log', ':数据未收到', 'null', 0);
            exit(json_encode($return));
        }
        $notifyData = json_decode($sParam, true);
        $flag = M('merchants_leshua')->where(array('merchantId' => $notifyData['sMerchantld']))->find();
        // shanpay数据库配置
        $db_shanpay = C('DB_SHANPAY');
        if ($notifyData['sStatus'] == 0) {
            $data['update_status'] = 3;
            if ($flag) {
                # App后台进件修改
                $db = 'APP';
                M('merchants_leshua')->where(array('merchantId' => $notifyData['sMerchantld']))->save($data);
            } else {
                # Api后台进件修改
                $db = 'API-shanpay';
                M("api_bank_intoleshua", "ypt_", "$db_shanpay")->where(array('merchantId' => $notifyData['sMerchantld']))->save($data);
            }
            $return['bResult'] = true;
            $return['errMsg'] = 'null';
            $this->writeLogA('mch_notify.log', ':修改成功' . $db, $notifyData['sMerchantld'] . PHP_EOL, 0);
            exit(json_encode($return));
        } else {
            $data['update_status'] = 1;
            $data['err_msg'] = $notifyData['sFailReason'];
            if ($flag) {
                # App后台进件修改
                $db = 'APP';
                M('merchants_leshua')->where(array('merchantId' => $notifyData['sMerchantld']))->save($data);
            } else {
                # Api后台进件修改
                $db = 'API-shanpay';
                M("api_bank_intoleshua", "ypt_", "$db_shanpay")->where(array('merchantId' => $notifyData['sMerchantld']))->save($data);
            }
            $return['bResult'] = true;
            $return['errMsg'] = 'status error';
            $this->writeLogA('mch_notify.log', ':修改失败' . $db, $notifyData['sMerchantld'], 0);
            $this->writeLogA('mch_notify.log', ':失败原因' . $db, $notifyData['sFailReason'] . PHP_EOL, 0);
            exit(json_encode($return));
        }
    }

    // 查询订单状态
    public function query($remark)
    {
        $pay_info = $this->pay_model
            ->field('p.transId,ls.merchantId,ls.key')
            ->join('p left join ypt_merchants_leshua ls on p.merchant_id=ls.m_id')
            ->where(array('remark' => $remark))
            ->find();
        $this->mch_id = $pay_info['merchantId'];
        $this->key = $pay_info['key'];
        $data['merchant_id'] = $this->mch_id;//商户号
        $data['service'] = 'query_status';
        $data['third_order_id'] = $remark;//商户系统内部的订单号
        $data['nonce_str'] = $this->getNonceStr();//UCHANG订单号，优先使用
        $data['sign'] = $this->getSignVeryfy($data, $this->key);
        $this->writeLog('query.log', ':参数', $data);
        $res = $this->httpRequst($this->url, $data);
        $res_arr = $this->xmlToArray($res);
        $this->writeLog('query.log', ':结果', $res_arr);

        return $res_arr;
    }

    // 退款
    public function refund($remark)
    {
        $pay_info = $this->pay_model
            ->field('p.transId,ls.merchantId,ls.key')
            ->join('p left join ypt_merchants_leshua ls on p.merchant_id=ls.m_id')
            ->where(array('remark' => $remark))
            ->find();
        $this->mch_id = $pay_info['merchantId'];
        $this->key = $pay_info['key'];
        $param['service'] = 'refund';
        $param['merchant_id'] = $this->mch_id;
        $param['third_order_id'] = $remark;
        $param['leshua_order_id'] = $pay_info['transId'];
        $param['nonce_str'] = $this->getNonceStr();
        $param['sign'] = $this->getSignVeryfy($param, $this->key);
        $this->writeLog('refund.log', ':参数', $param);
        $res = $this->httpRequst($this->url, $param);
        $res_arr = $this->xmlToArray($res);
        if ($res_arr['resp_code'] == '0' && $res_arr['result_code'] == '0' && ($res_arr['status'] == '11' || $res_arr['status'] == '10')) {
            $this->pay_model->where("remark='$remark'")->save(array("status" => 2, "back_status" => 1, "price_back" => $res_arr['amount'] / 100));
            $this->writeLog('refund.log', ':退款成功', $res_arr);
            return array("code" => "success", "msg" => "成功", "data" => "退款成功");
        } else if($res_arr['status'] == '10'){
            return $this->query_refund($param);
        } else {
            $this->writeLog('refund.log', ':退款失败', $res_arr);
            return array("code" => "error", "msg" => "error", "data" => "退款失败");
        }

    }

    public function query_refund($param)
    {
        sleep(6);
        $data['merchant_id'] = $this->mch_id;//商户号
        $data['service'] = 'query_status';
        $data['third_order_id'] = $param['third_order_id'];//商户系统内部的订单号
        $data['nonce_str'] = $this->getNonceStr();//UCHANG订单号，优先使用
        $data['sign'] = $this->getSignVeryfy($data, $this->key);
//        $data = $param;
        $this->writeLog('query_refund.log', ':参数', $data);
        $queryTimes = 6;
        while ($queryTimes > 0) {
            $queryTimes--;
            sleep(5);
            $res = $this->httpRequst($this->url, $data);
            $query_res = $this->xmlToArray($res);
            if ($query_res['resp_code'] == 0 && $query_res['result_code'] == 0) {
                if ($query_res['status'] == '10') {
                    $this->writeLog('query_refund.log', ':继续查询', $query_res);
                    continue;
                } else if ($query_res['status'] == '11') {
                    $this->writeLog('query_refund.log', ':退款成功', $query_res);
                    return array("code" => "success", "msg" => "成功", "data" => "退款成功");
                } else {
                    $this->writeLog('query_refund.log', ':退款失败', $query_res);
                    return array("code" => "error", "msg" => "失败", "data" => '请重试');
                }
            } else {
                $this->writeLog('query_refund.log', ':退款失败', $query_res);
                return array("code" => "error", "msg" => "失败", "data" => '请重试');
            }
        }
                return array("code" => "error", "msg" => "失败", "data" => '请重试');
    }

    public function myback()
    {
        $param['service'] = 'refund';
        $param['merchant_id'] = $this->mch_id;
        $param['third_order_id'] = I('remark');
        $param['leshua_order_id'] = I('san');
        $param['nonce_str'] = $this->getNonceStr();
        $param['sign'] = $this->getSignVeryfy($param, $this->key);
        $this->writeLog('refund.log', ':参数', $param);
        $res = $this->httpRequst($this->url, $param);
        $res_arr = $this->xmlToArray($res);
        $this->writeLog('refund.log', ':参数', $res_arr);
        $this->ajaxReturn($res_arr);
    }

    /**
     * @param $mch_id 洋仆淘商户ID
     * @param $way  支付方式1-微信,2-支付宝
     * @return bool
     */
    private function getIntoInfo($mch_id, $way)
    {
        $into_data = M('merchants_leshua')->field('m_id,merchantId,key,is_t0,ip_address,wx_t0_rate,wx_t1_rate,ali_t0_rate,ali_t1_rate')->where("m_id=$mch_id")->find();
        if (!$into_data) return false;

        $this->mch_id = $into_data['merchantId'];
        $this->key = $into_data['key'];
        $this->is_t = $into_data['is_t0'];
        $this->client_ip = $into_data['ip_address'] ?: $_SERVER['REMOTE_ADDR'];//IP
        if ($way == 1) {
            $this->rate = $this->is_t ? $into_data['wx_t0_rate'] : $into_data['wx_t1_rate'];
        } else {
            $this->rate = $this->is_t ? $into_data['ali_t0_rate'] : $into_data['ali_t1_rate'];
        }
        return true;
    }

    # 获取微信openID
    private function get_openid()
    {
        // 获取配置项
        if ($this->app_flag) {
            $data = M('merchants_leshua')->where("m_id=" . $this->merch_id)->find();
            $config['APPID'] = $data['pay_appid'];
            $config['APPSECRET'] = $data['pay_secret'];
        } else {
            $config = C('WEIXINPAY_CONFIG');
        }
        // 如果没有get参数没有code；则重定向去获取openid；
        if (!isset($_GET['code'])) {
            // 返回的url
            $redirect_uri = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            if ($_GET['id'] == 7) {
                dump($_SERVER['HTTP_HOST']);
                dump($_SERVER['REQUEST_URI']);
            }
            $redirect_uri = urlencode($redirect_uri);
            $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $config['APPID'] . '&redirect_uri=' . $redirect_uri . '&response_type=code&scope=snsapi_base#wechat_redirect';

            redirect($url);
            exit;
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

    /**
     * 获取台签信息
     * @param $id 台签id
     * @return mixed
     */
    private function get_cate_info($id)
    {
        $res = M('merchants_cate')->where(array('id' => $id))->find();
        return $res;
    }

    private function check_sign($data)
    {
        if (isset($data['sign'])) {
            $sign = $data['sign'];
            unset($data['sign']);
            unset($data['resp_code']);
            $new_sign = $this->getSignVeryfy($data, $this->key);
            if ($sign == $new_sign) {
                return true;
            }
        }
        return false;
    }

    private function alert_err($msg = '网络错误，请稍后再试')
    {
        $this->assign('err_msg', "$msg");
        $this->display(":Barcodexybank/error");
        exit;
    }

    private function js_pay($open_id, $price)
    {
        $param['service'] = 'get_tdcode';
        $param['pay_way'] = 'WXZF';
        $param['merchant_id'] = $this->mch_id;//商户号
        $param['third_order_id'] = $this->remark;//商户订单号
        $param['amount'] = ($price * 100);//金额
        $param['jspay_flag'] = 1;
        $param['sub_openid'] = $open_id;
        $param['client_ip'] = $this->client_ip;
//        $param['client_ip'] = '61.191.122.83';//IP 61.191.122.83,113.27.82.122,117.136.4.152
        $param['notify_url'] = $this->notify_url;//回调地址
        $param['t0'] = $this->is_t;
        $param['body'] = "向" . $this->cate_info['jianchen'] . "支付￥{$price}元";
        $param['nonce_str'] = $this->getNonceStr();//随机字符串
        $param['sign'] = $this->getSignVeryfy($param, $this->key);//签名
        $this->writeLog('wxJSpay.log', ':参数', $param);
        $url = $this->url;
        $res = $this->httpRequst($url, $param);
        $res_arr = $this->xmlToArray($res);

        return $res_arr;
    }

    private function ali_pay($price)
    {
        $param['service'] = 'get_tdcode';
        $param['pay_way'] = 'ZFBZF';
        $param['merchant_id'] = $this->mch_id;//商户号
        $param['third_order_id'] = $this->remark;//商户订单号
        $param['amount'] = ($price * 100);//金额
        $param['jspay_flag'] = 0;
        $param['t0'] = $this->is_t;
        $param['client_ip'] = $this->client_ip;//IP
        $param['notify_url'] = $this->notify_url;//回调地址
        $param['nonce_str'] = $this->getNonceStr();//随机字符串
        $param['sign'] = $this->getSignVeryfy($param, $this->key);//签名
        $this->writeLog('aliJSpay.log', ':参数', $param);

        $url = $this->url;
        $res = $this->httpRequst($url, $param);
        $res_arr = $this->xmlToArray($res);
        $this->writeLog('aliJSpay.log', ":结果", $res_arr);

        return $res_arr;
    }

    private function micropay($id, $price, $auth_code, $checker_id, $jmt_remark)
    {
        //插入数据库的数据
        $db_res = $this->add_db($id, $price, $checker_id, $jmt_remark);
        $error = array("code" => "error", "msg" => "失败", "data" => '请重试');
        if ($db_res) {
            $res_arr = $this->post_micropay($price, $auth_code);
            /*if($this->check_sign($res_arr)){*/
            if ($res_arr['resp_code'] == '0' && $res_arr['result_code'] == '0') {
                if ($res_arr['status'] == '0') {
                    return $this->password($this->remark);
                } else if ($res_arr['status'] == '2') {
                    $this->pay_model->where(array("remark" => $this->remark))
                        ->save(array("status" => "1", "paytime" => time(), 'transId' => $res_arr['leshua_order_id']));
                    return array("code" => "success", "msg" => "成功", "data" => '支付成功');
                } else {
                    return $error;
                }
            } else {
                return $error;
            }
            /*} else {
                return $error;
            }*/
        } else {
            return $error;
        }
    }

    private function post_micropay($price, $auth_code)
    {
        $param['service'] = 'upload_authcode';
        $param['pay_way'] = $this->pay_way;
        $param['merchant_id'] = $this->mch_id;//商户号
        $param['third_order_id'] = $this->remark;//商户订单号
        $param['amount'] = ($price * 100);//金额
        $param['client_ip'] = $this->client_ip;
        $param['t0'] = $this->is_t;
        $param['notify_url'] = $this->notify_url;
        $param['auth_code'] = $auth_code;
        $param['nonce_str'] = $this->getNonceStr();//随机字符串
        $param['sign'] = $this->getSignVeryfy($param, $this->key);//签名
        $this->writeLog('micro.log', ':参数', $param);
        $res = $this->httpRequst($this->url, $param);
        $res_arr = $this->xmlToArray($res);
        $this->writeLog('micro.log', ':返回', $res_arr);

        return $res_arr;
    }

    private function add_db($id, $price, $checker_id, $jmt_remark)
    {
        $data = array(
            'merchant_id' => $id,
            'order_id' => $this->order_id,
            'customer_id' => $this->customer_id,
            'buyers_account' => '',
            'phone_info' => '',
            'wx_remark' => '',
            'wz_remark' => '',
            'new_order_sn' => '',
            'no_number' => '',
            'transId' => '',
            'la_ka_la' => 0,
            'add_time' => time(),
//            'paytime' => time(),
            'bill_date' => date('Ymd'),
            'checker_id' => $checker_id,
            'paystyle_id' => $this->paystyle_id,
            'price' => $price,
            'remark' => $this->remark,
            'status' => 0,
            'cate_id' => $this->cate_id,
            'mode' => $this->mode,
            'bank' => $this->bank,
            'cost_rate' => $this->rate,
            'subject' => $this->subject,
            'remark_mer' => '',
        );
        $data['jmt_remark'] = $jmt_remark ?: '';

        return $this->pay_model->add($data);
    }

    private function password($remark)
    {
        $queryTimes = 6;
        while ($queryTimes > 0) {
            $queryTimes--;
            $query_res = $this->query($remark);
            if ($query_res['resp_code'] == 0 && $query_res['result_code'] == 0) {
                if ($query_res['status'] == 0) {
                    sleep(5);
                    $this->writeLog('query.log', ':继续查询', $query_res);
                    continue;
                } else if ($query_res['status'] == 2) {
                    $this->writeLog('query.log', ':支付成功', $query_res);
                    $this->pay_model->where(array("remark" => $remark))->save(array("status" => "1", "paytime" => time(), 'transId' => $query_res['leshua_order_id']));
                    return array("code" => "success", "msg" => "支付成功", "data" => '支付成功');
                } else {
                    $this->writeLog('query.log', ':支付失败', $query_res);
                    return array("code" => "error", "msg" => "失败", "data" => '请重试');
                }
            } else {
                return array("code" => "error", "msg" => "失败", "data" => '请重试');
            }
        }

        $cancel_res = $this->cancel($remark);
        if ($cancel_res['resp_code'] == 0 && $cancel_res['result_code'] == 0) {
            if ($cancel_res['status'] == 6) {
                sleep(1);
                $this->writeLog('cancel.log', ':撤销成功', $cancel_res);
                return array("code" => "error", "msg" => "失败", "data" => '交易已取消');
            } else {
                $this->writeLog('cancel.log', ':撤销失败', $cancel_res);
                return array("code" => "error", "msg" => "失败", "data" => '请重试');
            }
        } else {
            $this->writeLog('cancel.log', ':撤销失败2', $cancel_res);
            return array("code" => "error", "msg" => "失败", "data" => '请重试');
        }
    }

    public function cancel($remark)
    {
        $data['merchant_id'] = $this->mch_id;//商户号
        $data['service'] = 'close_order';
        $data['third_order_id'] = $remark;//商户系统内部的订单号
        $data['nonce_str'] = $this->getNonceStr();//UCHANG订单号，优先使用
        $data['sign'] = $this->getSignVeryfy($data, $this->key);
        $this->writeLog('cancel.log', ':参数', $data);
        $res = $this->httpRequst($this->url, $data);
        $res_arr = $this->xmlToArray($res);

        return $res_arr;
    }

    //xml转数组
    private function xmlToArray($xml)
    {
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $values;
    }

    //支付接口 curl
    private function httpRequst($url, $post_data)
    {
        $headers = array("Accept-Charset: utf-8");
        //初始化
        $curl = curl_init();
        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //设置post方式提交
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        //设置post数据
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
        //执行命令
        $data = curl_exec($curl);
        curl_close($curl);
        return $data;
        //显示获得的数据
    }

    //支付接口统一签名
    private function getSignVeryfy($para_temp, $key)
    {
        //除去待签名参数数组中的空值和签名参数
        $para_filter = $this->paraFilter($para_temp);

        //对待签名参数数组排序
        $para_sort = $this->argSort($para_filter);

        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $this->createLinkstring($para_sort);
        //拼接apikey
        $prestr = $prestr . "&key=" . $key;
        //MD5 转大写
        $prestr = strtoupper(md5($prestr));
        return $prestr;
    }

    //除去空字符串
    private function paraFilter($para)
    {
        $para_filter = array();
        while (list ($key, $val) = each($para)) {
            if ($key == "sign" || $key == "sign_type" || $val === "") continue;
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
     * @return bool|string 拼接完成以后的字符串
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

    /**
     * 获取随机字符串
     * @return string
     */
    private function getNonceStr()
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < 32; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }

        return strtoupper($str);
    }

    private function writeLog($file_name, $title, $param, $json = true)
    {
        $path = $this->get_date_dir();
        if ($json) {
            $param = json_encode($param);
        }
        file_put_contents($path . $file_name, date("Y-m-d H:i:s") . $title . ':' . $param . PHP_EOL . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    private function get_date_dir($path = '/data/log/leShua/')
    {
        $Y = $_SERVER['DOCUMENT_ROOT'] . $path . date("Y-m");
        $d = $Y . '/' . date('d');
        if (!file_exists($Y)) mkdir($Y, 0777, true);
        if (!file_exists($d)) mkdir($d, 0777);

        return $d . '/';
    }

    private function writeLogA($file_name, $title, $param, $json = true)
    {
        $path = $this->get_date_dirA();
        if ($json) {
            $param = json_encode($param);
        }
        file_put_contents($path . $file_name, date("Y-m-d H:i:s") . $title . ':' . $param . PHP_EOL . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    private function get_date_dirA($path = '/data/log/leShua/')
    {
        $Y = $_SERVER['DOCUMENT_ROOT'] . $path . date("Y-m");
        if (!file_exists($Y)) mkdir($Y, 0777, true);

        return $Y . '/';
    }

    # 会员卡充值储值回调
    public function cz_notify()
    {
        $data = file_get_contents('php://input');
        $result_arr = $this->xmlToArray($data);
        if ($result_arr['error_code'] == '0' && $result_arr['status'] == '2') {
            $order_sn = $result_arr['third_order_id'];
            $transId = $result_arr['leshua_order_id'];
            $orderData = M("user_recharge")->where(array('order_sn' => $order_sn, 'status' => 0))->find();
            if ($orderData) {
                if ($orderData['status'] == 0) {
                    if (bccomp($orderData['price'] * 100, $result_arr['amount'], 3) === 0) {
                        $this->writeLog('cz_notify.log', ':收到数据', $result_arr);
                        $this->parse_cz($orderData, $result_arr['amount'], $transId);
                    } else {
                        $this->writeLog('cz_notify.log', ':金额不等', $result_arr);
                    }
                } else if ($orderData['status'] == 1) {
                    $this->writeLog('cz_notify.log', ':二次通知', $result_arr);
                    exit("000000");
                } else {
                    $this->writeLog('cz_notify.log', ':订单状态异常', $result_arr);
                    echo "error";
                }
            } else {
                $this->writeLog('cz_notify.log', ':订单不存在', $result_arr);
                $this->writeLog('cz_notify.log', ':SQl', M()->_sql(), 0);
                exit("000000");
            }
        } else {
            $this->writeLog('cz_notify.log', ':支付失败', $data);
            echo "error";
        }
    }

    #处理储值订单
    private function parse_cz($order, $real_price, $transId)
    {
        if ($order) {
            $token = get_weixin_token();
            M('user_recharge')->where(array('id' => $order['id']))->save(array('status' => 1, 'update_time' => time(), 'real_price' => $real_price / 100, 'transId' => $transId));
            $screen_memcard_use = M('screen_memcard_use')->where(array('id' => $order['memcard_id']))->find();
            $screen_memcard = M('screen_memcard')->where(array('id' => $screen_memcard_use['memcard_id']))->find();
            //$screen_cardset = M('screen_cardset')->where(array('c_id' => $screen_memcard_use['memcard_id']))->find();
            //判断是否有充值赠送积分
            if ($screen_memcard['recharge_send_integral'] == 1) {
                //赠送积分比例
                //$order['price'] && $integral = (int)($order['price'] / $screen_memcard['expense']) * $screen_memcard['expense_credits'];
                $integral = (int)($order['price'] / $screen_memcard['recharge']) * $screen_memcard['recharge_send'];
                if ($integral > 0) {
                    if ($integral > $screen_memcard['recharge_send_max']) $integral = $screen_memcard['recharge_send_max'];
                    $save['card_amount'] = $screen_memcard_use['card_amount'] + $integral;//会员卡总积分
                    $save['card_balance'] = $screen_memcard_use['card_balance'] + $integral;//会员卡剩余积分
                    //记录推送信息
                    $ts['add_bonus'] = $integral;
                    $ts['code'] = $screen_memcard_use['card_code'];
                    $ts['card_id'] = $screen_memcard_use['card_id'];
                    $ts['record_bonus'] = urlencode('充值送积分');

                    $msg = request_post('https://api.weixin.qq.com/card/membercard/updateuser?access_token=' . $token, urldecode(json_encode($ts)));
                    $json_msg = $msg;
                    $msg = json_decode($msg, true);
                    if ($msg['errcode'] == 0) {
                        $ts_status = 1;
                    } else {
                        $ts_status = 0;
                    }
                    //记录日志
                    M('screen_memcard_log')->add(array('add_time' => time(), 'update_time' => time(), 'value' => $integral, 'balance' => $save['card_balance'], 'ts' => json_encode($ts), 'msg' => $json_msg, 'ts_status' => $ts_status, 'order_sn' => $order['order_sn'], 'code' => $screen_memcard_use['card_code'], 'record_bonus' => '充值送积分'));
                    $ts = array();
                }
            }

            $save['yue'] = $screen_memcard_use['yue'] + $order['total_price'];

            //开始更新余额
            M('screen_memcard_use')->where(array('id' => $order['memcard_id']))->save($save);
            //开始记录余额日志
            $yue['add_time'] = time();
            $yue['value'] = $order['total_price'];
            $yue['remark'] = '充值' . $order['total_price'];
            $yue['uid'] = $order['uid'];
            $yue['yue'] = $screen_memcard_use['yue'] + $order['total_price'];
            $ts['custom_field_value1'] = urldecode((string)($screen_memcard_use['yue'] + $order['total_price']));
            $ts['code'] = $screen_memcard_use['card_code'];
            $ts['card_id'] = $screen_memcard_use['card_id'];

            $ts = json_encode($ts);
            $yue['ts'] = $ts;
            //开始推送
            $msg = request_post('https://api.weixin.qq.com/card/membercard/updateuser?access_token=' . $token, urldecode($ts));
            $yue['ts_msg'] = $msg;
            $msg = json_decode($msg);
            if ($msg->errcode == 0) {
                $yue['ts_status'] = 1;
            }
            M('user_yue_log')->add($yue);
            $order_sn = $order['order_sn'];
            //记录流水
            if (!$this->pay_model->where(array('remark' => $order_sn, 'mode' => 12))->find()) {
                $pay['merchant_id'] = $order['mid'];
                $pay['customer_id'] = $order['uid'];
                $pay['paystyle_id'] = $order['paystyle_id'];
                $pay['order_id'] = $order['id'];
                $pay['mode'] = 12;
                $pay['price'] = $real_price / 100;
                $pay['remark'] = $order_sn;
                $pay['add_time'] = $order['add_time'];
                $pay['paytime'] = time();
                $pay['bill_date'] = date('Ymd');
                $pay['new_order_sn'] = $order_sn;
                $pay['transId'] = $transId;
                $pay['cate_id'] = $order['cate_id'];
                $pay['status'] = 1;
                $pay['bank'] = 11;
                $pay['cost_rate'] = M('merchants_xdl')->where(array('m_id' => $order['mid']))->getField('wx_rate');
                $this->pay_model->add($pay);
            }
            echo '000000';
        }
    }


    public function get_card_recharge_url($order, $cate_info)
    {
        $this->notify_url = "https://sy.youngport.com.cn/pay/leshuabank/cz_notify";
        $this->remark = $order['order_sn'];
        $price = $order['price'];
        $this->getIntoInfo($cate_info['merchant_id'], 2);
        $res_arr = $this->ali_pay($price);
        // 判断返回结果
        if ($this->check_sign($res_arr)) {
            if ($res_arr['resp_code'] == 0 && $res_arr['result_code'] == 0) {
                $return['code'] = '0000';
                $return['data'] = $res_arr['td_code'];
            } else {
                $return['code'] = '0001';
                $return['msg'] = $res_arr['error_msg'];
            }
        } else {
            $return['code'] = '0002';
            $return['msg'] = '签名错误';
        }

        return $return;
    }
}