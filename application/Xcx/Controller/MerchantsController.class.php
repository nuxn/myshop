<?php

namespace Xcx\Controller;

use Xcx\Controller\ApibaseController;
use Think\Controller;

class  MerchantsController extends ApibaseController
{

    public function info()
    {
        $Merchants = D('Merchants');
        //判断uid的身份
        if ($data = $Merchants->info(UID)) {
            //$data['is_show'] = (time()+3600*24*15)>$data['end_time']?1:0;
            $data['is_show'] = 0;
            succ($data);
        } else {
            err($Merchants->getError());
        }
    }

    public function update()
    {
        //配送距离,配送方式.
    }

    public function types()
    {
        $level = D('Level');
        $type = I('type') ? I('type') : null;
        $data['lists'] = $level->lists(UID, $type);
        $data['cash'] = D('UserCash')->lists(UID);
        $data['yue'] = M('MerchantsUsers')->where(array('id' => UID))->getField('balance');
        $data['is_pay_pwd'] = M('MerchantsUsers')->where(array('id' => UID))->getField('pay_pwd') ? 1 : 0;
        succ($data);
    }

    //员工
    public function staff()
    {
        $data = D('MerchantsUsers')->lists(UID, I('page'));
        succ($data);
    }

    //商户二维码海报
    public function show_m_qrcode()
    {
        $users = M('Merchants_users')->where(array('id' => UID))->find();
        $users || err('不存在商户');
        $data1 = M('Merchants')->where(array('uid' => UID))->field('merchant_name,base_url')->find();

        if ($users['qrcode']) {
            if (!file_exists(realpath('./') . $users['qrcode'])) {

                if (I('uid')) {
                    $token = $this->get_token(UID);
                } else {
                    $token = $this->get_token(AGENT_ID);
                }
                //生成二维码
                //$url = 'https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?access_token='.$token;
                $url = 'https://api.weixin.qq.com/wxa/getwxacode?access_token=' . $token;

                $param['path'] = '/pages/index/index?store_id=' . UID;
                $param['width'] = '200';
                add_log(json_encode($param));
                $data = curl_post($url, json_encode($param));
                // dump($data);
                $fileName = './data/upload/qrcode/' . UID . '.png';
                $fp = fopen($fileName, 'w');
                $a = fwrite($fp, $data);
                fclose($fp);
                $path = ltrim($fileName, '.');
                if (!file_exists(realpath('./') . $path)) {
                    err('生成失败1');
                } else {
                    $data = M('Merchants_users')->where(array('id' => UID))->setField('qrcode', $path);
                    $data1['qrcode'] = $path;
                    $data !== false ? succ($data1) : err('生成失败2');
                }

            } else {
                $data1['qrcode'] = $users['qrcode'];
                succ($data1);
            }

        } else {
            if (I('uid')) {
                $token = $this->get_token(UID);
            } else {
                $token = $this->get_token(AGENT_ID);
            }
            //生成二维码
            //$url = 'https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?access_token='.$token;
            $url = 'https://api.weixin.qq.com/wxa/getwxacode?access_token=' . $token;

            $param['path'] = '/pages/index/index?store_id=' . UID;
            $param['width'] = '200';
            add_log(json_encode($param));
            $data = curl_post($url, json_encode($param));
            // dump($data);
            $fileName = './data/upload/qrcode/' . UID . '.png';
            $fp = fopen($fileName, 'w');
            $a = fwrite($fp, $data);
            fclose($fp);
            $path = ltrim($fileName, '.');
            if (!file_exists(realpath('./') . $path)) {
                err('生成失败3');
            } else {
                $data = M('Merchants_users')->where(array('id' => UID))->setField('qrcode', $path);
                $data1['qrcode'] = $path;
                $data !== false ? succ($data1) : err('生成失败4');
            }
        }
    }

    public function show_m_qrcode1()
    {
        $users = M('Merchants_users')->where(array('id' => UID))->find();
        $users || err('不存在商户');
        $data1 = M('Merchants')->where(array('uid' => UID))->field('merchant_name,base_url')->find();

        if ($users['qrcode']) {
            $data1['qrcode'] = $users['qrcode'];
            succ($data1);
        } else {
            $token = $this->get_token(AGENT_ID);
            //生成二维码
            //$url = 'https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?access_token='.$token;
            $url = 'https://api.weixin.qq.com/wxa/getwxacode?access_token=' . $token;

            $param['path'] = '/pages/index/index?store_id=' . UID;
            $param['width'] = '200';
            add_log(json_encode($param));
            $data = curl_post($url, json_encode($param));
            // dump($data);
            $fileName = './data/upload/qrcode/' . UID . '.png';
            $fp = fopen($fileName, 'w');
            $a = fwrite($fp, $data);
            fclose($fp);
            $path = ltrim($fileName, '.');
            // dump($path);
            $data = M('Merchants_users')->where(array('id' => UID))->setField('qrcode', $path);

            $data1['qrcode'] = $path;
            $data !== false ? succ($data1) : err('生成失败');
        }
    }

