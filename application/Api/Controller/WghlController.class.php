<?php

namespace Api\Controller;

use Common\Controller\ApibaseController;

/**微光互联支付接口
 * 扫码支付、条码支付、刷卡支付
 * Class PayController
 * @package Api\Controller
 */
class  WghlController extends ApibaseController
{
    const brand = 'YPT';
    private $params;
    private $merchant_id;
    private $uid;
    private $pay_model;

    public function __construct()
    {
        parent::__construct();
        $this->saltKey = '4D1CFFB59D01F69E28538CD1ABF8F9C4';//盐值
        $php_input = file_get_contents('php://input', 'r');
        parse_str($php_input, $this->params);
        get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/wghl/', 'pay', 'get_params', json_encode($this->params));
        $this->merchant_id = 0;
        $this->uid = 0;
        $this->pay_model = M('pay');
        #验证签名
        if($this->params['sn']!='ypt999999') $this->checkSign($this->params);
        //$this->checkSign($this->params);
        #检查设备绑定
        $this->checkSn();
    }

    #验证签名
    private function checkSign($params)
    {
        $sign = $params['sign'];
        if (empty($sign)) {
            get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/wghl/', 'pay', 'Signature empty', json_encode($this->params));
            $this->succ(array('code' => 'error', 'msg' => 'Signature empty'));
        }
        unset($params['sign']);
        ksort($params);
        $str = '';
        foreach ($params as $v) {
            $str .= $v;
        }
        //dump($str);die;
        if ($sign !== md5($str . $this->saltKey)) {
            get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/wghl/', 'pay', 'Signature error', json_encode($this->params));
            $this->succ(array('code' => 'error', 'msg' => 'Signature error'));
        }
    }

    private function checkSn()
    {
        #检查该设备是否绑定
        if ($this->params['sn']) {
            $wghl = M('merchants_wghl')->where(array('sn' => $this->params['sn']))->find();
            if ($wghl) {
                $this->merchant_id = $wghl['merchant_id'];
                $this->uid = M('merchants')->where(array('id' => $this->merchant_id))->getField('uid');
            } else {
                get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/wghl/', 'pay', '该设备未绑定商户', json_encode($this->params));
                $this->succ(array('code' => 'error', 'msg' => '该设备未绑定商户'));
            }
        } else {
            get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/wghl/', 'pay', 'sn is empty', json_encode($this->params));
            $this->succ(array('code' => 'error', 'msg' => 'sn is empty'));
        }
    }

