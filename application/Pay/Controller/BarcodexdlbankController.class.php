<?php

namespace Pay\Controller;

use Common\Controller\HomebaseController;

/**
 * Class BarcodexdlbankController
 * @package Pay\Controller
 */
class BarcodexdlbankController extends HomebaseController
{
    private $opSys; // 操作系统 0：ANDROID 1：IOS 2：windows 3:直连
    private $payModel;
    private $url;
    private $characterSet = '00'; // 字符集
    private $signType = 'MD5'; // 签名方式
    private $version = 'V1.0.1'; // 签名方式
    private $pubVersion = 'V1.0.0'; // 签名方式
    private $orgNo; // 机构号7170
    private $mercId; // 商户号
    private $trmNo; // 终端设备号
    private $signKey; // 密钥
    private $cate_data; // 密钥
    private $server = 'http://gateway.starpos.com.cn/adpweb/ehpspos3/';//http://139.196.77.69:8280/adpweb/ehpspos3/ http://gateway.starpos.com.cn/adpweb/ehpspos3/
    private $remark;
    private $checker_id;
    private $jmt_remark;
    private $auth_code;
    private $pay_type;
    private $price;
    private $mode;
    private $channel;
    private $rate;
    private $id;
    private $notify_url;
    private $order_id = 0;
    private $customer_id = '';

    public function __construct()
    {
        parent::__construct();
        $this->notify_url = 'https://sy.youngport.com.cn/notify/xdl_notify.php';
        $this->payModel = M('pay');
        $this->orgNo = '7170';//7170
        $this->mercId = '800584000001927';//800290000005310
        $this->trmNo = '95077405';//95066032
        $this->signKey = '5AC3F315FF93B4D0ED4C607C85F38B45';//E29B72D4F4D1EFE145FC132C933DE9ED
    }

    public function scan()
    {
        $this->url = 'https://gateway.starpos.com.cn/sysmng/bhpspos4/5533020.do';
        $params['opsys'] = '0';
        $params['characterSet'] = $this->characterSet;
        $params['orgno'] = $this->orgNo;
        $params['mercid'] = $this->mercId;
        $params['trmno'] = $this->trmNo;
        $params['tradeno'] = $this->getRemark();
        $params['trmtyp'] = 'W';
        $params['txntime'] = date('YmdHis');
        $params['signtype'] = $this->signType;
        $params['version'] = $this->version;
        $params['amount'] = 1;
        $params['total_amount'] = 1;
        $params['paychannel'] = 'WXPAY'; //支付宝	ALIPAY 微信	WXPAY 银联	YLPAY
        $params['paysuccurl'] = $this->notify_url;
        $params['signvalue'] = $this->getSign($params);
        $this->writlog('JS_wx_pay.log', 'payParams：' . json_encode($params));
        $this->assign('param', $params);
        $this->display();
        exit;
    }

    public function returnurl()
    {
        $this->display();
        exit;
    }