    //生成二维码
    public function show_qrcode()
    {
        ($id = I('id')) || err('id is empty');

        $users = M('Merchants_users')->where(array('id' => $id, 'pid' => UID))->find();
        //	$pid = M('Merchants_users')->where(array('id'=>UID))->getField('pid');

        $users || err('不存在该员工');
        // dump($users['qrcode']);
        if ($users['qrcode']) {
            // dump(file_exists($users['qrcode']));
            // var_dump();
            // echo realpath('.//alidata/www/youngshop'$users['qrcode']);
            // echo 'ss';
            if (!file_exists(realpath('./') . $users['qrcode'])) {
                // echo "111";
                //生成二维码
                if (I('uid')) {
                    $path = $this->build_qrcode($id, $users['pid'], UID);
                } else {
                    $path = $this->build_qrcode($id, $users['pid'], AGENT_ID);
                }

                if ($path) {
                    $data = M('Merchants_users')->where(array('id' => $id))->setField('qrcode', $path);
                    // dump($path);
                    succ($path);
                } else {
                    err('生成失败');
                }
            } else {
                // echo "222";
                //成功
                succ($users['qrcode']);
            }
        } else {
            // echo "333";
            //生成二维码
            if (I('uid')) {
                $path = $this->build_qrcode($id, $users['pid'], UID);
            } else {
                $path = $this->build_qrcode($id, $users['pid'], AGENT_ID);
            }
            if ($path) {
                $data = M('Merchants_users')->where(array('id' => $id))->setField('qrcode', $path);
                $data != false ? succ($path) : err('更新失败1');
            } else {
                err('生成失败');
            }

        }
    }

    //生成token
    public function get_token($mid = 0)
    {

        //查看store_id
        ($appid = M('appid')->where(array('mid' => $mid))->find());
        if (empty($appid)) {
            $appid = M('appid')->where(array('mid' => 0))->find();
            $mid = 0;
        }
        // dump($mid);
        $token1 = M('config')->where(array('name' => 'xcx_access_token', 'type' => $mid))->find();
        $time = time();
        // dump($token1);
        if (empty($token1) || empty($token1['value']) || $token1['add_time'] + 7200 < $time) {
            // echo "11111";
            //获取token
            $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $appid['appid'] . '&secret=' . $appid['secret'];

            add_log($url);
            $token = curl_post($url, array());
            $token = json_decode($token, true);
            $token = $token['access_token'];
            $token1 ? M('config')->where(array('name' => 'xcx_access_token'))->where(array('type' => $mid))->save(array('value' => $token, 'add_time' => $time)) : M('config')->add(array('name' => 'xcx_access_token', 'value' => $token, 'add_time' => $time, 'type' => $mid));
        } else {
            // echo "222";
            $token = $token1['value'];
        }
        // dump($token);
        return $token;
    }

    //生成二维码
    public function build_qrcode($id, $store_id, $pid)
    {
        add_log($store_id);
        add_log($pid);
        // dump($pid);
        $token = $this->get_token($pid);
        add_log($token);
        //生成二维码
        // $url = 'https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?access_token='.$token;
        $url = 'https://api.weixin.qq.com/wxa/getwxacode?access_token=' . $token;
        $param['path'] = '/pages/index/index?store_id=' . $store_id . '&staff_id=' . $id;
        $param['width'] = '200';
        $data = curl_post($url, json_encode($param));
        get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/xcx/', 'build_qrcode', '小程序生成二维码', json_encode($param));
        add_log($data);
        $fileName = $this->upload_qr_img($data, $id);
        $len = strlen($id) * 25;
        $font = './public/fonts/simsun.ttc';//

        list($width, $height) = getimagesize($fileName);
        $img = imagecreatetruecolor($width, $height + 40);
        //$cornflowerblue = imagecolorallocate($img, 48,112,185);
        $black = imagecolorallocate($img, 84, 84, 84);
        $white = imagecolorallocate($img, 255, 255, 255);
        //imagefill($img, 0, 0, $cornflowerblue);  //填充背景色
        ImageFilledRectangle($img, 0, 0, 325, 40, $black);
        $a = imagefttext($img, 14, 0, (325 - $len) / 2, 27, $white, $font, $id);

        $src = imagecreatefromstring(file_get_contents($fileName));

        imagecopyresized($img, $src, 0, 40, 0, 0, $width, $height, $width, $height);
        //header("Content-type: image/jpg");
        //imagepng($img);

        imagepng($img, $fileName);
        imagedestroy($img);
        // return	$result?ltrim($fileName,'.'):false;
        if (!file_exists($fileName)) {
            return false;
        } else {
            return ltrim($fileName, '.');
        }

    }

