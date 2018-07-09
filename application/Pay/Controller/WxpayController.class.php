<?php

namespace Pay\Controller;

use Common\Controller\HomebaseController;

class WxpayController extends HomebaseController
{
    private $pay_model;
    
    public function __construct()
    {
        parent::__construct();
        $this->path = $_SERVER['DOCUMENT_ROOT'] . '/data/log/weixin/';
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
        Vendor('WxPayPubHelper.WxPayPubHelper');
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
        Vendor('WxPayPubHelper.WxPayPubHelper');
        // 这里直接获得openid;
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            $id = I("id");
            $checker_id = I("checker_id");
            $merchant = M("merchants_cate")->where(array('id' => $id))->find();
            $url = \WxPayConf_pub::JS_API_CALL_URL . "/id/{$id}/checker_id/{$checker_id}";
            $openid = $this->_get_openid($url);
            $this->getOffer($merchant, $openid);

            $this->assign("checker_id", $checker_id);
            $this->assign('openid', $openid);
            $this->assign("merchant", $merchant);
            $this->assign('seller_id', I("id"));
            $this->display();
            exit;
        }
    }

    /**
     * 公众号支付扫码支付收款
     */
    public function wx_pay()
    {
        // 得到输入的金额和商户的ID
        header("Content-type:text/html;charset=utf-8");
        Vendor('WxPayPubHelper.WxPayPubHelper');
        $jsApi = new \JsApi_pub();
        // 先获取openid防止 回调
        $remark = I('order_sn',date('YmdHis') . rand(100000, 999999));
        $mode = I('mode');
        if (I("seller_id") == "") {
            $id = I("id");
            $price = I("price");
            $checker_id = I("checker_id");
            $url = \WxPayConf_pub::PHONE_API_CALL_URL . "/id/{$id}/price/{$price}/checker_id/{$checker_id}/order_sn/$remark/mode/$mode";
            $sub_openid = $this->_get_openid($url);
            $res = M('merchants_cate')->where("id=$id")->find();
            $data['mode'] = I('mode',1);
            $data['checker_id'] = $checker_id;
        }
        if (I('seller_id') !== "") {
            $sub_openid = I('openid');
            $id = I('seller_id');
            $res = M('merchants_cate')->where("id=$id")->find();
            $price = I('price');
            $data['checker_id'] = $res['checker_id'];
            $data['mode'] = I('mode',0);
        }
        if(!$sub_openid){
            exit;
        }
        $data['bank'] = 3;
        if (I("checker_id")) {
            $data['checker_id'] = I("checker_id");
        } //app上的台签带上收银员的信息
        $wx_mch_data = M("merchants_upwx")->where("mid=" . $res['merchant_id'])->field("sub_mchid,cost_rate")->find();
        $wx_cost_rate = $wx_mch_data['cost_rate'];
        if ($wx_cost_rate) {
            $data['cost_rate'] = $wx_cost_rate;
        };
        $data['bill_date'] = date("Ymd", time());
        $payModel = $this->pay_model;

        // 插入数据库的数据
        $data['merchant_id'] = (int)$res['merchant_id'];
        $data['customer_id'] = $sub_openid;
        $data['paystyle_id'] = 1;
        $data['price'] = $price;
        $data['remark'] = $remark;
        $data['status'] = 0;
        $data['cate_id'] = $res['id'];
        $data['phone_info'] = $_SERVER['HTTP_USER_AGENT'];
        $data['add_time'] = time();
        $data['paytime'] = '0';
        $good_name = "向" . $res['jianchen'] . "支付" . $price . "元";
        $data['subject'] = $good_name;
        if(I("jmt_remark")){
            $data['jmt_remark']=I("jmt_remark");
        } else{
            $data['jmt_remark'] = I('memo', '');
        }
        $remark_exists = $payModel->where(array('remark'=>$remark))->find();
        if(!$remark_exists){
            $payModel->add($data);
        }
        // 微信围餐分配的商户id
//        $mchid = $wx_mch_data['sub_mchid'];
        $mchid = $res['wx_mchid'];

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
        $this->assign('mid', $res['merchant_id']);
        $this->display();
        exit;

    }

    /**
     * JSAPI支付通知,通用通知接口
     */
    public function notify()
    {
        Vendor('WxPayPubHelper.WxPayPubHelper');
        //使用通用通知接口
        $notify = new \Notify_pub();

        //存储微信的回调
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        $notify->saveData($xml);

        //验证签名，并回应微信。
        //对后台通知交互时，如果微信收到商户的应答不是成功或超时，微信认为通知失败，
        //微信会通过一定的策略（如30分钟共8次）定期重新发起通知，
        //尽可能提高通知的成功率，但微信不保证通知最终能成功。
        if ($notify->checkSign() == FALSE) {
            $return = array('return_code' => "FAIL", 'return_msg' => "签名失败");
            get_date_dir($this->path,'pay_notify','签名失败',$xml);
        } else {
            $data = $notify->data;
            $out_trade_no = $data["out_trade_no"];//回调的订单号
            if ($data['return_code'] == 'SUCCESS' && $data['result_code'] == 'SUCCESS') {
                // 读取订单信息
                $pay_info = $this->pay_model->where("remark='$out_trade_no'")->find();
                // 如果订单已支付返回成功
                if($pay_info['status'] == 1){
                    $return = array('return_code' => "SUCCESS",'return_msg' => "");
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
                    $save_data['price_back'] = $data['cash_fee']/100;
                    $save_data['price_gold'] = (isset($data['coupon_fee']) ? $data['coupon_fee'] : 0)/100;
                    $save_data['wx_remark'] = $data['transaction_id'];
                    $this->pay_model->where(array('id'=>$id))->save($save_data);
                    if($pay_info['mode'] == '0' && $pay_info['order_id'] != 0){
                        A('Barcode')->cardOff($pay_info['order_id']);
                    }
                    // 手机app推送消息
//                    $notify->setReturnParameter("return_code", "SUCCESS");//设置返回码
                    $return = array('return_code' => "SUCCESS",'return_msg' => "");
                    get_date_dir($this->path,'pay_notify','支付成功',json_encode($data));
                    A("Pay/Barcode")->push_pay_message($out_trade_no);
                } else {
                    get_date_dir($this->path,'pay_notify','金额对比异常',json_encode($data));
                    $return = array('return_code' => "FAIL");
                    A("Pay/Barcode")->push_pay_message($out_trade_no);
                }
            } else {
                get_date_dir($this->path,'pay_notify','重复回调或不存在',json_encode($data));
                $return = array('return_code' => "FAIL");
                A("Pay/Barcode")->push_pay_message($out_trade_no);
            }
        }

        $returnXml = $notify->returnNotifyXml($return);
        echo $returnXml;
    }

    /**
     * 双屏扫码支付
     */
    public function two_wxpay()
    {
        header("Content-type:text/html;charset=utf-8");
        Vendor('WxPayPubHelper.WxPayPubHelper');
        $jsApi = new \JsApi_pub();
//        先获取openid防止 回调
        $order_id = I("order_id");  // 订单id
        $checker_id = I("checker_id");
        $price = I("price");
        $id = I("id");
        $url = \WxPayConf_pub::TWO_API_CALL_URL . "/id/{$id}/order_id/{$order_id}/checker_id/{$checker_id}/price/{$price}";
        $openid = $this->_get_openid($url);
        if ($order_id != "") {
            $order = M("order");
            $remark = $order->where("order_id='$order_id'")->getField("order_sn");
//            $sub_openid = $openid;
            $data['order_id'] = $order_id;
            $data['mode'] = I('mode',3);
            $data['checker_id'] = I("checker_id");
            $order = $order->where("order_id='$order_id'")->find();
            $price = $order['order_amount'];
            $res = M('merchants_cate')->where("id=$id")->find();
            $wx_cost_rate = M("merchants_upwx")->where("mid=" . $res['merchant_id'])->getField("cost_rate");
//            插入数据库的数据
            $data['cost_rate'] = $wx_cost_rate;
            $order_sn = $remark;
            $data['merchant_id'] = (int)$res['merchant_id'];
            $data['customer_id'] = $openid;
            $data['paystyle_id'] = 1;
            $data['price'] = $price;
            $data['remark'] = $order_sn;
            $data['status'] = 0;
            $data['cate_id'] = 1;
            $data['add_time'] = time();
            $data['paytime'] = '0';
            $data['new_order_sn'] = $order_sn;
            $data['bank'] = 3;
            $data['jmt_remark'] = I('memo', '');
            $remark_exists = $this->pay_model->where("remark='$order_sn'")->find();
            if(!$remark_exists){
                $this->pay_model->add($data);
            }
            //由于回调地址的原因，将id存入session中

            $good_name = "向" . $res['jianchen'] . "支付" . $price . "元";
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
        $unifiedOrder->setParameter("out_trade_no", "$order_sn");//商户订单号
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
        $this->assign('mid', $res['merchant_id']);
        $this->display("wx_pay");
        exit;
    }

    /**
     * 刷卡支付
     */
    public function micropay($id, $price, $auth_code, $checker_id,$jmt_remark,$order_sn,$mode=2)
    {
        Vendor('WxPayPubHelper.WxPayPubHelper');

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
        $remark = $order_sn?$order_sn:date('YmdHis') . rand(100000, 999999);
//            插入数据库的数据
        $data['merchant_id'] = (int)$res['merchant_id'];
        $data['checker_id'] = $checker_id;
        $data['paystyle_id'] = 1;
        $data['price'] = $price;
        $data['remark'] = $remark;
        $data['status'] = 0;
        $data['cate_id'] = $res['id'];
        $data['mode'] = $mode;
        $data['add_time'] = time();
        $data['paytime'] = '0';
        $data['bank'] = 3;
        if($jmt_remark)$data['jmt_remark']=$jmt_remark;
//        添加的数据 
        $wx_cost_rate = M("merchants_upwx")->where("mid=" . $res['merchant_id'])->getField("cost_rate");
        if ($wx_cost_rate) {
            $data['cost_rate'] = $wx_cost_rate;
        };
        $data['bill_date'] = date("Ymd", time());

        $data['phone_info'] = $_SERVER['HTTP_USER_AGENT'];
        // 微信围餐分配的商户id
//        $mchid = $wx_cost_rate["sub_mchid"];
        $mchid = $res['wx_mchid'];

        $key = $res["wx_key"];
        $product = "向" . $res['jianchen'] . "支付" . $price . "元";
        $this->pay_model->add($data);

//        $data = array('pay_money' => $price, 'auth_code' => $auth_code, 'remark' => $remark, 'merchant_code' => $mchid, 'product' => $product, 'key' => $key);
        $input = new \WxPayMicroPay();
        $input->setParameter("auth_code", "$auth_code");    // 授权码
        $input->setParameter("body", "$product");  // 商品描述
        $input->setParameter("total_fee", $price*100); // 总金额
        $input->setParameter("out_trade_no", "$remark");  // 商户订单号
        $input->setParameter("sub_mch_id", $mchid);    // 子商户号

        $result = $input->pay();
        if ($result['flag'] == false) {
            get_date_dir($this->path,'pay_micro','失败',':micropay:订单号:' . $remark.',返回参数:'.json_encode($result));
            return array("code" => "error", "msg" => "失败", "data" => $result['msg']);
//            A("Pay/Barcode")->push_pay_message($remark);
        } else {
            get_date_dir($this->path,'pay_micro','成功',':micropay:订单号:' . $remark.',返回参数:'.json_encode($result));
            $save['paytime'] = time();
            $save['status'] = 1;
            $this->pay_model->where(array('remark' => $remark))->save($save);
            return array("code" => "success", "msg" => "成功", "data" => $result['msg']);
            A("App/PushMsg")->push_pay_message($remark);
        }

    }

    /**
     * 退款 https://sy.youngport.com.cn/index.php?g=Pay&m=Wxpay&a=pay_back
     * @param $remark 系统订单号
     * @param $price_back 退款金额
     * @return array
     */
    public function pay_back($remark,$price_back)
    {
//        $remark = '20170615105728290512';
        Vendor('WxPayPubHelper.WxPayPubHelper');
        $payBack = new \Refund_pub();
        // 查找交易记录表获取相关信息
        $pay = $this->pay_model->where("remark='$remark' And status = 1")->find();
        // 获取微信子商户ID
        $wx_mchid = M('merchants_upwx')
            ->where(array('mid' => $pay['merchant_id']))
            ->getField('sub_mchid');
        $payBack->setParameter('sub_mch_id', $wx_mchid);  //子商户号
        //$payBack->setParameter('transaction_id', $pay['wx_remark']);  //微信订单号 商户订单号只需一个，优先使用微信单号
        $payBack->setParameter('out_trade_no', $pay['remark']);  //商户订单号
        $payBack->setParameter('total_fee', $pay['price']*100);  //订单金额
        //$payBack->setParameter('refund_fee', $pay['price_back']*100);  //申请退款金额
        $payBack->setParameter('refund_fee', $price_back*100);  //申请退款金额
        $payBack->setParameter('out_refund_no', 'tk'.$remark);  //商户退款单号
        $result = $payBack->payBack();
        $sign = $result['sign'];
        unset($result['sign']);
        $_sign = $payBack->getSign($result);
        if ($sign != $_sign) {
            get_date_dir($this->path,'pay_back','签名错误',json_encode($result));
            return array('code' => 'error', 'msg' => '签名错误');
        }
        if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
            $data['status'] = '2';
            $data['back_status'] = '1';
            $data['price_back'] = $price_back;
            $this->pay_model->where(array('remark' => $remark))->save($data);
            get_date_dir($this->path,'pay_back','退款成功',json_encode($result));
            return array("code" => "success", "msg" => "成功", "data" => "退款成功");
        } else {
            get_date_dir($this->path,'pay_back','退款信息',json_encode($result));
            return array('code' => 'error', 'msg' => '退款失败','data' => $result);
        }
    }
    
    /**
     * 对账单下载
     */
    public function check_order()
    {
        $time = I('time', '');
        $time = !empty($time) ? $time : date("Ymd",strtotime("-1 day"));
        $check = M('everyday_wx_bill')->where(array('bill_date' => $time))->find();
        if($check){
            exit('已获取');
        }
        Vendor('WxPayPubHelper.WxPayPubHelper');
        $download = new \Wxpay_client_pub;
//            $download->setParameter('sub_mch_id', '');// 微信支付分配的子商户号，如需下载指定的子商户号对账单，则此参数必传。
        $download->setParameter('bill_date', $time);// 下载对账单的日期，格式：20140603
        $download->setParameter('bill_type', 'ALL');// ALL，返回当日所有订单信息,默认值。SUCCESS,返回当日成功支付的订单。REFUND,返回当日退款订单RECHARGE_REFUND，返回当日充值退款订单（相比其他对账单多一栏“返还手续费”）
//            $download->setParameter('tar_type', '');// 非必传参数，固定值：GZIP，返回格式为.gzip的压缩包账单。不传则默认为数据流形式。
        $download->url = \WxPayConf_pub::BILL_URL;
        $download->curl_timeout = 5;
        $response = $download->getBillResult();
        if(substr($response,1,3) == 'xml'){
            $response = $download->xmlToArray($response);
            get_date_dir($this->path,'check_bill','获取账单失败',json_encode($response));
            exit;
        }
        get_date_dir($this->path,'check_bill','获取账单成功',$time);
        $this->insert_bill($response);
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
        $arr[0] = array('bill_date','app_id','mchid','sub_mchid','device_info','wx_order_sn','mch_order_sn','openid','deal_type','deal_status','pay_bank','currency_type','deal_money','discount','wx_pay_back_sn','mch_pay_back_sn','pay_back_money','pay_back_discount','pay_back_type','pay_back_status','goods_name','goods_detail','poundage','cost_cate','add_time');
        //日期,交易总笔数,消费交易笔数,退货交易笔数,冲正交易笔数,交易总金额,手续费总额,代理商手续费总额,清算总金额,添加时间
        $arr[$length - 2] = array('bill_date', 'total_deal', 'consume_deal', 'return_deal', 'reverse_deal', 'total_money', 'poundage', 'anency_poudage', 'pay_money', 'add_time');
        //总交易单数,总交易额,总退款金额,总代金券或立减优惠退款金额,手续费总金额
        $arr[$length - 2] = array('total_deal', 'total_money', 'total_pay_back_money', 'total_pay_back_discount', 'poundage', 'bill_date','add_time');
        $bill_time = '';
        foreach ($arr as $k => $v) {
            if ($k != 0 && $k < $length - 2) {
                $array = array();
                array_map(function ($item) use (&$array) {
                    $array[] = substr($item,1);
                }, $v);
                array_push($array, time());
                $array[0] = strtotime($array[0]);
                $bill_time = $array[0];
                $detail_arr[] = array_combine($arr[0], $array);
            }
            if ($k == $length - 1) {$array = array();
                array_map(function ($item) use (&$array) {
                    $array[] = substr($item,1);
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


    /**
     * 双屏扫码支付
     */
    public function api_wxpay($params)
    {
        header("Content-type:text/html;charset=utf-8");
        Vendor('WxPayPubHelper.WxPayPubHelper');
        $jsApi = new \JsApi_pub();
        $price = $params['price'];
        // 微信围餐分配的商户id
        $mchid = M('merchants_upwx')->where(array('mid'=>$params['merchant_id']))->getField('sub_mchid');
        //使用统一支付接口
        $unifiedOrder = new \UnifiedOrder_pub();

        $unifiedOrder->setParameter("openid", $params['open_id']);//openid和sub_openid可以选传其中之一
        // $unifiedOrder->setParameter("sub_openid", "$sub_openid");//子商户appid下的唯一标识
        $unifiedOrder->setParameter("body", $params['subject']);//商品描述
        //自定义订单号，
        $unifiedOrder->setParameter("out_trade_no", $params['remark']);//商户订单号
        $unifiedOrder->setParameter("total_fee", $price * 100);//总金额
        $unifiedOrder->setParameter("notify_url", \WxPayConf_pub::NOTIFY_URL);//通知地址
        $unifiedOrder->setParameter("trade_type", "JSAPI");//交易类型
        $unifiedOrder->setParameter("sub_mch_id", $mchid);//子商户号服务商必填

        $prepay_id = $unifiedOrder->getPrepayId();
        //=========步骤3：使用jsapi调起支付============
        $jsApi->setPrepayId($prepay_id);
        $jsApiParameters = $jsApi->getParameters();
        return array('code'=>'0', 'pay_info'=>$jsApiParameters);
    }


    public function precreate($input)
    {
        $notify_url = 'https://sy.youngport.com.cn/Pay/Notify/weixin_notify';

        Vendor('WxPayPubHelper.WxPayPubHelper');
        $nativeApi = new \NativeLink_pub();
        //使用统一支付接口
        $unifiedOrder = new \UnifiedOrder_pub();

        $unifiedOrder->setParameter("body", $input['body']);//商品描述
        $unifiedOrder->setParameter("out_trade_no", $input['remark']);//商户订单号
        $unifiedOrder->setParameter("total_fee", $input['amount'] * 100);//总金额
        $unifiedOrder->setParameter("notify_url", $notify_url);//通知地址
        $unifiedOrder->setParameter("trade_type", "NATIVE");//交易类型
        $unifiedOrder->setParameter("sub_mch_id", '1490433412');//子商户号服务商必填
        $unifiedOrder->setParameter("product_id", $input['mch_id']);

        $res = $unifiedOrder->get_code_url();
        get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Pay/','wx','参数', json_encode($res));
        if($res['return_code'] == 'SUCCESS' && $res['result_code'] == 'SUCCESS'){
            return array('code'=>'0000', 'url'=>$res['code_url'],'rate'=>'1');
        } else {
            return array('code'=>'1001', 'msg'=>$res['return_msg']);
        }
    }

}
