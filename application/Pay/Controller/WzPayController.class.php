<?php

namespace Pay\Controller;

use Common\Controller\HomebaseController;
use Common\Lib\Subtable;


/**支付
 * Class BarcodeController
 * @package Pay\Controller
 */
class WzPayController extends HomebaseController
{

    private $pay_model;

    public function __construct()
    {
        parent::__construct();
        $this->pay_model = M(Subtable::getSubTableName('pay'));
    }
    /**
     * 支付成功展示页面
     */
    public function index()
    {
        $json_str = file_get_contents('php://input', 'r');
        // 转成php数组
        file_put_contents('./data/log/wz/weixin/' . date("Y_m_") . 'qingqiu.log', date("Y-m-d H:i:s") . '传递参数11' . $json_str . PHP_EOL, FILE_APPEND | LOCK_EX);

        $map = 1;
        $url = 'http://sy.youngport.com.cn/index.php?s=/Barcode/';
        $this->assign('map', $map);
        $this->assign('url', $url);
        $this->display();
    }

    /**
     * 扫码支付默认方法
     */
    public function qrcode()
    {
        header("content-type:text/html;charset=utf-8");
        $id = I('id');
        $price = I("price");
        $checker_id = I("checker_id") ? I("checker_id") : 0;
        $res = M('merchants_cate')->field('status,no_number,wx_bank,ali_bank')->where('id=' . $id)->find();
        $type = I("type");
        $order_id = I("order_id");
        $jmt_remark = I("jmt_remark");

        $http = 'https';
        // if ($_SERVER['HTTP_HOST'] != 'sy.youngport.com.cn') $http = 'http';
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {//跳转微信
//            微众支付跳转
            if ($res['wx_bank'] == "1") {
                if ((int)$res['status'] == 1) {
                    if ($order_id) $url = $http . "://" . $_SERVER['HTTP_HOST'] . "/index.php?g=Pay&m=Barcode&a=two_wz_pay&id=" . $id . "&price=" . $price . "&checker_id=" . $checker_id . "&order_id=" . $order_id."&jmt_remark=".$jmt_remark;//双屏端扫码支付收款
                    else if ($price) $url = $http . "://" . $_SERVER['HTTP_HOST'] . "/index.php?g=Pay&m=Barcode&a=wz_pay&id=" . $id . "&price=" . $price . "&checker_id=" . $checker_id."&jmt_remark=".$jmt_remark;//手机端扫码支付收款
                    else  $url = $http . "://" . $_SERVER['HTTP_HOST'] . "/index.php?g=Pay&m=Barcode&a=qr_weixipay&id=" . $id . "&checker_id=" . $checker_id;//台签收款
                    header("Location: $url");
                } else {
                    echo "<div style='margin: 10px auto;font-size: 30px;;width:80%';color:red>编号为：" . $res['no_number'] . " 的商家未上线 </div>";
                }
            }
//            民生支付跳转
            if ($res['wx_bank'] == "2") {
                if ((int)$res['status'] == 1) {
                    if ($order_id) $url = $http . "://" . $_SERVER['HTTP_HOST'] . "/index.php?g=Pay&m=Barcodembank&a=two_wz_pay&id=" . $id . "&price=" . $price . "&checker_id=" . $checker_id . "&order_id=" . $order_id;//双屏端扫码支付收款
                    else if ($price) $url = $http . "://" . $_SERVER['HTTP_HOST'] . "/index.php?g=Pay&m=Barcodembank&a=wz_pay&id=" . $id . "&price=" . $price . "&checker_id=" . $checker_id;//手机端扫码支付收款
                    else  $url = $http . "://" . $_SERVER['HTTP_HOST'] . "/index.php?g=Pay&m=Barcodembank&a=qr_weixipay&id=" . $id . "&checker_id=" . $checker_id;//台签收款

                    header("Location: $url");
                } else {
                    echo "<div style='margin: 10px auto;font-size: 30px;;width:80%';color:red>编号为：" . $res['no_number'] . " 的商家未上线 </div>";
                }

            }

//            微信支付跳转
            if ($res['wx_bank'] == "3") {
                if ((int)$res['status'] == 1) {
                    if ($order_id) $url = $http . "://" . $_SERVER['HTTP_HOST'] . "/index.php?g=Pay&m=Wxpay&a=two_wxpay&id=" . $id . "&price=" . $price . "&checker_id=" . $checker_id . "&order_id=" . $order_id;//双屏端扫码支付收款
                    else if ($price) $url = $http . "://" . $_SERVER['HTTP_HOST'] . "/index.php?g=Pay&m=Wxpay&a=wx_pay&id=" . $id . "&price=" . $price . "&checker_id=" . $checker_id;//手机端扫码支付收款
                    else  $url = $http . "://" . $_SERVER['HTTP_HOST'] . "/index.php?g=Pay&m=Wxpay&a=wxpay&id=" . $id . "&checker_id=" . $checker_id;//台签收款
                    header("Location: $url");
                } else {
                    echo "<div style='margin: 10px auto;font-size: 30px;;width:80%';color:red>编号为：" . $res['no_number'] . " 的商家未上线 </div>";
                }

            }


        } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient') !== false) {//跳转支付宝
//            微众支付跳转
            if ($res['ali_bank'] == "1") {
                if ((int)$res['status'] == 1) {
                    if ($order_id) $url = $http . "://" . $_SERVER['HTTP_HOST'] . "/index.php?g=Pay&m=Barcode&a=screen_wz_alipay&seller_id=" . $id . "&price=" . $price . "&checker_id=" . $checker_id . "&type=" . $type . "&order_id=" . $order_id;//双屏端扫码支付收款
                    else if ($price) $url = $http . "://" . $_SERVER['HTTP_HOST'] . "/index.php?g=Pay&m=Barcode&a=qr_to_alipay&seller_id=" . $id . "&price=" . $price . "&checker_id=" . $checker_id . "&type=" . $type;//手机端扫码支付收款
                    else $url = $http . "://" . $_SERVER['HTTP_HOST'] . "/index.php?g=Pay&m=Barcode&a=qr_alipay&id=" . $id . "&checker_id=" . $checker_id;//台签收款
                    header("Location: $url");
                } else {
                    echo "<div style='margin: 10px auto;font-size: 30px;width:80%';color:red>编号为：" . $res['no_number'] . " 的商家未上线 </div>";
                }
            }
            //            民生支付跳转
            if ($res['ali_bank'] == "2") {
                if ((int)$res['status'] == 1) {
                    if ($order_id) $url = $http . "://" . $_SERVER['HTTP_HOST'] . "/index.php?g=Pay&m=Barcodembank&a=screen_wz_alipay&seller_id=" . $id . "&price=" . $price . "&checker_id=" . $checker_id . "&type=" . $type . "&order_id=" . $order_id;//双屏端扫码支付收款
                    else if ($price) $url = $http . "://" . $_SERVER['HTTP_HOST'] . "/index.php?g=Pay&m=Barcodembank&a=qr_to_alipay&seller_id=" . $id . "&price=" . $price . "&checker_id=" . $checker_id . "&type=" . $type;//手机端扫码支付收款
                    else $url = $http . "://" . $_SERVER['HTTP_HOST'] . "/index.php?g=Pay&m=Barcodembank&a=qr_alipay&id=" . $id;//台签收款
                    header("Location: $url");
                } else {
                    echo "<div style='margin: 10px auto;font-size: 30px;width:80%';color:red>编号为：" . $res['no_number'] . " 的商家未上线 </div>";
                }
            }

        } else {//扫码失败
            echo "请用微信或者支付宝扫码~";
        }

    }


    //微信支付界面跳转
    public function qr_weixipay()
    {
//        这里直接获得openid;
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            $id = I("id");
            $checker_id = I("checker_id");
            $merchant = M("merchants_cate")->where("id=$id")->find();
            $mid=$merchant['merchant_id'];
            $openid = $this->_get_openid();
            $this->assign("checker_id", $checker_id);
            $this->assign('openid', $openid);
            $this->assign("merchant", $merchant);
            $this->assign('mid', $mid);
            $this->assign('seller_id', I('id'));
            $this->display();
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


    /**
     * 支付宝扫码支付，调起支付宝支付,生成订单
     */
    public function qr_to_alipay()
    {
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
        $wz = '1';
        if ($wz) {//是否走微众支付宝对接
            $this->_wz_alipay($res);
        } else {//单独调支付宝
            $this->_alipay($res);
        }

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
    public function ali_barcode_pay($id, $price, $auth_code, $checker_id)
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
            $data['cate_id'] = 1;//支付样式,台签类别
            $data['mode'] = 2;//0 为台签支付 1为扫码支付  2刷卡支付
            $data['add_time'] = time();//下单时间
            $data['subject'] = "向" . $res['jianchen'] . "支付" . $price . "元";
            $data['bank'] = 1;
            $wzcost_rate = M("merchants_upwz")->where("mid=" . $res['merchant_id'])->getField("aliCostRate");
            if ($wzcost_rate) $data['cost_rate'] = $wzcost_rate;
            $payModel->add($data);
        } else
            $remark = $data['remark'];

        $data['alipay_partner'] = $res['alipay_partner'];//支付宝商户号

        //调起条码支付
        $wz = '1';
        if ($wz) {//是否走微众支付宝对接
            $result = $this->_wz_ali_barcode_pay($data);
        } else {//单独调支付宝
            $result = $this->_ali_barcode_pay($data);
        }

        //支付结果处理
        if ($result['flag'] == false) {
            //$this->ajaxReturn(array("code" => "error", "msg" => "失败", "data" => $result['message']));
            return array("flag" => false, "msg" => "失败", "data" => $result['message']);
        } else {
            $payModel->where(array("remark" => $remark))->save(array("status" => "1", "paytime" => time(), "buyers_account" => $result['message']['buyerLogonId'], "wz_remark" => $result['message']['tradeNo']));
            //$this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $result['message']));
            $this->push_pay_message($remark);
            return array("flag" => true, "msg" => "成功", "data" => $result['message']);
        }

    }

    /**微众——支付宝——条码支付
     * @param $data
     * @return array
     */
    private function _wz_ali_barcode_pay($data)
    {
        header("Content-type:text/html;charset=utf-8");

        Vendor('QRcodeAlipay.Wz_pay');

        $wzPay = new \Wz_pay();

        $param = array(
            "wbMerchantId" => $data['alipay_partner'] ? $data['alipay_partner'] : \AlipayConfig::MERCHANTID,
            "orderId" => $data['remark'],//订单号
            "authCode" => $data['customer_id'],//支付授权码
            "totalAmount" => $data['price'],//订单总金额
            "subject" => $data['subject'],//订单标题
            "timeoutExpress" => "2m",//该笔订单允许的最晚付款时间
        );

        $result = $wzPay->pay_bar_code($param);
        return $result;
    }

    /**
     * 支付宝——条码支付
     */
    private function _ali_barcode_pay($data)
    {
        return '支付宝条码支付';
    }

    /**调起支付宝支付
     * @param $res
     */
    private function _alipay($res)
    {
        header("Content-type:text/html;charset=utf-8");
        Vendor('QRcodeAlipay.AlipaySubmit');
        Vendor('QRcodeAlipay.AlipayConfig');
        $config = new \AlipayConfig();
//        1
        $alipay_config = $config->con($res['alipay_partner']);
        $remark = date('YmdHis') . rand(100000, 999999);

        $good_name = "向" . $res['jianchen'] . "支付" . $res['price'] . "元";
//       支付订单提交的数据交互
        $data['merchant_id'] = $res['merchant_id'];
        $data['paystyle_id'] = 2;
        $data['price'] = $res['price'];
        $data['remark'] = $remark;
        $data['status'] = 0;
        $data['paytime'] = time();
        $data['checker_id'] = I("checker_id");
        $data['mode'] = ($data['checker_id'] || $data['checker_id'] == 0) ? 1 : 0;
        $data['cate_id'] = 1;
//        $data['good_name'] = $good_name;

        $this->pay_model->add($data);
        /**************************请求参数**************************/

        //商户订单号，商户网站订单系统中唯一订单号，必填
        $out_trade_no = $remark;

        //订单名称，必填
        $subject = $good_name;

        //付款金额，必填
        $total_fee = $res['price'];

        //收银台页面上，商品展示的超链接，必填
        $show_url = $_POST['WIDshow_url'];

        //商品描述，可空
        $body = $res['name'];


        /************************************************************/

        //构造要请求的参数数组，无需改动
        $parameter = array(
            "service" => $alipay_config['service'],
//            支付宝的ID
            "partner" => $res['alipay_partner'],
            "seller_id" => $res['alipay_partner'],
            "payment_type" => $alipay_config['payment_type'],
            //"notify_url"	=> $alipay_config['notify_url'],
            "return_url" => "http://" . $_SERVER['HTTP_HOST'] . "/index.php?s=/Pay/Barcode/qrcode_alipay_return",
            "_input_charset" => trim(strtolower($alipay_config['input_charset'])),
            "out_trade_no" => $out_trade_no,
            "subject" => $subject,
            "total_fee" => $total_fee,
            "show_url" => $show_url,
            "app_pay" => "Y",//启用此参数能唤起钱包APP支付宝
            "body" => $body,

        );

        //建立请求
        $alipaySubmit = new \AlipaySubmit($alipay_config);
        $html_text = $alipaySubmit->buildRequestForm($parameter, "get", "确认");
        echo $html_text;
    }

    /**
     * 调用微众--支付宝扫码支付
     * 包含台签扫码支付、手机收款扫码支付
     * @param $res
     */
    private function _wz_alipay($res)
    {
        header("Content-type:text/html;charset=utf-8");
        Vendor('QRcodeAlipay.Wz_pay');
        $wzPay = new \Wz_pay();
        $payModel = $this->pay_model;

        $where = array(
            "merchant_id" => $res['merchant_id'],
            "paystyle_id" => "2",
            "price" => $res['price'],
            "status" => "0",
            "mode" => $res['mode'],
            "cate_id" => $res['id'],
//            "paytime" => array('between', array(time() - 30, time() + 30)),
//            "customer_id" => $_REQUEST['PHPSESSID'],
        );
        // $rs = $payModel->where($where)->find();
        $where['subject'] = "向" . $res['jianchen'] . "支付" . $res['price'] . "元";
//        if ($rs) {
//            $remark = rand(1000, 9999) . $rs['remark'];
//            $payModel->where(array("id" => $rs['id']))->save(array("paytime" => time(), "new_order_sn" => $remark));
//        } else {
//            $remark = date('YmdHis') . rand(100000, 999999);
//            $where['remark'] = $remark;
//            $where['paytime'] = time();
//            $where['checker_id'] = $res['checker_id'];
//            $payModel->add($where);
//        }

        $remark = date('YmdHis') . rand(100000, 999999);
        $where['remark'] = $remark;
        $where['paytime'] = time();
        $where['checker_id'] = $res['checker_id'];

        $where['bank'] = 1;
        $wzcost_rate = M("merchants_upwz")->where("mid=" . $res['merchant_id'])->getField("aliCostRate");
        if ($wzcost_rate) $where['cost_rate'] = $wzcost_rate;
        $payModel->add($where);
        //构造要请求的参数数组，无需改动
        $parameter = array(
            'wbMerchantId' => $res['alipay_partner'] ? $res['alipay_partner'] : \AlipayConfig::MERCHANTID,
            'orderId' => $remark,
            'totalAmount' => $res['price'],
            'subject' => $where['subject'],
        );
        $wzPay->pay_for($parameter);
    }


    /**调用微众--支付宝扫码支付【双屏主扫】
     *
     */
    public function screen_wz_alipay()
    {
        header("Content-type:text/html;charset=utf-8");
        Vendor('QRcodeAlipay.Wz_pay');

        $wzPay = new \Wz_pay();

        $seller_id = I('seller_id');//二维码对应的id
        $order_id = I('order_id');
        $checker_id = I('checker_id', 0, 'intval');

        if (!$seller_id) exit('seller_id不能为空!');
        if (!$order_id) exit('订单号不能为空!');

        $res = M('merchants_cate')->where(array("id" => $seller_id))->find();
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
            $this->pay_model->where(array("remark" => $order_info['order_sn']))->save(array("paystyle_id" => 1));
        } else {
            $wzcost_rate = M("merchants_upwz")->where("mid=" . $res['merchant_id'])->getField("aliCostRate");
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
                "cate_id" => $res['id'],
                "checker_id" => $checker_id,
                "bank" => 1,
                "cost_rate" => $wzcost_rate?$wzcost_rate:'',
            );
            $this->pay_model->add($data);
        }

        $data['remark'] = rand(1000, 9999) . $data['remark'];
        $this->pay_model->where(array("remark" => $order_info['order_sn']))->save(array("new_order_sn" => $data['remark']));
        //构造要请求的参数数组,无需改动
        $parameter = array(
            'wbMerchantId' => $res['alipay_partner'] ? $res['alipay_partner'] : \AlipayConfig::MERCHANTID,
            'orderId' => $data['remark'], //商户订单号，商户网站订单系统中唯一订单号，必填
            'totalAmount' => $data['price'],//付款金额，必填
            'subject' => $data['subject'],//订单名称，必填
        );

        $wzPay->pay_for($parameter);
    }

    /**
     * 微众——支付宝扫码支付回调【主扫】
     */
    public function wzali_notify_return()
    {
        Vendor('QRcodeAlipay.Wz_pay');
        $wzPay = new \Wz_pay();
        $result = $wzPay->notify();
        $out_trade_no = $result['orderId'];
        if (mb_strlen($out_trade_no) >= '23') $out_trade_no = mb_substr($out_trade_no, 4, 22, 'utf-8');

        if ($result['tag']) {
            $rs = $this->pay_model->where(array("remark" => $out_trade_no))->save(array("status" => "1", "wz_remark" => $result['tradeNo']));
            if ($rs) $this->update_order_goods_number($out_trade_no);
        }
        $this->push_pay_message($out_trade_no);
    }

    /**
     * 微众——支付宝扫码支付对账单【主扫】
     */
    public function wzali_bill_notice()
    {
        Vendor('QRcodeAlipay.Wz_pay');
        $wzPay = new \Wz_pay();
        $wzPay->bill_notice();
    }

    /**
     * 微众——【支付宝】支付订单查询，用于检测支付是否成功
     * @param int $orderId 订单号，订单唯一标识，自己生成传给微众
     * @param int $mid 商户号，商家进件的商户号，默认为洋仆淘
     * @return bool|mixed 查询支付结果
     */
    public function wzali_query_order($orderId = 0, $mid = 0)
    {
        Vendor('QRcodeAlipay.Wz_pay');
        $wzPay = new \Wz_pay();
        ($orderId = $orderId ? $orderId : I('order_sn', 0, 'intval')) || exit("订单号不能为空!");
        ($mid = $mid ? $mid : I('mid', 0, 'intval')) || exit("商户号不能为空!");
        $rs = $wzPay->queryOrder($orderId, $mid);
        if ($_REQUEST['order_sn']) {
            echo '<pre/>';
            print_r($rs);
        }
        return $rs['info'];
    }

    //支付宝支付回调,成功检测,修改支付状态
    public function qrcode_alipay_return()
    {
        Vendor('QRcodeAlipay.AlipayNotify');
        Vendor('QRcodeAlipay.AlipayConfig');
        $partner = I('partner');
        $config = new \AlipayConfig();
        $alipay_config = $config->con($partner);
        //计算得出通知验证结果
        $alipayNotify = new \AlipayNotify($alipay_config);
        $verify_result = $alipayNotify->verifyReturn();
        //if($verify_result) {//验证成功
        //请在这里加上商户的业务逻辑程序代码

        //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
        //获取支付宝的通知返回参数，可参考技术文档中页面跳转同步通知参数列表

        //商户订单号
        $out_trade_no = $_GET['out_trade_no'];

        //支付宝交易号
        $trade_no = $_GET['trade_no'];

        //交易状态
        $trade_status = $_GET['trade_status'];
        $data['status'] = '1';
        $data['remark'] = $out_trade_no;
//        $data['paytime'] = time();   修改时间去掉

        if ($trade_status == 'TRADE_FINISHED' || $trade_status == 'TRADE_SUCCESS') {
            $res = $this->pay_model->where("remark=$out_trade_no")->save($data);
            if ($res) {
                $this->assign('price', $_GET['total_fee']);
                $this->display();
            }
        } else {
            echo "trade_status=" . $_GET['trade_status'];
        }
        //echo "验证成功<br />";


    }

    /**
     * 微众支付
     * 公账号支付
     */
    public function wz_pay()
    {
//        得到输入的金额和商户的ID
        header("Content-type:text/html;charset=utf-8");
        vendor('Wzpay.Wzpay');
        $wzPay = new \Wzpay();
        $mid  = I('mid')/100;
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
        if(I("jmt_remark")){ //金木堂定单号
            $data['jmt_remark'] = I("jmt_remark");
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
        $remark = date('YmdHis') . rand(100000, 999999);
        //            插入数据库的数据
        $data['merchant_id'] = (int)$res['merchant_id'];
        //$data['customer_id'] = $sub_openid;
        $data['customer_id'] =D("Api/ScreenMem")->add_member("$sub_openid",$res['merchant_id']);
        $data['paystyle_id'] = 1;
        $data['price'] = $price;
        $data['remark'] = $remark;
        $data['status'] = 0;
        $data['cate_id'] = $res['id'];
        $data['phone_info'] = $_SERVER['HTTP_USER_AGENT'];
        $data['paytime'] = time();
        $good_name = "向" . $res['jianchen'] . "支付" . $price . "元";
        $data['subject'] = $good_name;
        //$payModel->add($data);
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
        file_put_contents('./data/log/wz/weixin/' . date("Y_m_") . 'xiaoxi.log', date("Y-m-d H:i:s") . '发送信息:' . $returnData . ",定单号:" . "$remark" . PHP_EOL, FILE_APPEND | LOCK_EX);
        $this->assign('jsApiParameters', $returnData);
        $this->assign('price', $price);
        $this->assign('remark', $remark);
        $this->assign('mid', $mid);
        $this->assign('openid', $sub_openid);
        $this->display('Barcode/wz_pay');

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
        if ($order_id != "") {
            $openid = $this->_get_openid();
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
            $data['mode'] = 3;
            $data['checker_id'] = I("checker_id");
            $order = $order->where("order_id='$order_id'")->find();
            $price = $order['order_amount'];
            $res = M('merchants_cate')->where("id=$id")->find();
//            插入数据库的数据
            $data['merchant_id'] = (int)$res['merchant_id'];
            //$data['customer_id'] = $sub_openid;
            $data['customer_id'] =D("Api/ScreenMem")->add_member("$openid",$res['merchant_id']);
            $data['paystyle_id'] = 1;
            $data['price'] = $price;
            $data['remark'] = $remark;
            $data['status'] = 0;
            $data['cate_id'] = 1;
            $data['bank'] = 1;
            if(I("jmt_remark")){ //金木堂定单号
                $data['jmt_remark'] = I("jmt_remark");
            }
            $wzcost_rate = M("merchants_upwz")->where("mid=" . $res['merchant_id'])->getField("WxCostRate");
            if ($wzcost_rate) {
                $data['cost_rate'] = $wzcost_rate;
            };
            $data['paytime'] = time();
            $data['bill_date'] = date("Ymd", time());
            $order_sn = $remark . rand(1000, 9999);
            $data['new_order_sn'] = $order_sn;
            $this->pay_model->add($data);
            //由于回调地址的原因，将id存入session中

            $good_name = "向" . $res['jianchen'] . "支付" . $price . "元";
//       支付订单提交的数据交互
            $mchid = $res['wx_mchid'];
        }
        //使用统一支付接口()
        //file_put_contents('./data/log/wz/weixin/weixin.log', date("Y-m-d H:i:s") . '订单号' . $remark . '付款金额不一致' . PHP_EOL, FILE_APPEND | LOCK_EX);
        $wzPay->setParameter('sub_openid', $sub_openid);
        $wzPay->setParameter('mch_id', $mchid);
        $wzPay->setParameter('body', $good_name);
        $wzPay->setParameter('out_trade_no', $order_sn);
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


    /**
     * 微众支付  全额退款
     */
    public function wx_pay_back($remark)
    {
        header("Content-type:text/html;charset=utf-8");
        vendor('Wzpay.Wzcommon');
        vendor("WzPay.pub.config.php");
        $wzPay = new \Wzcommon();
        $pay = $this->pay_model->where("remark='$remark' And status = 1")->find();
        if ($pay['new_order_sn'] == null) {
            $terminal_serialno = $remark;
        } else {
            $terminal_serialno = $pay['new_order_sn'];
        }
        if (!$pay) return array("code" => "error", "msg" => "失败", "data" => "未找到订单");
        $merchant_id = $pay['merchant_id'];
        $mch_id = M("merchants_cate")->where("merchant_id=$merchant_id")->getField("wx_mchid");

        $refund_amount = $pay['price'];
        $url = \WzPayConf_pub::PAY_BACK;

        $wzPay->setParameter('merchant_code', $mch_id);
        $wzPay->setParameter('terminal_serialno', $terminal_serialno);
        $wzPay->setParameter('refund_amount', $refund_amount);
        $returnData = $wzPay->getParameters($url, $mch_id);
        if ($returnData == "OK") {
            $this->pay_back_suc($remark, $refund_amount);
            return array("code" => "success", "msg" => "成功", "data" => "退款成功");
        } else {
            if ($this->pay_model->where("remark='$remark'")->find()) {
                $this->pay_model->where("remark='$remark'")->save(array("status" => 3, "back_status" => 0));
            }
            return array("code" => "success", "msg" => "成功", "data" => "退款失败");
        }
    }

    function pay_back_suc($remark, $refund_amount)
    {
        if ($this->pay_model->where("remark='$remark'")->find()) {
            $this->pay_model->where("remark='$remark'")->save(array("status" => 2, "back_status" => 1, "price_back" => $refund_amount));
        }
    }

    /**退款成功
     * @return array
     */
    public function ali_pay_back($remark)
    {
        header("Content-type:text/html;charset=utf-8");
        Vendor('QRcodeAlipay.Wz_pay');
        $wzPay = new \Wz_pay();
        if (!$remark) return array("flag" => false, "msg" => "订单号不存在");
        $pay = $this->pay_model->where(array("remark" => $remark))->find();
        if (!$pay) return array("flag" => false, "msg" => "该订单不存在");
        if ($pay['status'] == "2") return array("flag" => false, "msg" => "不能重复退款");
        $merchant_id = $pay['merchant_id'];
        $res = M("merchants_cate")->where("merchant_id=$merchant_id")->find();
        if (!$res) return array("flag" => false, "msg" => "商户不存在");
        $outRequestNo = $remark . rand(1000, 9999);
        $parameter = array(
            'wbMerchantId' => $res['alipay_partner'] ? $res['alipay_partner'] : \AlipayConfig::MERCHANTID,
            'orderId' => $pay['mode'] < 3 ? $pay['remark'] : $pay['new_order_sn'], //商户订单号，商户网站订单系统中唯一订单号，必填
            'refundAmount' => $pay['price'],//付款金额，必填
            'outRequestNo' => $outRequestNo,//退款请求号，每次退款需保证唯一
        );

        $res = $wzPay->pay_back($parameter);

        if ($res['flag']) {
            $this->pay_back_suc($remark, $pay['price']);
            return array("code" => "success", "msg" => "成功", "data" => "退款成功");
        } else {
            $this->pay_model->where(array("remark" => $remark))->save(array("status" => "3"));
            return array("code" => "success", "msg" => "成功", "data" => "退款失败");
        }


    }


    /**
     * 支付成功后更新库存
     * 传入订单唯一标识$order_sn
     * @param int $order_sn
     */
    private function update_order_goods_number($order_sn = 0)
    {
        if (!$order_sn) return;
        $order_id = M("order")->where(array("order_sn" => $order_sn))->getField("order_id");
        $order_goods_list = M("order_goods")->where(array("order_id" => $order_id))->field("goods_id,goods_num")->select();
        if (!$order_goods_list) return;
        foreach ($order_goods_list as $k => $v) {
            if ($v['goods_id'] && $v['goods_num']) M("goods")->where(array("goods_id" => $v['goods_id']))->setDec('goods_number', $v['goods_num']); //更新库存
        }
    }

    /**
     * 微众支付
     * 公众号支付回调
     */
    public function wx_notify_return()
    {

        vendor('Wzpay.Wzpay');
        $wzPay = new \Wzpay();
        $result = $wzPay->notify();
        if ($result) {
            $orderInfo['out_trade_no'] = $result['out_trade_no'];
            $orderInfo['transaction_id'] = $result['transaction_id'];
            $orderInfo['mch_id'] = $result['mch_id'];
            $wz_remark = $result['orderid'];
            //file_put_contents('./data/log/wz/weixin/' . date("Y_m_") . 'weixin.log', date("Y-m-d H:i:s") . '测试订单号' . $wz_remark . PHP_EOL, FILE_APPEND | LOCK_EX);

//通过订单搜寻支付的数据库数据
            $remark = $orderInfo['out_trade_no'];
//           每次与微众相连需要商户号
            $queryOrderInfo = $wzPay->queryOrder($orderInfo);

            //从数据库查出订单价格,然后跟微众那边做对比
//            $pay=$this->pay_model->where('remark=$remark')->find();

            if (strlen($remark) != 20) {
                $remark = substr($remark, 0, -4);
            }
            $ab = $this->pay_model->where("remark='$remark'")->find();
            $orderPrice = $ab['price'];
            $id = $ab['id'];
            if ($queryOrderInfo['status'] === '0' && $queryOrderInfo['result_code'] === '0' && $queryOrderInfo['trade_state'] === 'SUCCESS') {
                //特别注意：商户后台接收到通知参数后，要对接收到通知参数里的订单号out_trade_no 和订单金额total_fee 和自身业务系统的订单和金额做校验
                //校验一致后才更新数据库订单状态
                if (bccomp($orderPrice * 100, $result['total_fee'], 3) === 0) {

                    $pay_change = $this->pay_model;
                    $pay_change->time = time();
                    $pay_change->price_gold = $queryOrderInfo['coupon_fee'];
                    $pay_change->status = 1;
                    $pay_change->wz_remark = $wz_remark;
                    $pay_change->where("id=$id")->save();
                    $this->push_pay_message($remark);
                    $order_id = $ab['order_id'];
                    if ($order_id != 0) {
                        $order_change = M("order");  //修改订单状态
                        $order_one = $order_change->where("order_id='$order_id'")->find();
                        if ($order_one) {
                            $this->_change_coupon($order_one);
                        }
                    }
                } else {
                    $this->push_pay_message($remark);
                    file_put_contents('./data/log/wz/weixin/' . date("Y_m_") . 'weixin.log', date("Y-m-d H:i:s") . '扫码支付回调信息' . $result['type'] . $result['data'] . PHP_EOL, FILE_APPEND | LOCK_EX);
                }
            } else {
                $this->push_pay_message($remark);
                file_put_contents('./data/log/wz/weixin/' . date("Y_m_") . 'weixin.log', date("Y-m-d H:i:s") . '重复回调或不存在' . json_encode($queryOrderInfo) . '重复回调或不存在' . PHP_EOL, FILE_APPEND | LOCK_EX);
            }
        }
    }

    public function push_pay_message($remark)
    {
        $pay = $this->pay_model->where("remark='$remark'")->find();
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
            $url = "https://api.weixin.qq.com/card/code/consume?access_token=" . get_weixin_token();
            $data['code'] = $code;
            $use_coupon = request_post($url, json_encode($data));
            $use_coupon = json_decode($use_coupon);
            file_put_contents('./data/log/wz/weixin/coupon.log', date("Y-m-d H:i:s") . '用户使用优惠券' . json_encode($use_coupon) . PHP_EOL, FILE_APPEND | LOCK_EX);
            if ($use_coupon->errmsg != "ok") {
                file_put_contents('./data/log/wz/weixin/coupon.log', date("Y-m-d H:i:s") . '用户使用优惠券失败' . $order_id . PHP_EOL, FILE_APPEND | LOCK_EX);
            }

            if ($use_coupon->errmsg == "ok") {
                M("screen_user_coupons")->where("usercard='$code'")->save(array("satus" => 0));
            }
        }
    }

    /**
     * 微众公众号支付回调
     */
    public function weixipay_return()
    {
        $price = (int)I('price') / 100;
        $openid = I('openid');
        $mid  = I('mid');
        $remark = I("remark");
        $merchants_info = M('pay p')->join('__MERCHANTS__ m on p.merchant_id=m.id')->where("p.customer_id= '$openid'AND p.merchant_id=$mid")->Field('p.remark,p.paytime,m.uid,m.merchant_name')->order('paytime desc')->find();
        file_put_contents('./data/log/wz/weixin/' . date("Y_m_") . 'taika.log', date("Y-m-d H:i:s") . 'remark:' . $remark . ',openid:' . $openid . ',price:' . $price . PHP_EOL, FILE_APPEND | LOCK_EX);
        $this->assign('price', $price);
        $this->assign('pay_time', $merchants_info['paytime']);
        $this->assign('remark', $merchants_info['remark']);
        $this->assign('mername', $merchants_info['merchant_name']);
        $this->display();


    }


    public function lingqu1()
    {
            $this->display();

    }
    /**
     * 微众支付 ajax请求
     */
    public function weixipay_order_confirm()
    {
        $remark = I("remark");
        $pay_one = $this->pay_model->where("remark='$remark' ")->find();
        if ($pay_one) {
            $mid = $pay_one['merchant_id'];
            $mch_id = M("merchants_cate")->where("merchant_id=$mid")->getField("wx_mchid");
        }
        if ($remark && $mch_id) {
            sleep(5);
            $checker_order = $this->wz_query_order($remark, $mch_id);
            file_put_contents('./data/log/wz/weixin/' . date("Y_m_") . 'orderConfirm.log', date("Y-m-d H:i:s") . "查询订单结果" . 'remark:' . json_encode($checker_order) . PHP_EOL, FILE_APPEND | LOCK_EX);
            if ($checker_order['trade_state'] == "SUCCESS") {
                $pay_change = $this->pay_model;
                $pay_change->status = 1;
                $pay_change->confirm_status = 1;
                $pay_change->where("remark='$remark'")->save();
            }
        } else {
            file_put_contents('./data/log/wz/weixin/' . date("Y_m_") . 'orderConfirm.log', date("Y-m-d H:i:s") . "信息错误:" . 'remark:' . $remark . 'mchid:' . $mch_id . PHP_EOL, FILE_APPEND | LOCK_EX);
        }

    }


    /**
     * 微众支付
     * 公众号支付订单查询
     * @param int $out_trade_no
     * @param int $mch_id
     */
    public function wz_query_order($out_trade_no = 0, $mch_id = 0)
    {
        vendor('Wzpay.Wzpay');
        $wzPay = new \Wzpay();
        $out_trade_no = $out_trade_no ? $out_trade_no : I("out_trade_no", "");
        $mch_id = $mch_id ? $mch_id : I("mch_id", "107584000030001");
        $data = array(
            "out_trade_no" => $out_trade_no,
            "mch_id" => $mch_id,
        );
        $result = $wzPay->queryOrder($data);
        return $result;
    }


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
     * 微众支付  微信
     * 扫码支付
     */
    public function wz_nativepay()
    {
        if (IS_POST) {
            vendor('Wzpay.nativepay');
            $nativepay = new \nativepay();
            $result = $nativepay->pay(array('amount' => '0.01'));
            if ($result['flag'] == true) {
                $this->assign('url', $result['message']['url']);
            } else {
                echo $result['message'];
            }
        }

        $this->display();
    }

    /**
     * 微众扫码支付  微信
     * 生成二维码
     */
    public function wz_qrcode()
    {
        $url = I('get.url');
        vendor('Wzpay.phpqrcode.phpqrcode');
        \QRcode::png($url);
    }

    /**
     * 微众支付
     * 刷卡支付
     */
    public function wz_micropay($id, $price, $auth_code, $checker_id,$jmt_remark ="",$order_sn="")
    {
        header('Content-Type:application/json; charset=utf-8');
//        if (IS_POST) {
        vendor('Wzpay.micropay');
//        $auth_code = I('post.auth_code', 0);
        if (!$auth_code) {
            $this->error('参数错误!');
        }
//        $id = I("id");
//        $price = I("price");
//        $checker_id = I("checker_id");
//           $checker_id=0;
//            $price = 0.01;
//            $id = 30;
//            支付信息
        if (!$id) {
            return array("code" => "error", "msg" => "失败", "data" => "支付失败");
        }
        $res = M('merchants_cate')->where("merchant_id=$id")->find();
        if ((int)$res['merchant_id'] == 0) {
            return array("code" => "error", "msg" => "失败", "data" => "还未申请支付业务");
        }
        $remark = date('YmdHis') . rand(100000, 999999);
//            插入数据库的数据
        $data['merchant_id'] = (int)$res['merchant_id'];
        $data['checker_id'] = $checker_id;
        $data['paystyle_id'] = 1;
        $data['price'] = $price;
        $data['remark'] = $remark;
        $data['status'] = 0;
        $data['cate_id'] = 1;
        $data['mode'] = 2;
        $data['paytime'] = time();
        $data['bank'] = 1;
        if($jmt_remark){ //金木堂定单号
            $data['jmt_remark'] = $jmt_remark;
        }
//        添加的数据
        $wzcost_rate = M("merchants_upwz")->where("mid=" . $res['merchant_id'])->getField("WxCostRate");
        if ($wzcost_rate) {
            $data['cost_rate'] = $wzcost_rate;
        };
        $data['bill_date'] = date("Ymd", time());

        $data['phone_info'] = $_SERVER['HTTP_USER_AGENT'];
        $merchant_code = $res["wx_mchid"];
        $key = $res["wx_key"];
        $product = "向" . $res['jianchen'] . "支付" . $price . "元";
        $this->pay_model->add($data);

        $data = array('pay_money' => $price, 'auth_code' => $auth_code, 'remark' => $remark, 'merchant_code' => $merchant_code, 'product' => $product, 'key' => $key);
        $micropay = new \micropay();
        $result = $micropay->pay($data);

        if ($result['flag'] == false) {
//            if($result['message'] == null) return array("code" => "error", "msg" => "失败", "data" => "收款码错误,请重新输入");
            $this->push_pay_message($remark);
            return array("code" => "error", "msg" => "失败", "data" => $result['message']);
        } else {
            $pay_change = $this->pay_model;
//            $data['paytime'] = time();
            $data['status'] = 1;
            if ($pay_change->where("remark=$remark")->find()) $pay_change->where("remark=$remark")->save($data);
            $this->push_pay_message($remark);
            return array("code" => "success", "msg" => "成功", "data" => $result['message']);
        }

    }

    /**
     * 微众支付
     * 扫码回调
     */
    public function wz_nativeurl()
    {
        vendor('Wzpay.nativepay');
        $nativepay = new \nativepay();
        $data = $nativepay->callback();
        if ($data) {
            file_put_contents('./data/log/wz/nativepay.log', date("Y-m-d H:i:s") . var_export($data, true) . PHP_EOL, FILE_APPEND | LOCK_EX);
        } else {
            file_put_contents('./data/log/wz/nativepay.log', date("Y-m-d H:i:s") . 'error' . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
    }

    /**
     * 微众支付
     * 扫码支付订单查询
     */
    public function wz_native_query_order()
    {
        vendor('Wzpay.nativepay');
        //通过orderid来查询订单,可以修改通过商户订单号或者微信支付订单号查询
        $queryOrder = new \orderQuery('20170518152417430671');
        $data = $queryOrder->orderQuery();
        dump($data);
    }


    /**
     * 微众支付
     * 刷卡支付订单查询
     */
    public function query_micropay()
    {
        vendor('Wzpay.micropay');
        $query = new \orderQuery('201701190950511484790651847747');
        echo '<hr/>';
        echo '-----------------------------<hr/>';
        dump($query->orderQuery());
        echo '-----------------------------<hr/>';
    }

    /**
     * 微众支付
     * 对账单下载
     */
    public function check_order()
    {
//        $json_str = '{"type":"FILE","data":"{\"corporationId\":\"99996\",\"app_id\":\"P5840001\",\"token\":\"9I6Dxosn\",\"file_id\":\"90000000149015678725301932931174000000014894655872533537\",\"file_hash\":\"34eb94dad72385484760cbf62f551839\",\"work_date\":\"20170313\"}"}';
        $json_str = file_get_contents('php://input', 'r');
        file_put_contents('./data/log/wz/checkOrder.log', date("Y-m-d H:i:s") . $json_str . PHP_EOL, FILE_APPEND | LOCK_EX);
        $data = json_decode($json_str, true);
        $info = json_decode($data['data'], true);
        $url = 'https://svrapi.webank.com/api/base/file?app_id=' . $info['app_id'] . '&token=' . $info['token'] . '&file_id=' . $info['file_id'] . '&version=1.0.0';
        file_put_contents('./data/log/wz/checkOrder.log', date("Y-m-d H:i:s") . $url . PHP_EOL, FILE_APPEND | LOCK_EX);
        $str = $this->_getXmlSSLCurl($url);
        file_put_contents('./data/log/wz/checkOrder.log', date("Y-m-d H:i:s") . $str . PHP_EOL, FILE_APPEND | LOCK_EX);
        //把账单流水插入数据库
        $this->insert_bill($str);

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

}