    //图片二进制数据转图片
    private function upload_qr_img($data, $id)
    {
        //生成图片  
        $imgDir = 'data/upload/qrcode/';
        $filename = $id . ".png";///要生成的图片名字

        $xmlstr = $data;
        if (empty($xmlstr)) {
            // echo "111";
            $xmlstr = file_get_contents('php://input');
        }

        $jpg = $xmlstr;//二进制原始数据
        if (empty($jpg)) {
            // echo "2222";
            echo 'nostream';
            exit();
        }
        $file = fopen("./" . $imgDir . $filename, "w");//打开文件准备写入
        fwrite($file, $jpg);//写入
        fclose($file);//关闭  

        $filePath = './' . $imgDir . $filename;
        // dump($filePath);
        //图片是否存在  
        if (!file_exists($filePath)) {
            // echo "555";

            return false;
        } else {
            // echo "444";

            return $filePath;
        }
    }

    //获取二维码二进制数据
    private function api_notice_increment($url, $data)
    {
        $ch = curl_init();
        $header = "Accept-Charset: utf-8";
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $tmpInfo = curl_exec($ch);
        if (curl_errno($ch)) {
            return false;
        } else {
            return $tmpInfo;
        }
    }

    //批量生成二维码
    public function build_qrcodes()
    {
        ($ids = I('ids')) || err('ids is empty');
        $ids = explode(',', $ids);
        foreach ($ids as $id) {
            $users = M('Merchants_users')->where(array('id' => $id, 'pid' => UID))->find();
            //	$pid = M('Merchants_users')->where(array('id'=>UID))->getField('pid');
            //生成二维码
            if (I('uid')) {
                $path = $this->build_qrcode($id, $users['pid'], UID);
            } else {
                $path = $this->build_qrcode($id, $users['pid'], AGENT_ID);
            }
            $path !== false || err('生成二维码失败');
            $data = M('Merchants_users')->where(array('id' => $id))->setField('qrcode', $path);
            $data !== false || err('更新失败');
            $qrcode[] = $path;
        }
        succ($qrcode);
    }

    //计算价格
    public function buy_systems()
    {
        add_log();
        ($id = I('id')) || err('id is empty');
        ($type = I('type')) || err('type is empty');
        //查询是否存在
        ($merchantsLevel = M('merchants_level')->where(array('id' => $id))->find()) || err('不存在该类型的小程序');
        //查看是否已经购买其他的
        if ($type == 'yue') {
            //判断支付密码是否正确md5(strtoupper(md5($pay_pwd)))
            ($pay_pwd = I('pay_pwd')) || err('pay_pwd is empty');
            $user = M('merchants_users')->where(array('id' => UID))->field('pay_pwd,balance')->find();
            empty($user['pay_pwd']) && err('请设置你的支付密码!');
            //add_log(md5(strtoupper(md5($pay_pwd))));
            (md5(strtoupper(md5($pay_pwd))) == $user['pay_pwd']) || err('支付密码错误');
            //判断余额是否足够
            ($user['balance'] < $merchantsLevel['price']) && err('余额不足');

        }
        $merchants = M('merchants')->where(array('uid' => UID))->field('type,end_time')->find();

        if ($merchants['mini_type']) {
            $merchants['mini_type'] == $merchantsLevel['type'] || err('你已经购买了其他小程序！');
        }
        if ($cash_id = I("cash_id")) {
            //查询兑换券
            $UserCash = D('UserCash');
            if (!$cash = $UserCash->info($cash_id, UID)) {
                err($UserCash->getError());
            }
        }
        //生成订单
        $data['mid'] = UID;
        $data['order_sn'] = date('YmdHis') . UID . rand(1000000, 9999999);
        $data['type'] = $merchantsLevel['type'];
        $data['level'] = $merchantsLevel['level'];
        $data['goods_price'] = $merchantsLevel['price'];
        $data['cash_id'] = $cash_id;
        $data['cash_price'] = isset($cash['price']) ? $cash['price'] : 0;
        $data['order_price'] = $merchantsLevel['price'] - $data['cash_price'];
        $time = time();

        add_log(date('y-m-d H:i:s', $data['start_time']));
        add_log(date('y-m-d H:i:s', $data['end_time']));


        $data['add_time'] = $time;
        $order_id = M('miniapp')->add($data);
        //生成签名
        $sign = $this->get_sign($order_id, $type);
        succ(array('sign' => $sign, 'price' => $data['order_price']));
    }

