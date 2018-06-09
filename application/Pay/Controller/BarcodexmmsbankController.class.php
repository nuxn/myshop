<?php

namespace Pay\Controller;

use Common\Controller\HomebaseController;

/**
 *                       _ooOoo_
 *                      o8888888o
 *                      88" . "88
 *                      (| -_- |)
 *                      O\  =  /O
 *                   ____/`---'\____
 *                 .'  \\|     |//  `.
 *                /  \\|||  :  |||//  \
 *               /  _||||| -:- |||||-  \
 *               |   | \\\  -  /// |   |
 *               | \_|  ''\---/''  |   |
 *               \  .-\__  `-`  ___/-. /
 *             ___`. .'  /--.--\  `. . __
 *          ."" '<  `.___\_<|>_/___.'  >'"".
 *         | | :  `- \`.;`\ _ /`;.`/ - ` : | |
 *         \  \ `-.   \_ __\ /__ _/   .-` /  /
 *    ======`-.____`-.___\_____/___.-`____.-'======
 *                       `=---='
 *    ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
 *                佛祖保佑       永无BUG
 */

/**
 * Class BarcodexmmsbankController
 * @package Pay\Controller
 */
class BarcodexmmsbankController extends HomebaseController
{

    public function __construct()
    {
        header("Content-type: text/html; charset=utf-8");
        parent::__construct();
    }

    private function exc()
    {
        vendor("PHPExcel.PHPExcel");
        $file = './data/upload/ec.xlsx';
//        $phpExcel = new \PHPExcel();
        $type = 'Excel5';
        // 判断使用哪种格式
        $objReader = $objReader = new \PHPExcel_Reader_Excel2007();
        $objPHPExcel = $objReader->load($file);
        $sheet = $objPHPExcel->getSheet(0);
        // 取得总行数
        $highestRow = $sheet->getHighestRow();
        // 取得总列数
        $highestColumn = $sheet->getHighestColumn();
        //循环读取excel文件,读取一条,插入一条
        $user_data = array();
        $mch_data = array();
        //从第一行开始读取数据
        for ($j = 3; $j <= $highestRow; $j++) {
            $user_data[$j] = $this->user_data;
            $mch_data[$j] = $this->mch_data;
            //从A列读取数据
            for ($k = 'A'; $k <= $highestColumn; $k++) {
                // 读取单元格
                $val = $objPHPExcel->getActiveSheet()->getCell("$k$j")->getValue();
                if (!$val) continue;
                switch ($k) {
                    case 'A':
                        $mch_data[$j]['merchant_name'] = $val;
                        $mch_data[$j]['merchant_jiancheng'] = $val;
                        $user_data[$j]['user_name'] = $val;
                        $user_data[$j]['add_time'] = time();
                        break;
                    case 'B':
                        $mch_data[$j]['address'] = $val;
                        break;
                    case 'C':
                        $user_data[$j]['user_phone'] = "$val";
                        break;
                }
            }
            $user_data[$j]['user_pwd'] = md5(123456);
//            $uid = M('merchants_users')->add($user_data[$j]);
                get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/test/','inser','user未插入数据', json_encode($user_data[$j]));
//            $res = M('merchants')->add($mch_data[$j]);

        }
    }

    public function sql()
    {

    }

    public function create()
    {
        $token = get_weixin_token();
        $url = "https://api.weixin.qq.com/cgi-bin/poi/updatepoi?access_token={$token}";
        $param['business']['base_info'] = array(
            "poi_id" => "488098370",
            "business_name"=>urlencode("湘厨"),
        );
        $param = urldecode(json_encode($param));
        $res = request_post($url, $param);
//        $res = '487200645';
        dump($res);
    }

