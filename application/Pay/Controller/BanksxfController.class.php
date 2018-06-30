<?php

namespace Pay\Controller;

use Common\Controller\HomebaseController;
use Common\Lib\Subtable;


/**
 * 随行付支付
 * Class BanksxfController
 * @package Pay\Controller
 */
class BanksxfController extends HomebaseController
{
    private $mode;
    private $rate;
    private $mno;
    private $price;
    private $openid;
    private $remark;
    private $subject;
    private $cate_id = 0;
    private $order_id = 0;
    private $checker_id;
    private $jmt_remark;
    private $paystyle_id;
    private $merchant_id;
    private $auth_code;
    private $pay_type;
    private $customer_id = '';

    private $refund_notify_url;
    private $notify_url;
    private $pay_model;
    private $sxfModel;
    private $path;
    private $bank = '14';
    public function _initialize()
    {
        parent::_initialize(); 
        $this->path = $_SERVER['DOCUMENT_ROOT'] . '/data/log/Banksxf/';
        $this->pay_model = M(Subtable::getSubTableName('pay'));
        $this->sxfModel = D('Pay/Merchants_upsxf');
        $this->notify_url = 'https://sy.youngport.com.cn/notify/sxf_notify.php';
//        $this->notify_url = 'sxf_notify.php';

        $this->refund_notify_url = 'https://sy.youngport.com.cn/Pay/Banksxf/refund_notify';
    }

