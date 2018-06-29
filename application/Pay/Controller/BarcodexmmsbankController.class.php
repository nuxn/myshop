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
 * 这是测试用的
 * Class BarcodexmmsbankController
 * @package Pay\Controller
 */

class BarcodexmmsbankController extends HomebaseController
{

    public function __construct()
    {
        parent::__construct();
        header("Content-type: text/html; charset=utf-8");
    }

    public function getexcel()
    {
        $res = $this->import_excel('./222.xlsx');
        dump($res);
    }

    public function import_excel($file){
        ini_set('max_execution_time', '0');
        Vendor('PHPExcel.PHPExcel');
        // 判断使用哪种格式
        $extension = strtolower( pathinfo($file, PATHINFO_EXTENSION) );

        if ($extension =='xlsx') {
            $objReader = new \PHPExcel_Reader_Excel2007();
            $objPHPExcel = $objReader ->load($file);
        } else if ($extension =='xls') {
            $objReader = new \PHPExcel_Reader_Excel5();
            $objPHPExcel = $objReader ->load($file);
        } else {
            $type = pathinfo($file);
            $type = strtolower($type["extension"]);
            $type=$type==='csv' ? $type : 'Excel5';
            $objReader = \PHPExcel_IOFactory::createReader($type);
            $objPHPExcel = $objReader->load($file);
        }

        $sheet = $objPHPExcel->getSheet(0);
        // 取得总行数
        $highestRow = $sheet->getHighestRow();
        // 取得总列数
        $highestColumn = $sheet->getHighestColumn();
        //循环读取excel文件,读取一条,插入一条
        $data=array();
        //从第二行开始读取数据
        for($j=2;$j<=$highestRow;$j++){
            //从A列读取数据
            for($k='A';$k<$highestColumn;$k++){
                // 读取单元格
                if($k == 'A') $data[$j-2]['type']=(string)$objPHPExcel->getActiveSheet()->getCell("$k$j")->getValue();
                if($k == 'B') $data[$j-2]['type_name']=(string)$objPHPExcel->getActiveSheet()->getCell("$k$j")->getValue();
                if($k == 'C') $data[$j-2]['mccCd']=(string)$objPHPExcel->getActiveSheet()->getCell("$k$j")->getValue();
                if($k == 'D') $data[$j-2]['mccNm']=(string)$objPHPExcel->getActiveSheet()->getCell("$k$j")->getValue();
            }
        }
        return $data;
    }

    public function sql()
    {
        session('asfasfas',array('si'=>'213213','34asdasd'=>'sadasdqwe32314132'));
//        $message = A("Pay/Banksxf")->pay_back('20180626160118488563', '0.01');
//        dump($message);
//        $message = A("Pay/Banksxf")->query('20180626160118488563');
//        dump($message);
        dump($_SESSION);
    }

    public function test()
    {
        session();
        dump($_SESSION);

    }
########################################################################################################################
    public function wx()
    {
        $token = get_weixin_token();
        $create_card_url = "https://api.weixin.qq.com/card/update?access_token=$token";
//        $curl_datas = '{
//            "card_id":"pyaFdwLHQrMNdBIFpLFiuEQVHjGQ",
//            "member_card":{
//                "base_info":{
//                    "description":"水果不过夜
//天天吃果鲜
//
//凡储值顾客享受本店如下优惠
//
//一、储值300元
//1、免费畅饮价值220元众和乳业老酸奶两个月
//2、凭储值金额来本店消费水果
//二、储值1000元
//1、免费畅饮价值1200元众和乳业老酸奶一年
//2、凭储值金额来本店消费水果
//三、备注
//1、酸奶配送由我店专人配送 （节假日除外）
//2、会员卡在微信卡包 消费余额清晰可见
//3、会员卡长期有效
//
//
//…此活动解释权果然鲜水果超市"
//                }
//            }
//        }';

        $curl_datas = '{"card_id":"pyaFdwIQylOP-5T_IjS3qabfrufk","member_card":{"base_info":{"location_id_list":[488209631]}}}';
//        $curl_datas = '{"card_id":"pyaFdwLHQrMNdBIFpLFiuEQVHjGQ","member_card":{"base_info":{"title":"水果会员卡"}}}';
        $result = request_post($create_card_url, $curl_datas);
        echo $result;
    }

    # 连接shanpay数据库
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