    public function qr_weixipay()
    {
        //这里直接获得openid;
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            $id = I("id");
            $checker_id = I("checker_id");
            $merchant = M("merchants_cate")->where(array('id' => $id))->find();
            $openid = $this->get_openid();
            $this->getOffer($merchant, $openid);
            $this->assign("checker_id", $checker_id);
            $this->assign('openid', $openid);
            $this->assign("merchant", $merchant);
            $this->assign('seller_id', I("id"));
            $this->display();
            exit;
        }

    }

    public function wx_pay()
    {
        $sub_openid = I('openid','1');
        $id = I('seller_id');
        $price = I('price');
        $this->price = $price;
        $cate_info = $this->get_cate_info($id);
        $into_data = M('merchants_xdl')->where(array('m_id'=>$cate_info['merchant_id']))->find();
        $checker_id = $cate_info['checker_id'];
        $remark = $this->getRemark();
        $data = array(
            'merchant_id' => $cate_info['merchant_id'],
            'customer_id' =>  D("Api/ScreenMem")->add_member("$sub_openid", $cate_info['merchant_id']),
            'buyers_account' => '',
            'phone_info' => '',
            'wx_remark' => '',
            'wz_remark' => '',
            'new_order_sn' => '',
            'no_number' => '',
            'transId' => '',
            'la_ka_la' => 0,
            'add_time' => time(),
            'bill_date' => date('Ymd'),
            'checker_id' => $checker_id,
            'paystyle_id' => 1,
            'price' => $price,
            'remark' => $remark,
            'status' => 0,
            'cate_id' => $cate_info['id'],
            'mode' => I('mode',0),
            'bank' => 11,
            'cost_rate' => $into_data['wx_rate'],
            'subject' => $cate_info['jianchen'].$price,
            'remark_mer' => '',
        );
        $data['jmt_remark'] = I('memo','')?:I("jmt_remark",'');
        $db_res = true;
        if(!$this->payModel->where(array('remark'=>$remark))->find()){
            $db_res =  $this->payModel->add($data);
        }
        if($db_res!==false){
            $money = $price*100;
            $this->notify_url = "https://sy.youngport.com.cn/Pay/Barcode/weixipay_return000/price/{$money}/sub_openid/{$sub_openid}/remark/{$remark}/mid/{$cate_info[merchant_id]}";
            $this->getInfo($cate_info['merchant_id']);
            $this->fileName = 'wx_js_pay.log';
            $this->url = 'https://gateway.starpos.com.cn/sysmng/bhpspos4/5533020.do';
            $params['opsys'] = '0';
            $params['characterset'] = $this->characterSet;
            $params['orgno'] = $this->orgNo;
            $params['mercid'] = $this->mercId;
            $params['trmno'] = $this->trmNo;
            $params['tradeno'] = $remark;
            $params['trmtyp'] = 'W';
            $params['txntime'] = date('YmdHis');
            $params['signtype'] = $this->signType;
            $params['version'] = 'V1.0.0';
            $params['amount'] = $price*100;
            $params['total_amount'] = $price*100;
            $params['paychannel'] = 'WXPAY'; //支付宝	ALIPAY 微信	WXPAY 银联	YLPAY
            $params['paysuccurl'] = $this->notify_url;
            $params['signvalue'] = $this->getSign($params);
            $this->writlog('JS_wx_pay.log', 'payParams：' . json_encode($params));
            if($params['amount'] == 2){
//                exit("金额过低");
            }
            $this->assign('data',$params);
            $this->assign('url',$this->url);
            $this->display('wxpay');
            exit;
        } else {
            $this->alert_err();
        }
    }

    public function two_wx_pay()
    {
        $sub_openid = I('openid','1');
        $id = I('id');
        $mode = I('mode',0);
        $order_id = I('order_id');
        if ($order_id) {
            $order_info = M("order")->where("order_id='$order_id'")->find();
            $remark = $order_info['order_sn'];
            $price = $order_info['order_amount'];
            $this->price = $price;
            $cate_info = $this->get_cate_info($id);
            $into_data = M('merchants_xdl')->where(array('m_id' => $cate_info['merchant_id']))->find();
            $checker_id = $cate_info['checker_id'];
            $data = array(
                'order_id' => $order_id,
                'merchant_id' => $cate_info['merchant_id'],
                'customer_id' => D("Api/ScreenMem")->add_member("$sub_openid", $cate_info['merchant_id']),
                'buyers_account' => '',
                'phone_info' => '',
                'wx_remark' => '',
                'wz_remark' => '',
                'new_order_sn' => '',
                'no_number' => '',
                'transId' => '',
                'la_ka_la' => 0,
                'add_time' => time(),
                'bill_date' => date('Ymd'),
                'checker_id' => $checker_id,
                'paystyle_id' => 1,
                'price' => $price,
                'remark' => $remark,
                'status' => 0,
                'cate_id' => $cate_info['id'],
                'mode' => $mode,
                'bank' => 11,
                'cost_rate' => $into_data['wx_rate'],
                'subject' => $cate_info['jianchen'].$price,
                'remark_mer' => '',
            );
            $data['jmt_remark'] = I('memo', '') ?: I("jmt_remark", '');
            $db_res = true;
            if(!$this->payModel->where(array('remark'=>$remark))->find()){
                $db_res =  $this->payModel->add($data);
            }
            if ($db_res !== false) {
                $this->getInfo($cate_info['merchant_id']);
                $this->fileName = 'wx_js_pay.log';
                $this->url = 'https://gateway.starpos.com.cn/sysmng/bhpspos4/5533020.do';
                $money = $price*100;
                $this->notify_url = "https://sy.youngport.com.cn/Pay/Barcode/weixipay_return000/price/{$money}/sub_openid/{$sub_openid}/remark/{$remark}/mid/{$cate_info[merchant_id]}";
                $params['opsys'] = '0';
                $params['characterset'] = $this->characterSet;
                $params['orgno'] = $this->orgNo;
                $params['mercid'] = $this->mercId;
                $params['trmno'] = $this->trmNo;
                $params['tradeno'] = $remark;
                $params['trmtyp'] = 'W';
                $params['txntime'] = date('YmdHis');
                $params['signtype'] = $this->signType;
                $params['version'] = 'V1.0.0';
                $params['amount'] = $price*100;
                $params['total_amount'] = $price*100;
                $params['paychannel'] = 'WXPAY'; //支付宝	ALIPAY 微信	WXPAY 银联	YLPAY
                $params['paysuccurl'] = $this->notify_url;
                $params['signvalue'] = $this->getSign($params);
                $this->writlog('JS_wx_pay.log', 'payParams：' . json_encode($params));
                $this->assign('data', $params);
                $this->assign('url', $this->url);
                $this->display('wxpay');
                exit;
            } else {
                $this->alert_err();
            }
        } else {
            $this->alert_err();
        }
    }

    public function get_openid()
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

    # POS机_支付宝_条形码支付
    public function pos_ali_micropay($id, $price, $auth_code, $checker_id, $order_sn)
    {
        $this->checker_id = $checker_id;
        $this->jmt_remark = '';
        $this->auth_code = $auth_code;
        $this->remark = $order_sn?:$this->getRemark();
        $this->pay_type = 2;
        $this->price = $price;
        $this->mode = 5;
        $this->id = $id;
        $this->channel = 'ALIPAY';

        return $this->micropay();
    }

    # POS机_微信_条形码支付
    public function pos_micropay($id, $price, $auth_code, $checker_id, $order_sn)
    {
        $this->checker_id = $checker_id;
        $this->jmt_remark = '';
        $this->auth_code = $auth_code;
        $this->remark = $order_sn?:$this->getRemark();
        $this->pay_type = 1;
        $this->price = $price;
        $this->mode = 5;
        $this->id = $id;
        $this->channel = 'WXPAY';

        return $this->micropay();
    }

    # APP_支付宝_条形码支付
    public function ali_micropay($id, $price, $auth_code, $checker_id, $jmt_remark,$order_sn,$mode)
    {

        $this->checker_id = $checker_id;
        $this->jmt_remark = $jmt_remark;
        $this->auth_code = $auth_code;
        $this->remark = $order_sn?:$this->getRemark();
        $this->pay_type = 2;
        $this->price = $price;
        $this->mode = $mode?:2;
        $this->id = $id;
        $this->channel = 'ALIPAY';

        return $this->micropay();
    }

    # APP_微信_条形码支付
    public function wx_micropay($id, $price, $auth_code, $checker_id,$jmt_remark,$order_sn,$mode)
    {
//        get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/test/','2222',':日志', 111);
        $this->checker_id = $checker_id;
        $this->jmt_remark = $jmt_remark;
        $this->auth_code = $auth_code;
        $this->remark = $order_sn?:$this->getRemark();
        $this->pay_type = 1;
        $this->price = $price;
        $this->mode = $mode?:2;
        $this->id = $id;
        $this->channel = 'WXPAY';

//        if($jmt_remark == 'ypttest'){
//            $result = D('Pay/Pay')->card_off($auth_code, $id, $price, $this->remark, $checker_id, $jmt_remark);
//            if($result){
//                if($result['status'] === 1){
//                    A('Pay/Barcode')->cardOff($result['order_id']);
//                    A("App/PushMsg")->push_pay_message($this->remark);
//                    return array('code'=>'success','data' =>$result['pay_id']);
//                } else {
//                    $this->order_id = $result['order_id'];
//                    $this->customer_id = $result['customer_id'];
//                    $this->price = $result['price'];
//                }
//            }
//        }
        return $this->micropay();
    }

    # 支付宝_台签界面展示
    public function qr_alipay()
    {
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient') !== false) {
            $id = I('id');
            $merchant = M("merchants_cate")->where("id=$id")->find();
            $this->assign("checker_id", I('checker_id'));
            $this->assign("merchant", $merchant);
            $this->assign('seller_id', $id);
            $this->display();
            exit;
        }
    }

    # 支付宝_台签支付
    public function qr_to_alipay()
    {
        header("Content-type:text/html;charset=utf-8");
        $id = I("seller_id");
        $cate_info = $this->get_cate_info($id);
        $into_data = M('merchants_xdl')->where("m_id=$cate_info[merchant_id]")->find();
        $this->cate_data = $cate_info;
        $this->checker_id = I("checker_id");
        $this->price = I("price");
        if(empty($this->price)){
            $this->price = '';
        }
        $order_sn = I('order_sn','');
        $this->pay_type = 2;
        $this->remark = I('order_sn',date('YmdHis') . rand(100000, 999999));
        $this->mode = 0;
        if($order_sn){
            $this->remark = $order_sn;
            $this->order_id = M('order')->where(array('order_sn'=>$order_sn))->getField('order_id');
	    $this->order_id = $this->order_id?:0;
        }else{
            $this->remark = date('YmdHis') . rand(100000, 999999);
        }
        if(I('mode')){
            $this->mode=I('mode');
        }elseif($order_sn){
            $this->mode = 1;
        }
        $this->jmt_remark = I('memo','')?:I("jmt_remark",'');
        $this->rate = $into_data['ali_rate'];
        $this->orgNo = $into_data['orgNo'];
        $this->mercId = $into_data['mercId'];
        $this->trmNo = $into_data['trmNo'];
        $this->signKey = $into_data['signKey'];
        // 插入数据库的数据
        $db_res = $this->add();
        if($db_res){
            // 请求服务器获取js支付参数
            $this->url = $this->server . 'sdkBarcodePosPay.json';
            $this->channel = 'ALIPAY';
            $this->writlog('JS_ali_pay.log', 'qr_to_alipay：' . json_encode($_REQUEST));
            $res_arr = $this->ali_pay($into_data);
            // 判断返回结果
            if ($res_arr['returnCode'] == '000000' && $res_arr['result'] == 'S') {
                $url = $res_arr['payCode'];
                header("Location: $url");
            } else {
                $this->alert_err("$res_arr[message]");
            }
        } else {
            $this->alert_err();
        }
    }

    # 支付宝_已有订单号(双屏/APP二维码)
    public function two_alipay()
    {
        header("Content-type:text/html;charset=utf-8");
        $order_id = I("order_id");
        $id = I("seller_id");
        $this->mode = I("mode",3);
        if ($order_id) {
            $this->order_id = $order_id;
            $order_info = M('order')->where("order_id=$order_id")->find();
            $cate_info = $this->get_cate_info($id);
            $into_data = M('merchants_xdl')->where("m_id=$cate_info[merchant_id]")->find();
            $this->cate_data = $cate_info;
            $this->checker_id = I("checker_id",0);
            $this->price = $order_info["order_amount"];
            $this->pay_type = 2;
            $this->remark = $order_info['order_sn']?:date('YmdHis') . rand(100000, 999999);
            $this->mode = 0;
            $this->jmt_remark = I('memo','')?:I("jmt_remark",'');
            $this->rate = $into_data['ali_rate'];
            $this->orgNo = $into_data['orgNo'];
            $this->mercId = $into_data['mercId'];
            $this->trmNo = $into_data['trmNo'];
            $this->signKey = $into_data['signKey'];
            // 插入数据库的数据
            $db_res = $this->add();
            if($db_res){
                // 请求服务器获取js支付参数
                $this->url = $this->server . 'sdkBarcodePosPay.json';
                $this->channel = 'ALIPAY';
                $this->writlog('JS_ali_pay.log', 'two_alipay：' . json_encode($_REQUEST));
                $res_arr = $this->ali_pay($into_data);
                // 判断返回结果
                if ($res_arr['returnCode'] == '000000' && $res_arr['result'] == 'S') {
                    $url = $res_arr['payCode'];
                    header("Location: $url");
                } else {
                    $this->alert_err();
                }
            } else {
                $this->alert_err();
            }
        }else {
            $this->alert_err('订单号为空');
        }
    }

    private function micropay()
    {
        $this->url = $this->server . 'sdkBarcodePay.json';
        if (!$this->auth_code || !$this->id || $this->price < 0.01) $this->ajaxReturn(array("code" => "error", "msg" => "参数错误"));
        $this->cate_data = M('merchants_cate')->where(array('merchant_id'=>$this->id,'status'=>1))->find();
        $this->mercId = M('merchants_xdl')->where(array('m_id' => $this->cate_data['merchant_id']))->getField('mercId');
        $rate_name = $this->channel=='ALIPAY' ? 'ali_rate' : 'wx_rate';
        $this->rate = M('merchants_xdl')->where(array('m_id' => $this->cate_data['merchant_id']))->getField($rate_name);
        $this->getInfo($this->id);
        $add_res = $this->add();
        if ($add_res) {
            $params = $this->requestHead();
            $params['tradeNo'] = $this->remark;
            $params['amount'] = $this->price * 100;
            $params['total_amount'] = $this->price * 100;
            $params['authCode'] = $this->auth_code;
            $params['payChannel'] = $this->channel; //支付宝	ALIPAY 微信	WXPAY 银联	YLPAY
            $params['signValue'] = $this->getSign($params);
            $this->writlog('micro.log', 'payParams：' . json_encode($params));
            $return = $this->requestPost(json_encode($params));
            $result = json_decode(urldecode($return), true);
            if ($result['returnCode'] == '000000' && $result['result'] == 'S') {
                $this->writlog('micro.log', '支付成功：' . urldecode($return));
                $save = array(
                    "status" => "1",
                    "paytime" => time(),
                    'transId' => $result['logNo'],
                    'new_order_sn' => $result['orderNo'],
                );
                $this->payModel->where(array("remark" => $this->remark))->save($save);
                if($this->mode!=5 && $this->mode!=25){
                    A("App/PushMsg")->push_pay_message($this->remark);
                }
                if($this->order_id != 0){
                    A('Pay/Barcode')->cardOff($this->order_id);
                }
                return array("code" => "success", "msg" => "成功", "data" => '支付成功');
            } else if ($result['returnCode'] == '000000' && ($result['result'] == "A" || $result['result'] == "Z")) {
                $this->writlog('micro.log', '输入密码：' . urldecode($return));
                return $this->password($this->remark);
            } else {
                $this->writlog('micro.log', '支付失败：' . urldecode($return));
                return array("code" => "error", "msg" => "失败", "data" => $result['message']);
            }
        }
    }

    public function ali_pay()
    {
        $params = $this->requestHead();
        $params['tradeNo'] = $this->remark;
        $params['amount'] = $this->price * 100;
        $params['total_amount'] = $this->price * 100;
        $params['payChannel'] = $this->channel; //支付宝	ALIPAY 微信	WXPAY 银联	YLPAY
        $params['signValue'] = $this->getSign($params);
        $this->writlog('JS_ali_pay.log', 'payParams：' . json_encode($params));
        $return = $this->requestPost(json_encode($params));
        $result = json_decode(urldecode($return), true);
        $this->writlog('JS_ali_pay.log', 'payResult：' . json_encode($result));

        return $result;
    }

    # 查询订单状态
    public function query($order_sn)
    {
        $this->url = $this->server . 'sdkQryBarcodePay.json';
        $params = $this->requestHead();
        $params['tradeNo'] = $this->getRemark();
        $params['qryNo'] = $order_sn;
        $params['signValue'] = $this->getSign($params);
        $this->writlog('query.log', 'PARAMS：' . json_encode($params));
        $result = $this->requestPost(json_encode($params));
        $this->writlog('query.log', 'queryResult：' . urldecode($result));

        return json_decode(urldecode($result), true);
    }

    # 撤销订单
    public function reverse($order_sn)
    {
        $this->url = $this->server . 'RevokeBarcodepay.json';
        $params = $this->requestHead();
        $params['tradeNo'] = $this->getRemark();
        $params['qryNo'] = $order_sn;
        $params['signValue'] = $this->getSign($params);
        $result = $this->requestPost(json_encode($params));
        $this->writlog('micro.log', 'cancelResult：' . urldecode($result));

        return json_decode(urldecode($result), true);
    }

    public function pay_back($remark, $price_back)
    {
        $pay = $this->payModel->where(array("remark" => $remark))->find();
        if (!$pay) {
            return array("code" => 'error', "msg" => "该订单不存在");
        }
        $back_order = $pay['new_order_sn'];
        if ($pay['status'] == "2") {
            return array("code" => 'error', "msg" => "不能重复退款");
        }
        $merchant_id = $pay['merchant_id'];
        $res = M("merchants_cate")->where("merchant_id=$merchant_id")->find();
        if (!$res) {
            return array("code" => 'error', "msg" => "商户不存在");
        }
        $this->getInfo($merchant_id);
        $this->url = $this->server . 'sdkRefundBarcodePay.json';
        $params = $this->requestHead();
        $params['tradeNo'] = $this->getRemark();
        $params['orderNo'] = $back_order;
        $params['txnAmt'] = $price_back * 100;
        $params['signValue'] = $this->getSign($params);
        $this->writlog('payback.log', $merchant_id.'backParams：' . json_encode($params));
        $return = $this->requestPost(json_encode($params));
        $result = json_decode(urldecode($return), true);
        if ($result['returnCode'] == '000000' && $result['result'] == 'S') {
            $this->payModel->where("remark='$remark'")->save(array("status" => 2, "back_status" => 1, "price_back" => $result['txnAmt'] / 100));
            $this->writlog('payback.log', '退款成功：' . urldecode($return));
            return array("code" => "success", "msg" => "成功", "data" => "退款成功");
        } else {
            $this->writlog('payback.log', '退款失败：' . urldecode($return));
            return array("code" => "error", "msg" => "error", "data" => "退款失败");
        }
    }

    public function all_query()
    {
        $this->url = $this->server . 'sdkBarcodePosPay.json';
        $this->getInfo(84);
        $params = $this->requestHead();
        $params['tradeNo'] = $this->getRemark();
        $params['amount'] = 1;
        $params['total_amount'] = 1;
        $params['payChannel'] = 'WXPAY'; //支付宝	ALIPAY 微信	WXPAY 银联	YLPAY
        $params['signValue'] = $this->getSign($params);
        $this->writlog('JS_wx_pay.log', 'payParams：' . json_encode($params));
        $return = $this->requestPost(json_encode($params));
        $result = json_decode(urldecode($return), true);
        $this->writlog('JS_wx_pay.log', 'return：' .$return);
        $this->writlog('JS_wx_pay.log', 'returnJSON：' .json_encode($result));

        return $result;
    }

    public function notify()
    {
        $notify_data = file_get_contents('php://input', 'r');
        $result_arr = json_decode($notify_data,true);
        $this->writlog('notify.log', ' 接收异步通知数据:'.json_encode($result_arr));
        if (isset($result_arr['TxnStatus']) && $result_arr['TxnStatus'] == '1') {
            if($result_arr['BusinessId'] == '800603000000617'){
                $this->url = 'http://onesecond.ypt5566.com/c1/xdlpay/notify';
                $this->requestPost(json_encode($result_arr),5);
            }
            if($result_arr['BusinessId']=='800584000001035'){
                $url="http://www.ypt5566.com/index.php?s=/Wxpay1/notify";
                $str='';
                foreach ($result_arr as $key => $value) {
                    $str.='/'.$key.'/'.$value;
                }
                header("Location: " . $url.$str);
            }
            header('Content-type: application/json');
            $order_sn = $result_arr['TxnLogId'];
            $transId = $result_arr['OfficeId'];
            #如果是储值订单
            if(strpos($order_sn,'cz')){
                $this->writlog('cz_notify.log',json_encode($result_arr));
                $this->parse_cz($order_sn,$result_arr['TxnAmt']*100,$transId);
            }
            #如果是pay-api订单
            if(strpos($order_sn,'PA')){
                $url="https://sy.youngport.com.cn/Pay/Notify/xdl_notify";
                $str='';
                foreach ($result_arr as $key => $value) {
                    $str.='/'.$key.'/'.$value;
                }
                header("Location: " . $url.$str);
            }
            $orderData = $this->payModel->where(array('remark' => $order_sn))->find();
            if($orderData){
                if ($orderData['status'] == 0) {
                    if($result_arr['TxnAmt']*100 == $orderData['price']*100){
                        $update['paytime'] = time();
                        $update['status'] = '1';
                        $update['transId'] = $transId;
                        $update['new_order_sn'] = $transId;
                        $this->payModel->where(array('remark'=>$order_sn))->save($update);
                        $this->writlog('notify.log', ' 支付成功');
                        if($result_arr['TxnCode'] == 'N007' && $orderData['order_id'] != 0){
                            A('Barcode')->cardOff($orderData['order_id']);
                        }
                        A("App/PushMsg")->push_pay_message($order_sn);
                        $this->norify_succ();
                    } else {
                        $this->writlog('notify.log', ' 金额不符');
                    }
                } else if($orderData['status'] == 1){
                    $this->writlog('notify.log', ' 二次通知');
                    $this->norify_succ();
                } else {
                    $this->writlog('notify.log', ' 订单状态异常');
                    $this->writlog('notify.log', ' 订单详情:'.json_encode($orderData));
                    echo json_encode(array('RspCode'=>'111111','RspDes'=>'error'));exit;
                }
            } else {
                // API项目中是否存在该订单
                $db_shanpay = C('DB_SHANPAY');
                $api_data = M("api_order","ypt_","$db_shanpay")->where(array('remark' => $order_sn))->getField('id');
                if($api_data){
                    $this->writlog('api_notify.log', ' API订单:'.$api_data);
                    $url="https://api.youngport.com.cn/api/notify/xdl_notify";
                    $str='';
                    foreach ($result_arr as $key => $value) {
                        $str.='/'.$key.'/'.$value;
                    }
                    header("Location: " . $url.$str);
                    exit;
                }
                if($result_arr['TxnCode'] == 'L005'){
                    $this->writlog('_notify_refund.log', ' 退款详情:'.json_encode($result_arr));
                    $this->norify_succ();
                }
                $remark_mer = $result_arr['logNo'];
                $orderData = $this->payModel->where(array('remark_mer' => $remark_mer))->find();
                if($orderData){
                    $this->writlog('_notify.log', ' 已存在该电子立牌订单:'.$order_sn);
                    $this->norify_succ();
                } else {
                    $this->addOrder($result_arr);
                }
                $this->writlog('_micro_notify.log', ' 没订单的回调:'.json_encode($result_arr));
                $this->norify_succ();
            }
        }else {
            if($result_arr['BusinessId'] == '800603000000617'){
                $this->url = 'http://onesecond.ypt5566.com/c1/xdlpay/notify';
                $this->requestPost(json_encode($result_arr),5);
            }
            if(!$result_arr){
                $this->writlog('notify.log', '_REQUEST'.json_encode($_REQUEST));
                $url = "https://sy.youngport.com.cn/index.php?s=/Pay/Barcodexdlbank/returnurl";
                header("Location: $url");
                exit;
            }
            $this->writlog('notify.log', '支付失败');
            echo json_encode(array('RspCode'=>'222222','RspDes'=>'error'));
            if($result_arr['BusinessId']=='800584000001035'){
                $url="http://www.ypt5566.com/index.php?s=/Wxpay1/notify";
                $str='';
                foreach ($result_arr as $key => $value) {
                    $str.='/'.$key.'/'.$value;
                }
                header("Location: " . $url.$str);
            }

        }
    }

    #处理储值订单
    private function parse_cz($order_sn,$real_price,$transId)
    {
        $order = M('user_recharge')->where(array('order_sn' => $order_sn,'status'=> 0))->find();
        if ($order) {
            $token = get_weixin_token();
            M('user_recharge')->where(array('id' => $order['id']))->save(array('status' => 1, 'update_time' => time(), 'real_price' => $real_price/100, 'transId' => $transId));
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
                    M('screen_memcard_log')->add(array('add_time' => time(), 'update_time' => time(), 'value' => $integral, 'balance' => $save['card_balance'], 'ts' => json_encode($ts),'msg'=>$json_msg,'ts_status'=>$ts_status, 'order_sn' => $order_sn, 'code' => $screen_memcard_use['card_code'],'record_bonus'=>'充值送积分'));
                    $ts=array();
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
            $msg = request_post('https://api.weixin.qq.com/card/membercard/updateuser?access_token='.$token,urldecode($ts));
            $yue['ts_msg'] = $msg;
            $msg = json_decode($msg);
            if($msg->errcode==0){
                $yue['ts_status'] = 1;
            }
            M('user_yue_log')->add($yue);
            //记录流水
            if (!$this->payModel->where(array('remark' => $order_sn, 'mode' => 12))->find()) {
                $pay['merchant_id'] = $order['mid'];
                $pay['customer_id'] = $order['uid'];
                $pay['paystyle_id'] = $order['paystyle_id'];
                $pay['order_id'] = $order['id'];
                $pay['mode'] = 12;
                $pay['price'] = $real_price/100;
                $pay['remark'] = $order_sn;
                $pay['add_time'] = $order['add_time'];
                $pay['paytime'] = time();
                $pay['bill_date'] = date('Ymd');
                $pay['new_order_sn'] = $order_sn;
                $pay['transId'] = $transId;
                $pay['cate_id'] = $order['cate_id'];
                $pay['status'] = 1;
                $pay['bank'] = 11;
                $pay['cost_rate'] = M('merchants_xdl')->where(array('m_id'=>$order['mid']))->getField('wx_rate');
                $this->payModel->add($pay);
            }
            # 充值会员卡成功，需要给消费者微信推送消息
            if($screen_memcard_use['fromname']){
                A('Wechat/Message')->recharge($screen_memcard_use['fromname'],$order['price'],$order['send_price'],$screen_memcard['merchant_name'],$yue['yue']);
            }
            $this->norify_succ();
        }
    }

    private function norify_succ()
    {
        $this->ajaxReturn(array('RspCode'=>'000000','RspDes'=>'success'));exit;
    }

    public function ttt()
    {
        $str = '{"status":1,"merchant_id":"966","paytime":1521744582,"transId":"UD180323A01175300702492689394894","new_order_sn":"UD180323A01175300702492689394894","la_ka_la":0,"add_time":1521744583,"bill_date":"20180323","price":"3100","remark":"20180323024943435246","remark_mer":"201803230209556524","bank":"11","cost_rate":"0.35","subject":"\u7535\u5b50\u7acb\u724c\u652f\u4ed83100\u5143","paystyle_id":"1","mode":"20"}';
        $arr = json_decode($str, true);
        $remark = $arr['ChannelId'];
        $dn_res = $this->payModel->where(array('remark' => $remark))->getField('id');
//        $dn_res = $this->payModel->add($arr);
        echo M()->getLastSql();
        dump($dn_res);
        if($dn_res){
            echo 'aaa';
        } else {
            echo '000';
        }

    }
    private function addOrder($info)
    {
        $remark = $info['ChannelId'];
        $dn_res = $this->payModel->where(array('remark' => $remark))->getField('id');
        if($dn_res){
            $this->writlog('_notify.log', ' 已存在该单为POS机订单:'.json_encode($info));
//            $this->norify_succ();
            exit;
        }
        $this->writlog('_notify.log', ' 系统不存在该订单:'.json_encode($info));
        $data = $this->getCate($info['BusinessId']);
        $time = $info['TxnDate'] . $info['TxnTime'];
        $pay_data = array(
            'status'        => 1,
            'merchant_id'   => $data['m_id'],
            'paytime'       => strtotime($time),
            'transId'       => $info['OfficeId'],
            'new_order_sn'  => $info['OfficeId'],
            'la_ka_la'      => 0,
            'add_time'      => time(),
            'bill_date'     => $info['TxnDate'],
            'price'         => $info['TxnAmt'],
            'remark'        => $this->getRemark(),
            'remark_mer'    => $info['logNo'],
            'bank'          => '11',
            'subject'       => '电子立牌支付'.$info['TxnAmt'].'元',
        );
        $pay_data['paystyle_id'] = $this->get_paystyle_id($info['PayChannel']);
        $pay_data['cost_rate'] = $this->get_cost_rate($pay_data['paystyle_id'], $data);
        $pay_data['mode'] = $this->get_mode($info['TxnCode']);

        $res = $this->payModel->add($pay_data);
        $this->writlog('_notify.log', ' 电子立牌订单入库数据:'.json_encode($pay_data));
        $this->writlog('_notify.log', ' 电子立牌订单入库结果:'.$res);
        if($res){
            $this->norify_succ();
        } else {
//            $this->payModel->add($pay_data);
        }
    }

    private function get_cost_rate($paystyle, $info)
    {
        switch ($paystyle) {
            case '1':
                $rate = $info['wx_rate'];
                break;
            case '2':
                $rate = $info['ali_rate'];
                break;
            case '3':
                $rate = $info['debit_rate'];
                break;
            case '4':
                $rate = $info['credit_rate'];
                break;
            default:
                $rate = $info['wx_rate'];
                break;
        }
        return $rate;
    }

    private function get_mode($TxnCode)
    {
        switch ($TxnCode) {
            case 'N001':
                $mode = '5';
                break;
            case 'N002':
                $mode = '20';
                break;
            case 'N007':
                $mode = '1';
                break;
            case 'L020':
                $this->writlog('_notify.log', ' 银行卡支付不处理:'.$TxnCode);
                exit();
                break;
            default:
                $this->writlog('_notify.log', ' 未知mode:'.$TxnCode);
                exit();
                break;
        }
        return $mode;
    }

    private function get_paystyle_id($PayChannel)
    {
        switch ($PayChannel) {
            case '1':
                $paystyle_id = '2';
                break;
            case '2':
                $paystyle_id = '1';
                break;
            default:
                $paystyle_id = '3';
                break;
        }
        return $paystyle_id;
    }

    private function getCate($BusinessId)
    {
        $info = M('merchants_xdl')->where(array('mercId' => $BusinessId))->find();
        return $info;
    }

    # 获取台签信息
    private function get_cate_info($id)
    {
        $res = M('merchants_cate')->where(array('id'=>$id))->find();
        return $res;
    }

    # 轮询条码支付订单
    public function password($order_sn)
    {
        $queryTimes = 18;
        while ($queryTimes--) {
            sleep(5);
            $queryRes = $this->query($order_sn);
            if ($queryRes['returnCode'] == '000000') {
                $result = $queryRes['result'];
                if ($result == 'S') {   // 支付成功
                    $brr = array("status" => "1", "paytime" => time(), 'transId' => $queryRes['logNo'], 'new_order_sn' => $result['orderNo'],);
                    $this->writlog('micro.log', 'querySucc：' . json_encode($queryRes));
                    $this->payModel->where(array("remark" => $order_sn))->save($brr);
                    return array("code" => "success", "msg" => "失败", "data" => '支付失败');
                } else if ($result == 'A') {    // 等待密码
                    continue;
                } else if ($result == 'Z') {    // 未知状态
                    continue;
                } else if ($result == 'F') {    // 支付失败
//                    $this->payModel->where(array("remark" => $order_sn))->save(array("status" => "-2"));
                    return array("code" => "error", "msg" => "失败", "data" => '支付失败');
                } else if ($result == 'D') {    // 已撤销
//                    $this->payModel->where(array("remark" => $order_sn))->save(array("status" => "-2"));
                    return array("code" => "error", "msg" => "失败", "data" => '支付失败');
                }
            } else {
                $this->writlog('micro.log', '请求失败：' . json_encode($queryRes));
//                $this->payModel->where(array("remark" => $order_sn))->save(array("status" => "-2"));
                return array("code" => "error", "msg" => "失败", "data" => '支付失败');
            }
        }
        $res = $this->reverse($order_sn);
        sleep(3);
        if ($res['returnCode'] == '000000' && $res['result'] == 'S') {
            $this->payModel->where(array("remark" => $res['out_trade_no']))->save(array("status" => "-2"));
            return array("code" => "error", "msg" => "失败", "data" => '交易时间过长,支付失败');;
        }
    }

    # 将订单插入数据库
    private function add()
    {
        //插入数据库的数据
        $data['merchant_id'] = (int)$this->cate_data['merchant_id'];//商户ID
        $data['phone_info'] = $_SERVER['HTTP_USER_AGENT'];
        $data['customer_id'] = $this->customer_id;              //买方账号ID
        $data['buyers_account'] = '';              //买方账号ID
        $data['order_id'] = $this->order_id?:0;              //买方账号ID
        $data['checker_id'] = $this->checker_id;              //收银员的ID
        $data['paystyle_id'] = $this->pay_type;               //支付方式 1是微信 2是支付宝
        $data['price'] = $this->price;
        $data['remark'] = $this->remark;                    //订单号
        $data['status'] = 0;                            //待付款
        $data['cate_id'] = $this->cate_data['id'];                  //支付样式,台签类别
        $data['mode'] = $this->mode;                              //0 为台签支付 1为扫码支付  2刷卡支付
        $data['jmt_remark'] = $this->jmt_remark;
        $data['add_time'] = time();                     //下单时间
        $data['subject'] = "向" . $this->cate_data['jianchen'] . "支付" . $this->price . "元";
        $data['bank'] = 11;
        $data['cost_rate'] = $this->rate;
        if($this->payModel->where(array('remark'=>$this->remark))->find()){
            return $this->payModel->where(array('remark'=>$this->remark))->save($data);
        }else{
            return $this->payModel->add($data);
        }
    }

    private function getInfo($merchant_id)
    {
        $re = M('merchants_xdl')->where(array('m_id' => $merchant_id))->find();
        $this->orgNo = $re['orgNo'];
        $this->mercId = $re['mercId'];
        $this->trmNo = $re['trmNo'];
        $this->signKey = $re['signKey'];
    }

    # 发送请求
    private function requestPost($data, $second = 30)
    {
        $header = array("Content-type:application/json;charset=UTF-8");
        //初始化curl
        $curl = curl_init();
        //设置超时
        curl_setopt($curl, CURLOPT_TIMEOUT, $second);
        curl_setopt($curl, CURLOPT_URL, $this->url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        //post提交方式
        curl_setopt($curl, CURLOPT_POST, TRUE);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        //运行curl
        $res = curl_exec($curl);
        //返回结果
        if ($res) {
            curl_close($curl);
            return $res;
        } else {
            $error = curl_errno($curl);
            $this->writlog('request.log', 'ERROR：' . "curl出错，错误码:$error");
            curl_close($curl);
            return false;
        }
    }

    # 组织请求头部参数
    private function requestHead()
    {
        $header = array();
        $header['opSys'] = '0';
        $header['orgNo'] = $this->orgNo;
        $header['characterSet'] = $this->characterSet;
        $header['mercId'] = $this->mercId;
        $header['trmNo'] = $this->trmNo;
        $header['txnTime'] = date('YmdHis');
        $header['signType'] = $this->signType;
        $header['version'] = $this->version;

        return $header;
    }

    private function getRemark()
    {
        return date('YmdHis') . rand(100000, 999999);
    }

    private function getSign($params)
    {
        ksort($params);
//        $this->writlog('JS_wx_pay.log', 'SortParams：' . json_encode($params));
        $str = '';
        foreach ($params as $v) {
            $str .= $v;
        }

        return md5($str . $this->signKey);
    }

    # 错误信息展示
    private function alert_err($msg = '网络异常，请重试！')
    {
        $this->assign('err_msg',"$msg");
        $this->display(":Barcodexybank/error");
        exit;
    }

    private function writlog($file_name, $data)
    {
        $path = $this->get_date_dir();
        file_put_contents($path . $file_name, date("H:i:s") . $data . PHP_EOL . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    private function get_date_dir($path = '/data/log/xindalu/')
    {
        $Y = $_SERVER['DOCUMENT_ROOT'] . $path . date("Y-m");
        $d = $Y . '/' . date("m-d");
        if (file_exists($Y)) {
//            echo '存在';
        } else {
            mkdir($Y, 0777, true);
        }
        if (!file_exists($d)) mkdir($d, 0777);

        return $d . '/';
    }

    public function get_card_recharge_url($order,$cate_info)
    {
        $this->price = $order['price'];
        $this->remark = $order['order_sn'];
        $into_data = M('merchants_xdl')->where("m_id=$cate_info[merchant_id]")->find();
        $this->rate = $into_data['ali_rate'];
        $this->orgNo = $into_data['orgNo'];
        $this->mercId = $into_data['mercId'];
        $this->trmNo = $into_data['trmNo'];
        $this->signKey = $into_data['signKey'];

        $this->url = $this->server . 'sdkBarcodePosPay.json';
        $this->channel = 'ALIPAY';
        $res_arr = $this->ali_pay($into_data);
        // 判断返回结果
        if ($res_arr['returnCode'] == '000000' && $res_arr['result'] == 'S') {
            $return['code'] = '0000';
            $return['data'] = $res_arr['payCode'];
        } else {
            $return['code'] = '0001';
            $return['msg'] = $res_arr['message'];
        }

        return $return;
    }

    public function precreate($input)
    {
        $pay_style = $input['pay_style'];
        switch ($pay_style) {
            case 1:
                $this->channel = 'WXPAY';
                break;
            case 2:
                $this->channel = 'ALIPAY';
                break;
            case 5:
                $this->channel = 'YLPAY';
                break;
            default:
                $this->channel = 'WXPAY';
                break;
        }
        $into_data = M('merchants_xdl')->where("m_id=$input[mch_id]")->find();
        if(!$into_data) return array('code'=>'1001', 'msg'=>'未进件，请联系客服。');
        $this->rate = $into_data['ali_rate'];
        $this->orgNo = $into_data['orgNo'];
        $this->mercId = $into_data['mercId'];
        $this->trmNo = $into_data['trmNo'];
        $this->signKey = $into_data['signKey'];

        // 请求服务器获取js支付参数
        $this->url = $this->server . 'sdkBarcodePosPay.json';
        $params = $this->requestHead();
        $params['tradeNo'] = $input['remark'];
        $params['amount'] = $input['amount'] * 100;
        $params['total_amount'] = $input['amount'] * 100;
        $params['payChannel'] = $this->channel; //支付宝	ALIPAY 微信	WXPAY 银联	YLPAY
        $params['signValue'] = $this->getSign($params);

        $this->writlog('precreate.log', '请求参数：' . json_encode($params));
        $return = $this->requestPost(json_encode($params));
        $result = json_decode(urldecode($return), true);
        // 判断返回结果
        if ($result['returnCode'] == '000000' && $result['result'] == 'S') {
            $this->writlog('precreate.log', '请求成功：' . json_encode($result));
            $url = $result['payCode'];
            return array('code'=>'0000', 'url'=>$url,'rate'=>$this->rate);
        } else {
            $this->writlog('precreate.log', '失败：' . json_encode($result));
            return array('code'=>'1001', 'msg'=>$result['message']);
        }
    }
}