    public function get_sign($order_id, $type)
    {
        //查询订单
        $data = M('miniapp')->where(array('id' => $order_id))->find();
        empty($data) && err('该订单不存在');
        ($data['status'] == 1) && err('该订单已经支付');
        ($data['status'] == 0) || err('该订单不能支付');
        switch ($type) {
            case 'wx':
                return $this->wx_pay($data['order_sn'], $data['order_price']);
                break;
            case 'zfb':
                return $this->zfb_pay($data['order_sn'], $data['order_price']);
                break;
            case 'yue':
                //支付成功
                $this->common($data['order_sn'], '', $data['order_price'], 'yue');
                break;
            default:
                err('type is wrong');
                break;
        }
    }

//				req.appId =context.getString(R.string.weixin_key);
//              req.partnerId =result.getResult().getWeixinInfo().getPartnerid();
//              req.prepayId = result.getResult().getWeixinInfo().getPrepayid();
//              req.nonceStr = result.getResult().getWeixinInfo().getNoncestr();
//              req.timeStamp = result.getResult().getWeixinInfo().getTimestamp();
//              req.packageValue ="Sign=WXPay";
//              req.sign = result.getResult().getWeixinInfo().getSign();
    //微信微众支付
    public function wx_wz_pay($order_sn, $price)
    {
        $key = 'youngPort4a21';
        $param = array(
            'mch_id' => '107584000030001',
            'is_raw' => 2,
            'out_trade_no' => time(),
            'device_info' => 'web',
            'body' => '测试啊',
            'total_fee' => 99,
            'mch_create_ip' => '120.24.99.79',
            'notify_url' => 'http://sy.youngport.com.cn',
            'nonce_str' => rand(1000000, 9999999)
        );
        $string = $this->getSign($param);
        var_dump($string . '&key=' . $key);
        $sign = strtoupper(md5($string . '&key=' . $key));
        $param['sign'] = $sign;
        $xml = arrtoxml($param);
        var_dump($xml);
        $data = curl_post('https://svrapi.webank.com/wbap-bbfront/AddOrder', $xml);
        p($data);
    }

    public function wx_pay($order_sn, $price)
    {
        Vendor('Wxpay.appWxPayPubHelper.WxPayPubHelper');
        $unifiedOrder = new \UnifiedOrder_pub();
        $price = $price * 100; //发送给微信服务器的价格要乘上100
        $unifiedOrder->setParameter("body", '测试'); //商品描述
        $unifiedOrder->setParameter("out_trade_no", $order_sn); //商户订单号
        $unifiedOrder->setParameter("total_fee", (int)$price); //总金额
        $unifiedOrder->setParameter("notify_url", 'http://a.ypt5566.com/index.php?g=xcx&m=Merchants&a=wx_notify_url'); //通知地址
        $unifiedOrder->setParameter("trade_type", "APP"); //交易类型
        $unifiedOrderResult = $unifiedOrder->getResult(); //获取统一支付接口结果
        //商户根据实际情况设置相应的处理流程
        if ($unifiedOrderResult["return_code"] == "FAIL") {
            $this->ajaxReturn(array("通信出错：" . $unifiedOrderResult['return_msg']));
        } elseif ($unifiedOrderResult["result_code"] == "FAIL") {
            $this->ajaxReturn(array('status' => '2', 'message' => "错误代码：" . $unifiedOrderResult['err_code'] . "错误代码描述：" . $unifiedOrderResult['err_code_des']));
        } elseif ($unifiedOrderResult["prepay_id"] != NULL) {
            $data = array();

            $data['order_sn'] = $order_sn; //返回订单号
            $data['prepay_id'] = $unifiedOrderResult["prepay_id"]; //获取prepay_id
            $data['money'] = $price;
            succ($data);
        }
    }

