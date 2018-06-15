<?php

namespace Apiscreen\Controller;

use Common\Controller\ScreenbaseController;
use Think\Upload;

class  PayController extends ScreenbaseController
{
    public $host;
    public $uid;
    public $order;
    const brand = 'YPT';
    public $user_id;

    function _initialize()
    {
        parent::_initialize();
        $this->host = 'http://' . $_SERVER['HTTP_HOST'];
        $this->uid = $this->userInfo['uid'];
        $this->user_id = I("userId");
        $this->order = M("order");
        $this->pays = M('pay');
        $this->merchants = M("merchants");
        $this->cates = M("merchants_cate");
        $this->payBack = M("pay_back");
    }

    //双屏扫码收款【被扫】
    public function two_get_card($uid, $order_sn, $mode = 1)
    {
        vendor("phpqrcode.phpqrcode");
        if (!$order_sn) return array("code" => "error", "msg" => "无订单号");
        if (!$this->order->where("order_sn='$order_sn'")->find()) return array("code" => "error", "msg" => "该订单号不存在");
        $order_id = $this->order->where("order_sn='$order_sn'")->getField("order_id");
        $role_id = M("merchants_role_users")->where("uid=$uid")->getField('role_id');
        if ($role_id == 3 || $role_id == 2) {//商户
            $checker_id = 0;
            $u_id = $uid;
        } else {
            $checker_id = $uid;
            $u_id = M("merchants_users")->where("id=$uid")->getField("pid");
        }
        $merchant_id = M("merchants")->where("uid=$u_id")->getField("id");
        $cate_id = M("merchants_cate")->where(array('merchant_id' => $merchant_id, 'status' => 1, 'checker_id' => $checker_id))->getField("id");
        if (!$cate_id) {
            $cate_id = M("merchants_cate")->where(array('merchant_id' => $merchant_id, 'status' => 1))->getField("id");
            if (!$cate_id) {
                return array("code" => "error", "msg" => "该用户未绑定台签业务");
            }
        }
        $no_number = $this->create_no_number($cate_id);//每张二维码唯一标识
//        $value = "http://" . $_SERVER['HTTP_HOST'] . "/index.php?g=Pay&m=Barcode2&a=qrcode&type=0|" . $no_number . "&id=" . $cate_id  . "&checker_id=" . $checker_id ."&order_id=".$order_id;
        $value = "https://sy.youngport.com.cn/index.php?g=Pay&m=Barcode&a=qrcode&type=0|" . $no_number . "&id=" . $cate_id . "&checker_id=" . $checker_id . "&order_id=" . $order_id . "&mode=" . $mode;
        get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Apiscreen/', 'two_get_card', 'value', $value);

        return array("code" => "success", "msg" => "成功", "data" => $value);
    }

//    双屏现金
    public function cash_pay()
    {
        $uid = $this->uid;
        $order_id = I("order_id");
        $order = $this->order->where("order_id=$order_id")->find();
        if (!$order) $this->ajaxReturn(array("code" => "error", "msg" => "该订单号不存在"));
        $merchant_id = $this->get_merchant($uid);
        if ($merchant_id == $uid) $checker_id = 0;
        else $checker_id = $uid;
        $price = $order['order_amount'];
        $remark = $order['order_sn'];
        $customer_id = "ypt_screen_" . rand(1000, 9999);
        $data = array(
            "order_id" => $order_id,
            "mode" => 4,
            "checker_id" => $checker_id,
            "merchant_id" => (int)$merchant_id,
            "customer_id" => $customer_id,
            "paystyle_id" => 5,
            "price" => $price,
            "remark" => $remark,
            "status" => 1,
            "cate_id" => 1,
            "paytime" => time()
        );
        if ($this->pays->add($data)) {
            $this->order->where("order_id=$order_id")->save(array("pay_status" => 1));
            $this->ajaxReturn(array("code" => "success", "msg" => "支付成功"));
        }

    }