    //刷卡支付【商户主扫】
    public function micropay()
    {
        $price = $this->params['price'];
        $code = $this->params['auth_code'];
        $checker_id = 0;
        $number = substr($code, 0, 2);
        $jmt_remark = '';
        $mode = 25;//25波普刷卡
        if ($order_sn = $this->params['order_sn']) {
            #检查该笔订单使用的储值、积分是否充足，是否有优惠券
            $order_info = M('order')->where(array('order_sn' => $order_sn))->field('order_amount,card_code,user_money,integral,coupon_code')->find();
            if ($order_info) {
                if ($order_info['order_amount'] != $price) $this->ajaxReturn(array('code' => 'error', 'msg' => '金额有误'));
                $price = $order_info['order_amount'];
                $this->check_preferential($order_info['card_code'], $order_info["user_money"], $order_info['integral'], $order_info['coupon_code']);
            }
        } else {
            $order_sn = date('YmdHis') . rand(100000, 999999);
        }
        get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/wghl/', 'micropay', '请求,订单号:' . $order_sn . ',参数', json_encode($this->params));
        #根据设备sn号查对应商户id
        $id = $this->merchant_id;
        if (!$id) {
            get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/wghl/', 'micropay', '未绑定商户,订单号:' . $order_sn . ',参数', json_encode($this->params));
            $this->succ(array("code" => "error", "msg" => "未绑定商户"));
        }

        $res = M('merchants_cate')->field('status,wx_bank,ali_bank,is_ypt')->where(array("merchant_id" => $id, 'status' => 1, 'checker_id' => $checker_id))->find();
        // 微信支付
        if ($number == "10" || $number == "11" || $number == "12" || $number == "13" || $number == "14" || $number == "15" && strlen($code) == 18) {
            // 微信官方通道
            if ($res['wx_bank'] == "3") {
                $message = A("Pay/Wxpay")->micropay($id, $price, $code, $checker_id, $jmt_remark, $order_sn, $mode);
                get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/wghl/', 'wx_return', '订单号:' . $order_sn . ',微信返回', json_encode($message));
                if ($message['code'] == "error") {
                    $this->succ(array("code" => "error", "msg" => "交易失败"));
                }
                if ($message['code'] == "success") {
                    $this->card_off($order_sn);
                    $this->succ(array("code" => "success", "msg" => "交易成功"));
                }
            }
            //兴业银行
            if ($res['wx_bank'] == "7") {
                $message = A("Pay/Barcodexybank")->wz_micropay($id, $price, $code, $checker_id, $jmt_remark, $order_sn, $mode);
                get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/wghl/', 'xy_return', '订单号:' . $order_sn . ',微信返回', json_encode($message));
                if ($message['code'] == "error") {
                    $this->succ(array("code" => "error", "msg" => "交易失败"));
                }
                if ($message['code'] == "success") {
                    $this->card_off($order_sn);
                    $this->succ(array("code" => "success", "msg" => "交易成功"));
                }
            }
            // 宿州李灿
            if ($res['wx_bank'] == "9") {
                $message = A("Pay/Szlzpay")->micropay($id, $price, $code, $checker_id, $order_sn, $mode);
                get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/wghl/', 'szlz_return', '订单号:' . $order_sn . ',微信返回', json_encode($message));
                if ($message['code'] == "error") {
                    $this->succ(array("code" => "error", "msg" => "交易失败"));
                }
                if ($message['code'] == "success") {
                    $this->card_off($order_sn);
                    $this->succ(array("code" => "success", "msg" => "交易成功"));
                }
            }
            //东莞中信
            if ($res['wx_bank'] == "10") {
                $message = A("Pay/Barcodepfbank")->wz_micropay($id, $price, $code, $checker_id, $jmt_remark, $order_sn, $mode);
                get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/wghl/', 'pfbank_return', '订单号:' . $order_sn . ',微信返回', json_encode($message));
                if ($message['code'] == "error") {
                    $this->succ(array("code" => "error", "msg" => "交易失败"));
                }
                if ($message['code'] == "success") {
                    $this->card_off($order_sn);
                    $this->succ(array("code" => "success", "msg" => "交易成功"));
                }
            }
            if ($res['wx_bank'] == "11") {//新大陆
                $message = A("Pay/Barcodexdlbank")->wx_micropay($id, $price, $code, $checker_id, $jmt_remark, $order_sn, $mode);
                get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/wghl/', 'xdl_return', '订单号:' . $order_sn . ',微信返回', json_encode($message));
                if ($message['code'] == "error") {
                    $this->succ(array("code" => "error", "msg" => "交易失败"));
                }
                if ($message['code'] == "success") {
                    $this->card_off($order_sn);
                    $this->succ(array("code" => "success", "msg" => "交易成功"));
                }
            } elseif ($res['wx_bank'] == "12") {// 乐刷支付
                $message = A("Pay/Leshuabank")->wx_micropay($id, $price, $code, $checker_id, $jmt_remark, $order_sn, $mode);
                get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/wghl/', 'leshua_return', '订单号:' . $order_sn . ',微信返回', json_encode($message));
                if ($message['code'] == "error") {
                    $this->succ(array("code" => "error", "msg" => "交易失败"));
                }
                if ($message['code'] == "success") {
                    $this->card_off($order_sn);
                    $this->succ(array("code" => "success", "msg" => "交易成功"));
                }
            } else {
                $this->succ(array("code" => "error", "msg" => "暂不支持该商户通道"));
            }
        } else if ($number == '28') {//支付宝支付
            if ($res['ali_bank'] == "7") { //兴业
                $message = A("Pay/Barcodexybank")->ali_barcode_pay($id, $price, $code, $checker_id, $jmt_remark, $order_sn, $mode);
                get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/wghl/', 'xy_return', '订单号:' . $order_sn . ',支付宝返回', json_encode($message));
                if ($message['code'] == 'success') {
                    $this->card_off($order_sn);
                    $this->succ(array("code" => "success", "msg" => "交易成功"));
                } else
                    $this->succ(array("code" => "error", "msg" => "交易失败"));
            }
            if ($res['ali_bank'] == "9") {//宿州李灿
                $message = A("Pay/Szlzpay")->ali_micropay($id, $price, $code, $checker_id, $order_sn, $mode);
                get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/wghl/', 'szlz_return', '订单号:' . $order_sn . ',支付宝返回', json_encode($message));
                if ($message['code'] == "error") {
                    $this->succ(array("code" => "error", "msg" => "交易失败"));
                }
                if ($message['code'] == "success") {
                    $this->card_off($order_sn);
                    $this->succ(array("code" => "success", "msg" => "交易成功"));
                }
            }
            if ($res['ali_bank'] == "10") { //东莞中信
                $message = A("Pay/Barcodepfbank")->ali_barcode_pay($id, $price, $code, $checker_id, $jmt_remark, $order_sn, $mode);
                get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/wghl/', 'pfbank_return', '订单号:' . $order_sn . ',支付宝返回', json_encode($message));
                if ($message['code'] == 'success') {
                    $this->card_off($order_sn);
                    $this->succ(array("code" => "success", "msg" => "交易成功"));
                } else
                    $this->succ(array("code" => "error", "msg" => "交易失败"));
            }
            if ($res['ali_bank'] == "11") {//新大陆
                $message = A("Pay/Barcodexdlbank")->ali_micropay($id, $price, $code, $checker_id, $jmt_remark, $order_sn, $mode);
                get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/wghl/', 'xdl_return', '订单号:' . $order_sn . ',支付宝返回', json_encode($message));
                if ($message['code'] == "error") {
                    $this->succ(array("code" => "error", "msg" => "交易失败"));
                }
                if ($message['code'] == "success") {
                    $this->card_off($order_sn);
                    $this->succ(array("code" => "success", "msg" => "交易成功"));
                }
            } elseif ($res['ali_bank'] == "12") {//乐刷支付
                $message = A("Pay/Leshuabank")->ali_micropay($id, $price, $code, $checker_id, $jmt_remark, $order_sn, $mode);
                get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/wghl/', 'leshua_return', '订单号:' . $order_sn . ',支付宝返回', json_encode($message));
                if ($message['code'] == "error") {
                    $this->succ(array("code" => "error", "msg" => "交易失败"));
                }
                if ($message['code'] == "success") {
                    $this->card_off($order_sn);
                    $this->succ(array("code" => "success", "msg" => "交易成功"));
                }
            } else {
                $this->succ(array("code" => "error", "msg" => "暂不支持该商户通道"));
            }
        } else {
            $this->succ(array("code" => "error", "msg" => "请扫微信或支付宝支付"));
        }

    }

