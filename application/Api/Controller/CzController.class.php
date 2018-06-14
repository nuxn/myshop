<?php

namespace Api\Controller;

use think\Controller;

class  CzController extends Controller
{

    public $path;
    private $signKey; // 密钥
    private $pay_model;

    public function _initialize()
    {
        $this->pay_model = M('pay');
        $this->url = "https://aop.koolyun.com:443/apmp/rest/v2";
        $this->apikey = "YPT17001P";
        $this->notify = "http://sy.youngport.com.cn/notify/msbank.php";
        $this->path = $_SERVER['DOCUMENT_ROOT'] . '/data/log/member/';
        $this->private_key = '-----BEGIN RSA PRIVATE KEY-----
MIICXQIBAAKBgQCbexvFt/rOGUOVDPbT99wWt3ChnmcqRc+lmJkEDHP98c8rd+Ih
V34VfjeA2+bhaJ66ZlN+sxJG871GIA6X9o7MOFjFsdAkXYAK+EyHiRZx4drhoaiM
LqxP+ygH3BlvvEEHUUT+ZW0lg2wgcRrzcUDHKZ0u112cQkZgo+Skivm6QQIDAQAB
AoGAS2g8wvsE9/pGzb5Y49sdciMLzEbQEC+FkvHcnJsRkoM5kAJ3uOX/L5tkfemp
I3+jJBJGwndFEQZbsOwRR+B7xoywgJ5+dlyneXEoNfbOJ4J3tP/IVoIDHr2ax8uW
3/IizcgcL8Wc6AyryaQfFb9nEBMUdTt3k3VUEZC4Ef/xccECQQDJ0dj5e3vYbS7F
yIsNlv5HBVzSK++qbxmefT0ZTrvgYPp/g+tFhY8blzOxhbJj3Cp+FxPqL9GOLg1P
hVNMYYj5AkEAxTian96ke9hQY5FjJ/e6q1fe8KzQG79/aC4q4j7rS5Z35kSuDA/Y
Pko47ta2AI5otCdQVXsvNBhFHaO3FKMViQJBAJcNK+NWS9Qpq9c2iPTL7VcEqXtY
jRG4A6m+vKsjZbTDgNlNyBqJoxmYaoVUtrbNAzTKWwptbd+HkkjRVg4V9ikCQQCX
KFkqqwQ6f4KtraLn4TFLXh/bKzid69oEyU3I9hx1ZLAk5wLW79X3d//G3v3D02Jg
obkqqy10qh1fKDmMMaqxAkB+h+DHSA3k4AmRtuKA+fQ9PoLRSbGqYiKEmGLaZvuE
WBDdsn6coSK8qlh4Jxv9dquCaymS9Y+lGzBh2o4n0jOF
-----END RSA PRIVATE KEY-----';
    }

    public function test()
    {
        echo date('Y-m-d H:i:s');
    }

    private function rsaSign($data, $private_key)
    {
        //以下为了初始化私钥，保证在您填写私钥时不管是带格式还是不带格式都可以通过验证。
        $res = openssl_get_privatekey($private_key);
        if ($res) {
            openssl_sign($data, $sign, $res);
        } else {
            echo "您的私钥格式不正确!" . "<br/>" . "The format of your private_key is incorrect!";
            exit();
        }
        openssl_free_key($res);
        $sign = strtoupper(bin2hex($sign));
        return $sign;
    }

    private function httpRequst($url, $data, $res, $appkey)
    {
        $post_data = 'params=' . $data;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 4);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type:application/x-www-form-Urlencoded;charset=utf-8",
            "Accept-Language:zh-cn",
            "x-apsignature:" . $res,
            "x-appkey:" . $appkey
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);

        curl_setopt($ch, CURLOPT_HEADER, TRUE);
        curl_setopt($ch, CURLOPT_TIMEOUT, 180);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $output = curl_exec($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $response_header = substr($output, 0, $header_size);
        $response_body = substr($output, $header_size);
        curl_close($ch);
        $response_body = trim($response_body, '[');
        $response_body = trim($response_body, ']');
        $response_body = json_decode($response_body, 1);
        $response_header_arr = array();
        $response_header_arr = explode(': ', $response_header);
        if ((json_last_error() != JSON_ERROR_NONE) or empty($response_header_arr)) {
            throw new QrcodePayException("Analyze return json error.");
        }
        $response_header_return = array();
        if (!empty($response_header_arr[4])) {
            $response_header_return['x_apsignature'] = str_replace(array("\r\n", "\r", "\n", "Content-Type"), "", $response_header_arr[4]);
        }
        return json_encode(array('header' => $response_header_return, 'body' => $response_body));
    }

    public function verify()
    {
        $Verify = new \Think\Verify();
        $Verify->codeSet = '0123456789';
        $Verify->length = 4;
        $Verify->entry();
    }

    public function setPw()
    {
        //验证码短信验证码
        ($phone = I('phone')) || $this->err('手机号码不对');
        ($code = I("code")) || $this->err('短信验证码为空');
        ($card_id = I("card_id")) || $this->err('短信验证码为空');
        ($openid = I("openid")) || $this->err('短信验证码为空');
        ($code = I("code")) || $this->err('短信验证码为空');
        ($password = I('password')) || $this->err('密码为空');
        M('sms_logs')->where(array('phone' => $phone, 'code' => $code))->find() || $this->err('验证码不对');

        M('screen_memcard_use')->where(array('id' => $card_id, 'fromname' => $openid))->find() || $this->err('memcard_use is empty');

        M('screen_memcard_use')->where(array('id' => $card_id))->save(array('pay_pass' => md5($password . 'tiancaijing'))) !== false ? $this->succ() : $this->err('密码设置失败');
    }

    public function sendSms()
    {
        //  $this->add_log();
        ($phone = I('phone')) || $this->err('手机号码为空');
        //验证码验证码
        $verify = new \Think\Verify();
        $verify->check(trim(I('code'))) || $this->err('图片验证码不对');
        //检测图片验证码
        Vendor("SMS.CCPRestSmsSDK");
        $config_arr = C('SMS_CONFIG'); // 读取短信配置
        // 选择短信模板
        $tempId = $config_arr['PwdTemplateId'];
        $rest = new \REST($config_arr['serverIP'], $config_arr['serverPort'], $config_arr['softVersion']);
        $rest->setAccount($config_arr['accountSid'], $config_arr['accountToken']);
        $rest->setAppId($config_arr['appId']);
        $sms_msg = rand(1000, 9999);
        M('sms_logs')->add(array('phone' => $phone, 'code' => $sms_msg, 'sms_type' => '5'));
        $result = $rest->sendTemplateSMS($phone, array($sms_msg, '5'), $tempId); // 发送模板短信
        if (empty($result) || $result->statusCode != 0) {
            $result = (array)$result;
            $this->err($result['statusMsg']);
        } else {
            $this->succ();
        }
    }

    public function check_code()
    {
        ($phone = I('phone')) || err('手机号码不对');
        ($code = I("code")) || err('短信验证码为空');
        //检测验证码
        $data = M('sms_logs')->where(array('phone' => $phone, 'code' => $code))->find();

        $data ? $this->succ() : $this->err('验证码不对');
    }

    public function info()
    {
        ($card_id = I('card_id')) || $this->alert('card_id is empty');
        ($openid = I('openid')) || $this->alert('openid is empty');
        //查看用户的基本信息
        ($screen_memcard = M('screen_memcard')->where(array('card_id' => $card_id))->field('id,integral_dikou,credits_use,credits_discount,mid')->find());
        //查看用户id
        ($mem = M('screen_mem')->where(array('openid' => $openid, 'userid' => $screen_memcard['mid']))->find()) || $this->alert($openid . ' member is not find ' . $screen_memcard['mid']);

        $info = M('screen_memcard_use')->where(array('memcard_id' => $screen_memcard['id'], 'memid' => $mem['id']))->find();
        //余额信息
        $this->mem = $mem;
        $this->info = $info;
        $this->openid = $openid;
        $this->card_id = $card_id;
        $this->display();
    }

    //暂时没有做分页
    public function record()
    {
        ($card_id = I('card_id')) || $this->err('card_id is empty');
        ($openid = I('openid')) || $this->err('openid is empty');

        ($screen_memcard = M('screen_memcard')->where(array('card_id' => $card_id))->find()) || $this->err('card is emtpy');
        ($mem = M('screen_mem')->where(array('openid' => $openid, 'userid' => $screen_memcard['mid']))->find()) || $this->err('screen_mem is not find');

        ($use = M('screen_memcard_use')->where(array('memcard_id' => $screen_memcard['id'], 'memid' => $mem['id']))->find()) || $this->err('use is empty');

        $cdklist = M('screen_memcard_cdk_log')->field('l.use_time,price')
            ->join('l right join ypt_screen_memcard_cdk c on l.cdk_id=c.id')
            ->where(array('l.memid' => $use['id']))
            ->select();
        $lists = M('user_recharge')->order('id desc')->where(array('memcard_id' => $use['id'], 'uid' => $use['memid'], 'status' => 1))->select();
        $this->lists = $lists;
        $this->cdklist = $cdklist;
        $this->display();
    }

    public function alert($str)
    {
        echo '<script>alert("' . $str . '")</script>';
        exit;
    }

    public function index()
    {
        ($openid = I('openid')) || $this->alert('openid is empty');
        ($card_id = I('card_id')) || $this->alert('card_id is empty');
        $encrypt_code = I('encrypt_code','','trim');

        ($screen_memcard = M('screen_memcard')->where(array('card_id' => $card_id))->field('id,integral_dikou,credits_use,credits_discount,mid')->find());
        if($encrypt_code){
            $encrypt_code = str_replace(' ','+',$encrypt_code);
            $card_code = $this->decrypt_code($encrypt_code);
            //查看用户id
            $info = M('screen_memcard_use')->where(array('card_code' => $card_code))->find();
            $mem = M('screen_mem')->where(array('id' => $info['memid']))->find();
        }else{
            //查看用户id
            ($mem = M('screen_mem')->where(array('openid' => $openid, 'userid' => $screen_memcard['mid']))->find()) || $this->alert($openid . ' member is not find ' . $screen_memcard['mid']);
            $info = M('screen_memcard_use')->where(array('memcard_id' => $screen_memcard['id'], 'memid' => $mem['id']))->find();
        }
        //查看商户积分
        //$screen_mem = M('screen_mem')->where(array('id' => $screen_memcard['mid']))->find();

        //	$merchants  = M('merchants')->where(array('uid'=>$screen_memcard['mid']))->find();
        //var_dump($screen_memcard['mid']);
        $screen_cardset = M('screen_cardset')->where(array('c_id' => $screen_memcard['id']))->find();
        if(!$screen_cardset){
            header('Content-Type: text/html; charset=utf-8');
            exit('<script type="text/javascript">alert("会员卡已失效!")</script>');
        }

        if ($screen_cardset['recharge_1'] > 0) $price[]['price'] = $screen_cardset['recharge_1'];
        if ($screen_cardset['recharge_2'] > 0) $price[]['price'] = $screen_cardset['recharge_2'];
        if ($screen_cardset['recharge_3'] > 0) $price[]['price'] = $screen_cardset['recharge_3'];
        if ($screen_cardset['recharge_4'] > 0) $price[]['price'] = $screen_cardset['recharge_4'];
        if ($screen_cardset['recharge_5'] > 0) $price[]['price'] = $screen_cardset['recharge_5'];
        $send = 0;
        foreach ($price as $key => $v) {
            //判断是否赠送开关是否打开，时间区间
            if ($screen_cardset['recharge_send_cash'] && $screen_cardset['recharge_sen_start'] <= time() && $screen_cardset['recharge_sen_end'] >= time()) {
                $send = 1;
                if ($screen_cardset['recharge_sen_range']) {
                    $range = explode(';', $screen_cardset['recharge_sen_range']);
                    foreach ($range as &$val) {
                        $val = explode(',', $val);
                        if ($v['price'] >= $val[0] && $v['price'] <= $val[1]) {
                            $price[$key]['de_price'] = $val[2];
                            break;
                        }
                    }
                } elseif ($screen_cardset['recharge_sen_percent'] && ($v['price'] >= $screen_cardset['recharge_min'])) {
                    $price[$key]['de_price'] = $v['price'] * $screen_cardset['recharge_sen_percent'] / 100;
                } else {
                    $price[$key]['de_price'] = 0;
                }
            } else {
                $price[$key]['de_price'] = 0;
            }
        }

        $this->recharge_custom = $screen_cardset['recharge_custom'];
        $this->screen_cardset = $screen_cardset;
        $this->mem = $mem;
        $this->openid = $openid;
        $this->card = $card_id;
        $this->price = $price;
        $this->info = $info;
        $this->send = $send;
        $this->display();
    }

    //长按识别二维码,激活绑定会员卡
    public function wx_card_page()
    {
        $mu_id = I('uid');
        $mch_info = $this->get_mch_info($mu_id);
        $this->assign('mch_info', $mch_info);
        $url = M('screen_memcard')->where(array('mid'=>$mu_id))->getField('show_qrcode_url');
        $this->assign('url', $url);
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')) {
            $title = '长按识别二维码,激活绑定会员卡';
        }else {
            $title = '长按保存二维码,至微信识别二维码后,激活绑定会员卡';
        }
        $this->assign('title', $title);
        $this->display('member_info');
    }

    #实体卡充值入口
    public function entity_cz()
    {
        // 这里获取商户的id，默认值
        $mu_id = I('uid',3568);
        $this->assign('uid', $mu_id);
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false || $mu_id==115) {
            $openid = $this->get_openid();
            $this->assign('openid', $openid);
        } else if(strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient') !== false || $mu_id == 494){
        } else {
            $this->display('error');
            die;
        }
        $this->display();
    }

    #实体卡充值入口
    public function entity_cz_post()
    {
        $mu_id = I('uid');
        $phone = I('phone');
        $real_code = I('real_code');
        $mch_info = $this->get_mch_info($mu_id);
        ($screen_memcard = M('screen_memcard')->where(array('mid' => $mu_id))->field('id,integral_dikou,credits_use,credits_discount,mid')->find()) || $this->alert('mid is empty');
        $info = M('screen_memcard_use')->where(array('entity_card_code|card_code' => $real_code))->find();
        ($mem = M('screen_mem')->where(array('memid'=>$info['memid']))->find()) || $this->alert('会员信息不存在');

        ($screen_cardset = M('screen_cardset')->where(array('c_id' => $screen_memcard['id']))->find()) || $this->alert('没有设置');
        if ($screen_cardset['recharge_1'] > 0) $price[]['price'] = $screen_cardset['recharge_1'];
        if ($screen_cardset['recharge_2'] > 0) $price[]['price'] = $screen_cardset['recharge_2'];
        if ($screen_cardset['recharge_3'] > 0) $price[]['price'] = $screen_cardset['recharge_3'];
        if ($screen_cardset['recharge_4'] > 0) $price[]['price'] = $screen_cardset['recharge_4'];
        if ($screen_cardset['recharge_5'] > 0) $price[]['price'] = $screen_cardset['recharge_5'];
        foreach ($price as $key => $v) {
            //判断是否赠送开关是否打开，时间区间
            if ($screen_cardset['recharge_send_cash'] && $screen_cardset['recharge_sen_start'] <= time() && $screen_cardset['recharge_sen_end'] >= time()) {
                if ($screen_cardset['recharge_sen_range']) {
                    $range = explode(';', $screen_cardset['recharge_sen_range']);
                    foreach ($range as &$val) {
                        $val = explode(',', $val);
                        if ($v['price'] >= $val[0] && $v['price'] <= $val[1]) {
                            $price[$key]['de_price'] = $val[2];
                            break;
                        }
                    }
                } elseif ($screen_cardset['recharge_sen_percent'] && ($v['price'] >= $screen_cardset['recharge_min'])) {
                    $price[$key]['de_price'] = $v['price'] * $screen_cardset['recharge_sen_percent'] / 100;
                } else {
                    $price[$key]['de_price'] = 0;
                }
            } else {
                $price[$key]['de_price'] = 0;
            }
        }
        $this->assign('price', $price);
        $this->assign('code', $real_code);
        $this->assign('phone', $phone);
        $this->assign('hidephone', substr_replace($phone, '****', 4, 4));
        $this->assign('mch_info', $mch_info);
        $this->assign('uid', $mu_id);
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false || $mu_id==115) {
            $openid = I('openid');
            $this->assign('openid', $openid);
            $info = $this->getConfig();
            $this->assign('parm', $info);
            $this->display('wx_recharge');
        } else if(strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient') !== false){
            $this->display('ali_recharge');
        } else {
            $this->display('ali_recharge');
//            $this->display('error');
        }
        die;
    }

