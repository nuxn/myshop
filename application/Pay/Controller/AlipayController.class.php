<?php

namespace Pay\Controller;

use Common\Controller\HomebaseController;
use Common\Lib\Subtable;

class AlipayController extends HomebaseController
{
    private $ali_private_key = "MIICXAIBAAKBgQC/UIMSw0mWKRp3wP3v0tbKKqtQO80iL3gBkceCE41KRtpE8+ljXzH16jXs5Alj3cPNZlAh+2SApLBv9sVY9nGU8rd6d5294HHH8APDdrHBtdUTpgZnKGNATFhCeiZPuLD76DJslWtSM4a8kW9EkBUDe3mFLtDQwu+ZtbUYf0k8eQIDAQABAoGALzKFo5NaDBmH1hNeklPJeYadTOXz7YMYcAqu1YBNUw23u1sRMNrDUI+/TfnT9zc2nu2mxztlx/bZMEYI2bGyw0Y/3oYl4GHDmeSyqq9o6SjL9S6GRtj+ngG8CX9QuVj7lTqcvHJrBR8E48EiyH4VK9ouySIHE9Ukf71VQVPd0AECQQD5uCTXuCd6aGbs2XqTfX/tfaUio5lFsdvTrccnFdTvDJ/EWwuMXgceJvehsOmNvZK8NabruGkyAk25ABM9bxq5AkEAxCBR2xvTVSBf/ohpB1/y94Imx1pb5OLvrRMvxq3LEcQORDzbSQdY27UZ1i3tC8CLvPF68KE985j75xgY//9PwQJBALx10LhM7t5etG7DotJ0wHtHe70sopwKotCaMda5jz3p1RmnbIu+2rhSyEhq75hdHcSU6Si0wA9R1b5s5BhVJWECQClVdmrrLlree9y0+w2m1xn6wBl7napbeY/MX2FL92RDIY6YFM8LUVrcjBlrjG3RoqGrcvGLyfaw40YM+hfZwwECQFNwNJ6wB5INtt+CeJkJSiaHuhevFmr3w8UD00dOXxTMz2m86cmV+ZNl5srCkeunKdwVCMwNhUHFfLfCno3XyEk=";
    private $appid = '2017010704905089'; // 支付宝APPID
    private $ali_notify_url = 'https://sy.youngport.com.cn/notify/alipay_notify.php'; // 支付宝回调地址
    private $url = 'https://openapi.alipay.com/gateway.do';
    private $path;
    private $pay_model;

    public function __construct()
    {
        parent::__construct();
        $this->path = $_SERVER['DOCUMENT_ROOT'] . '/data/log/alipay/';
        $this->pay_model = M(Subtable::getSubTableName('pay'));
    }

    public function test()
    {

        $data['order_sn'] = getRemark();
        $data['price'] = '0.01';
        $this->payOpenLoan($data);
    }

    public function payOpenLoan($info)
    {
        //构造要请求的参数数组，无需改动
        $content = array(
            'out_trade_no' => $info['order_sn'],
            'seller_id' => '2088421497824441',
            'timeout_express' => '30m',
            'product_code' => 'QUICK_MSECURITY_PAY',
            'total_amount' => $info['price'],
            'subject' => '深圳前海洋仆淘',
            'extend_params' => array(
                'sys_service_provider_id' => '2088421497824441'
            )
        );
        $this->ali_notify_url = 'https://sy.youngport.com.cn/notify/alipay_loan_notify.php';
        $request = array(
            'app_id' => $this->appid,
            'method' => 'alipay.trade.app.pay',
            'charset' => 'utf-8',
            'sign_type' => 'RSA',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'notify_url' => $this->ali_notify_url,
//            'app_auth_token' => $token,
            'biz_content' => json_encode($content),
        );
        $string = $this->getSignContent($request);
        $sign = $this->rsaSign($string, $this->ali_private_key);

        $request['sign'] = $sign;
        get_date_dir($this->path,'app_pay','下单数据', json_encode($request));

        $sign = $this->getSignContentUrlencode($request);

        return array('sign'=>$sign,'price'=>$info['price']);
    }