    // 微信付款界面
    public function qr_weixipay()
    {
        //这里直接获得openid;
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            $id = I("id");
            $merchant = M("merchants_cate")->where("id=$id")->find();
            $openid = get_wx_openid();
            $this->getOffer($merchant, $openid);
            $this->assign('openid', $openid);
            $this->assign("merchant", $merchant);
            $this->assign('seller_id', I('id'));
            $this->display();
            die;
        }
    }

    // 支付宝付款界面
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

    // 或取微信会员id
    private function get_costomer_id($sub_openid, $merchant_id)
    {
        $this->customer_id = D("Api/ScreenMem")->add_member($sub_openid, $merchant_id);
    }

    // 微信扫码支付
    public function wx_pay()
    {
        // 先获取openid
        if (I("seller_id") == "") {
            $this->openid = get_wx_openid();
            $this->mode = 1;
            $this->cate_id = I("id");
            $cate_info = get_cate_info($this->cate_id);
            $this->checker_id = I("checker_id");
        } else {
            $this->openid = I('openid');
            $this->mode = 0;
            $this->cate_id = I('seller_id');
            $cate_info = get_cate_info($this->cate_id);
            $this->checker_id = $cate_info['checker_id'];
        }
        $this->remark = I('order_sn', getOrderNumber());
        if($this->pay_model->where(array('remark'=>  $this->remark))->find()){
            $this->alert_err("订单已存在");
        }
        $this->merchant_id = $cate_info['merchant_id'];
        $this->get_costomer_id($this->openid, $this->merchant_id);  // 获取会员ID
        $this->get_into(1);
        $this->price = I('price');
        $this->jmt_remark = I('memo','')?:I("jmt_remark",'');
        $this->subject = $cate_info['jianchen'];
        $this->paystyle_id = 1;

        $db_res = $this->add_db();
        if($db_res){
            // 请求服务器获取js支付参数
            $res_arr = $this->wx_jspay();
            // 判断返回结果
            if ($res_arr['code'] == '0000') {
                $body = $res_arr['pay_info'];
                $this->assign('body', $body);
                $this->assign('price', $this->price);
                $this->assign('openid', $this->openid);
                $this->assign('remark', $this->remark);
                $this->assign('mid', $this->merchant_id);
                $this->display("wx_pay");
            } else {
                $this->alert_err($res_arr['msg']);
            }
        } else {
            $this->alert_err();
        }
        die;
    }

    // 双屏扫码支付
    public function two_wx_pay()
    {
        $this->order_id = I("order_id");
        $this->mode = I("mode", 3);
        $this->openid = I("openid", '');
        if ($this->order_id) {
            if (!$this->openid) {
                $this->openid = get_wx_openid();
            }
            $this->cate_id = I("id");
            $cate_info = get_cate_info($this->cate_id);
            $order_info = M("order")->where(array('order_id'=>$this->order_id))->find();
            if ($this->pay_model->where(array('order_id'=>$this->order_id))->getField('id')) {
                $this->alert_err('订单已存在');
            }
            // 插入数据库的数据
            $this->checker_id = I("checker_id");
            $this->merchant_id = $cate_info['merchant_id'];
            $this->remark = $order_info['order_sn'];
            $this->price = $order_info['order_amount'];
            $this->jmt_remark = I('memo', '') ?: I("jmt_remark", '');
            $this->get_costomer_id($this->openid, $this->merchant_id);  // 获取会员ID
            $this->get_into(1);
            $this->subject = $cate_info['jianchen'];
            $this->paystyle_id = 1;
            $db_res = $this->add_db();
            if ($db_res) {
                // 请求服务器获取js支付参数
                $res_arr = $this->wx_jspay();
                // 判断返回结果
                if ($res_arr['code'] == '0000') {
                    $body = $res_arr['pay_info'];
                    $this->assign('body', $body);
                    $this->assign('price', $this->price);
                    $this->assign('openid', $this->openid);
                    $this->assign('remark', $this->remark);
                    $this->assign('mid', $this->merchant_id);
                    $this->display("wx_pay");
                } else {
                    $this->alert_err();
                }
            } else {
                $this->alert_err();
            }
        } else {
            $this->alert_err('订单号为空');
        }
        die;

    }

    public function payPag($res_arr)
    {
    }

    // 微信公众号支付请求
    private function wx_jspay()
    {
        $this->sxfModel->setParameters('ordNo', $this->remark);      // 商户订单号
        $this->sxfModel->setParameters('mno', $this->mno);        // 商户入驻返回的商户编号
        $this->sxfModel->setParameters('amt', $this->price);        // 订单总金额，单位为元，
        $this->sxfModel->setParameters('payType', 'JSAPI');        // JSAPI公众号 或 FWC--支付 宝服务窗
        $this->sxfModel->setParameters('subject', urlencode($this->subject));    // 订单标题
        $this->sxfModel->setParameters('subOpenid', $this->openid);
        $this->sxfModel->setParameters('subAppid', 'wx3fa82ee7deaa4a21');
        $this->sxfModel->setParameters('notifyUrl', urlencode($this->notify_url));  // 回调地址

        return $this->sxfModel->getPayInfo();
    }

    // 支付宝扫码支付
    public function qr_to_alipay()
    {
        $this->cate_id = I("seller_id");
        $this->checker_id = I("checker_id");
        $this->price = I("price");
        $this->jmt_remark = I('memo') ?: I("jmt_remark", '');
        $this->mode = 0;
        $this->remark = I('order_sn', getOrderNumber());
        if ($this->pay_model->where(array('remark' => $this->remark))->find()) {
            $this->alert_err("订单已存在");
        }
        $cate_info = get_cate_info($this->cate_id);
        $this->merchant_id = $cate_info['merchant_id'];
        $this->get_into(2);
        $this->subject = $cate_info['jianchen'];
        $this->paystyle_id = 2;
        $db_res = $this->add_db();
        if ($db_res) {
            // 请求服务器获取js支付参数
            $res_arr = $this->ali_jspay();
            if($res_arr['code'] == '0000'){
                header("Location:" . $res_arr['url']);
            } else {
                $this->alert_err($res_arr['msg']);
            }
        } else {
            $this->alert_err();
        }
    }

    // 支付宝双屏支付
    public function two_alipay()
    {
        $this->order_id = I("order_id");
        $this->mode = I("mode", 3);
        if ($this->order_id) {
            $this->cate_id = I("seller_id");
            $order_info = M("order")->where(array('order_id'=>$this->order_id))->find();
            if ($this->pay_model->where(array('order_id'=>$this->order_id))->getField('id')) {
                $this->alert_err('订单已存在');
            }
            $cate_info = get_cate_info($this->cate_id);
            // 插入数据库的数据
            $this->checker_id = I("checker_id");
            $this->merchant_id = $cate_info['merchant_id'];
            $this->remark = $order_info['order_sn'];
            $this->price = $order_info['order_amount'];
            $this->jmt_remark = I('memo', '') ?: I("jmt_remark", '');
            $this->get_into(2);
            $this->subject = $cate_info['jianchen'];
            $this->paystyle_id = 2;
            $db_res = $this->add_db();
            if ($db_res) {
                // 请求服务器获取js支付参数
                $res_arr = $this->ali_jspay();
                if($res_arr['code'] == '0000'){
                    header("Location:" . $res_arr['url']);
                } else {
                    $this->alert_err($res_arr['msg']);
                }
            } else {
                $this->alert_err();
            }
        } else {
            $this->alert_err('订单号为空');
        }
        die;
    }

    // 支付宝扫码支付请求
    private function ali_jspay()
    {
        $this->sxfModel->setParameters('mno', $this->mno);        // 商户入驻返回的商户编号
        $this->sxfModel->setParameters('ordNo', $this->remark);      // 商户订单号
        $this->sxfModel->setParameters('amt', $this->price);        // 订单总金额，单位为元，
        $this->sxfModel->setParameters('payType', $this->pay_type);    // 取值范围：WECHAT--微信扫码、ALIPAY--支付宝扫码、UNIONPAY--银联扫码
        $this->sxfModel->setParameters('subject', urlencode($this->subject));    // 订单标题
        $this->sxfModel->setParameters('notifyUrl', urlencode($this->notify_url));  // 回调地址

        return $this->sxfModel->getPayUrl();
    }

    private function add_db()
    {
        $add_data['status'] =  0;
        $add_data['mode'] =  $this->mode;
        $add_data['bank'] =  $this->bank;
        $add_data['price'] =  $this->price;
        $add_data['remark'] =  $this->remark;
        $add_data['cost_rate'] =  $this->rate;
        $add_data['cate_id'] =  $this->cate_id;
        $add_data['subject'] =  $this->subject;
        $add_data['merchant_id'] =  $this->merchant_id;
        $add_data['order_id'] =  $this->order_id?:0;
        $add_data['jmt_remark'] =  $this->jmt_remark;
        $add_data['checker_id'] =  $this->checker_id;
        $add_data['customer_id'] =  $this->customer_id;
        $add_data['paystyle_id'] =  $this->paystyle_id;
        $add_data['add_time'] =  time();
        $add_data['bill_date'] =  date('Ymd');
        $add_data['phone_info'] =  '';

        return $this->pay_model->add($add_data);
    }

    // 微信付款码支付
    public function wx_micropay($mch_id, $price, $auth_code, $checker_id, $jmt_remark, $order_sn, $mode)
    {
        $this->merchant_id = $mch_id;
        $this->checker_id = $checker_id;
        $this->jmt_remark = $jmt_remark;
        $this->auth_code = $auth_code;
        $this->price = $price;
        $this->mode = $mode ?: 2;
        $this->paystyle_id = 1;
        $this->subject = "支付{$price}元";
        $this->get_into(1);
        if ($order_sn) {
            $this->remark = $order_sn;
        } else {
            $this->remark = getOrderNumber();
        }
        //插入数据库的数据
        $db_res = $this->add_db();
        if ($db_res) {
            return $this->send_micropay();
        } else {
            return array("code" => "error", "msg" => "入库失败");
        }
    }

    // 支付宝付款码支付
    public function ali_micropay($mch_id, $price, $auth_code, $checker_id, $jmt_remark, $order_sn, $mode)
    {
        $this->merchant_id = $mch_id;
        $this->checker_id = $checker_id;
        $this->jmt_remark = $jmt_remark;
        $this->auth_code = $auth_code;
        $this->price = $price;
        $this->mode = $mode ?: 2;
        $this->paystyle_id = 2;
        $this->subject = "支付{$price}元";
        $this->get_into(2);
        if ($order_sn) {
            $this->remark = $order_sn;
        } else {
            $this->remark = getOrderNumber();
        }
        //插入数据库的数据
        $db_res = $this->add_db();
        if ($db_res) {
            return $this->send_micropay();
        } else {
            return array("code" => "error", "msg" => "失败", "data" => '请重试');
        }
    }

    /**
     * 获取进件信息，如商户编号，费率等
     * @param $paystyle  支付方式 1=微信，=支付宝
     */
    private function get_into($paystyle)
    {
        $info = $this->sxfModel->field('mno,wx_rate,ali_rate')->where("merchant_id=$this->merchant_id")->find();
        if(!$info){
            $this->alert_err('商户未进件！');
        }
        if($paystyle == '1') {
            $this->rate = $info['wx_rate'];
            $this->pay_type = 'WECHAT';
        }
        if($paystyle == '2') {
            $this->rate = $info['ali_rate'];
            $this->pay_type = 'ALIPAY';
        }
        $this->mno = $info['mno'];
    }

    private function send_micropay()
    {
        $this->sxfModel->setParameters('mno', $this->mno);        // 商户入驻返回的商户编号
        $this->sxfModel->setParameters('ordNo', $this->remark);      // 商户订单号
        $this->sxfModel->setParameters('authCode', $this->auth_code);   // 通过扫码枪
        $this->sxfModel->setParameters('amt', $this->price);        // 订单总金额，单位为元，
        $this->sxfModel->setParameters('payType', $this->pay_type);    // 取值范围：WECHAT--微信扫码、ALIPAY--支付宝扫码、UNIONPAY--银联扫码
        $this->sxfModel->setParameters('subject', urlencode($this->subject));    // 订单标题
        $this->sxfModel->setParameters('notifyUrl', urlencode($this->notify_url));  // 回调地址

        $res_arr = $this->sxfModel->micropay();

        if($res_arr['code'] == '0000'){
            $this->pay_model
                ->where(array("remark" => $this->remark))
                ->save(array("status" => "1", "paytime" => time(), 'transId' => $res_arr['transId']));
            return array("code" => "success", "msg" => "支付成功");
        } else {
            return array("code" => "error", "msg" => $res_arr['msg']);
        }
    }

    // 支付成功回到
    public function notify()
    {
        header("Content-type:application/json;charset=utf-8");
        $json_str = file_get_contents('php://input', 'r');
        $notifyData = json_decode($json_str);
        $order_sn = $notifyData->ordNo;
        $transId = $notifyData->uuid;
        $buyerId = $notifyData->buyerId;
        $orderData = $this->pay_model->where(array('remark' => $order_sn))->find();
        if ($orderData) {
            if ($orderData['status'] == 0) {
                $save['transId'] = $transId;
                $save['paytime'] = time();
                $save['status'] = 1;
                $save['buyers_account'] = $buyerId;
                if (bccomp($orderData['price']*100, $notifyData->amt*100, 3) === 0) {
                    get_date_dir($this->path,'notify','支付成功', $json_str);
                    $this->pay_model->where(array('id' => $orderData['id']))->save($save);
                    if ($orderData['paystyle_id'] == '1' && $orderData['order_id'] != 0) {
                        A('Barcode')->cardOff($orderData['order_id']);
                    }
                    A("App/PushMsg")->push_pay_message($order_sn);
                    exit('{"code":"success","msg":"成功"}');
                } else {
                    get_date_dir($this->path,'notify','金额异常', $json_str);
                    exit('{"code":"error","msg":""}');
                }
            } else if ($orderData['status'] == 1) {
                get_date_dir($this->path,'notify','重复通知', $json_str);
                exit('{"code":"success","msg":"成功"}');
            } else {
                get_date_dir($this->path,'notify','状态异常', $json_str);
                exit('{"code":"error","msg":""}');
            }
        } else {
            get_date_dir($this->path,'notify','订单不存在', $json_str);
            exit('{"code":"error","msg":""}');
        }
    }

    // 退款回调
    public function refund_notify()
    {
        $json_str = file_get_contents('php://input', 'r');
        get_date_dir($this->path,'refund_notify','数据', $json_str);
        get_date_dir($this->path,'refund_notify','数据1', json_encode($_REQUEST));
    }

    // 进件回调
    public function mer_notify()
    {
        $json_str = file_get_contents('php://input', 'r');
        file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/data/log/Banksxf/'.date('Y-m') . "/into_notify.log",
            date("Y-m-d H:i:s") . ':'. $json_str . PHP_EOL . PHP_EOL, FILE_APPEND | LOCK_EX);
        $data = json_decode($json_str);
        switch ($data->msg) {
            case '进件成功':
                $re = M('merchants_upsxf')->where(array('task_code'=>$data->taskCode))->save(array('mno'=>$data->mno,'status'=>2));
                break;
            case '秒审驳回':
                $re = M('merchants_upsxf')->where(array('task_code'=>$data->taskCode))->save(array('status'=>3));
                break;
            default:
                $re = false;
                break;
        }
        if($re !== false){
            exit('{"code":"success","msg":"成功"}');
        } else exit('{"code":"error","msg":""}');
    }

    /**
     * 退款
     * @param $remark   系统订单号
     * @param $price    退款金额
     * @return array
     */
    public function pay_back($remark, $price)
    {
        $pay = $this->pay_model->field('merchant_id,transId,price')->where(array("remark" => $remark))->find();
        if (!$pay) {
            return array("code" => 'error', "msg" => "该订单不存在");
        }
        if ($pay['status'] == "2") {
            return array("code" => 'error', "msg" => "不能重复退款");
        }
        $this->mno = $this->sxfModel->where("merchant_id=$pay[merchant_id]")->getField('mno');
        if (!$this->mno) {
            return array("code" => 'error', "msg" => "商户不存在");
        }
        if ($pay['price'] < $price) {
            return array("code" => 'error', "msg" => "退款金额错误");
        }
        $result = $this->refund($remark, $price, $pay['transId']);
        if($result['code'] == '0000'){
            return array("code" => "success", "msg" => "成功", "data" => "退款成功");
        } else {
            return array("code" => "error", "msg" => "error", "data" => "退款失败");
        }
    }

    /**
     * 退款请求
     * @param $remark   系统订单号
     * @param $price    订单退款金额
     * @param $tran_id  第三方（随行付）订单号
     * @return mixed
     */
    private function refund($remark, $price, $tran_id)
    {
        $this->sxfModel->setNull();
        $this->sxfModel->setParameters('ordNo', getOrderNumber());
        $this->sxfModel->setParameters('mno', $this->mno);
        $this->sxfModel->setParameters('origOrderNo', $remark);
        $this->sxfModel->setParameters('origUuid', $tran_id);
        $this->sxfModel->setParameters('amt', $price);
        $this->sxfModel->setParameters('notifyUrl', urlencode($this->refund_notify_url));

        return $this->sxfModel->refund();
    }

    /**
     * 查询订单信息
     * @param $remark   系统订单号
     * @return mixed
     */
    public function query($remark)
    {
        $pay = $this->pay_model->field('merchant_id')->where(array("remark" => $remark))->find();
        $mno = $this->sxfModel->where("merchant_id=$pay[merchant_id]")->getField('mno');
        $this->sxfModel->setNull();
        $this->sxfModel->setParameters('ordNo', $remark);
        $this->sxfModel->setParameters('mno', $mno);

        return $this->sxfModel->query();
    }

    /**
     * 错误信息提示页面
     * @param string $msg 提示信息
     */
    public function alert_err($msg = '网络错误，请稍后再试')
    {
        $this->assign('err_msg', "$msg");
        $this->display(":error");
        exit;
    }
}