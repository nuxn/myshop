<?php

namespace Pay\Controller;

use Common\Controller\HomebaseController;

/**支付
 * Class BarcodeController
 * @package Pay\Controller
 */
class BarcodeController extends HomebaseController
{
    private $pay_model;

    public function __construct()
    {
        parent::__construct();
        $this->pay_model = M('pay');
    }

    /**
     * 支付成功展示页面
     */
    public function index()
    {
        $json_str = file_get_contents('php://input', 'r');
        // 转成php数组
        file_put_contents('./data/log/wz/weixin/' . date("Y_m_") . 'qingqiu.log', date("Y-m-d H:i:s") . '传递参数11' . $json_str . PHP_EOL, FILE_APPEND | LOCK_EX);

        $map = 1;
        $url = 'http://sy.youngport.com.cn/index.php?s=/Barcode/';
        $this->assign('map', $map);
        $this->assign('url', $url);
        $this->display();
        exit;
    }

    public function memcard_recharge()
    {
        $card_id = I('card_id');
        if (!isset($card_id) && empty($card_id)) {
            $result['code'] = 'error';
            $result['msg']  = '参数错误';
            $this->ajaxReturn($result);
        }
        $price = I('price');
        if (!isset($price) && empty($price)) {
            $result['code'] = 'error';
            $result['msg']  = '参数错误';
            $this->ajaxReturn($result);
        }
        $ypt_screen_memcard = M('screen_memcard')->where(array('card_id' => $card_id))->find();
        $id                 = $ypt_screen_memcard['id'];
        $user_id            = $ypt_screen_memcard['mid'];
        $accountData        = M('merchants_users')->where(array('id' => $user_id))->find();
        $re                 = M('merchants_role_users')->where(array('uid' => $user_id))->find();
        $role_id            = $re['role_id'];
        //商家
        if ($role_id == 3) {
            $uid = $user_id;
            //收银员
        } else if ($role_id == 7) {
            $pid   = $accountData['pid'];
            $pData = M('merchants_users')->where(array('id' => $pid))->find();
            $uid   = $pData['id'];
        }
        $sql      = "select alipay_partner,wx_mchid,wx_bank,merchant_id  from ypt_merchants,ypt_merchants_cate where ypt_merchants.id=ypt_merchants_cate.merchant_id AND ypt_merchants.uid='$uid'";
        $cateData = M('')->query($sql);
        $cateData = $cateData['0'];
        $wx_bank  = $cateData['wx_bank'];
        $wx_mchid = $cateData['wx_mchid'];
        //商户id
        $mid = $cateData['merchant_id'];
        //用户id
        $account_id = $user_id;
        $http       = 'https';
        $httpUrl    = $http . "://" . $_SERVER['HTTP_HOST'];
        if ($wx_bank == 1) {
            echo "string";
        } else if ($wx_bank == 2) {
            $url = $httpUrl . "/index.php?g=Pay&m=Barcodembank&a=pay_cash&account_id=" . $account_id . "&price=" . $price . "&wx_mchid=" . $wx_mchid . "&mid=" . $mid . "&id=" . $id;
        } else if ($wx_bank == 3) {
            echo "1";
        } else if ($wx_bank == 4) {
            $url = $httpUrl . "/index.php?g=Pay&m=Barcodezsbank&a=pay_cash&account_id=" . $account_id . "&price=" . $price . "&wx_mchid=" . $wx_mchid . "&wx_key=" . $cateData['wx_key'] . "&mid=" . $mid . "&id=" . $id;
        }
        header("Location: $url");
    }