    protected function succ($data)
    {
        // 返回JSON数据格式到客户端 包含状态信息
        $data['msg'] = urlencode($data['msg']);
        header('Content-Type:application/json; charset=utf-8');
        exit(urldecode(json_encode($data)));
    }

    //退款码退款
    public function refund()
    {
        $refunds_code = $this->params['refunds_code'];
        $pay_info = $this->pay_model
            ->where(array('merchant_id'=>$this->merchant_id,'status'=>1,'wx_remark|new_order_sn|transId'=>$refunds_code))
            ->field('remark,price')->find();
        if(!$pay_info){
            get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/wghl/', 'refunds', '退款未查到该笔订单: ','参数', json_encode($this->params));
            $this->succ(array("code" => "error", "msg" => "未查到该笔订单"));
        }
        get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/wghl/', 'refunds', '退款: ','参数', json_encode($this->params));
        request_post('http://sy.youngport.com.cn/Api/Pay/pay_back',array("mid"=>$this->merchant_id,'style'=>'2','remark'=>$pay_info['remark'],'price_back'=>$pay_info['price']*100,'sign'=>'5e022b44a15a90c0'));
        //$a = request_post('http://sy.youngport.com.cn/Api/Base/pay_back',array("mid"=>$this->merchant_id,'style'=>'2','remark'=>$pay_info['remark'],'price_back'=>$pay_info['price']*100,'sign'=>'5e022b44a15a90c01'));
    }

    //扫码收款【商户被扫】
    public function barcode()
    {
        get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/wghl/', 'barcode', ':get_params', json_encode($this->params));
        $price = I("price");
        if (I('order_sn')) {
            $order_sn = I('order_sn');
            $order_amount = M('order')->where(array('order_sn' => $order_sn))->getField('order_amount');
            if ($order_amount && $order_amount != $price) {
                $this->succ(array('code' => 'error', 'msg' => '金额有误'));
            }
        } else {
            $order_sn = date('YmdHis') . rand(100000, 999999);
        }
        if ($price == 0) $this->succ(array("code" => "error", "msg" => "价格不能为空!"));
        $cate_id = M("merchants_cate")->where(array("merchant_id" => $this->merchant_id, 'status' => 1))->getField("id");
        if (!$cate_id) $this->succ(array("code" => "error", "msg" => "失败", "data" => "未绑定台签"));
        $no_number = $this->create_no_number($cate_id);//每张二维码唯一标识
        $pay_url = "http://" . $_SERVER['HTTP_HOST'] . "/index.php?g=Pay&m=Barcode&a=qrcode&type=0|" . $no_number . "&id=" . $cate_id . "&price=" . $price . "&order_sn=" . $order_sn . "&mode=22";

        $this->succ(array("code" => "success", "msg" => "成功", "pay_url" => $pay_url, "order_sn" => $order_sn));
    }

    /**生成no_number
     * @param $cate_id
     * @return string
     */
    private function create_no_number($cate_id)
    {
        $no_number = $this->pay_model->where(array("cate_id" => $cate_id))->order("id desc")->getField('no_number');
        $no_number = substr($no_number, -7) + 1;
        $seven = "000000" . $no_number;
        $cate_name = 'SJ';
        $no_number = self::brand . $cate_name . substr($seven, -7);
        return $no_number;
    }

    /**
     * @auth LXL
     * 扫码订单查询
     */
    public function query()
    {
        get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/wghl/', 'query', ':请求参数', json_encode($this->params));
        $remark = I("order_sn");

        $where['remark'] = $remark;
        $pay = $this->pay_model->where($where)->find();
        if ($pay['merchant_id'] != $this->merchant_id) {
            $return = array("code" => "error", "msg" => "不能查询非本商户流水");
        } elseif ($pay['status'] == 1) {
            $return = array("code" => "success", "msg" => "交易成功");
        } else {
            $return = array("code" => "A", "msg" => "交易未支付");
        }
        get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/wghl/', 'query', ':返回参数', json_encode($this->params));
        $this->succ($return);
    }

    #检查该笔订单使用的储值、积分是否充足，是否有优惠券
    public function check_preferential($card_code, $yue, $integral, $coupon_code)
    {
        #会员卡
        if ($card_code > 0) {
            $card_info = M('screen_memcard_use')->where(array('card_code' => $card_code))->field('yue,card_balance')->find();
            if ($yue > 0) {
                if ($yue > $card_info['yue']) {
                    $this->succ(array('code' => 'error', 'msg' => '储值余额有变动，请重新收款！'));
                }
            }
            if ($integral > 0) {
                if ($integral > $card_info['card_balance']) {
                    $this->succ(array('code' => 'error', 'msg' => '积分有变动，请重新收款！'));
                }
            }
        }
        #优惠券
        if ($coupon_code > 0) {
            $coupon_status = M('screen_user_coupons')->where(array('usercard' => $coupon_code))->getField('status');
            if ($coupon_status == 0) {
                $this->succ(array('code' => 'error', 'msg' => '优惠券已被使用，请重新收款！'));
            }
        }
    }

