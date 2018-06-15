<?php

namespace Api\Controller;

use Common\Controller\ApibaseController;

class  ShopnewsController extends ApibaseController
{
//  用户表里面的id
    public $id;
    public $host;
    public $merchants;
    public $users;
    public $roles;
    public $pays;
    public $cates;
    public $payBack;

    public function __construct()
    {
        parent::__construct();
        $this->id = $this->userInfo['uid'];
        $this->host = 'https://' . $_SERVER['HTTP_HOST'];
        $this->merchants = M("merchants");
        $this->users = M("merchants_users");
        $this->roles = M("merchants_role_users");
        $this->cates = M("merchants_cate");
        $this->pays = M('pay');
        $this->payBack = M("pay_back");
    }

// 判断是否为金木堂
    public function check_merchant_api()
    {
        $this->checkLogin();
        $uid = $this->id;
        $role_id = $this->roles->where(array("uid" => $uid))->getField("role_id");
        if ($role_id == 3) {
            $mer = $this->merchants->where(array("uid" => $uid))->field("id,mid")->find();
            if ($mer['id'] == 74) {
                $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => "1"));
            } else {
                $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => "0"));
            }
        } elseif ($role_id != 2) {
            $pid = $this->users->where(array("id" => $uid))->getField("pid");
            if (!$pid) $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => "0"));
            $mer = $this->merchants->where(array("uid" => $pid))->field("id,mid")->find();
            if ($mer['id'] == 74) {
                $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => "1"));
            } else {
                $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => "0"));
            }
        } else {
            $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => "0"));
        }
    }

//    金木堂编辑订单号
    public function edit_jmt_remark()
    {
        $jmt_remark = I("jmt_remark");
        $pay_id = (int)I("pay_id");
        $status = I('status');
        if ($status == '5') {
            if (!$this->payBack->where("id=$pay_id")->find()) {
                $this->ajaxReturn(array("code" => "error", "msg" => "失败", "data" => "编辑失败失败"));
            }
            $this->payBack->where("id=$pay_id")->save(array("jmt_remark" => $jmt_remark));
            $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => "编辑成功"));
        } else {
            if (!$this->pays->where("id=$pay_id")->find()) {
                $this->ajaxReturn(array("code" => "error", "msg" => "失败", "data" => "编辑失败失败"));
            }
            //if($this->pays->where("jmt_remark=".$jmt_remark)->find())$this->ajaxReturn(array("code" => "error","msg"=>"失败", "data"=>"该订单号已存在"));
            $this->pays->where("id=$pay_id")->save(array("jmt_remark" => $jmt_remark));
            $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => "编辑成功"));
        }
    }

//    获取商户的现金券信息
    public function get_cash_detail()
    {
        $this->checkLogin();
        $uid = $this->get_merchant($this->id);
        $coupon = M("merchants")->where(array("uid" => $uid))->getField("base_url");
        if ($coupon) $data['logo_url'] = $this->host . $coupon; //商户头像
        else {
            $data['logo_url'] = "";
        }
        if ($uid == $this->id) {
            $data['user_name'] = "";
        } else {
            $user_name = $this->users->where(array('id' => $this->id))->getField("user_name");
            $data['user_name'] = $user_name ? $user_name : "";
        }
        $data['cash_total'] = $this->users->where(array('id' => $uid))->getField("balance"); //现金总额
        $data['card_balance'] = $this->users->where(array('id' => $uid))->getField("card_balance"); //联名卡余额
        $data['card_balance'] = strval(round($data['card_balance'], 2));
        $data['cash_total'] = strval(round($data['cash_total'], 2));
        $data['cash_num'] = M("user_cash")->where(array('uid' => $uid, 'status' => 1))->count("id"); //现金券数量
        $data['is_miniapp'] = M("merchants")->where(array('uid' => $uid))->getField("is_miniapp"); //是否开通小程序
        if (!$data['is_miniapp']) $data['is_miniapp'] = '';
        $data['is_pay'] = M("merchants_users")->where(array('id' => $uid))->getField("pay_pwd") ? "1" : "0"; //是否有密码支付
        $data['withdraw_num'] = strval(1 - M('withdraw')->where(array('uid' => $uid, 'add_time' => array('EGT', strtotime(date('Y-m-d', time())))))->count());//今日可提现次数
        $data['withdraw_log'] = M('withdraw')->where(array('uid' => $uid, 'add_time' => array('EGT', time() - 86400 * 30)))->field('id,price,status,add_time')->order('add_time desc')->select();//近三十天提现记录
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $data));
    }

    #提现审核详情
    public function withdraw_detail()
    {
        $id = I('id');
        if (!$id) $this->ajaxReturn(array("code" => "error", "msg" => "id is empty"));

        $data = M('withdraw w')->join('left join ypt_merchants_bank b on b.id=w.bank_id')->where(array('w.id' => $id))
            ->field("w.price,w.rate_price,w.bank_id,w.uid,w.status,w.add_time,w.update_time,w.remark,ifnull(bank_account,'') as bank_account,ifnull(bank_account_no,'') as bank_account_no")
            ->find();
        if ($data['bank_id'] == 0) {
            $bank = M('merchants')->where(array('uid' => $data['uid']))->field('bank_account,bank_account_no')->find();
            $data['bank_account'] = $bank['bank_account'];
            $data['bank_account_no'] = decrypt($bank['bank_account_no']);
        }
        if ($data['bank_account_no']) $data['bank_account_no'] = substr($data['bank_account_no'], -4, 4);
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $data));
    }

    #提现银行卡列表
    public function bank_card_list()
    {
        #添加的银行卡列表
        $data = M('merchants_bank')->where(array('uid' => $this->userId))->field('id,bank_account_no,bank_account')->select();
        #如果是商户，到商户表查找进件收款卡信息
        if ($this->userInfo['role_id'] == 3) {
            $card = M('merchants')->where(array('uid' => $this->userId))->field('0 as id,bank_account_no,bank_account')->find();
            array_unshift($data, $card);
        }
        foreach ($data as &$v) {
            $v['bank_account_no'] = decrypt($v['bank_account_no']);//解密
        }
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $data));
    }

    #添加提现银行卡
    public function add_bank_card()
    {
        $sms = I('sms', '', 'trim');
        if (!$sms) {
            $this->ajaxReturn(array('code' => 'error', 'msg' => '未输入验证码'));
        }
        $phone = I('phone', '', 'trim');
        if (!$sms) {
            $this->ajaxReturn(array('code' => 'error', 'msg' => 'phone is empty'));
        }
        $this->checkSms($phone, $sms);

        $bank_account_no = I('bank_account_no', '', 'trim');
        if ($bank_account_no && !is_numeric($bank_account_no)) {
            $this->ajaxReturn(array('code' => 'error', 'msg' => '银行卡号格式不正确'));
        } elseif (!$bank_account_no) {
            $this->ajaxReturn(array('code' => 'error', 'msg' => '未输入银行卡号'));
        }

        $account_name = I('account_name', '', 'trim');
        if (!$account_name) {
            $this->ajaxReturn(array('code' => 'error', 'msg' => '未输入持卡人姓名'));
        }

        $bank_account = I('bank_account', '', 'trim');
        if (!$bank_account) {
            $this->ajaxReturn(array('code' => 'error', 'msg' => '未输入开户银行'));
        }

        $branch_account = I('branch_account', '', 'trim');
        /*if (!$branch_account) {
            $this->ajaxReturn(array('code'=>'error','msg'=>'未输入开户支行'));
        }*/
        $res = M('merchants_bank')->add(array(
            'bank_account_no' => encrypt($bank_account_no),
            'account_name' => $account_name,
            'bank_account' => $bank_account,
            'branch_account' => $branch_account,
            'add_time' => time(),
            'uid' => $this->userId,
        ));
        if ($res) {
            $this->ajaxReturn(array("code" => "success", "msg" => "添加成功", "id" => $res));
        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => "添加失败"));
        }
    }

    #删除添加的银行卡
    public function del_bank_card()
    {
        ($id = I('id')) || $this->ajaxReturn(array("code" => "error", "msg" => "id is empty"));
        if ($id == 0) $this->ajaxReturn(array("code" => "error", "msg" => "不能删除进件收款银行卡"));
        $card_info = M('merchants_bank')->where("id=$id")->find();
        if (!$card_info) $this->ajaxReturn(array("code" => "error", "msg" => "card is not found"));
        if ($card_info['uid'] != $this->userId) $this->ajaxReturn(array("code" => "error", "msg" => "不能删除他人的银行卡"));
        if (M('merchants_bank')->where("id=$id")->delete()) {
            $this->ajaxReturn(array("code" => "success", "msg" => "删除成功"));
        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => "网络错误"));
        }
    }

    #验证验证码
    private function checkSms($phone, $sms)
    {
        $sms_logs = M('sms_logs')->where(array('phone' => $phone, 'code' => $sms, 'type' => 6))->order('id desc')->find();
        if (!$sms_logs) {
            $this->ajaxReturn(array("code" => "error", "msg" => '验证码有误'));
        }
        $sms_time = strtotime($sms_logs['sms_time']);
        #30分钟有效期
        if ($sms_time + 1800 < time()) {
            $this->ajaxReturn(array("code" => "error", "msg" => '验证码已过期'));
        }
    }

//    商户服务
    public function service()
    {
        $this->checkLogin();
        if ($this->version == "1.2") {
            $id = $this->get_merchant($this->id);
            $type = I("type");
            $time = $this->type_time($type);
            $count = $this->count_merchant($id, $time);
            $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $count));
        }
        if ($this->version == "1.3") {
            $m_info = $this->get_merchant1($this->id);
            $type = I("type");
            $time = $this->type_time($type);
            $count = $this->count_merchant1($m_info, $time);
            $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $count));
        }


    }

    //    1.3.4首页banner流水
    public function service1()
    {
        $this->checkLogin();
        $m_info = $this->get_merchant1($this->id);
        $type = I("type");
        $time = $this->type_time($type);
        $count = $this->count_merchant2($m_info, $time);
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $count));
    }

    public function coin()
    {
        $this->checkLogin();
        if ($this->version == "1.2") {
            $id = $this->get_merchant($this->id);
            $type = I("type");
            $paystyle = I("paystyle");
            $status = I("status");
            $time = $this->type_time($type);
            $pays = $this->merchant_detail($id, $time, $paystyle, $status);
            $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $pays));
        }
        if ($this->version == "1.3") {
            $this->coin1();
        }

    }

//    流水
    public function coin1()
    {
        $id = I("id") ? I("id") : $this->id;
        $m_info = $this->get_merchant1($id);
        $type = I("type") ? I("type") : 3;
        $paystyle = I("paystyle") ? I("paystyle") : "";
        $status_type = I("status") ? I("status") : "";
        $checker_id = I("checker_id") ? I("checker_id") : "";
        //$page=I("page")?I("page"):0;
        $mode = I("mode") ? I("mode") : "";
        $status_type = $this->get_status($status_type);
        $mode_type = $this->get_mode($mode);
        if ($type == 7)  //不区分时分秒
        {
            $begin_time = strtotime(I("begin_time"));  //今天开始0点
            $end_time = strtotime(I("end_time"));
            $number = ($end_time - $begin_time) / 24 / 60 / 60 + 1;
            $time = $end_time + 24 * 60 * 60 - 1;              //今天结束23:59:59
            $time_detail = array($begin_time, $time);
        } elseif ($type == 8) {//时分秒的
            $begin_time = strtotime(I("begin_time"));  //今天开始0点
            $end_time = strtotime(I("end_time"));
            if (date("Y-m-d", $begin_time) == date("Y-m-d", $end_time)) {
                $number = 1;
            } else {
                $a_time = $this->time_transform($begin_time);
                $b_time = $this->time_transform($end_time);
                $number = ($b_time - $a_time) / 24 / 60 / 60 + 1;
            }

            $time = $end_time;              //今天结束23:59:59
            $time_detail = array($begin_time, $time);
        } else {
            $number = $this->get_number($type);
            $time = $this->type_time($type);
            $time_detail = $time;
            $time = $time[1];
        }
        $total = array();
        $detail = $this->merchant_detail1($m_info, $checker_id, $time_detail, $paystyle, $status_type, $mode_type);
        if ($type == 8) { //时分秒的
            if ($number == 1) {
                $time_now = array($begin_time, $end_time);
                $total[] = $this->count_merchant_detail1($m_info, $checker_id, $time_now, $paystyle, $status_type, $mode_type);
            } else {
                for ($i = $number; $i >= 1; $i--) {
                    $time_now = $this->day_detail1($begin_time, $end_time, $i, $number);
                    $total[] = $this->count_merchant_detail1($m_info, $checker_id, $time_now, $paystyle, $status_type, $mode_type);
                }
            }

        } else {
            for ($i = 1; $i <= $number; $i++) {
                $time_now = $this->day_detail($time, $i);
                $total[] = $this->count_merchant_detail1($m_info, $checker_id, $time_now, $paystyle, $status_type, $mode_type);
            }
        }
//        $start=$page*10;
//        $detail = array_slice($detail,$start,10);
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array('total' => $total, 'detail' => $detail)));
    }

    //1.3.4流水
    public function coin2()
    {
        add_log();
        $id = I("id", $this->id);
        $m_info = $this->get_merchant1($id);
        $type = I("type", 3);
        $paystyle = I("paystyle", "");
        $status_type = I("status", "");
        $checker_id = I("checker_id", "");
        //$page=I("page",0);
        $mode = I("mode", '');
        $cz_style = I("cz_style", '');//储值类型，1商户储值，2代理储值
        $status_type = $this->get_status($status_type);
        $mode_type = $this->get_mode($mode);

        if ($type == 7)  //不区分时分秒
        {
            $begin_time = strtotime(I("begin_time"));  //今天开始0点
            $end_time = strtotime(I("end_time"));
            $number = ($end_time - $begin_time) / 24 / 60 / 60 + 1;
            $time = $end_time + 24 * 60 * 60 - 1;              //今天结束23:59:59
            $time_detail = array($begin_time, $time);
        } elseif ($type == 8) {//时分秒的
            $begin_time = strtotime(I("begin_time"));  //今天开始0点
            $end_time = strtotime(I("end_time"));
            if (date("Y-m-d", $begin_time) == date("Y-m-d", $end_time)) {
                $number = 1;
            } else {
                $a_time = $this->time_transform($begin_time);
                $b_time = $this->time_transform($end_time);
                $number = ($b_time - $a_time) / 24 / 60 / 60 + 1;
            }

            $time = $end_time;              //今天结束23:59:59
            $time_detail = array($begin_time, $time);
        } else {
            $number = $this->get_number($type);
            $time = $this->type_time($type);
            $time_detail = $time;
            $time = $time[1];
        }
        $total = array();
        $detail = $this->merchant_detail2($m_info, $checker_id, $time_detail, $paystyle, $status_type, $mode_type, $cz_style);
        if ($type == 8) { //时分秒的
            if ($number == 1) {
                $time_now = array($begin_time, $end_time);
                $total[] = $this->count_merchant_detail2($m_info, $checker_id, $time_now, $paystyle, $status_type, $mode_type, $cz_style);
            } else {
                for ($i = $number; $i >= 1; $i--) {
                    $time_now = $this->day_detail1($begin_time, $end_time, $i, $number);
                    $total[] = $this->count_merchant_detail2($m_info, $checker_id, $time_now, $paystyle, $status_type, $mode_type, $cz_style);
                }
            }

        } else {
            for ($i = 1; $i <= $number; $i++) {
                $time_now = $this->day_detail($time, $i);
                $total[] = $this->count_merchant_detail2($m_info, $checker_id, $time_now, $paystyle, $status_type, $mode_type, $cz_style);
            }
        }
//        $start=$page*10;
//        $detail = array_slice($detail,$start,10);
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array('total' => $total, 'detail' => $detail)));
    }

    public function get_card_code($cz_style)
    {
        $code_list = array();
        if ($cz_style == 1) {
            $c_id = M('screen_memcard')->where(array('mid' => $this->userId))->getField('id');
            $code_list = M('screen_memcard_use')->where(array('memcard_id' => $c_id))->getField('card_code', true);
        } elseif ($cz_style == 2) {
            $agent_id = M('merchants_users')->where(array('id' => $this->userId))->getField('agent_id');
            $c_id = M('screen_memcard')->where(array('mid' => $agent_id))->getField('id');
            $code_list = M('screen_memcard_use')->where(array('memcard_id' => $c_id))->getField('card_code', true);
        }
        return $code_list;

    }

