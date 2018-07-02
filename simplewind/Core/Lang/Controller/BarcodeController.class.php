<?php

namespace Pay\Controller;

use Common\Controller\HomebaseController;

/**
 * 首页
 */
class BarcodeController extends HomebaseController
{


    public function index()
    {
        $map = 1;
        $url = 'http://sy.youngport.com.cn/index.php?s=/Barcode/barcode/';
        $this->assign('map', $map);
        $this->assign('url', $url);
        $this->display();
    }

    public function qrcode()
    {
        header("content-type:text/html;charset=utf-8");
        $id = I('id');
        $res = M('merchants_cate')->field('status,no_number')->where('id=' . $id)->find();

        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {

            if ((int)$res['status'] == 1) {
                $url = "https://sy.youngport.com.cn/index.php?g=Pay&m=Barcode&a=qr_weixipay&id=" . $id;
                header("Location: $url");
            } else {
                echo "<div style='margin: 10px auto;font-size: 30px;;width:80%';color:red>编号为：" . $res['no_number'] . " 的商家未上线 </div>";
            }
        } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient') !== false) {
            if ((int)$res['status'] == 1) {
                $url = "https://sy.youngport.com.cn/index.php?g=Pay&m=Barcode&a=qr_alipay&id=".I('id');
                header("Location: $url");
            } else {
                echo "<div style='margin: 10px auto;font-size: 30px;width:80%';color:red>编号为：" . $res['no_number'] . " 的商家未上线 </div>";
            }

        } else {
            echo "请用微信或者支付宝扫码~";
        }

    }

    // 微信支付界面跳转
    public function qr_weixipay()
    {
        $openid=$this->_get_openid();

        $this->assign('seller_id', I('id'));
        $this->assign('openid',$openid);
        $this->display();
//        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
//            $this->assign('seller_id', I('id'));
//            $this->display();
//        }
    }

    // 支付宝支付界面跳转
    public function qr_alipay()
    {
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient') !== false) {
            $id = I('id');
            $this->assign('seller_id', $id);
            $this->display();
        }
    }

//    阿里支付完成，生成订单
    public function qr_to_alipay()
    {
//       $seller_id 二维码对应的id

        $seller_id = '';
        if (I('seller_id')) {
            $seller_id = I('seller_id');
            $res = M('merchants_cate')->where('id=' . $seller_id)->find();
        }
        header("Content-type:text/html;charset=utf-8");
        Vendor('QRcodeAlipay.AlipaySubmit');
        Vendor('QRcodeAlipay.AlipayConfig');
        $config = new \AlipayConfig();
//        1
        $alipay_config = $config->con($res['partner']);
        $remark = date('YmdHis') . rand(100000, 999999);
        $price = I('price') ? I('price') : '0.01';
        $good_name = "向" . $res['name'] . "支付" . $price . "钱";
//       支付订单提交的数据交互
        $data['merchant_id'] = $res['merchant_id'];
        $data['paystyle_id'] = 2;
        $data['price'] = $price;
        $data['remark'] = $remark;
        $data['status'] = 0;
        $data['paytime'] = time();
//        $data['good_name'] = $good_name;

        M("pay")->add($data);
        /**************************请求参数**************************/

        //商户订单号，商户网站订单系统中唯一订单号，必填
        $out_trade_no = $remark;

        //订单名称，必填
        $subject = $good_name;

        //付款金额，必填
        $total_fee = $price;

        //收银台页面上，商品展示的超链接，必填
        $show_url = $_POST['WIDshow_url'];

        //商品描述，可空
        $body = $res['name'];


        /************************************************************/

        //构造要请求的参数数组，无需改动
        $parameter = array(
            "service"        => $alipay_config['service'],
//            支付宝的ID
            "partner"        => $res['alipay_partner'],
            "seller_id"      => $res['alipay_partner'],
            "payment_type"   => $alipay_config['payment_type'],
            //"notify_url"	=> $alipay_config['notify_url'],
            "return_url"     => "http://sy.youngport.com.cn/index.php?s=/Pay/Barcode/qrcode_alipay_return",
            "_input_charset" => trim(strtolower($alipay_config['input_charset'])),
            "out_trade_no"   => $out_trade_no,
            "subject"        => $subject,
            "total_fee"      => $total_fee,
            "show_url"       => $show_url,
            "app_pay"        => "Y",//启用此参数能唤起钱包APP支付宝
            "body"           => $body,

        );

        $alipaySubmit = new \AlipaySubmit($alipay_config);
        $html_text = $alipaySubmit->buildRequestForm($parameter, "get", "确认");
        echo $html_text;
    }