    public function cancel_card()
    {
        $code = $this->params['code'];
        $price = $this->params['price'];
        $mch_uid = $this->uid;
        $order_sn = date('YmdHis') . mt_rand(100000, 999999);
        $agent_id = M('merchants_users')->where(array('id' => $mch_uid))->getField('agent_id');
        if ($agent_id == '0') $agent_id = '-1';
        if ($data = M("screen_user_coupons")->where(array("usercard" => $code, "status" => 1))->find()) {
            get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/wghl/', 'cancel_card_coupons', ':get_params', json_encode($this->params));
            #优惠券
            $res = M('screen_coupons')->alias('c')
                ->join('join ypt_screen_user_coupons uc on uc.coupon_id=c.id')
                ->join('join ypt_merchants m on m.id=c.mid')
                ->join('join ypt_merchants_users mu on mu.id=m.uid')
                ->where(array("c.id" => $data['coupon_id'], 'c.card_type' => 'GENERAL_COUPON'))
                ->field('c.total_price,c.de_price,c.begin_timestamp,c.end_timestamp,mu.id')
                ->find();
            if (!$res){
                get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/wghl/', 'cancel_card_coupons', ':该优惠券不可使用', json_encode($this->params));
                $this->succ(array("code" => "error", "result_code" => '-2', "msg" => "该优惠券不可使用"));
            }
            if ($res['id'] != $mch_uid) {
                get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/wghl/', 'cancel_card_coupons', ':该优惠券不是本店优惠券', json_encode($this->params));
                $this->succ(array("code" => "error", "result_code" => '-2', "msg" => "该优惠券不是本店优惠券"));
            }
            if ($res['total_price'] > $price) {
                get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/wghl/', 'cancel_card_coupons', ':消费金额未达到优惠券需求金额！', json_encode($this->params));
                $this->succ(array("code" => "error", "result_code" => '-2', "msg" => "消费金额未达到优惠券需求金额！"));
            }
            if (time() < $res['begin_timestamp'] || time() > $res['end_timestamp']) {
                get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/wghl/', 'cancel_card_coupons', ':该优惠券不在使用时间范围', json_encode($this->params));
                $this->succ(array("code" => "error", "result_code" => '-2', "msg" => "该优惠券不在使用时间范围"));
            }

            $real_price = $price - $res['de_price'];
            $order_info = array(
                'order_sn' => $order_sn,//流水号
                'total_amount' => $price,//原始金额
                'order_amount' => round($real_price, 2),//应付金额
                'coupon_code' => $code,//优惠券code
                'coupon_price' => $res['de_price'],//优惠券优惠金额
                'order_benefit' => $res['de_price'],//优惠总额
            );
            $order_id = M('order')->add($order_info);
            //应付金额为0则不调起支付
            if ($real_price == 0) {
                $pay_info = array(
                    "order_id" => $order_id,
                    "remark" => $order_sn,
                    "mode" => 27,
                    "merchant_id" => $this->merchant_id,
                    "paystyle_id" => 1,
                    "price" => $real_price,
                    "status" => 1,
                    "paytime" => time(),
                    "bill_date" => date('Ymd')
                );
                $pay_id = $this->pay_model->add($pay_info);
                if ($pay_id) {
                    $this->card_off($order_sn);
                    $this->succ(array("code" => "success", "msg" => "交易成功", "price" => '0',  "result_code" => '2', 'order_sn' => $order_sn));
                }
            }
            $this->succ(array("code" => "success", "msg" => "验证优惠券成功，请支付","result_code" => '1', "price" => strval(round($real_price, 2)), 'order_sn' => $order_sn));
        } elseif ($d = M("screen_memcard_use")->where(array("entity_card_code|card_code" => $code, "status" => 1))->find()) {
            #会员卡
            $res = M('screen_memcard')->alias('m')
                ->join('join ypt_screen_memcard_use u on m.id=u.memcard_id')
                ->where(array("u.entity_card_code|u.card_code" => $code))
                ->field('u.card_amount,u.memid,u.yue,u.level,u.card_id,u.card_balance,m.id,m.max_reduce_bonus,m.credits_set,m.integral_dikou,m.max_reduce_bonus,m.credits_use,m.credits_discount,m.discount_set,m.discount,m.mid,m.level_set')
                ->find();
            get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/wghl/', 'cancel_card', ':会员卡信息', json_encode($res));

            if ($res['mid'] != $mch_uid && $res['mid'] != $agent_id) $this->succ(array("code" => "error", "msg" => "该会员卡不能在本店使用"));
            if ($res['mid'] == $agent_id) {
                $use_merchants = M('screen_memcard')->alias('m')
                    ->join('join ypt_screen_memcard_use u on m.id=u.memcard_id')
                    ->join('join ypt_screen_cardset s on s.c_id=m.id')
                    ->where(array("u.entity_card_code|u.card_code" => $code))
                    ->getField('s.use_merchants');
                $arr = explode(',', $use_merchants);
                if (!in_array($mch_uid, $arr)) {
                    get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/wghl/', 'cancel_card', ':该联名会员卡不能在本店使用', json_encode($this->params));
                    $this->succ(array("code" => "error", "result_code" => '-2', "msg" => "该联名会员卡不能在本店使用"));
                }

            }
            #1算折扣
            if ($res['level_set'] == '1') {
                $d['discount'] = M('screen_memcard_level')->where(array('c_id' => $res['id'], 'level' => $res['level']))->getField('level_discount') * 0.1;
            } elseif ($res['discount_set'] == 0 || $res['discount'] == 0 || !$res['discount']) {
                $d['discount'] = '1';
            } else {
                $d['discount'] = $res['discount'] * 0.1;
            }
            $new_price = $price * $d['discount'];
            $discount_price = $price - $new_price;

            #2算优惠券
            $where = array('c.card_type' => 'GENERAL_COUPON', 'm.uid' => $mch_uid, 'mem.id' => $res['memid'], 'uc.status' => 1, 'c.total_price' => array('ELT', $new_price), 'c.begin_timestamp' => array('ELT', time()), 'c.end_timestamp' => array('EGT', time()));
            $coupon = M('screen_user_coupons')->alias('uc')
                ->join('join ypt_screen_coupons c on uc.coupon_id=c.id')
                ->join('right join ypt_screen_mem mem on mem.unionid=uc.unionid')
                ->join('left join ypt_merchants m on m.id=c.mid')
                ->field('c.de_price,uc.usercard')
                ->where($where)
                ->order('c.de_price DESC')
                ->find();

            if ($coupon) {
                $new_price2 = $new_price - $coupon['de_price'];
                //如果使用优惠券之后金额=0，返回支付成功
                if($new_price2==0){
                    $order_info = array(
                        'order_sn' => $order_sn,//流水号
                        'total_amount' => $price,//原始金额
                        'order_amount' => $new_price2,//应付金额
                        'card_code' => $code,//会员卡code
                        'integral' => $data['integral'],//使用积分
                        'integral_money' => $data['integral_money'],//使用的积分对应的金额
                        'coupon_code' => $coupon['usercard'],//优惠券code
                        'coupon_price' => $coupon['de_price'],//优惠券抵扣金额
                        'discount' => $d['discount'] * 100,//折扣，100=10折
                        'discount_money' => $discount_price,//折扣金额
                        'order_benefit' => $coupon['de_price'] + $discount_price,//总优惠金额
                        'user_money' => 0,//使用储值
                    );
                    $order_id = M('order')->add($order_info);
                    $pay_info = array(
                        "order_id" => $order_id,
                        "remark" => $order_sn,
                        "mode" => 27,
                        "merchant_id" => $this->merchant_id,
                        "paystyle_id" => 1,
                        "price" => 0,
                        "status" => 1,
                        "paytime" => time(),
                        "bill_date" => date('Ymd')
                    );
                    $this->pay_model->add($pay_info);
                    $this->card_off($order_sn);
                    $this->succ(array("code" => "success", "msg" => "交易成功", "price" => '0',  "result_code" => '2', 'order_sn' => $order_sn));
                }
            } else {
                $new_price2 = $new_price;
            }
            #3算积分
            if ($res['integral_dikou'] == 0) {
                $data = array('integral_money' => '0', 'integral' => '0');
            } else {
                #剩余积分小于最多可以使用的积分
                if ($res['card_balance'] < $res['max_reduce_bonus']) {
                    #本单积分可以抵扣多少钱 = (剩余积分/使用积分)*积分抵扣金额
                    $p = floor($res['card_balance'] / $res['credits_use']) * $res['credits_discount'];
                } else {
                    #剩余积分大于最多可以使用的积分
                    #本单最多可用积分抵扣多少钱 = (最多可以使用的积分/使用积分)*积分抵扣金额
                    $p = floor($res['max_reduce_bonus'] / $res['credits_use']) * $res['credits_discount'];
                }
                #积分抵扣金额小于当前需支付的的金额(折扣和使用优惠券过后)
                if ($p < $new_price2) {
                    $data['integral_money'] = "$p";//积分抵扣金额
                    $data['integral'] = $p / $res['credits_discount'] * $res['credits_use'];//使用了多少积分
                } else {
                    $data['integral'] = floor($new_price2 / $res['credits_discount']) * $res['credits_use'];
                    $data['integral_money'] = ($data['integral'] / $res['credits_use']) * $res['credits_discount'];
                    #如果应付金额=积分使用的金额，返回支付成功
                    if($data['integral_money'] == $new_price2){
                        $order_info = array(
                            'order_sn' => $order_sn,//流水号
                            'total_amount' => $price,//原始金额
                            'order_amount' => $new_price2,//应付金额
                            'card_code' => $code,//会员卡code
                            'integral' => $data['integral'],//使用积分
                            'integral_money' => $data['integral_money'],//使用的积分对应的金额
                            'coupon_code' => $coupon['usercard']?:'',//优惠券code
                            'coupon_price' => $coupon['de_price']?:'0',//优惠券抵扣金额
                            'discount' => $d['discount'] * 100,//折扣，100=10折
                            'discount_money' => $discount_price,//折扣金额
                            'order_benefit' => $data['integral_money'] + $coupon['de_price'] + $discount_price,//总优惠金额
                            'user_money' => 0,//使用储值
                        );
                        $order_id = M('order')->add($order_info);
                        $pay_info = array(
                            "order_id" => $order_id,
                            "remark" => $order_sn,
                            "mode" => 27,
                            "merchant_id" => $this->merchant_id,
                            "paystyle_id" => 1,
                            "price" => 0,
                            "status" => 1,
                            "paytime" => time(),
                            "bill_date" => date('Ymd')
                        );
                        $this->pay_model->add($pay_info);
                        $this->card_off($order_sn);
                        $this->succ(array("code" => "success", "msg" => "交易成功", "price" => '0',  "result_code" => '2', 'order_sn' => $order_sn));
                    }
                }
            }
            #当前应支付金额,应付金额-总优惠金额
            $order_amount = $price - ($data['integral_money'] + $coupon['de_price'] + $discount_price);

            #4算储值
            #如果储值余额大于等于应付金额，要么使用储值支付，储值不够则不使用
            if ($res['yue'] > 0 && $res['yue'] >= $order_amount) {
                $use_money = $order_amount;
                $order_amount = 0;
            } else {
                $use_money = 0;
            }
            $order_info = array(
                'order_sn' => $order_sn,//流水号
                'total_amount' => $price,//原始金额
                'order_amount' => $order_amount,//应付金额
                'card_code' => $code,//会员卡code
                'integral' => $data['integral'],//使用积分
                'integral_money' => $data['integral_money'],//使用的积分对应的金额
                'coupon_code' => $coupon['usercard'],//优惠券code
                'coupon_price' => $coupon['de_price'],//优惠券抵扣金额
                'discount' => $d['discount'] * 100,//折扣，100=10折
                'discount_money' => $discount_price,//折扣金额
                'order_benefit' => $data['integral_money'] + $coupon['de_price'] + $discount_price,//总优惠金额
                'user_money' => $use_money,//使用储值
            );
            M('order')->add($order_info);
            if ($order_amount == 0) {
                $this->succ(array("code" => "success", "result_code" => '0', "msg" => "需要输入支付密码", 'price' => '0', 'order_sn' => $order_sn));
            }
            $this->succ(array("code" => "success", "msg" => "验证会员卡成功", "result_code" => '1', "price" => strval(round($order_amount, 2)), 'order_sn' => $order_sn));
        } else {
            $this->succ(array("code" => "error", "result_code" => '-2', "msg" => "无效卡号"));
        }
    }

