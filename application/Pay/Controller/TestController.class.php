<?php
/**
 *
 * Created by PhpStorm.
 * User: Joan
 * Date: 2018/6/20
 * Time: 12:26
 */
namespace Pay\Controller;

use Common\Controller\HomebaseController;
use Common\Lib\Subtable;

/**
 * 用于处理回调后的测试服处理.
 * Class TestController
 * @package Pay\Controller
 */
class TestController extends HomebaseController
{
    /**
     *测试
     */
    public function test()
    {
        header("Content-Type: text/html;charset=utf-8");
        echo '客户端IP ', get_client_ip(), "\r\n\r\n";
        echo '服务器IP ', $_SERVER['SERVER_ADDR'], "\r\n\r\n";
        $pay1 = Subtable::getSubTableName('pay', '');# 获取分表表名,不返回表前缀
        $pay2 = Subtable::getSubTableName('pay', '', '');# 获取分表表名，返回表前缀
        $pay3 = Subtable::getSubTableName('pay', array('order_sn' => '20180516172351463811'), '');# 获取分表表名，根据订单号
        echo $pay1, "\r\n", $pay2, "\r\n", $pay3, "\r\n";
        //$sqlAll = Subtable::getSubTableUnionSql('', '',2);# 返回基于分表后完整的sql语句

        M('order o')
            ->join('ypt_pay p on p.remark=o.order_sn', 'left')
            //->join('(' . $sqlAll . ')p  ON p.remark = o.order_sn', 'left')
            ->field('(o.order_amount+o.user_money) as price,o.add_time,o.pay_time as paytime,p.paystyle_id,p.status')
            ->where('card_code is not null')
            // ->limit(40,20)
            ->select(false);
        $baseSql = M()->_sql();
        //echo $baseSql;exit;
        $sqlAll = Subtable::getSubTableUnionSql('pay', $baseSql);
        $rs = M()->query($sqlAll);
        echo $sqlAll, "\r\n", count($rs), "\r\n";
        //print_r($rs);
        //Vendor('Wzpay.Wzpay');
        // (new \Wzpay())->notify();
    }

    public function reg()
    {
        #  curl进件
        if (class_exists('\CURLFile')) {#php5.5及以上
            $fieldname = new \CURLFile($_SERVER['DOCUMENT_ROOT'] . '/image/bank.png', 'image/jpeg', 'bank.png');
            echo 11;
        } else {#php5.5以下
            $fieldname = '@' . realpath('image/bank.png') . ';type=image/png';
        }
        $param = array(
            'idCardBackPhoto' => $fieldname,
        );

        $result = $this->http_post($param, 'http://b.ypt5566.com/api/zhang/index');#收款
        var_dump($result);
    }

    /**
     * 公众号支付扫码支付收款
     */
    public function wx_pay()
    {
        // 得到输入的金额和商户的ID
        header("Content-type:text/html;charset=utf-8");
        Vendor('WxPayPubHelper.WxPayPubHelper');
        $jsApi = new \JsApi_pub();
        $mchid = '1490433412';
        $sub_openid = "oyaFdwGG6w5U-RGyeh1yWOMoj5fM";
        $price = 0.01;
        $remark = date('YmdHis') . rand(100000, 999999);
        $good_name = '111';
        //使用统一支付接口
        $unifiedOrder = new \UnifiedOrder_pub();
        echo '<pre/>';
        //设置统一支付接口参数
        //设置必填参数
        //appid已填,商户无需重复填写
        //mch_id已填,商户无需重复填写
        //noncestr已填,商户无需重复填写
        //spbill_create_ip已填,商户无需重复填写
        //sign已填,商户无需重复填写
        $unifiedOrder->setParameter("openid", "$sub_openid");//openid和sub_openid可以选传其中之一
//        $unifiedOrder->setParameter("sub_openid", "$sub_openid");//子商户appid下的唯一标识
        $unifiedOrder->setParameter("body", $good_name);//商品描述
        //自定义订单号，
        $price = $price * 100;
        $unifiedOrder->setParameter("out_trade_no", "$remark");//商户订单号
        $unifiedOrder->setParameter("total_fee", "$price");//总金额
        $unifiedOrder->setParameter("notify_url", \WxPayConf_pub::NOTIFY_URL);//通知地址
        $unifiedOrder->setParameter("trade_type", "JSAPI");//交易类型
        $unifiedOrder->setParameter("sub_mch_id", $mchid);//子商户号服务商必填

        print_r($unifiedOrder);
        $prepay_id = $unifiedOrder->getPrepayId();
        var_dump($prepay_id);
        //=========步骤3：使用jsapi调起支付============
        $jsApi->setPrepayId($prepay_id);
        $jsApiParameters = $jsApi->getParameters();
        print_r($jsApiParameters);
        $this->assign('jsApiParameters', $jsApiParameters);
        $this->assign('price', $price);
        $this->assign('remark', $remark);
        $this->assign('openid', $sub_openid);
        $this->assign('mid', 1);
        $this->display('wx_pay1');
    }


