<?php

namespace Pay\Controller;

use Common\Controller\HomebaseController;

/**支付
 * Class BarcodeController
 * @package Pay\Controller
 */
class BarcodejybankController extends HomebaseController
{
    function _initialize()
    {
        $this->notifyUrl = 'https://sy.youngport.com.cn/notify/jybank.php';
        $this->httpUrl = 'http://test.chinavalleytech.com/hfcb/';
        $this->account = '18180098028';
        $this->password = '123456';
        $this->code = '295595';
        $this->cbzid = '820170327161713396240';
        $this->privatekeypath = './data/key/';
        $this->publickeypath = $this->privatekeypath . "rsa_public_key.pem";
        $this->keystring = 'BD161A60C8933E7EC1D1B802376D6245';
        $this->RSA_MAX_ORIGINAL = 117;
        $this->RSA_MAX_CIPHER = 256;
    }

    /**
     * 注册账号
     */
    public function register()
    {
        $post_data = array(
            'account' => $this->account,
            'pass' => $this->password,
            'code' => $this->code,
            'cbzid' => $this->cbzid
        );
        $result = $this->send_post($this->httpUrl . 'rlregister', $post_data);
        echo $result;
        //$this->ajaxReturn(json_decode($result));
    }

    /**
     * 下载密钥
     */
    public function downloadkeys()
    {
        $post_data = array(
            'orderCode' => 'tb_DownLoadKey',
            'account' => $this->account,
            'password' => $this->password,
            'language' => 'PHP'//非必填项,不填默认为Java
        );
        $datas = base64_encode(json_encode($post_data));
        $encrypted = $this->rsaPublicEncrypt($datas, $this->publickeypath);
        $params = array(
            'data' => $encrypted
        );
        $res = $this->send_post1($this->httpUrl . 'Kubei', json_encode($params));
        $res = json_decode($res, true);
        $data = $res['data'];
        $count = $res['count'];
        $plain_text = mcrypt_decrypt(MCRYPT_3DES, self::hexStrToBytes($this->keystring, 24), self::hexStrToBytes($data), MCRYPT_MODE_ECB);
        $resjson = substr($plain_text, 0, $count);
        echo $resjson;
        $resArr = json_decode($resjson, true);
        $respCode = $resArr['respCode'];
        if ("000000" != $respCode) {
            $code['result'] = '11';
            $code['msg'] = $resArr['respInfo'];
            $this->ajaxReturn($code);
        }
        $priKey = $resArr['privatekey'];
        if ($priKey == null || $priKey == '') {
            $code['result'] = '11';
            $code['msg'] = '下载失败';
            $this->ajaxReturn($code);
        }
        $priKeyFilePath = $this->privatekeypath . $post_data['account'] . "_private_key.pem";
        if (file_exists($priKeyFilePath)) {
            unlink($priKeyFilePath);
        }
        if (!file_put_contents($priKeyFilePath, $resArr['privatekey'])) {
            $code['result'] = '11';
            $code['msg'] = '密钥保存文件失败!';
            $this->ajaxReturn($code);
        } else {
            $code['result'] = '00';
            $code['msg'] = '密钥保存文件成功!';
            $this->ajaxReturn($code);
        }
    }

    /**
     * 修改费率
     */
    public function changeRate()
    {
        $post_data = array(
            'account' => $this->account,
            'orderCode' => 'xy_ChangeRate'
        );

        $msgDate = array(
            'channel_code' => 'WXPAY',
            'password' => $this->password,
            'wx_rate' => 0.004,
            'ali_rate' => 0.004,
            'jd_rate' => 0.004,
            'cbzid' => $this->cbzid
        );

        $post_data['msg'] = base64_encode(json_encode($msgDate));

        $priKeyFilePath = $this->privatekeypath . $post_data['account'] . "_private_key.pem";
        $sign = $this->rsaDataSign($post_data['msg'], $priKeyFilePath); //RSA签名


        $data = base64_encode(json_encode($post_data));
        $decrypt = $this->rsaPublicEncrypt($data, $this->publickeypath); //RSA公钥加密

        $send_data = array(
            'data' => $decrypt,
            'signature' => $sign
        );

        $res = $this->send_post1($this->httpUrl . 'Kubei', json_encode($send_data));
        $res = json_decode($res, true);
        $res_data = $res['data'];
        $res_sign = $res['signature'];

        $original = $this->rsaPrivateDecrypt(base64_decode($res_data), $priKeyFilePath); //RSA私钥解密
        $original = json_decode($original, true);
        $res_msg = json_decode($original['msg'], true);

//验证签名
        $valid = $this->isValid($original['msg'], base64_decode($res_sign), $this->publickeypath);

        if ("000000" === $res_msg['respCode']) {
            print("修改费率成功.");
        } else {
            print("修改费率失败</br>返回码:" . $res_msg['respCode'] . "</br>失败原因:" . $res_msg['respInfo'] . "<br/>");
            var_dump($res_msg);
        }
    }