    //扫码支付查询
    public function find_pay()
    {
        $order_id = I("order_id");
        $remark = M("order")->where("order_sn='$order_id'")->find();
        if (!$remark) {
            $this->ajaxReturn(array("code" => "error", "msg" => "未找到订单"));
        }
        $pay = $this->pays->where("remark='$order_id'")->find();
        if ($pay['status'] == 1) {
            switch ($pay['paystyle_id']) {
                case "1":
                    $pastyle = "微信支付";
                    break;
                case "2":
                    $pastyle = "支付宝支付";
                    break;
                case "5":
                    $pastyle = "现金支付";
                    break;
                default:
                    $pastyle = "其他支付";
                    break;
            }
            $this->ajaxReturn(array("code" => "success", "msg" => "支付成功", "data" => $pastyle));
        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => "还未支付成功"));
        }

    }

    /**生成no_number
     * @param $cate_id
     * @return string
     */
    private function create_no_number($cate_id)
    {
        $no_number = M("merchants_cate")->where(array("id" => $cate_id))->getField('no_number');
        $no_number = substr($no_number, -7) + 1;
        $seven = "000000" . $no_number;
        $cate_name = 'SJ';
        $no_number = self::brand . $cate_name . substr($seven, -7);
        return $no_number;
    }

    /**
     * @param $uid
     * @return 获取商户id
     */
    protected function get_merchant($uid)
    {
        $role_id = M("merchants_role_users")->where("uid=$uid")->getField('role_id');
        if ($role_id == 3) {
            return $uid;
        } else {
            return M("merchants_users")->where("id=$uid")->getField("pid");
        }
    }


    //退款
    public function pay_back()
    {
        if (I('sign') == '5e022b44a15a90c0') {
            $mid = I('mid');
        } else {
            $muid = $this->get_merchant($this->uid);
            $mid = $this->merchants->where("uid=$muid")->getField("id");
        }

        $style = I("style");
        $remark = I("remark");
        $price_back = I("price_back");

        $pay = $this->pays->where("remark='$remark' And merchant_id= $mid ")->find();
        if (!$pay) $this->ajaxReturn(array("code" => "error", "msg" => "未找到订单"));
        #储值订单退款
        if ($pay['mode'] == 12) {
            #该笔订单充值到会员卡实际到账的金额
            $recharge_info = M('user_recharge')->where(array('order_sn' => $remark))->field('memcard_id,total_price')->find();
            $total_yue = $recharge_info['total_price'];
            #查询会员卡的储值是否足够订单充值到账的金额
            $card = M('screen_memcard_use u')
                ->join('join ypt_screen_memcard m on m.id=u.memcard_id')
                ->where(array('u.id' => $recharge_info['memcard_id']))
                ->field('u.yue,u.card_code,m.card_id')
                ->find();
            $user_yue = $card['yue'];
            if ($user_yue < $total_yue) {
                get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Apiscreen/', 'pay_back', '订单号', $remark . ',退款金额：' . $price_back . ',用户剩余储值：' . $user_yue . '，充值的储值：' . $total_yue . ',失败原因：用户剩余储值少于充值的金额');
                $this->ajaxReturn(array("code" => "error", "msg" => "用户剩余储值少于充值的金额"));
            } else {
                #如果储值充足退款必须退全款
                if ($price_back != $pay['price']) {
                    get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Apiscreen/', 'pay_back', '订单号', $remark . ',退款金额：' . $price_back . ',订单金额：' . $pay['price'] . ',失败原因：储值订单必须全额退款');
                    $this->ajaxReturn(array("code" => "error", "msg" => "储值订单必须全额退款"));
                }
                $final_yue = $user_yue - $total_yue;
            }
        } else {
            #其他使用储值订单退款处理
            $order_info = M('order')->where(array('order_sn' => $remark))->field('card_code,user_money')->find();
            $dec_card_balance = 0;
            if ($order_info && $order_info['user_money'] > 0) {
                #如果使用了储值
                $card = M('screen_memcard_use u')
                    ->join('join ypt_screen_memcard m on m.id=u.memcard_id')
                    ->where(array('u.card_code' => $order_info['card_code']))
                    ->field('u.yue,u.card_code,m.card_id')
                    ->find();
                $final_yue = $card['yue'] + $order_info['user_money'];
                #判断如果是使用了代理商储值，查询商户余额够不够，把代理商余额扣除
                $is_agent = M('screen_memcard m')->join('ypt_screen_memcard_use u on u.memcard_id=m.id')->where(array('u.card_code' => $order_info['card_code']))->getField('is_agent');
                if ($is_agent) {
                    #如果是代理商会员卡
                    if ($price_back != $pay['price']) $this->ajaxReturn(array("code" => "error", "msg" => "该笔订单使用了异业联盟卡必须全额退款"));
                    #算储值折扣前的的金额是否大于商户现在的余额
                    $card_balance = M('merchants_users')->where(array('id' => $muid))->getField('card_balance/card_rate*100');
                    if ($card_balance < $order_info['user_money']) $this->ajaxReturn(array("code" => "error", "msg" => "商户余额不足"));
                    #dec_card_balance 实际扣除商户余额金额
                    $dec_card_balance = M('merchants_users')->where(array('id' => $muid))->getField("$order_info[user_money]*card_rate/100");
                }
            }
        }

        $price = $pay['price'];
        if ($price_back > $price) {
            get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Apiscreen/', 'pay_back', '订单号', $remark . ',退款金额：' . $price_back . ',订单金额：' . $pay['price'] . ',失败原因：退款金额不能大于原有金额');
            $this->ajaxReturn(array("code" => "error", "msg" => "退款金额不能大于原有金额"));
        }
        if ($style == 1) { //现金退款
            if ($pay) $this->pays->where("remark='$remark'")->save(array("status" => "2", "price_back" => $price_back, "back_status" => 1));
            $back_info = $this->add_pay_back($pay, 99, $price_back);
            if ($dec_card_balance) {
                M('merchants_users')->where(array('id' => $this->userId))->setDec('card_balance', $dec_card_balance);
                M('balance_log')->add(array('price' => -$dec_card_balance, 'ori_price' => -$dec_card_balance, 'rate_price' => 0, 'order_sn' => $remark, 'add_time' => time(), 'remark' => '退款扣除余额', 'mid' => $this->userId, 'balance' => M('merchants_users')->where(array('id' => $this->userId))->getField('balance')));
            }
            $this->add_order_goods_number($remark);
            $this->ajaxReturn(array("code" => "success", "msg" => "退款成功", 'back_info' => $back_info));
        } else if ($style == 2) { //原路全额退款
            if ($pay['bank'] == 3) {
                get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Apiscreen/', 'pay_back', '订单号', $remark . '-price_back-' . $price_back . '-price-' . $price);
                if ($price_back > $price) $this->ajaxReturn(array("code" => "error", "msg" => "退款金额不能大于原有金额"));
                $result = A("Pay/Wxpay")->pay_back($remark, $price_back);
                if ($result['code'] == "success") {
                    $result['back_info'] = $this->add_pay_back($pay, 98, $price_back);
                    if ($dec_card_balance) {
                        M('merchants_users')->where(array('id' => $this->userId))->setDec('card_balance', $dec_card_balance);
                        M('balance_log')->add(array('price' => -$dec_card_balance, 'ori_price' => -$dec_card_balance, 'rate_price' => 0, 'order_sn' => $remark, 'add_time' => time(), 'remark' => '退款扣除余额', 'mid' => $this->userId, 'balance' => M('merchants_users')->where(array('id' => $this->userId))->getField('balance')));
                    }
                    //$this->add_order_goods_number($remark);
                    if ($card) $this->reduce_cz($card, $final_yue, $remark);
                }
                get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Apiscreen/', 'pay_back', '订单号', $remark . ",result:" . json_encode($result));
                $this->ajaxReturn($result);
            }

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
                        if ($dec_card_balance) {
                            M('merchants_users')->where(array('id' => $this->userId))->setDec('card_balance', $dec_card_balance);
                            M('balance_log')->add(array('price' => -$dec_card_balance, 'ori_price' => -$dec_card_balance, 'rate_price' => 0, 'order_sn' => $remark, 'add_time' => time(), 'remark' => '退款扣除余额', 'mid' => $this->userId, 'balance' => M('merchants_users')->where(array('id' => $this->userId))->getField('balance')));
                        }
                        $this->add_order_goods_number($remark);
                        if ($card) $this->reduce_cz($card, $final_yue, $remark);
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
                        if ($dec_card_balance) {
                            M('merchants_users')->where(array('id' => $this->userId))->setDec('card_balance', $dec_card_balance);
                            M('balance_log')->add(array('price' => -$dec_card_balance, 'ori_price' => -$dec_card_balance, 'rate_price' => 0, 'order_sn' => $remark, 'add_time' => time(), 'remark' => '退款扣除余额', 'mid' => $this->userId, 'balance' => M('merchants_users')->where(array('id' => $this->userId))->getField('balance')));
                        }
                        $this->add_order_goods_number($remark);
                        if ($card) $this->reduce_cz($card, $final_yue, $remark);
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
                        if ($dec_card_balance) {
                            M('merchants_users')->where(array('id' => $this->userId))->setDec('card_balance', $dec_card_balance);
                            M('balance_log')->add(array('price' => -$dec_card_balance, 'ori_price' => -$dec_card_balance, 'rate_price' => 0, 'order_sn' => $remark, 'add_time' => time(), 'remark' => '退款扣除余额', 'mid' => $this->userId, 'balance' => M('merchants_users')->where(array('id' => $this->userId))->getField('balance')));
                        }
                        //$this->add_order_goods_number($remark);
                        if ($card) $this->reduce_cz($card, $final_yue, $remark);
                    }
                    $this->ajaxReturn($result);
                }
                if ($pay_style == 2) {//支付宝原路退款
                    //if ($price_back != $price) $this->ajaxReturn(array("code" => "error", "msg" => "退款金额必须等于原有金额"));
                    if ($price_back > $price) $this->ajaxReturn(array("code" => "error", "msg" => "退款金额不能大于原有金额"));
                    $result = A("Pay/Szlzpay")->ali_pay_back($remark, $price_back);
                    if ($result['code'] == "success") {
                        $result['back_info'] = $this->add_pay_back($pay, 98, $price_back);
                        if ($dec_card_balance) {
                            M('merchants_users')->where(array('id' => $this->userId))->setDec('card_balance', $dec_card_balance);
                            M('balance_log')->add(array('price' => -$dec_card_balance, 'ori_price' => -$dec_card_balance, 'rate_price' => 0, 'order_sn' => $remark, 'add_time' => time(), 'remark' => '退款扣除余额', 'mid' => $this->userId, 'balance' => M('merchants_users')->where(array('id' => $this->userId))->getField('balance')));
                        }
                        //$this->add_order_goods_number($remark);
                        if ($card) $this->reduce_cz($card, $final_yue, $remark);
                    }
                    $this->ajaxReturn($result);
                }
            }
            // 浦发银行
            if ($pay['bank'] == 10) {
                $pay_style = $pay['paystyle_id'];
                if ($pay_style == 1) {//微信原路退款
                    //$d = M('merchants_pfpay')->where(array('merchant_id'=>$mid))->getField('pay_style');
                    //if($d!='1'){$this->ajaxReturn(array("code" => "error", "msg" => "D0通道不能退款"));}
                    //if ($price_back != $price) $this->ajaxReturn(array("code" => "error", "msg" => "退款金额必须等于原有金额"));
                    if ($price_back > $price) $this->ajaxReturn(array("code" => "error", "msg" => "退款金额不能大于原有金额"));
                    $result = A("Pay/Barcodepfbank")->wx_pay_back($remark, $price_back);
                    if ($result['code'] == "success") {
                        $result['back_info'] = $this->add_pay_back($pay, 98, $price_back);
                        if ($dec_card_balance) {
                            M('merchants_users')->where(array('id' => $this->userId))->setDec('card_balance', $dec_card_balance);
                            M('balance_log')->add(array('price' => -$dec_card_balance, 'ori_price' => -$dec_card_balance, 'rate_price' => 0, 'order_sn' => $remark, 'add_time' => time(), 'remark' => '退款扣除余额', 'mid' => $this->userId, 'balance' => M('merchants_users')->where(array('id' => $this->userId))->getField('balance')));
                        }
                        $this->add_order_goods_number($remark);
                        if ($card) $this->reduce_cz($card, $final_yue, $remark);
                    }
                    $this->ajaxReturn($result);
                }
                if ($pay_style == 2) {//支付宝原路退款
                    //$d = M('merchants_pfpay')->where(array('merchant_id'=>$mid))->getField('pay_style');
                    //if($d!='1'){$this->ajaxReturn(array("code" => "error", "msg" => "D0通道不能退款"));}
                    //if ($price_back != $price) $this->ajaxReturn(array("code" => "error", "msg" => "退款金额必须等于原有金额"));
                    if ($price_back > $price) $this->ajaxReturn(array("code" => "error", "msg" => "退款金额不能大于原有金额"));
                    $result = A("Pay/Barcodepfbank")->ali_pay_back($remark, $price_back);
                    if ($result['code'] == "success") {
                        $result['back_info'] = $this->add_pay_back($pay, 98, $price_back);
                        if ($dec_card_balance) {
                            M('merchants_users')->where(array('id' => $this->userId))->setDec('card_balance', $dec_card_balance);
                            M('balance_log')->add(array('price' => -$dec_card_balance, 'ori_price' => -$dec_card_balance, 'rate_price' => 0, 'order_sn' => $remark, 'add_time' => time(), 'remark' => '退款扣除余额', 'mid' => $this->userId, 'balance' => M('merchants_users')->where(array('id' => $this->userId))->getField('balance')));
                        }
                        $this->add_order_goods_number($remark);
                        if ($card) $this->reduce_cz($card, $final_yue, $remark);
                    }
                    $this->ajaxReturn($result);
                }
            }
            // 新大陆
            if ($pay['bank'] == 11) {
                $pay_style = $pay['paystyle_id'];
                if ($pay_style == 1) {//微信原路退款
                    if ($price_back > $price) $this->ajaxReturn(array("code" => "error", "msg" => "退款金额不能大于原有金额"));
                    $result = A("Pay/Barcodexdlbank")->pay_back($remark, $price_back);
                    if ($result['code'] == "success") {
                        $result['back_info'] = $this->add_pay_back($pay, 98, $price_back);
                        if ($dec_card_balance) {
                            M('merchants_users')->where(array('id' => $this->userId))->setDec('card_balance', $dec_card_balance);
                            M('balance_log')->add(array('price' => -$dec_card_balance, 'ori_price' => -$dec_card_balance, 'rate_price' => 0, 'order_sn' => $remark, 'add_time' => time(), 'remark' => '退款扣除余额', 'mid' => $this->userId, 'balance' => M('merchants_users')->where(array('id' => $this->userId))->getField('balance')));
                        }
                        if ($card) $this->reduce_cz($card, $final_yue, $remark);
                    }
                    $this->ajaxReturn($result);
                }
                if ($pay_style == 2) {//支付宝原路退款
                    if ($price_back > $price) $this->ajaxReturn(array("code" => "error", "msg" => "退款金额不能大于原有金额"));
                    $result = A("Pay/Barcodexdlbank")->pay_back($remark, $price_back);
                    if ($result['code'] == "success") {
                        $result['back_info'] = $this->add_pay_back($pay, 98, $price_back);
                        if ($dec_card_balance) {
                            M('merchants_users')->where(array('id' => $this->userId))->setDec('card_balance', $dec_card_balance);
                            M('balance_log')->add(array('price' => -$dec_card_balance, 'ori_price' => -$dec_card_balance, 'rate_price' => 0, 'order_sn' => $remark, 'add_time' => time(), 'remark' => '退款扣除余额', 'mid' => $this->userId, 'balance' => M('merchants_users')->where(array('id' => $this->userId))->getField('balance')));
                        }
                        if ($card) $this->reduce_cz($card, $final_yue, $remark);
                    }
                    $this->ajaxReturn($result);
                }
            }
            // 乐刷
            if ($pay['bank'] == 12) {
                if ($price_back != $price) $this->ajaxReturn(array("code" => "error", "msg" => "仅支持全额退款"));
                $result = A("Pay/Leshuabank")->refund($remark);
                if ($result['code'] == "success") {
                    $result['back_info'] = $this->add_pay_back($pay, 98, $price_back);
                    if ($dec_card_balance) {
                        M('merchants_users')->where(array('id' => $this->userId))->setDec('card_balance', $dec_card_balance);
                        M('balance_log')->add(array('price' => -$dec_card_balance, 'ori_price' => -$dec_card_balance, 'rate_price' => 0, 'order_sn' => $remark, 'add_time' => time(), 'remark' => '退款扣除余额', 'mid' => $this->userId, 'balance' => M('merchants_users')->where(array('id' => $this->userId))->getField('balance')));
                    }
                    if ($card) $this->reduce_cz($card, $final_yue, $remark);
                }
                $this->ajaxReturn($result);
            }
            // 平安付
            if ($pay['bank'] == 13) {
                if ($price_back > $price) $this->ajaxReturn(array("code" => "error", "msg" => "退款金额不能大于原有金额"));
                $result = A("Pay/Barcodepabank")->pay_back($remark, $price_back);
                if ($result['code'] == "success") {
                    $result['back_info'] = $this->add_pay_back($pay, 98, $price_back);
                    if ($dec_card_balance) {
                        M('merchants_users')->where(array('id' => $this->userId))->setDec('card_balance', $dec_card_balance);
                        M('balance_log')->add(array('price' => -$dec_card_balance, 'ori_price' => -$dec_card_balance, 'rate_price' => 0, 'order_sn' => $remark, 'add_time' => time(), 'remark' => '退款扣除余额', 'mid' => $this->userId, 'balance' => M('merchants_users')->where(array('id' => $this->userId))->getField('balance')));
                    }
                    if ($card) $this->reduce_cz($card, $final_yue, $remark);
                }
                $this->ajaxReturn($result);
            }
            $this->ajaxReturn(array("code" => "error", "msg" => "EOF"));

        }
    }


    //退款成功添加到退款记录表
    protected function add_pay_back($pay_back, $mode, $price_back)
    {
        $pay_back['back_pid'] = $pay_back['id'];
        $pay_back['status'] = 5;
        $pay_back['price_back'] = $price_back;
        $pay_back['paytime'] = time();
        $pay_back['mode'] = $mode;
        $pay_back['bill_date'] = date('Ymd');
        $order = M('order')->where(array('order_sn' => $pay_back['remark']))->find();
        if ($order) {
            M('order')->where(array('order_sn' => $pay_back['remark']))->save(array('order_status' => 0));
        }
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


    #储值订单退款撤回充值的储值
    private function reduce_cz($card, $final_yue, $remark)
    {
        M()->startTrans();
        M('screen_memcard_use')->where('card_code=' . $card['card_code'])->setField('yue', $final_yue);
        $token = get_weixin_token();
        $ts['code'] = urlencode($card['card_code']);//卡号
        $ts['card_id'] = urlencode($card['card_id']);//卡id
        $ts['custom_field_value1'] = urlencode($final_yue);//会员卡储值
        $res = request_post('https://api.weixin.qq.com/card/membercard/updateuser?access_token=' . $token, urldecode(json_encode($ts)));
        get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Apiscreen/', 'pay_back', '退款退储值，订单号', $remark . '，会员卡code:' . $card['card_code']);
        get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Apiscreen/', 'pay_back', '请求参数', json_encode($ts));
        get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Apiscreen/', 'pay_back', '返回结果', $res);
        $result = json_decode($res, true);
        if ($result['errcode'] == 0) {
            M()->commit();
        } else {
            M()->rollback();
        }
    }

}