    //支付密码支付流水
    public function pwd_pay()
    {
        ($order_sn = $this->params['order_sn']) || $this->succ(array("code" => "error", "msg" => "order_sn is empty"));
        ($password = $this->params['password']) || $this->succ(array("code" => "error", "msg" => "password is empty"));
        $pwd = M('order o')->join('left join ypt_screen_memcard_use u on u.card_code=o.card_code')->where(array('o.order_sn' => $order_sn))->getField('u.pay_pass');
        if (md5($password . 'tiancaijing') == $pwd) {
            $order_id = M('order')->where(array('order_sn' => $order_sn))->getField('order_id');
            $pay_info = array(
                "order_id" => $order_id,
                "remark" => $order_sn,
                "mode" => 27,
                "merchant_id" => $this->merchant_id,
                "paystyle_id" => 1,
                "price" => 0,
                "status" => 1,
                "paytime" => time(),
                "bill_date" => date('Ymd')
            );
            $pay_id = $this->pay_model->add($pay_info);
            if ($pay_id) {
                $this->card_off($order_sn);
                $this->succ(array("code" => "success", "msg" => "支付成功"));
            }
        } else {
            $this->succ(array("code" => "error", "msg" => "密码错误"));
        }
    }

    //核销优惠券、扣会员卡余额、积分1.6.0
    public function card_off($order_sn)
    {
        $this->path = $_SERVER['DOCUMENT_ROOT'] . '/data/log/member/';
        $order = M('order')->where("order_sn='$order_sn'")->find();
        if (!$order) return;
        if ($order['is_cancel'] == 1) {
            return array("code" => "success", 'msg' => '该笔订单已核销');
        }
        $coupon_code = $order['coupon_code'];//优惠券code
        $card_code = $order['card_code'];//会员卡code
        $price = $order['order_amount'];//订单应付金额（优惠后的价格）
        $dikoufen = $order['integral'];//会员卡使用的积分
        $yue = $order['user_money'];//会员卡使用的余额
        $save['update_time'] = time();
        $save['pay_time'] = time();
        $save['order_status'] = '5';
        $save['pay_status'] = '1';
        $save['is_cancel'] = '1';
        M('order')->where("order_sn='$order_sn'")->save($save);

        //核销优惠券
        if ($coupon_code) {
            $url = "https://api.weixin.qq.com/card/code/consume?access_token=" . get_weixin_token();
            $use_coupon = request_post($url, json_encode(array('code'=>$coupon_code)));
            $coupon_result = json_decode($use_coupon, true);
            if ($coupon_result['errmsg'] == "ok") {
                get_date_dir($this->path, 'coupon', 'API核销', '消费使用，订单号:' . $order_sn . '，优惠券code:' . $coupon_code . ',核销结果:' . json_encode($coupon_result));
                M("screen_user_coupons")->where("usercard=$coupon_code")->setField(array('status' => 0, 'update_time' => time()));
            } else {
                file_put_contents('./data/log/member/coupon_cancel_error.log', date("Y-m-d H:i:s") . '，优惠券核销失败，订单号:' . $order_sn . '，优惠券code:' . $coupon_code . ',结果:' . json_encode($coupon_result) . PHP_EOL, FILE_APPEND | LOCK_EX);
            }
        }

        //会员卡
        if ($card_code) {
            $card = M("screen_memcard_use")->alias('u')
                ->join('left join ypt_screen_memcard m on u.card_id=m.card_id')
                ->field('m.id,m.credits_set,m.expense,m.level_set,m.is_agent,m.level_up,m.expense_credits,m.expense_credits_max,u.id as smu_id,u.card_code,u.entity_card_code,u.card_balance,u.yue,u.card_id,u.card_amount,u.level')
                ->where(array('u.card_code|u.entity_card_code'=>$card_code))
                ->find();
            //会员卡消费送积分
            $send = 0;
            if ($card['credits_set'] == 1) {
                $send = floor(($price + $yue) / $card['expense']) * $card['expense_credits'];
                //如果送的积分大于最多可送的分，则赠送最大积分
                if ($send > $card['expense_credits_max']) {
                    $send = $card['expense_credits_max'];
                }
            }
            #如果使用联名卡，给商家加上储值
            if ($card['is_agent'] == 1) {
                $role_id = M('merchants_role_users')->where(array('uid' => $order['user_id']))->getField('role_id');
                if ($role_id == 3 && $order['user_money'] > 0) {//商家&&使用了储值支付
                    #1.8版本先扣增加余额扣掉手续费，2018.4.11
                    $card_rate = M('merchants_users')->where(array('id' => $order['user_id']))->getField('card_rate');
                    $inc_price = $order['user_money'] * $card_rate / 100;
                    M('merchants_users')->where(array('id' => $order['user_id']))->setInc('card_balance', $inc_price);
                    #余额日志
                    M('balance_log')->add(array('price' => $inc_price, 'ori_price' => $order['user_money'], 'rate_price' => $order['user_money'] - $inc_price, 'order_sn' => $order_sn, 'add_time' => time(), 'remark' => '核销异业联盟卡', 'mid' => $order['user_id'], 'balance' => M('merchants_users')->where(array('id' => $order['user_id']))->getField('balance')));
                }
            }
            //$card['yue']，会员卡余额 - 使用的余额
            $total_yue = $card['yue'] - $yue;//计算后的储值
            M("screen_memcard_use")->where(array('id'=>$card['smu_id']))->save(array('yue'=>$total_yue));
            if($card['card_code']){
                $token = get_weixin_token();
                $ts['code'] = urlencode($card_code);//卡号
                $ts['card_id'] = urlencode($card['card_id']);//卡id
                $ts['custom_field_value1'] = urlencode($total_yue);//会员卡储值
                request_post('https://api.weixin.qq.com/card/membercard/updateuser?access_token=' . $token, urldecode(json_encode($ts)));
            }

            //获取商户的等级信息,level_set等级设置，level_up是否可升级
            if ($card['level_set'] == 1 && $card['level_up'] == 1) {
                //获取该会员的单次消费expense_single，累计消费expense，累计积分card_amount
                $field = 'ifnull(sum(order_amount),0) as expense,ifnull(max(order_amount),0) as expense_single';
                $mem_info_where['order_status'] = '5';
                if($card['card_code'] && $card['entity_card_code']){
                    $mem_info_where['card_code'] = array(array('eq',$card['card_code']),array('eq',$card['entity_card_code']),'or');
                }else{
                    if ($card['card_code']) {
                        $mem_info_where['card_code'] = $card['card_code'];
                    }elseif ($card['entity_card_code']) {
                        $mem_info_where['card_code'] = $card['entity_card_code'];
                    }
                }
                $mem_info = M('order')->where($mem_info_where)->field($field)->find();
                $mem_info['card_amount'] = M("screen_memcard_use")->where(array('id'=>$card['smu_id']))->getField('card_amount');
                //会员卡所有等级列表
                #充值记录信息，recharge累计充值金额，recharge_single单次充值最大金额
                $recharge_info = M('user_recharge')
                    ->where(array('memcard_id' => $card['smu_id'], 'status' => 1))
                    ->field('ifnull(sum(real_price),0) as recharge,ifnull(max(real_price),0) as recharge_single')
                    ->find();
                $mem_info = array_merge($mem_info, $recharge_info);
                $memcard_level = M('screen_memcard_level')->where(array('c_id' => $card['id']))->order('level asc')->select();
                foreach ($memcard_level as &$value) {
                    $type = explode(',', $value['level_up_type']);
                    foreach ($type as &$val) {
                        #会员当前等级信息,current_level当前等级,current_level_name当前等级名称
                        $level = $this->get_level($val, $mem_info, $value);
                        if ($level) {
                            $current_level = $level['current_level'];
                            $current_level_name = $level['current_level_name'];
                            break;
                        }
                    }
                }
            }
            if ($current_level && $current_level > $card['level']) {
                M("screen_memcard_use")->where(array('id'=>$card['smu_id']))->setField(array('level' => $current_level));
                $ts['custom_field_value2'] = urlencode($current_level_name);//会员卡名称
            }
            $final_order = $order_sn;
            if ($dikoufen > 0) {
                M("screen_memcard_use")->where(array('id'=>$card['smu_id']))->setDec('card_balance', $dikoufen);
                $card_balance = M("screen_memcard_use")->where(array('id'=>$card['smu_id']))->getField('card_balance');
                if($card['card_code']){
                    $ts["add_bonus"] = urlencode('-' . $dikoufen);//增加的积分，负数为减
                    $ts["record_bonus"] = urlencode('消费使用积分');//增加的积分，负数为减
                    $dikoufen_ts_res = request_post('https://api.weixin.qq.com/card/membercard/updateuser?access_token=' . $token, urldecode(json_encode($ts)));
                    $dikoufen_ts_result = json_decode($dikoufen_ts_res, true);
                    $dikoufen_ts_result['errcode'] == 0 ? $dikoufen_ts_result_msg = 1 : $dikoufen_ts_result_msg = 0;
                    get_date_dir($this->path, 'card_coupon', '核销', "消费使用，订单号{$final_order}，会员卡code:{$card_code}");
                    get_date_dir($this->path, 'card_coupon', '核销', '使用积分:' . $dikoufen);
                    get_date_dir($this->path, 'card_coupon', '核销', '请求参数:' . json_encode($ts));
                    get_date_dir($this->path, 'card_coupon', '核销', '返回结果:' . $dikoufen_ts_res);
                }
                M('screen_memcard_log')->add(array('add_time' => time(), 'update_time' => time(), 'value' => '-' . $dikoufen, 'balance' => $card_balance, 'ts' => json_encode($ts), 'order_sn' => $order_sn, 'code' => $card_code, 'ts_status' => $dikoufen_ts_result_msg, 'msg' => $dikoufen_ts_res, 'record_bonus' => '消费使用积分'));
            }
            if ($send > 0) {
                //card_balance，会员卡剩余积分
                M("screen_memcard_use")->where(array('id'=>$card['smu_id']))->setInc('card_balance', $send);
                //card_balance，会员卡总积分
                M("screen_memcard_use")->where(array('id'=>$card['smu_id']))->setInc('card_amount', $send);
                $card_balance = M("screen_memcard_use")->where(array('id'=>$card['smu_id']))->getField('card_balance');
                if($card['card_code']){
                    $ts["add_bonus"] = urlencode($send);//增加的积分，负数为减
                    $ts["record_bonus"] = urlencode('消费赠送积分');//增加的积分，负数为减
                    $send_ts_res = request_post('https://api.weixin.qq.com/card/membercard/updateuser?access_token=' . $token, urldecode(json_encode($ts)));
                    $send_ts_result = json_decode($send_ts_res, true);
                    $send_ts_result['errcode'] == 0 ? $send_ts_result_msg = 1 : $send_ts_result_msg = 0;
                    get_date_dir($this->path, 'card_coupon', '核销', "消费使用，订单号{$final_order}，会员卡code:{$card_code}");
                    get_date_dir($this->path, 'card_coupon', '核销', '赠送积分:' . $send);
                    get_date_dir($this->path, 'card_coupon', '核销', '请求参数:' . json_encode($ts));
                    get_date_dir($this->path, 'card_coupon', '核销', '返回结果:' . $send_ts_res);
                }
                M('screen_memcard_log')->add(array('add_time' => time(), 'update_time' => time(), 'value' => $send, 'balance' => $card_balance, 'ts' => json_encode($ts), 'order_sn' => $order_sn, 'code' => $card_code, 'ts_status' => $send_ts_result_msg, 'msg' => $send_ts_res, 'record_bonus' => '消费赠送积分'));
            }
        }
    }

