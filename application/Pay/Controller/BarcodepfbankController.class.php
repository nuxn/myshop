<?php

namespace Pay\Controller;

use Common\Controller\HomebaseController;
use Common\Lib\Subtable;


//          ┗━┻━┛   ┗━┻━┛

/**支付
 * Class BarcodeController
 * @package Pay\Controller
 */
class BarcodepfbankController extends HomebaseController
{
    private $apikey;
    private $order_id = 0;
    private $pay_model;

    function _initialize()
    {
        parent::_initialize();
        $this->notifyUrl = "https://sy.youngport.com.cn/notify/pfbank.php";
        $this->version = '2.0';
        $this->path = $_SERVER['DOCUMENT_ROOT'] . '/data/log/pfbank/';
//        $this->apikey = 'fe0e779dd2222420f1713b9248b7f415';
        $this->pay_model = M(Subtable::getSubTableName('pay'));
    }

    public function test()
    {

        file_put_contents('./data/log/pfbank/' . date("Y_m") . '_wx_micro.log', date("Y-m-d H:i:s") . '支付数据:test' . PHP_EOL, FILE_APPEND | LOCK_EX);
//        $this->apikey = 'fe0e779dd2222420f1713b9248b7f415';
//        $auth_code = I('code');
//        $bank['mch_id'] = '101590129081';
//        $bank['out_trade_no'] = '2017081645542915961242';
//        $bank['body'] = '测试支付0.1';
//        $bank['total_fee'] = 1;
//        $bank['mch_create_ip'] = $_SERVER["REMOTE_ADDR"];
//        $bank['auth_code'] =$auth_code;
//        $res = $this->micropay($bank);
//        $res = $this->xmlToArray($res);
//        var_dump($res);
    }