//阿里支付成功检测，修改支付状态
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
        $data['paytime'] = time();

        if ($trade_status == 'TRADE_FINISHED' || $trade_status == 'TRADE_SUCCESS') {
            $res = M('pay')->where("remark=$out_trade_no")->save($data);
            if ($res) {
                $this->assign('price', $_GET['total_fee']);
                $this->display();
            }
        } else {
            echo "trade_status=" . $_GET['trade_status'];
        }

        //echo "验证成功<br />";


    }



    public function check_order()
    {
        echo 567;
        exit;

    }

    /**
     * 微信支付
     * 公账号支付
     */
    public function wz_pay()
    {
//        echo  'https://sy.youngport.com.cn'.$_SESSION['HTTP_HOST'].$_SERVER['REQUEST_URI'];
//        exit;
//        得到输入的金额和商户的ID
//        unset($_SESSION["mchid"]);
        header("Content-type:text/html;charset=utf-8");
        vendor('Wzpay.Wzpay');
        $wzPay = new \Wzpay();
//        先获取openid防止 回调

        if (I('seller_id') !=="") {
            $sub_openid=I('openid');
            $id = I('seller_id');
            $price = I('price');
            $res = M('merchants_cate')->where("id=$id")->find();
            $remark = date('YmdHis') . rand(100000, 999999);
//            插入数据库的数据
            $data['merchant_id'] = (int)$res['merchant_id'];
            $data['paystyle_id'] = 1;
            $data['price'] = $price;
            $data['remark'] = $remark;
            $data['status'] = 0;
            $data['cate_id']=1;
            $data['paytime'] = time();
            M("pay")->add($data);
            //由于回调地址的原因，将id存入session中

            $good_name = "向" . $res['name'] . "支付" . $price . "钱";
//       支付订单提交的数据交互
            $mchid = $res['wx_mchid'];
            $appid = $res['wx_appid'];
            $wx_key = $res['wx_key'];

//            $_SESSION["mchid"]=$mchid;

//            echo $price;
            //使用统一支付接口()
            $wzPay->setParameter('sub_openid', $sub_openid);
            $wzPay->setParameter('mch_id', $mchid);
            $wzPay->setParameter('sub_appid', $appid);
            $wzPay->setParameter('wx_key', $wx_key);
            $wzPay->setParameter('body', $good_name);
            $wzPay->setParameter('out_trade_no', $remark);
            $wzPay->setParameter('goods_tag', 1213);
            $wzPay->setParameter('total_fee', $price * 100);
//            dump($wzPay);
//            exit;
            $returnData = $wzPay->getParameters();
            $this->assign('jsApiParameters', $returnData);

            $this->display();

        }
    }

    /**
     * 微众支付
     * 公众号支付回调
     */
    public function wx_notify_return()
    {
//        dump($_POST);
//        exit;
        vendor('Wzpay.Wzpay');
        $wzPay = new \Wzpay();
        $result = $wzPay->notify();

        if ($result) {
            $orderInfo['out_trade_no'] = $result['out_trade_no'];
            $orderInfo['transaction_id'] = $result['transaction_id'];
            $orderInfo['mch_id']=$result['mch_id'];
//通过订单搜寻支付的数据库数据
            $remark=$orderInfo['out_trade_no'];
//           每次与微众相连需要商户号
            $queryOrderInfo = $wzPay->queryOrder($orderInfo);
            //从数据库查出订单价格,然后跟微众那边做对比
//            $pay=M("pay")->where('remark=$remark')->find();
            $ab=M("pay")->where("remark=$remark")->find();
            $orderPrice = $ab['price'];
//            $id=$ab['id'];
            if ($queryOrderInfo['status'] === '0' && $queryOrderInfo['result_code'] === '0' && $queryOrderInfo['trade_state'] === 'SUCCESS') {
//                file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/update/" . 'wzlog.log', date("Y-m-d H:i:s") . "444" . PHP_EOL, FILE_APPEND | LOCK_EX);
                //特别注意：商户后台接收到通知参数后，要对接收到通知参数里的订单号out_trade_no 和订单金额total_fee 和自身业务系统的订单和金额做校验
                //校验一致后才更新数据库订单状态
                if (bccomp($orderPrice * 100, $result['total_fee'], 3) === 0) {
//                    file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/update/" . 'wzlog.log', date("Y-m-d H:i:s") . "ok" . PHP_EOL, FILE_APPEND | LOCK_EX);
//                    $pay_change=M("pay");
//                    $pay_change->time = time();
//                    $pay_change->status = 1;
//                    $pay_change->where("id=$id")->save();
                        $data['time']=time();
                        $data['status']=1;
                        $ab->save($data);
                }
            }
        }

    }


    /**
     * 微众支付
     * 公众号支付订单查询
     */
    public function wz_query_order()
    {
        vendor('Wzpay.Wzpay');
        $wzPay = new \Wzpay();
        $result = $wzPay->queryOrder(array('out_trade_no' => '37848201701171504479942', 'transaction_id' => '4000602001201701176624856993'));
        dump($result);
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
//            $redirect_uri = U('Pay/Barcode/wz_pay', '', '', true);
            $redirect_uri= 'https://sy.youngport.com.cn'.$_SESSION['HTTP_HOST'].$_SERVER['REQUEST_URI'];
            $redirect_uri = urlencode($redirect_uri);
            $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $config['APPID'] . '&redirect_uri=' . $redirect_uri . '&response_type=code&scope=snsapi_base#wechat_redirect';
            redirect($url);
        } else {
            // 如果有code参数；则表示获取到openid
            $code = I('get.code');
            // 组合获取prepay_id的url
            $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $config['APPID'] . '&secret=' . $config['APPSECRET'] . '&code=' . $code . '&grant_type=authorization_code';
            // curl获取prepay_id
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
     * 微众支付
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
     * 微众支付
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
        $queryOrder = new \orderQuery('201701191508411484809721667237');
        $data = $queryOrder->orderQuery();
        dump($data);
    }

    /**
     * 微众支付
     * 刷卡支付
     */
    public function wz_micropay()
    {
        if (IS_POST) {
            vendor('Wzpay.micropay');
            $auth_code = I('post.auth_code', 0);
            if (!$auth_code) {
                $this->error('参数错误!');
            }
            $data = array('pay_money' => '0.1', 'auth_code' => $auth_code);
            $micropay = new \micropay();
            $result = $micropay->pay($data);
            if ($result['flag'] == false) {
                echo $result['message'];
            }
            if ($result['flag'] == true) {
                echo 'success';
                dump($result['message']);
            }
            file_put_contents('./data/log/wz/micropay.log', date("Y-m-d H:i:s") . $result . PHP_EOL, FILE_APPEND | LOCK_EX);
        } else {
            $this->display();
        }
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
}