//  流水中的直接查询订单
    public function find_order()
    {
        $remark = I("remark") ? I("remark") : "";
        $merchant_id = M('merchants')->where(array('uid'=>$this->userId))->getField('id');
        $pay = $this->pays->where("remark='$remark' and merchant_id=$merchant_id")->find();
        if ($pay) {
            $checker_id = $pay['checker_id'];
            if ($checker_id != 0) {
                $checker_name = M()->query("select user_name from ypt_merchants_users where id=$checker_id");
                $pay['checker_name'] = $checker_name[0]['user_name'];
            } else {
                $pay['checker_name'] = "";
            }
            $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $pay));
        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => "失败", "data" => "未找到该订单"));
        }
    }

    //  1.8.2流水中的直接查询订单
    public function find_order1()
    {
        ($remark = I("remark")) || $this->ajaxReturn(array("code" => "error", "msg" => "remark is empty"));
        $where['remark|jmt_remark'] = array('LIKE',"%$remark%");
        if($this->userInfo['role_id']==3){
            $where['merchant_id'] = M('merchants')->where(array('uid'=>$this->userId))->getField('id');
        }else{
            $where['checker_id'] = $this->userId;
        }
        $pay = $this->pays->where($where)->field("id,paystyle_id,checker_id,price,remark,status,paytime,ifnull(bill_date,0) as bill_date,mode,authorization")->select();
        if ($pay) {
            foreach($pay as &$v){
                $checker_id = $v['checker_id'];
                if ($checker_id != 0) {
                    $v['checker_name'] = M('merchants_users')->where(array('id'=>$checker_id))->getField('user_name');
                } else {
                    $v['checker_name'] = "";
                }
                $v['mode_name'] = $this->numberstyle($v['mode']);
            }
            $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $pay));
        } else {
            $this->ajaxReturn(array("code" => "success", "msg" => "无数据", "data" => array()));
        }
    }

//商户某条交易信息详情
    public function coin_detail()
    {
        $this->checkLogin();
        $p_id = I("id");
        $pay = $this->pays
            ->where("id = $p_id")
            ->find();
        $checker_id = $pay['checker_id'];
        if ($checker_id != 0) {
            $checker_name = M()->query("select user_name from ypt_merchants_users where id=$checker_id");
            $pay['checker_name'] = $checker_name[0]['user_name'];
        } else {
            $pay['checker_name'] = "";
        }
        if (!$pay['jmt_remark']) {
            $pay['jmt_remark'] = "";
        }
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $pay));
    }

//  新改之后的流水详情
    public function coin_detail1()
    {
        $this->checkLogin();
        $pay_id = I("pay_id");
        $pay = $this->pays
            ->where(array('id' => $pay_id))
            ->field("id,status,cate_id,customer_id,paystyle_id,checker_id,remark,paytime,jmt_remark,mode,new_order_sn,price,price_back,la_ka_la,authorization")
            ->find();
        $pay['is_cash'] = $this->cates->where(array('id' => $pay['cate_id']))->getField("is_cash") ?: 0;
        $checker_id = $pay['checker_id'];
        if ($checker_id != 0) {
            $checker_name = M()->query("select user_name from ypt_merchants_users where id=$checker_id");
            $pay['checker_name'] = $checker_name[0]['user_name'];
        } else {
            $pay['checker_name'] = "";
        }
        if (!$pay['jmt_remark']) {
            $pay['jmt_remark'] = "";
        }


        if ($pay['mode'] == 10) {
            $order = M('quick_pay')->where(array('order_sn' => $pay['remark']))->find();
            if ($order) {
                $pay['integral_money'] = $order['integral_money'] ? $order['integral_money'] : "0.00"; // 积分
                $pay['discount'] = $order['discount'] ? (string)($order['discount'] * 10) : "100";      //折扣

                $pay['user_money'] = $order['yue_price'] ? $order['yue_price'] : "0.00"; //  会员卡储值
                $pay['coupon_price'] = $order['coupons_price'] ? $order['coupons_price'] : "0.00"; //  优惠券使用金额
                $pay['total_price'] = $order['order_price']; //总金额
                // M('log')->add(array('param'=>json_encode($pay)));
            } else {
                $pay['integral_money'] = "0.00";
                $pay['discount'] = "100";
                $pay['user_money'] = "0.00";
                $pay['coupon_price'] = "0.00";
                $pay['total_price'] = $pay['price'];
            }
        } elseif ($pay['mode'] == 12) {
            $order = M('user_recharge')->where(array('order_sn' => $pay['remark']))->find();
            if ($order) {
                $pay['integral_money'] = "0.00"; // 积分
                $pay['discount'] = "100";      //折扣
                $pay['user_money'] = "0.00"; //  会员卡储值
                $pay['coupon_price'] = "0.00"; //  优惠券使用金额
                $pay['total_price'] = $order['total_price']; //总金额
                $pay['send_price'] = $order['send_price'];
            } else {
                $pay['integral_money'] = "0.00";
                $pay['discount'] = "100";
                $pay['user_money'] = "0.00";
                $pay['coupon_price'] = "0.00";
                $pay['total_price'] = $pay['price'];
            }

        } else {
            $order = M("order")->where(array('order_sn' => $pay['remark']))->find();
            if ($order) {
                $pay['integral_money'] = $order['integral_money'] ? $order['integral_money'] : "0.00"; // 积分
                $pay['discount'] = $order['discount'] ? $order['discount'] : "100";      //折扣
                $pay['user_money'] = $order['user_money'] ? $order['user_money'] : "0.00"; //  会员卡储值
                $pay['coupon_price'] = $order['coupon_price'] ? $order['coupon_price'] : "0.00"; //  优惠券使用金额
                $pay['total_price'] = $order['total_amount']; //总金额
            } else {
                $pay['integral_money'] = "0.00";
                $pay['discount'] = "100";
                $pay['user_money'] = "0.00";
                $pay['coupon_price'] = "0.00";
                $pay['total_price'] = $pay['price'];
            }
        }
        $pay['send_price'] = isset($pay['send_price']) ? $pay['send_price'] : "0.00";
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $pay));

    }

    //  1.3.4的流水详情
    public function coin_detail2()
    {
        add_log();
        $this->checkLogin();
        $pay_id = I("pay_id");
        $status = I("status");
        if ($status == '5') {
            $pay = M('pay_back')
                ->where(array('back_pid' => $pay_id))
                ->field("id,status,cate_id,customer_id,paystyle_id,checker_id,remark,paytime,jmt_remark,mode,new_order_sn,price_back as price,price_back")
                ->find();
            //   add_log(json_encode($pay));

            if (!$pay) {
                $pay = M('pay_back')
                    ->where(array('id' => $pay_id))
                    ->field("id,status,cate_id,customer_id,paystyle_id,checker_id,remark,paytime,jmt_remark,mode,new_order_sn,price_back as price,price_back")
                    ->find();
            }
        } else {
            $pay = $this->pays
                ->where(array('id' => $pay_id))
                ->field("id,status,cate_id,customer_id,paystyle_id,checker_id,remark,paytime,jmt_remark,mode,new_order_sn,price,price_back")
                ->find();
        }
        //  add_log(json_encode($pay));
        $pay['is_cash'] = $this->cates->where(array('id' => $pay['cate_id']))->getField("is_cash") ?: 0;
        $checker_id = $pay['checker_id'];
        if ($checker_id != 0) {
            $checker_name = M()->query("select user_name from ypt_merchants_users where id=$checker_id");
            $pay['checker_name'] = $checker_name[0]['user_name'];
        } else {
            $pay['checker_name'] = "";
        }
        if (!$pay['jmt_remark']) {
            $pay['jmt_remark'] = "";
        }


        if ($pay['mode'] == 10) {
            $order = M('quick_pay')->where(array('order_sn' => $pay['remark']))->find();
            if ($status == '5') {
                $pay['integral_money'] = "0.00";
                $pay['discount'] = "100";
                $pay['user_money'] = "0.00";
                $pay['coupon_price'] = "0.00";
                $pay['total_price'] = $pay['price'];
            } elseif ($order) {
                $pay['integral_money'] = $order['credits_price'] ? $order['credits_price'] : "0.00"; // 积分
                $pay['discount'] = $order['discount'] ? (string)($order['discount'] * 10) : "100";      //折扣
                $pay['user_money'] = $order['yue_price'] ? $order['yue_price'] : "0.00"; //  会员卡储值
                $pay['coupon_price'] = $order['coupons_price'] ? $order['coupons_price'] : "0.00"; //  优惠券使用金额
                $pay['total_price'] = $order['order_price']; //总金额
                // M('log')->add(array('param'=>json_encode($pay)));
            } else {
                $pay['integral_money'] = "0.00";
                $pay['discount'] = "100";
                $pay['user_money'] = "0.00";
                $pay['coupon_price'] = "0.00";
                $pay['total_price'] = $pay['price'];
            }
        } elseif ($pay['mode'] == 12) {
            $order = M('user_recharge')->where(array('order_sn' => $pay['remark']))->find();
            if ($status == '5') {
                $pay['integral_money'] = "0.00";
                $pay['discount'] = "100";
                $pay['user_money'] = "0.00";
                $pay['coupon_price'] = "0.00";
                $pay['total_price'] = $pay['price'];
            } elseif ($order) {
                $pay['integral_money'] = "0.00"; // 积分
                $pay['discount'] = "100";      //折扣
                $pay['user_money'] = "0.00"; //  会员卡储值
                $pay['coupon_price'] = "0.00"; //  优惠券使用金额
                $pay['total_price'] = $order['total_price']; //总金额
                $pay['send_price'] = $order['send_price'];
            } else {
                $pay['integral_money'] = "0.00";
                $pay['discount'] = "100";
                $pay['user_money'] = "0.00";
                $pay['coupon_price'] = "0.00";
                $pay['total_price'] = $pay['price'];
            }

        } elseif ($pay['mode'] == 15) {

            $pay['integral_money'] = "0.00";
            $pay['discount'] = "100";
            $pay['user_money'] = "0.00";
            $pay['coupon_price'] = "0.00";
            $pay['total_price'] = $pay['price'];

        } else {
            $order = M("order")->where(array('order_sn' => $pay['remark']))->find();
            if ($status == '5') {
                $pay['integral_money'] = "0.00";
                $pay['discount'] = "100";
                $pay['user_money'] = "0.00";
                $pay['coupon_price'] = "0.00";
                $pay['total_price'] = $pay['price'];
            } elseif ($order) {
                $pay['integral_money'] = $order['integral_money'] ? $order['integral_money'] : "0.00"; // 积分
                $pay['discount'] = $order['discount'] ? $order['discount'] : "100";      //折扣
                $pay['user_money'] = $order['user_money'] ? $order['user_money'] : "0.00"; //  会员卡储值
                $pay['coupon_price'] = $order['coupon_price'] ? $order['coupon_price'] : "0.00"; //  优惠券使用金额
                $pay['total_price'] = $order['total_amount']; //总金额
            } else {
                $pay['integral_money'] = "0.00";
                $pay['discount'] = "100";
                $pay['user_money'] = "0.00";
                $pay['coupon_price'] = "0.00";
                $pay['total_price'] = $pay['price'];
            }
        }

        $pay['send_price'] = isset($pay['send_price']) ? $pay['send_price'] : "0.00";
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $pay));

    }

    //pos拉卡拉流水详情
    public function coin_detail_la()
    {
        $this->checkLogin();
        $remark = I("remark");
        $pay = $this->pays
            ->where(array('remark' => $remark))
            ->field("id,status,cate_id,customer_id,paystyle_id,checker_id,remark,paytime,jmt_remark,mode,new_order_sn,price,price_back,la_ka_la")
            ->find();
        $pay['is_cash'] = $this->cates->where(array('id' => $pay['cate_id']))->getField("is_cash") ?: 0;
        $checker_id = $pay['checker_id'];
        if ($checker_id != 0) {
            $checker_name = M()->query("select user_name from ypt_merchants_users where id=$checker_id");
            $pay['checker_name'] = $checker_name[0]['user_name'];
        } else {
            $pay['checker_name'] = "";
        }
        if (!$pay['jmt_remark']) {
            $pay['jmt_remark'] = "";
        }

        $order = M("order")->where(array('order_sn' => $pay['remark']))->find();
        if ($order) {
            $pay['integral_money'] = $order['integral_money'] ? $order['integral_money'] : "0.00"; // 积分
            $pay['discount'] = $order['discount'] ? $order['discount'] : "100";      //折扣
            $pay['user_money'] = $order['user_money'] ? $order['user_money'] : "0.00"; //  会员卡储值
            $pay['coupon_price'] = $order['coupon_price'] ? $order['coupon_price'] : "0.00"; //  优惠券使用金额
            $pay['total_price'] = $order['total_amount']; //总金额
        } else {
            $pay['integral_money'] = "0.00";
            $pay['discount'] = "100";
            $pay['user_money'] = "0.00";
            $pay['coupon_price'] = "0.00";
            $pay['total_price'] = $pay['price'];
        }

        $pay['send_price'] = isset($pay['send_price']) ? $pay['send_price'] : "0.00";
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $pay));
    }