    public function qr_weixipay()
    {
        //这里直接获得openid;
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            $id = I("id");
            $merchant = M("merchants_cate")->where("id=$id")->find();
            $code = M('merchants_pfpay')->where(array('merchant_id' => $merchant['merchant_id']))->find();
            $openid = $this->_get_openid($code['channel']);
            $this->getOffer($merchant, $openid);
            $checker_id = I("checker_id");
            $this->assign("checker_id", $checker_id);
            $this->assign('openid', $openid);
            $this->assign("merchant", $merchant);
            $this->assign('seller_id', I('id'));
            $this->display();
        }
    }

    /**
     * 微信支付
     *
     */
    public function wz_pay()
    {
        header("Content-type:text/html;charset=utf-8");
//        先获取openid防止 回调
        if (I("seller_id") == "") {
            $id = I("id");
            $res = M('merchants_cate')->where("id=$id")->find();
            $code = M('merchants_pfpay')->where(array('merchant_id' => $res['merchant_id']))->find();
            $sub_openid = $this->_get_openid($code['channel']);
            $price = I("price");
            $data['mode'] = I('mode', 1);
            $data['checker_id'] = I("checker_id");
        }
        if (I('seller_id') !== "") {
            $sub_openid = I('openid');
            $id = I('seller_id');
            $res = M('merchants_cate')->where("id=$id")->find();
            $price = I('price');
            $data['checker_id'] = $res['checker_id'];
            $data['mode'] = I('mode', 0);
        }
        $data['bank'] = 10;
        if (I("checker_id")) {
            $data['checker_id'] = I("checker_id");
        } //app上的台签带上收银员的信息
        if (I("jmt_remark")) {
            $data['jmt_remark'] = I("jmt_remark");
        } else {
            $data['jmt_remark'] = I('memo', '');
        }
        $data['bill_date'] = date("Ymd", time());
        $payModel = $this->pay_model;
        $remark = I('order_sn', date('YmdHis') . rand(100000, 999999));
        //            插入数据库的数据
        $data['merchant_id'] = (int)$res['merchant_id'];
        //$data['customer_id'] = $sub_openid;
        $data['customer_id'] = D("Api/ScreenMem")->add_member("$sub_openid", $res['merchant_id']);
        $data['paystyle_id'] = 1;
        $data['cost_rate'] = $this->cost_rate_1($res['wx_mchid'], 1);
        $data['price'] = $price;
        $data['remark'] = $remark;
        $data['status'] = 0;
        $data['cate_id'] = $res['id'];
        $data['phone_info'] = $_SERVER['HTTP_USER_AGENT'];
        $data['paytime'] = time();
        $good_name = "向" . $res['jianchen'] . "支付" . $price . "元";
        $data['subject'] = $good_name;
        $remark_exists = $payModel->where(array('remark' => $remark))->find();
        if (!$remark_exists) {
            $payModel->add($data);
        }
        $config = C('WEIXINPAY_CONFIG');
        $bank['mch_id'] = $res['wx_mchid'];
        $this->apikey = $res['wx_key'];
        $bank['out_trade_no'] = $remark;
        $bank['body'] = $good_name;
        $bank['sub_openid'] = $sub_openid;
        $code = M('merchants_pfpay')->where(array('merchant_id' => $res['merchant_id']))->find();
        if ($code['channel'] == 1) {
            $bank['sub_appid'] = 'wx8b17740e4ea78bf5';
        } else {
            $bank['sub_appid'] = $config['APPID'];
        }

        $bank['is_raw'] = "1";
        $bank['total_fee'] = $price * 100;
        $bank['mch_create_ip'] = $_SERVER["REMOTE_ADDR"];
        file_put_contents('./data/log/pfbank/' . date('Y_m') . '_weixin_pay.log', date("Y-m-d H:i:s") . 'params：' . $remark . json_encode($bank) . PHP_EOL, FILE_APPEND | LOCK_EX);

        $result = $this->weixin_jsapi($bank);
        //xml 转数据
        $res_arr = $this->xmlToArray($result);

        $sign = $res_arr['sign'];
        $status = $res_arr['status'];
        $result_code = $res_arr['result_code'];
        unset($res_arr['sign']);
        $resign = $this->getSignVeryfy($res_arr);

        if ($sign == $resign) {
            if ((int)$status == 0 && (int)$result_code == 0) {
                file_put_contents('./data/log/pfbank/' . date('Y_m') . '_weixin_pay.log', date("Y-m-d H:i:s") . 'js支付调起：' . $remark . json_encode($res_arr) . PHP_EOL, FILE_APPEND | LOCK_EX);
                $body = $res_arr['pay_info'];
                $this->assign('body', $body);
                $this->assign('price', $price);
                $this->assign('openid', $sub_openid);
                $this->assign('remark', $remark);
                $this->assign('mid', $data['merchant_id']);
                $this->display("wz_pay");
            } else {
                file_put_contents('./data/log/pfbank/' . date('Y_m') . '_weixin_pay.log', date("Y-m-d H:i:s") . 'js支付err:' . $res_arr['err_msg'] . $res_arr['message'] . json_encode($res_arr) . PHP_EOL, FILE_APPEND | LOCK_EX);
                $this->assign('err_msg', "网络异常，请稍后再试");
                $this->display("error");
            }
        } else {
            file_put_contents('./data/log/pfbank/' . date('Y_m') . '_weixin_pay.log', date("Y-m-d H:i:s") . 'js签名err,mch:' . $res['wx_mchid'] . '、key:' . $this->apikey . ',' . json_encode($res_arr) . $res_arr['message'] . PHP_EOL, FILE_APPEND | LOCK_EX);
            echo '<script type="text/javascript">alert("网络异常，请稍后再试!")</script>';
            if ($status == "410") {
                $this->assign('err_msg', "网络异常，请稍后再试");
                $this->display("error");
            }
        }
    }

    /**
     * 双屏扫码支付
     */
    public function two_wz_pay()
    {
        header("Content-type:text/html;charset=utf-8");
//        先获取openid防止 回调
        $order_id = I("order_id");
        $mode = I("mode", 3);
        $id = I("id");
        if ($order_id != "") {

            $order = M("order");
            $remark = $order->where("order_id='$order_id'")->getField("order_sn");
            $data['order_id'] = $order_id;
            $data['mode'] = $mode;
            $data['checker_id'] = I("checker_id");
            $order = $order->where("order_id='$order_id'")->find();
            $price = $order['order_amount'];
            $res = M('merchants_cate')->where("id=$id")->find();
            $code = M('merchants_pfpay')->where(array('merchant_id' => $res['merchant_id']))->find();
            $sub_openid = $this->_get_openid($code['channel']);
            $this->apikey = $res['wx_key'];
//            插入数据库的数据
            $data['merchant_id'] = (int)$res['merchant_id'];
            //$data['customer_id'] = $sub_openid;
            $data['customer_id'] = D("Api/ScreenMem")->add_member("$sub_openid", $res['merchant_id']);
            $data['paystyle_id'] = 1;
            $data['price'] = $price;
            $data['remark'] = $remark;
            $data['status'] = 0;
            $data['cate_id'] = 1;
            $data['bank'] = 10;
            if (I("jmt_remark")) { //金木堂定单号
                $data['jmt_remark'] = I("jmt_remark");
            } else {
                $data['jmt_remark'] = I('memo', '');
            }
            $wzcost_rate = $this->cost_rate_1($res['wx_mchid'], 1);
            if ($wzcost_rate) {
                $data['cost_rate'] = $wzcost_rate;
            };
            $data['paytime'] = time();
            $data['bill_date'] = date("Ymd", time());
            $order_sn = $remark . rand(1000, 9999);
            $data['new_order_sn'] = $order_sn;
            $good_name = "向" . $res['jianchen'] . "支付" . $price . "元";
            $data['subject'] = $good_name;
            //预防pay表订单重复
            $remark_exists = $this->pay_model->where(array('remark' => $remark))->find();
            if (!$remark_exists) {
                $this->pay_model->add($data);
            }
            //由于回调地址的原因，将id存入session中

//       支付订单提交的数据交互
            $mchid = $res['wx_mchid'];
        }
        //使用统一支付接口()
        $config = C('WEIXINPAY_CONFIG');
        //拼接微信jsapi数据

        $bank['mch_id'] = $res['wx_mchid'];
        $bank['out_trade_no'] = $remark;
        $bank['body'] = $good_name;
        $bank['sub_openid'] = $sub_openid;
        $code = M('merchants_pfpay')->where(array('merchant_id' => $res['merchant_id']))->find();
        if ($code['channel'] == 1) {
            $bank['sub_appid'] = 'wx8b17740e4ea78bf5';
        } else {
            $bank['sub_appid'] = $config['APPID'];
        }
        $bank['is_raw'] = "1";
        $bank['total_fee'] = $price * 100;
        $bank['mch_create_ip'] = $_SERVER["REMOTE_ADDR"];
        $res = $this->weixin_jsapi($bank);
        //xml 转数据
        $res = $this->xmlToArray($res);
        $sign = $res['sign'];
        $status = $res['status'];
        $result_code = $res['result_code'];
        unset($res['sign']);
        $resign = $this->getSignVeryfy($res);
        if ($sign == $resign) {
            if ((int)$status == 0 && (int)$result_code == 0) {
                file_put_contents('./data/log/pfbank/' . date("Y_m") . '_weixin_pay.log', date("Y-m-d H:i:s") . 'sp支付suc:' . $remark . json_encode($res) . PHP_EOL, FILE_APPEND | LOCK_EX);
                $body = $res['pay_info'];
                $this->assign('body', $body);
                $this->assign('price', $price);
                $this->assign('openid', $sub_openid);
                $this->assign('remark', $remark);
                $this->assign('mid', $data['merchant_id']);
                $this->display("wz_pay");
            } else {
                file_put_contents('./data/log/pfbank/' . date("Y_m") . '_weixin_pay.log', date("Y-m-d H:i:s") . 'sp支付err:' . $mchid . $res['err_msg'] . json_encode($res) . PHP_EOL, FILE_APPEND | LOCK_EX);
                $this->assign('err_msg', "网络异常，请稍后再试");
                $this->display("error");
            }
        } else {
            file_put_contents('./data/log/pfbank/' . date("Y_m") . '_weixin_pay.log', date("Y-m-d H:i:s") . 'sp签名err:' . $remark . json_encode($res) . PHP_EOL, FILE_APPEND | LOCK_EX);
            echo '<script type="text/javascript">alert("签名失败!")</script>';
        }
    }

    // 支付宝双拼
    public function screen_wz_alipay()
    {
        $seller_id = I('seller_id');//二维码对应的id
        $order_id = I('order_id');
        $checker_id = I('checker_id', 0, 'intval');
        $mode = I('mode', 1);
        if (!$seller_id) exit('seller_id不能为空!');
        if (!$order_id) exit('订单号不能为空!');

        $res = M('merchants_cate')->where(array("id" => $seller_id))->find();
        $this->apikey = $res['alipay_public_key'];
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
                "subject" => $pay_info['subject'] ? $pay_info['subject'] : "向" . $res['jianchen'] . "支付" . $order_info['order_amount'] . "元",
                "checker_id" => $checker_id,
            );
            $this->pay_model->where(array("remark" => $order_info['order_sn']))->save(array("paystyle_id" => 2));
        } else {
            $wzcost_rate = $this->cost_rate_1($res['wx_mchid'], 2);
            $data = array(
                "merchant_id" => $res['merchant_id'],
                "price" => $order_info['order_amount'] ? $order_info['order_amount'] : '0.01',
                "subject" => "向" . $res['jianchen'] . "支付" . $order_info['order_amount'] . "元",
                "mode" => $mode,//双屏扫码
                "paystyle_id" => "2",//支付宝
                "order_id" => $order_id,//订单编号
                "remark" => $order_info['order_sn'],//订单号唯一
                "status" => "0",//未付款
                "paytime" => time(),
                "add_time" => time(),
                "cate_id" => $res['id'],
                "checker_id" => $checker_id,
                "bank" => 10,
                "cost_rate" => $wzcost_rate ? $wzcost_rate : '',
                "jmt_remark" => I('jmt_remark') ? I('jmt_remark') : '',
            );
            $this->pay_model->add($data);
        }

        $data['remark'] = rand(1000, 9999) . $data['remark'];
        $this->pay_model->where(array("remark" => $order_info['order_sn']))->save(array("new_order_sn" => $data['remark']));
        //构造要请求的参数数组,无需改动
        $bank['mch_id'] = $res['alipay_partner'];
        $bank['body'] = $data['subject'];
        $bank['out_trade_no'] = $order_info['order_sn'];
        $bank['total_fee'] = $order_info['order_amount'] * 100;
        $bank['mch_create_ip'] = $_SERVER["REMOTE_ADDR"];
        $result = $this->alipay_native($bank);
        $res_arr = $this->xmlToArray($result);
        $sign = $res_arr['sign'];
        $status = $res_arr['status'];
        $result_code = $res_arr['result_code'];
        unset($res_arr['sign']);
        $resign = $this->getSignVeryfy($res_arr);
        if ($sign == $resign) {
            if ((int)$status == 0 && (int)$result_code == 0) {
                file_put_contents('./data/log/pfbank/' . date("Y_m") . '_ali_pay.log', date("Y-m-d H:i:s") . 'sp支付suc:' . json_encode($res_arr) . PHP_EOL, FILE_APPEND | LOCK_EX);
                $url = $res_arr['code_url'];
                header("Location: $url");
            } else {
                file_put_contents('./data/log/pfbank/' . date("Y_m") . '_ali_pay.log', date("Y-m-d H:i:s") . 'sp支付err:' . $res_arr['err_msg'] . json_encode($res_arr) . PHP_EOL, FILE_APPEND | LOCK_EX);
                $this->assign('err_msg', "网络异常，请稍后再试");
                $this->display("error");
            }
        } else {
            file_put_contents('./data/log/pfbank/' . date("Y_m") . '_ali_pay.log', date("Y-m-d H:i:s") . 'sp签名err,key:' . $this->apikey . ",new>>{$resign},old>>{$sign}" . PHP_EOL, FILE_APPEND | LOCK_EX);
            echo '<script type="text/javascript">alert("签名失败!")</script>';
        }
    }

    //支付宝手机扫码支付
    public function qr_to_alipay()
    {
        $seller_id = I('seller_id');//二维码对应的id
        $checker_id = I('checker_id', 0, 'intval');
        if (!$seller_id) exit('seller_id不能为空!');
        $type = I("type");

        $res = M('merchants_cate')->where('id=' . $seller_id)->find();
        $this->apikey = $res['alipay_public_key'];
        if (!$res) exit('二维码信息不存在!');
        $res['checker_id'] = $checker_id ? $checker_id : intval($res['checker_id']);
        $price = I('price');
        $res['price'] = $price ? $price : '0.01';
        if ($type || $type == '0') $res['mode'] = '1';
        else $res['mode'] = '0';
        I("jmt_remark") ? $res['jmt_remark'] = I("jmt_remark") : $res['jmt_remark'] = "";
        $res['order_sn'] = I('order_sn');
        $this->_wz_alipay($res);


    }

    private function _wz_alipay($res)
    {
        $payModel = $this->pay_model;
        $where = array(
            "merchant_id" => $res['merchant_id'],
            "paystyle_id" => "2",
            "price" => $res['price'],
            "status" => "0",
            "mode" => $res['mode'],
            "cate_id" => $res['id'],
        );
        $where['subject'] = "向" . $res['jianchen'] . "支付" . $res['price'] . "元";
        //金木堂订单号
        if ($res['jmt_remark']) {
            $where['jmt_remark'] = $res['jmt_remark'];
        } else {
            $where['jmt_remark'] = I('memo', '');
        }
        $remark = $res['order_sn'] ? $res['order_sn'] : date('YmdHis') . rand(100000, 999999);
        $where['remark'] = $remark;
        $where['paytime'] = time();
        $where['checker_id'] = $res['checker_id'];
        $where['bank'] = 10;
        $where['phone_info'] = $_SERVER['HTTP_USER_AGENT'];
        $wzcost_rate = $this->cost_rate_1($res['alipay_partner'], 2);
        if ($wzcost_rate) $where['cost_rate'] = $wzcost_rate;
        $remark_exists = $payModel->where(array('remark' => $remark))->find();
        if (!$remark_exists) {
            $payModel->add($where);
        }
        //构造要请求的参数数组，无需改动

        $bank['mch_id'] = $res['alipay_partner'];
        $bank['body'] = $where['subject'];
        $bank['out_trade_no'] = $remark;
        $bank['total_fee'] = $res['price'] * 100;
        $bank['mch_create_ip'] = $_SERVER["REMOTE_ADDR"];
        $result = $this->alipay_native($bank);
        $res_arr = $this->xmlToArray($result);
        $sign = $res_arr['sign'];
        $status = $res_arr['status'];
        $result_code = $res_arr['result_code'];
        unset($res_arr['sign']);
        $resign = $this->getSignVeryfy($res_arr);
        if ($sign == $resign) {
            if ((int)$status == 0 && (int)$result_code == 0) {
                file_put_contents('./data/log/pfbank/' . date("Y_m") . '_ali_pay.log', date("Y-m-d H:i:s") . 'tq支付suc:' . json_encode($res_arr) . PHP_EOL, FILE_APPEND | LOCK_EX);
                $url = $res_arr['code_url'];
                header("Location: $url");
            } else {
                file_put_contents('./data/log/pfbank/' . date("Y_m") . '_ali_pay.log', date("Y-m-d H:i:s") . 'tq支付err:' . $res_arr['err_msg'] . "商户ID:" . $res['merchant_id'] . PHP_EOL, FILE_APPEND | LOCK_EX);
                $this->assign('err_msg', '"网络异常，请稍后再试"');
                $this->display("error");
            }
        } else {
            file_put_contents('./data/log/pfbank/' . date("Y_m") . '_ali_pay.log', date("Y-m-d H:i:s") . 'tq签名err,key:' . $this->apikey . ",<{$sign}>>{$resign}>>{$result}" . PHP_EOL, FILE_APPEND | LOCK_EX);
            echo '<script type="text/javascript">alert("签名失败!")</script>';
        }
    }

    //支付宝支付界面跳转
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

    public function wz_micropay($id, $price, $auth_code, $checker_id, $jmt_remark, $order_sn, $mode)
    {
        $this->customer_id = '';
        $this->order_id = 0;
        $remark = $order_sn ? $order_sn : date('YmdHis') . rand(100000, 999999);

//        if ($jmt_remark == 'ypttest') {
//            $presult = D('Pay/Pay')->card_off($auth_code, $id, $price, $remark, $checker_id, $jmt_remark);
//            if ($presult) {
//                if ($presult['status'] === 1) {
//                    A('Pay/Barcode')->cardOff($presult['order_id']);
//                    return array('code' => 'success');
//                } else {
//                    $this->order_id = $presult['order_id'];
//                    $this->customer_id = $presult['customer_id'];
//                    $price = $presult['price'];
//                }
//            }
//        }
        header('Content-Type:application/json; charset=utf-8');
        if (!$auth_code) {
            return array("code" => "error", "msg" => "失败", "data" => "支付失败");
        }
        if (!$id) {
            return array("code" => "error", "msg" => "失败", "data" => "支付失败");
        }
        $res = M('merchants_cate')->where("merchant_id=$id")->find();
        $this->apikey = $res['wx_key'];
        if ((int)$res['merchant_id'] == 0) {
            return array("code" => "error", "msg" => "失败", "data" => "还未申请支付业务");
        }
//            插入数据库的数据
        $data['merchant_id'] = (int)$res['merchant_id'];
        $data['checker_id'] = $checker_id;
        $data['paystyle_id'] = 1;
        $data['order_id'] = $this->order_id;
        $data['customer_id'] = $this->customer_id;
        $data['price'] = $price;
        $data['remark'] = $remark;
        $data['status'] = 0;
        $data['cate_id'] = 1;
        $data['mode'] = $mode ? $mode : 2;
        $data['paytime'] = time();
        $data['bank'] = 10;
        $data['cost_rate'] = $this->cost_rate_1($res['wx_mchid'], 1);
        $merchant_code = $res["wx_mchid"];
        $product = "向" . $res['jianchen'] . "支付" . $price . "元";
        if ($jmt_remark) $data['jmt_remark'] = $jmt_remark;
        $data['subject'] = $product;
        file_put_contents('./data/log/pfbank/' . date("Y_m") . '_wx_micro.log', date("Y-m-d H:i:s") . '支付数据:' . $remark . json_encode($data) . PHP_EOL, FILE_APPEND | LOCK_EX);
        $this->pay_model->add($data);
        $bank['mch_id'] = $merchant_code;
        $bank['out_trade_no'] = $remark;
        $bank['body'] = $product;
        $bank['total_fee'] = $price * 100;
        $bank['mch_create_ip'] = $_SERVER["REMOTE_ADDR"];
        $bank['auth_code'] = $auth_code;
        $result = $this->micropay($bank);
        get_date_dir($this->path, 'wx_micropay', '刷卡支付', json_encode($bank));
        $res_arr = $this->xmlToArray($result);
        get_date_dir($this->path, 'wx_micropay', '刷卡支付返回', json_encode($res_arr));
        if ($res_arr['status'] == 0 && $res_arr['result_code'] == 0) {
            if (isset($res_arr['need_query']) && $res_arr['need_query'] == 'Y') {
                file_put_contents('./data/log/pfbank/' . date("Y_m") . '_wx_micro.log', date("Y-m-d H:i:s") . '支付验密:' . $remark . json_encode($res_arr) . PHP_EOL, FILE_APPEND | LOCK_EX);
                $payData['mch_id'] = $bank['mch_id'];
                $payData['out_trade_no'] = $remark;
                $payData['merchant_id'] = $data['merchant_id'];
                return $this->pay_password($payData);
            } else if ($res_arr['pay_result'] == 0) {
                file_put_contents('./data/log/pfbank/' . date("Y_m") . '_wx_micro.log', date("Y-m-d H:i:s") . '支付成功:' . $remark . json_encode($res_arr) . PHP_EOL, FILE_APPEND | LOCK_EX);
                $customer_id = D("Api/ScreenMem")->add_member($res_arr['openid'], $data['merchant_id']);
                $this->pay_model->where(array("remark" => $remark))->save(array("status" => "1", "paytime" => time(), "customer_id" => $customer_id, 'transId' => $res_arr['transaction_id']));
                if ($mode != 17) {
                    A("App/PushMsg")->push_pay_message($remark);
                }
                if ($this->order_id != 0) {
                    A('Pay/Barcode')->cardOff($this->order_id);
                }
                return array("code" => "success", "msg" => "成功", "data" => '支付成功');
            } else {
                file_put_contents('./data/log/pfbank/' . date("Y_m") . '_wx_micro.log', date("Y-m-d H:i:s") . '支付失败1:' . $remark . json_encode($res_arr) . PHP_EOL, FILE_APPEND | LOCK_EX);
                return array("code" => "error", "msg" => "支付失败");
            }
        } else if (strval($res_arr['status']) === '0' && strval($res_arr['result_code']) !== '0') {
            file_put_contents('./data/log/pfbank/' . date("Y_m") . '_wx_micro.log', date("Y-m-d H:i:s") . '支付验密:' . $remark . json_encode($res_arr) . PHP_EOL, FILE_APPEND | LOCK_EX);
            $payData['mch_id'] = $bank['mch_id'];
            $payData['out_trade_no'] = $remark;
            $payData['merchant_id'] = $data['merchant_id'];
            return $this->pay_password($payData);
        } else {
            file_put_contents('./data/log/pfbank/' . date("Y_m") . '_wx_micro.log', date("Y-m-d H:i:s") . '支付失败2:' . $remark . json_encode($res_arr) . PHP_EOL, FILE_APPEND | LOCK_EX);
            return array("code" => "error", "msg" => "支付失败");
        }
    }

    public function pos_wz_micropay($id, $price, $auth_code, $checker_id, $jmt_remark, $order_sn)
    {
        header('Content-Type:application/json; charset=utf-8');
        if (!$auth_code) {
            $this->error('参数错误!');
        }
        if (!$id) {
            return array("code" => "error", "msg" => "失败", "data" => "支付失败");
        }
        $res = M('merchants_cate')->where("merchant_id=$id")->find();
        $this->apikey = $res['wx_key'];
        if ((int)$res['merchant_id'] == 0) {
            return array("code" => "error", "msg" => "失败", "data" => "还未申请支付业务");
        }
        $remark = $order_sn ? $order_sn : date('YmdHis') . rand(100000, 999999);
//            插入数据库的数据
        $data['merchant_id'] = (int)$res['merchant_id'];
        $data['checker_id'] = $checker_id;
        $data['paystyle_id'] = 1;
        $data['price'] = $price;
        $data['remark'] = $remark;
        $data['status'] = 0;
        $data['cate_id'] = $res['id'];
        $data['mode'] = 5;
        $data['paytime'] = time();
        $data['bank'] = 10;
        $data['cost_rate'] = $this->cost_rate_1($res['wx_mchid'], 1);
        $merchant_code = $res["wx_mchid"];
        $product = "向" . $res['jianchen'] . "支付" . $price . "元";
        if ($jmt_remark) $data['jmt_remark'] = $jmt_remark;
        $data['subject'] = $product;
        file_put_contents('./data/log/pfbank/' . date("Y_m") . '_wx_micro.log', date("Y-m-d H:i:s") . '支付数据:' . $remark . json_encode($data) . PHP_EOL, FILE_APPEND | LOCK_EX);
        $this->pay_model->add($data);
        $bank['mch_id'] = $merchant_code;
        $bank['out_trade_no'] = $remark;
        $bank['body'] = $product;
        $bank['total_fee'] = $price * 100;
        $bank['mch_create_ip'] = $_SERVER["REMOTE_ADDR"];
        $bank['auth_code'] = $auth_code;
        $result = $this->micropay($bank);
        $res_arr = $this->xmlToArray($result);
        if ($res_arr['status'] == 0 && $res_arr['result_code'] == 0) {
            if (isset($res_arr['need_query']) && $res_arr['need_query'] == 'Y') {
                file_put_contents('./data/log/pfbank/' . date("Y_m") . '_wx_micro.log', date("Y-m-d H:i:s") . '支付验密:' . $remark . json_encode($res_arr) . PHP_EOL, FILE_APPEND | LOCK_EX);
                $payData['mch_id'] = $bank['mch_id'];
                $payData['out_trade_no'] = $remark;
                $payData['merchant_id'] = $data['merchant_id'];
                return $this->pay_password($payData);
            } else if ($res_arr['pay_result'] == 0) {
                file_put_contents('./data/log/pfbank/' . date("Y_m") . '_wx_micro.log', date("Y-m-d H:i:s") . '支付成功:' . $remark . json_encode($res_arr) . PHP_EOL, FILE_APPEND | LOCK_EX);
                $customer_id = D("Api/ScreenMem")->add_member($res_arr['openid'], $data['merchant_id']);
                $this->pay_model->where(array("remark" => $remark))->save(array("status" => "1", "paytime" => time(), "customer_id" => $customer_id, 'transId' => $res_arr['transaction_id']));
                A("App/PushMsg")->push_pay_message($remark);
                return array("code" => "success", "msg" => "成功", "data" => '支付成功');
            } else {
                file_put_contents('./data/log/pfbank/' . date("Y_m") . '_wx_micro.log', date("Y-m-d H:i:s") . '支付失败:' . $remark . json_encode($res_arr) . PHP_EOL, FILE_APPEND | LOCK_EX);
                return array("code" => "error", "msg" => "失败", "data" => $res_arr['err_msg']);
            }
        } else if (strval($res_arr['status']) === '0' && strval($res_arr['result_code']) !== '0') {
            file_put_contents('./data/log/pfbank/' . date("Y_m") . '_wx_micro.log', date("Y-m-d H:i:s") . '支付验密:' . $remark . json_encode($res_arr) . PHP_EOL, FILE_APPEND | LOCK_EX);
            $payData['mch_id'] = $bank['mch_id'];
            $payData['out_trade_no'] = $remark;
            $payData['merchant_id'] = $data['merchant_id'];
            return $this->pay_password($payData);
        } else {
            file_put_contents('./data/log/pfbank/' . date("Y_m") . '_wx_micro.log', date("Y-m-d H:i:s") . '支付失败:' . $remark . json_encode($res_arr) . PHP_EOL, FILE_APPEND | LOCK_EX);
            return array("code" => "error", "msg" => "失败", "data" => $res_arr['errorMsg']);
        }
    }

    //支付查询接口
    private function trade_query($data)
    {
        //接口类型
        $param['service'] = 'unified.trade.query';
        //版本号
        $param['version'] = $this->version;
        //商户号
        $param['mch_id'] = $data['mch_id'];
        //商户订单号
        $param['out_trade_no'] = $data['out_trade_no'];
        //平台订单号
//        $param['transaction_id'] = $data['transaction_id'];
        //随机字符串
        $param['nonce_str'] = $this->getNonceStr();
        //签名
        $param['sign'] = $this->getSignVeryfy($param);
        //转换成xml格式post提交数据
        $xmlData = $this->arrayToXml($param);
        $url = "https://pay.swiftpass.cn/pay/gateway";
        return $this->httpRequst($url, $xmlData);
    }

    //支付撤销接口
    private function micropay_reverse($data)
    {
        //接口类型
        $param['service'] = 'unified.micropay.reverse';
        //版本号
        $param['version'] = $this->version;
        //商户号
        $param['mch_id'] = $data['mch_id'];
        //商户订单号
        $param['out_trade_no'] = $data['out_trade_no'];
        //随机字符串
        $param['nonce_str'] = $this->getNonceStr();
        //签名
        $param['sign'] = $this->getSignVeryfy($param);
        //转换成xml格式post提交数据
        $xmlData = $this->arrayToXml($param);
        $url = "https://pay.swiftpass.cn/pay/gateway";
        return $this->httpRequst($url, $xmlData);
    }

    public function tequery()
    {
        $this->apikey = I('key');
        $bank['out_trade_no'] = I('no');
        $bank['mch_id'] = I('mchid');
        $res = $this->trade_query($bank);
        $ressult = $this->xmlToArray($res);
        echo json_encode($ressult);
    }

    private function pay_password($payData)
    {
        $queryTimes = 6;
        while ($queryTimes >= 0) {
            $bank['out_trade_no'] = $payData['out_trade_no'];
//            $bank['transaction_id'] = $payData['transaction_id'];
            $bank['mch_id'] = $payData['mch_id'];
            $res = $this->trade_query($bank);
            $res = $this->xmlToArray($res);
            file_put_contents('./data/log/pfbank/query/query.log', date("Y-m-d H:i:s") . '支付查询:' . $bank['out_trade_no'] . json_encode($res) . PHP_EOL, FILE_APPEND | LOCK_EX);
            if ($res['status'] == 0 && $res['result_code'] == 0) {
                //如果需要等待5s后继续
                $succResult = $res['trade_state'];
                //支付成功
                if ($succResult == 'SUCCESS') {
                    $customer_id = D("Api/ScreenMem")->add_member($res['openid'], $payData['merchant_id']);
                    $brr = array("status" => "1", "paytime" => time(), "customer_id" => $customer_id, 'transId' => $res['transaction_id']);
                    file_put_contents('./data/log/pfbank/query/query.log', date("Y-m-d H:i:s") . '支付成功:' . $bank['out_trade_no'] . json_encode($res) . PHP_EOL, FILE_APPEND | LOCK_EX);
                    $this->pay_model->where(array("remark" => $bank['out_trade_no']))->save($brr);
                    if ($this->order_id != 0) {
                        A('Pay/Barcode')->cardOff($this->order_id);
                    }
                    A("App/PushMsg")->push_pay_message($res['out_trade_no']);
                    return array("code" => "success", "msg" => "成功", "data" => '支付成功');
                    //转入退款
                } else if ($succResult == 'REFUND') {
                    //未支付
                } else if ($succResult == 'NOTPAY') {
                    $this->pay_model->where(array("remark" => $res['out_trade_no']))->save(array("status" => "-2"));
                    return array("code" => "error", "msg" => "失败", "data" => '支付失败');
                    //已关闭
                } else if ($succResult == 'CLOSED') {
                    $this->pay_model->where(array("remark" => $res['out_trade_no']))->save(array("status" => "-2"));
                    return array("code" => "error", "msg" => "失败", "data" => '支付失败');
                    //已冲正
                } else if ($succResult == 'REVERSE') {
                    $this->pay_model->where(array("remark" => $res['out_trade_no']))->save(array("status" => "-2"));
                    return array("code" => "error", "msg" => "失败", "data" => '支付失败');
                    //已撤销
                } else if ($succResult == 'REVOKED') {
                    $this->pay_model->where(array("remark" => $res['out_trade_no']))->save(array("status" => "-2"));
                    return array("code" => "error", "msg" => "失败", "data" => '支付失败');
                    //用户支付中
                } else if ($succResult == 'USERPAYING' || $succResult == '10003') {
                    if ($queryTimes == 0) {
                        break;
                    } else {
                        sleep(5);
                        $queryTimes--;
                        continue;
                    }
                    //支付失败
                } else if ($succResult == 'PAYERROR') {
                    $this->pay_model->where(array("remark" => $res['out_trade_no']))->save(array("status" => "-2"));
                    return array("code" => "error", "msg" => "失败", "data" => '支付失败');
                }
            } else {
                $this->pay_model->where(array("remark" => $payData['out_trade_no']))->save(array("status" => "-2"));
                return array("code" => "error", "msg" => "失败", "data" => '支付失败');
            }
        }
        $bank['out_trade_no'] = $payData['out_trade_no'];
        $bank['mch_id'] = $payData['mch_id'];
        $res = $this->micropay_reverse($bank);
        $res = $this->xmlToArray($res);
        file_put_contents('./data/log/pfbank/query/query.log', date("Y-m-d H:i:s") . '--支付撤销--' . $bank['out_trade_no'] . json_encode($res) . PHP_EOL, FILE_APPEND | LOCK_EX);
        if ($res['status'] == '0' && $res['result_code'] == '0') {
            $this->pay_model->where(array("remark" => $res['out_trade_no']))->save(array("status" => "-2"));
            return array("code" => "error", "msg" => "失败", "data" => '交易时间过长,支付失败');
        }
    }

    /**
     * 支付宝条码支付
     */
    public function ali_barcode_pay($id, $price, $auth_code, $checker_id, $jmt_remark = "", $mode = 2)
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
        $this->apikey = $res['alipay_public_key'];
        if (!$res['alipay_partner']) return array("flag" => false, "msg" => "未开通或未绑定支付宝支付");

        $data = $payModel->where("customer_id=$auth_code")->find();
        if (!$data) {
            $remark = date('YmdHis') . rand(100000, 999999);//订单号
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
            $data['subject'] = "向" . $res['jianchen'] . "支付" . $price . "元";
            $data['bank'] = 10;
            if ($jmt_remark) { //金木堂定单号
                $data['jmt_remark'] = $jmt_remark;
            }
            $wzcost_rate = $this->cost_rate_1($res['wx_mchid'], 2);
            if ($wzcost_rate) $data['cost_rate'] = $wzcost_rate;
            $payModel->add($data);
        } else
            $remark = $data['remark'];
        //拼接支付宝数据
        $bank['mch_id'] = $res['alipay_partner'];
        $bank['out_trade_no'] = $remark;
        $bank['body'] = $data['subject'];
        $bank['total_fee'] = $price * 100;
        $bank['mch_create_ip'] = $_SERVER["REMOTE_ADDR"];
        $bank['auth_code'] = $auth_code;
        $result = $this->micropay($bank);
        $res_arr = $this->xmlToArray($result);
        $sign = $res_arr['sign'];
        unset($res_arr['sign']);
        $resign = $this->getSignVeryfy($res_arr);
        if ($res_arr['status'] == 0 && $res_arr['result_code '] == 0) {
            if (isset($res_arr['need_query']) && $res_arr['need_query'] == 'Y') {
                file_put_contents('./data/log/pfbank/' . date("Y_m") . '_ali_micro.log', date("Y-m-d H:i:s") . '支付验密:' . $remark . json_encode($res_arr) . PHP_EOL, FILE_APPEND | LOCK_EX);
                $payData['mch_id'] = $bank['mch_id'];
                $payData['out_trade_no'] = $remark;
                $payData['merchant_id'] = $data['merchant_id'];
                return $this->pay_password($payData);
            } else if ($res_arr['pay_result'] == 0) {
                file_put_contents('./data/log/pfbank/' . date("Y_m") . '_ali_micro.log', date("Y-m-d H:i:s") . '支付成功:' . $remark . json_encode($res_arr) . PHP_EOL, FILE_APPEND | LOCK_EX);
                $customer_id = D("Api/ScreenMem")->add_member($res_arr['openid'], $data['merchant_id']);
                $this->pay_model->where(array("remark" => $remark))->save(array("status" => "1", "paytime" => time(), "customer_id" => $customer_id, 'transId' => $res_arr['transaction_id']));
                A("App/PushMsg")->push_pay_message($remark);
                return array("code" => "success", "msg" => "成功", "data" => '支付成功');
            } else {
                file_put_contents('./data/log/pfbank/' . date("Y_m") . '_ali_micro.log', date("Y-m-d H:i:s") . '支付失败:' . $remark . json_encode($res_arr) . PHP_EOL, FILE_APPEND | LOCK_EX);
                return array("code" => "error", "msg" => "失败", "data" => $res_arr['errorMsg']);
            }
        } else if ($res_arr['status'] == 0 && $res_arr['result_code'] != 0) {
            file_put_contents('./data/log/pfbank/' . date("Y_m") . '_ali_micro.log', date("Y-m-d H:i:s") . '支付验密:' . $remark . json_encode($res_arr) . PHP_EOL, FILE_APPEND | LOCK_EX);
            $payData['mch_id'] = $bank['mch_id'];
            $payData['out_trade_no'] = $remark;
            $payData['merchant_id'] = $data['merchant_id'];
            $this->pay_password($payData);
        } else {
            file_put_contents('./data/log/pfbank/' . date("Y_m") . '_ali_micro.log', date("Y-m-d H:i:s") . '支付失败:' . $remark . json_encode($res_arr) . PHP_EOL, FILE_APPEND | LOCK_EX);
            return array("code" => "error", "msg" => "失败", "data" => $res_arr['errorMsg']);
        }
    }

    /**
     * 支付宝条码支付
     */
    public function pos_ali_barcode_pay($id, $price, $auth_code, $checker_id, $jmt_remark = "", $order_sn)
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
        $this->apikey = $res['alipay_public_key'];
        if (!$res['alipay_partner']) return array("flag" => false, "msg" => "未开通或未绑定支付宝支付");

        $data = $payModel->where("customer_id=$auth_code")->find();
        if (!$data) {
            $remark = $order_sn ? $order_sn : date('YmdHis') . rand(100000, 999999);//订单号
            //插入数据库的数据
            $data['merchant_id'] = (int)$res['merchant_id'];//商户ID
            $data['customer_id'] = $auth_code;//买方账号ID
            $data['checker_id'] = $checker_id;//收银员的ID
            $data['paystyle_id'] = 2;//支付方式 1是微信 2是支付宝
            $data['price'] = $price;
            $data['remark'] = $remark;//订单号
            $data['status'] = 0;//待付款
            $data['cate_id'] = $res['id'];//支付样式,台签类别
            $data['mode'] = 5;//0 为台签支付 1为扫码支付  2刷卡支付
            $data['add_time'] = time();//下单时间
            $data['subject'] = "向" . $res['jianchen'] . "支付" . $price . "元";
            $data['bank'] = 10;
            if ($jmt_remark) { //金木堂定单号
                $data['jmt_remark'] = $jmt_remark;
            }
            $wzcost_rate = $this->cost_rate_1($res['wx_mchid'], 2);
            if ($wzcost_rate) $data['cost_rate'] = $wzcost_rate;
            $payModel->add($data);
        } else
            $remark = $data['remark'];
        //拼接支付宝数据
        $bank['mch_id'] = $res['alipay_partner'];
        $bank['out_trade_no'] = $remark;
        $bank['body'] = $data['subject'];
        $bank['total_fee'] = $price * 100;
        $bank['mch_create_ip'] = $_SERVER["REMOTE_ADDR"];
        $bank['auth_code'] = $auth_code;
        $result = $this->micropay($bank);
        $res_arr = $this->xmlToArray($result);
        $sign = $res_arr['sign'];
        unset($res_arr['sign']);
        $resign = $this->getSignVeryfy($res_arr);
        if ($res_arr['status'] == 0 && $res_arr['result_code '] == 0) {
            if (isset($res_arr['need_query']) && $res_arr['need_query'] == 'Y') {
                file_put_contents('./data/log/pfbank/' . date("Y_m") . '_ali_micro.log', date("Y-m-d H:i:s") . '支付验密:' . $remark . json_encode($res_arr) . PHP_EOL, FILE_APPEND | LOCK_EX);
                $payData['mch_id'] = $bank['mch_id'];
                $payData['out_trade_no'] = $remark;
                $payData['merchant_id'] = $data['merchant_id'];
                return $this->pay_password($payData);
            } else if ($res_arr['pay_result'] == 0) {
                file_put_contents('./data/log/pfbank/' . date("Y_m") . '_ali_micro.log', date("Y-m-d H:i:s") . '支付成功:' . $remark . json_encode($res_arr) . PHP_EOL, FILE_APPEND | LOCK_EX);
                $customer_id = D("Api/ScreenMem")->add_member($res_arr['openid'], $data['merchant_id']);
                $this->pay_model->where(array("remark" => $remark))->save(array("status" => "1", "paytime" => time(), "customer_id" => $customer_id, 'transId' => $res_arr['transaction_id']));
                A("App/PushMsg")->push_pay_message($remark);
                return array("code" => "success", "msg" => "成功", "data" => '支付成功');
            } else {
                file_put_contents('./data/log/pfbank/' . date("Y_m") . '_ali_micro.log', date("Y-m-d H:i:s") . '支付失败:' . $remark . json_encode($res_arr) . PHP_EOL, FILE_APPEND | LOCK_EX);
                return array("code" => "error", "msg" => "失败", "data" => $res_arr['errorMsg']);
            }
        } else if ($res_arr['status'] == 0 && $res_arr['result_code'] != 0) {
            file_put_contents('./data/log/pfbank/' . date("Y_m") . '_ali_micro.log', date("Y-m-d H:i:s") . '支付验密:' . $remark . json_encode($res_arr) . PHP_EOL, FILE_APPEND | LOCK_EX);
            $payData['mch_id'] = $bank['mch_id'];
            $payData['out_trade_no'] = $remark;
            $payData['merchant_id'] = $data['merchant_id'];
            $this->pay_password($payData);
        } else {
            file_put_contents('./data/log/pfbank/' . date("Y_m") . '_ali_micro.log', date("Y-m-d H:i:s") . '支付失败:' . $remark . '---' . $res['id'] . '---' . json_encode($res_arr) . PHP_EOL, FILE_APPEND | LOCK_EX);
            return array("code" => "error", "msg" => "失败", "data" => $res_arr['errorMsg']);
        }
    }

    private function trade_refund($data)
    {
        //接口类型
        $param['service'] = 'unified.trade.refund';
        //版本号
        $param['version'] = $this->version;
        //商户号
        $param['mch_id'] = $data['mch_id'];
        //商户订单号
        $param['out_trade_no'] = $data['out_trade_no'];
        //平台订单号
        $param['transaction_id'] = $data['transaction_id'];
        //退款订单号
        $param['out_refund_no'] = $data['out_refund_no'];
        //总金额
        $param['total_fee'] = $data['total_fee'];
        //退款金额
        $param['refund_fee'] = $data['refund_fee'];
        //操作员id
        $param['op_user_id'] = $data['op_user_id'];
        //随机字符串
        $param['nonce_str'] = $this->getNonceStr();
        //签名
        $param['sign'] = $this->getSignVeryfy($param);
        //转换成xml格式post提交数据
        $xmlData = $this->arrayToXml($param);
        $url = "https://pay.swiftpass.cn/pay/gateway";
        return $this->httpRequst($url, $xmlData);
    }

    //微信退借
    public function wx_pay_back($remark, $price_back)
    {
        header("Content-type:text/html;charset=utf-8");
        vendor('Wzpay.Wzcommon');
        vendor("WzPay.pub.config.php");
        $wzPay = new \Wzcommon();
        $pay = $this->pay_model->where("remark='$remark' And status = 1")->find();

        if (!$pay) return array("code" => "error", "msg" => "失败", "data" => "未找到订单");
        $merchant_id = $pay['merchant_id'];
        //$list = M("merchants_cate")->where("merchant_id=$merchant_id")->find();
        $list = M("merchants_pfpay")->where("merchant_id=$merchant_id")->find();

        //$param['mch_id'] = $list['wx_mchid'];
        $param['mch_id'] = $list['mch_id'];
        //$this->apikey = $list['wx_key'];
        $this->apikey = $list['mch_key'];
        $param['out_trade_no'] = $remark;
        $param['transaction_id'] = $pay['transId'];
        $param['out_refund_no'] = date("YmdHis") . rand(10000, 99999);
        $param['total_fee'] = $pay['price'] * 100;
        //$param['refund_fee'] = $pay['price'] * 100;
        $param['refund_fee'] = $price_back * 100;
        //$param['op_user_id'] = $list['wx_mchid'];
        $param['op_user_id'] = $list['mch_id'];
        file_put_contents('./data/log/pfbank/' . date("Y_m") . '_pay_back.log', date("Y-m-d H:i:s") . '-param-' . json_encode($param) . PHP_EOL, FILE_APPEND | LOCK_EX);
        $result = $this->trade_refund($param);
        $res_arr = $this->xmlToArray($result);
        file_put_contents('./data/log/pfbank/' . date("Y_m") . '_pay_back.log', date("Y-m-d H:i:s") . '-返回结果-' . json_encode($res_arr) . PHP_EOL, FILE_APPEND | LOCK_EX);
        if ($res_arr['result_code'] == '0' && $res_arr['status'] == '0') {
            $this->pay_back_suc($remark, $price_back);
            file_put_contents('./data/log/pfbank/' . date("Y_m") . '_pay_back.log', date("Y-m-d H:i:s") . "微信退款成功：订单号：$remark" . PHP_EOL, FILE_APPEND | LOCK_EX);
            return array("code" => "success", "msg" => "成功", "data" => "退款成功");
        } else {

            /*if ($this->pay_model->where("remark='$remark'")->find()) {
                $this->pay_model->where("remark='$remark'")->save(array("status" => 3, "back_status" => 0));
            }*/
            file_put_contents('./data/log/pfbank/' . date("Y_m") . '_pay_back.log', date("Y-m-d H:i:s") . "微信退款失败:" . json_encode($res_arr) . PHP_EOL, FILE_APPEND | LOCK_EX);
            return array("code" => "error", "msg" => "失败", "data" => "退款失败");
        }
    }

    //处理退款订单状态
    private function pay_back_suc($remark, $refund_amount)
    {
        if ($this->pay_model->where("remark='$remark'")->find()) {
            $this->pay_model->where("remark='$remark'")->save(array("status" => 2, "back_status" => 1, "price_back" => $refund_amount));
        }
    }

    //支付宝退款
    public function ali_pay_back($remark, $price_back)
    {
        header("Content-type:text/html;charset=utf-8");
        if (!$remark) return array("flag" => false, "msg" => "订单号不存在");
        $pay = $this->pay_model->where(array("remark" => $remark))->find();
        if (!$pay) return array("flag" => false, "msg" => "该订单不存在");
        if ($pay['status'] == "2") return array("flag" => false, "msg" => "不能重复退款");
        $merchant_id = $pay['merchant_id'];
//        $res = M("merchants_cate")->where("merchant_id=$merchant_id")->find();
        $list = M("merchants_pfpay")->where("merchant_id=$merchant_id")->find();
        if (!$list) return array("flag" => false, "msg" => "商户不存在");
        $this->apikey = $list['mch_key'];
        $param['mch_id'] = $list['mch_id'];
        $param['out_trade_no'] = $remark;
        $param['transaction_id'] = $pay['transId'];
        $param['out_refund_no'] = date("YmdHis") . rand(10000, 99999);
        $param['total_fee'] = $pay['price'] * 100;
        //$param['refund_fee'] = $pay['price'] * 100;
        $param['refund_fee'] = $price_back * 100;
        $param['op_user_id'] = $list['mch_id'];
        $result = $this->trade_refund($param);
        $res_arr = $this->xmlToArray($result);
        $sign = $res_arr['sign'];
        unset($res_arr['sign']);
        $resign = $this->getSignVeryfy($res_arr);
        if ($sign == $resign) {
            if ($res_arr['result_code'] == '0' && $res_arr['status'] == '0') {
                $this->pay_back_suc($remark, $price_back);
                file_put_contents('./data/log/pfbank/' . date("Y_m") . '_pay_back.log', date("Y-m-d H:i:s") . "阿里退款成功:{$result}" . PHP_EOL, FILE_APPEND | LOCK_EX);
                return array("code" => "success", "msg" => "成功", "data" => "退款成功");
            } else {
                /*if ($this->pay_model->where("remark='$remark'")->find()) {
                    $this->pay_model->where("remark='$remark'")->save(array("status" => 3, "back_status" => 0));
                }*/
                file_put_contents('./data/log/pfbank/' . date("Y_m") . '_pay_back.log', date("Y-m-d H:i:s") . "阿里退款失败:" . json_encode($res_arr) . PHP_EOL, FILE_APPEND | LOCK_EX);
                return array("code" => "error", "msg" => "成功", "data" => "退款失败");
            }
        } else {

            /*if ($this->pay_model->where("remark='$remark'")->find()) {
                $this->pay_model->where("remark='$remark'")->save(array("status" => 3, "back_status" => 0));
            }*/
            file_put_contents('./data/log/pfbank/' . date("Y_m") . '_pay_back.log', date("Y-m-d H:i:s") . "阿里签名错误,key:" . $this->apikey . ',' . json_encode($res_arr) . PHP_EOL, FILE_APPEND | LOCK_EX);
            return array("code" => "error", "msg" => "成功", "data" => "退款失败");
        }
    }

    public function notify()
    {
        $json_str = file_get_contents('php://input', 'r');
        $res = $this->xmlToArray($json_str);
        $sign = $res['sign'];
        $mch_id = $res['mch_id'];
        $order_sn = $res['out_trade_no'];
        $data = M('merchants_pfpay')->where("mch_id=$mch_id")->find();
        $this->apikey = $data['mch_key'];
        unset($res['sign']);
        $resign = $this->getSignVeryfy($res);
        if ($sign == $resign) {
            if ($res['result_code'] == '0' && $res['status'] == '0') {
                $transId = $res['transaction_id'];
                $openid = $res['openid'];
                $orderData = $this->pay_model->where(array('remark' => $order_sn))->find();
                if ($orderData['status'] == 0) {
                    $this->pay_model->where(array('remark'=>$order_sn))->save(array('status'=>1,'transId'=>$transId));
                    file_put_contents('../data/log/pfbank/' . date('Y_m_') . '_notify.log', date("Y-m-d H:i:s") . '支付成功1:' . json_encode($res) . PHP_EOL, FILE_APPEND | LOCK_EX);
                    echo 'success';
                    if ($orderData['mode'] == '0' && $orderData['order_id'] != 0) {
                        A('Barcode')->cardOff($orderData['order_id']);
                    }
                    A("App/PushMsg")->push_pay_message($order_sn);
                    file_put_contents('../data/log/pfbank/' . date('Y_m_') . '_notify.log', date("Y-m-d H:i:s") . '支付成功3:' . json_encode($res) . PHP_EOL, FILE_APPEND | LOCK_EX);
                } else if ($orderData['status'] == 1) {
                    echo "success";
                    exit;
                } else {
                    file_put_contents('../data/log/pfbank/' . date('Y_m_') . '_notify.log', date("Y-m-d H:i:s") . '订单过期:' . $order_sn . PHP_EOL, FILE_APPEND | LOCK_EX);
                    echo "failrue";
                }
            } else {
                file_put_contents('../data/log/pfbank/' . date('Y_m_') . '_notify.log', date("Y-m-d H:i:s") . '支付失败:' . json_encode($res) . PHP_EOL, FILE_APPEND | LOCK_EX);
                echo "failrue";
            }
        } else {
            file_put_contents('../data/log/pfbank/' . date('Y_m_') . '_notify.log', date("Y-m-d H:i:s") . '签名错误,key:' . $this->apikey . ",生成签名>>{$resign},.原有签名>>{$sign}" . PHP_EOL, FILE_APPEND | LOCK_EX);
            echo "failrue";
        }
    }

    //微信H5
    private function weixin_jsapi($data)
    {
        //接口类型
        $param['service'] = 'pay.weixin.jspay';
        //字符集
        $param['charset'] = 'UTF-8';
        //版本号
//        $param['version'] = (string)$this->version;
        //商户号
        $param['mch_id'] = $data['mch_id'];
        //商户订单号
        $param['out_trade_no'] = (string)$data['out_trade_no'];
        //商品描述
        $param['body'] = $data['body'];
        //用户openid
        $param['sub_openid'] = $data['sub_openid'];
        //公众账号或小程序ID
        $param['sub_appid'] = $data['sub_appid'];
        //是否原生态
        $param['is_raw'] = "1";
        //金额
        $param['total_fee'] = $data['total_fee'];
        //ip
        $param['mch_create_ip'] = $data['mch_create_ip'];
        //回调地址
        $param['notify_url'] = $this->notifyUrl;
        //订单生成时间
//        $param['time_start'] = (string)date("YmdHis");
        //订单超时时间
//        $param['time_expire'] = (string)date("YmdHis", time() + 60);
        //随机字符串
        $param['nonce_str'] = $this->getNonceStr();
        //签名
        $param['sign'] = $this->getSignVeryfy($param);
        $this->writlog("two_wx_pay.log", '参数：' . json_encode($param));
        //转换成xml格式post提交数据
        $xmlData = $this->arrayToXml($param);
        $url = "https://pay.swiftpass.cn/pay/gateway";
        return $this->httpRequst($url, $xmlData);
    }

    //刷卡支付
    private function micropay($data)
    {
        //接口类型
        $param['service'] = 'unified.trade.micropay';
        //版本号
        $param['version'] = $this->version;
        //商户号
        $param['mch_id'] = $data['mch_id'];
        //商户订单号
        $param['out_trade_no'] = $data['out_trade_no'];
        //商品描述
        $param['body'] = $data['body'];
        //金额
        $param['total_fee'] = $data['total_fee'];
        //ip
        $param['mch_create_ip'] = $data['mch_create_ip'];
        //授权码
        $param['auth_code'] = $data['auth_code'];
        //订单生成时间
        $param['time_start'] = date("YmdHis");
        //订单超时时间
        $param['time_expire'] = date("YmdHis", time() + 60);
        //随机字符串
        $param['nonce_str'] = $this->getNonceStr();
        //签名
        $param['sign'] = $this->getSignVeryfy($param);
        //转换成xml格式post提交数据
        $xmlData = $this->arrayToXml($param);
        $url = "https://pay.swiftpass.cn/pay/gateway";
        return $this->httpRequst($url, $xmlData);
    }

    //微信扫码支付
    private function weixin_native($data)
    {
        //接口类型
        $param['service'] = 'pay.weixin.native';
        //版本号
        $param['version'] = $this->version;
        //商户号
        $param['mch_id'] = $data['mch_id'];
        //商户订单号
        $param['out_trade_no'] = $data['out_trade_no'];
        //商品描述
        $param['body'] = $data['body'];
        //金额
        $param['total_fee'] = $data['total_fee'];
        //ip
        $param['mch_create_ip'] = $data['mch_create_ip'];
        //回调地址
        $param['notify_url'] = $this->notifyUrl;
        //订单生成时间
        $param['time_start'] = date("YmdHis");
        //订单超时时间
        $param['time_expire'] = date("YmdHis", time() + 60);
        //随机字符串
        $param['nonce_str'] = $this->getNonceStr();
        //签名
        $param['sign'] = $this->getSignVeryfy($param);
        //转换成xml格式post提交数据
        $xmlData = $this->arrayToXml($param);
        $url = "https://pay.swiftpass.cn/pay/gateway";
        return $this->httpRequst($url, $xmlData);
    }

    //支付宝扫码支付
    private function alipay_native($data)
    {
        //接口类型
        $param['service'] = 'pay.alipay.native';
        //版本号
        $param['version'] = $this->version;
        //商户号
        $param['mch_id'] = $data['mch_id'];
        //商户订单号
        $param['out_trade_no'] = $data['out_trade_no'];
        //商品描述
        $param['body'] = $data['body'];
        //金额
        $param['total_fee'] = $data['total_fee'];
        //ip
        $param['mch_create_ip'] = $data['mch_create_ip'];
        //回调地址
        $param['notify_url'] = $this->notifyUrl;
        //订单生成时间
        $param['time_start'] = date("YmdHis");
        //订单超时时间
        $param['time_expire'] = date("YmdHis", time() + 60);
        //随机字符串
        $param['nonce_str'] = $this->getNonceStr();
        //签名
        $param['sign'] = $this->getSignVeryfy($param);
        $this->writlog("two_wx_pay.log", '参数：' . json_encode($param));
        //转换成xml格式post提交数据
        $xmlData = $this->arrayToXml($param);
        $url = "https://pay.swiftpass.cn/pay/gateway";
        return $this->httpRequst($url, $xmlData);
    }

    //支付宝js_api
    private function alipay_jspay($data)
    {
        //接口类型
        $param['service'] = 'pay.alipay.jspay';
        //版本号
        $param['version'] = $this->version;
        //商户号
        $param['mch_id'] = $data['mch_id'];
        //商户订单号
        $param['out_trade_no'] = $data['out_trade_no'];
        //商品描述
        $param['body'] = $data['body'];
        //金额
        $param['total_fee'] = $data['total_fee'];
        //ip
        $param['mch_create_ip'] = $data['mch_create_ip'];
        //回调地址
        $param['notify_url'] = $this->notifyUrl;
        //订单生成时间
        $param['time_start'] = date("YmdHis");
        //订单超时时间
        $param['time_expire'] = date("YmdHis", time() + 60);
        //随机字符串
        $param['nonce_str'] = $this->getNonceStr();
        //买家支付宝账号
        $param['buyer_logon_id'] = $data['buyer_logon_id'];
        //买家支付宝用户ID
        $param['buyer_id'] = $data['buyer_id'];
        //签名
        $param['sign'] = $this->getSignVeryfy($param);
        //转换成xml格式post提交数据
        $xmlData = $this->arrayToXml($param);
        $url = "https://pay.swiftpass.cn/pay/gateway";
        return $this->httpRequst($url, $xmlData);
    }

    private function arrayToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key => $val) {
            $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
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
    private function getSignVeryfy($para_temp)
    {
        //除去待签名参数数组中的空值和签名参数
        $para_filter = $this->paraFilter($para_temp);
        //对待签名参数数组排序
        $para_sort = $this->argSort($para_filter);

        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $this->createLinkstring($para_sort);
        //拼接apikey
        $prestr = $prestr . "&key=" . $this->apikey;
        //MD5 转大写
        $prestr = strtoupper(md5($prestr));
        return $prestr;
    }

    //除去空字符串
    private function paraFilter($para)
    {
        $para_filter = array();
        while (list ($key, $val) = each($para)) {
            if ($key == "sign" || $val === "") continue;
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
     * @param $para  需要拼接的数组
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

    //获取openid
    private function _get_openid($channel)
    {
        if ($channel == 0) {
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
        } else if ($channel == 1) {
            return $this->get_openid();
        }

    }

    private function get_openid()
    {
        header("content-type:text/html;charset=utf-8");
        // 获取配置项
        //洋仆淘
        //和众世纪
        $config['APPID'] = 'wx8b17740e4ea78bf5';
        $config['APPSECRET'] = 'bbd06a32bdefc1a00536760eddd1721d';
        $redirect_uri = 'https://' . $_SERVER['HTTP_HOST'] . $_SESSION['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $redirect_uri = urlencode($redirect_uri);
        $url = "http://m.hz41319.com/redirect/get-weixin-code.html?appid=" . $config['APPID'] . "&scope=snsapi_base&state=hello-world&redirect_uri=" . $redirect_uri;
        // 如果没有get参数没有code；则重定向去获取openid；
        if (!isset($_GET['code'])) {
            redirect($url);
        } else {
            //如果有code参数；则表示获取到openid

            $code = I('get.code');
            //组合获取prepay_id的url
            $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $config['APPID'] . '&secret=' . $config['APPSECRET'] . '&code=' . $code . '&grant_type=authorization_code';
            //curl获取prepay_id
            $result = $this->get_contents($url);
            $result = json_decode($result, true);
            return $result['openid'];

        }
    }

    private function get_contents($url)
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

    //获取支付费率
    private function cost_rate_1($mch_id, $pay_type)
    {
        $re = M('merchants_pfpay')->where(array('mch_id' => $mch_id))->find();
        switch ($pay_type) {
            case 1:
                return $re['wx_code'];
                break;
            case 2:
                return $re['ali_code'];
                break;
            default:
                break;
        }
    }

    /**
     * 获取随机字符串
     * @return string
     */
    public function getNonceStr()
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < 32; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }

        return strtoupper($str);
    }

    public function bill()
    {
//        $this->getbilld1();
        $this->apikey = "9bb58b2174a3cdf1cac1dd50a37f38e7";
        //接口类型
        $param['service'] = 'pay.bill.agent';
        //字符集
        $param['charset'] = 'UTF-8';
        //对账日期
        $date = I("date");
        if (empty($date)) {
            $param['bill_date'] = date("Ymd", strtotime("-1 day"));
        } else {
            $param['bill_date'] = $date;
        }
        //账单类型
        $param['bill_type'] = "ALL";
        //渠道号
        $param['mch_id'] = "101580129387";
        //随机字符串
        $param['nonce_str'] = $this->getNonceStr();
        //签名
        $param['sign'] = $this->getSignVeryfy($param);

        //转换成xml格式post提交数据
        $xmlData = $this->arrayToXml($param);
        $url = "https://download.swiftpass.cn/gateway";

        $bills = $this->httpRequst($url, $xmlData);
//        file_put_contents('../data/log/pfbank/'.date('Y_m_').'_bill.log', date("Y-m-d H:i:s") .':'.$param['bill_date'].':'.$bills . PHP_EOL, FILE_APPEND | LOCK_EX);
//        file_put_contents('./data/log/pfbank/'.date('Y_m_').'_bill.log', date("Y-m-d H:i:s") .':'.$param['bill_date'].':'.$bills . PHP_EOL, FILE_APPEND | LOCK_EX);
        if (strlen($bills) < 100) {
            file_put_contents('../data/log/pfbank/' . date('Y_m_') . '_bill.log', date("Y-m-d H:i:s") . ':' . $param['bill_date'] . $bills . PHP_EOL, FILE_APPEND | LOCK_EX);
            file_put_contents('./data/log/pfbank/' . date('Y_m_') . '_bill.log', date("Y-m-d H:i:s") . ':' . $param['bill_date'] . $bills . PHP_EOL, FILE_APPEND | LOCK_EX);
            sleep(15);
            while (1) {
                $bills = $this->httpRequst($url, $xmlData);
                if (strlen($bills) < 100) {
                    file_put_contents('../data/log/pfbank/' . date('Y_m_') . '_bill.log', date("Y-m-d H:i:s") . ':' . $param['bill_date'] . $bills . PHP_EOL, FILE_APPEND | LOCK_EX);
                    file_put_contents('./data/log/pfbank/' . date('Y_m_') . '_bill.log', date("Y-m-d H:i:s") . ':' . $param['bill_date'] . $bills . PHP_EOL, FILE_APPEND | LOCK_EX);
                    sleep(15);
                }
                if (strlen($bills) > 100) {
                    break;
                }
            }
        }

        $this->saveBill($bills, $param['bill_date'], "d1");
    }

    /**
     * 对账单入库
     * @param $bills
     * @param $date
     * @param $load
     */
    public function saveBill($bills, $date, $load)
    {
        $data = explode("\n", $bills);
        $count = count($data);
        $total_str = $data[$count - 1];
        unset($data[$count - 1]);
        unset($data[$count - 2]);
        unset($data[0]);
        $savedata = array();
        foreach ($data as $val) {
            $array = array();
            $lists = explode(',', $val);
            array_map(function ($v) use (&$array) {
                $array[] = strval(substr($v, 1));
            }, $lists);
            $savedata[] = array(
                'bill_date' => $array[0],
                'app_id' => $array[1],
                'mch_id' => $array[4],
                'pf_order_sn' => $array[6],
                'san_order_sn' => $array[7],
                'mch_order_sn' => $array[8],
                'open_id' => $array[9],
                'deal_type' => $array[10],
                'deal_status' => $array[11],
                'pay_bank' => $array[12],
                'currency_type' => $array[13],
                'deal_money' => $array[14],
                'discount' => $array[15],
                'payback_sn' => $array[16],
                'mch_payback_sn' => $array[17],
                'pay_back_money' => $array[18],
                'pay_back_discount' => $array[19],
                'pay_back_type' => $array[20],
                'pay_back_status' => $array[21],
                'goods_name' => $array[22],
                'poundage' => $array[24],
                'cost_cate' => $array[25],
                'bill_type' => $array[27],
                'mch_name' => $array[29],
                'sub_mch_id' => $array[31],
                'discount_money' => $array[32],
                'actual_money' => $array[33],
                'bill_time' => strtotime($array[0]),
            );
        }
        $total = array();
        $lists = explode(',', $total_str);
        array_map(function ($v) use (&$total) {
            $total[] = strval(substr($v, 1));
        }, $lists);
        $total = array(
            'bill_date' => $date,
            'total_deal' => $total[0],
            'total_money' => $total[1],
            'total_pay_back_money' => $total[2],
            'total_pay_back_discount' => $total[3],
            'total_poundage' => $total[4],
            'total_actual_money' => $total[5],
            'load_style' => "$load"
        );
        M('bill_pf_count')->add($total);
        M('bill_pf')->addAll($savedata);
    }

    public function getbilld1()
    {
        $this->apikey = "21c2db19b928bcc3fe1c443e68052895";
        //接口类型
        $param['service'] = 'pay.bill.agent';
        //字符集
        $param['charset'] = 'UTF-8';
        //对账日期
        $date = I("date");
        if (empty($date)) {
            $param['bill_date'] = date("Ymd", strtotime("-1 day"));
        } else {
            $param['bill_date'] = $date;
        }
        //账单类型
        $param['bill_type'] = "ALL";
        //渠道号
        $param['mch_id'] = "101550127924";
        //随机字符串
        $param['nonce_str'] = $this->getNonceStr();
        //签名
        $param['sign'] = $this->getSignVeryfy($param);

        //转换成xml格式post提交数据
        $xmlData = $this->arrayToXml($param);
        $url = "https://download.swiftpass.cn/gateway";

        $bills = $this->httpRequst($url, $xmlData);
        if (strlen($bills) < 10) {
            file_put_contents('../data/log/pfbank/' . date('Y_m_') . '_bill.log', date("Y-m-d H:i:s") . ':' . $param['bill_date'] . $bills . PHP_EOL, FILE_APPEND | LOCK_EX);
            sleep(15);
            while (1) {
                $bills = $this->httpRequst($url, $xmlData);
                if (strlen($bills) < 10) {
                    file_put_contents('../data/log/pfbank/' . date('Y_m_') . '_bill.log', date("Y-m-d H:i:s") . ':' . $param['bill_date'] . $bills . PHP_EOL, FILE_APPEND | LOCK_EX);
                    sleep(15);
                }
                if (strlen($bills) > 10) {
                    break;
                }
            }
        }

        $this->saveBill($bills, $param['bill_date'], "d1");
    }

    private function writlog($file_name, $data)
    {
        $path = $this->get_date_dir();
        file_put_contents($path . $file_name, date("H:i:s") . $data . PHP_EOL . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    private function get_date_dir($path = '/data/log/pfbank/')
    {
        $Y = $_SERVER['DOCUMENT_ROOT'] . $path . date("Y-m");
        $d = $Y . '/' . date("m-d");
        if (!file_exists($Y)) mkdir($Y, 0777, true);
        if (!file_exists($d)) mkdir($d, 0777);

        return $d . '/';
    }
}