    public function query()
    {
        //487200645,487202334
        header('content-type:application/json');
        $token = get_weixin_token();
        $url = 'http://api.weixin.qq.com/cgi-bin/poi/getpoi?access_token=' . $token;
        $url = 'https://api.weixin.qq.com/cgi-bin/poi/getpoilist?access_token=' . $token;
        $params = json_encode(array('poi_id' => 487202334));
        $params = json_encode(array('begin' => 0, 'limit' => 50));
        $res = request_post($url, $params);
        echo $res;
    }

    public function createa()
    {
        $token = get_weixin_token();
        $url = "http://api.weixin.qq.com/cgi-bin/poi/addpoi?access_token={$token}";
        $param['business']['base_info'] = array(
            "sid" => "84",
            "business_name" => urlencode("合众世纪"),
            "branch_name" => "",
            "province" => urlencode("广东省"),
            "city" => urlencode("深圳市"),
            "district" => urlencode("宝安区"),
            "address" => urlencode("银田路4号"),
            "telephone" => "1520648764",
            "categories" => array(urlencode("购物,其它购物")),
            "offset_type" => 1,
            "longitude" => 113.85882,
            "latitude" => 22.58231,
        );
        $param = urldecode(json_encode($param));
        $res = request_post($url, $param);
        echo $res;
        dump($res);
    }

#苏宁代付################################################################################################################
    public function testwd()
    {
//        succ(array(
//            "info" => array(
//                'status' => 1,
//                'result_code' => 'SUCCESS',
//                'orderSn' => $this->getRemark(),
//                'remark' => 'askjfhashf',
//                'message' => '提现已提交',
//            ))
//        );
        $send = array(
            'receiverCardNo' => '6222021907006368927',
            'receiverName' => urlencode('刘晓龙'),
            'receiverType' => 'PERSON',
            'bankName' => urlencode('中国工商银行'),
            'bankCode' => 'ICBC',
            'bankProvince' => urlencode('湖南省'),
            'bankCity' => urlencode('岳阳市'),
            'payeeBankLinesNo' => '102557060263',
            'amount' => '10',
            'serialNo' => getRemark(),
        );
        $data = array();
        $data[] = $send;
        $post = array(
            'totalNum' => '1',
            'totalAmount' => '10',
            'load' => 'SN',
            'mchId' => '10170023415356',
            'timestamp' => time(),
            'detail' => json_encode($data),
        );
        $key = '121ACECE85BB83A92879B8A1CB0B48C088DC21C8';
        $post['sign'] = getSign($post, $key);
        dump(http_build_query($post));
        $url = 'https://api.youngport.com.cn/Api/Transfer/transfer';
//        $res = $this->sendRequest($url,http_build_query($post));
//        dump(json_decode($res,true));
        $postStr = http_build_query($post);
        $postUrl = $url . '?' . $postStr;
        header("Location:$postUrl");
    }

    public function testquery()
    {
        $token = get_weixin_token();
        $create_card_url = "https://api.weixin.qq.com/card/code/unavailable?access_token=$token";
        $data['card_id'] = 'pyaFdwP6Eg4YTCjAUzwDIcaeZY6M';
        $data['code'] = '235139306709';
        $data['reason'] = 'close card';
        $result = request_post($create_card_url, json_encode($data));
        echo $result;
    }
########################################################################################################################
    public function aaa()
    {
        $token = get_weixin_token();
        $create_card_url = "https://api.weixin.qq.com/card/update?access_token=$token";
//        $curl_datas = '{
//            "card_id":"pyaFdwGLKr-hhjMGdQH9bIYIUNio",
//            "member_card":{
//                "base_info":{
//                    "description":"尊敬的颐生道健康中国会员您好，欢迎加入健康中国2030全民健康教育宣传推广事业，此卡可在公示的医疗保健，旅游养生，商业消费的合作单位享受相应的产品会员消费及服务，最终解释权归本单位所有！\n地址：北京市朝阳区高碑店华声天桥六号楼，中国人生科学学会健康教育专业委员会。"
//                }
//            }
//        }';

        $curl_datas = '{"card_id":"pyaFdwJixe57X63_eIUAhzv433i0","member_card":{"base_info":{"location_id_list":[488098370]}}}';
        $result = request_post($create_card_url, $curl_datas);
        echo $result;
    }