//    台签
    public function cart()
    {
        $this->checkLogin();
        if ($this->version == "1.2") {
            $id = $this->get_merchant($this->id);
            $m_id = M()->query("select id FROM ypt_merchants where uid =$id");
            $m_id = $m_id[0]['id'];
            $cart = $this->cates->where("merchant_id=$m_id")->find();
            if (!$cart) {
                $this->ajaxReturn(array("code" => "error", "msg" => "失败", "data" => "还没有绑定优惠券"));
            }
            $cart['barcode_img'] = "http://sy.youngport.com.cn/" . $cart['barcode_img'];
            $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $cart));
        }
        if ($this->version == "1.3") {
            $this->cart1();
        }
    }

    public function cart1()
    {
        $m_info = $this->get_merchant1($this->id);
        $cart = $this->cates->where("merchant_id=" . $m_info['mid'])->find();
        if (!$cart) {
            $this->ajaxReturn(array("code" => "error", "msg" => "还没有绑定优惠券", "data" => "还没有绑定优惠券"));
        }
        $value = "https://" . $_SERVER['HTTP_HOST'] . "/index.php?g=Pay&m=Barcode&a=qrcode&id=" . $cart['id'] . "&checker_id=" . $m_info['checker'];
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array('url' => $value, 'message' => $cart)));
    }

//    版本1.3.3 台签
    public function cart2()
    {
        $this->checkLogin();
        $role_id = $this->roles->where(array("uid" => $this->id))->getField("role_id");
        if ($role_id == 3) {
            $mid = $this->merchants->where(array("uid" => $this->id))->getField("id");
            $cates_m = $cart = $this->cates
                ->where(array("merchant_id" => $mid, 'status' => 1, 'checker_id' => 0))
                ->field("id,no_number,cate_name,barcode_img,create_time,is_cash")
                ->order('create_time asc')
                ->limit(5)
                ->select();
            if (!$cates_m) {
                $this->ajaxReturn(array("code" => "error", "msg" => "该商户还未绑定台签"));
            } else {
                foreach ($cates_m as $k => &$v) {
                    $v['barcode_img'] = "http://sy.youngport.com.cn/" . $v['barcode_img'];
                    if (!$v['cate_name']) $v['cate_name'] = "默认台签";
                }
                $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $cates_m));
            }
        } else{
            //            收银员的情况
            $m_uid = $this->users->where(array('id' => $this->id))->getField("pid");
            $mid = $this->merchants->where(array("uid" => $m_uid))->getField("id");
            $cate_m = $this->cates->where(array("merchant_id" => $mid, 'status' => 1, 'checker_id' => 0))->find();
            if (!$cate_m) $this->ajaxReturn(array("code" => "error", "msg" => "该商户还未绑定台卡"));
            $cates_c = $cart = $this->cates
                ->where(array("merchant_id" => $mid, 'status' => 1, 'checker_id' => $this->id))
                ->field("id,no_number,cate_name,barcode_img,create_time,checker_id,is_cash")
                ->order('create_time asc')
                ->limit(5)
                ->select();
            if ($cates_c) {
                foreach ($cates_c as $k => &$v) {
                    $v['barcode_img'] = "http://sy.youngport.com.cn/" . $v['barcode_img'];
                    if (!$v['cate_name']) $v['cate_name'] = "默认台签";
                }
                $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $cates_c));
            } else {
                $cate_c_id = $this->cates->order("id desc")->getField("id") + 1;
                $seven = "000000" . $cate_c_id;
                $no_number = "YPTTQ" . substr($seven, -7);
                $path_url = "data/upload/pay/" . $no_number . ".png";
                $cate_m['id'] = $cate_c_id;
                $cate_m['checker_id'] = $this->id;
                $cate_m['no_number'] = $no_number;
                $cate_m['cate_name'] = "默认台签";
                $cate_m['barcode_img'] = $path_url;
                $cate_m['update_time'] = null;
                $cate_m['create_time'] = time();
                $this->add_cate_png($cate_c_id, $no_number);
                if ($this->cates->add($cate_m)) {

                    $cate_m['barcode_img'] = "http://sy.youngport.com.cn/" . $cate_m['barcode_img'];
                    $ab[] = $cate_m;
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $ab));
                }
                $this->ajaxReturn(array("code" => "error", "msg" => "EOF"));
            }
            $this->ajaxReturn(array("code" => "error", "msg" => "EOF"));
        }
        $this->ajaxReturn(array("code" => "error", "msg" => "EOF"));
    }

//   版本1.3.3 台签管理
    public function cart_list()
    {
        $this->checkLogin();
        $per_page = 10;
        $page = I("page") ? I("page") : 0;
        $role_id = $this->roles->where(array("uid" => $this->id))->getField("role_id");
        $today_time = $this->type_time(1);

        if ($role_id == 3) {
            $mid = $this->merchants->where(array("uid" => $this->id))->getField("id");
            $cates_m = $this->cates
                ->alias("c")
                ->join("left join __MERCHANTS_USERS__ u on u.id=c.checker_id")
                ->where(array("c.merchant_id" => $mid, 'c.status' => 1))
                ->limit($page * $per_page, $per_page)
                ->field("c.id,c.no_number,c.cate_name,c.barcode_img,c.checker_id,u.user_name")
                ->order('create_time asc')->select();

            if (!$cates_m) $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array(array('id' => '', 'no_number' => "", 'cate_name' => '', 'barcode_img' => '', 'checker_id' => '', 'user_name' => ''))));
            foreach ($cates_m as $k => &$v) {
                $v['barcode_img'] = "http://sy.youngport.com.cn/" . $v['barcode_img'];
                $total_pay = $this->pays->where(array('paytime' => array("between", $today_time), 'cate_id' => $v['id'], 'status' => 1))->sum("price");
                $v['total_pay'] = $total_pay ? $total_pay : "0.00";
                if (!$v['cate_name']) $v['cate_name'] = "默认台签";
                if (!$v['user_name']) $v['user_name'] = "";
            }

            $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $cates_m));
        } elseif ($role_id == 7) {
//            收银员的情况
            $m_uid = $this->users->where(array('id' => $this->id))->getField("pid");
            $mid = $this->merchants->where(array("uid" => $m_uid))->getField("id");
            $cate_m = $this->cates->where(array("merchant_id" => $mid, 'status' => 1, 'checker_id' => 0))->find();
            if (!$cate_m) $this->ajaxReturn(array("code" => "error", "msg" => "该商户还未绑定台卡"));
            $cates_c = $cart = $this->cates
                ->alias("c")
                ->join("left join __MERCHANTS_USERS__ u on u.id=c.checker_id")
                ->where(array("c.merchant_id" => $mid, 'c.status' => 1, 'c.checker_id' => $this->id))
                ->limit($page * $per_page, $per_page)
                ->field("c.id,c.no_number,c.cate_name,c.barcode_img,c.checker_id,u.user_name")
                ->order('create_time asc')->select();
//            判断分页进行滑动
            if (!$cates_c) $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array(array('id' => '', 'no_number' => "", 'cate_name' => '', 'barcode_img' => '', 'checker_id' => '', 'user_name' => ''))));
            if ($cates_c) {
                foreach ($cates_c as $k => &$v) {
                    $v['barcode_img'] = "http://sy.youngport.com.cn/" . $v['barcode_img'];
                    $total_pay = $this->pays->where(array('paytime' => array("between", $today_time), 'cate_id' => $v['id'], 'status' => 1))->sum("price");
                    $v['total_pay'] = $total_pay ? $total_pay : "0.00";
                    if (!$v['cate_name']) $v['cate_name'] = "默认台签";
                    if (!$v['user_name']) $v['user_name'] = "";
                }
                $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $cates_c));
            } else {
                $cate_c_id = $this->cates->order("id desc")->getField("id") + 1;
                $seven = "000000" . $cate_c_id;
                $no_number = "YPTTQ" . substr($seven, -7);
                $path_url = "data/upload/pay/" . $no_number . ".png";
                $cate_m['id'] = $cate_c_id;
                $cate_m['checker_id'] = $this->id;
                $cate_m['no_number'] = $no_number;
                $cate_m['cate_name'] = "默认台签";
                $cate_m['barcode_img'] = $path_url;
                $cate_m['update_time'] = null;
                $cate_m['create_time'] = time();
                $this->add_cate_png($cate_c_id, $no_number);
                if ($this->cates->add($cate_m)) {
                    $cate_m['barcode_img'] = "http://sy.youngport.com.cn/" . $cate_m['barcode_img'];
                    $ab[] = $cate_m;

                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $ab));
                }
                $this->ajaxReturn(array("code" => "error", "msg" => "EOF"));
            }
        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => "EOF"));
        }
    }

//   版本1.3.3 台签添加
    public function cart_add()
    {
        $this->checkLogin();
        $role_id = $this->roles->where(array("uid" => $this->id))->getField("role_id");
        $cate_name = I("cate_name");
        $cate_id = I("cate_id");
        $checker_id = I("checker_id",0);
        if (!$cate_name) $this->ajaxReturn(array("code" => "error", "msg" => "未填写台签名称"));
        if ($role_id == 3) { //商户
            $mid = $this->merchants->where(array("uid" => $this->id))->getField("id");
            $cate_m = $this->cates->where(array("merchant_id" => $mid, 'status' => 1, 'checker_id' => 0))->find();
            if (!$cate_m) {
                $agent_id = M('merchants_users')->where(array("id" => $this->id))->getField('agent_id');
                if (!in_array($agent_id, array(0, 1))) {
                    $cate_m['is_ypt'] = 1;
                }
                $cate_now = $this->cates->where(array("id" => $cate_id))->find();
                $cate_m['merchant_id'] = $mid;
                $cate_m['cate_name'] = $cate_name;
                $cate_m['checker_id'] = $checker_id;
                $cate_m['qz_number'] = $cate_now['qz_number'];
                $cate_m['no_number'] = $cate_now['no_number'];
                $cate_m['barcode_img'] = $cate_now['barcode_img'];
                $cate_m['update_time'] = time();
                $cate_m['status'] = 1;
                $cate_m['create_time'] = time();
                $this->cates->where(array("id" => $cate_id))->save($cate_m);
                $this->ajaxReturn(array("code" => "success", "msg" => "添加成功"));
            }
            unset($cate_m['id']);
            if ($cate_id) {//区分是否是扫台签
                $cate_now = $this->cates->where(array("id" => $cate_id))->find();
                $cate_m['merchant_id'] = $mid;
                $cate_m['cate_name'] = $cate_name;
                $cate_m['checker_id'] = $checker_id;
                $cate_m['qz_number'] = $cate_now['qz_number'];
                $cate_m['no_number'] = $cate_now['no_number'];
                $cate_m['barcode_img'] = $cate_now['barcode_img'];
                $cate_m['update_time'] = time();
                $cate_m['status'] = 1;
                $cate_m['create_time'] = time();
                $this->cates->where(array("id" => $cate_id))->save($cate_m);
                $this->ajaxReturn(array("code" => "success", "msg" => "添加成功"));
            } else {
                $cate_c_id = $this->cates->order("id desc")->getField("id") + 1;
                $seven = "000000" . $cate_c_id;
                $no_number = "YPTTQ" . substr($seven, -7);
                $path_url = "data/upload/pay/" . $no_number . ".png";
                $cate_m['id'] = $cate_c_id;
                $cate_m['checker_id'] = $checker_id;
                $cate_m['merchant_id'] = $mid;
                $cate_m['no_number'] = $no_number;
                $cate_m['cate_name'] = $cate_name;
                $cate_m['barcode_img'] = $path_url;
                $cate_m['status'] = 1;
                $cate_m['update_time'] = time();;
                $cate_m['create_time'] = time();
                $this->add_cate_png($cate_c_id, $no_number);
                if ($this->cates->add($cate_m)) {
                    $this->ajaxReturn(array("code" => "success", "msg" => "添加成功"));
                }
            }
            $this->ajaxReturn(array("code" => "error", "msg" => "绑定失败"));
        } elseif ($role_id == 7) {//收银员
            if ($cate_id) {
                $m_uid = $this->users->where(array('id' => $this->id))->getField("pid");
                $mid = $this->merchants->where(array("uid" => $m_uid))->getField("id");
                $cate_m = $this->cates->where(array("merchant_id" => $mid, 'status' => 1, 'checker_id' => 0))->find();
                if (!$cate_m) $this->ajaxReturn(array("code" => "error", "msg" => "该商户还未添加台签"));
                $cate_now = $this->cates->where(array("id" => $cate_id))->find();
                unset($cate_m['id']);
                $cate_m['merchant_id'] = $mid;
                $cate_m['cate_name'] = $cate_name;
                $cate_m['checker_id'] = $this->id;
                $cate_m['qz_number'] = $cate_now['qz_number'];
                $cate_m['no_number'] = $cate_now['no_number'];
                $cate_m['barcode_img'] = $cate_now['barcode_img'];
                $cate_m['update_time'] = time();
                $cate_m['status'] = 1;
                $cate_m['create_time'] = time();
                $this->cates->where(array("id" => $cate_id))->save($cate_m);
                $this->ajaxReturn(array("code" => "success", "msg" => "添加成功"));

            } else {
                $this->ajaxReturn(array("code" => "error", "msg" => "收银员绑定失败"));
                $m_uid = $this->users->where(array('id' => $this->id))->getField("pid");
                $mid = $this->merchants->where(array("uid" => $m_uid))->getField("id");
                $cate_m = $this->cates->where(array("merchant_id" => $mid, 'status' => 1, 'checker_id' => 0))->find();
                unset($cate_m['id']);
                $cate_c_id = $this->cates->order("id desc")->getField("id") + 1;
                $seven = "000000" . $cate_c_id;
                $no_number = "YPTTQ" . substr($seven, -7);
                $path_url = "data/upload/pay/" . $no_number . ".png";
                $cate_m['id'] = $cate_c_id;
                $cate_m['checker_id'] = $this->id;
                $cate_m['merchant_id'] = $mid;
                $cate_m['no_number'] = $no_number;
                $cate_m['cate_name'] = $cate_name;
                $cate_m['barcode_img'] = $path_url;
                $cate_m['status'] = 1;
                $cate_m['update_time'] = null;
                $cate_m['create_time'] = time();
                $this->add_cate_png($cate_c_id, $no_number);
                if ($this->cates->add($cate_m)) {
                    $this->ajaxReturn(array("code" => "success", "msg" => "添加成功"));
                }
            }
        } elseif ($role_id == 2 || $this->userInfo['pid']==2) {//代理或者代理员工给商户添加台签
            ($mid = I('mid')) || $this->ajaxReturn(array("code" => "error", "msg" => "mid is empty"));
            $cate_m = $this->cates->where(array("merchant_id" => $mid, 'status' => 1, 'checker_id' => 0))->find();
            if (!$cate_m) {
                $agent_id = $role_id==2 ? $this->userId : M('merchants_users')->where(array('id'=>$this->userId))->getField('agent_id');
                if (!in_array($agent_id, array(0, 1))) {
                    $cate_m['is_ypt'] = 1;
                }
                $cate_now = $this->cates->where(array("id" => $cate_id))->find();
                $cate_m['merchant_id'] = $mid;
                $cate_m['cate_name'] = $cate_name;
                $cate_m['checker_id'] = $checker_id;
                $cate_m['qz_number'] = $cate_now['qz_number'];
                $cate_m['no_number'] = $cate_now['no_number'];
                $cate_m['barcode_img'] = $cate_now['barcode_img'];
                $cate_m['update_time'] = time();
                $cate_m['status'] = 1;
                $cate_m['create_time'] = time();
                $this->cates->where(array("id" => $cate_id))->save($cate_m);
                $this->ajaxReturn(array("code" => "success", "msg" => "添加成功"));
            }
            unset($cate_m['id']);
            if ($cate_id) {//区分是否是扫台签
                $cate_now = $this->cates->where(array("id" => $cate_id))->find();
                $cate_m['merchant_id'] = $mid;
                $cate_m['cate_name'] = $cate_name;
                $cate_m['checker_id'] = $checker_id;
                $cate_m['qz_number'] = $cate_now['qz_number'];
                $cate_m['no_number'] = $cate_now['no_number'];
                $cate_m['barcode_img'] = $cate_now['barcode_img'];
                $cate_m['update_time'] = time();
                $cate_m['status'] = 1;
                $cate_m['create_time'] = time();
                $this->cates->where(array("id" => $cate_id))->save($cate_m);
                $this->ajaxReturn(array("code" => "success", "msg" => "添加成功"));
            } else {
                $cate_c_id = $this->cates->order("id desc")->getField("id") + 1;
                $seven = "000000" . $cate_c_id;
                $no_number = "YPTTQ" . substr($seven, -7);
                $path_url = "data/upload/pay/" . $no_number . ".png";
                $cate_m['id'] = $cate_c_id;
                $cate_m['checker_id'] = $checker_id;
                $cate_m['merchant_id'] = $mid;
                $cate_m['no_number'] = $no_number;
                $cate_m['cate_name'] = $cate_name;
                $cate_m['barcode_img'] = $path_url;
                $cate_m['status'] = 1;
                $cate_m['update_time'] = time();;
                $cate_m['create_time'] = time();
                $this->add_cate_png($cate_c_id, $no_number);
                if ($this->cates->add($cate_m)) {
                    $this->ajaxReturn(array("code" => "success", "msg" => "添加成功"));
                }
            }
            $this->ajaxReturn(array("code" => "error", "msg" => "绑定失败"));
        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => "角色验证失败"));

        }
    }