    protected function checkEmpty($value){
        if (!isset($value))
            return true;
        if ($value === null)
            return true;
        if (trim($value) === "")
            return true;

        return false;
    }

    public function getSignContentUrlencode($params){
        $sign = $params['sign'];
        unset($params['sign']);
        ksort($params);
        $params['sign'] = $sign;
        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {
                // 转换成目标字符集
                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . urlencode($v);
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . urlencode($v);
                }
                $i++;
            }
        }
        unset ($k, $v);
        return $stringToBeSigned;
    }
    private function err($msg)
    {
        $this->ajaxReturn(array('code'=>'error', 'msg'=>$msg));
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
            exit;
        }
    }

    # 支付宝手机扫码支付
    public function qr_to_alipay()
    {
        $cate_id = I('id');//二维码对应的id
        $checker_id = I('checker_id', 0);
        if (!$cate_id) exit('cate_id不能为空!');
        $res = M('merchants_cate')->where('id=' . $cate_id)->find();
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
        $mode = I('mode',6);
        $jmt_remark = I('jmt_remark','');
        if (!$cate_id) exit('cate_id不能为空!');
        if (!$order_id) exit('订单号不能为空!');

        $res = M('merchants_cate')->where(array("id" => $cate_id))->find();
        $ali_mchid = $res['alipay_partner'];
        $token = $res['alipay_public_key'];
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
                "bank" => 3,
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
                'sys_service_provider_id' => '2088421497824441'
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
        get_date_dir($this->path,'log','下单数据2', json_encode($request_arr));

        $res_str = $this->curl($this->url, $request_arr);
        $results = json_decode($res_str, true);
        $result = $results['alipay_trade_precreate_response'];
        if($result['code'] === '10000'){
            get_date_dir($this->path,'alipay_js','下单成功', json_encode($results));
            header("Location: $result[qr_code]");
        } else {
            echo '<script type="text/javascript">alert("网络异常，请稍后再试！")</script>';
            get_date_dir($this->path,'alipay_js','下单失败', json_encode($results));
        }
    }
    
    public function alipay($res)
    {
        header("Content-type:text/html;charset=utf-8");

        $ali_mchid = $res['alipay_partner'];
        $price = $res['price'];
//        $token = $res['alipay_public_key'];
//        if(empty($token)){
//            echo '<script type="text/javascript">alert("网络异常，请稍后再试！")</script>';
//        }
        $payModel = $this->pay_model;
        $where = array(
            "merchant_id" => $res['merchant_id'],
            "paystyle_id" => "2",
            "price" => $price,
            "status" => "0",
            "mode" => 1,
            "cate_id" => $res['id'],
        );
        $subject = $res['jianchen'];
        $where['subject'] = $subject;
        $remark = $res['order_sn']?:$this->getRemark();
        $where['remark'] = $remark;
        $where['jmt_remark'] = I('memo', '');
        $where['paytime'] = time();
        $where['checker_id'] = $res['checker_id'];
        $where['bank'] = 3;
        $where['phone_info'] = $_SERVER['HTTP_USER_AGENT'];
        $wzcost_rate = $this->getRate($ali_mchid);
        if ($wzcost_rate) $where['cost_rate'] = $wzcost_rate;
        $sql_res = $payModel->add($where);
        if(!$sql_res){
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
                'sys_service_provider_id' => '2088421497824441'
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
//            'app_auth_token' => $token,
            'biz_content' => json_encode($content),
        );
        $string = $this->getSignContent($request);
        $sign = $this->rsaSign($string, $this->ali_private_key);

        $request['sign'] = $sign;
        get_date_dir($this->path,'alipay_js','下单数据', json_encode($request));

        $res_str = $this->curl($this->url, $request);
        $results = json_decode($res_str, true);
        $result = $results['alipay_trade_precreate_response'];
        if($result['code'] === '10000'){
            get_date_dir($this->path,'alipay_js','下单成功', json_encode($result));
            header("Location: $result[qr_code]");
        } else {
            echo '<script type="text/javascript">alert("网络异常，请稍后再试！")</script>';
            get_date_dir($this->path,'alipay_js','下单失败', json_encode($result));
        }
    }

    public function getRate($mch_id)
    {
        return M('merchants_ali')->where(array('ali_mchid' => $mch_id))->getField('rate');
    }

    public function ali_notify()
    {
        $post = $_POST;
        if($post['trade_status'] === 'TRADE_SUCCESS'){
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
            if (bccomp($orderPrice*100, $post['total_amount']*100, 3) === 0) {
                // 更改订单状态
                $save_data['paytime'] = time();
                $save_data['status'] = 1;
                $save_data['price_back'] = $post['buyer_pay_amount'];
                $save_data['remark_mer'] = $post['trade_no'];
                $this->pay_model->where(array('id' => $id))->save($save_data);
                get_date_dir($this->path,'alipay_notify','支付成功', json_encode($post));
                // 手机app推送消息
                A("App/PushMsg")->push_pay_message($remark);
//                    $notify->setReturnParameter("return_code", "SUCCESS");//设置返回码
                echo 'success';
            } else {
                get_date_dir($this->path,'alipay_notify','金额效验失败', json_encode($post));
                A("App/PushMsg")->push_pay_message($remark);
                echo 'fail';
            }
        } else {
            get_date_dir($this->path,'alipay_notify','支付失败', json_encode($post));
        }
    }

    public function loan_notify()
    {
        $post = $_POST;
        if($post['trade_status'] === 'TRADE_SUCCESS'){
            $remark = $post['out_trade_no'];
            $pay_info = M("order_loan")->where("order_sn='$remark'")->find();
            // 如果订单已支付返回成功
            if ($pay_info['pay_status'] == 1) {
                echo 'success';
                exit;
            }
            $orderPrice = $pay_info['price'];
            $id = $pay_info['id'];
            // 比较订单价格是否一致
            if (bccomp($orderPrice*100, $post['total_amount']*100, 3) === 0) {
                // 更改订单状态
                $save_data['pay_time'] = time();
                $save_data['pay_status'] = 1;
                $save_data['trade_no'] = $post['trade_no'];
                M("order_loan")->where(array('id' => $id))->save($save_data);
                get_date_dir($this->path,'alipay_loan_notify','支付成功', json_encode($post));
                // 手机app推送消息
                M('merchants_users')->where(array('id' => $pay_info['u_id']))->save(array('open_loan'=>1));
                echo 'success';
            } else {
                get_date_dir($this->path,'alipay_loan_notify','金额效验失败', json_encode($post));
                echo 'fail';
            }
        } else {
            get_date_dir($this->path,'alipay_loan_notify','支付失败', json_encode($post));
        }
    }

    /**
     * 刷卡支付 $id, $price, $auth_code, $checker_id
     */
    public function ali_micropay($id, $price, $auth_code, $checker_id,$order_sn,$mode=2)
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
        $remark = $order_sn?:$this->getRemark();
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
        $data['bank'] = 3;