    public function activateuserform()
    {
        $param["card_id"] = 'pyaFdwOumLRLu35CbZlcYk70DmYw';
        $token = get_weixin_token();
        $arr = array(
            "card_id" => $param["card_id"],
            "required_form" => array(
                "common_field_id_list" => array(
                    "USER_FORM_INFO_FLAG_MOBILE",
                    "USER_FORM_INFO_FLAG_NAME",
                    "USER_FORM_INFO_FLAG_BIRTHDAY"
                )
            )
        );
            //"name": "老会员绑定",
//            "url": "https://www.qq.com"
            $arr['bind_old_card']['name'] = urlencode('有实体卡会员');
            $arr['bind_old_card']['url'] = 'https://sy.youngport.com.cn/index.php?s=api/wechat/have_card';

        $mem_card_query_url = "https://api.weixin.qq.com/card/membercard/activateuserform/set?access_token=$token";
        $result = request_post($mem_card_query_url, urldecode(json_encode($arr)));
        $result = json_decode($result, true);
        dump($result);
    }

    public function newdb()
    {
        $a = A('Barcode')->cardOff('28902');
        $db_shanpay = C('DB_SHANPAY');
        $res = M("api_bank_intoleshua", "ypt_", $db_shanpay)->where(array('update_status' => 3))->select();
        dump($res);
    }



//----------------------------------------------------------------------------------------------------------------------