    /**
     * 扫码支付默认方法
     */
    public function qrcode()
    {
        header("content-type:text/html;charset=utf-8");
        $id         = I('id');
        $mode       = I('mode', 1);
        $price      = I("price");
        $res        = M('merchants_cate')->field('status,no_number,wx_bank,ali_bank,checker_id,is_ypt')->where('id=' . $id)->find();
        $checker_id = I("checker_id", $res['checker_id']);
        $type       = I("type");
        $order_id   = I("order_id");
        $jmt_remark = I("jmt_remark");
        $order_sn   = I('order_sn');

        $http = 'https';
        // if ($_SERVER['HTTP_HOST'] != 'sy.youngport.com.cn') $http = 'http';

        #检查该笔订单使用的储值、积分是否充足，是否有优惠券
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
        if ((int)$res['status'] != 1) {
            echo "<title>微信支付</title><div style='margin: 10px auto;font-size: 30px;width:80%;color:red'>商家未上线</div>";
            exit;
        }
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            $httpUrl = $http . "://" . $_SERVER['HTTP_HOST'];
            $twoPayStr = $id . "&price=" . $price . "&checker_id=" . $checker_id . "&order_id=" . $order_id . "&jmt_remark=" . $jmt_remark;
            $wxPayStr = $id . "&price=" . $price . "&checker_id=" . $checker_id . "&jmt_remark=" . $jmt_remark . "&order_sn=" . $order_sn . "&mode=" . $mode;

            # 判断是否走洋仆淘支付
            if ($res['is_ypt'] == "1" && in_array($id, array(7, 11))) {
                if ($order_id) {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodexybank&a=ypt_two_wz_pay&id=" . $twoPayStr;
                } else if ($price) {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodexybank&a=ypt_wz_pay&id=" . $wxPayStr;
                } else {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodexybank&a=ypt_qr_weixipay&id=" . $id . "&checker_id=" . $checker_id;
                }
                header("Location: $url");
            }

            if (in_array($res['wx_bank'], array('1', '2', '4', '5', '6', '8'))) {
                exit("<title>微信支付</title><div style='margin: 10px auto;font-size: 30px;width:60%;'>支付通道暂未开放</div>");
            }

            // 微众支付跳转
            if ($res['wx_bank'] == "1") {
                if ($order_id) {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcode&a=two_wz_pay&id=" . $twoPayStr;
                } else if ($price) {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcode&a=wz_pay&id=" . $wxPayStr;
                } else {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcode&a=qr_weixipay&id=" . $id . "&checker_id=" . $checker_id;
                }
                header("Location: $url");
            }
            // 民生支付跳转
            if ($res['wx_bank'] == "2") {
                if ($order_id) {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodembank&a=two_wz_pay&id=" . $twoPayStr;
                } else if ($price) {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodembank&a=wz_pay&id=" . $wxPayStr;
                } else {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodembank&a=qr_weixipay&id=" . $id . "&checker_id=" . $checker_id;
                }

                header("Location: $url");
            }

            // 微信支付跳转
            if ($res['wx_bank'] == "3") {
                if ($order_id) {
                    $url = $httpUrl . "/index.php?g=Pay&m=Wxpay&a=two_wxpay&id=" . $twoPayStr;
                } else if ($price) {
                    $url = $httpUrl . "/index.php?g=Pay&m=Wxpay&a=wx_pay&id=" . $wxPayStr;
                } else {
                    $url = $httpUrl . "/index.php?g=Pay&m=Wxpay&a=wxpay&id=" . $id . "&checker_id=" . $checker_id;
                }
                header("Location: $url");
            }
            //招行支付跳转
            if ($res['wx_bank'] == "4") {
                if ($order_id) {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodezsbank&a=two_wz_pay&id=" . $twoPayStr;
                } else if ($price) {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodezsbank&a=wz_pay&id=" . $wxPayStr;
                } else {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodezsbank&a=qr_weixipay&id=" . $id . "&checker_id=" . $checker_id;
                }
                header("Location: $url");
            }
            // 钱方微信支付跳转
            if ($res['wx_bank'] == "5") {
                if ($order_id) {
                    $url = $httpUrl . "/index.php?g=Pay&m=QianFangPay&a=twoWxpay&id=" . $twoPayStr;
                } else if ($price) {
                    $url = $httpUrl . "/index.php?g=Pay&m=QianFangPay&a=qianFangPay&id=" . $wxPayStr;
                } else {
                    $url = $httpUrl . "/index.php?g=Pay&m=QianFangPay&a=wxpay&id=" . $id . "&checker_id=" . $checker_id;
                }
                header("Location: $url");
            }
            //民生D0支付跳转
            if ($res['wx_bank'] == "6") {
                if ($order_id) {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodemsday1&a=two_wz_pay&id=" . $twoPayStr;
                } else if ($price) {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodemsday1&a=wz_pay&id=" . $wxPayStr;
                } else {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodemsday1&a=qr_weixipay&id=" . $id . "&checker_id=" . $checker_id;
                }
                header("Location: $url");
            }
            //兴业
            if ($res['wx_bank'] == "7") {
                if ($order_id) {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodexybank&a=two_wz_pay&id=" . $twoPayStr;
                } else if ($price) {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodexybank&a=wz_pay&id=" . $wxPayStr;
                } else {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodexybank&a=qr_weixipay&id=" . $id . "&checker_id=" . $checker_id;
                }
                header("Location: $url");
            }
            // 恒丰久运昌
            if ($res['wx_bank'] == '8') {
                if ($order_id) {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodejybank&a=twoscree_pay&id=" . $twoPayStr;
                } else if ($price) {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodejybank&a=wxpay&id=" . $wxPayStr;
                } else {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodejybank&a=qr_wxpay&id=" . $id . "&checker_id=" . $checker_id;
                }
                header("Location: $url");
            }

            // 宿州李总微信通道
            if ($res['wx_bank'] == "9") {
                if ($order_id) {
                    $url = $httpUrl . "/index.php?g=Pay&m=Szlzpay&a=two_wxpay&id=" . $id . "&price=" . $price . "&checker_id=" . $checker_id . "&order_id=" . $order_id . "&mode=" . $mode . "&jmt_remark=" . $jmt_remark;
                } else if ($price) {
                    $url = $httpUrl . "/index.php?g=Pay&m=Szlzpay&a=wx_pay&id=" . $id . "&price=" . $price . "&checker_id=" . $checker_id . "&jmt_remark=" . $jmt_remark . "&order_sn=" . $order_sn;
                } else {
                    $url = $httpUrl . "/index.php?g=Pay&m=Szlzpay&a=wxpay&id=" . $id . "&checker_id=" . $checker_id;
                }
                header("Location: $url");
            }
            //浦发
            if ($res['wx_bank'] == "10") {
                if ($order_id) {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodepfbank&a=two_wz_pay&id=" . $twoPayStr;
                } else if ($price) {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodepfbank&a=wz_pay&id=" . $wxPayStr;
                } else {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodepfbank&a=qr_weixipay&id=" . $id . "&checker_id=" . $checker_id;
                }
                header("Location: $url");
            }
            //新大陆
            if ($res['wx_bank'] == "11") {
                if ($order_id) {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodexdlbank&a=two_wx_pay&id=" . $twoPayStr;
                } else if ($price) {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodexdlbank&a=wx_pay&seller_id=" . $wxPayStr;
                } else {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodexdlbank&a=qr_weixipay&id=" . $id . "&checker_id=" . $checker_id;
                }
                header("Location: $url");
            }

            //乐刷
            if ($res['wx_bank'] == "12") {
                if ($order_id) {
                    $url = $httpUrl . "/index.php?g=Pay&m=Leshuabank&a=two_wx_pay&id=" . $twoPayStr;
                } else if ($price) {
                    $url = $httpUrl . "/index.php?g=Pay&m=Leshuabank&a=wx_pay&id=" . $wxPayStr;
                } else {
                    $url = $httpUrl . "/index.php?g=Pay&m=Leshuabank&a=qr_weixipay&id=" . $id . "&checker_id=" . $checker_id;
                }
                header("Location: $url");
            }
            //平安付
            if ($res['wx_bank'] == "13") {
                if ($order_id) {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodepabank&a=two_wx_pay&id=" . $twoPayStr;
                } else if ($price) {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodepabank&a=wx_pay&id=" . $wxPayStr;
                } else {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodepabank&a=qr_weixipay&id=" . $id . "&checker_id=" . $checker_id;
                }
                header("Location: $url");
            }
            // 随行付支付
            if ($res['wx_bank'] == "14") {
                if ($order_id) {
                    $url = $httpUrl . "/index.php?g=Pay&m=Banksxf&a=two_wx_pay&id=" . $twoPayStr;
                } else if ($price) {
                    $url = $httpUrl . "/index.php?g=Pay&m=Banksxf&a=wx_pay&id=" . $wxPayStr;
                } else {
                    $url = $httpUrl . "/index.php?g=Pay&m=Banksxf&a=qr_weixipay&id=" . $id . "&checker_id=" . $checker_id;
                }
                header("Location: $url");
            }

        } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient') !== false) {
            # ======================  支付宝支付  =======================================================================
            $httpUrl = $http . "://" . $_SERVER['HTTP_HOST'];
            $screenStr = "seller_id={$id}&price={$price}&checker_id={$checker_id}&type={$type}&order_id={$order_id}&jmt_remark={$jmt_remark}";
            $priceStr = "seller_id={$id}&price={$price}&checker_id={$checker_id}&type={$type}&jmt_remark={$jmt_remark}&order_sn={$order_sn}&mode={$mode}";

            # 判断是否走洋仆淘支付
            if ($res['is_ypt'] == "1" && in_array($id, array(7, 11))) {
                if ($order_id) {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodexybank&a=ypt_screen_alipay&{$screenStr}";
                } else if ($price) {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodexybank&a=yptqr_to_alipay&{$priceStr}";
                } else {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodexybank&a=ypt_qr_alipay&id=" . $id . "&checker_id=" . $checker_id;
                }
                header("Location: $url");
            }

            if (in_array($res['ali_bank'], array('1', '2', '4', '5', '6', '8'))) {
                exit("<title>支付宝安全支付</title><div style='margin: 10px auto;font-size: 30px;width:60%;'>支付通道暂未开放</div>");
            }