//    使用支付宝充值会员卡
    public function ali_recharge_pay()
    {
        $mu_id = I('uid');
        $price = I('price');
        $card_code = I('card_code');
        $m_id = M('merchants')->where(array('uid'=>$mu_id))->getField('id');
        $cate_data = M('merchants_cate')->field('ali_bank,id,merchant_id')->where(array('merchant_id'=>$m_id,'checker_id'=> 0))->find();
        $order_info = $this->create_ali_order($price,$card_code,$cate_data['id']);
        if($order_info){
            switch ($cate_data['ali_bank']) {
                case 3:
                    $result = array('code'=>'0003','msg'=>'暂未开通，敬请期待!');
                    break;
                case 9:
                    $result = array('code'=>'0003','msg'=>'暂未开通，敬请期待!');
                    break;
                case 10:
                    $result = array('code'=>'0003','msg'=>'暂未开通，敬请期待!');
                    break;
                case 11:
                    $result = A("Pay/Barcodexdlbank")->get_card_recharge_url($order_info,$cate_data);
                    break;
                case 12:
                    $result = A("Pay/Leshuabank")->get_card_recharge_url($order_info,$cate_data);
                    break;
                default:
                    $result = array('code'=>'0003','msg'=>'暂未开通，敬请期待!');
                    break;
            }
            $this->ajaxReturn($result);
        } else {
            $this->err('订单未生成');
        }
    }

    public function get_mch_info($uid)
    {
        $role_id = M('merchants_role_users')->where(array('uid'=>$uid))->getField('role_id');
        // 角色为代理
        if($role_id == '2'){

        } else if($role_id == '3'){ // 商户角色
            $info = M('merchants')->field('logo_url,merchant_jiancheng,base_url')->where(array('uid'=>$uid))->find();
            return $info;
        } else {
            return false;
        }
    }
    //验证手机号和卡号
    public function check_phone_code()
    {
        $code = I('code');
        $memphone = I('memphone');
        ($uid = I('uid')) || $this->err('uid is empty');
        $where['m.mid'] = $uid;
        if(!$code && !$memphone){
            $this->err('卡号或者手机号必须填一项');
        }
        if($code){
            $where['entity_card_code|card_code'] = $code;
        }elseif ($memphone){
            $where['mem.memphone'] = $memphone;
        }

        $card = M('screen_memcard_use u')
            ->where($where)
            ->join('ypt_screen_memcard m on m.id=u.memcard_id')
            ->join('left join ypt_screen_mem mem on mem.id=u.memid')
            ->field('u.card_code,u.entity_card_code,u.status,u.e_status,mem.memphone,m.mid,m.is_agent')
            ->find();
        if(!$card){
            $this->err('卡号不存在');
        } elseif ($card['entity_card_code'] && $card['e_status'] != 1){
            $this->err('该实体卡未绑定，请联系商户绑定');
        } elseif ($card['card_code'] && $card['status'] != 1){
            $this->err('该微信卡未激活');
        } else {
            $this->succ($card);
        }
    }

    public function member_info()
    {
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            $this->display();
            die;
        }  else {
            $this->assign('msg','请使用微信打开页面');
            $this->display('error');
        }
    }

    # 充值成功页
    public function member_ok()
    {
        ($type=I('type')) || $this->err('type is empty');
        ($card_code=I('card_code')) || $this->err('card_code is empty');
        $uid=I('uid');
        $this->assign('uid',$uid);
        $this->assign('type',$type);
        $this->assign('card_code',$card_code);
        //充值码充值
        if($type==1){
            ($code = I('code')) || $this->err('code is empty');
            $cdk = M('screen_memcard_cdk')->where(array('code'=>$code))->field('price,id')->find();
            $price = $cdk['price'];
            $time = M('screen_memcard_cdk_log')->where(array('cdk_id'=>$cdk['id']))->getField('use_time');
            $this->assign('time',$time);
            $this->assign('price',$price);//实到金额
        }else{
            //金额充值
            ($order_sn=I('order_sn')) || $this->err('order_sn is empty');
            $recharge = M('user_recharge')->where(array('order_sn'=>$order_sn,'status'=>1))->field('total_price,real_price,add_time')->find();
            $this->assign('total_price',$recharge['total_price']);
            $this->assign('real_price',$recharge['real_price']);
            $this->assign('time',$recharge['add_time']);
        }
        $this->display();
    }

    public function get_total_price()
    {
        ($card_id = I('card_id')) || $this->err('card_id is empty');
        ($price = I('price')) || $this->err('price is empty');
        $c_id = M('screen_memcard')->where(array('card_id' => $card_id))->getField('id');
        $screen_cardset = M('screen_cardset')
            ->where(array('c_id' => $c_id))
            ->field('recharge_send_cash,recharge_min,recharge_sen_percent,recharge_sen_range,recharge_sen_start,recharge_sen_end')
            ->find();
        $data['total_price'] = $price;
        if ($screen_cardset['recharge_send_cash'] && $screen_cardset['recharge_sen_start'] <= time() && $screen_cardset['recharge_sen_end'] >= time()) {
            if ($screen_cardset['recharge_sen_range']) {
                $range = explode(';', $screen_cardset['recharge_sen_range']);
                foreach ($range as &$val) {
                    $val = explode(',', $val);
                    if ($price >= $val[0] && $price <= $val[1]) {
                        $data['total_price'] = $price + $val[2];
                        break;
                    }
                }
            } elseif ($screen_cardset['recharge_sen_percent'] && ($price >= $screen_cardset['recharge_min'])) {
                $data['total_price'] = $price + $price * $screen_cardset['recharge_sen_percent'] / 100;
            }
        }
        $this->succ($data);
    }

    public function quick_buy_ajax()
    {
        ($uid = I('uid')) || $this->err('uid is empty');
    }