    public function getSign($params)
    {
        ksort($params);
        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {

                // 转换成目标字符集
                $v = $this->characet($v, $this->postCharset);

                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . $v;
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . $v;
                }
                $i++;
            }
        }
        unset ($k, $v);
        return $stringToBeSigned;
    }

    public function zfb_pay($order_sn, $price)
    {
        // 支付宝合作者身份ID，以2088开头的16位纯数字
        $partner = "2017010704905089";
        // 支付宝账号
        $seller_id = 'guoweidong@hz41319.com';
        // 商品网址
        // 异步通知地址
        $notify_url = 'http://sy.youngport.com.cn/notify/xcx_notify.php';
        //$notify_url = 'http://a.ypt5566.com/notify.php';
        // 订单标题
        $subject = '1';
        // 订单详情
        $body = '我是测试数据';
        // 订单号，示例代码使用时间值作为唯一的订单ID号
        $content = array();
        $content['timeout_express'] = '30m';
        $content['product_code'] = 'QUICK_MSECURITY_PAY';
        $content['total_amount'] = $price;
        $content['subject'] = $subject;
        $content['body'] = $body;
        $content['out_trade_no'] = $order_sn;
        //$orderinfo['order_amount'];
        $data = array();
        $data['app_id'] = $partner;
        $data['biz_content'] = json_encode($content);
        $data['charset'] = 'utf-8';
        $data['format'] = 'json';
        $data['method'] = 'alipay.trade.app.pay';
        $data['notify_url'] = $notify_url;
        $data['sign_type'] = 'RSA';
        $data['timestamp'] = date('Y-m-d H:i:s');
        $data['version'] = '1.0';

        $orderInfo = $this->createLinkstring($data);

        //$orderInfo = 'biz_content={"timeout_express":"30m","product_code":"QUICK_MSECURITY_PAY","total_amount":"0.01","subject":"1","body":"我是测试数据","out_trade_no":"0603181557-1017"}&method=alipay.trade.app.pay&charset=utf-8&version=1.0&app_id=2017010704905089&timestamp=2016-07-29 16:55:53&sign_type=RSA';
        //var_dump($orderInfo);
        $sign = $this->sign($orderInfo);
        //var_dump($sign);
        $data['sign'] = $sign;
        $orderInfo = $this->getSignContentUrlencode($data);

        //$orderInfo .= '&sign='.urlencode($sign);
        //$orderInfo = "biz_content=%7B%22timeout_express%22%3A%2230m%22%2C%22product_code%22%3A%22QUICK_MSECURITY_PAY%22%2C%22total_amount%22%3A%220.01%22%2C%22subject%22%3A%221%22%2C%22body%22%3A%22%E6%88%91%E6%98%AF%E6%B5%8B%E8%AF%95%E6%95%B0%E6%8D%AE%22%2C%22out_trade_no%22%3A%220603181557-1017%22%7D&method=alipay.trade.app.pay&charset=utf-8&version=1.0&app_id=2017010704905089&timestamp=2016-07-29+16%3A55%3A53&sign_type=RSA&sign=YZPNvZRrerdHsGrcWx9O3IimjMEXGvPeWQcOt8e71eZgo5xedgzn2wDH5nKAX9TEKWa9kDOT7DorsSfYpXST8AQkquzNTqyqzB%2BWmtD4D6Xk73emfJaokbqYNl560rZ01i2mCmdhksgBq2%2F9hgcmPU%2FBzsPlKbw2Zamd50ZWPKE%3D";

        return $orderInfo;
    }

    public function zfb_notify_url()
    {
        //$data= '{"total_amount":"0.01","buyer_id":"2088702133211466","trade_no":"2017060521001004460255924689","body":"\u6211\u662f\u6d4b\u8bd5\u6570\u636e","notify_time":"2017-06-05 14:41:58","subject":"1","sign_type":"RSA","buyer_logon_id":"188****4165","auth_app_id":"2017010704905089","charset":"utf-8","notify_type":"trade_status_sync","invoice_amount":"0.01","out_trade_no":"20170605144154469765694","trade_status":"TRADE_SUCCESS","gmt_payment":"2017-06-05 14:41:58","version":"1.0","point_amount":"0.00","sign":"okwV2yR7Lgv3Mir+wy17ZzZUlM4qheAIWkKdkBokfDGC5POZKnZXBLM+CpNEPBLtnvX\/NckkaOYf3McUJtP1UPe1I1LBFzwz46hYVIXekSrz1RcBGTMhYm53rMChO8b0KNbFvtANtvdTqgG0cHtzST2quR4c++BmDkc5PHVLblM=","gmt_create":"2017-06-05 14:41:58","buyer_pay_amount":"0.01","receipt_amount":"0.01","fund_bill_list":"[{\\"amount\\":\\"0.01\\",\\"fundChannel\\":\\"ALIPAYACCOUNT\\"}]","app_id":"2017010704905089","seller_id":"2088421497824441","notify_id":"77f9d898eed7cd97279b28eb55c6fc8jju","seller_email":"guoweidong@hz41319.com"}';
//				$data = M('log')->where(array('id'=>77))->getField('post');
//				$data = json_decode($data,true);
        $data = $_POST;
        $sign = $data['sign'];
        $data['sign_type'] = null;
        $data['sign'] = null;
        $data = $this->getSignContent($data);
        $pubKey = 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDDI6d306Q8fIfCOaTXyiUeJHkrIvYISRcc73s3vF1ZT7XN8RNPwJxo8pWaJMmvyTn9N4HQ632qJBVHf8sxHi/fEsraprwCtzvzQETrNRwVxLO5jVmRGi60j8Ue1efIlzPXV9je9mkjzOmdssymZkh2QhUrCmZYI/FCEa3/cNMW0QIDAQAB';
        $res = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($pubKey, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";
        $result = (bool)openssl_verify($data, base64_decode($sign), $res);
        if ($result && $_POST['trade_status'] == 'TRADE_SUCCESS') {
            add_log($_POST['out_trade_no'] . ' ' . $_POST['trade_no'] . ' ' . $_POST['total_amount'] . ' ' . 'zfb');
            $this->common($_POST['out_trade_no'], $_POST['trade_no'], $_POST['total_amount'], 'zfb');
        } else {
            var_dump($result);
        }
    }

    public function getSignContent($params)
    {
        ksort($params);
        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {
                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . stripslashes($v);
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . stripslashes($v);
                }
                $i++;
            }
        }

        unset ($k, $v);
        return $stringToBeSigned;
    }

    public function getSignContentUrlencode($params)
    {
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

// 				req.appId =context.getString(R.string.weixin_key);
//              req.partnerId =result.getResult().getWeixinInfo().getPartnerid();
//              req.prepayId = result.getResult().getWeixinInfo().getPrepayid();
//              req.nonceStr = result.getResult().getWeixinInfo().getNoncestr();
//              req.timeStamp = result.getResult().getWeixinInfo().getTimestamp();
//              req.packageValue ="Sign=WXPay";
//              req.sign = result.getResult().getWeixinInfo().getSign();

    //参数
    public function createLinkstring($params)
    {
        ksort($params);
        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {

                // 转换成目标字符集
                $v = $this->characet($v, 'utf-8');

                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . "$v";
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . "$v";
                }
                $i++;
            }
        }
        unset ($k, $v);
        return $stringToBeSigned;
    }

    protected function checkEmpty($value)
    {
        if (!isset($value))
            return true;
        if ($value === null)
            return true;
        if (trim($value) === "")
            return true;

        return false;
    }

    protected function sign($data, $signType = "RSA")
    {
        $priKey = "MIICXAIBAAKBgQC/UIMSw0mWKRp3wP3v0tbKKqtQO80iL3gBkceCE41KRtpE8+ljXzH16jXs5Alj3cPNZlAh+2SApLBv9sVY9nGU8rd6d5294HHH8APDdrHBtdUTpgZnKGNATFhCeiZPuLD76DJslWtSM4a8kW9EkBUDe3mFLtDQwu+ZtbUYf0k8eQIDAQABAoGALzKFo5NaDBmH1hNeklPJeYadTOXz7YMYcAqu1YBNUw23u1sRMNrDUI+/TfnT9zc2nu2mxztlx/bZMEYI2bGyw0Y/3oYl4GHDmeSyqq9o6SjL9S6GRtj+ngG8CX9QuVj7lTqcvHJrBR8E48EiyH4VK9ouySIHE9Ukf71VQVPd0AECQQD5uCTXuCd6aGbs2XqTfX/tfaUio5lFsdvTrccnFdTvDJ/EWwuMXgceJvehsOmNvZK8NabruGkyAk25ABM9bxq5AkEAxCBR2xvTVSBf/ohpB1/y94Imx1pb5OLvrRMvxq3LEcQORDzbSQdY27UZ1i3tC8CLvPF68KE985j75xgY//9PwQJBALx10LhM7t5etG7DotJ0wHtHe70sopwKotCaMda5jz3p1RmnbIu+2rhSyEhq75hdHcSU6Si0wA9R1b5s5BhVJWECQClVdmrrLlree9y0+w2m1xn6wBl7napbeY/MX2FL92RDIY6YFM8LUVrcjBlrjG3RoqGrcvGLyfaw40YM+hfZwwECQFNwNJ6wB5INtt+CeJkJSiaHuhevFmr3w8UD00dOXxTMz2m86cmV+ZNl5srCkeunKdwVCMwNhUHFfLfCno3XyEk=";
        //$priKey="MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBAL6EhsF9ufhXqx5ZJwGy5MLP5AcoFsp1I3hWpJgWwLSXKSRM5mkKmp/OOLltJtIF+ViKk1nOgE99J3C9yFjoXV9PWtNhClZmvOk+qAGweC4rzkjumhNC5vTnYf11Hp2+oes5vWMm7DAFFx/owNecNrlQl9cHQCj96pcElWFrhYhNAgMBAAECgYEAln5nWEbxdWwDHwj7mArxS7YegUy4nBrl9vQyNnWaqczSUftw8r7On7et9UN0q+jOK5Pji8hkcOYDFrrDnP+IaRX6KVMYjL4sHltoj+XlEWnUdz5B9MIlKg6ops1aEd4d5PFD+ixw5yvbEsc9nXaKz+8ttm2w+7LWkUTEGres6t0CQQD+paORxMv7APKSlKtzyOw0m6Xr7cydwtJqWexzOI8whfud7ODJV2VEmsJMfsh7HCxpeJET/9Rt5jq9P51ZicbrAkEAv4epQ3xaNUFfkFgYn94V8gGP0K11LrFhB30/MvWGHEuPt+/2ZiF9hXmyeIIktW3QDTcwfd0hfHAzkwgrurcPpwJAUUsbztteq0EAL59apNoN3jWaYJlH601Y0y7l91qlC76aNy56DIzj/WTSho0q/3JdE0a0OghADt2i/uuiFgWQBQJAVFnr6uPWWsP60XhrB+VoZtfXPcFW7YSDRigb8FZ/hPCmUAznyJ0RSfqJ5lby0dCWI2vd+GCuQb6siCG+GJJM2wJATROJfcSEWwNahKNCykUeN8eDd8Iv4Ko1uixynvnMdZZB8YgVQ4C0Y09RBtzi7Dt1StF1aYlAqn9T/ryhFMoP3A=="
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($priKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";

        ($res) or die('您使用的私钥格式错误，请检查RSA私钥配置');

        if ("RSA2" == $signType) {
            openssl_sign($data, $sign, $res, OPENSSL_ALGO_SHA256);
        } else {
            openssl_sign($data, $sign, $res);
        }
        openssl_free_key($res);
        $sign = base64_encode($sign);
        return $sign;
    }

    /**
     * 转换字符集编码
     * @param $data
     * @param $targetCharset
     * @return string
     */
    public function characet($data, $targetCharset)
    {

        if (!empty($data)) {
            $fileType = $this->fileCharset;
            if (strcasecmp($fileType, $targetCharset) != 0) {
                $data = mb_convert_encoding($data, $targetCharset, $fileType);
                //				$data = iconv($fileType, $targetCharset.'//IGNORE', $data);
            }
        }
        return $data;
    }

    // 签名生成订单信息
    public function rsaSign($data)
    {
        $rsaPrivateKey = 'MIICXgIBAAKBgQDktchFUfoxjoaGGTOY5/S9cpWWon6Gc2AmmwgEwmqCHMnUIPRMa9nYfJEJo0lnJsJTUfmjwvuyWmKLyBdLjvEIbvvNMHtddKev5WfO4SEU24E2OPeGAQdxFcM89mBHxgcYoGIU7W8WUKTwY2oTjNoFuHG4SFsSBr8FdB0K0E9T/QIDAQABAoGBAMgZz5XuymKvWz1aMU2XrAZQiVZY5zBFI5vDSjm3y634+BCzoGp4dgm1usPe7Crmu2BguXSw9Lwv3kaEEvWVo4VgmOmpUTPBRxBOkSlbEH3zxsy2JtzcSV0dGLzkWlsmWymOCktk7XwKj9KCNLcfyiygIvWlI/sJRLg6fz+X2PN1AkEA/O+1LbRYLP7IvF6KO8Q0+G4/FdNThreoTwuO7ve32mUMBQOFrej08mbt9XAFuB+t7L3FKqEa7Tqq4uoiFpxSRwJBAOd69Hm4sE5bi59qO3hUCxlyLUaw5+PnmWv90wlGCNxgNYZWQyNJES74ifD4Jo3Ya2vJI9azF+MbaxXkLt1w5ZsCQQCPw6eVPMZJfN+XwZyMb+8zrWYJ/72f+s+dbhJl3UMQzRJR3ziiKqDfDoX+VRfLGaZ/wzVID54AbLIom6+Ybm2NAkEAhKZdOvOvdPfZBz2lXssqoSZg88Wk3WF29f+60/GiWkd35MfCKZJRmo0q30AVN+vxgw78zqhK5AZuU1kz0gpESQJAboDgERMcwS5e9ib9ax5lPzsAQ5F5JVUHH0VogEzpx6GjAzPp3/94zoCMEHTBpGy2ABpiyjTJvVM2Aaa0qAfmvw==';
        $priKey = $rsaPrivateKey;
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($priKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";
        $res = openssl_get_privatekey($res);

        ($res) or die('您使用的私钥格式错误，请检查RSA私钥配置');

        openssl_sign($data, $sign, $res);
        openssl_free_key($res);
        $sign = base64_encode($sign);
        $sign = urlencode($sign);
        return $sign;
    }

    public function wx_notify_url()
    {

    }

    public function common($order_sn = '', $transaction = '', $price = 0, $pay_type)
    {
        //查看是否已经支付成功
        $miniapp = M('miniapp')->where(array('order_sn' => $order_sn))->find();

        if ($miniapp['status'] != 0) {
            //记录日志
            err('该订单已经支付了');
        }
        $end_time = M('merchants')->where(array('uid' => $miniapp['mid']))->getField('end_time');
        //开启事务
        M()->startTrans();
        $time = time();

        //修改订单信息
        $data['pay_time'] = $time;
        $data['pay_price'] = $price;
        $data['transaction'] = $transaction;
        $data['pay_type'] = $pay_type;
        $data['status'] = 1;
        $data['start_time'] = $end_time < $time ? $time : $end_time;
        $data['end_time'] = strtotime('+1 year', $data['start_time']);

        if (false === M('miniapp')->where(array('id' => $miniapp['id']))->save($data)) {
            M()->rollback();
            err('该订单已经支付了');
        }

        //修改优惠券
        if ($miniapp['cash_id']) {
            if (false === M('user_cash')->where(array('id' => $miniapp['cash_id']))->setField('status', 2)) {
                M()->rollback();
                err('修改优惠券失败');
            }
        }
        $data1['end_time'] = $data['end_time'];
        $data1['is_miniapp'] = 2;
        $data1['mini_type'] = $miniapp['type'];
        //开始添加时长
        if (false === M('merchants')->where(array('uid' => $miniapp['mid']))->save($data1)) {
            M()->rollback();
            err('修改优惠券失败');
        }
        //余额减少
        if ($pay_type == 'yue') {
            if (false === M('merchants_users')->where(array('id' => $miniapp['mid']))->setDec('balance', $price)) {
                err('yue is wrong');
            }

            //添加日志
            yue_log(-$price, '购买小程序', $miniapp['mid'],null, $order_sn);
        }
        add_log(M()->_sql());
        M()->commit();
        succ((object)array());
        die;
    }
// 	//支付宝微众支付
// 	public function zfb_wz_pay($order_sn,$price){
//      Vendor('QRcodeAlipay.AlipayConfig_old');
//    	Vendor('QRcodeAlipay.Wz_pay');
//		$Wz_pay = new \Wz_pay();
//      //构造要请求的参数数组，无需改动
//      $parameter = array(
//          'wbMerchantId' =>\AlipayConfig::MERCHANTID,
//          'orderId' => $order_sn,
//          'totalAmount' => $price,
//          'subject' => 'w1w1w1'
//      );
//      // if (\AlipayConfig::MERCHANTID) $wzPay->get_merchantId($alipay_config['wz']);
//      // $wzPay->get_merchantId($alipay_config['wz']);
//      $url = \AlipayConfig::PAY_TO;
//      
//      $pay_info = $Wz_pay->getParameters($url, $parameter);
//      $pay_info = json_decode($pay_info, true);
//      var_dump($pay_info);
// 	}

}