            if ($res['ali_bank'] == "1") {
                if ($order_id) {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcode&a=screen_wz_alipay&{$screenStr}";
                } else if ($price) {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcode&a=qr_to_alipay&{$priceStr}";
                } else {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcode&a=qr_alipay&id=" . $id . "&checker_id=" . $checker_id;
                }
                header("Location: $url");
            }
            // 民生支付跳转
            if ($res['ali_bank'] == "2") {
                if ($order_id) {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodembank&a=screen_wz_alipay&{$screenStr}";
                } else if ($price) {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodembank&a=qr_to_alipay&{$priceStr}";
                } else {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodembank&a=qr_alipay&id=" . $id . "&checker_id=" . $checker_id;
                }
                header("Location: $url");
            }
            // 支付宝官方支付
            if ($res['ali_bank'] == "3") {
                if ($order_id) {
                    $url = $httpUrl . "/index.php?g=Pay&m=Alipay&a=screen_alipay&{$screenStr}";
                } else if ($price) {
                    $url = $httpUrl . "/index.php?g=Pay&m=Alipay&a=qr_to_alipay&{$priceStr}";
                } else {
                    $url = $httpUrl . "/index.php?g=Pay&m=Alipay&a=qr_alipay&id=" . $id . "&checker_id=" . $checker_id;
                }
                header("Location: $url");
            }
            //招行支付跳转
            if ($res['ali_bank'] == "4") {
                if ($order_id) {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodezsbank&a=screen_wz_alipay&{$screenStr}";
                } else if ($price) {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodezsbank&a=qr_to_alipay&{$priceStr}";
                } else {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodezsbank&a=qr_alipay&id=" . $id . "&checker_id=" . $checker_id;
                }
                header("Location: $url");
            }
            //民生d0
            if ($res['ali_bank'] == "6") {
                if ($order_id) {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodemsday1&a=screen_wz_alipay&{$screenStr}";
                } else if ($price) {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodemsday1&a=qr_to_alipay&{$priceStr}";
                } else {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodemsday1&a=qr_alipay&id=" . $id . "&checker_id=" . $checker_id;
                }
                header("Location: $url");
            }
            //兴业
            if ($res['ali_bank'] == "7") {
                if ($order_id) {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodexybank&a=screen_wz_alipay&{$screenStr}";
                } else if ($price) {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodexybank&a=qr_to_alipay&{$priceStr}";
                } else {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodexybank&a=qr_alipay&id=" . $id . "&checker_id=" . $checker_id;
                }
                header("Location: $url");
            }
            // 恒丰久运昌
            if ($res['ali_bank'] == "8") {
                if ($order_id) {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodejybank&a=screen_wz_alipay&{$screenStr}";
                } else if ($price) {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodejybank&a=qr_to_alipay&{$priceStr}";
                } else {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodejybank&a=qr_alipay&id=" . $id . "&checker_id=" . $checker_id;
                }
                header("Location: $url");
            }
            // 宿州李总支付宝
            if ($res['ali_bank'] == "9") {
                if ($order_id) {
                    $url = $httpUrl . "/index.php?g=Pay&m=Szlzpay&a=screen_alipay&id=" . $id . "&price=" . $price . "&checker_id=" . $checker_id . "&order_id=" . $order_id . "&jmt_remark=" . $jmt_remark;
                } else if ($price) {
                    $url = $httpUrl . "/index.php?g=Pay&m=Szlzpay&a=qr_to_alipay&id=" . $id . "&price=" . $price . "&checker_id=" . $checker_id . "&jmt_remark=" . $jmt_remark . "&order_sn=" . $order_sn;
                } else {
                    $url = $httpUrl . "/index.php?g=Pay&m=Szlzpay&a=qr_alipay&id=" . $id . "&checker_id=" . $checker_id;
                }
                header("Location: $url");
            }
            //浦发
            if ($res['ali_bank'] == "10") {
                if ($order_id) {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodepfbank&a=screen_wz_alipay&{$screenStr}";
                } else if ($price) {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodepfbank&a=qr_to_alipay&{$priceStr}";
                } else {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodepfbank&a=qr_alipay&id=" . $id . "&checker_id=" . $checker_id;
                }
                header("Location: $url");
            }
            //新大陆
            if ($res['ali_bank'] == "11") {
                if ($order_id) {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodexdlbank&a=two_alipay&{$screenStr}";
                } else if ($price) {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodexdlbank&a=qr_to_alipay&{$priceStr}";
                } else {
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodexdlbank&a=qr_alipay&id=" . $id . "&checker_id=" . $checker_id;
                }
                header("Location: $url");
            }
            //乐刷
            if ($res['ali_bank'] == "12") {
                if ($order_id) {//双屏端扫码支付收款
                    $url = $httpUrl . "/index.php?g=Pay&m=Leshuabank&a=two_alipay&{$screenStr}";
                } else if ($price) {//手机端扫码支付收款
                    $url = $httpUrl . "/index.php?g=Pay&m=Leshuabank&a=qr_to_alipay&{$priceStr}";
                } else {//台签收款
                    $url = $httpUrl . "/index.php?g=Pay&m=Leshuabank&a=qr_alipay&id=" . $id . "&checker_id=" . $checker_id;
                }

                header("Location: $url");
            }
            //平安
            if ($res['ali_bank'] == "13") {
                if ($order_id) {//双屏端扫码支付收款
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodepabank&a=two_alipay&{$screenStr}";
                } else if ($price) {//手机端扫码支付收款
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodepabank&a=qr_to_alipay&{$priceStr}";
                } else {//台签收款
                    $url = $httpUrl . "/index.php?g=Pay&m=Barcodepabank&a=qr_alipay&id=" . $id . "&checker_id=" . $checker_id;
                }
                header("Location: $url");
            }
            // 随行付
            if ($res['ali_bank'] == "14") {
                if ($order_id) {//双屏端扫码支付收款
                    $url = $httpUrl . "/index.php?g=Pay&m=Banksxf&a=two_alipay&{$screenStr}";
                } else if ($price) {//手机端扫码支付收款
                    $url = $httpUrl . "/index.php?g=Pay&m=Banksxf&a=qr_to_alipay&{$priceStr}";
                } else {//台签收款
                    $url = $httpUrl . "/index.php?g=Pay&m=Banksxf&a=qr_alipay&id=" . $id . "&checker_id=" . $checker_id;
                }
                header("Location: $url");
            }
        } else {//扫码失败
            echo "请用微信或者支付宝扫码~";
            exit;
        }

    }

    public function pay_back_suc($remark, $refund_amount)
    {
        if ($this->pay_model->where("remark='$remark'")->find()) {
            $this->pay_model->where("remark='$remark'")->save(array("status" => 2, "back_status" => 1, "price_back" => $refund_amount));
        }
    }

    /**
     * 支付成功后更新库存
     * 传入订单唯一标识$order_sn
     * @param int $order_sn
     */
    private function update_order_goods_number($order_sn = 0)
    {
        if (!$order_sn) {
            return;
        }

        $order_id         = M("order")->where(array("order_sn" => $order_sn))->getField("order_id");
        $order_goods_list = M("order_goods")->where(array("order_id" => $order_id))->field("goods_id,goods_num")->select();
        if (!$order_goods_list) {
            return;
        }

        foreach ($order_goods_list as $k => $v) {
            if ($v['goods_id'] && $v['goods_num']) {
                M("goods")->where(array("goods_id" => $v['goods_id']))->setDec('goods_number', $v['goods_num']);
            }
            //更新库存
        }
    }

    public function push_pay_message($remark)
    {
        $pay = $this->pay_model->where("remark='$remark'")->find();
        if (!$pay) {
            return;
        }

        //声明推送消息日志路径
        $path = get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/message/');
        if (!$pay) {
            return;
        }
        $mid     = $pay['merchant_id'];
        $checker = $pay['checker_id'];

        $status = $pay['status'];
        $price  = $pay['price'];
        $mode   = $pay['mode'];
        switch ($mode) {
            case 0:
                $mode = "台签";
                break;
            case 1:
                $mode = "商业扫码支付";
                break;
            case 2:
                $mode = "商业刷卡支付";
                break;
            case 3:
                $mode = "收银扫码支付";
                break;
            case 4:
                $mode = "收银现金支付";
                break;
            default:
                $mode = "其他支付";
                break;
        }
        if ($status == 0) {
            $massage = "收款失败";
        } else if ($status == 1) {
            $massage = "[" . $mode . "]" . "来钱啦,收款" . $price . "元！";
        } else {
            $massage = '';
        }

        //有收银员的情况下,将信息发给收银员
        if ($checker) {
            $check_phone      = M("merchants_users")->where("id=$checker")->getField("user_phone");
            $check_device_tag = M("merchants_users")->where("id=$checker")->getField("device_tag");
        }

        //当前商户
        $merchants_info  = M("merchants")->where("id=$mid")->field("uid,mid")->find();
        $uid             = $merchants_info['uid'];
        $user_phone      = M("merchants_users")->where(array('id' => $uid))->getField("user_phone");
        $user_device_tag = M("merchants_users")->where(array('id' => $uid))->getField("device_tag");

        //多门店大商户
        if ($merchants_info['mid'] > 0) {
            $big_uid             = M("merchants")->where(array('id' => $merchants_info['mid']))->getField("uid");
            $big_user_phone      = M("merchants_users")->where(array('id' => $big_uid))->getField("user_phone");
            $big_user_device_tag = M("merchants_users")->where(array('id' => $big_uid))->getField("device_tag");
        }
        file_put_contents($path . date("Y_m_d_") . 'pay_message.log', date("Y-m-d H:i:s") . '未发送1,' . "$remark" . PHP_EOL, FILE_APPEND | LOCK_EX);
        /***推送给大商户****/
        if (isset($big_user_device_tag) && isset($big_uid) && M("token")->where(array('uid' => $big_uid))->getField("uid")) {
            //A("Message/adminpush")->adminpush("$massage", "$remark", "$status", "$big_user_phone");
            A("Message/adminpush")->api_push_msg("$massage", $pay['id'], "ok", "$big_user_device_tag");
            file_put_contents($path . date("Y_m_d_") . 'pay_message.log', date("Y-m-d H:i:s") . '发送信息给多门店大商户: ' . $big_user_phone . "___" . $status . "____" . $massage . ",订单号:" . "$remark" . PHP_EOL, FILE_APPEND | LOCK_EX);
        } else {
            file_put_contents($path . date("Y_m_d_") . 'pay_message.log', date("Y-m-d H:i:s") . '未发送2,' . $user_phone . "的上级门店未登录____订单号:  " . "$remark" . PHP_EOL, FILE_APPEND | LOCK_EX);
            file_put_contents($path . date("Y_m_d_") . 'pay_message.log', date("Y-m-d H:i:s") . '未发送3,' . "$remark" . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
        /***推送给收银员****/
        if (isset($check_device_tag) && M("token")->where(array('uid' => $checker))->getField("uid")) {
            //A("Message/adminpush")->adminpush("$massage", "$remark", "$status", "$check_phone");
            A("Message/adminpush")->api_push_msg("$massage", $pay['id'], "ok", "$check_device_tag");

            file_put_contents($path . date("Y_m_d_") . 'pay_message.log', date("Y-m-d H:i:s") . '发送信息给收银员: ' . $check_phone . "___" . $status . "____" . $massage . ",订单号:" . "$remark" . PHP_EOL, FILE_APPEND | LOCK_EX);
        } else {
            file_put_contents($path . date("Y_m_d_") . 'pay_message.log', date("Y-m-d H:i:s") . '未发送4,' . "$remark" . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
        /***推送给商户****/
        if ($user_device_tag && M("token")->where(array('uid' => $uid))->getField("uid")) {
            //$res = A("Message/adminpush")->adminpush("$massage", "$remark", "$status", "$user_phone");
            A("Message/adminpush")->api_push_msg("$massage", $pay['id'], "ok", "$user_device_tag");
            file_put_contents($path . date("Y_m_d_") . 'pay_message.log', date("Y-m-d H:i:s") . '发送信息给商户: ' . $user_phone . PHP_EOL, FILE_APPEND | LOCK_EX);
        } else {
            file_put_contents($path . date("Y_m_d_") . 'pay_message.log', date("Y-m-d H:i:s") . '未发送5,' . $user_phone . "未登录____订单号:  " . "$remark" . PHP_EOL, FILE_APPEND | LOCK_EX);
        }

    }

//    支付成功  卡券信息更新
    public function _change_coupon($order_one)
    {
        $order_id = $order_one['order_id'];
        M("order")->where("order_id='$order_id'")->save(array("pay_time" => time(), "pay_status" => 1, "paystyle" => 3));
        $this->update_order_goods_number($order_one['order_sn']);
        $code            = $order_one['coupon_code'];
        $coupon_user_one = M("screen_user_coupons")->where("usercard='$code'")->find();
        if ($coupon_user_one) {
            $url          = "https://api.weixin.qq.com/card/code/consume?access_token=" . get_weixin_token();
            $data['code'] = $code;
            $use_coupon   = request_post($url, json_encode($data));
            $use_coupon   = json_decode($use_coupon);
            file_put_contents('./data/log/wz/weixin/coupon.log', date("Y-m-d H:i:s") . '用户使用优惠券' . json_encode($use_coupon) . PHP_EOL, FILE_APPEND | LOCK_EX);
            if ($use_coupon->errmsg != "ok") {
                file_put_contents('./data/log/wz/weixin/coupon.log', date("Y-m-d H:i:s") . '用户使用优惠券失败' . $order_id . PHP_EOL, FILE_APPEND | LOCK_EX);
            }

            if ($use_coupon->errmsg == "ok") {
                M("screen_user_coupons")->where("usercard='$code'")->save(array("satus" => 0));
            }
        }
    }

    /**
     * 微众公众号支付回调
     */
    public function weixipay_return()
    {
        $price          = (int) I('price') / 100;
        $openid         = I('openid');
        $mid            = I('mid');
        $remark         = I("remark");
        $merchants_info = M('pay p')->join('__MERCHANTS__ m on p.merchant_id=m.id')->where("p.customer_id= '$openid'AND p.merchant_id=$mid")->Field('p.remark,p.paytime,m.uid,m.merchant_name')->order('paytime desc')->find();
        file_put_contents('./data/log/wz/weixin/' . date("Y_m_") . 'taika.log', date("Y-m-d H:i:s") . 'remark:' . $remark . ',openid:' . $openid . ',price:' . $price . PHP_EOL, FILE_APPEND | LOCK_EX);
        $this->assign('price', $price);
        $this->assign('pay_time', $merchants_info['paytime']);
        $this->assign('remark', $merchants_info['remark']);
        $this->assign('mername', $merchants_info['merchant_name']);
        if ($openid !== "oyaFdwKf7Hg-uK9efnS8KojGaXW8") {
            $this->display();
            exit;
        } else {
            $this->display('weixipay_return000');
            exit;
        }

    }

    public function weixipay_return000()
    {
        $price    = (int) I('price') / 100;
        $openid   = I('openid');
        $remark   = I("remark");
        $order_id = I("order_id");
        $mid      = I("mid");
        if (isset($_GET['returncode']) && $_GET['returncode'] != '000000') {
            get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/test/', 'weixipay_return000', '数据', json_encode($_GET));
            $this->assign('err_msg', $_GET['returncode'] . ":交易失败");
            $this->display(":Barcodexybank/error");
            exit;
        }
        if ($_GET['sub_openid']) {
            $openid = $_GET['sub_openid'];
        }
        $merchant = M('merchants')->where("id=$mid")->field('merchant_name,base_url')->find();
        //缩短商户的名称
        $merchant_name = $this->shortName($merchant['merchant_name']);
        //获取商户后台设置的广告图
        $ad = $this->ad($mid);
        if (!$ad) {
            $ad[0]['url']   = 'http://m.hz41319.com/wei/index.php';
            $ad[0]['intro'] = '';
            $ad[0]['thumb'] = "./themes/simplebootx/Public/pay/img/img1.jpg";
        } else {
            foreach ($ad as $k => $v) {
                $ad[$k]['thumb'] = 'http://sy.youngport.com.cn' . $v['thumb'];
            }
        }
        //获取本次消费是否能够领取会员卡1
        $memcard = $this->_get_memcard($mid, $price,$remark);
        //判断该用户是否领过该商户的会员卡
        if ($memcard) {
            if ($this->_has_memcard($openid, $memcard['card_id'])) {
                $count = 0;
            } else {
                $count = 1;
            }
        }
        //获取本次消费是否能够领取代理商联名卡
        $agent_memcard = $this->_get_agent_card($mid, $price,$remark);
        if ($agent_memcard) {
            if ($this->_has_memcard($openid, $agent_memcard['card_id'])) {
                $count += 0;
            } else {
                $count += 1;
            }
        }
        //获取消费金额是否能够领取优惠券,货架只能显示5张卡券
        $coupon = $this->coupon($mid, $price, 5 - $count, $openid);
        //查看当前订单是否使用优惠券
        if ($remark) {
            $dePrice = $this->_get_de_price($remark);
        }
        if ($order_id) {
            $info         = M('order')->where(array('order_id' => $order_id))->find();
            $yue          = $info['user_money'];
            $total_amount = $info['total_amount'];
//            $this->cardOff($order_id);
        } else {
            $total_amount = $price;
        }
        file_put_contents('./data/log/wz/weixin/' . date("Y_m_") . 'taika.log', date("Y-m-d H:i:s") . 'remark:' . $remark . ',openid:' . $openid . ',price:' . $price . PHP_EOL, FILE_APPEND | LOCK_EX);
        $this->assign('openid', $openid);
        $this->assign('wxprice', $price);
        $this->assign('price', $price + $yue);
        $this->assign('total_amount', $total_amount);
        $this->assign('merchant_name', $merchant_name);
        $this->assign('ad', $ad);
        $this->assign('coupon', $coupon);
        $this->assign('dePrice', $dePrice);
//        $this->assign('pay_time', $this->pay_model->where(array('remark' => $remark))->getField("paytime"));
        $this->assign('pay_time', time());
        $this->assign('logo', $merchant['base_url']);
        $this->assign('mid', $mid);
        $this->assign('memcard', $count);
        $this->assign('yue', $yue ?: 0);
        $this->display();
        exit;
    }

    # 台签卡券核销，仅使用余额支付，不使用微信钱包时的回调页面
    public function weixipay_return111()
    {
        $price    = 0;
        $openid   = I('openid');
        $order_id = I("order_id");
        $remark   = M("order")->where(array("order_id" => $order_id))->getField('order_sn');
        $mid      = I("mid");

        $merchant = M('merchants')->where("id=$mid")->field('merchant_name,base_url')->find();
        //缩短商户的名称
        $merchant_name = $this->shortName($merchant['merchant_name']);
        //获取商户后台设置的广告图
        $ad = $this->ad($mid);
        if (!$ad) {
            $ad[0]['url']   = 'http://m.hz41319.com/wei/index.php';
            $ad[0]['thumb'] = "./themes/simplebootx/Public/pay/img/img1.jpg";
        } else {
            foreach ($ad as $k => $v) {
                $ad[$k]['thumb'] = 'http://sy.youngport.com.cn' . $v['thumb'];
            }
        }
        //获取本次消费是否能够领取会员卡1
        $memcard = $this->_get_memcard($mid, $price);
        //判断该用户是否领过该商户的会员卡
        if ($memcard) {
            if ($this->_has_memcard($openid, $memcard['card_id'])) {
                $count = 0;
            } else {
                $count = 1;
            }
        }
        //获取消费金额是否能够领取优惠券,
        if ($count == 0) {
//货架只能显示5张卡券
            $coupon = $this->coupon($mid, $price, 5, $openid);
        } else {
            $coupon = $this->coupon($mid, $price, 4, $openid);
        }
        //查看当前订单是否使用优惠券
        if ($remark) {
            $dePrice = $this->_get_de_price($remark);
        }
//        $this->cardOff($order_id);

        $info         = M('order')->where(array('order_id' => $order_id))->find();
        $yue          = $info['user_money'];
        $total_amount = $info['total_amount'];

        file_put_contents('./data/log/wz/weixin/' . date("Y_m_") . 'taika.log', date("Y-m-d H:i:s") . 'remark:' . $remark . ',openid:' . $openid . ',price:' . $price . PHP_EOL, FILE_APPEND | LOCK_EX);
        $this->assign('openid', $openid);
        $this->assign('price', $price + $yue);
        $this->assign('total_amount', $total_amount);
        $this->assign('merchant_name', $merchant_name);
        $this->assign('ad', $ad);
        $this->assign('coupon', $coupon);
        $this->assign('dePrice', $dePrice);
        $this->assign('logo', $merchant['base_url']);
        $this->assign('mid', $mid);
        $this->assign('memcard', $count);
        $this->assign('pay_time', $this->pay_model->where(array('remark' => $remark))->getField("paytime"));
        $this->assign('yue', $yue ?: 0);
        $this->display("weixipay_return000");
        exit;
    }

    //判断该用户是否领过该商户的会员卡
    private function _has_memcard($openid, $card_id)
    {
        $data = M('screen_memcard_use')
            ->where("fromname='$openid' and card_id='$card_id'")
            ->find();
        return $data;
    }

    //获取本次消费是否能够领取会员卡
    public function _get_memcard($mid, $price,$remark='')
    {
        if($remark){
            $mode = $this->pay_model->where(array('remark'=>$remark))->getField('mode');
            #判断订单类型，检查该类型的订单会员卡设置的时候是否投放
            if($mode==11){//小程序
                $where['sc.delivery_xcx'] = 1;
            }elseif ($mode==0){//台签
                $where['sc.delivery_taiqian'] = 1;
            }elseif (in_array($mode,array(3,4,17,18))){//双屏
                $where['sc.delivery_shuangping'] = 1;
            }elseif (in_array($mode,array(6,7,8,9,19))){//POS机
                $where['sc.delivery_pos'] = 1;
            }
        }
        $where['m.id'] = $mid;
        $where['cardstatus'] = 4;
        $where['delivery_rules'] = 1;
        $where['delivery_cash'] = array('elt',$price);
        $data = M('screen_memcard')->alias('sm')
            ->join('join ypt_merchants m on sm.mid=m.uid')
            ->join('join ypt_screen_cardset sc on sm.id=sc.c_id')
            ->where($where)
            ->where("(cardnum-drawnum)>0")
            ->find();
        return $data;
    }

    //获取本次消费是否能够领代理商联名卡
    public function _get_agent_card($mid, $price,$remark='')
    {
        $uid      = M('merchants')->where(array('id' => $mid))->getField('uid');
        $agent_id = M('merchants')->alias('m')
            ->join('join __MERCHANTS_USERS__ u on m.uid=u.id')
            ->where(array('m.id' => $mid))
            ->getField('agent_id');
        if ($agent_id == 0) {
            return false;
        } else {
            if($remark){
                $mode = $this->pay_model->where(array('remark'=>$remark))->getField('mode');
                #判断订单类型，检查该类型的订单会员卡设置的时候是否投放
                if($mode==11){//小程序
                    $where['sc.delivery_xcx'] = 1;
                }elseif ($mode==0){//台签
                    $where['sc.delivery_taiqian'] = 1;
                }elseif (in_array($mode,array(3,4,17,18))){//双屏
                    $where['sc.delivery_shuangping'] = 1;
                }elseif (in_array($mode,array(6,7,8,9,19))){//POS机
                    $where['sc.delivery_pos'] = 1;
                }
            }
            $where['sm.mid'] = $agent_id;
            $where['cardstatus'] = 4;
            $where['delivery_rules'] = 1;
            $where['delivery_cash'] = array('elt',$price);
            $card = M('screen_memcard')->alias('sm')
                ->join('join ypt_screen_cardset sc on sm.id=sc.c_id')
                ->where($where)
                ->where("(cardnum-drawnum)>0")
                ->find();
            if ($card) {
                $use_merchants = explode(',', $card['use_merchants']);
                if (in_array($uid, $use_merchants)) {
                    return $card;
                } else {
                    return false;
                }
            } else {
                return false;
            }

        }
    }

    //获取当前订单是否使用优惠券
    public function _get_de_price($remark)
    {
        if (strlen($remark) != 20) {
            $remark = substr($remark, 0, -4);
        }
        $order_id = $this->pay_model->where("remark = '$remark'")->getField('order_id');
        if (!$order_id) {
            return 0;
        } else {
            $de_price = M('order')->where("order_id = $order_id")->getField('order_benefit');
            if ($de_price == 0) {
                return 0;
            } else {
                return $de_price;
            }
        }
    }

    //判断消费金额是否能够领取优惠券，如果该用户领取过则不能再领取
    public function coupon($mid, $price, $count, $openid)
    {
        $now    = time();
        $coupon = M('screen_coupons')
            ->where("card_type='GENERAL_COUPON' and mid=$mid and auto_price<=$price and status=3 and quantity>0 and is_auto=2 and begin_timestamp<=$now and end_timestamp>=$now")
            ->limit($count)
            ->select();
        if ($coupon) {
            //判断是否已经领取过
            foreach ($coupon as $k => $v) {
                $map['card_id']  = $v['card_id'];
                $map['fromname'] = $openid;
                $is_use          = M('screen_user_coupons')->where($map)->count();
                if ($is_use > 0) {
                    unset($coupon[$k]);
                }
            }
            return count($coupon);
        } else {
            return 0;
        }
    }

    //获取商户广告
    public function ad($mid)
    {
        if ($mid != 0) {
            $agent_id = M('merchants_users mu')->join('__MERCHANTS__ m on mu.id=m.uid')->where("m.id=$mid")->getField('mu.agent_id');
            $ad       = M('adver')->alias('a')
                ->field("a.url,a.thumb")
                ->join("join __MERCHANTS_USERS__ mu on a.muid=mu.id")
                ->where("mu.id != 1 and mu.id=$agent_id and a.status=1 and road=2 and kind=1 and callstyle=2")
                ->order("sort desc")
                ->limit(3)
                ->select();
        } else {
            $ad = array();
        }

        if (count($ad) < 3) {
            $limit  = 3 - count($ad);
            $ypt_ad = M('adver')
                ->field("url,thumb,intro")
                ->where("is_ypt=1 and status=1 and road=2 and kind=1 and callstyle=2")
                ->order("sort desc")
                ->limit($limit)
                ->select();
            if ($ypt_ad) {
                foreach ($ypt_ad as $k => $v) {
                    array_push($ad, $ypt_ad[$k]);
                }
            }
        }
        return $ad;
    }

    //缩短商户名称
    public function shortName($merchant)
    {
        if (strpos($merchant, "镇")) {
            $merchant_name = substr(strstr($merchant, '镇'), 3);
        } elseif (strpos($merchant, "区")) {
            $merchant_name = substr(strstr($merchant, '区'), 3);
        } elseif (strpos($merchant, "县")) {
            $merchant_name = substr(strstr($merchant, '县'), 3);
        } elseif (strpos($merchant, "市")) {
            $merchant_name = substr(strstr($merchant, '市'), 3);
        } elseif (strpos($merchant, "省")) {
            $merchant_name = substr(strstr($merchant, '省'), 3);
        } else {
            $merchant_name = $merchant;
        }
        return $merchant_name;
    }

    public function lingqu1()
    {
        $this->display();
        exit;
    }

    /**
     *    作用：使用证书，以GET方式提交参数
     */
    private function _getXmlSSLCurl($url, $second = 30)
    {

        $ch = curl_init();
        //超时时间
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        //这里设置代理，如果有的话
        //curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
        //curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, false);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //设置证书
        //使用证书：cert 与 key 分别属于两个.pem文件
        //默认格式为PEM，可以注释
        curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
        // curl_setopt($ch, CURLOPT_SSLCERT, '/alidata/www/hzsj/store/simplewind/Core/Library/Vendor/Bbshop/youngPort/cert/apiclient_cert.pem');
        curl_setopt($ch, CURLOPT_SSLCERT, '/nasdata/www/youngshop/simplewind/Core/Library/Vendor/Wzpay/cert/apiclient_cert.pem');
        //默认格式为PEM，可以注释
        curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
        //curl_setopt($ch, CURLOPT_SSLKEY, '/alidata/www/hzsj/store/simplewind/Core/Library/Vendor/Bbshop/youngPort/cert/apiclient_key.pem');
        curl_setopt($ch, CURLOPT_SSLKEY, '/nasdata/www/youngshop/simplewind/Core/Library/Vendor/Wzpay/cert/apiclient_key.pem');
        $data = curl_exec($ch);
        //返回结果
        if ($data) {
            curl_close($ch);

            return $data;
        } else {
            $error = curl_errno($ch);
            echo "curl出错，错误码:$error" . "<br>";
            echo "<a href='http://curl.haxx.se/libcurl/c/libcurl-errors.html'>错误原因查询</a></br>";
            curl_close($ch);

            return false;
        }
    }

    /**
     * 台签使用会员卡储值余额支付
     */
    public function yue_pay()
    {
        if (IS_POST) {
            M()->startTrans();
            $pass = I('pass');
            unset($_POST['pass']);
            $code   = I('card_code');
            $cateid = I('cate_id');
            $this->writlog('kaquan_hexiao.log', '----------台卡余额支付接收参数：' . json_encode($_POST));
            $pwd = M("screen_memcard_use")->where("card_code='$code'")->getField('pay_pass');
            if ($pwd == md5($pass . 'tiancaijing')) {
                $pay_yue = I("yue");
                //插入order表
                $jmt_remark                    = trim(I("jmt_remark")) ?: I('memo', '');
                $order_info                    = array();
                $order_sn                      = date('YmdHis') . mt_rand(100000, 999999); //流水号
                $order_info["order_sn"]        = $order_sn;
                $order_amount                  = I("order_amount");
                $order_info["order_amount"]    = $order_amount; //应收金额
                $order_info["pay_status"]      = 1; //支付状态为1
                $order_info["type"]            = "0"; //0为收银订单
                $order_info["order_status"]    = "5"; //1.待付款，5.交易成功
                $order_info['integral']        = I('dikoufen'); //该订单使用积分
                $order_info['integral_money']  = I('dikoujin'); //该订单使用积分抵扣金额
                $coupon_code                   = I("coupon_code", "");
                $order_info["coupon_code"]     = $coupon_code; //优惠券ID
                $order_info["coupon_price"]    = I("coupon_price"); //使用优惠券抵扣多少金额
                $order_info["order_goods_num"] = 0; //商品数量为0
                $order_info["total_amount"]    = I("total_amount"); //订单总价
                $order_info["user_money"]      = $pay_yue; //使用余额
                $mch_id                        = I('mch_id');
                $user_id                       = M('merchants')->where(array('id' => $mch_id))->getField("uid");
                $order_info["user_id"]         = $user_id;
                $order_info["add_time"]        = time();
                $order_info["discount"]        = I("discount") * 10; //整单折扣
                $order_info["order_benefit"]   = I("order_benefit"); //整单优惠金额
                $order_info["card_code"]       = $code; //会员卡号
                $order                         = M('order');
                $role_id                       = M('merchants_role_users')->where(array('uid' => $user_id))->getField('role_id');
                if ($role_id == '7') {
                    $pid         = M('merchants_users')->where(array('id' => $user_id))->getField('pid');
                    $merchant_id = M('merchants')->where(array('uid' => $pid))->getField('id');
                    $checker_id  = $user_id;
                } else {
                    $merchant_id = M('merchants')->where(array('uid' => $user_id))->getField('id');
                    $checker_id  = '0';
                }
                $order_add = $order->add($order_info);
                $openid    = I('openid');
                $customer  = D("Api/ScreenMem")->add_member($openid, $merchant_id);
                //插入pay表
                $pay_info = array(
                    "remark"      => $order_sn,
                    "customer_id" => $customer,
                    "order_id"    => $order_add,
                    "phone_info"  => $_SERVER['HTTP_USER_AGENT'],
                    "mode"        => 16,
                    "merchant_id" => $merchant_id,
                    "checker_id"  => I('checker_id')?:$checker_id,
                    "paystyle_id" => 1,
                    "price"       => $pay_yue,
                    "status"      => 1,
                    "cate_id"     => $cateid,
                    "bill_date"   => date('Ymd'),
                    "add_time"    => time(),
                    "paytime"     => time(),
                    "jmt_remark"  => $jmt_remark,
                );
                $pay     = $this->pay_model;
                $pay_add = $pay->add($pay_info);
                if ($order_add && $pay_add) {
                    A("App/PushMsg")->push_pay_message($order_sn);
                    $this->cardOff($pay_info['order_id']);
                    M()->commit();
                    $this->ajaxReturn(array('code' => 'success', 'msg' => '支付成功', 'data' => $order_add));
                } else {
                    M()->rollback();
                    $this->ajaxReturn(array('code' => 'error', 'msg' => '网络请求失败'));
                }
            } else {
                M()->rollback();
                $this->ajaxReturn(array('code' => 'error', 'msg' => '支付密码错误'));
            }
        }
    }

    /**
     * 台签卡券核销创建订单
     */
    public function cate_order()
    {
        if (IS_POST) {
            $order_sn          = date('YmdHis') . mt_rand(100000, 999999); //流水号
            $_POST['order_sn'] = $order_sn;
            $this->writlog('kaquan_hexiao.log', '-台卡核销创建订单接收参数：' . json_encode($_POST));
            $order_info                    = array();
            $order_info["order_sn"]        = $order_sn;
            $order_amount                  = I("order_amount"); //应收金额
            $order_info['integral']        = I('dikoufen'); //该订单使用积分
            $order_info['integral_money']  = I('dikoujin'); //该订单使用积分抵扣金额
            $order_info["coupon_price"]    = I("coupon_price"); //使用优惠券抵扣多少金额
            $order_info["total_amount"]    = I("total_amount"); //订单总价
            $order_info["user_money"]      = I("yue"); //使用余额
            $order_info["add_time"]        = time();
            $order_info["discount"]        = I("discount") * 10; //整单折扣
            $order_info["order_benefit"]   = I("order_benefit"); //整单优惠金额
            $code                          = I("coupon_code", "");
            $card_code                     = I("card_code", "");
            $mch_id                        = I('mch_id');
            $user_id                       = M('merchants')->where(array('id' => $mch_id))->getField("uid");
            $order_info["user_id"]         = $user_id;
            $order_info["order_amount"]    = $order_amount;
            $order_info["pay_status"]      = 0; //支付状态为1
            $order_info["type"]            = "0"; //0为收银订单
            $order_info["order_status"]    = "1"; //1.待付款
            $order_info["coupon_code"]     = $code; //优惠券ID
            $order_info["order_goods_num"] = 0; //商品数量为0
            $order_info["card_code"]       = $card_code; //会员卡号
            try {
                $res = M('order')->add($order_info);
                if ($res) {
                    $this->ajaxReturn(array('code' => 'success', 'msg' => 'OK', 'data' => $res));
                } else {
                    throw new Exception('网络错误');
                }
            } catch (Exception $e) {
                $this->ajaxReturn(array('code' => 'error', 'msg' => $e->getMessage()));
            }
        } else {
            $this->ajaxReturn(array('code' => 'error', 'msg' => '网络异常', 'data' => 'error'));
        }

    }

    //扣减订单下所有规格商品数量

    public function decrease_stock($order_id)
    {

        $order_goods_info = M('order_goods')->where(array('order_id' => $order_id))->field('sku,goods_id,goods_num,bar_code')->select();

        if (!empty($order_goods_info)) {
            foreach ($order_goods_info as $val) {
                $str_len=strlen($val['bar_code']);
                //8位或者13位不用扣库存
                if ($str_len !=8 &&$str_len!=13) {
                    continue;

                }
                $goods_id = $val['goods_id'];
                if (!$goods_id) {
                    $goods_id=M('goods')->where(array('bar_code'=>$val['bar_code']))->getField('goods_id');

                }
                if ($goods_id) {
                    $stock_number     = M('goods')->where('goods_id=' . $goods_id)->getField('goods_number');
                    $goods_num        = $val['goods_num'];
                    $new_stock_number = $stock_number - $goods_num;
                    if ($new_stock_number >= 0) {

                        $sku_goods_info = M('goods_sku')->where(array('sku_id' => $val['sku']))->find();
                        if (!empty($sku_goods_info)) {
                             $new_sku_stock  = $sku_goods_info['quantity'] - $goods_num;

                            if ($new_sku_stock >= 0) {
                                $update_goods = array('goods_number' => $new_stock_number);
                                $update_sku   = array('quantity' => $new_sku_stock);
                              $is_sku_success =  M('goods_sku')->where('sku_id=' . $val['sku'])->save($update_sku);//更新规格库存
                               $is_goods_success = M('goods')->where('goods_id='.$goods_id)->save($update_goods);//更新商品库存

                               if ($is_sku_success !== false && $is_goods_success !== false ) {//更新销量
                                    M('goods')->where('goods_id='.$goods_id)->setInc('sales',$goods_num);

                               }


                                get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/fasong/','fasong','数据', json_encode($update_goods));

                            }
                        } else {
                              $update_goods = array('goods_number' => $new_stock_number);

                                 get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/fasong/','fasong','数据', json_encode($update_goods));
                             $is_goods_success = M('goods')->where('goods_id='.$goods_id)->save($update_goods);//更新商品库存

                                 if ( $is_goods_success !== false ) {//更新销量
                                    M('goods')->where('goods_id='.$goods_id)->setInc('sales',$goods_num);

                               }


                        }

                    } else {
                        $this->ajaxReturn(array("code" => "error", 'msg' => '库存不足'));
                    }

                }

            }

        }

    }

    //核销优惠券、扣会员卡余额、积分
    public function cardOff($order_id)
    {
//        $order_sn = I('order_sn');
        $order = M('order')->where("order_id='$order_id'")->find();
        #判断该笔订单是否已核销
        if ($order['is_cancel'] == 1) {
            return array("code" => "success", 'msg' => '该笔订单已核销');
        }
        $order_sn    = $order['order_sn'];
        $coupon_code = $order['coupon_code']; //优惠券code
        $card_code   = $order['card_code']; //会员卡code
        $price       = $order['order_amount'] + $order['user_money']; //订单应付金额（优惠后的价格）
        $dikoufen    = $order['integral']; //会员卡使用的积分
        $yue         = $order['user_money']; //会员卡使用的余额

        $save['update_time']  = time();
        $save['pay_time']     = time();
        $save['order_status'] = '5';
        $save['pay_status']   = '1';
        $save['pay_time']     = time();
        $save['is_cancel']    = 1;
        $add                  = M('order')->where("order_id='$order_id'")->save($save);
      

        //主扫生成的订单号改成order_sn
        //        $remark = I('remark');
        //        if($remark){
        //            $orderId = M('order')->where("order_sn='$order_sn'")->getField('order_id');
        //            $this->pay_model->where("remark='$remark'")->setField('order_id',$orderId);
        //            M('order')->where("order_sn='$order_sn'")->setField('order_sn',$remark);
        //            $pay_id = $this->pay_model->where("remark='$remark'")->getField('id');
        //        }else{
        //            $mode = $this->pay_model->where("remark='$order_sn'")->getField('mode');
        //            if($mode=='3'){$this->pay_model->where("remark='$order_sn'")->setField('mode',1);}
        //            $pay_id = $this->pay_model->where("remark='$order_sn'")->getField('id');
        //        }

        //核销优惠券
        if ($coupon_code) {
            $url          = "https://api.weixin.qq.com/card/code/consume?access_token=" . get_weixin_token();
            $data['code'] = $coupon_code;
            $use_coupon   = request_post($url, json_encode($data));
            $this->writlog('kaquan_hexiao.log', "-----台卡核销优惠券url:{$url};\n参数：" . json_encode($data));
            $result = json_decode($use_coupon, true);
            M("screen_user_coupons")->where("usercard=$coupon_code")->setField('status', '0');
            if ($result['errmsg'] != "ok") {
                $coupon_off = false;
                $this->writlog('kaquan_hexiao.log', '-核销失败：' . $use_coupon);
            } elseif ($result['errmsg'] == "ok") {
                $this->writlog('kaquan_hexiao.log', '-核销成功：' . $use_coupon);
                $coupon_off = true;
            }
        }

        //会员卡
        if ($card_code) {
            $card = M("screen_memcard_use")->alias('u')
                ->join('left join ypt_screen_memcard m on u.card_id=m.card_id')
                ->field('m.id,m.is_agent,m.credits_set,m.expense,m.expense_credits,m.expense_credits_max,m.merchant_name,u.fromname,u.card_balance,u.yue,u.card_id,u.card_amount,m.level_set,m.level_up,u.id as smu_id,u.level')
                ->where("u.card_code='$card_code'")
                ->find();
            //会员卡消费送积分
            if ($card['credits_set'] == 1) {
                $send = floor($price / $card['expense']) * $card['expense_credits'];
                //如果送的积分大于最多可送的分
                if ($send > $card['expense_credits_max']) {
                    $send = $card['expense_credits_max'];
                }
            }
            #如果使用联名卡，给商家加上储值
            if ($card['is_agent'] == 1) {
                $role_id = M('merchants_role_users')->where(array('uid' => $order['user_id']))->getField('role_id');
                if ($role_id == 3 && $order['user_money'] > 0) {
//商家
                    #1.8版本先扣增加余额扣掉手续费，2018.4.11
                    $card_rate = M('merchants_users')->where(array('id' => $order['user_id']))->getField('card_rate');
                    $inc_price = $order['user_money'] * $card_rate / 100;
                    M('merchants_users')->where(array('id' => $order['user_id']))->setInc('card_balance', $inc_price);
                    #余额日志
                    M('balance_log')->add(array('price' => $inc_price, 'ori_price' => $order['user_money'], 'rate_price' => $order['user_money'] - $inc_price, 'order_sn' => $order_sn, 'add_time' => time(), 'remark' => '核销异业联盟卡', 'mid' => $order['user_id'], 'balance' => M('merchants_users')->where(array('id' => $order['user_id']))->getField('balance')));
                }
            }
            //获取商户的等级信息,level_set等级设置，level_up是否可升级
            if ($card['level_set'] == 1 && $card['level_up'] == 1) {
                //获取该会员的单次消费expense_single，累计消费expense，累计积分card_amount
                $field                   = 'ifnull(sum(order_amount),0) as expense,ifnull(max(order_amount),0) as expense_single';
                $mem_info                = M('order')->where(array('card_code' => $card_code, 'order_status' => '5'))->field($field)->find();
                $mem_info['card_amount'] = M("screen_memcard_use")->where(array('entity_card_code|card_code'=>$card_code))->getField('card_amount');
                //会员卡所有等级列表
                #充值记录信息，recharge累计充值金额，recharge_single单次充值最大金额
                $recharge_info = M('user_recharge')
                    ->where(array('memcard_id' => $card['smu_id'], 'status' => 1))
                    ->field('ifnull(sum(real_price),0) as recharge,ifnull(max(real_price),0) as recharge_single')
                    ->find();
                $mem_info      = array_merge($mem_info, $recharge_info);
                $memcard_level = M('screen_memcard_level')->where(array('c_id' => $card['id']))->order('level asc')->select();
                foreach ($memcard_level as &$value) {
                    $type = explode(',', $value['level_up_type']);
                    foreach ($type as &$val) {
                        #会员当前等级信息,current_level当前等级,current_level_name当前等级名称
                        $level = $this->get_level($val, $mem_info, $value);
                        if ($level) {
                            $current_level      = $level['current_level'];
                            $current_level_name = $level['current_level_name'];
                            break;
                        }
                    }
                }
            }
            if ($current_level && $current_level > $card['level']) {
                M("screen_memcard_use")->where("card_code='$card_code'")->setField(array('level' => $current_level));
                $ts['custom_field_value2'] = urlencode($current_level_name); //会员卡名称
            }

            //card_balance，会员卡剩余积分
            $member_data['card_balance'] = $card['card_balance'] + $send - $dikoufen;
            //card_balance，会员卡总积分
            $member_data['card_amount'] = $card['card_amount'] + $send;
            if ($yue && $card['yue'] != 0) {
                $member_data['yue'] = ($card['yue'] * 100 - $yue * 100) / 100;
                if ($member_data['yue'] < 0) {
                    $member_data['yue'] = 0;
                }

            } else {
                $member_data['yue'] = 0;
            }
            $card_off                  = M("screen_memcard_use")->where("card_code='$card_code'")->save($member_data);
            $ts['code']                = urlencode($card_code);
            $ts['card_id']             = urlencode($card['card_id']);
            $ts['custom_field_value1'] = urlencode($member_data['yue']); //会员卡余额
            $token = get_weixin_token();
            request_post('https://api.weixin.qq.com/card/membercard/updateuser?access_token=' . $token, urldecode(json_encode($ts)));

            # 使用了储值支付，需要给消费者微信推送消息
            if($yue > 0 && $card['fromname']){
                A('Wechat/Message')->use_balance($card['fromname'],$card_code,$yue,$card['merchant_name'],$member_data['yue']);
            }

            if ($dikoufen > 0) {
                $ts["add_bonus"]                                              = urlencode('-' . $dikoufen); //增加的积分，负数为减
                $ts["record_bonus"]                                           = urlencode('台卡消费使用积分'); //增加的积分，负数为减
                $dikoufen_ts_res                                              = request_post('https://api.weixin.qq.com/card/membercard/updateuser?access_token=' . $token, urldecode(json_encode($ts)));
                $dikoufen_ts_result                                           = json_decode($dikoufen_ts_res, true);
                $dikoufen_ts_result['errcode'] == 0 ? $dikoufen_ts_result_msg = 1 : $dikoufen_ts_result_msg = 0;
                $this->writlog('kaquan_hexiao.log', '-使用积分更新会员信息参数：' . urldecode(json_encode($ts)) . PHP_EOL . "返回：{$dikoufen_ts_res}");
                M('screen_memcard_log')->add(array('add_time' => time(), 'update_time' => time(), 'value' => $send, 'balance' => $card['card_balance'] + $send, 'ts' => json_encode($ts), 'order_sn' => $order_sn, 'code' => $card_code, 'ts_status' => $dikoufen_ts_result_msg, 'msg' => $dikoufen_ts_res));
            }
            if ($send > 0) {
                $ts["add_bonus"]    = urlencode($send); //增加的积分，负数为减
                $ts["record_bonus"] = urlencode('台卡消费赠送积分'); //增加的积分，负数为减
                $send_ts_res        = request_post('https://api.weixin.qq.com/card/membercard/updateuser?access_token=' . $token, urldecode(json_encode($ts)));
                $this->writlog('kaquan_hexiao.log', '-赠送积分更新会员信息参数：' . urldecode(json_encode($ts)) . PHP_EOL . "返回：{$send_ts_res}");
                $send_ts_result                                       = json_decode($send_ts_res, true);
                $send_ts_result['errcode'] == 0 ? $send_ts_result_msg = 1 : $send_ts_result_msg = 0;
                M('screen_memcard_log')->add(array('add_time' => time(), 'update_time' => time(), 'value' => '-' . $dikoufen, 'balance' => $card['card_balance'] - $dikoufen, 'ts' => json_encode($ts), 'order_sn' => $order_sn, 'code' => $card_code, 'ts_status' => $send_ts_result_msg, 'msg' => $send_ts_res));
            }
        }

        if ($coupon_off || $card_off || $add) {
            $this->decrease_stock($order_id);//扣减库存
            return array("code" => "success", 'msg' => '核销成功');
        } else {
            return array("code" => "error", 'msg' => '核销失败');
        }
    }
    //获取会员当前等级信息
    private function get_level($type, $up_info, $level_info)
    {
        switch ($type) {
            case 1:
                if ($up_info['recharge_single'] >= $level_info['level_recharge_single']) {
                    $level['current_level']          = $level_info['level'];
                    $level['current_level_name']     = $level_info['level_name'];
                    $level['current_level_discount'] = $level_info['level_discount'];
                    return $level;
                }
                break;
            case 2:
                if ($up_info['recharge'] >= $level_info['level_recharge']) {
                    $level['current_level']          = $level_info['level'];
                    $level['current_level_name']     = $level_info['level_name'];
                    $level['current_level_discount'] = $level_info['level_discount'];
                    return $level;
                }
                break;
            case 3:
                if ($up_info['expense_single'] >= $level_info['level_expense_single']) {
                    $level['current_level']          = $level_info['level'];
                    $level['current_level_name']     = $level_info['level_name'];
                    $level['current_level_discount'] = $level_info['level_discount'];
                    return $level;
                }
                break;
            case 4:
                if ($up_info['expense'] >= $level_info['level_expense']) {
                    $level['current_level']          = $level_info['level'];
                    $level['current_level_name']     = $level_info['level_name'];
                    $level['current_level_discount'] = $level_info['level_discount'];
                    return $level;
                }
                break;
            case 5:
                if ($up_info['card_amount'] >= $level_info['level_integral']) {
                    $level['current_level']          = $level_info['level'];
                    $level['current_level_name']     = $level_info['level_name'];
                    $level['current_level_discount'] = $level_info['level_discount'];
                    return $level;
                }
                break;
            default:
                if ($level_info['level'] == 1) {
                    $level['current_level']      = $level_info['level'];
                    $level['current_level_name'] = $level_info['level_name'];
                } else {
                    $level = null;
                }
                return $level;
        }
    }

    private function writlog($file_name, $data)
    {
        $path = $this->get_date_dir();
        file_put_contents($path . $file_name, date("H:i:s") . $data . PHP_EOL . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    private function get_date_dir($path = '/data/log/member/')
    {
        $Y = $_SERVER['DOCUMENT_ROOT'] . $path . date("Y-m");
        $d = $Y . '/' . date("Y-m-d");
        if (!file_exists($Y)) {
            mkdir($Y, 0777, true);
        }

        if (!file_exists($d)) {
            mkdir($d, 0777);
        }

        return $d . '/';
    }
}