    public function http_post($post_data, $url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        var_dump(curl_getinfo($ch));
        $result = curl_exec($ch);
        var_dump(curl_errno($ch));

        curl_close($ch);
        return $result;
    }


    /**
     * 模拟银行推送,回调写入redis，消息队列轮询
     */
    public function notify()
    {
        //接收银行推送
        $json_str = file_get_contents('php://input', 'r');
        if ($json_str) {
            //返回银行,终止推送
            echo 'SUCCESS<br/><br/>';
            echo '<pre/>';
            set_time_limit(0); // 取消脚本运行时间的超时上限

            $post_data = $json_str;
            $notify_url = 'http://test.ypt5566.com/api/test/test';//推送地址
            $result = $this->http_post($post_data, $notify_url);//第一次推送

            //推送给商户
//            if ($result != '0000') {
//                //第二次推送,两次推送商家无返回,则写入redis队列
//                $result = $this->http_post($post_data, $notify_url);
            if ($result != '0000') {
                $this->redis_curl(array("type" => 'notify', "url" => $notify_url, "data" => $post_data));
                exit;
            } else echo '该改状态,商户有返回<br/>';
            //}

            //商户有返回,更改回调状态
            //if ($result == '0000') M('api_pay')->where(array('remark' => $remark, 'status' => 1))->save(array('notify_status' => 2));
        }
    }


    /**
     * 测试redis
     */
    public function redis_curl($param = array())
    {
        set_time_limit(0); // 取消脚本运行时间的超时上限

        Vendor('Cache.MyRedis');
        $redis = new \MyRedis();

        //队列键
        $content_key = 'API_NOTIFY_CONTENT';
        //$redis->del($content_key);//清除redis

        //有传值则加入redis,没有则为定时任务
        //异步通知,每个订单的异步通知实行分频率发送:15s 3m 10m 30m 30m 1h 2h 6h 15h

        //轮询队列时间间隔
        $frequency_arr = array(
            '1' => '15',
            '2' => '180',
            '3' => '600',
            '4' => '1800',
            '5' => '1800',
            '6' => '3600',
            '7' => '7200',
            '8' => '21600',
            '9' => '54000',
        );

        echo '<pre/>';

        if ($param) {
            $post_data = array(
                'url' => $param['url'],//请求的商户url
                'data' => json_decode($param['data'], true),//入列数据
                'time' => time(),//入列时间
                'turn' => '1'//默认为1
            );
            $list1 = $redis->lset("$content_key", json_encode($post_data));
            $list2 = $redis->lget("$content_key", 0, 100000);
            var_dump($list1);
            var_dump($list2);
        } else {

            $count = 0;
            //判断回调是否返回'0000'
            $notify_tag = false;
            // 获取现有消息队列的长度
            $max = $redis->lLen("$content_key");

            var_dump($max);

            // 存储商户回调无返回的数组
            $roll_back_arr = array();

            while ($count < $max) {
                //先进先出,取出队列一个
                $json_info = $redis->ldel("$content_key");
                if (empty($json_info)) {
                    echo '队列已完成<br/>';
                    break;
                }

                $content = json_decode($json_info, true);

                //判断轮询的时间是否符合时间间隔
                if (time() - $content['time'] > $frequency_arr[$content['turn']]) {
                    $result = $this->http_post(json_encode($content['data']), $content['url']);
                    if ($result == '0000') {
                        $notify_tag = true;
                        echo 'ok <br/>';
                        break;
                    } else {
                        echo '商户无返回!<br/>';
                        $content['turn'] += 1;//轮询次数累加
                    }
                }
                //小于10次且无返回的继续入队轮询
                if ($content['turn'] < 10 && !$notify_tag) $roll_back_arr[] = json_encode($content);
                echo $count . '次<br/>';
                $count++;
            }


            if (count($roll_back_arr) > 0) {
                foreach ($roll_back_arr as $k) {
                    //商户无返回的加入队列
                    $redis->lset("$content_key", $k, false);
                }
            }

            //查看队列所有
            $list_data = $redis->lget("$content_key", 0, 10000000);
            var_dump($list_data);

        }


    }