    /**
     * 商户验卡,包含同步商户资料以及验卡,第一次验卡是新增商户以及验卡,成功后再次调用验卡接口则是修改商户及验卡
     */
    public function verifyInfo()
    {
        $post_data = array(
            'account' => $this->account,
            'orderCode' => 'tb_verifyInfo'
        );

        $msgDate = array(
            'real_name' => base64_encode("张鹏"), //真实姓名
            'cmer' => base64_encode("张鹏的点点滴滴"), //商户全称
            'cmer_short' => base64_encode("张鹏的点点滴滴多多多"), //商户简称
            'channel_code' => 'WXPAY', //通道标识
            'region_code' => '310106', //地区编码 参照:http://www.stats.gov.cn/tjsj/tjbz/xzqhdm/201703/t20170310_1471429.html?spm=a219a.7629140.0.0.7aZWPD
            'address' => '张鹏', //详细地址
            'business_id' => 53, //经营类目(传对应的微信MCC)
            'phone' => '18180098028', //商户联系电话
            'card_type' => '1', //结算卡类型(默认值1,借记卡)
            'card_no' => '6230582000036254277', //结算卡号
            'cert_type' => '00', //身份证件号类型(默认值00,身份证号)
            'cert_no' => '513825199205273817', //身份证件号码
            'mobile' => '18180098028', //结算卡开户手机号
            'location' => base64_encode("成都"), //结算卡开户城市
        );
        echo json_encode($msgDate);
        $picJson = array(
            'cert_correct' => $this->base64EncodeImage("E:/1.jpg"),
            'cert_opposite' => $this->base64EncodeImage("E:/1.jpg"),
            'cert_meet' => $this->base64EncodeImage("E:/1.jpg"),
            'card_correct' => $this->base64EncodeImage("E:/1.jpg"),
            'card_opposite' => $this->base64EncodeImage("E:/1.jpg"),
            'bl_img' => $this->base64EncodeImage("E:/1.jpg"),
            'door_img' => $this->base64EncodeImage("E:/1.jpg"),
            'cashier_img' => $this->base64EncodeImage("E:/1.jpg"),
        );

        $priKeyFilePath = $this->privatekeypath . $post_data['account'] . "_private_key.pem";
        $sign = $this->rsaDataSign(json_encode($msgDate), $priKeyFilePath); //RSA签名

        $post_data['msg'] = json_encode($msgDate);

        $data = base64_encode(json_encode($post_data));
        $decrypt = $this->rsaPublicEncrypt($data, $this->publickeypath); //RSA公钥加密

        $send_data = array(
            'data' => $decrypt,
            'signature' => $sign,
            'pic' => json_encode($picJson)
        );
        echo json_encode($send_data);echo "-------------";
        $res = $this->send_post1($this->httpUrl . 'Kubei', json_encode($send_data));
        echo $res;
        $res = json_decode($res, true);
        $res_data = $res['data'];
        $res_sign = $res['signature'];
        $original = $this->rsaPrivateDecrypt(base64_decode($res_data), $priKeyFilePath); //RSA私钥解密
        $original = json_decode($original, true);
        $res_msg = json_decode($original['msg'], true);
        echo $original['msg'];

//验证签名
        $valid = $this->isValid($original['msg'], base64_decode($res_sign), $this->publickeypath);

        if ("000000" === $res_msg['respCode']) {
            print("验卡成功.");
        } else {
            print("验卡失败.返回码:" . $res_msg['respCode'] . ",失败原因:" . $res_msg['respInfo'] . "<br/>");
        }
    }