// 支付宝创建订单
    public function create_ali_order($price,$card_code,$cate_id)
    {

        ($memcard_use = M('screen_memcard_use')->where(array('entity_card_code|card_code' => $card_code))->find()) || $this->err('memcard_use is NULL');
        ($screen_memcard = M('screen_memcard')->where(array('id' => $memcard_use['memcard_id']))->field('id,card_id,integral_dikou,credits_use,credits_discount,mid')->find()) || $this->err('memcard is NULL');
        ($mem = M('screen_mem')->where(array('id' => $memcard_use['memid']))->find()) || $this->err('screen_mem is not find');
        ($screen_cardset = M('screen_cardset')->where(array('c_id' => $screen_memcard['id']))->find()) || $this->err('没有设置');

        $memcard_id = $memcard_use['id'];
        $order['send_price'] = 0;
        if ($screen_cardset['recharge_send_cash'] && $screen_cardset['recharge_sen_start'] <= time() && $screen_cardset['recharge_sen_end'] >= time()) {
            if ($screen_cardset['recharge_sen_range']) {
                $range = explode(';', $screen_cardset['recharge_sen_range']);
                foreach ($range as &$val) {
                    $val = explode(',', $val);
                    if ($price >= $val[0] && $price <= $val[1]) {
                        $order['send_price'] = $val[2];
                        break;
                    }
                }
            } elseif ($screen_cardset['recharge_sen_percent'] && ($price >= $screen_cardset['recharge_min'])) {
                $order['send_price'] = $screen_cardset['recharge_sen_percent'] / 100;
            }
        }

        //判断是商户还是代理商
        $role_id = M('merchants_role_users')->where(array('uid' => $screen_memcard['mid']))->getField('role_id');

        if ($role_id == 2) {
            $order['agent_id'] = 1;
            $order['mid'] = $screen_memcard['mid'];
        } else {
            $merchants = M('merchants')->where(array('uid' => $screen_memcard['mid']))->find();
            $order['mid'] = $merchants['id'];
            $order['agent_id'] = 0;
        }
        $order['order_sn'] = date('YmdHis') . rand(100000, 999999) . 'cz';
        $order['price'] = $price;
        $order['paystyle_id'] = 2;
        $order['cate_id'] = $cate_id;
        $order['total_price'] = $price + $order['send_price'];
        $order['uid'] = $mem['id'];
        $order['add_time'] = time();
        $order['memcard_id'] = $memcard_id;

        $order_id = M('user_recharge')->add($order);
        return $order;
    }

    //生成订单
    public function entity_create_order()
    {
        ($openid = I('openid')) || $this->err('openid is emtpy');
        ($card_code = I('card_code')) || $this->err('card_code is empty');
        ($price = I('price') / 100) || $this->err('price is empty');

        ($memcard_use = M('screen_memcard_use')->where(array('entity_card_code|card_code' => $card_code))->find()) || $this->err('memcard_use is NULL');
        ($screen_memcard = M('screen_memcard')->where(array('id' => $memcard_use['memcard_id']))->field('id,card_id,integral_dikou,credits_use,credits_discount,mid')->find()) || $this->err('memcard is NULL');
        ($mem = M('screen_mem')->where(array('id' => $memcard_use['memid']))->find()) || $this->err('screen_mem is not find');
        ($screen_cardset = M('screen_cardset')->where(array('c_id' => $screen_memcard['id']))->find()) || $this->err('没有设置');

        $memcard_id = $memcard_use['id'];
        //检测是否有密码
        //$memcard_use['pay_pass'] || $this->err('请设置支付密码', 1);

        $order['send_price'] = 0;
        if ($screen_cardset['recharge_send_cash'] && $screen_cardset['recharge_sen_start'] <= time() && $screen_cardset['recharge_sen_end'] >= time()) {
            if ($screen_cardset['recharge_sen_range']) {
                $range = explode(';', $screen_cardset['recharge_sen_range']);
                foreach ($range as &$val) {
                    $val = explode(',', $val);
                    if ($price >= $val[0] && $price <= $val[1]) {
                        $order['send_price'] = $val[2];
                        break;
                    }
                }
            } elseif ($screen_cardset['recharge_sen_percent'] && ($price >= $screen_cardset['recharge_min'])) {
                $order['send_price'] = $screen_cardset['recharge_sen_percent'] / 100;
            }
        }

        //判断是商户还是代理商
        $role_id = M('merchants_role_users')->where(array('uid' => $screen_memcard['mid']))->getField('role_id');

        if ($role_id == 2) {
            $order['agent_id'] = 1;
            $order['mid'] = $screen_memcard['mid'];
        } else {
            $merchants = M('merchants')->where(array('uid' => $screen_memcard['mid']))->find();
            $order['mid'] = $merchants['id'];
            $order['agent_id'] = 0;
        }
        $order['order_sn'] = date('YmdHis') . rand(100000, 999999) . 'cz';
        $order['price'] = $price;
        $order['total_price'] = $price + $order['send_price'];
        $order['uid'] = $mem['id'];
        $order['add_time'] = time();
        $order['memcard_id'] = $memcard_id;

        $order_id = M('user_recharge')->add($order);
        if ($order['agent_id'] == 1) {
            $data = $this->create_sign_agent($order_id, $openid);
        } else {
            $data = $this->create_sign($order_id, $openid);
        }
        get_date_dir($this->path,'charge','create_order_data',$data);
        //$this->succ($data);
        $this->ajaxReturn(array('code'=>'0','msg'=>'SUCC','data'=>$data,'order_sn'=>$order['order_sn']));
    }
    //生成订单
    public function create_order()
    {
        ($openid = I('openid')) || $this->err('openid is emtpy');
        ($card_id = I('card_id')) || $this->err('card_id is empty');
        ($price = I('price') / 100) || $this->err('price is empty');
        //查看用户id
        ($screen_memcard = M('screen_memcard')->where(array('card_id' => $card_id))->field('id,integral_dikou,credits_use,credits_discount,mid')->find());
        ($mem = M('screen_mem')->where(array('openid' => $openid, 'userid' => $screen_memcard['mid']))->find()) || $this->err('screen_mem is not find');

        ($screen_cardset = M('screen_cardset')->where(array('c_id' => $screen_memcard['id']))->find()) || $this->err('没有设置');

        $memcard = M('screen_memcard_use')->where(array('memcard_id' => $screen_memcard['id'], 'fromname' => $openid))->find();
        $memcard_id = $memcard['id'];
        //检测是否有密码
        $memcard['pay_pass'] || $this->err('请设置支付密码', 1);

        $order['send_price'] = 0;
        if ($screen_cardset['recharge_send_cash'] && $screen_cardset['recharge_sen_start'] <= time() && $screen_cardset['recharge_sen_end'] >= time()) {
            if ($screen_cardset['recharge_sen_range']) {
                $range = explode(';', $screen_cardset['recharge_sen_range']);
                foreach ($range as &$val) {
                    $val = explode(',', $val);
                    if ($price >= $val[0] && $price <= $val[1]) {
                        $order['send_price'] = $val[2];
                        break;
                    }
                }
            } elseif ($screen_cardset['recharge_sen_percent'] && ($price >= $screen_cardset['recharge_min'])) {
                $order['send_price'] = $screen_cardset['recharge_sen_percent'] / 100;
            }
        }

        //判断是商户还是代理商
        $role_id = M('merchants_role_users')->where(array('uid' => $screen_memcard['mid']))->getField('role_id');

        if ($role_id == 2) {
            $order['agent_id'] = 1;
            $order['mid'] = $screen_memcard['mid'];
        } else {
            $merchants = M('merchants')->where(array('uid' => $screen_memcard['mid']))->find();
            $order['mid'] = $merchants['id'];
            $order['agent_id'] = 0;
        }
        $order['order_sn'] = date('YmdHis') . rand(100000, 999999) . 'cz';
        $order['price'] = $price;
        $order['total_price'] = $price + $order['send_price'];
        $order['uid'] = $mem['id'];
        $order['add_time'] = time();
        $order['memcard_id'] = $memcard_id;

        $order_id = M('user_recharge')->add($order);
        if ($order['agent_id'] == 1) {
            $data = $this->create_sign_agent($order_id, $openid);
        } else {
            $data = $this->create_sign($order_id, $openid);
        }
        get_date_dir($this->path,'charge','create_order_data',$data);
        $this->succ($data);
    }

    //1 为微众 2为民生 3微信 4 招商 5 钱方
    public function create_sign($order_id, $openid)
    {
        $order_id || $this->err('order_id is empty');
        ($order = M('user_recharge')->where(array('id' => $order_id))->find()) || $this->err('quick_pay is empty');
        ($res = M('merchants_cate')->where(array('merchant_id' => $order['mid'],'checker_id'=>0,'status'=>1))->find()) || $this->err('merchants is empty');
        M('user_recharge')->where(array('id' => $order_id))->setField('cate_id', $res['id']);
        switch ($res['wx_bank']) {
            //民生银行
            case 2:
                $pay['action'] = 'wallet/trans/jsSale';
                $pay['version'] = '2.0';
                $pay['reqTime'] = date("YmdHis");
                $pay['appId'] = 'wx3fa82ee7deaa4a21';
                $pay['uuid'] = $openid;
                $pay['orderId'] = $order['order_sn'];
                $pay['reqId'] = date("YmdHis") . rand(1000, 9999) . '251';
                $pay['deviceId'] = 'payuser';//终端号
                $pay['transTimeOut'] = '1440';
                $pay['orderSubject'] = '快速购买';
                $pay['orderDesc'] = '快速购买';//订单描述
                $pay['totalAmount'] = $order['price'] * 100;
                $pay['bankCardLimit'] = 2;
                $pay['currency'] = "CNY";
                $pay['acquirerType'] = 'wechat';
                $pay['operatorId'] = "POS 操作员";
                $pay['custId'] = $res['wx_mchid'];
                $pay['notifyUrl'] = 'http://sy.youngport.com.cn/notify/cz/ms.php';
                $pay['cost_rate'] = $this->cost_rate_1($res['wx_mchid'], 1);
                $pay['orderDesc'] = '付款';
                $data = json_encode($pay);
                $data = "[" . $data . "]";
                $res = $this->rsaSign($data, $this->private_key);
                $result = $this->httpRequst($this->url, $data, $res, $this->apikey);
                $data = json_decode($result);
                $result = $data->body->payInfo;
                break;
            // 微众
            case 1:
                header("Content-type:text/html;charset=utf-8");
                vendor('Wzpay.Wzczpay');
                $wzPay = new \Wzczpay();
                $wzPay->setParameter('sub_openid', $openid);
                $wzPay->setParameter('mch_id', $res['wx_mchid']);
                $wzPay->setParameter('body', '充值');
                $wzPay->setParameter('out_trade_no', $order['order_sn']);
                $wzPay->setParameter('goods_tag', $order['order_sn']);
                $wzPay->setParameter('total_fee', $order['price'] * 100);
                $wzPay->setParameter('notify_url', 'https://sy.youngport.com.cn/index.php?g=api&m=cz&a=wz_notify');
                $returnData = $wzPay->getParameters();
                return $returnData;
                break;
            //招商
            case 4:
                $bank['mch_id'] = $res['wx_mchid'];
                $bank['sub_appid'] = 'wx3fa82ee7deaa4a21';
                $bank['nonce_str'] = time() . rand(10000, 99999) . '251';
                $bank['body'] = '测试下';
                $bank['reqId'] = date("YmdHis") . rand(1000, 9999) . '251';
                $bank['out_trade_no'] = $order['order_sn'];
                $bank['total_fee'] = $order['price'] * 100;
                $bank['spbill_create_ip'] = $_SERVER["REMOTE_ADDR"];
                $bank['mch_pay_key'] = $res['wx_key'];
                $bank['notify_url'] = "http://sy.youngport.com.cn/notify/cz/zs.php";
                $bank['time_start'] = date("YmdHis");
                $bank['trade_type'] = 'JSAPI';
                $bank['sub_openid'] = $openid;
                //  var_dump($bank);
                $res = $this->weixin_c_b_pay($bank);
                //xml 转数据
                $res = $this->xmlToArray($res);

                if ($res['return_code'] == "FAIL") {
                    $this->error($res['return_msg']);
                }
                $result = $res['js_prepay_info'];
                break;
            //围餐
            case 3:
                // 得到输入的金额和商户的ID
                header("Content-type:text/html;charset=utf-8");
                Vendor('WxPayPubHelper.WxPayPubHelper');
                $jsApi = new \JsApi_pub();
                $unifiedOrder = new \UnifiedOrder_pub();
                $unifiedOrder->setParameter("openid", $openid);//openid和sub_openid可以选传其中之一
                //$unifiedOrder->setParameter("sub_openid", "$sub_openid");//子商户appid下的唯一标识
                $unifiedOrder->setParameter("body", '充值');//商品描述
                //自定义订单号，
                $unifiedOrder->setParameter("out_trade_no", $order['order_sn']);//商户订单号
                $unifiedOrder->setParameter("total_fee", $order['price'] * 100);//总金额
                $unifiedOrder->setParameter("notify_url", 'https://sy.youngport.com.cn/notify/cz/wc.php');//通知地址
                $unifiedOrder->setParameter("trade_type", "JSAPI");//交易类型
                $unifiedOrder->setParameter("sub_mch_id", $res['wx_mchid']);//子商户号服务商必填
                $prepay_id = $unifiedOrder->getPrepayId();
                $jsApi->setPrepayId($prepay_id);
                $jsApiParameters = $jsApi->getParameters();
                return $jsApiParameters;
                break;
            //新业银行
            case 7:
                $param['service'] = 'pay.weixin.jspay';
                $param['charset'] = 'UTF-8';
                $param['mch_id'] = $res['wx_mchid'];
                $param['out_trade_no'] = $order['order_sn'];
                $param['body'] = '订单号：' . $order['order_sn'];
                $param['sub_openid'] = $openid;
                $param['sub_appid'] = 'wx3fa82ee7deaa4a21';
                $param['mch_create_ip'] = $_SERVER["REMOTE_ADDR"] ? $_SERVER["REMOTE_ADDR"] : '127.0.0.1';
                $param['is_raw'] = "1";
                $param['total_fee'] = (int)($order['price'] * 100);
                $param['notify_url'] = 'http://sy.youngport.com.cn/notify/cz/xy.php';
                $param['nonce_str'] = date("YmdHis") . rand(1000, 9999) . '251';
                $param['sign'] = $this->getSignVeryfy_pay($param, $res['wx_key']);
                $xmlData = $this->arrayToXml($param);
                $url = "https://pay.swiftpass.cn/pay/gateway";
                $res = $this->httpRequst_pay($url, $xmlData);
                $res = $this->xmlToArray($res);
                return $res['pay_info'];
                break;
                //东莞中信
            case 10:
                $param['service'] = 'pay.weixin.jspay';
                $param['charset'] = 'UTF-8';
//        $param['version'] = (string)$this->version;
                $param['mch_id'] = $res['wx_mchid'];
                $param['out_trade_no'] = $order['order_sn'];
                $param['body'] = $order['order_sn'];
                $param['sub_openid'] = $openid;
                $param['sub_appid'] = 'wx3fa82ee7deaa4a21';
                $param['is_raw'] = "1";
                $param['total_fee'] = (int)($order['price'] * 100);
                $param['mch_create_ip'] = $_SERVER["REMOTE_ADDR"];
                $param['notify_url'] = "https://sy.youngport.com.cn/notify/cz/pf.php";
                $param['nonce_str'] = $this->getNonceStr();
                //签名
                $param['sign'] = $this->getSignVeryfy_pay($param, $res['wx_key']);
                //转换成xml格式post提交数据
                $xmlData = $this->arrayToXml($param);
                $url = "https://pay.swiftpass.cn/pay/gateway";
                $res = $this->httpRequst_pay($url, $xmlData);
                $res = $this->xmlToArray($res);
                return $res['pay_info'];
                break;
                //新大陆
            case 11:
                $merchants_xdl = M('merchants_xdl')->where(array('m_id' => $order['mid']))->find();
                $this->fileName = 'wx_js_pay.log';
                $params['opsys'] = '0';
                $params['characterset'] = '00';
                $params['orgno'] = $merchants_xdl['orgNo'];
                $params['mercid'] = $merchants_xdl['mercId'];
                $params['trmno'] = $merchants_xdl['trmNo'];
                $params['tradeno'] = $order['order_sn'];
                $params['trmtyp'] = 'W';
                $params['txntime'] = date('YmdHis');
                $params['signtype'] = 'MD5';
                $params['version'] = 'V1.0.0';
                $params['amount'] = $order['price']*100;
                $params['total_amount'] = $order['price']*100;
                $params['paychannel'] = 'WXPAY'; //支付宝	ALIPAY 微信	WXPAY 银联	YLPAY
                $order['price']=$order['price']*100;
                $params['paysuccurl'] = "https://sy.youngport.com.cn/Pay/Barcode/weixipay_return000/price/".$order['price']."/sub_openid/{$openid}/remark/{$order['order_sn']}/mid/{$order[mid]}";
                //dump($params);
                $this->signKey = $merchants_xdl['signKey'];
                //dump($this->signKey);
                $params['signvalue'] = $this->getSign($params);
                $this->writlog('cz_JS_wx_pay.log','key:'.$this->signKey.  ',payParams：' . json_encode($params));
                /*if($params['amount'] == 2){
                    exit("金额过低");
                }*/
                $params['bank']=11;
                $params['signKey'] = $this->signKey;
                return $params;
                break;
            case 12:
                $merchants_leshua = M('merchants_leshua')->where(array('m_id' => $order['mid']))->find();
                $param['service'] = 'get_tdcode';
                $param['pay_way'] = 'WXZF';
                $param['merchant_id'] = $merchants_leshua['merchantId'];//商户号
                $param['third_order_id'] = $order['order_sn'];//商户订单号
                $param['amount'] = ($order['price'] * 100);//金额
                $param['jspay_flag'] = 1;
                $param['sub_openid'] = $openid;
                $param['client_ip'] = $merchants_leshua['ip_address'] ?: $_SERVER['REMOTE_ADDR'];
                $param['notify_url'] = "https://sy.youngport.com.cn/notify/cz/ls.php";//回调地址
                $param['t0'] = $merchants_leshua['is_t0'];
                $param['body'] = '充值';
                $param['nonce_str'] = $this->getNonceStr();//随机字符串
                $param['sign'] = $this->getSignVeryfy_pay($param, $merchants_leshua['key']);//签名
                $url = "https://mobilepos.yeahka.com/cgi-bin/lepos_pay_gateway.cgi";
                $res = $this->httpRequst_pay($url, $param);
                $res_arr = $this->xmlToArray($res);

                return $res_arr['jspay_info'];
                break;
            default:
                $this->err('不存在该支付方式');
                break;
        }
        //微众银行
        return $result;
    }

    public function xdl_wxpay()
    {
        $params = I('');
        $mid = M('merchants_cate c')->join('ypt_user_recharge u on u.cate_id=c.id')->where(array('u.order_sn'=>$params['tradeno']))->getField('c.merchant_id');
        $params['paysuccurl'] = "https://sy.youngport.com.cn/Pay/Barcode/weixipay_return000/price/".$params['amount']."/sub_openid/".$params['openid']."/remark/".$params['tradeno']."/mid/".$mid;
        $this->assign('url','https://gateway.starpos.com.cn/sysmng/bhpspos4/5533020.do');
        unset($params['openid']);
        //unset($params['signKey']);
        $this->assign('data',$params);
        $this->display('wxpay');
    }

    private function getSign($params)
    {
        ksort($params);
        $str = '';
        foreach ($params as $v) {
            $str .= $v;
        }
        return md5($str . $this->signKey);
    }

    //支付接口统一签名
    private function getSignVeryfy($para_temp)
    {
        //除去待签名参数数组中的空值和签名参数
        $para_filter = $this->paraFilter($para_temp);
        //对待签名参数数组排序
        $para_sort = $this->argSort($para_filter);

        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $this->createLinkstring($para_sort);
        //拼接apikey
        $prestr = $prestr . "&key=" . $this->apikey;
        //MD5 转大写
        $prestr = strtoupper(md5($prestr));
        return $prestr;
    }

    function ceshi()
    {
//        echo $this->get_openid();
        $ab = $this->create_sign_agent(730, 'oyaFdwBXWgEckdh3rL-L6pS12ZFk');
    }

    public function create_sign_agent($order_id, $openid)
    {
        $order_id || $this->err('order_id is empty');
        ($order = M('user_recharge')->where(array('id' => $order_id))->find()) || $this->err('quick_pay is empty');
        $agent_id = M("merchants_agent")->where(array('uid' => $order['mid']))->getField("id"); //代理id
        ($res = M('merchants_agentbank')->where(array('agent_id' => $agent_id, 'status' => 1))->find()) || $this->err('代理未进件');
        //招商银行
        if ($res['bank_style'] == 3) {
            $bank['mch_id'] = $res['wx_mchid'];
            $bank['sub_appid'] = 'wx3fa82ee7deaa4a21';
            $bank['nonce_str'] = time() . rand(10000, 99999) . '251';
            $bank['body'] = '测试下';
            $bank['reqId'] = date("YmdHis") . rand(1000, 9999) . '251';
            $bank['out_trade_no'] = $order['order_sn'];
            $bank['total_fee'] = $order['price'] * 100;
            $bank['spbill_create_ip'] = $_SERVER["REMOTE_ADDR"];
            $bank['mch_pay_key'] = $res['wx_key'];
            $bank['notify_url'] = "http://sy.youngport.com.cn/notify/cz/zs.php";
            $bank['time_start'] = date("YmdHis");
            $bank['trade_type'] = 'JSAPI';
            $bank['sub_openid'] = $openid;
            get_date_dir($this->path,'charge','create_sign_agent请求参数',json_encode($bank));
            $res = $this->weixin_c_b_pay($bank);
            //xml 转数据
            $res = $this->xmlToArray($res);
            get_date_dir($this->path,'charge','create_sign_agent返回参数',json_encode($res));
            if ($res['return_code'] == "FAIL") {
                $this->error($res['return_msg']);
            }
            return $res['js_prepay_info'];
        }
        //微信围餐
        if ($res['bank_style'] == 7) {
            header("Content-type:text/html;charset=utf-8");
            Vendor('WxPayPubHelper.WxPayPubHelper');
            $jsApi = new \JsApi_pub();
            $unifiedOrder = new \UnifiedOrder_pub();
            $unifiedOrder->setParameter("openid", $openid);//openid和sub_openid可以选传其中之一
            //$unifiedOrder->setParameter("sub_openid", "$sub_openid");//子商户appid下的唯一标识
            $unifiedOrder->setParameter("body", '充值');//商品描述
            //自定义订单号，
            $unifiedOrder->setParameter("out_trade_no", $order['order_sn']);//商户订单号
            $unifiedOrder->setParameter("total_fee", $order['price'] * 100);//总金额
            $unifiedOrder->setParameter("notify_url", 'https://sy.youngport.com.cn/notify/cz/wc.php');//通知地址
            $unifiedOrder->setParameter("trade_type", "JSAPI");//交易类型
            $unifiedOrder->setParameter("sub_mch_id", $res['wx_mchid']);//子商户号服务商必填
            $prepay_id = $unifiedOrder->getPrepayId();
            $jsApi->setPrepayId($prepay_id);
            $jsApiParameters = $jsApi->getParameters();
            return $jsApiParameters;
        }
        //东莞中信
        if ($res['bank_style'] == 10) {
            //接口类型
            $param['service'] = 'pay.weixin.jspay';
            //字符集
            $param['charset'] = 'UTF-8';
            //版本号
//        $param['version'] = (string)$this->version;
            //商户号
            $param['mch_id'] = $res['wx_mchid'];
            //商户订单号
            $param['out_trade_no'] = $order['order_sn'];
            //商品描述
            $param['body'] = $order['order_sn'];
            //用户openid
            $param['sub_openid'] = $openid;
            //公众账号或小程序ID
            $param['sub_appid'] = 'wx3fa82ee7deaa4a21';
            //是否原生态
            $param['is_raw'] = "1";
            //金额
            $param['total_fee'] = (int)($order['price'] * 100);
            //ip
            $param['mch_create_ip'] = $_SERVER["REMOTE_ADDR"];
            //回调地址
            $param['notify_url'] = "https://sy.youngport.com.cn/notify/cz/pf.php";
            //订单生成时间
//        $param['time_start'] = (string)date("YmdHis");
            //订单超时时间
//        $param['time_expire'] = (string)date("YmdHis", time() + 60);
            //随机字符串
            $param['nonce_str'] = $this->getNonceStr();
            //签名
            $param['sign'] = $this->getSignVeryfy_pay($param, $res['wx_key']);
            //转换成xml格式post提交数据
            $xmlData = $this->arrayToXml($param);
            $url = "https://pay.swiftpass.cn/pay/gateway";
            $res = $this->httpRequst_pay($url, $xmlData);
            $res = $this->xmlToArray($res);
            return $res['pay_info'];
        }
    }

    public function getNonceStr()
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < 32; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }

        return strtoupper($str);
    }



    /**
     * 获取jssdk需要用到的数据
     * @return array jssdk需要用到的数据
     */
    public function get_openid()
    {
        $config['APPID'] = 'wx3fa82ee7deaa4a21';
        //$config['APPSECRET'] = '6b6a7b6994c220b5d2484e7735c0605a';
        $config['APPSECRET'] = '3fa1c129be3bcbca0dcda28465d361a1';
        $redirect_uri = 'https://' . $_SERVER['HTTP_HOST'] . $_SESSION['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $redirect_uri = urlencode($redirect_uri);
        $url = "http://sy.youngport.com.cn/redirect/get-weixin-code.html?appid=" . $config['APPID'] . "&scope=snsapi_base&state=hello-world&redirect_uri=" . $redirect_uri;
        // 如果没有get参数没有code；则重定向去获取openid；
        if (!isset($_GET['code'])) {
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


    function getClientIP()
    {
        global $ip;
        if (getenv("HTTP_CLIENT_IP"))
            $ip = getenv("HTTP_CLIENT_IP");
        else if (getenv("HTTP_X_FORWARDED_FOR"))
            $ip = getenv("HTTP_X_FORWARDED_FOR");
        else if (getenv("REMOTE_ADDR"))
            $ip = getenv("REMOTE_ADDR");
        else $ip = "Unknow";
        return $ip;
    }

    //微信支付用户扫商家接口
    private function weixin_c_b_pay($data)
    {
        $param['mch_id'] = $data['mch_id'];//商户号，由UCHANG分配
        //否
        if (isset($data['sub_appid']) && !empty($data['sub_appid'])) {
            $param['sub_appid'] = $data['sub_appid'];//商户微信公众号appid,app支付时,为在微信开放平台上申请的APPID
        }
        //否
        if (isset($data['device_info']) && !empty($data['device_info'])) {
            $param['device_info'] = $data['device_info'];//终端设备号(门店号或收银设备ID)，注意：PC网页或公众号内支付请传“WEB”
        }
        //是
        $param['nonce_str'] = $data['nonce_str'];//随机字符串，不长于32位
        //是
        $param['body'] = $data['body'];//商品描述
        //否
        if (isset($data['detail']) && !empty($data['detail'])) {
            $param['detail'] = $data['detail'];//商品详细列表，使用Json格式，传输签名前请务必使用CDATA标签将JSON文本串保护起来。goods_detail 服务商必填 []：└ goods_id String 必填 32 商品的编号└ wxpay_goods_id String 可选 32 微信支付定义的统一商品编号└ goods_name String 必填 256 商品名称└ quantity Int 必填 商品数量└ price Int 必填 商品单价，单位为分└ goods_category String 可选 32 商品类目ID└ body String 可选 1000 商品描述信息
        }
        //否
        if (isset($data['attach']) && !empty($data['attach'])) {
            $param['attach'] = $data['attach'];//附加数据，在查询API和支付通知中原样返回，该字段主要用于商户携带订单的自定义数据
        }
        //是
        $param['out_trade_no'] = $data['out_trade_no'];//商户系统内部的订单号,32个字符内、可包含字母, 其他说明见商户订单号
        //是
        $param['fee_type'] = "CNY";//符合ISO 4217标准的三位字母代码，默认人民币：CNY
        //是
        $param['total_fee'] = $data['total_fee'];//总金额，以分为单位，不允许包含任何字、符号
        //是
        $param['spbill_create_ip'] = $data['spbill_create_ip'];//APP和网页支付提交用户端ip，Native支付填调用微信支付API的机器IP。
        //是
        // $param['time_start']=date("YmdHis");//订单生成时间，格式为yyyyMMddHHmmss，如2009年12月25日9点10分10秒表示为20091225091010
        // //是
        // $param['time_expire']=date("YmdHis");//如上
        //否
        if (isset($data['goods_tag']) && !empty($data['goods_tag'])) {
            $param['goods_tag'] = $data['goods_tag'];//商品标记，代金券或立减优惠功能的参数
        }
        //是
        $param['notify_url'] = $data['notify_url'];//接收微信支付异步通知回调地址，通知url必须为直接可访问的url，不能携带参数。
        //是
        $param['trade_type'] = $data['trade_type'];//取值如下：JSAPI，NATIVE，APP
        //否
        if (isset($data['product_id']) && !empty($data['product_id'])) {
            $param['product_id'] = $data['product_id'];//trade_type=NATIVE，此参数必传。此id为二维码中包含的商品ID，商户自行定义。
        }
        //否
        if (isset($data['limit_pay']) && !empty($data['limit_pay'])) {
            $param['limit_pay'] = $data['limit_pay'];//no_credit–指定不能使用信用卡支付
        }
        //否
        if (isset($data['sub_openid']) && !empty($data['sub_openid'])) {
            $param['sub_openid'] = $data['sub_openid'];//trade_type=JSAPI，此参数必传，用户在子商户appid下的唯一标识。openid和sub_openid可以选传其中之一，如果选择传sub_openid,则必须传sub_appid。

        }

        if (isset($data['wxapp']) && !empty($data['wxapp'])) {
            $param['wxapp'] = $data['wxapp'];//true–小程序支付；此字段控制 js_prepay_info 的生成，为true时js_prepay_info返回小程序支付参数，否则返回公众号支付参数
        }
        //获取签名

        $param['sign'] = $this->getSignVeryfy_pay($param, $data['mch_pay_key']);
        //转换成xml格式post提交数据
        $xmlData = $this->arrayToXml($param);
        $url = "http://api.cmbxm.mbcloud.com/wechat/orders";
        $result = $this->httpRequst_pay($url, $xmlData);
        return $result;
    }

    //数组转xml
    private function arrayToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }

    //xml转数组
    private function xmlToArray($xml)
    {
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $values;
    }

    //支付接口统一签名
    private function getSignVeryfy_pay($para_temp, $paykey)
    {
        //除去待签名参数数组中的空值和签名参数
        $para_filter = $this->paraFilter($para_temp);
        //对待签名参数数组排序
        $para_sort = $this->argSort($para_filter);
        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $this->createLinkstring($para_sort);
        //拼接apikey
        $prestr = $prestr . "&key=" . $paykey;
        //MD5 转大写
        $prestr = strtoupper(md5($prestr));
        return $prestr;
    }

    private function paraFilter($para)
    {
        $para_filter = array();
        while (list ($key, $val) = each($para)) {
            if ($key == "sign" || $key == "sign_type" || $val == "") continue;
            else    $para_filter[$key] = $para[$key];
        }
        return $para_filter;
    }

    //数组排序
    private function argSort($para)
    {
        ksort($para);
        reset($para);
        return $para;
    }

    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     * @param $para 需要拼接的数组
     * return 拼接完成以后的字符串
     */
    private function createLinkstring($para)
    {
        $arg = "";
        while (list ($key, $val) = each($para)) {
            $arg .= $key . "=" . $val . "&";
        }
        //去掉最后一个&字符
        $arg = substr($arg, 0, count($arg) - 2);

        //如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }

        return $arg;
    }

    private function cost_rate_1($wx_mchid, $paytype)
    {
        if ($paytype == 1) {
            $re = M('merchants_mpay')->where(array('wechat' => $wx_mchid))->find();
            return '0.' . $re['weicodefen'];
        } elseif ($paytype == 2) {
            $re = M('merchants_mpay')->where(array('alipay' => $wx_mchid))->find();
            return '0.' . $re['alipaycodefen'];
        }
    }

    private function cost_rate_2($bank, $paytype, $mid)
    {
        switch ($bank) {
            case 1:
                $paytype == 1 ? $rate = 'wxCostRate' : $rate = 'aliCostRate';
                return M('merchants_upwz')->where(array('mid' => $mid))->getField($rate);
                break;
            case 3:
                return M('merchants_upwx')->where(array('mid' => $mid))->getField('cost_rate');
                break;
            case 4:
                $paytype == 1 ? $rate = 'payment_type3' : $rate = 'payment_type9';
                return '0.' . M('merchants_zspay')->where(array('merchant_id' => $mid))->getField($rate);
                break;
            case 7:
                $paytype == 1 ? $rate = 'wx_code' : $rate = 'ali_code';
                return M('merchants_xypay')->where(array('merchant_id' => $mid))->getField($rate);
                break;
            case 10:
                $paytype == 1 ? $rate = 'wx_code' : $rate = 'ali_code';
                return M('merchants_pfpay')->where(array('merchant_id' => $mid))->getField($rate);
                break;
            case 11:
                $paytype == 1 ? $rate = 'wx_rate' : $rate = 'ali_rate';
                return M('merchants_xdl')->where(array('m_id' => $mid))->getField($rate);
                break;
			case 12:
                $paytype == 1 ? $rate = 'wx_rate' : $rate = 'ali_rate';
				$into_data = M('merchants_leshua')->where(array('m_id' => $mid))->find();
                return $into_data['is_t0'] ? $into_data['wx_t0_rate'] : $into_data['wx_t1_rate'];
                break;
        }
    }

    public function ms_notify()
    {

        //验签
        $this->add_log();
        //	$post = M('log')->where(array('id'=>'18092'))->getField('post');
//
        //		$post = json_decode($post,true);
        $post = $_POST;
        $data = json_decode($post['body'], true);

        //初步代表验证通过
        if (substr($data['reqId'], strlen($data['reqId']) - 3, 3) == '251') {
            $this->common($data['orderId'], $data['totalAmount'] / 100, $data['transId'], 2);
        }
    }

    public function xy_notify()
    {
        $data = $this->xmlToArray(file_get_contents('php://input', 'r'));
        $this->add_log(json_encode($data));
        if ($data['status'] == 0) {
            $this->common($data['out_trade_no'], $data['total_fee'] / 100, $data['transaction_id'], 7);
        }
    }

    public function wc_notify()
    {
        Vendor('WxPayPubHelper.WxPayPubHelper');
        //使用通用通知接口
        $notify = new \Notify_pub();
        //存储微信的回调
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        $this->add_log($xml);
        $notify->saveData($xml);
        if ($notify->checkSign() == FALSE) {
            $return = array('return_code' => "FAIL", 'return_msg' => "签名失败");
            get_date_dir($this->path,'charge','快速买单签名失败',$xml);
        } else {
            $data = $notify->data;
            $out_trade_no = $data["out_trade_no"];//回调的订单号
            if ($data['return_code'] == 'SUCCESS' && $data['result_code'] == 'SUCCESS') {
                get_date_dir($this->path,'pay_notify','快速买单支付成功', json_encode($data));
                // 读取订单信息
                $this->common($out_trade_no, $data['total_fee'] / 100, $data['transaction_id'], 3);
            } else {
                //A("Pay/Barcode")->push_pay_message($out_trade_no);
                get_date_dir($this->path,'pay_notify','快速买单重复回调或不存在', json_encode($data));
                $return = array('return_code' => "FAIL");
            }
        }
        $returnXml = $notify->returnNotifyXml($return);
        echo $returnXml;
    }

    public function wz_notify()
    {
        header("Content-type:text/html;charset=utf-8");
        vendor('Wzpay.Wzczpay');
        $wzPay = new \Wzczpay();
        // 获取json
        $json_str = file_get_contents('php://input', 'r');
        $this->add_log($json_str);
        // 转成php数组
        $data = json_decode($json_str, true);
        // 保存原sign
        $data_sign = $data['sign'];
        //获取用户key
        $wzPay->key = M('merchants_cate')->where(array('wx_mchid' => $data['mch_id']))->getField('wx_key');
        // sign不参与签名
        unset($data['sign']);
        $sign = $wzPay->getSign($data);
        $this->add_log($sign);
        // 判断签名是否正确  判断支付状态
        if ($sign === $data_sign && $data['status'] === '0' && $data['result_code'] === '0') {
            $this->common($data['out_trade_no'], $data['total_fee'] / 100, $data['transaction_id'], 1);
        } else {
            $this->add_log($sign);
        }

    }

    public function zs_notify()
    {

        $this->add_log(file_get_contents('php://input', 'r'));
        $this->add_log(json_encode($this->xmlToArray(file_get_contents('php://input', 'r'))));
        $data = $this->xmlToArray(file_get_contents('php://input', 'r'));
        //暂时没有验证签名
        $this->common($data['out_trade_no'], $data['cash_fee'] / 100, $data['transaction_id'], 4);
        //初步代表验证通过
//				if(substr($data['reqId'],strlen($data['reqId'])-3,3) == '251'){
//
//
//				}
//				$json_str = file_get_contents('php://input', 'r');
//	        	$data=$this->xmlToArray($json_str);
    }
    public function pf_notify()
    {
        $data = $this->xmlToArray(file_get_contents('php://input', 'r'));
        $this->add_log(json_encode($data));
        if ($data['status'] == 0) {
            $this->common($data['out_trade_no'], $data['total_fee'] / 100, $data['transaction_id'], 10);
        }
    }
    public function xdl_notify()
    {
        $this->add_log(file_get_contents('php://input', 'r'));
        $this->add_log(json_encode($this->xmlToArray(file_get_contents('php://input', 'r'))));
        $data = $this->xmlToArray(file_get_contents('php://input', 'r'));
        //暂时没有验证签名
        if(isset($data['TxnStatus']) && $data['TxnStatus'] == '1'){
            $this->common($data['TxnLogId'], $data['TxnAmt'] , $data['OfficeId'], 11);
        }
    }
	
    public function ls_notify()
    {
        $this->add_log(file_get_contents('php://input', 'r'));
        $this->add_log(json_encode($this->xmlToArray(file_get_contents('php://input', 'r'))));
        $data = $this->xmlToArray(file_get_contents('php://input', 'r'));
        //暂时没有验证签名
        if($data['error_code'] == '0' && $data['status'] == '2'){
            $this->common($data['third_order_id'], $data['amount'] / 100 , $data['leshua_order_id'], 12);
        }
    }

    //支付成功而且验证成功
    public function common($order_sn, $price, $transid, $bank)
    {
        $this->add_log($order_sn, $price, $transid, $bank);
        if (!($order_sn && $price && $transid && $bank)) {
            return false;
        }
        get_date_dir($this->path,'charge','common流水号',$order_sn);
        $user_recharge = M('user_recharge');
        ($order = $user_recharge->where(array('order_sn' => $order_sn))->find()) || $this->err('quick_buy is not find');
        if ($order['status'] != 0) {
            $this->err('该订单已经支付');
        }
        $time = time();
        //开启事务
        //M()->startTrans();
        //更新订单状态
        $user_recharge->where(array('id' => $order['id']))->save(array('status' => 1, 'update_time' => $time, 'real_price' => $price, 'transId' => $transid));
        //记录余额
        $order['memcard_id'] || $this->err('memcard_id is empty');
        ($screen_memcard_use = M('screen_memcard_use')->where(array('id' => $order['memcard_id']))->find()) || $this->err('会员卡不存在');
        //查看会员卡
        $screen_memcard = M('screen_memcard')->where(array('id' => $screen_memcard_use['memcard_id']))->find();
        $screen_cardset = M('screen_cardset')->where(array('c_id' => $screen_memcard_use['memcard_id']))->find();
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
                //记录日志
                M('screen_memcard_log')->add(array('add_time' => $time, 'update_time' => $time, 'value' => $integral, 'balance' => $save['card_balance'], 'ts' => json_encode($ts), 'order_sn' => $order_sn, 'code' => $screen_memcard_use['card_code'],'record_bonus'=>'充值送积分'));
            }
            unset($ts);
        }

        $save['yue'] = $screen_memcard_use['yue'] + $order['total_price'];

        //开始更新余额
        M('screen_memcard_use')->where(array('id' => $order['memcard_id']))->save($save);
        //开始记录余额日志
        $yue['add_time'] = $time;
        $yue['value'] = $order['total_price'];
        $yue['remark'] = '充值' . $price;
        $yue['uid'] = $order['uid'];
        $yue['yue'] = $screen_memcard_use['yue'] + $order['total_price'];
        if($screen_memcard_use['card_code']){
            $ts['custom_field_value1'] = urldecode((string)($screen_memcard_use['yue'] + $order['total_price']));
            $ts['code'] = $screen_memcard_use['card_code'];
            $ts['card_id'] = $screen_memcard_use['card_id'];
            $ts = json_encode($ts);
            $yue['ts'] = $ts;
            M('user_yue_log')->add($yue);
        }
        //记录流水
        if (!$this->pay_model->where(array('order_id' => $order['id'], 'mode' => 12))->find()) {
            $pay['merchant_id'] = $order['mid'];
            //查询openid
            $pay['customer_id'] = $order['uid'];
            $pay['paystyle_id'] = 1;
            $pay['order_id'] = $order['id'];
            $pay['mode'] = 12;
            $pay['price'] = $price;
            $pay['remark'] = $order_sn;
            $pay['add_time'] = $order['add_time'];
            $pay['paytime'] = time();
            $pay['bill_date'] = date('Ymd');
            $pay['new_order_sn'] = $order_sn;
            $pay['transId'] = $transid;
            $pay['cate_id'] = $order['cate_id'];
            $this->add_log($bank . '  ' . $order['mid']);
            //$pay['cost_rate'] = get_rate($bank, $order['mid']);
            $pay['status'] = 1;
            $pay['bank'] = $bank;
            $pay['cost_rate'] = $this->cost_rate_2($bank, 1, $order['mid']);
            $this->pay_model->add($pay);
        }
        //M()->commit();
        get_date_dir($this->path,'charge','common_order',json_encode($order));
        //开启推送
        # 充值会员卡成功，需要给消费者微信推送消息
        if($screen_memcard_use['fromname']){
            A('Wechat/Message')->recharge($screen_memcard_use['fromname'],$order['price'],$order['send_price'],$screen_memcard['merchant_name'],$yue['yue']);
        }

        if($screen_memcard_use['card_code']){
            $this->ts();
            $this->mem_card1($order['memcard_id']);
        }
    }

    public function ts()
    {

        $token = get_weixin_token();
        //余额推送
        $yue_log = M('user_yue_log')->where(array('ts_status' => 0,'ts'=>array('neq','')))->select();
        foreach ($yue_log as $v) {
            get_date_dir($this->path,'charge','cz_ts推送参数',$v['ts']);
            $msg = request_post('https://api.weixin.qq.com/card/membercard/updateuser?access_token=' . $token, urldecode($v['ts']));

            $data['ts_msg'] = $msg;
            $msg = json_decode($msg, true);
            get_date_dir($this->path,'charge','cz_ts推送结果',json_encode($msg));
            if ($msg['errcode'] == 0) {
                $data['ts_status'] = 1;
            } else {
                $data['ts_status'] = 0;
            }
            M('user_yue_log')->where(array('id' => $v['id']))->save($data);
        }
        //积分推送
        $memcard_log = M('screen_memcard_log')->where(array('ts_status' => 0,'ts'=>array('neq','')))->select();

        foreach ($memcard_log as $v) {
            $msg = request_post('https://api.weixin.qq.com/card/membercard/updateuser?access_token=' . $token, urldecode($v['ts']));
            $data['msg'] = $msg;
            $msg = json_decode($msg, true);
            if ($msg['errcode'] == 0) {
                $data['ts_status'] = 1;
            } else {
                $data['ts_status'] = 0;
            }
            M('screen_memcard_log')->where(array('id' => $v['id']))->save($data);
        }
    }

    public function get_rate()
    {
        echo request_post('http://sy.youngport.com.cn/index.php?s=api/base/ts', '');

    }

    //会员卡等级1.6.0
    public function mem_card1($card_id)
    {
        if (empty($card_id)) {
            return false;
        }
        $screen_memcard_use = M('screen_memcard_use')->where(array('id' => $card_id))->find();
        $screen_memcard = M('screen_memcard')->where(array('id' => $screen_memcard_use['memcard_id']))->find();
        get_date_dir($this->path,'charge','mem_card1_screen_memcard',json_encode($screen_memcard));
        if ($screen_memcard['level_set'] == 0) {
            $value = $screen_memcard['discount'];
        } elseif ($screen_memcard['level_set']==1 && $screen_memcard['level_up']==1) {
            #消费和积分信息，expense累计消费，expense_single单次消费最大金额，card_amount累计积分
            $field = 'ifnull(sum(order_amount),0) as expense,ifnull(max(order_amount),0) as expense_single';
            $mem_info = M('order')->where(array('card_code'=>$screen_memcard_use['card_code'],'order_status'=>'5'))->field($field)->find();
            $mem_info['card_amount'] = $screen_memcard_use['card_amount'];
            #充值记录信息，recharge累计充值金额，recharge_single单次充值最大金额
            $recharge_info = M('user_recharge')
                ->where(array('memcard_id'=>$screen_memcard_use['id'],'uid'=>$screen_memcard_use['memid'],'status'=>1))
                ->field('ifnull(sum(real_price),0) as recharge,ifnull(max(real_price),0) as recharge_single')
                ->find();
            $mem_info = array_merge($mem_info,$recharge_info);
            $memcard_level = M('screen_memcard_level')->where('c_id =' . $screen_memcard['id'])->order('level asc')->select();
            foreach($memcard_level as &$value){
                $type = explode(',',$value['level_up_type']);
                foreach($type as &$val){
                    #会员当前等级信息,current_level当前等级,current_level_name当前等级名称
                    $level = $this->get_level($val,$mem_info,$value);
                    if($level){
                        $current_level = $level['current_level'];
                        $current_level_name = $level['current_level_name'];
                        break;
                    }
                }
            }
            //开始更新会员卡等级
            if($current_level && $current_level>$screen_memcard_use['level']){
                M('screen_memcard_use')->where(array('id' => $card_id))->setField('level', $current_level);
                $value = $current_level_name;
                //开始推送等级
                if($value) {
                    $card_ts['code'] = $screen_memcard_use['card_code'];
                    $card_ts['card_id'] = $screen_memcard_use['card_id'];
                    $card_ts['custom_field_value2'] = urlencode($value);
                    get_date_dir($this->path,'charge','mem_card1_ts',json_encode($card_ts));
                    $token = get_weixin_token();
                    $msg = request_post('https://api.weixin.qq.com/card/membercard/updateuser?access_token=' . $token, urldecode(json_encode($card_ts)));
                    get_date_dir($this->path,'charge','mem_card1_msg',$msg);
                }
            }
        }

    }

    //获取会员当前等级信息
    private function get_level($type,$up_info,$level_info)
    {
        switch ($type) {
            case 1:
                if($up_info['recharge_single'] >= $level_info['level_recharge_single']){
                    $level['current_level'] = $level_info['level'];
                    $level['current_level_name'] = $level_info['level_name'];
                    return $level;
                }
                break;
            case 2:
                if($up_info['recharge'] >= $level_info['level_recharge']){
                    $level['current_level'] = $level_info['level'];
                    $level['current_level_name'] = $level_info['level_name'];
                    return $level;
                }
                break;
            case 3:
                if($up_info['expense_single'] >= $level_info['level_expense_single']){
                    $level['current_level'] = $level_info['level'];
                    $level['current_level_name'] = $level_info['level_name'];
                    return $level;
                }
                break;
            case 4:
                if($up_info['expense'] >= $level_info['level_expense']){
                    $level['current_level'] = $level_info['level'];
                    $level['current_level_name'] = $level_info['level_name'];
                    return $level;
                }
                break;
            case 5:
                if($up_info['card_amount'] >= $level_info['level_integral']){
                    $level['current_level'] = $level_info['level'];
                    $level['current_level_name'] = $level_info['level_name'];
                    return $level;
                }
                break;
            default:
                if($level_info['level']==1){
                    $level['current_level'] = $level_info['level'];
                    $level['current_level_name'] = $level_info['level_name'];
                }else{
                    $level = null;
                }
                return $level;
        }
    }
    //会员卡等级 1.6.0之前版本，mem_card1稳定后可删除
    public function mem_card($card_id)
    {
        get_date_dir($this->path,'charge','mem_card_card_id',$card_id);
        if (empty($card_id)) {
            return false;
        }
        $screen_memcard_use = M('screen_memcard_use')->where(array('id' => $card_id))->find();
        $screen_memcard = M('screen_memcard')->where(array('id' => $screen_memcard_use['memcard_id']))->field('id,integral_dikou,credits_use,level_set,credits_discount,mid,discount,discount_set,mid')->find();

        //根据积分来判断
        if ($screen_memcard['discount_set'] != 1) {
            return false;
        }


        if ($screen_memcard['level_set'] == 0) {
            $value = $screen_memcard['discount'];

        } else {
            //var_dump($screen_memcard['id']);
            //查询流水
            $mid = M('merchants')->where(array('uid' => $screen_memcard['mid']))->getField('id');

            //根据积分查询
            $memcard_level = M('screen_memcard_level')->where('c_id =' . $screen_memcard['id'] . ' and level_integral <= ' . $screen_memcard_use['card_amount'])->field('level,level_name')->order('level desc')->find();

            //开始更新会员卡等级
            M('screen_memcard_use')->where(array('card_id' => $card_id))->setField('level', $memcard_level['level']);
            //开始推送等级
            $value = $memcard_level['level_name'];
        }
        $card_ts['code'] = $screen_memcard_use['card_code'];
        $card_ts['card_id'] = $screen_memcard_use['card_id'];
        $card_ts['custom_field_value2'] = urlencode($value);

        $token = get_weixin_token();
        $msg = request_post('https://api.weixin.qq.com/card/membercard/updateuser?access_token=' . $token, urldecode(json_encode($card_ts)));
        get_date_dir($this->path,'charge','mem_card_msg',json_encode($msg));
    }

    //开启快速购买
    public function open_quick_buy()
    {
        //$token = get_weixin_token();
        $token = '1pH2kBv9vBh-ECmK1AQGjMn0yDpG-a5vrpqd4s7yUhD4_t2a8pPecmmBGzdJJGztOReYC4na2yJBdEhJ1x7GWEm0MEsCJZPInOuE4bErLMlXatQ1HNdIte3Zbxhj4FuRAQFjAJAUKZ';
        $url = 'https://api.weixin.qq.com/card/paycell/set?access_token=' . $token;
        $data['card_id'] = 'pyaFdwKGX4LNil-Bcv5kbGByX3Ig';
        $data['is_open'] = true;
        $r = request_post($url, json_encode($data));
        p($r);
    }

    //创建门店
    public function create_location()
    {
        $token = get_weixin_token();
        $url = 'http://api.weixin.qq.com/cgi-bin/poi/addpoi?access_token=' . $token;
        $info['sid'] = '33788392';
        $info['business_name'] = '汪氏毛椒火辣';
        $info['branch_name'] = '西乡店';
        $info['province'] = '广东省';
        $info['city'] = '深圳市';
        $info['district'] = '宝安区';
        $info['address'] = '西乡街道共乐社区盐田1栋商业楼2-3楼';
        $info['telephone'] = '18823404165';
        $info['categories'] = array("美食,小吃快餐");
        $info['offset_type'] = '1';
        $info['longitude'] = '116.41637';
        $info['latitude'] = '39.92855';
        $data['business']['base_info'] = $info;
        $json = '{"business":{
									"base_info":{
										   "sid":"33788392",
										   "business_name":"汪氏毛椒火辣",
										   "branch_name":"西乡店",
										   "province":"广东省",
										   "city":"深圳市",
										   "district":"宝安区",
										   "address":"西乡街道共乐社区盐田1栋商业楼2-3楼",
										   "telephone":"18823404165",
										   "categories":["美食,小吃快餐"], 
										   "offset_type":1,
										   "longitude":113.863210,
										   "latitude":22.581540
							}}
							}';
        echo json_encode($data);
        $r = request_post($url, $json);
        p($r);
    }

    public function add_log($param = '')
    {
        $data['action'] = MODULE_NAME . '/' . CONTROLLER_NAME . '/' . ACTION_NAME;
        $data['add_time'] = date('Y-m-d H:i:s');
        $data['get'] = json_encode(I('get.'));
        $data['post'] = json_encode($_POST);
        $data['param'] = $param;
        M('log')->add($data);
    }

    //支付接口 curl
    private function httpRequst_pay($url, $post_data)
    {
        //初始化
        $curl = curl_init();
        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //设置post方式提交
        curl_setopt($curl, CURLOPT_POST, 1);
        //设置post数据
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
        //执行命令
        $data = curl_exec($curl);

        curl_close($curl);
        return $data;
        //显示获得的数据
    }

    public function curl_post($url, $data)
    {
        $ch = curl_init();
        $headers[] = "Accept-Charset: utf-8";
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    public function err($msg = '', $code = 404)
    {
        header("Content-type: text/json");
        $array = array();
        $array['code'] = $code;
        $array['msg'] = $msg;
        echo json_encode($array);
        exit;
    }

    public function succ($data = array(), $msg = 'SUCC')
    {
        $array = array();
        $array['code'] = 0;
        $array['msg'] = $msg;
        $array['data'] = $data;
        $nums = func_num_args();
        $nums > 2 && $array = array_merge($array, func_get_arg(2));
        header("Content-type: text/json");
        echo json_encode($array);
        exit;
    }

    public function isset_pass()
    {
        if (IS_POST) {
            $open_id = I('openid');
            $card_id = I('card_id');
            $screen_memcard = M('screen_memcard')->where(array('card_id' => $card_id))->field('id,mid')->find();
            $info = M('screen_memcard_use')->where(array('memcard_id' => $screen_memcard['id'], 'fromname' => $open_id))->find();
            if (empty($info['pay_pass'])) {
                $this->err('未设置支付密码', 1);
            }
            //M('screen_memcard_cdk')->where(array('uid' => $screen_memcard['mid']))->find() || $this->err('该卡暂无充值码!', 2);
            $this->succ($info['card_code']);
        }
    }

    public function entity_code_recharge()
    {
        if (IS_POST) {
            $card_code = I('card_code');//会员卡号
            $code = I('code');//充值码
            get_date_dir($this->path,'code_recharge','充值码充值参数',json_encode($_POST));

            # 查询是否存在充值码
            $where['code'] = $code;
            $where['status'] = 1;
            $where['is_delete'] = 0;
            $where['is_use'] = 1;
            $where['start_time'] = array('ELT', time());
            $where['end_time'] = array('EGT', time());
            ($info = M('screen_memcard_cdk')->where($where)->find()) || $this->err('充值码错误!', 1);

            # 查询卡信息及会员信息
            $card = M("screen_memcard")
                ->field("c.mid,c.card_id,c.id,u.card_code,u.card_amount,u.yue,u.id as memid")
                ->join("c left join ypt_screen_memcard_use u on c.id=u.memcard_id")
                ->where(array('entity_card_code|card_code' => $card_code))
                ->find();
            $yue = $card['yue'] * 100;
            $price = $info['price'] * 100;

            $new_yue = ($price + $yue) / 100;
            M()->startTrans();
            // 增加会员余额
            M("screen_memcard_use")->where(array('entity_card_code|card_code' => $card_code))->setField('yue', $new_yue);
            // 插入充值码使用日志表
            $log_data = array(
                'cdk_id' => $info['id'],
                'use_time' => time(),
                'memid' => $card['memid'],
                'uid' => $card['mid'],
            );
            M('screen_memcard_cdk_log')->add($log_data);
            # 判断有没有绑定微信卡
            if($card['card_code']){
                // 更改微信端会员信息
                $ts['code'] = urlencode($card['card_code']);
                $ts['card_id'] = urlencode($card['card_id']);
                $ts['custom_field_value1'] = urlencode($new_yue);//会员卡余额
                # 获取微信token
                $token = get_weixin_token();
                $res = request_post('https://api.weixin.qq.com/card/membercard/updateuser?access_token=' . $token, urldecode(json_encode($ts)));
                get_date_dir($this->path,'code_recharge','充值码充值发送',urldecode(json_encode($ts)));
                get_date_dir($this->path,'code_recharge','返回',$res);
                $res = json_decode($res,true);
                if ($res['errcode'] == 0) {
                    // 若充值成功则将该充值吗状态改为已使用
                    M('screen_memcard_cdk')->where(array('code' => $code))->save(array('is_use' => 2));
                    M()->commit();
                    $this->succ();
                } else {
                    M()->rollback();
                }
            }else{
                M()->commit();
                $this->succ();
            }
        }
    }

    // 充值码充值
    public function code_recharge()
    {
        $open_id = I('openid');
        $card_id = I('card_id');
        $card_code = I('card_code');
        if (IS_POST) {
            get_date_dir($this->path,'code_recharge','充值码充值参数',json_encode($_POST));
            $code = I('code');
            $cdl_model = M('screen_memcard_cdk');

            # 查询是否存在充值码
            $where['code'] = $code;
            $where['status'] = 1;
            $where['is_delete'] = 0;
            $where['is_use'] = 1;
            $where['start_time'] = array('ELT', time());
            $where['end_time'] = array('EGT', time());
            ($info = $cdl_model->where($where)->find()) || $this->err('充值码错误!', 1);
            // 查询卡信息及会员信息
            $card = M("screen_memcard")
                ->field("c.mid,c.id,u.card_amount,u.yue,u.id as memid")
                ->join("c left join ypt_screen_memcard_use u on c.id=u.memcard_id")
                ->where(array('c.card_id' => $card_id, 'u.card_code' => $card_code))
                ->find();
            $yue = $card['yue'] * 100;
            $price = $info['price'] * 100;

            $new = ($price + $yue) / 100;
            M()->startTrans();
            // 增加会员余额
            M("screen_memcard_use")->where(array('card_code' => $card_code))->setField('yue', $new);
            // 插入充值码使用日志表
            $log_data = array(
                'cdk_id' => $info['id'],
                'use_time' => time(),
                'memid' => $card['memid'],
                'uid' => $card['mid'],
            );
            M('screen_memcard_cdk_log')->add($log_data);

            // 更改微信端会员信息
            $ts['code'] = urlencode($card_code);
            $ts['card_id'] = urlencode($card_id);
            $ts['custom_field_value1'] = urlencode($new);//会员卡余额
            //$ts['custom_field_value2'] = urlencode(M('screen_memcard_level')->where("c_id=$card[id] and level_integral<=$card[card_amount]")->order('level desc')->getField('level_name'));//会员卡名称
            # 获取微信token
            $token = get_weixin_token();
            $res = request_post('https://api.weixin.qq.com/card/membercard/updateuser?access_token=' . $token, urldecode(json_encode($ts)));
            get_date_dir($this->path,'code_recharge','充值码充值发送',urldecode(json_encode($ts)));
            get_date_dir($this->path,'code_recharge','返回',$res);
            $res = json_decode($res,true);
            if ($res['errcode'] == 0) {
                // 若充值成功则将该充值吗状态改为已使用
                $cdl_model->where(array('code' => $code))->save(array('is_use' => 2));
                M()->commit();
                $this->succ();
            } else {
                M()->rollback();
            }
        } else {
            $info = $this->getConfig();
            $this->assign('parm', $info);
            $this->assign('card_code', $card_code);
            $this->assign('openid', $open_id);
            $this->assign('card_id', $card_id);
            $this->display();
        }
    }

    //获取wx.config配置参数
    public function getConfig()
    {
        $token = get_weixin_token();
        $wxModel = M("weixin_token");
        $appid = 'wx3fa82ee7deaa4a21';
        $wxInfo = $wxModel->where(array("type" => "1"))->find();
        $ticket = !empty($wxInfo['tickets']) ? $wxInfo['tickets'] : '';

        if ($wxInfo['t_time'] + 7000 < time() || empty($wxInfo['tickets'])) {
            //  获取ticket
            $ticket_url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=' . $token . '&type=jsapi';
            $result = request_post($ticket_url);
            $res = json_decode($result, true);
            if (!empty($res['ticket']) && $res['errcode'] == 0) {
                $ticket = $res['ticket'];
                $wxModel->where(array("type" => "1"))->save(array('tickets' => $ticket, 't_time' => time()));
            }
        }
        $http_type = $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
        $param = array(
            'redirect_url' => $http_type . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
            'appid' => $appid,
            'jsapi_ticket' => $ticket,
        );
        $rs = $this->get_wx_config($param);
        return $rs;
    }

    //生成配置参数
    public function get_wx_config($param = array())
    {
        $timestamp = time();
        $nonceStr = rand(100000, 999999);
        $Parameters = array();
        //===============下面数组 生成SING 使用=====================
        $Parameters['url'] = $param['redirect_url'];
        $Parameters['timestamp'] = "$timestamp";
        $Parameters['noncestr'] = "$nonceStr";
        $Parameters['jsapi_ticket'] = $param['jsapi_ticket'];
        // 生成 SING
        $addrSign = $this->genSha1Sign($Parameters);
        $retArr = array();
        $retArr['appid'] = $param['appid'];
        $retArr['timestamp'] = "$timestamp";
        $retArr['noncestr'] = "$nonceStr";
        $retArr['signature'] = "$addrSign";
        return $retArr;
    }

    //创建签名SHA1
    public function genSha1Sign($Parameters)
    {
        $signPars = '';
        ksort($Parameters);
        foreach ($Parameters as $k => $v) {
            if ("" != $v && "sign" != $k) {
                if ($signPars == '')
                    $signPars .= $k . "=" . $v;
                else
                    $signPars .= "&" . $k . "=" . $v;
            }
        }
        //$signPars = http_build_query($Parameters);
        $sign = SHA1($signPars);
        $Parameters['sign'] = $sign;
        return $sign;
    }

    public function decrypt_code($encrypt_code)
    {
        $token = get_weixin_token();
        $data = json_encode(array('encrypt_code' => $encrypt_code));
        $msg = request_post('https://api.weixin.qq.com/card/code/decrypt?access_token=' . $token, $data);
        $res = json_decode($msg,true);
        if($res['errcode']==0 && $res['errmsg']=='ok'){
            return $res['code'];
        }else{
            return false;
        }
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


}