//        添加的数据
        $wx_cost_rate = M("merchants_ali")->where("mid=" . $res['merchant_id'])->find();
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
                'sys_service_provider_id' => '2088421497824441'
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
            get_date_dir($this->path,'alipay_micro','支付成功', json_encode($result));
            return array("code" => "success", "msg" => "成功", "data" => $result['message']);
        } else if($result['code'] == '10003'){
            return $this->password($request,$remark);
        } else {
            A("App/PushMsg")->push_pay_message($remark);
            get_date_dir($this->path,'alipay_micro','支付失败', json_encode($result));
            return array("code" => "error", "msg" => "失败", "data" => $result['message']);
        }

    }
    /**
     * 刷卡支付 $id, $price, $auth_code, $checker_id
     */
    public function pos_ali_micropay($id, $price, $auth_code, $checker_id,$remark)
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
        $data['cate_id'] =  $res['id'];;
        $data['mode'] = 8;
        $data['paytime'] = time();
        $data['bank'] = 3;
//        添加的数据
        $wx_cost_rate = M("merchants_ali")->where("mid=" . $res['merchant_id'])->find();
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
                'sys_service_provider_id' => '2088421497824441'
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
            get_date_dir($this->path,'alipay_pos','支付成功', json_encode($result));
            return array("code" => "success", "msg" => "成功", "data" => $result['message']);
        } else if($result['code'] == '10003'){
            return $this->password($request,$remark);
        } else {
            A("App/PushMsg")->push_pay_message($remark);
            get_date_dir($this->path,'alipay_pos','支付失败', json_encode($result));
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
        get_date_dir($this->path,'alipay_micro','QUERY', json_encode($result));

        if($result['code'] == '10000' && $result['trade_status'] == 'TRADE_SUCCESS'){
            $succResult = 1;
            return $result;
        } else if($result['code'] == '10000' && $result['trade_status'] == 'WAIT_BUYER_PAY'){
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
        get_date_dir($this->path,'alipay_micro','CANCEL', $return);

        $results = json_decode($return, true);
        $result = $results['alipay_trade_cancel_response'];
        if($result['code'] == '10000'){
            return true;
        }else {
            return false;
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
        Vendor('SzWxPayPubHelper.WxPayPubHelper');
        $payBack = new \Refund_pub();
        // 查找交易记录表获取相关信息
        $pay = $this->pay_model->where("remark='$remark' And status = 1")->find();
        // 获取微信子商户ID
        $wx_mchid = M('merchants_ali')
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
            get_date_dir($this->path,'wxpay_back','成功退款:单号', $remark . ',返回数据:' . json_encode($result));
            return array("code" => "success", "msg" => "成功", "data" => "退款成功");
        } else {
            get_date_dir($this->path,'wxpay_back','退款失败:单号', $remark . ',返回数据:' . json_encode($result));
            return array('code' => 'error', 'msg' => '退款失败', 'data' => '失败');
        }
    }

    public function ali_pay_back($remark,$price_back)
    {
        $pay_data = $this->pay_model->where("remark='$remark' And status = 1")->find();
        if(empty($pay_data)){
            return array('code' => 'error', 'msg' => '退款失败', 'data' => '失败');
        }
        $token = M('merchants_ali')->where(array('mid' => $pay_data['merchant_id']))->getField('ali_token');
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
        get_date_dir($this->path,'alipay_back','REFUND-PARAMS', json_encode($request));
        $string = $this->getSignContent($request);
        $sign = $this->rsaSign($string, $this->ali_private_key);
        $request['sign'] = $sign;

        $return = $this->curl($this->url, $request);

        $results = json_decode($return, true);
        $result = $results['alipay_trade_refund_response'];
        if($result['code'] == '10000'){
            $data['status'] = '2';
            $data['back_status'] = '1';
            $data['price_back'] = $result['refund_fee'];
            $this->pay_model->where(array('remark' => $remark))->save($data);
            get_date_dir($this->path,'alipay_back','REFUND-SUCC', json_encode($return));
            return array("code" => "success", "msg" => "成功", "data" => "退款成功");
        }else {
            get_date_dir($this->path,'alipay_back','REFUND-FAIL', json_encode($return));
            return array('code' => 'error', 'msg' => '退款失败', 'data' => '失败');
        }
        
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
        $return = $this->curl($this->url, $request);
        get_date_dir($this->path,'alipay_token','接收参数', json_encode($_REQUEST));
        get_date_dir($this->path,'alipay_token','请求参数', json_encode($request));
        get_date_dir($this->path,'alipay_token','返回结果', $return);

        header("Content-type:text/html;charset=utf-8");
        $results = json_decode($return, true);
        $result = $results['alipay_open_auth_token_app_response'];
        M("merchants_ali")->where(array('mid' => $mid))->save(array('ali_token' => $result['app_auth_token']));
        if($result['code'] == '10000' && $result['msg'] == 'Success'){
            echo '<script type="text/javascript">alert("授权成功")</script>';
        }
    }
}