    private function send_post($url, $post_data)
    {
        $postdata = http_build_query($post_data);
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type:application/x-www-form-urlencoded',
                'content' => $postdata,
                'timeout' => 30 * 60 // 超时时间（单位:s）
            )
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        return $result;
    }

    private function send_post1($url = '', $post_data = '')
    {
        if (empty($url) || empty($post_data)) {
            return false;
        }
        $postUrl = $url;
        $curlPost = $post_data;
        $ch = curl_init(); //初始化curl
        curl_setopt($ch, CURLOPT_URL, $postUrl); //抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0); //设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1); //post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        $data = curl_exec($ch); //运行curl
        curl_close($ch);
        return $data;
    }

    /**
     * RSA公钥加密(分段加密)
     * @param type $data
     * @param type $keyPath
     * @return type
     */
    private function rsaPublicEncrypt($data, $keyPath)
    {
        $key = openssl_pkey_get_public(file_get_contents($keyPath));
        $ciphertext = null;
        $cipher_len = strlen($data);
        if ($cipher_len - $this->RSA_MAX_ORIGINAL > 0) {
            $flag = 0;
            for ($i = ceil($cipher_len / $this->RSA_MAX_ORIGINAL); $i > 0; $i--) {
                $temp = substr($data, $flag, $this->RSA_MAX_ORIGINAL);
                $r = openssl_public_encrypt($temp, $encryptData, $key);
                $ciphertext .= $encryptData;
                if ($r) {
                    $flag += $this->RSA_MAX_ORIGINAL;
                } else {
                    print("RSA分段加密失败.");
                }
            }
        } else {
            $r = openssl_public_encrypt($data, $encryptData, $key);
            if ($r) {
                $ciphertext = $encryptData;
            }
        }
        return base64_encode($ciphertext);
    }

    /**
     * RSA私钥解密(分段解密)
     * @param $data
     * @param $keyPath
     * @return bool|string
     */
    private function rsaPrivateDecrypt($data, $keyPath)
    {
        $key = openssl_pkey_get_private(file_get_contents($keyPath));
        $originalText = null;
        $original_len = strlen($data);
        if ($original_len - $this->RSA_MAX_CIPHER > 0) {
            $flag = 0;
            for ($i = ceil($original_len / $this->RSA_MAX_CIPHER); $i > 0; $i--) {
                $temp = substr($data, $flag, $this->RSA_MAX_CIPHER);
                $r = openssl_private_decrypt($temp, $decrypted, $key);
                $originalText .= $decrypted;
                if ($r) {
                    $flag += $this->RSA_MAX_CIPHER;
                } else {
                    print("RSA分段解密失败.");
                }
            }
        } else {
            $r = openssl_private_decrypt($data, $decrypted, $key);
            if ($r) {
                $originalText = $decrypted;
            }
        }
        return base64_decode($originalText);
    }

    /**
     * 数据签名
     * @param type $data
     * @param type $keyPath
     * @return boolean
     */
    private function rsaDataSign($data, $keyPath)
    {

        if (empty($data)) {
            return False;
        }

        $private_key = file_get_contents($keyPath);
        if (empty($private_key)) {
            echo "Private Key error!";
            return False;
        }

        $pkeyid = openssl_get_privatekey($private_key);
        if (empty($pkeyid)) {
            echo "private key resource identifier False!";
            return False;
        }

        $verify = openssl_sign($data, $signature, $pkeyid, OPENSSL_ALGO_MD5);
        openssl_free_key($pkeyid);
        return base64_encode($signature);
    }

    /**
     * 数据验签
     * @param string $data
     * @param string $signature
     * @param $keyPath
     * @return bool
     */
    private function isValid($data = '', $signature = '', $keyPath)
    {
        if (empty($data) || empty($signature)) {
            return False;
        }

        $public_key = file_get_contents($keyPath);
        if (empty($public_key)) {
            echo "Public Key error!</br>";
            return False;
        }

        $pkeyid = openssl_get_publickey($public_key);
        if (empty($pkeyid)) {
            echo "public key resource identifier False!</br>";
            return False;
        }

        $ret = openssl_verify($data, $signature, $pkeyid, OPENSSL_ALGO_MD5);
        if ($ret == 1) {
            return true;
        } else {
            return false;
        }
    }

    public static function hexStrToBytes($str, $length = null)
    {
        $ret = array('c*');
        for ($i = 0, $l = strlen($str) / 2; $i < $l; ++$i) {
            $x = intval(substr($str, 2 * $i, 2), 16);
            if ($x > 128)
                $x -= 256;
            $ret[] = $x;
        }
        //补全24位
        if (isset($length)) {
            for ($i = count($ret), $j = 1; $i <= $length; ++$i, ++$j)
                $ret[] = $ret[$j];
        }
        return call_user_func_array('pack', $ret);
    }

    /**
     * 图片Base64编码
     * @param type $image_file
     * @return string
     */
    private function base64EncodeImage($image_file)
    {
        $image_info = getimagesize($image_file);
        $image_data = fread(fopen($image_file, 'r'), filesize($image_file));
        $base64_image = array(
            'suffix' => pathinfo($image_file, PATHINFO_EXTENSION),
            'content' => chunk_split(base64_encode($image_data))
        );
        return json_encode($base64_image);
    }

    /**
     * 用户扫双屏二维码
     */
    public function twoscree_wxpay()
    {

    }

    /**
     * 用户扫手机二维码
     */
    public function wxpay()
    {
        $price = I('price');

        $post_data = array(
            'account' => $this->account,
            'orderCode' => 'tb_WeixinPay'
        );

        $msgDate = array(
            'channel_code' => 'WXPAY',
            'amount' => $price * 100,
            'info' => ''
        );
        $post_data['msg'] = base64_encode(json_encode($msgDate));

        $priKeyFilePath = $this->privatekeypath . $post_data['account'] . "_private_key.pem";
        $sign = $this->rsaDataSign($post_data['msg'], $priKeyFilePath); //RSA签名

        $data = base64_encode(json_encode($post_data));
        $decrypt = $this->rsaPublicEncrypt($data, $this->publickeypath); //RSA公钥加密

        $send_data = array(
            'data' => $decrypt,
            'signature' => $sign
        );

        $res = $this->send_post1($this->httpUrl . 'Kubei', json_encode($send_data));
        $res = json_decode($res, true);
        $res_data = $res['data'];
        $res_sign = $res['signature'];

        $original = $this->rsaPrivateDecrypt(base64_decode($res_data), $priKeyFilePath); //RSA私钥解密
        $original = json_decode($original, true);
        $res_msg = json_decode($original['msg'], true);

//验证签名
        $valid = $this->isValid($original['msg'], base64_decode($res_sign), $this->publickeypath);

        if (!$valid) {
            file_put_contents('./data/log/jiuyunbank/pay.log', date("Y-m-d H:i:s") . "扫码支付-签名错误:" . json_encode($res) . PHP_EOL, FILE_APPEND | LOCK_EX);
        }

        if ("000000" === $res_msg['respCode']) {
            file_put_contents('./data/log/jiuyunbank/pay.log', date("Y-m-d H:i:s") . "扫码支付-支付成功:" . json_encode($res_msg) . PHP_EOL, FILE_APPEND | LOCK_EX);
            $this->assign('url', $res_msg['QRcodeURL']);
            redirect($res_msg['QRcodeURL']);die;
            $this->display();
            //订单号
            $res_msg['orderId'];
        } else {
            file_put_contents('./data/log/jiuyunbank/pay.log', date("Y-m-d H:i:s") . "扫码支付-支付失败:" . json_encode($res) . PHP_EOL, FILE_APPEND | LOCK_EX);

        }
    }

    /**
     * 用户扫台签
     */
    public function qr_wxpay()
    {
        //这里直接获得openid;
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            $id = I("id");
            $merchant = M("merchants_cate")->where("id=$id")->find();
//            $openid = $this->_get_openid();
//            $this->assign('openid', $openid);
            $this->assign("merchant", $merchant);
            $this->assign('seller_id', I('id'));
            $this->display();
        }
    }

    /**
     * 扫用户支付码
     */
    public function microwxpay($id, $price, $auth_code, $checker_id = '', $type)
    {
        header("Content-type:text/html;charset=utf-8");
        if (!$auth_code) {
            $this->error('参数错误!');
        }
        // 支付信息
        if (!$id) {
            return array("code" => "error", "msg" => "失败", "data" => "支付失败");
        }
        $res = M('merchants_cate')->where("merchant_id=$id")->find();
        if ((int)$res['merchant_id'] == 0) {
            return array("code" => "error", "msg" => "失败", "data" => "还未申请支付业务");
        }
        $remark = date('YmdHis') . rand(100000, 999999);
        // 插入数据库的数据
        $data['merchant_id'] = (int)$res['merchant_id'];
        $data['checker_id'] = $checker_id;
        $data['paystyle_id'] = 1;
        $data['price'] = $price;
        $data['remark'] = $remark;
        $data['status'] = 0;
        $data['cate_id'] = $res['id'];
        $data['mode'] = 2;
        $data['paytime'] = time();
        $data['bank'] = 5;
        //添加的数据
        $data['cost_rate'] = '';
        $data['bill_date'] = date("Ymd", time());

        $data['phone_info'] = $_SERVER['HTTP_USER_AGENT'];
//        $mchid = $res['wx_mchid'];

        $good_name = "向" . $res['jianchen'] . "支付" . $price . "元";
        M("pay")->add($data);

        $post_data = array(
            'account' => $this->account,
            'orderCode' => 'tb_wxscanpay'
        );

        $msgDate = array(
            'channel_code' => '',
            'tran_amount' => $price * 100,
            'product_name' => base64_encode("$good_name"),
            'product_detail' => base64_encode("$good_name"),
            'auth_code' => $auth_code
        );
        if($type == 'wx')
            $msgDate['channel_code'] = 'WXPAY';
        else if($type == 'ali')
            $msgDate['channel_code'] = 'ALIPAY';
        else
            return array("code" => "error", "msg" => "参数错误", "data" => '参数错误');

        $post_data['msg'] = base64_encode(json_encode($msgDate));

        $priKeyFilePath = $this->privatekeypath . $post_data['account'] . "_private_key.pem";
        $sign = $this->rsaDataSign($post_data['msg'], $priKeyFilePath); //RSA签名


        $data = base64_encode(json_encode($post_data));
        $decrypt = $this->rsaPublicEncrypt($data, $this->publickeypath); //RSA公钥加密

        $send_data = array(
            'data' => $decrypt,
            'signature' => $sign
        );
        // str_replace("\\/", "/",  json_encode($send_data))
        $res = $this->send_post1($this->httpUrl . 'Kubei', json_encode($send_data));
        $res = json_decode($res, true);
        $res_data = $res['data'];
        $res_sign = $res['signature'];

        $original = $this->rsaPrivateDecrypt(base64_decode($res_data), $priKeyFilePath); //RSA私钥解密
        $original = json_decode($original, true);
        $res_msg = json_decode($original['msg'], true);
        echo $original['msg'];
//验证签名
        $valid = $this->isValid($original['msg'], base64_decode($res_sign), $this->publickeypath);
        if (!$valid) {
            file_put_contents('./data/log/jiuyunbank/pay.log', date("Y-m-d H:i:s") . "授权码支付-签名错误:" . json_encode($res) . PHP_EOL, FILE_APPEND | LOCK_EX);
        }

        if ("000000" === $res_msg['respCode']) {
            $pay_change = M("pay");
            $data['remark'] = $remark;
            $data['status'] = 1;
            if ($pay_change->where("remark=$remark")->find()) $pay_change->where("remark=$remark")->save($data);
            A("Pay/Barcode")->push_pay_message($remark);
            file_put_contents('./data/log/jiuyunbank/pay.log', date("Y-m-d H:i:s") . "授权码支付-支付成功:" . json_encode($res_msg) . PHP_EOL, FILE_APPEND | LOCK_EX);
            return array("code" => "success", "msg" => "成功", "data" => '支付成功');
        } else {
            A("Pay/Barcode")->push_pay_message($remark);
            file_put_contents('./data/log/jiuyunbank/pay.log', date("Y-m-d H:i:s") . "授权码支付-支付失败:" . json_encode($res) . PHP_EOL, FILE_APPEND | LOCK_EX);
            return array("code" => "error", "msg" => "失败", "data" => $res_msg['respInfo']);
        }
    }

    /**
     * 订单状态查询
     */
    public function orderConfirm()
    {
    	$orderId = I('orderid');
        $OC_URL = 'http://check.chinavalleytech.com/ChannelOrderQuery/Kubei';
        
        $post_data = array(
            'account' => $this->account,
            'orderCode' => 'tb_OrderConfirm'
        );

        $msgDate = array(
            'orderId' => $orderId,
            'CallbackFlag' => 1,
        );

        $post_data['msg'] = json_encode($msgDate);

        $priKeyFilePath = $this->privatekeypath . $post_data['account'] . "_private_key.pem";
        $sign = $this->rsaDataSign($post_data['msg'], $priKeyFilePath); //RSA签名


        $data = base64_encode(json_encode($post_data));
        $decrypt = $this->rsaPublicEncrypt($data, $this->publickeypath); //RSA公钥加密

        $send_data = array(
            'data' => $decrypt,
            'signature' => $sign,
            'ChannelFlag' => "SDD"
        );

        $res = $this->send_post1($OC_URL, json_encode($send_data));
        $res = json_decode($res, true);
        $res_data = $res['data'];
        $res_sign = $res['signature'];

        $original = $this->rsaPrivateDecrypt(base64_decode($res_data), $priKeyFilePath); //RSA私钥解密
        $original = json_decode($original, true);
        $res_msg = json_decode($original['msg'], true);

//验证签名
        $valid = $this->isValid($original['msg'], base64_decode($res_sign), $this->publickeypath);
        print_r($res_msg);

    }

    /**
     * 异步回调
     */
    public function notify()
    {
        file_put_contents('../jynotify.log', date("Y-m-d H:i:s") . json_encode($_REQUEST) . PHP_EOL, FILE_APPEND | LOCK_EX);

    }

    public function paa()
    {
        $auth_code = I('auth_code');
        $type = I('type');
        $post_data = array(
            'account' => $this->account,
            'orderCode' => 'tb_wxscanpay'
        );

        $msgDate = array(
            'channel_code' => '',
            'tran_amount' => 1,
            'product_name' => base64_encode("电脑"),
            'product_detail' => base64_encode("电脑"),
            'auth_code' => $auth_code
        );
        if($type == 'wx')
            $msgDate['channel_code'] = 'WXPAY';
        else if($type == 'ali')
            $msgDate['channel_code'] = 'ALIPAY';

        $post_data['msg'] = base64_encode(json_encode($msgDate));

        $priKeyFilePath = $this->privatekeypath . $post_data['account'] . "_private_key.pem";
        $sign = $this->rsaDataSign($post_data['msg'], $priKeyFilePath); //RSA签名


        $data = base64_encode(json_encode($post_data));
        $decrypt = $this->rsaPublicEncrypt($data, $this->publickeypath); //RSA公钥加密

        $send_data = array(
            'data' => $decrypt,
            'signature' => $sign
        );
        // str_replace("\\/", "/",  json_encode($send_data))
        $res = $this->send_post1($this->httpUrl . 'Kubei', json_encode($send_data));
        $res = json_decode($res, true);
        $res_data = $res['data'];
        $res_sign = $res['signature'];

        $original = $this->rsaPrivateDecrypt(base64_decode($res_data), $priKeyFilePath); //RSA私钥解密
        $original = json_decode($original, true);
        $res_msg = json_decode($original['msg'], true);
//验证签名
        $valid = $this->isValid($original['msg'], base64_decode($res_sign), $this->publickeypath);
        if (!$valid) {
            echo "error";
        }

        if ("000000" === $res_msg['respCode']) {
            dump($res_msg);
            print("扫码扣款成功.");
        } else {
            print("扫码扣款失败</br>返回码:" . $res_msg['respCode'] . "</br>失败原因:" . $res_msg['respInfo'] . "<br/>");
            var_dump($res_msg);
        }

    }

}