    public function qr_weixipay()
    {
        //这里直接获得openid;
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            $id = I("id",'7');
            $merchant = M("merchants_cate")->where("id=$id")->find();
            $openid = $this->get_openid();
            $this->assign('openid', $openid);
            $this->assign("merchant", $merchant);
            $this->assign('seller_id', I('id'));die;
            $this->display();
        }
    }
    # 获取微信openID
    private function get_openid()
    {
        // 获取配置项
        $config = C('WEIXINPAY_CONFIG');
        // 如果没有get参数没有code；则重定向去获取openid；
        if (!isset($_GET['code'])) {
            // 返回的url
            $redirect_uri = 'https://' . $_SERVER['HTTP_HOST'] . $_SESSION['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            $redirect_uri = urlencode($redirect_uri);
            $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $config['APPID'] . '&redirect_uri=' . $redirect_uri . '&response_type=code&scope=snsapi_base#wechat_redirect';
            redirect($url);
        } else {
            //如果有code参数；则表示获取到openid
            $code = I('get.code');
            //组合获取openid的url
            $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $config['APPID'] . '&secret=' . $config['APPSECRET'] . '&code=' . $code . '&grant_type=authorization_code';
            //curl获取openid
            $result = $this->curl_get_contents($url);
            $result = json_decode($result, true);

            return $result['openid'];

        }
    }

    private function curl_get_contents($url)
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

    public function qrcodeaa()
    {
        header("content-type:text/html;charset=utf-8");
        $cate_id = I('id');
        $price = I("price",0);
        $order_id = I("order_id",0);
        $this->mode = I('mode', 1);
        $this->jmt_remark = I("jmt_remark",'');
        $order_sn = I('order_sn','');
        $openid = I('openid','');
        $httpUrl = "https://" . $_SERVER['HTTP_HOST'];

        #检查该笔订单使用的储值、积分是否充足，是否有优惠券
        $this->checkt_order($order_sn);

        $cate_res = $this->get_cate_data($cate_id);
        $this->checker_id = I("checker_id", $cate_res['checker_id']);

        if ($cate_res['status'] != 1) { $this->out_err('商家未上线');}

        // 微信支付
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
//            # 判断是否走洋仆淘支付
//            if($cate_res['is_ypt'] == "1" && in_array($cate_id, array(7, 11))){
//                if ($order_id) $url = $httpUrl . "/index.php?g=Pay&m=Barcodexybank&a=ypt_two_wz_pay&id=" . $twoPayStr;//双屏端扫码支付收款
//                else if ($price) $url = $httpUrl . "/index.php?g=Pay&m=Barcodexybank&a=ypt_wz_pay&id=" . $wxPayStr;//手机端扫码支付收款
//                else  $url = $httpUrl . "/index.php?g=Pay&m=Barcodexybank&a=ypt_qr_weixipay&id=" . $cate_id . "&checker_id=" . $checker_id;//台签收款
//                header("Location: $url");
//                exit;
//            }
            $this->wx_bank = $cate_res['wx_bank'];
            #支付通道暂未开放
            if(in_array($this->wx_bank, array('1','2','4','5','6','8'))){$this->out_err('支付通道暂未开放');}

            if(!$openid) $openid = $this->get_openid();
            $this->remark = $order_sn?:getRemark();

            if($order_id){ // 有订单号时
                $send_params = $this->get_wxpay_params($cate_res,$price,$openid,$order_id);
            } else if($price){  // 有金额时
                $send_params = $this->get_wxpay_params($cate_res,$price,$openid);
            } else { // 没有
                $card_url = "";
                $pay_url = "s=Pay/Barcodexmmsbank/qrcodeaa/id/{$cate_id}/openid/{$openid}/checker_id/{$this->checker_id}";
                $this->assign('pay_url', $pay_url);
                $this->display("wx_page");
                exit;

            }
            $pay_info = $this->wx_jspay($send_params);
            $this->assign('body', $pay_info);
            $this->display('wx_pay');
            exit;

        } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient') !== false) {
            # ======================  支付宝支付  =======================================================================

//            # 判断是否走洋仆淘支付
//            if($res['is_ypt'] == "1" && in_array($id, array(7, 11))){
//                if ($order_id) $url = $httpUrl . "/index.php?g=Pay&m=Barcodexybank&a=ypt_screen_alipay&{$screenStr}";//双屏端扫码支付收款
//                else if ($price) $url = $httpUrl . "/index.php?g=Pay&m=Barcodexybank&a=yptqr_to_alipay&{$priceStr}";//手机端扫码支付收款
//                else $url = $httpUrl . "/index.php?g=Pay&m=Barcodexybank&a=ypt_qr_alipay&id=" . $id . "&checker_id=" . $checker_id;//台签收款
//                header("Location: $url");
//            }

            $this->ali_bank = $cate_res['wx_bank'];
            #支付通道暂未开放
            if(in_array($this->ali_bank, array('1','2','4','5','6','8')))
                exit("<title>支付宝安全支付</title><div style='margin: 10px auto;font-size: 30px;width:60%;'>支付通道暂未开放</div>");

            $this->remark = $order_sn?:getRemark();

            if($order_id){ // 有订单号时
                $send_params = $this->get_alipay_params($cate_res,$price,$order_id);
            } else if($price){  // 有金额时
                $send_params = $this->get_alipay_params($cate_res,$price);
            } else { // 没有
                $card_url = "";
                $pay_url = "s=Pay/Barcodexmmsbank/qrcodeaa/id/{$cate_id}/checker_id/{$this->checker_id}";
                $this->assign('pay_url', $pay_url);
                $this->display("ali_page");
                exit;

            }
            $ali_url = $this->ali_jspay($send_params);
            header("Location: $ali_url");
            exit;
        } else {//扫码失败
            echo "请用微信或者支付宝扫码~";
            exit;
        }

    }


    #检查该笔订单使用的储值、积分是否充足，是否有优惠券
    private function checkt_order($order_sn)
    {
        $order_info = M('order')->where(array('order_sn' => $order_sn))->field('card_code,user_money,integral,coupon_code')->find();
        if ($order_info) {
            #会员卡
            if ($order_info['card_code'] > 0) {
                $card_info = M('screen_memcard_use')->where(array('card_code' => $order_info['card_code']))->field('yue,card_balance')->find();
                if ($order_info['user_money'] > 0) {
                    if ($order_info['user_money'] > $card_info['yue']) {
                        $this->ajaxReturn(array('code' => 'error', 'msg' => '储值不足'));
                    }
                }
                if ($order_info['integral'] > 0) {
                    if ($order_info['integral'] > $card_info['card_balance']) {
                        $this->ajaxReturn(array('code' => 'error', 'msg' => '积分不足'));
                    }
                }
            }
            #优惠券
            if ($order_info['coupon_code'] > 0) {
                $coupon_status = M('screen_user_coupons')->where(array('usercard' => $order_info['coupon_code']))->getField('status');
                if ($coupon_status == 0) {
                    $this->ajaxReturn(array('code' => 'error', 'msg' => '优惠券已被使用'));
                }
            }
        }
    }

    private function get_cate_data($cate_id)
    {
        $res = M('merchants_cate')->field('jianchen,merchant_id,status,no_number,wx_bank,ali_bank,checker_id,is_ypt')->where('id=' . $cate_id)->find();
        if($res){
            return $res;
        } else {
            $this->out_err('商家未上线');
        }
    }

    private function wx_jspay($order_params)
    {
        $this->wx_bank = 3;
        switch ($this->wx_bank) {
            case 3:
                $res = A("Pay/Wxpay")->api_wxpay($order_params);
                break;
            case 7:

                break;
            case 9:

                break;
            case 10:

                break;
            case 11:

                break;
            case 12:

                break;
            default:
                $this->out_err('支付通道暂未开放');
        }
        if($res['code'] == '0'){
            return $res['pay_info'];
        } else {
            $this->assign('err_msg',"网络异常，请重试！");
            $this->display("error");
            exit;
        }

    }

    private function ali_jspay($order_params)
    {
        $this->ali_bank = 3;
        switch ($this->ali_bank) {
            case 3:
                $res = A("Pay/Alipay")->api_alipay($order_params);
                break;
            case 7:

                break;
            case 9:

                break;
            case 10:

                break;
            case 11:

                break;
            case 12:

                break;
            default:
                $this->out_err('支付通道暂未开放');
        }
        if($res['code'] == '0'){
            return $res['pay_info'];
        } else {
            $this->assign('err_msg',"网络异常，请重试！");
            $this->display("error");
            exit;
        }

    }

    private function get_wxpay_params($cate_res,$price,$openid,$order_id = '')
    {
        if($order_id){
            $order_data = M("order")->field('order_sn,order_amount')->where("order_id='$order_id'")->find();
            $this->remark = $order_data['order_sn'];
            $send_params['price']  = $order_data['order_amount'];
        } else {
            $send_params['price']  = $price;
        }
        $this->subject = "向{$cate_res['jianchen']}支付{$price}元";

        $send_params['remark'] = $this->remark;
        $send_params['subject'] = $this->subject;
        $send_params['open_id']= $openid;
        $send_params['merchant_id']= $cate_res['merchant_id'];

        return $send_params;
    }

    private function get_alipay_params($cate_res,$price,$order_id = '')
    {
        if($order_id){
            $order_data = M("order")->field('order_sn,order_amount')->where("order_id='$order_id'")->find();
            $this->remark = $order_data['order_sn'];
            $send_params['price']  = $order_data['order_amount'];
        } else {
            $send_params['price']  = $price;
        }
        $this->subject = "向{$cate_res['jianchen']}支付{$price}元";

        $send_params['remark'] = $this->remark;
        $send_params['subject'] = $this->subject;
//        $send_params['open_id']= $openid;
        $send_params['merchant_id']= $cate_res['merchant_id'];

        return $send_params;
    }

    private function out_err($msg)
    {
        echo "<title>微信支付</title><div style='margin: 10px auto;font-size: 30px;width:80%;color:#666'>$msg</div>";
        exit;
    }

}