//   版本1.3.3 台签编辑
    public function cart_edit()
    {
        $this->checkLogin();
        $data['cate_name'] = I("cate_name");
        $cate_id = I("cate_id");
        if (!$this->cates->where(array("id" => $cate_id))->find()) $this->ajaxReturn(array("code" => "error", "msg" => "失败", "data" => "台签不存在"));
        $data['checker_id'] = I("checker_id") ? I("checker_id") : 0;
        $this->cates->where(array("id" => $cate_id))->save($data);
        $this->ajaxReturn(array("code" => "success", "msg" => "编辑成功"));

    }

//   版本1.3.3 扫台签
    public function cart_code()
    {
        $this->checkLogin();
        $role_id = $this->roles->where(array("uid" => $this->id))->getField("role_id");
        $cate_id = I("cate_id");
        $cate = $this->cates->where(array("id" => $cate_id))->field("id,no_number,status")->find();
        if (!$cate) {
            $this->ajaxReturn(array("code" => "error", "msg" => "请使用有效的二维码"));
        } else if ($cate['merchant_id']) {
            $this->ajaxReturn(array("code" => "error", "msg" => "该台签已被绑定"));
        } else if ($role_id != 3 && $role_id != 7) {

            $this->ajaxReturn(array("code" => "error", "msg" => "权限不足"));
        } else {
            $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $cate));
        }
    }

//    台签绑定
    public function cart_blind()
    {

    }

//  版本1.3.3 台签流水
    public function cart_coin()
    {
        $this->checkLogin();
        $cate_id = I("cate_id");
        if (!$cate_id) $this->ajaxReturn(array("code" => "error", "msg" => "cate_id is empty", "data" => "EOF"));
        if (!I("begin_time")) $this->ajaxReturn(array("code" => "error", "msg" => "begin_time is empty", "data" => "EOF"));
        if (!I("end_time")) $this->ajaxReturn(array("code" => "error", "msg" => "end_time is empty", "data" => "EOF"));
        $type = I("type");
        if ($type == 7) {
            $begin_time = strtotime(I("begin_time"));  //今天开始0点
            $end_time = strtotime(I("end_time"));
            $number = ($end_time - $begin_time) / 24 / 60 / 60 + 1;
            $time = $end_time + 24 * 60 * 60 - 1;              //今天结束23:59:59
            $time_detail = array($begin_time, $time);
        } else {
            $number = $this->get_number($type);
            $time = $this->type_time($type);
            $time_detail = $time;
            $time = $time[1];
        }

        $detail = $this->cate_detail($cate_id, $time_detail);
        $total = array();
        for ($i = 1; $i <= $number; $i++) {
            $time_now = $this->day_detail($time, $i);
            $total[] = $this->count_cate_day($cate_id, $time_now);
        }
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array('total' => $total, 'detail' => $detail)));

    }

    //  版本1.3.4 台签流水
    public function cart_coin1()
    {
        $this->checkLogin();
        $cate_id = I("cate_id");
        if (!$cate_id) $this->ajaxReturn(array("code" => "error", "msg" => "失败", "data" => "EOF"));
        if (!I("begin_time")) $this->ajaxReturn(array("code" => "error", "msg" => "失败", "data" => "EOF"));
        if (!I("end_time")) $this->ajaxReturn(array("code" => "error", "msg" => "失败", "data" => "EOF"));
        $type = I("type");
        if ($type == 7) {
            $begin_time = strtotime(I("begin_time"));  //今天开始0点
            $end_time = strtotime(I("end_time"));
            $number = ($end_time - $begin_time) / 24 / 60 / 60 + 1;
            $time = $end_time + 24 * 60 * 60 - 1;              //今天结束23:59:59
            $time_detail = array($begin_time, $time);
        } else {
            $number = $this->get_number($type);
            $time = $this->type_time($type);
            $time_detail = $time;
            $time = $time[1];
        }

        $detail = $this->cate_detail1($cate_id, $time_detail);
        $total = array();
        for ($i = 1; $i <= $number; $i++) {
            $time_now = $this->day_detail($time, $i);
            $total[] = $this->count_cate_day1($cate_id, $time_now);
        }
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array('total' => $total, 'detail' => $detail)));

    }


//    收银员流水
    public function customer_coin()
    {
        if ($this->version == "1.2") {
            $this->checkLogin();
            $id = I("id") ? I("id") : $this->id;
            $type = I("type");
            $paystyle = I("paystyle");
            $status = I("status");
            $time = $this->type_time($type);
            $pays = $this->customer_detail($id, $time, $paystyle, $status);
            $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $pays));
        }
        if ($this->version == "1.3") {
            $this->coin1();
        }
    }

//    报表一
    public function excel()
    {
        $this->checkLogin();
        if ($this->version == "1.2") {
            $id = $this->get_merchant($this->id);
            $type = I("type");
            $time = $this->type_month($type);
            $data = $this->count_merchant($id, $time, 1);
            $data['tab1'] = $this->host . "/index.php?g=Api&m=Shopnews&a=excel_total&type=" . $type . "&id=" . $id;
            $data['tab2'] = $this->host . "/index.php?g=Api&m=Shopnews&a=excel_number&type=" . $type . "&id=" . $id;
            $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $data));
        } elseif ($this->version >= "1.3" && $this->version <= '1.8') {
            $this->excel1();
        } elseif ($this->version > '1.8') {
            $this->excel1();
        }
    }

    public function excel1()
    {
        $m_info = $this->get_merchant1($this->id);
        $type = I("type");
        $time = $this->type_month($type);
        $data = $this->count_merchant1($m_info, $time, 1);
        $data['tab1'] = $this->host . "/index.php?g=Api&m=Shopnews&a=excel_total1&type=" . $type . "&id=" . $this->id;
        $data['tab2'] = $this->host . "/index.php?g=Api&m=Shopnews&a=excel_number1&type=" . $type . "&id=" . $this->id;
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $data));
    }

//    报表一  总值
    public function excel_total()
    {
        $id = I("id");
        $type = I("type");
        $time = $this->type_month($type);
        $data = $this->count_merchant($id, $time, 1);
//        dump($data);
        $data = json_encode($data);
        $this->assign('data', $data);
        $this->display();
    }

    public function excel_total1()
    {
        $m_info = $this->get_merchant1(I("id"));
        $type = I("type");
        $time = $this->type_month($type);
        $data = $this->count_merchant1($m_info, $time, 1);
//        dump($data);
        $data = json_encode($data);
        $this->assign('data', $data);
        $this->display();
    }

//    商户 报表一 交易数量比较
    public function excel_number()
    {
        $id = I("id");
        $type = I("type");
        $time = $this->type_month($type);
        $data = $this->count_merchant($id, $time, 1);
//        dump($data);
        $data = json_encode($data);
        $this->assign('data', $data);
        $this->display();
    }

    public function excel_number1()
    {
        $m_info = $this->get_merchant1(I("id"));
        $type = I("type");
        $time = $this->type_month($type);
        $data = $this->count_merchant1($m_info, $time, 1);
//        dump($data);
        $data = json_encode($data);
        $this->assign('data', $data);
        $this->display();
    }

//    报表二
    public function excel_detail()
    {
        $this->checkLogin();
        if ($this->version == "1.2") {
            $id = $this->get_merchant($this->id);
            $type = I("type");
            $time = $this->get_mark($type);
            $count = array();
            foreach ($time[0] as $k => $v) {
                $array = array($time[0][$k]['begin_time'], $time[0][$k]['end_time']);
                $count[$k]['pay'] = $this->count_merchant($id, $array, 1);
                $count[$k]['time'] = date("n.d", $v['begin_time']) . "~" . date("n.d", $v['end_time']);
            }
            $data['tab1'] = $this->host . "/index.php?g=Api&m=Shopnews&a=excel_total_detail&type=" . $type . "&id=" . $id;
            $data['tab2'] = $this->host . "/index.php?g=Api&m=Shopnews&a=excel_number_detail&type=" . $type . "&id=" . $id;
            $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $data));
        } elseif ($this->version >= "1.3") {
            $this->excel_detail1();
        }
    }

    public function excel_detail1()
    {
        $m_info = $this->get_merchant1($this->id);
        $type = I("type");
        $time = $this->get_mark($type);
        $count = array();
        foreach ($time[0] as $k => $v) {
            $array = array($time[0][$k]['begin_time'], $time[0][$k]['end_time']);
            $count[$k]['pay'] = $this->count_merchant1($m_info, $array, 1);
            $count[$k]['time'] = date("n.d", $v['begin_time']) . "~" . date("n.d", $v['end_time']);
        }
        $data['tab1'] = $this->host . "/index.php?g=Api&m=Shopnews&a=excel_total_detail1&type=" . $type . "&id=" . $this->id;
        $data['tab2'] = $this->host . "/index.php?g=Api&m=Shopnews&a=excel_number_detail1&type=" . $type . "&id=" . $this->id;
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $data));
    }

    //    商户报表二 交易总额
    public function excel_total_detail()
    {
        $id = I("id");
        $type = I("type");
        $time = $this->get_mark($type);
        $count = array();
        foreach ($time[0] as $k => $v) {
            $array = array($time[0][$k]['begin_time'], $time[0][$k]['end_time']);
            $count[$k]['pay'] = $this->count_merchant($id, $array, 1);
            $count[$k]['time'] = date("n.d", $v['begin_time']) . "~" . date("n.d", $v['end_time']);
        }
        $count = json_encode($count);
        $this->assign("count", $count);
        $this->display();
    }

    public function excel_total_detail1()
    {
        $m_info = $this->get_merchant1(I("id"));
        $type = I("type");
        $time = $this->get_mark($type);
        $count = array();
        foreach ($time[0] as $k => $v) {
            $array = array($time[0][$k]['begin_time'], $time[0][$k]['end_time']);
            $count[$k]['pay'] = $this->count_merchant1($m_info, $array, 1);
            $count[$k]['time'] = date("n.d", $v['begin_time']) . "~" . date("n.d", $v['end_time']);
        }
        $count = json_encode($count);
        $this->assign("count", $count);
        $this->display();
    }


    //    商户报表二 交易总数量
    public function excel_number_detail()
    {
        $id = I("id");
        $type = I("type");
        $time = $this->get_mark($type);
        $count = array();
        foreach ($time[0] as $k => $v) {
            $array = array($time[0][$k]['begin_time'], $time[0][$k]['end_time']);
            $count[$k]['pay'] = $this->count_merchant($id, $array, 1);
            $count[$k]['time'] = date("n.d", $v['begin_time']) . "~" . date("n.d", $v['end_time']);
        }
        $count = json_encode($count);
        $this->assign("count", $count);
        $this->display();
    }

    public function excel_number_detail1()
    {
        $m_info = $this->get_merchant1(I("id"));
        $type = I("type");
        $time = $this->get_mark($type);
        $count = array();
        foreach ($time[0] as $k => $v) {
            $array = array($time[0][$k]['begin_time'], $time[0][$k]['end_time']);
            $count[$k]['pay'] = $this->count_merchant1($m_info, $array, 1);
            $count[$k]['time'] = date("n.d", $v['begin_time']) . "~" . date("n.d", $v['end_time']);
        }
        $count = json_encode($count);
        $this->assign("count", $count);
        $this->display();
    }

    //    台签流水
    public function cate_detail($cate_id, $time_detail)
    {
        $map['cate_id'] = $cate_id;
        $map['paytime'] = array('between', $time_detail);
        $map['status'] = array('in', array(1, 2));
        $pays = $this->pays->where($map)->field('id,paytime,price,status,paystyle_id,remark')->order("paytime desc")->select();
        return $pays;
    }

    //    1.3.4台签流水
    public function cate_detail1($cate_id, $time_detail)
    {
        $map['cate_id'] = $where['cate_id'] = $cate_id;
        $map['paytime'] = $where['paytime'] = array('between', $time_detail);
        $map['status'] = array('in', array(1, 2));
        $where['status'] = 5;
        $pays = $this->pays->where($map)->field('id,paytime,price,status,mode,paystyle_id,remark,checker_id,bill_date')->order("paytime desc")->select();
        $payBack = $this->payBack->where($where)->field('id,paytime,price_back as price,mode,status,paystyle_id,remark,checker_id,bill_date')->order("paytime desc")->select();
        if ($payBack) {
            foreach ($payBack as $k => $v)
                array_push($pays, $payBack[$k]);
        }
        return $pays;
    }