    /**
     * 微众支付
     * 公账号支付
     */
    public function wz_pay()
    {

//        得到输入的金额和商户的ID
        header("Content-type:text/html;charset=utf-8");
        vendor('Wzpay.Wzpay');

        $wzPay = new \Wzpay();

        $mid = I('mid') / 100;
//        先获取openid防止 回调
        if (I("seller_id") == "") {
            $openid = $this->_get_openid();
            $sub_openid = $openid;
            $id = I("id");
            $res = M('merchants_cate')->where("id=$id")->find();
            $price = I("price");
            $data['mode'] = 1;
            $data['checker_id'] = I("checker_id");
        }
        if (I('seller_id') !== "") {
            $sub_openid = I('openid');
            $id = I('seller_id');
            $res = M('merchants_cate')->where("id=$id")->find();
            $price = I('price');
            $data['checker_id'] = $res['checker_id'];
            $data['mode'] = 0;
        }


        $data['bank'] = 1;
        if (I("checker_id")) {
            $data['checker_id'] = I("checker_id");
        } //app上的台签带上收银员的信息
        if (I("jmt_remark")) { //金木堂定单号
            $data['jmt_remark'] = I("jmt_remark");
        }
        $wzcost_rate = M("merchants_upwz")->where("mid=" . $res['merchant_id'])->getField("WxCostRate");
        if ($wzcost_rate) {
            $data['cost_rate'] = $wzcost_rate;
        };
        $data['bill_date'] = date("Ymd", time());

        $remark = date('YmdHis') . rand(100000, 999999);
        //            插入数据库的数据
        $data['merchant_id'] = (int)$res['merchant_id'];
        //$data['customer_id'] = $sub_openid;
        //$data['customer_id'] = D("Api/ScreenMem")->add_member("$sub_openid", $res['merchant_id']);
        $data['paystyle_id'] = 1;
        $data['price'] = $price;
        $data['remark'] = $remark;
        $data['status'] = 0;
        $data['cate_id'] = $res['id'];
        $data['phone_info'] = $_SERVER['HTTP_USER_AGENT'];
        $data['paytime'] = time();
        $good_name = "向" . $res['jianchen'] . "支付" . $price . "元";
        $data['subject'] = $good_name;

//       支付订单提交的数据交互
        $mchid = $res['wx_mchid'];
        //使用统一支付接口()
        $wzPay->setParameter('sub_openid', $sub_openid);
        $wzPay->setParameter('mch_id', $mchid);
        $wzPay->setParameter('body', $good_name);
        $wzPay->setParameter('out_trade_no', $remark);
        $wzPay->setParameter('goods_tag', 1213);
        $wzPay->setParameter('total_fee', $price * 100);
        echo '服务器IP ' . $_SERVER['SERVER_ADDR'] . '  <br/>';
        $returnData = $wzPay->getParameters();
        var_dump($returnData);

    }

    /**
     * 获取jssdk需要用到的数据
     * @return array jssdk需要用到的数据
     */
    public function get_openid()
    {
        // 获取配置项
        //$config = C('WEIXINPAY_CONFIG');
        $config['APPID'] = 'wx8b17740e4ea78bf5';
        $config['APPSECRET'] = 'bbd06a32bdefc1a00536760eddd1721d';
        $redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . $_SESSION['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $redirect_uri = urlencode($redirect_uri);
        $url = "http://m.hz41319.com/redirect/get-weixin-code.html?appid=" . $config['APPID'] . "&scope=snsapi_base&state=hello-world&redirect_uri=" . $redirect_uri;
        // 如果没有get参数没有code；则重定向去获取openid；
        if (!isset($_GET['code'])) {
            redirect($url);
        } else {
            //如果有code参数；则表示获取到openid
            $code = I('get.code');
            var_dump($_GET['code']);
            //组合获取prepay_id的url
            $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid= ' . $config['APPID'] . '&secret=' . $config['APPSECRET'] . '&code=' . $code . '&grant_type=authorization_code';
            //curl获取prepay_id
            $result = $this->http_post(array(), $url);

            var_dump($result);
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

    //微信支付界面跳转
    public function qr_weixipay()
    {
        $id = I("id");
        $merchant = M("merchants_cate")->where("id=$id")->find();
        $openid = $this->_get_openid();
        //if(!$openid)$openid = $this->_get_openid();
        $this->assign('openid', $openid);
        $this->assign("merchant", $merchant);
        $this->assign('seller_id', I('id'));
        $this->display();
    }


}