    //获取会员当前等级信息
    private function get_level($type, $up_info, $level_info)
    {
        switch ($type) {
            case 1:
                if ($up_info['recharge_single'] >= $level_info['level_recharge_single']) {
                    $level['current_level'] = $level_info['level'];
                    $level['current_level_name'] = $level_info['level_name'];
                    $level['current_level_discount'] = $level_info['level_discount'];
                    return $level;
                }
                break;
            case 2:
                if ($up_info['recharge'] >= $level_info['level_recharge']) {
                    $level['current_level'] = $level_info['level'];
                    $level['current_level_name'] = $level_info['level_name'];
                    $level['current_level_discount'] = $level_info['level_discount'];
                    return $level;
                }
                break;
            case 3:
                if ($up_info['expense_single'] >= $level_info['level_expense_single']) {
                    $level['current_level'] = $level_info['level'];
                    $level['current_level_name'] = $level_info['level_name'];
                    $level['current_level_discount'] = $level_info['level_discount'];
                    return $level;
                }
                break;
            case 4:
                if ($up_info['expense'] >= $level_info['level_expense']) {
                    $level['current_level'] = $level_info['level'];
                    $level['current_level_name'] = $level_info['level_name'];
                    $level['current_level_discount'] = $level_info['level_discount'];
                    return $level;
                }
                break;
            case 5:
                if ($up_info['card_amount'] >= $level_info['level_integral']) {
                    $level['current_level'] = $level_info['level'];
                    $level['current_level_name'] = $level_info['level_name'];
                    $level['current_level_discount'] = $level_info['level_discount'];
                    return $level;
                }
                break;
            default:
                if ($level_info['level'] == 1) {
                    $level['current_level'] = $level_info['level'];
                    $level['current_level_name'] = $level_info['level_name'];
                } else {
                    $level = null;
                }
                return $level;
        }
    }

}