//    台签流水汇总
    public function count_cate_day($cate_id, $time)
    {
        $map['cate_id'] = $cate_id;
        $map['paytime'] = array('between', $time);
        $map['status'] = array('in', array(1, 2));
        $filed = "ifnull(sum( if(status =1,price, 0)),0) as pay_money ,ifnull(sum( if(status =2,price_back, 0)),0) as back_money,paytime";
        $pays_total = $this->pays->where($map)->field($filed)->find();
        if ($pays_total['pay_money'] == null || $pays_total['pay_money'] == 0) {
            return array("pay_money" => "0", "back_money" => $pays_total['back_money'], "paytime" => "$time[0]");
        }
        return $pays_total;
    }

    //    1.3.4台签流水汇总
    public function count_cate_day1($cate_id, $time)
    {
        $map['p.cate_id'] = $cate_id;
        $map['p.paytime'] = array('between', $time);
        $map['p.status'] = array('in', array(1, 2));
        $filed = "ifnull(sum( if(p.status=1 or p.status=2,p.price, 0)),0) as pay_money ,ifnull(sum(b.price_back),0) as back_money,p.paytime";
        $pays_total = $this->pays->alias('p')
            ->join("left join __PAY_BACK__ b on b.back_pid=p.id")
            ->where($map)
            ->field($filed)
            ->find();
        if ($pays_total['pay_money'] == null || $pays_total['pay_money'] == 0) {
            return array("pay_money" => "0", "back_money" => $pays_total['back_money'], "paytime" => "$time[0]");
        }
        return $pays_total;
    }

    /**
     * 版本 1.2
     * @param $id  用户表里面的id
     * @param $time 按时间区分
     * @param $is_detail 是否需要微信和支付宝支付的细节
     * 返回该商户交易的总额
     */
    public function count_merchant($id, $time = "", $is_detail = 0)
    {
        if ($time != "") $map['paytime'] = array("between", $time);
        $map['uid'] = $id;
//        $map['p.status']=1;
        if ($is_detail == 1) {
            $field = "p.paytime,ifnull(sum(if( p.status =1,p.price, 0)),0) as total_price,ifnull(sum( if(p.status=1, 1, 0)),0) as total_num,
            ifnull(sum( if( p.paystyle_id =1 And p.status=1, 1, 0)),0) as per_weixin_num,ifnull(sum( if( p.paystyle_id =2 And p.status=1, 1, 0)),0) as per_ali_num, ifnull(sum( if( p.paystyle_id =5 And p.status=1, 1, 0)),0) as per_cash_num,
            ifnull(sum( if( p.paystyle_id =1 And p.status=1,p.price, 0)),0) as per_wei_price,ifnull(sum( if( p.paystyle_id =2 And p.status=1,p.price, 0)),0) as per_ali_price,ifnull(sum( if( p.paystyle_id =5 And p.status=1,p.price, 0)),0) as per_cash_price";
        } else {
            $field = "p.paytime,ifnull(sum( if(p.status=1, 1, 0)),0) as total_num,ifnull(sum(if( p.status =1,p.price, 0)),0) as total_price";
        }
        $pay = $this->merchants->alias("m")
            ->join("right join __PAY__ p on p.merchant_id=m.id")
            ->field($field)
            ->where($map)
            ->find();
        return $pay;
    }

    /**
     * 版本 1.3
     * @param $id  用户表里面的id
     * @param $time 按时间区分
     * @param $is_detail 是否需要微信和支付宝支付的细节
     * 返回该商户交易的总额
     */
    public function count_merchant1($m_info, $time = "", $is_detail = 0)
    {
        if ($time != "") $map['paytime'] = array("between", $time);
        if ($m_info['is_all'] == 1) {
            $map['m.id'] = $m_info['mid'];
        } else {
            $map['m.id'] = $m_info['mid'];
            $map['p.checker_id'] = $m_info['checker'];
        }
//        $map['p.status']=1;
        if ($is_detail == 1) {
            $field = "p.paytime,ifnull(sum(if( p.status =1,p.price, 0)),0) as total_price,ifnull(sum( if(p.status=1, 1, 0)),0) as total_num,
            ifnull(sum( if( p.paystyle_id =1 And p.status=1, 1, 0)),0) as per_weixin_num,ifnull(sum( if( p.paystyle_id =2 And p.status=1, 1, 0)),0) as per_ali_num, ifnull(sum( if( p.paystyle_id =5 And p.status=1, 1, 0)),0) as per_cash_num,
            ifnull(sum( if( p.paystyle_id =1 And p.status=1,p.price, 0)),0) as per_wei_price,ifnull(sum( if( p.paystyle_id =2 And p.status=1,p.price, 0)),0) as per_ali_price,ifnull(sum( if( p.paystyle_id =5 And p.status=1,p.price, 0)),0) as per_cash_price";
        } else {
            $field = "p.paytime,ifnull(sum( if(p.status=1, 1, 0)),0) as total_num,ifnull(sum(if( p.status =1,p.price, 0)),0) as total_price";
        }
        $pay = $this->merchants->alias("m")
            ->join("right join __PAY__ p on p.merchant_id=m.id")
            ->field($field)
            ->where($map)
            ->find();
        return $pay;
    }

    //1.3.4
    public function count_merchant2($m_info, $time = "", $is_detail = 0)
    {
        if ($time != "") $map['p.paytime'] = array("between", $time);
        if ($m_info['is_all'] == 1) {
            $map['m.id'] = $m_info['mid'];
        } else {
            $map['m.id'] = $m_info['mid'];
            $map['p.checker_id'] = $m_info['checker'];
        }
        $map['p.status'] = array('in', array(1, 2, 5));
        $pays = $this->merchants->alias('m')
            ->join("__PAY__ p on p.merchant_id=m.id")
            ->where($map)
            ->field("p.price,p.status,p.paytime")
            ->select();
        $payBack = $this->merchants->alias('m')
            ->join("__PAY_BACK__ p on p.merchant_id=m.id")
            ->where($map)
            ->field("p.price_back as price,p.status,p.paytime")
            ->select();
        if ($payBack) {
            foreach ($payBack as $k => $v)
                array_push($pays, $payBack[$k]);
        }
        $total_price = 0;
        foreach ($pays as $k => $v) {
            if ($v['status'] == 5) {
                $total_price -= $v['price'] * 100;
            } else {
                $total_price += $v['price'] * 100;
            }
        }
        return array('paytime' => $pays[0]['paytime'], 'total_num' => strval(count($pays)), 'total_price' => strval($total_price / 100));
    }

    /**
     * 版本1.2
     * @param $id   商户的id
     * @param $time  时间区间
     * return 返回商户所有的流水
     */
    public function merchant_detail($id, $time, $paystyle = "", $status = "")
    {
        $map['u.id'] = $id;
        if ($time != null) $map['p.paytime'] = array("between", $time);
        if ($paystyle !== "") $map['paystyle_id'] = $paystyle;
        if ($status !== "") {
            $map['p.status'] = $status;
        } else {
            $map['p.status'] = array('in', array(1, 2, 3, 4));
        }
        $pays = $this->users->alias("u")
            ->join("__MERCHANTS__ m on m.uid=u.id")
            ->join("__PAY__ p on p.merchant_id=m.id")
            ->order("paytime desc")
            ->where($map)
            ->field("p.id,p.paystyle_id,p.checker_id,p.price,p.remark,p.status,p.paytime")
            ->select();
        return $pays;
    }

    /**
     * 版本1.3
     * @param $id   商户的id
     * @param $time  时间区间
     * return 返回商户所有的流水
     */
    public function merchant_detail1($m_info, $checker_id, $time, $paystyle = "", $status = "", $mode = "")
    {
        if ($m_info['is_all'] == "0") {
            $map['p.checker_id'] = $m_info['checker'];  //收银权限为看自己
        }
        if ($checker_id) {
            $map['p.checker_id'] = $checker_id; //商户选取收银员
        }
        $map['m.id'] = $m_info['mid'];
        if ($time != null) $map['p.paytime'] = array("between", $time);
        if ($paystyle !== "") $map['paystyle_id'] = $paystyle;
        if ($status !== "") $map['p.status'] = $status;
        if ($mode !== "") $map['p.mode'] = $mode;
        $pays = $this->users->alias("u")
            ->join("__MERCHANTS__ m on m.uid=u.id")
            ->join("__PAY__ p on p.merchant_id=m.id")
            ->order("paytime desc")
            ->where($map)
            ->field("p.id,p.paystyle_id,p.checker_id,p.price,p.remark,p.status,p.paytime,p.bill_date")
            ->select();
        return $pays;
    }

    /**
     * 版本1.3
     * @param $id   商户的id
     * @param $time  时间区间
     * return 返回商户所有的流水
     */
    public function count_merchant_detail1($m_info, $checker_id, $time, $paystyle = "", $status = "", $mode = "")
    {
        if ($m_info['is_all'] == "0") {
            $map['p.checker_id'] = $m_info['checker'];  //收银权限为看自己
        }
        if ($checker_id) {
            $map['p.checker_id'] = $checker_id; //商户选取收银员
        }
        $map['m.id'] = $m_info['mid'];
        if ($time != null) $map['p.paytime'] = array("between", $time);
        if ($paystyle !== "") $map['paystyle_id'] = $paystyle;
        if ($status !== "") $map['p.status'] = $status;
        if ($mode !== "") $map['p.mode'] = $mode;
        $filed = "sum( if( p.status =1, p.price, 0)) as pay_money ,sum( if( p.status =2, p.price_back, 0)) as back_money,p.paytime";
        $pays = $this->users->alias("u")
            ->join("__MERCHANTS__ m on m.uid=u.id")
            ->join("__PAY__ p on p.merchant_id=m.id")
            ->order("paytime desc")
            ->where($map)
            ->field($filed)
            ->find();
        if ($pays['pay_money'] == null) {
            return array("pay_money" => "0", "back_money" => "0", "paytime" => "$time[0]");
        }
        return $pays;
    }

    /**
     * 版本1.3.4
     * @param $time  时间区间
     * return 返回商户所有的流水
     */
    public function merchant_detail2($m_info, $checker_id, $time, $paystyle = "", $status = "", $mode = "", $cz_style)
    {
        if ($m_info['is_all'] == "0") {
            $map['p.checker_id'] = $m_info['checker'];  //收银权限为看自己
        }
        if ($checker_id) {
            $map['p.checker_id'] = $checker_id; //商户选取收银员
        }
        $map['m.id'] = $m_info['mid'];
        if ($time != null) $map['p.paytime'] = array("between", $time);
        if ($paystyle !== "") $map['paystyle_id'] = $paystyle;
        if ($status) {
            $map['p.status'] = $status;
        } else {
            $map['p.status'] = array('gt', 0);
        }
        if ($mode !== "") $map['p.mode'] = $mode;

        if ($cz_style) {
            $code_list = $this->get_card_code($cz_style);
            if ($code_list) {
                $map['o.card_code'] = array('in', $code_list);
            } else {
                return array();
            }
        }
        $pays = $this->users->alias("u")
            ->join("__MERCHANTS__ m on m.uid=u.id")
            ->join("__PAY__ p on p.merchant_id=m.id")
            ->join("__ORDER__ o on o.order_sn=p.remark", 'left')
            ->order("paytime desc")
            ->where($map)
            ->field("p.id,p.paystyle_id,p.checker_id,p.price,p.remark,p.status,p.paytime,p.bill_date,p.mode,p.authorization")
            ->select();
        // echo $this->users->getLastSql();die;
        $payBack = $this->users->alias("u")
            ->join("__MERCHANTS__ m on m.uid=u.id")
            ->join("__PAY_BACK__ p on p.merchant_id=m.id")
            ->join("__ORDER__ o on o.order_sn=p.remark", 'left')
            ->order("paytime desc")
            ->where($map)
            ->field("p.back_pid as id,p.paystyle_id,p.checker_id,p.price_back as price,p.remark,p.status,p.paytime,p.bill_date,p.mode,p.type")
            ->select();
        if ($payBack) {
            foreach ($payBack as $k => $v)
                array_push($pays, $payBack[$k]);
        }
        foreach ($pays as &$v) {
            $v['mode_name'] = $this->numberstyle($v['mode']);
        }
        return $pays;
    }
    //支付样式判断
    function numberstyle($number)
    {
        switch ($number) {
            case 0:
                return "台签";
            case 1:
                return "App扫码";
            case 2:
                return "App主扫";
            case 3:
                return "收银扫码";
            case 4:
                return "收银现金";
            case 5:
                return "Pos机主扫";
            case 6:
                return "Pos机扫码";
            case 7:
                return "pos机现金";
            case 8:
                return "pos机其他";
            case 9:
                return "pos机刷银行卡";
            case 10:
                return "快速支付";
            case 11:
                return "小程序";
            case 12:
                return "会员卡充值";
            case 13:
                return "收银APP现金";
            case 14:
                return "收银APP余额";
            case 15:
                return "小白盒";
            case 16:
                return "台签余额";
            case 17:
                return "双屏主扫";
            case 18:
                return "双屏余额";
            case 19:
                return "Pos机余额";
            case 20:
                return "小程序余额";
            case 21:
                return "波普主扫";
            case 22:
                return "波普扫码";
            case 23:
                return "波普刷银行卡";
            case 24:
                return "波普余额";
            case 25:
                return "商+宝主扫";
            case 26:
                return "api接口订单";
            case 27:
                return "商+宝余额";
            case 98:
                return "原路退款";
            case 99:
                return "现金退款";
            default:
                return "其他支付";
        }
    }

    /**
     * 版本1.3.4
     * @param $id   商户的id
     * @param $time  时间区间
     * return 返回商户所有的流水
     */
    public function count_merchant_detail2($m_info, $checker_id, $time, $paystyle = "", $status = "", $mode = "", $cz_style)
    {
        if ($m_info['is_all'] == "0") {
            $map['p.checker_id'] = $m_info['checker'];  //收银权限为看自己
        }
        if ($checker_id) {
            $map['p.checker_id'] = $checker_id; //商户选取收银员
        }
        $map['m.id'] = $m_info['mid'];
        if ($time != null) $map['p.paytime'] = array("between", $time);
        if ($paystyle !== "") $map['p.paystyle_id'] = $paystyle;
        if ($status !== "") $map['p.status'] = $status;
        if ($mode !== "") $map['p.mode'] = $mode;
        if ($cz_style) {
            $code_list = $this->get_card_code($cz_style);
            if ($code_list) {
                $map['o.card_code'] = array('in', $code_list);
            } else {
                return array('back_money' => '0', 'pay_money' => '0', 'paytime' => '0');
            }
        }
        //$filed="sum( if( p.status =1, p.price, 0)) as pay_money ,sum( if( p.status =2, p.price_back, 0)) as back_money,p.paytime";
        $filed = "sum( if( p.status between 1 and 4, p.price, 0)) as pay_money,p.paytime";
        $pays = $this->users->alias("u")
            ->join("__MERCHANTS__ m on m.uid=u.id")
            ->join("__PAY__ p on p.merchant_id=m.id")
            ->join("__ORDER__ o on o.order_sn=p.remark", 'left')
            ->order("paytime desc")
            ->where($map)
            ->field($filed)
            ->find();
        $payBack = $this->users->alias("u")
            ->join("__MERCHANTS__ m on m.uid=u.id")
            ->join("__PAY_BACK__ p on p.merchant_id=m.id")
            ->join("__ORDER__ o on o.order_sn=p.remark", 'left')
            ->order("paytime desc")
            ->where($map)
            ->field('paystyle_id,price,price_back,bill_date,paytime')
            ->field('sum(p.price_back) as back_money,paytime')
            ->find();
        if ($payBack) $pays['back_money'] = $payBack['back_money'];
        if ($pays['pay_money'] == null) $pays['pay_money'] = '0';
        if ($pays['back_money'] == null) $pays['back_money'] = '0';
        if ($pays['paytime'] == null) $pays['paytime'] = $payBack['paytime'] ?: '0';
        return $pays;
    }

    /**
     * @param $status_type
     * @return array  获取支付状态
     */
    public function get_status($status_type)
    {
        if ($status_type == "") {
            return array('in', array(1, 2, 3, 4, 5, 6));
        }
        if ($status_type == "1") {
            return array('in', array(1));
        }
        if ($status_type == "2") {
            return array('in', array(2, 3, 4));
        }
    }

    /**
     * @param $mode
     * @return array  获取支付状态
     */
    public function get_mode($mode)
    {
        if ($mode == "") {
            return "";
        }
        if ($mode == "0") {
            return array('in', array(0, 16));;
        }
        if ($mode == "1") {
            return array('in', array(1, 2, 13, 14));
        }
        if ($mode == "2") {
            return array('in', array(3, 4, 17));
        }
        if ($mode == "3") {
            return array('in', array(5, 6, 7, 8, 9));
        }
        if ($mode == "4") {
            return array('in', array(15));
        }
    }

    /**
     * @param $type  时间分类
     * @return array 0: 所有1 :今日 2:昨日 3:本周 4:本月 5:上周 6:上月
     */
    public function get_number($type)
    {
        switch ($type) {
            case 0:
                return;
            case 1:
                //  今天
                return 1;
            case 2:
                //昨天
                return 1;
            case 3:
                //        本周
                $time = time();
                $number = date('w', $time);
                if ($number == 0) $number = 7;
                return $number;
            case 4:
                //        本月
                $beginThismonth = mktime(0, 0, 0, date('m'), 1, date('Y'));
                $endToday = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y'));
                return ($endToday - $beginThismonth) / 24 / 60 / 60;
            case 5:
                //上周
                return 7;
            case 6:
                //上月
                $beginLastmonth = mktime(0, 0, 0, date("m") - 1, 1, date("Y"));
                $endLastmonth = mktime(23, 59, 59, date("m"), 0, date("Y")) + 1;
                return ($endLastmonth - $beginLastmonth) / 24 / 60 / 60;
        }

    }


    /**
     * @param $id   收银员的id
     * @param $time  时间区间
     * return 返回商户所有的流水
     */
    public function customer_detail($id, $time, $paystyle = "", $status = "")
    {
        $map['u.id'] = $id;
        if ($time != null) $map['p.paytime'] = array("between", $time);
        if ($paystyle !== "") $map['paystyle_id'] = $paystyle;
        if ($status !== "") $map['p.status'] = $status;

        $pays = $this->users->alias("u")
            ->join("__PAY__ p on p.checker_id = u.id")
            ->where($map)
            ->field("p.*")
            ->select();
        return $pays;
    }

    /**
     * 测试时间信息
     */
    public function checkdata()
    {
        $type = I("type");
        dump($this->get_number($type));
        exit;
    }

    function trans_time($time)
    {
        $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $endToday = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
        $beginYesterday = mktime(0, 0, 0, date('m'), date('d') - 1, date('Y'));
        $endYesterday = mktime(0, 0, 0, date('m'), date('d'), date('Y')) - 1;
        if ($time >= $beginToday And $time <= $endToday) {
            return "今天";
        } elseif ($time >= $beginYesterday And $time <= $endYesterday) {
            return "昨天";
        } else {
            return date("Y-m-d", $time);
        }
    }

    /**
     * @param $type   支付的类型;
     * @return int    0: 所有1 :今日 2:昨日 3:本周 4:本月 5:上周 6:上月
     */
    function type_time($type)
    {
        switch ($type) {
            case 0:
                return;
            case 1:
                //  今天
                $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
                $endToday = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
                return array($beginToday, $endToday);
            case 2:
                //昨天
                $beginYesterday = mktime(0, 0, 0, date('m'), date('d') - 1, date('Y'));
                $endYesterday = mktime(0, 0, 0, date('m'), date('d'), date('Y')) - 1;
                return array($beginYesterday, $endYesterday);
            case 3:
                //        本周
                $beginThisweek = mktime(0, 0, 0, date('m'), date('d') - date('w') + 1, date('Y'));
                $endToday = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;

//                $endThisweek=mktime(23,59,59,date('m'),date('d')-date('w')+7,date('Y'));
                return array($beginThisweek, $endToday);
            case 4:
                //        本月
                $beginThismonth = mktime(0, 0, 0, date('m'), 1, date('Y'));
                $endToday = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;

//                $endThismonth = mktime(23,59,59,date('m'),date('t'),date('Y'));
                return array($beginThismonth, $endToday);
            case 5:
                //上周
                $beginLastweek = mktime(0, 0, 0, date('m'), date('d') - date('w') + 1 - 7, date('Y'));
                $endLastweek = mktime(23, 59, 59, date('m'), date('d') - date('w') + 7 - 7, date('Y'));
                return array($beginLastweek, $endLastweek);
            case 6:
                //上月
                $beginLastmonth = mktime(0, 0, 0, date("m") - 1, 1, date("Y"));
                $endLastmonth = mktime(23, 59, 59, date("m"), 0, date("Y"));
                return array($beginLastmonth, $endLastmonth);
        }
    }

    /**
     * @param $time
     * @return false|int  转化时间
     */
    function time_transform($time)
    {
        $begin_timestamp = date("Y-m-d", $time);
        $time = strtotime($begin_timestamp);
        return $time;
    }

    /**
     * @param $type  选择的为负几月份
     * 0:全部  1:当前月份  2:-1月份 3:-2月份 4:-3月份 5:-4月份 6:-5月份
     * @return array|  负月份的时间戳
     */
    function type_month($type)
    {
        switch ($type) {
            case 0:
                return;
            case 1:
                $begin_time = mktime(0, 0, 0, date("m"), 1, date("Y"));
                $end_time = mktime(23, 59, 59, date("m"), date("t"), date("Y"));
                return array($begin_time, $end_time);
            case 2:
                $begin_time = mktime(0, 0, 0, date("m") - 1, 1, date("Y"));
                $end_time = mktime(23, 59, 59, date("m"), 0, date("Y"));
                return array($begin_time, $end_time);
            case 3:
                $begin_time = mktime(0, 0, 0, date("m") - 2, 1, date("Y"));
                $end_time = mktime(23, 59, 59, date("m") - 1, 0, date("Y"));
                return array($begin_time, $end_time);
            case 4:
                $begin_time = mktime(0, 0, 0, date("m") - 3, 1, date("Y"));
                $end_time = mktime(23, 59, 59, date("m") - 2, 0, date("Y"));
                return array($begin_time, $end_time);
            case 5:
                $begin_time = mktime(0, 0, 0, date("m") - 4, 1, date("Y"));
                $end_time = mktime(23, 59, 59, date("m") - 3, 0, date("Y"));
                return array($begin_time, $end_time);
            case 6:
                $begin_time = mktime(0, 0, 0, date("m") - 5, 1, date("Y"));
                $end_time = mktime(23, 59, 59, date("m") - 4, 0, date("Y"));
                return array($begin_time, $end_time);
        }
    }


    /**
     * @param $number  选择支付距离现在几天 0 只全部 1 最后一天 2 最后第二天
     * @param $type  是否是全部数据 不是的话判断最后的时间 0是全部
     * @return array
     */
    function get_day($type, $number = 0)
    {
//        区分是否是本月或者全部
        if ($type == 0 || $type == 1) {
            $time = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
        } else {
            $time = $this->type_month($type);
            $time = $time[1];
        }
//       这个是判断总金额的
        if ($number == 0) {
            return;
        }
        $begin_time = $time - 24 * 60 * 60 * ($number) + 1;
        $end_time = $time - 24 * 60 * 60 * ($number - 1);
//        全部时间不用判断是或否会超过本月第一天
        if ($type == 0) {
            return array($begin_time, $end_time);
        }
        $last_time = $this->type_month($type);
        $last_time = $last_time[0];
        if ($begin_time < $last_time) {
            return "已经超过当前月份了";
        }
        return array($begin_time, $end_time);
    }

    /**
     * @param $type    时间类型1
     * @param $number  往前推进一天  1为选中当前天
     * @return string
     */
    function day_detail1($begin_time, $end_time, $i, $number)
    {
        if ($i == 1) {
            $begin_time_end = strtotime(date("Y-m-d 23:59:59", $begin_time));
            return array($begin_time, $begin_time_end);
        } else if ($i == $number) {
            $end_time_start = strtotime(date("Y-m-d 0:0:0", $end_time));
            return array($end_time_start, $end_time);
        } else {
            $time_start = strtotime(date("Y-m-d 0:0:0", $begin_time)) + 24 * 60 * 60 * ($i - 1);
            $time_end = $time_start + 24 * 60 * 60 - 1;
            return array($time_start, $time_end);
        }
    }

    /**
     * @param $type    时间类型
     * @param $number  往前推进一天  1为选中当前天
     * @return string
     */
    function day_detail($time, $number)
    {

//        $time=$this->type_time($type);
//        $time_start=$time[0];
        $time_end = $time;
        $begin_time = $time_end - 24 * 60 * 60 * ($number) + 1;
        $end_time = $time_end - 24 * 60 * 60 * ($number - 1);
//        if($begin_time < $time_start) return "超过时间无数据显示";
        return array($begin_time, $end_time);
    }

    /**
     * @param $type  选择的时间分类
     * @return array
     */
    function get_mark($type)
    {
        $time = time();
        switch ($type) {
            case 1:
                $day[7]['end_time'] = $time;
                $day[7]['begin_time'] = strtotime("today");
                $day[6]['end_time'] = $day[7]['begin_time'] - 1;
                $day[6]['begin_time'] = $day[7]['begin_time'] - 24 * 60 * 60;
                $day[5]['end_time'] = $day[6]['end_time'] - 24 * 60 * 60;
                $day[5]['begin_time'] = $day[6]['begin_time'] - 24 * 60 * 60;
                $day[4]['end_time'] = $day[5]['end_time'] - 24 * 60 * 60;
                $day[4]['begin_time'] = $day[5]['begin_time'] - 24 * 60 * 60;
                $day[3]['end_time'] = $day[4]['end_time'] - 24 * 60 * 60;
                $day[3]['begin_time'] = $day[4]['begin_time'] - 24 * 60 * 60;
                $day[2]['end_time'] = $day[3]['end_time'] - 24 * 60 * 60;
                $day[2]['begin_time'] = $day[3]['begin_time'] - 24 * 60 * 60;
                $day[1]['end_time'] = $day[2]['end_time'] - 24 * 60 * 60;
                $day[1]['begin_time'] = $day[2]['begin_time'] - 24 * 60 * 60;
                return array($day);
            case 2:
                $day[7]['end_time'] = $time;
                $day[7]['begin_time'] = strtotime("yesterday");
                $day[6]['end_time'] = $day[7]['begin_time'] - 1;
                $day[6]['begin_time'] = $day[7]['begin_time'] - 24 * 60 * 60 * 2;
                $day[5]['end_time'] = $day[6]['end_time'] - 24 * 60 * 60 * 2;
                $day[5]['begin_time'] = $day[6]['begin_time'] - 24 * 60 * 60 * 2;
                $day[4]['end_time'] = $day[5]['end_time'] - 24 * 60 * 60 * 2;
                $day[4]['begin_time'] = $day[5]['begin_time'] - 24 * 60 * 60 * 2;
                $day[3]['end_time'] = $day[4]['end_time'] - 24 * 60 * 60 * 2;
                $day[3]['begin_time'] = $day[4]['begin_time'] - 24 * 60 * 60 * 2;
                $day[2]['end_time'] = $day[3]['end_time'] - 24 * 60 * 60 * 2;
                $day[2]['begin_time'] = $day[3]['begin_time'] - 24 * 60 * 60 * 2;
                $day[1]['end_time'] = $day[2]['end_time'] - 24 * 60 * 60 * 2;
                $day[1]['begin_time'] = $day[2]['begin_time'] - 24 * 60 * 60 * 2;
                return array($day);
            case 3:
                $day[7]['end_time'] = $time;
                $day[7]['begin_time'] = strtotime("-5 day");
                $day[6]['end_time'] = $day[7]['begin_time'] - 1;
                $day[6]['begin_time'] = $day[7]['begin_time'] - 24 * 60 * 60 * 5;
                $day[5]['end_time'] = $day[6]['end_time'] - 24 * 60 * 60 * 5;
                $day[5]['begin_time'] = $day[6]['begin_time'] - 24 * 60 * 60 * 5;
                $day[4]['end_time'] = $day[5]['end_time'] - 24 * 60 * 60 * 5;
                $day[4]['begin_time'] = $day[5]['begin_time'] - 24 * 60 * 60 * 5;
                $day[3]['end_time'] = $day[4]['end_time'] - 24 * 60 * 60 * 5;
                $day[3]['begin_time'] = $day[4]['begin_time'] - 24 * 60 * 60 * 5;
                $day[2]['end_time'] = $day[3]['end_time'] - 24 * 60 * 60 * 5;
                $day[2]['begin_time'] = $day[3]['begin_time'] - 24 * 60 * 60 * 5;
                $day[1]['end_time'] = $day[2]['end_time'] - 24 * 60 * 60 * 5;
                $day[1]['begin_time'] = $day[2]['begin_time'] - 24 * 60 * 60 * 5;
                return array($day);
            case 4:
                $day[7]['end_time'] = $time;
                $day[7]['begin_time'] = strtotime("-10 day");
                $day[6]['end_time'] = $day[7]['begin_time'] - 1;
                $day[6]['begin_time'] = $day[7]['begin_time'] - 24 * 60 * 60 * 10;
                $day[5]['end_time'] = $day[6]['end_time'] - 24 * 60 * 60 * 10;
                $day[5]['begin_time'] = $day[6]['begin_time'] - 24 * 60 * 60 * 10;
                $day[4]['end_time'] = $day[5]['end_time'] - 24 * 60 * 60 * 10;
                $day[4]['begin_time'] = $day[5]['begin_time'] - 24 * 60 * 60 * 10;
                $day[3]['end_time'] = $day[4]['end_time'] - 24 * 60 * 60 * 10;
                $day[3]['begin_time'] = $day[4]['begin_time'] - 24 * 60 * 60 * 10;
                $day[2]['end_time'] = $day[3]['end_time'] - 24 * 60 * 60 * 10;
                $day[2]['begin_time'] = $day[3]['begin_time'] - 24 * 60 * 60 * 10;
                $day[1]['end_time'] = $day[2]['end_time'] - 24 * 60 * 60 * 10;
                $day[1]['begin_time'] = $day[2]['begin_time'] - 24 * 60 * 60 * 10;
                return array($day);
        }
    }

    /**
     * @param $time   unix时间
     * @return 星期几
     */
    function weekday($time)
    {
        $weekday = array('星期日', '星期一', '星期二', '星期三', '星期四', '星期五', '星期六');
        return $weekday[date('w', $time)];
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
     * 硬件设备绑定
     */
    public function pc_list()
    {
        if($this->userInfo['role_id'] == 3){
            $id = M('merchants')->where(array('uid'=>$this->userId))->getField('id');
        }else{
            //店铺id
            ($id = I('id')) || $this->ajaxReturn(array('code'=>'error','msg'=>'id is empty'));
        }
        //意锐插件
        $pcsy = M('merchants_pcsy')->where(array('mid'=>$id))->field('id,device_no as sn,1 as type,add_time')->select();
        //商+宝
        $wghl = M('merchants_wghl')->where(array('merchant_id'=>$id))->field('id,substring(sn,-6,6) sn,2 as type,add_time')->select();
        //聚财宝
        $pop = M('merchants_pop')->where(array('merchant_id'=>$id))->field('id,sn,3 as type,add_time')->select();
        $data = array_merge($pcsy,$wghl,$pop);
        array_multisort(array_column($data,'add_time'),SORT_DESC,$data);
        $this->ajaxReturn(array('code'=>'success','msg'=>'成功','data'=>$data));
    }

    /**
     * 绑定设备
     */
    public function bind_pc()
    {
        get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Api/', 'bind_pc', ':绑定设备参数', json_encode(I('')));
        ($sn = I('sn')) || $this->ajaxReturn(array('code'=>'error','msg'=>'device_no is empty'));
        ($type = I('type')) || $this->ajaxReturn(array('code'=>'error','msg'=>'type is empty'));
        ($mid = I('id')) || $this->ajaxReturn(array('code'=>'error','msg'=>'id is empty'));
        $mer_count = M('merchants')->where(array('id'=>$mid))->count();
        if($mer_count == 0) $this->ajaxReturn(array('code'=>'error','msg'=>'id is error'));
        $type = $this->userInfo['role_id'] == 3 ? 1 : 2;
        switch ($type) {
            case 1:
                if(M('merchants_pcsy')->where(array('device_no'=>$sn))->find()) {
                    $this->ajaxReturn(array('code'=>'error','msg'=>'该设备已经被绑定'));
                }
                M()->startTrans();
                $res = M('merchants_pcsy')->add(array('device_no'=>$sn,'mid'=>$mid,'add_time'=>time()));
                $log_res = M('merchants_pcsy_log')->add(array('device_no'=>$sn,'mid'=>$mid,'add_time'=>time(),'action'=>1,'type'=>$type));
                if($res && $log_res){
                    M()->commit();
                    get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Api/', 'bind_pc', ':绑定小白盒成功', json_encode(I('')));
                    $this->ajaxReturn(array('code'=>'success','msg'=>'绑定成功','id'=>$res));
                }else{
                    M()->rollback();
                    get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Api/', 'bind_pc', ':绑定小白盒失败', json_encode(I('')));
                    $this->ajaxReturn(array('code'=>'error','msg'=>'绑定失败'));
                }
                break;
            case 2:
                if(strlen($sn) != 6 && !is_numeric($sn)){
                    $this->ajaxReturn(array('code'=>'error','msg'=>'sn is error'));
                }
                if(M('merchants_wghl')->where(array('sn'=>'ypt'.$sn))->find()) {
                    $this->ajaxReturn(array('code'=>'error','msg'=>'该设备已经被绑定'));
                }
                M()->startTrans();
                $res = M('merchants_wghl')->add(array('sn'=>'ypt'.$sn,'merchant_id'=>$mid,'add_time'=>time()));
                $log_res = M('merchants_wghl_log')->add(array('sn'=>'ypt'.$sn,'merchant_id'=>$mid,'add_time'=>time(),'action'=>1,'type'=>$type));
                if($res && $log_res){
                    M()->commit();
                    get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Api/', 'bind_pc', ':绑定商+宝成功', json_encode(I('')));
                    $this->ajaxReturn(array('code'=>'success','msg'=>'绑定成功','id'=>$res));
                }else{
                    M()->rollback();
                    get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Api/', 'bind_pc', ':绑定商+宝失败', json_encode(I('')));
                    $this->ajaxReturn(array('code'=>'error','msg'=>'绑定失败'));
                }
                break;
            case 3:
                if(M('merchants_pop')->where(array('sn'=>$sn))->find()) {
                    $this->ajaxReturn(array('code'=>'error','msg'=>'该设备已经被绑定'));
                }
                M()->startTrans();
                $res = M('merchants_pop')->add(array('sn'=>$sn,'merchant_id'=>$mid,'add_time'=>time()));
                $log_res = M('merchants_pop_log')->add(array('sn'=>$sn,'merchant_id'=>$mid,'add_time'=>time(),'action'=>1,'type'=>$type));
                if($res && $log_res){
                    M()->commit();
                    get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Api/', 'bind_pc', ':绑定聚财宝成功', json_encode(I('')));
                    $this->ajaxReturn(array('code'=>'success','msg'=>'绑定成功','id'=>$res));
                }else{
                    M()->rollback();
                    get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Api/', 'bind_pc', ':绑定聚财宝失败', json_encode(I('')));
                    $this->ajaxReturn(array('code'=>'error','msg'=>'绑定失败'));
                }
                break;
            default:
                $this->ajaxReturn(array('code'=>'error','msg'=>'type error'));
        }
    }

    /**
     * 解绑设备
     */
    public function unbind_pc()
    {
        get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Api/', 'unbind_pc', ':解绑设备参数', json_encode(I('')));
        ($this->userInfo['role_id'] == 3) || $this->ajaxReturn(array('code'=>'error','msg'=>'商户才能解绑设备'));
        ($type = I('type')) || $this->ajaxReturn(array('code'=>'error','msg'=>'type is empty'));
        ($id = I('id')) || $this->ajaxReturn(array('code'=>'error','msg'=>'id is empty'));
        $merchant_id = M('merchants')->where(array('uid'=>$this->userId))->getField('id');
        switch ($type) {
            case 1:
                if(!$info = M('merchants_pcsy')->where(array('id'=>$id,'mid'=>$merchant_id))->find()) {
                    $this->ajaxReturn(array('code'=>'error','msg'=>'未找到该设备'));
                }
                M()->startTrans();
                $res = M('merchants_pcsy')->where(array('id'=>$id))->delete();
                $log_res = M('merchants_pcsy_log')->add(array('device_no'=>$info['device_no'],'mid'=>$merchant_id,'add_time'=>time(),'action'=>2,'type'=>1));
                if($res && $log_res){
                    M()->commit();
                    get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Api/', 'unbind_pc', ':解绑小白盒成功', json_encode(I('')));
                    $this->ajaxReturn(array('code'=>'success','msg'=>'解绑成功'));
                }else{
                    M()->rollback();
                    get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Api/', 'unbind_pc', ':解绑小白盒失败', json_encode(I('')));
                    $this->ajaxReturn(array('code'=>'error','msg'=>'解绑失败'));
                }
                break;
            case 2:
                if(!$info = M('merchants_wghl')->where(array('id'=>$id,'merchant_id'=>$merchant_id))->find()) {
                    $this->ajaxReturn(array('code'=>'error','msg'=>'未找到该设备'));
                }
                M()->startTrans();
                $res = M('merchants_wghl')->where(array('id'=>$id))->delete();
                $log_res = M('merchants_wghl_log')->add(array('sn'=>$info['sn'],'merchant_id'=>$merchant_id,'add_time'=>time(),'action'=>2,'type'=>1));
                if($res && $log_res){
                    M()->commit();
                    get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Api/', 'unbind_pc', ':解绑小白盒成功', json_encode(I('')));
                    $this->ajaxReturn(array('code'=>'success','msg'=>'解绑成功'));
                }else{
                    M()->rollback();
                    get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Api/', 'unbind_pc', ':解绑小白盒失败', json_encode(I('')));
                    $this->ajaxReturn(array('code'=>'error','msg'=>'解绑失败'));
                }
                break;
            case 3:
                if(!$info = M('merchants_pop')->where(array('id'=>$id,'merchant_id'=>$merchant_id))->find()) {
                    $this->ajaxReturn(array('code'=>'error','msg'=>'未找到该设备'));
                }
                M()->startTrans();
                $res = M('merchants_pop')->where(array('id'=>$id))->delete();
                $log_res = M('merchants_pop_log')->add(array('sn'=>$info['sn'],'merchant_id'=>$merchant_id,'add_time'=>time(),'action'=>2,'type'=>1));
                if($res && $log_res){
                    M()->commit();
                    get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Api/', 'unbind_pc', ':解绑小白盒成功', json_encode(I('')));
                    $this->ajaxReturn(array('code'=>'success','msg'=>'解绑成功'));
                }else{
                    M()->rollback();
                    get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Api/', 'unbind_pc', ':解绑小白盒失败', json_encode(I('')));
                    $this->ajaxReturn(array('code'=>'error','msg'=>'解绑失败'));
                }
                break;
            default:
                $this->ajaxReturn(array('code'=>'error','msg'=>'type error'));
        }
    }


    /**
     * 商+宝wifi绑定配置地址
     */
    public function wifi()
    {
        $this->display();
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
//            $redirect_uri = U('Pay/Barcode/qr_weixipay', '', '', true);
            $redirect_uri = 'https://sy.youngport.com.cn' . $_SESSION['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            $redirect_uri = urlencode($redirect_uri);
            $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $config['APPID'] . '&redirect_uri=' . $redirect_uri . '&response_type=code&scope=snsapi_base#wechat_redirect';
            redirect($url);
        } else {
            //如果有code参数；则表示获取到openid
            $code = I('code');
            //组合获取prepay_id的url
            $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $config['APPID'] . '&secret=' . $config['APPSECRET'] . '&code=' . $code . '&grant_type=authorization_code';
            //curl获取prepay_id
            $result = $this->_curl_get_contents($url);
            $result = json_decode($result, true);

            return $result['openid'];

        }
    }

    /**
     * 版本1.2
     * @param $uid   商户或者收银员在用户表的id
     * @return mixed   商户的id
     */
    private function get_merchant($uid)
    {
        $role_id = $this->roles->where("uid=$uid")->getField('role_id');
        if ($role_id == 3) {
            return $uid;
        } else {
            return $this->users->where("id=$uid")->getField("pid");
        }
    }

    /**
     * 版本1.3
     * @param $uid   商户或者收银员在用户表的id
     * @return mixed   商户的信息
     */
    private function get_merchant1($uid)
    {
        $data = array();
        $role_id = $this->roles->where("uid=$uid")->getField('role_id');
        $data['role'] = $role_id;
        if ($role_id == 3) {
            $m_uid = $uid;
            $data['checker'] = 0;
            $data['is_all'] = 1;
        } else {
            $user = $this->users->where("id=$uid")->find();
            $m_uid = $user['pid'];
            $data['checker'] = $uid;
            $data['is_all'] = $user['is_all'];
        }
        $data['mid'] = $this->merchants->where("uid=$m_uid")->getField("id");
        return $data;
    }

    public function shuzu($data)
    {
        $total = array();
        foreach ($data as $key => $value) {
            foreach ($value as $k => $v) {
                $total[] = $v;
            }
        }
        return $total;
    }

    /** 新增图片到数据库
     * @param $cate_c_id  新增台签的id
     * @param $no_number  数字
     */
    function add_cate_png($cate_c_id, $no_number)
    {
        //新增图片到数据库
        $value = "https://sy.youngport.com.cn/index.php?g=Pay&m=Barcode&a=qrcode&type=0&id=" . $cate_c_id;
        $errorCorrectionLevel = 'H';//容错级别
        $matrixPointSize = 10;//生成图片大小
        //生成二维码图片
        $path_url = "data/upload/pay/" . $no_number . ".png";
        // 生成二位码的函数
        vendor("phpqrcode.phpqrcode");
        $av = new \QRcode();
        ob_clean(); //这个很重要
        $av->png($value, $path_url, $errorCorrectionLevel, $matrixPointSize, 2);
        $imgs = "data/upload/pay/seller_barcode/bg_pay.png";
        $this->save_qrcode($imgs, $path_url, $no_number);
        return true;
    }

    //测试生成只有下面带有标签的图片
    function save_qrcode($imges, $qrcode, $number = '')
    {
        //加载背景图
        $img_bg_info = getimagesize($imges);
        $img_bg_type = image_type_to_extension($img_bg_info[2], false);
        $fun_bg = "imagecreatefrom{$img_bg_type}";
        $img_bg = $fun_bg($imges);


        //加载二维码
        $img_qrcode_src = $qrcode;
        $img_qrcode_info = getimagesize($img_qrcode_src);
        list($width, $height) = $img_qrcode_info;
        $img_qrcode_type = image_type_to_extension($img_qrcode_info[2], false);
        $fun_qrcode = "imagecreatefrom{$img_qrcode_type}";
        $img_qrcode = $fun_qrcode($img_qrcode_src);

//        $font='data/upload/pay/seller_barcode/ttf/arial-bold.otf';
        $font = 'data/upload/pay/seller_barcode/ttf/ceshi.TTF';
        $fontsize = 12;
        $dstwidth = imagesx($img_bg);
        $black = imagecolorallocate($img_bg, 30, 30, 30);
        $len = $this->utf8_strlen($number);
        $a = 19;
        $b = 385;
        for ($i = 0; $i <= $len;) {
            $box = imagettfbbox($fontsize, 0, $font, mb_substr($number, $i, $a, 'utf8'));
            $box_width = max(abs($box[2] - $box[0]), abs($box[4] - $box[6]));
            $x = ceil(($dstwidth - $box_width) / 2);
            $tempstr = mb_substr($number, $i, $a, 'utf8');
            imagettftext($img_bg, $fontsize, 0, $x, $b, $black, $font, $tempstr);
            if ($this->utf8_strlen($tempstr) == $a) {
                $i += $a;
                $b += 50;
            } else {
                break;
            }
        }
        imagecopyresized($img_bg, $img_qrcode, 0, 0, 0, 0, 370, 370, $width, $height);
        $save_img = "data/upload/pay/cate/QR_" . $number . ".png";
        imagepng($img_bg, $save_img);
        imagedestroy($img_bg);
        imagedestroy($img_qrcode);

    }

    public function utf8_strlen($string = null)
    {
        preg_match_all("/./us", $string, $match);
        return count($match[0]);
    }

    public function report_data()
    {
        $this->checkLogin();
        if ($this->version >= '1.8') {
            $this->report_data_18();
        } else {
            $this->ajaxReturn(array('code' => 'error', 'msg' => '版本错误'));
        }
    }

    private function report_data_18()
    {
        $m_info = $this->get_merchant1($this->id);
        $type = I("type");
        $time = $this->type_month($type);
        $data = $this->count_paystyle($m_info, $time);
        $data['trade_amount_excel'] = $this->host . "/index.php?g=Api&m=Shopnews&a=report_data_amount&type=" . $type . "&id=" . $this->id;
        $data['trade_number_excel'] = $this->host . "/index.php?g=Api&m=Shopnews&a=report_data_number&type=" . $type . "&id=" . $this->id;
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $data));
    }

    private function count_paystyle($m_info, $time = "")
    {
        if ($time != "") $map['p.paytime'] = array("between", $time);
        if ($m_info['is_all'] == 1) {
            $map['p.merchant_id'] = $m_info['mid'];
        } else {
            $map['p.merchant_id'] = $m_info['mid'];
            $map['p.checker_id'] = $m_info['checker'];
        }

        $field = "p.paytime,ifnull(sum(if( p.status =1,p.price, 0)),0) as total_price,
        ifnull(sum( if(p.status=1, 1, 0)),0) as total_num,
        ifnull(sum( if( p.paystyle_id =1 And p.status=1, 1, 0)),0) as per_weixin_num,
        ifnull(sum( if( p.paystyle_id =2 And p.status=1, 1, 0)),0) as per_ali_num, 
        ifnull(sum( if( p.paystyle_id =3 And p.status=1, 1, 0)),0) as per_unionpay_num, 
        ifnull(sum( if( p.paystyle_id =5 And p.status=1, 1, 0)),0) as per_cash_num,
        ifnull(sum( if( p.paystyle_id =1 And p.status=1,p.price, 0)),0) as per_wei_price,
        ifnull(sum( if( p.paystyle_id =2 And p.status=1,p.price, 0)),0) as per_ali_price,
        ifnull(sum( if( p.paystyle_id =3 And p.status=1,p.price, 0)),0) as per_unionpay_price,
        ifnull(sum( if( p.paystyle_id =5 And p.status=1,p.price, 0)),0) as per_cash_price";
        $pay = $this->pays->alias("p")
            ->field($field)
            ->where($map)
            ->find();
        $order_model = D('Order');
        $merchant_balance = $order_model->get_merchant_info($m_info['mid'],$time);
        $agent_balance = $order_model->get_agent_info($m_info['mid'],$time);
        $pay['total_price'] = $pay['total_price']+$merchant_balance['amount'] + $agent_balance['amount'];
        $pay['total_num'] = $pay['total_num']+$merchant_balance['number'] + $agent_balance['number'];
        $return['trade_amount'] = array();
        $return['trade_amount'][] = array('name' => '微信', 'value' => $pay['per_wei_price'], 'percentage' => round($pay['per_wei_price'] * 100 / $pay['total_price'], 2) . '%');

        $return['trade_amount'][] = array('name' => '支付宝', 'value' => $pay['per_ali_price'], 'percentage' => round($pay['per_ali_price'] * 100 / $pay['total_price'], 2) . '%');

        $return['trade_amount'][] = array('name' => '现金', 'value' => $pay['per_cash_price'], 'percentage' => round($pay['per_cash_price'] * 100 / $pay['total_price'], 2) . '%');

        $return['trade_amount'][] = array('name' => '银联刷卡', 'value' => $pay['per_unionpay_price'], 'percentage' => round($pay['per_unionpay_price'] * 100 / $pay['total_price'], 2) . '%');

        $return['trade_amount'][] = array('name' => '商户储值', 'value' => $merchant_balance['amount'], 'percentage' => round($merchant_balance['amount'] * 100 / $pay['total_price'], 2) . '%');

        $return['trade_amount'][] = array('name' => '代理储值', 'value' => $agent_balance['amount'], 'percentage' => round($agent_balance['amount'] * 100 / $pay['total_price'], 2) . '%');


        $return['trade_number'] = array();
        $return['trade_number'][] = array('name' => '微信', 'value' => $pay['per_weixin_num'], 'percentage' => round($pay['per_weixin_num'] * 100 / $pay['total_num'], 2) . '%');

        $return['trade_number'][] = array('name' => '支付宝', 'value' => $pay['per_ali_num'], 'percentage' => round($pay['per_ali_num'] * 100 / $pay['total_num'], 2) . '%');

        $return['trade_number'][] = array('name' => '现金', 'value' => $pay['per_cash_num'], 'percentage' => round($pay['per_cash_num'] * 100 / $pay['total_num'], 2) . '%');

        $return['trade_number'][] = array('name' => '银联刷卡', 'value' => $pay['per_unionpay_num'], 'percentage' => round($pay['per_unionpay_num'] * 100 / $pay['total_num'], 2) . '%');

        $return['trade_number'][] = array('name' => '商户储值', 'value' => $merchant_balance['number'], 'percentage' => round($merchant_balance['number'] * 100 / $pay['total_num'], 2) . '%');

        $return['trade_number'][] = array('name' => '代理储值', 'value' => $agent_balance['number'], 'percentage' => round($agent_balance['number'] * 100 / $pay['total_num'], 2) . '%');

        return $return;
    }

    public function report_data_amount()
    {
        $m_info = $this->get_merchant1(I('id'));
        $type = I("type");
        $time = $this->type_month($type);
        $data = $this->count_paystyle($m_info, $time);
        $data = json_encode($data['trade_amount']);
        $this->assign('data', $data);
        $this->assign('title', '交易金额');
        $this->display();
    }

    public function report_data_number()
    {
        $m_info = $this->get_merchant1(I('id'));
        $type = I("type");
        $time = $this->type_month($type);
        $data = $this->count_paystyle($m_info, $time);
        $data = json_encode($data['trade_number']);
        $this->assign('data', $data);
        $this->assign('title', '交易笔数');
        $this->display('report_data_amount');
    }

    public function report_trend()
    {
        $this->checkLogin();
        if ($this->version >= '1.8') {
            $this->report_trend_18();
        } else {
            $this->ajaxReturn(array('code' => 'error', 'msg' => '版本错误'));
        }
    }

    // 版本1.8
    private function report_trend_18()
    {
        $type = I("type");
        if(!$type) $this->ajaxReturn(array("code" => "error", "msg" => "请传入type"));
        $data['amount_trend'] = $this->host . "/index.php?g=Api&m=Shopnews&a=report_trend_amount&type=" . $type . "&id=" . $this->id;
        $data['number_trend'] = $this->host . "/index.php?g=Api&m=Shopnews&a=report_trend_number&type=" . $type . "&id=" . $this->id;
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $data));
    }

    public function report_trend_amount()
    {
        $m_info = $this->get_merchant1(I("id"));
        $type = I("type");
        $time = $this->get_mark($type);
        $count = array();
        foreach ($time[0] as $k => $v) {
            $array = array($time[0][$k]['begin_time'], $time[0][$k]['end_time']);
            $count[$k]['pay'] = $this->count_paystyle($m_info, $array);
            $count[$k]['time'] = date("n.d", $v['begin_time']) . "~" . date("n.d", $v['end_time']);
        }
        $weixin_data = $ali_data = $cash_data = $bank_data = $merchant_data = $agent_data = array();
        foreach ($count as $val) {
            $weixin_data[] = $val['pay']['trade_amount'][0]['value'];
            $ali_data[] = $val['pay']['trade_amount'][1]['value'];
            $cash_data[] = $val['pay']['trade_amount'][2]['value'];
            $bank_data[] = $val['pay']['trade_amount'][3]['value'];
            $merchant_data[] = $val['pay']['trade_amount'][4]['value'];
            $agent_data[] = $val['pay']['trade_amount'][5]['value'];
        }
        $excel[] = array('name' => '微信', 'type' => 'line', 'stack' => '总量', 'areaStyle' => array('normal'=>array()), 'data'=> array_reverse($weixin_data));
        $excel[] = array('name' => '支付宝', 'type' => 'line', 'stack' => '总量', 'areaStyle' => array('normal'=>array()), 'data'=> array_reverse($ali_data));
        $excel[] = array('name' => '现金', 'type' => 'line', 'stack' => '总量', 'areaStyle' => array('normal'=>array()), 'data'=> array_reverse($cash_data));
        $excel[] = array('name' => '银联刷卡', 'type' => 'line', 'stack' => '总量', 'areaStyle' => array('normal'=>array()), 'data'=> array_reverse($bank_data));
        $excel[] = array('name' => '商户储值', 'type' => 'line', 'stack' => '总量', 'areaStyle' => array('normal'=>array()), 'data'=> array_reverse($merchant_data));
        $excel[] = array('name' => '代理储值', 'type' => 'line', 'stack' => '总量', 'areaStyle' => array('normal'=>array()), 'data'=> array_reverse($agent_data));
        $count = json_encode($count);
        $excel = json_encode($excel);
        $this->assign("count", $count);
        $this->assign("excel", $excel);
        $this->display();

    }
    public function report_trend_number()
    {
        $m_info = $this->get_merchant1(I("id"));
        $type = I("type");
        $time = $this->get_mark($type);
        $count = array();
        foreach ($time[0] as $k => $v) {
            $array = array($time[0][$k]['begin_time'], $time[0][$k]['end_time']);
            $count[$k]['pay'] = $this->count_paystyle($m_info, $array);
            $count[$k]['time'] = date("n.d", $v['begin_time']) . "~" . date("n.d", $v['end_time']);
        }
        $weixin_data = $ali_data = $cash_data = $bank_data = $merchant_data = $agent_data = array();
        foreach ($count as $val) {
            $weixin_data[] = $val['pay']['trade_number'][0]['value'];
            $ali_data[] = $val['pay']['trade_number'][1]['value'];
            $cash_data[] = $val['pay']['trade_number'][2]['value'];
            $bank_data[] = $val['pay']['trade_number'][3]['value'];
            $merchant_data[] = $val['pay']['trade_number'][4]['value'];
            $agent_data[] = $val['pay']['trade_number'][5]['value'];
        }
        $excel[] = array('name' => '微信', 'type' => 'line', 'stack' => '总量', 'areaStyle' => array('normal'=>array()), 'data'=> array_reverse($weixin_data));
        $excel[] = array('name' => '支付宝', 'type' => 'line', 'stack' => '总量', 'areaStyle' => array('normal'=>array()),  'type' => 'line', 'data'=> array_reverse($ali_data));
        $excel[] = array('name' => '现金', 'type' => 'line', 'stack' => '总量', 'areaStyle' => array('normal'=>array()),  'type' => 'line', 'data'=> array_reverse($cash_data));
        $excel[] = array('name' => '银联刷卡', 'type' => 'line', 'stack' => '总量', 'areaStyle' => array('normal'=>array()),  'type' => 'line', 'data'=> array_reverse($bank_data));
        $excel[] = array('name' => '商户储值', 'type' => 'line', 'stack' => '总量', 'areaStyle' => array('normal'=>array()),  'type' => 'line', 'data'=> array_reverse($merchant_data));
        $excel[] = array('name' => '代理储值', 'type' => 'line', 'stack' => '总量', 'areaStyle' => array('normal'=>array()),  'type' => 'line', 'data'=> array_reverse($agent_data));
        $count = json_encode($count);
        $excel = json_encode($excel);
        $this->assign("count", $count);
        $this->assign("excel", $excel);
        $this->display('report_trend_amount');

    }
}
