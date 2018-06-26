<?php
/**
 * Created by PhpStorm.
 * User: Joan
 * Date: 2017/3/13
 * Time: 14:34
 */

/**
 *        createNoncestr()，产生随机字符串，不长于32位
 *        formatBizQueryParaMap(),格式化参数，签名过程需要用到
 *        getSign(),生成签名
 **/

include_once("AlipayConfig_old.php");//微众支付配置文件

class Wz_pay
{
    //请求参数
    private $parameters;

    public function __construct()
    {

    }


    /**
     * @param $arr 要加密的数组
     * @param $sign 当前使用的key
     * @return string 生成签名
     */
    private function getSign($arr = array())
    {
        if (!$arr["ticket"]) exit('ticket不存在!');
        if ($arr["sign"]) unset($arr["sign"]);
        //过滤null和空
        $Parameters = array_filter($arr, function ($v) {
            if ($v === null || $v === '') {
                return false;
            }
            return true;
        });

        //签名步骤一：按字典序排序参数
        sort($Parameters);
        $String = implode($Parameters);
        file_put_contents('./data/log/wz/ali/' . date("Y_m_") . 'zhifubao_curl.log', date("Y-m-d H:i:s") . '扫码支付:sign签名参数字符串' . implode('||', $Parameters) . PHP_EOL, FILE_APPEND | LOCK_EX);
        //签名步骤二：sha1加密
        $result_ = sha1($String);

        //签名步骤三：所有字符转为大写
        $result_ = strtoupper($result_);

        return $result_;
    }


    /**作用：格式化参数，签名过程需要使用
     * @param array $paraMap
     * @param $urlencode
     * @return string
     */
    private function formatBizQueryParaMap($paraMap = array(), $urlencode)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if ($urlencode) {
                $v = urlencode($v);
            }
            //$buff .= strtolower($k) . "=" . $v . "&";
            $buff .= $k . "=" . $v . "&";
        }
        $reqPar = '';
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }

        return $reqPar;
    }

    /**作用：产生随机字符串，不长于32位
     * @param int $length
     * @return string
     */
    private function createNoncestr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**作用：使用证书，以post方式提交json字符串到对应的接口url，默认post
     * @param $url
     * @param $json
     * @param string $method 发送方式
     * @param int $second
     * @return bool|mixed
     */
    private function postCurl($url, $json, $method = 'POST', $second = 30)
    {
        file_put_contents('./data/log/wz/ali/' . date("Y_m_") . 'zhifubao_curl.log', date("Y-m-d H:i:s") . '扫码支付curl信息:请求url' . $url . '请求参数' . $json . PHP_EOL, FILE_APPEND | LOCK_EX);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($json))
        );
        //设置证书
        //使用证书：cert 与 key 分别属于两个.pem文件
        //默认格式为PEM，可以注释
        curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLCERT, AlipayConfig::SSLCERT_PATH);
        //默认格式为PEM，可以注释
        curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLKEY, AlipayConfig::SSLKEY_PATH);
        $result = curl_exec($ch);
        file_put_contents('./data/log/wz/ali/' . date("Y_m_") . 'zhifubao_curl.log', date("Y-m-d H:i:s") . '扫码支付curl信息:请求结果' . $result . PHP_EOL, FILE_APPEND | LOCK_EX);
        //返回结果
        if ($result) {
            curl_close($ch);
            return $result;
        } else {
            $error = curl_errno($ch);
            echo "curl出错，错误码:$error" . "<br>";
            curl_close($ch);
            return false;
        }

    }


    /**作用：设置请求参数
     * @param $parameter
     * @param $parameterValue
     */
    public function setParameter($parameter, $parameterValue)
    {
        $this->parameters[$parameter] = $parameterValue;
    }


    /**获取微众支付宝访问令牌（access token）
     * @return string
     */
    private function get_access_token()
    {
        $str = $this->formatBizQueryParaMap($this->parameters, false);
        $str = \AlipayConfig::ACCESS_TOKEN . '?' . $str;
        //S(array('type' => 'xcache', 'prefix' => 'think_wz', 'expire' => 6600));

        $model = M("pay_token");
        $result = $model->lock(true)->where(array("type" => "1"))->find();
        if (!$result['access_token'] || $result['a_time'] + 6600 < time()) {//判断是否存
            $res = $this->postCurl($str, '', 'GET');
            $res = json_decode($res, true);
            if ($res['code'] != 0) {
                exit($res['msg']);
            }

            M()->startTrans();//开启事务
            if ($model->lock(true)->where(array("type" => "1"))->save(array("access_token" => $res['access_token'], "a_time" => time()))) {
                M()->commit();//事务提交
            } else {
                M()->rollback();//回滚
            }
            return $res['access_token'];

        } else
            return $result['access_token'];

    }

    /**获取微众支付宝访问tickets
     * @param $access_token
     * @return string
     */
    private function get_sign_ticket($access_token)
    {
        if (!$access_token) exit('缺少access_token');

        $str = $this->formatBizQueryParaMap(array(
            'app_id' => $this->parameters['app_id'],
            'access_token' => $access_token,
            'type' => 'SIGN',
            'version' => $this->parameters['version'],
        ), false);
        $str = \AlipayConfig::TICKETS . '?' . $str;

        //S(array('type' => 'xcache', 'prefix' => 'think_wz', 'expire' => 3000));

        $model = M("pay_token");
        $result = $model->lock(true)->where(array("type" => "1"))->find();

        if (!$result['tickets'] || $result['t_time'] + 3000 < time()) {//判断是否存
            $res = $this->postCurl($str, '', 'GET');
            $res = json_decode($res, true);
            if ($res['code'] != 0) {
                exit($res['msg']);
            }
            $tickets = $res['tickets'][0]['value'];
            M()->startTrans();//开启事务
            if ($model->lock(true)->where(array("type" => "1"))->save(array("tickets" => $tickets, "t_time" => time()))) {
                M()->commit();//事务提交
            } else {
                M()->rollback();//回滚
            }

            return $tickets;
        } else
            return $result['tickets'];

    }


    /**发起扫码支付
     * @param $parameter
     */
    public function pay_for($parameter)
    {

        $url = \AlipayConfig::PAY_TO;
        $pay_info = $this->getParameters($url, $parameter);
        $pay_info = json_decode($pay_info, true);
        print_r($pay_info);
        if ($pay_info['code'] == 0) {
            $url = $pay_info['qrCode'];
            header("Location: $url");
        } else
            die($pay_info['msg']);
    }

    /**退款
     * @param $parameter
     */
    public function pay_back($parameter)
    {

        $url = \AlipayConfig::RE_FUND;
        $pay_info = $this->getParameters($url, $parameter);
        $pay_info = json_decode($pay_info, true);
        file_put_contents('./data/log/wz/ali/' . date("Y_m_") . 'tuikuan.log', date("Y-m-d H:i:s") . '   ' . "退款结果" . json_encode($pay_info) . PHP_EOL, FILE_APPEND | LOCK_EX);

        if ($pay_info['code'] == "0" && $pay_info['success'] == true) {
            return array('flag' => true, 'message' => $pay_info);
        } else
            return array('flag' => false, 'message' => $pay_info);
    }

    /**发起条码支付
     * @param $parameter
     */
    public function pay_bar_code($parameter)
    {
        set_time_limit(0);

        $url = \AlipayConfig::PAY_BARCODE;
        $pay_info = $this->getParameters($url, $parameter);
        $pay_info = json_decode($pay_info, true);
        //print_r(json_encode($pay_info));
        if ($pay_info['code'] == '0' && $pay_info['retCode'] == '10000' && $pay_info['retMsg'] == 'Success') {//支付成功
            file_put_contents('./data/log/wz/ali/' . date("Y_m_") . 'zhifubao.log', date("Y-m-d H:i:s") . '   ' . "免密支付成功" . json_encode($pay_info) . PHP_EOL, FILE_APPEND | LOCK_EX);
            return array('flag' => true, 'message' => $pay_info);

        } else if ($pay_info['retCode'] == '10003') {//需要输密码

            $queryTimes = 12;

            while ($queryTimes > 0) {

                $queryResult = $this->queryOrder($parameter['orderId']);
                $result = $queryResult['info'];
                $tradeStatus = $result['tradeStatus'];

                file_put_contents('./data/log/wz/ali/' . date("Y_m_") . 'zhifubao_pay_num_curl.log', date("Y-m-d H:i:s") . '   ' . "密码支付次数_:" . $queryTimes . PHP_EOL, FILE_APPEND | LOCK_EX);
                //如果需要等待5s后继续
                if ($tradeStatus == '00') {//交易处理中
                    sleep(5);
                    $queryTimes--;
                    continue;
                } else if ($tradeStatus == '03') {//交易创建，等待买家付款
                    sleep(5);
                    $queryTimes--;
                    continue;
                } else if ($tradeStatus == '04') {//未付款交易超时关闭，或支付完成后全额退款
                    sleep(5);
                    $queryTimes--;
                    continue;
                } else if ($tradeStatus == '01') {//交易支付成功

                    file_put_contents('./data/log/wz/ali/' . date("Y_m_") . 'zhifubao.log', date("Y-m-d H:i:s") . '   ' . "密码支付成功" . json_encode($result) . PHP_EOL, FILE_APPEND | LOCK_EX);
                    return array('flag' => true, 'message' => $result);
                } else {

                    //订单交易失败
                    file_put_contents('./data/log/wz/ali/' . date("Y_m_") . 'zhifubao.log', date("Y-m-d H:i:s") . '   ' . "密码支付失败" . json_encode($result) . PHP_EOL, FILE_APPEND | LOCK_EX);
                    return array('flag' => false, 'message' => $result);
                }
            }


        }

        if (!$this->cancel($parameter['orderId'])) {
            file_put_contents('./data/log/wz/ali/' . date("Y_m_") . 'zhifubao.log', date("Y-m-d H:i:s") . '   ' . "订单交易时间过长" . $parameter['orderId'] . PHP_EOL, FILE_APPEND | LOCK_EX);
            return array('flag' => false, 'message' => '订单交易时间过长，请重新支付');

        } else {
            file_put_contents('./data/log/wz/ali/' . date("Y_m_") . 'zhifubao.log', date("Y-m-d H:i:s") . '   ' . "已撤销订单" . $parameter['orderId'] . PHP_EOL, FILE_APPEND | LOCK_EX);
            return array('flag' => false, 'message' => '订单交易时间过长，已撤销订单');
        }

    }

    /**取消订单
     * @param $orderId
     * @param int $depth
     * @return bool
     */
    private function cancel($orderId, $depth = 0)
    {

        if ($depth > 5) {
            return false;
        }
        $arr = array(
            'wbMerchantId' => \AlipayConfig::MERCHANTID,
            'orderId' => $orderId,
        );

        $url = \AlipayConfig::PAY_CANCEL;
        $result = $this->getParameters($url, $arr);

        file_put_contents('./data/log/wz/ali/' . date("Y_m_") . 'zhifubao_order.log', date("Y-m-d H:i:s") . '   ' . "订单撤销时间" . $result . PHP_EOL, FILE_APPEND | LOCK_EX);
        $result = json_decode($result, true);

        //如果结果为success且不需要重新调用撤销，则表示撤销成功
        if ($result['retMsg'] === 'Success' && $result['retryFlag'] === 'N') {
            return true;
        } else if ($result['retryFlag'] == 'Y') {
            sleep(3);
            return $this->cancel($orderId, ++$depth);
        }

        return false;


    }

    /**接口公用参数
     * 生成签名,调接口
     * @param $alipay_config
     * @param $parameter
     */
    public function getParameters($url, $parameter)

    {

        $this->setParameter('app_id', \AlipayConfig::APP_ID);
        $this->setParameter('secret', \AlipayConfig::SECRET);
        $this->setParameter('grant_type', \AlipayConfig::GRANT_TYPE);
        $this->setParameter('version', \AlipayConfig::VERSION);

        $this->setParameter('Jsonbody', $this->encode_json($parameter));

        $access_token = $this->get_access_token1();//获取access_token

        $this->parameters["ticket"] = $this->get_sign_ticket1($access_token);//获取ticket

        $this->parameters["nonce"] = $this->createNoncestr();//随机字符串

        unset($this->parameters['secret']);
        unset($this->parameters['grant_type']);

        $this->parameters['sign'] = $this->getSign($this->parameters);//获取签名

        unset($this->parameters['Jsonbody']);
        unset($this->parameters['grant_type']);
        unset($this->parameters['secret']);
        unset($this->parameters['ticket']);

        $str = $this->formatBizQueryParaMap($this->parameters);//转成&拼接的参数值对
        $str = $url . '?' . $str;//拼接带签名的url
        $json = $this->encode_json($parameter);//将数组的参数转成json字符串

        $result = $this->postCurl($str, $json);//发起支付

        return $result;

    }

    /**扫码支付回调
     * @return array|mixed
     */
    public function notify()
    {
        // 获取json
        $json_str = file_get_contents('php://input', 'r');
        //存储回调的来源信息
        $from_arr = array(
            'HTTP_USER_AGENT' => $_SERVER['HTTP_USER_AGENT'],
            'QUERY_STRING' => $_SERVER['QUERY_STRING'],
            'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'],
            'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'],
        );
        file_put_contents('./data/log/wz/ali/' . date("Y_m_") . 'zhifubao.log', PHP_EOL . date("Y-m-d H:i:s") . '【回调开始】' . '扫码支付资源来源:' . json_encode($from_arr) . PHP_EOL, FILE_APPEND | LOCK_EX);

        if (!$json_str) exit('访问错误');

        // 转成php数组
        $result = json_decode($json_str, true);
        file_put_contents('./data/log/wz/ali/' . date("Y_m_") . 'zhifubao.log', date("Y-m-d H:i:s") . '扫码支付回调信息' . $result['type'] . $result['data'] . PHP_EOL, FILE_APPEND | LOCK_EX);

        $data = json_decode($result['data'], true);
        $fundBillList = str_replace('\\', '', stripslashes($data['fundBillList']));
        $fundBillList = json_decode($fundBillList, true);

        //var_dump($fundBillList[0]['amount']);//具体金额
        $ab = M("pay")->where(array('remark' => $data['orderId']))->getField('id');
        if (!$ab) {
            //$url = 'https://api.youngport.com.cn/api/wzbank/wx_notify_return';
            $url = 'http://apiadmin.ypt5566.com/index/curl/ali_notify_return';
            $request1 = $this->postarrayCurl($json_str, $url);
            echo $request1;
            exit;
        }
        if ($data['tradeStatus'] == '00') {//回调处理中,主动查询订单       
            $result = $this->queryOrder($data['orderId']);
            if ($result['tag']) return $result['info'];
            else return false;
        } else if ($data['tradeStatus'] == '01') {//支付成功
            M()->startTrans();//开启事务
            $order_info = $this->get_order($data['orderId']);
            if ($order_info) {
                if ($order_info['status'] == '1') file_put_contents('./data/log/wz/ali/' . date("Y_m_") . 'zhifubao.log', date("Y-m-d H:i:s") . '订单号' . $data['orderId'] . '重复回调' . PHP_EOL, FILE_APPEND | LOCK_EX);
                else {
                    if (bccomp(floatval($data['totalAmount']), floatval($order_info['price']), 2) === 0) {//核对金额
                        file_put_contents('./data/log/wz/ali/' . date("Y_m_") . 'zhifubao.log', date("Y-m-d H:i:s") . '回调成功' . json_encode($order_info) . PHP_EOL, FILE_APPEND | LOCK_EX);
                        $data['tag'] = true;
                        M()->commit();//事务提交
                        echo 200;
                        return $data;
                    } else
                        file_put_contents('./data/log/wz/ali/' . date("Y_m_") . 'zhifubao.log', date("Y-m-d H:i:s") . '订单号' . $data['orderId'] . '付款金额不一致' . PHP_EOL, FILE_APPEND | LOCK_EX);
                }


            } else {
                M()->rollback();//回滚
                file_put_contents('./data/log/wz/ali/' . date("Y_m_") . 'zhifubao.log', date("Y-m-d H:i:s") . '订单号' . $data['orderId'] . '不存在' . PHP_EOL, FILE_APPEND | LOCK_EX);
            }


        }

        return false;
    }

    /**
     * 微众——支付宝——主扫对账单
     */
    public function bill_notice()
    {
        // 获取json
        $json_str = file_get_contents('php://input', 'r');
        file_put_contents('./data/log/wz/ali/' . date("Y_m_") . 'zhifubao.log', date("Y-m-d H:i:s") . '扫码支付-对账单-资源来源' . $_SERVER['HTTP_REFERER'] . PHP_EOL, FILE_APPEND | LOCK_EX);
        file_put_contents('./data/log/wz/ali/' . date("Y_m_") . 'zhifubao.log', date("Y-m-d H:i:s") . '扫码支付-对账单-回调信息' . $json_str . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    /**查询订单
     * @param $orderId
     * @return bool|mixed
     */
    public function queryOrder($orderId, $MerchantId)
    {
        $arr = array(
            'wbMerchantId' => $MerchantId ?: \AlipayConfig::MERCHANTID,
            'orderId' => $orderId,
        );

        $url = \AlipayConfig::QUERY_TRADE;
        $pay_info = $this->getParameters($url, $arr);

        file_put_contents('./data/log/wz/ali/' . date("Y_m_") . 'zhifubao_order.log', date("Y-m-d H:i:s") . '查询订单号' . $orderId . $pay_info . PHP_EOL, FILE_APPEND | LOCK_EX);
        $pay_info = json_decode($pay_info, true);

        $order_info = $this->get_order($orderId);

        if ($order_info) {
            if (bccomp(floatval($pay_info['totalAmount']), floatval($order_info['price']), 2) === 0 && $pay_info['tradeStatus'] == '01' && $pay_info['success'] == 'true') {//核对金额
                return array("tag" => true, "info" => $pay_info);
            }

        }
        return array("tag" => false, "info" => $pay_info);
    }

    /**商户入驻
     * @param $str
     * @param $json
     */
    public function get_merchantId($parameter)
    {
        $this->getParameters(\AlipayConfig::REGMCH_RUL, $parameter);
    }

    /**将数组转成json字符串，并且中文不转码
     * @param $str
     * @return string
     */
    private function encode_json($str)
    {
        return urldecode(json_encode($this->url_encode($str)));
    }

    /**urlencode中文字符
     * @param $str
     * @return array|string
     */
    private function url_encode($str)
    {
        if (is_array($str)) {
            foreach ($str as $key => $value) {
                $str[urlencode($key)] = $this->url_encode($value);
            }
        } else {
            $str = urlencode($str);
        }

        return $str;
    }

    private function get_order($orderId)
    {
        if (mb_strlen($orderId) >= '23') $orderId = mb_substr($orderId, 4, 22, 'utf-8');
        $info = M('pay')->lock(true)->where(array('remark' => $orderId, 'status' => 0))->find();
        return $info;
    }

    /**获取微众支付宝访问_token
     * @return string
     */
    /*private function get_access_token1()
    {

        // 取消脚本运行时间的超时上限
        set_time_limit(0);
        Vendor('Cache.MyRedis');
        $redis = new \MyRedis();
        //获取存入redis的token的值，以渠道号做键名
        $re=$redis->get($this->parameters['app_id'].'_token');
        //redis数据过期就重新获取数据
        if (!$re) {
            $str = $this->formatBizQueryParaMap($this->parameters, false);
            $str = \AlipayConfig::ACCESS_TOKEN . '?' . $str;
            $res = $this->postCurl($str, '', 'GET');
            $res = json_decode($res, true);
            $re=$res['access_token'];
            //存入redis，设置有效期
            $redis->set($this->parameters['app_id'].'_token',$re,6600);
            return $re;
        }
        return $re;
    }*/

    /**获取微众支付宝访问tickets
     * @param $access_token
     * @return string
     */
    /*private function get_sign_ticket1($access_token)
    {
        // 取消脚本运行时间的超时上限
        set_time_limit(0);
        Vendor('Cache.MyRedis');
        $redis = new \MyRedis();
        //获取存入redis的token的值，以渠道号做键名
        $re=$redis->get($this->parameters['app_id'].'_ticket');
        if(!$re){
            $str = $this->formatBizQueryParaMap(array(
                'app_id' => $this->parameters['app_id'],
                'access_token' => $access_token,
                'type' => 'SIGN',
                'version' => $this->parameters['version'],
            ), false);
            $str = \AlipayConfig::TICKETS .'?'. $str;
                $res = $this->postCurl($str, '', 'GET');
                $res = json_decode($res, true);
                $re=$res['tickets']['0']['value'];
                $redis->set($this->parameters['app_id'].'_ticket',$re,3000);
                return $re;
        }
        return  $re;
    }*/

    /**获取微众支付宝访问_token
     * @return string
     */
    private function get_access_token1()
    {

        // 取消脚本运行时间的超时上限
        set_time_limit(0);
        Vendor('Cache.MyRedis');
        $redis = new \MyRedis();
        //获取存入redis的token的值，以渠道号做键名
        $re = $redis->get($this->parameters['app_id'] . '_token');
        //redis数据过期就重新获取数据
        if (!$re) {
            $str = $this->formatBizQueryParaMap($this->parameters, false);
            $str = \AlipayConfig::ACCESS_TOKEN . '?' . $str;
            $res = $this->postCurl($str, '', 'GET');
            $res = json_decode($res, true);
            if (!isset($res['access_token'])) {
                $queryTimes = 8;
                while ($queryTimes > 0) {
                    $re = $redis->get($this->parameters['app_id'] . '_token');
                    if (!$re) {
                        sleep(1);
                        $queryTimes--;
                        continue;
                    } else {
                        return $re;
                    }
                }
            }
            $re = $res['access_token'];
            //存入redis，设置有效期
            $redis->set($this->parameters['app_id'] . '_token', $re, 6600);
            return $re;
        }
        return $re;
    }

    /**获取微众支付宝访问tickets
     * @param $access_token
     * @return string
     */
    private function get_sign_ticket1($access_token)
    {
        // 取消脚本运行时间的超时上限
        set_time_limit(0);
        Vendor('Cache.MyRedis');
        $redis = new \MyRedis();
        //获取存入redis的token的值，以渠道号做键名
        $re = $redis->get($this->parameters['app_id'] . '_ticket');
        if (!$re) {
            $str = $this->formatBizQueryParaMap(array(
                'app_id' => $this->parameters['app_id'],
                'access_token' => $access_token,
                'type' => 'SIGN',
                'version' => $this->parameters['version'],
            ), false);

            $str = \AlipayConfig::TICKETS . '?' . $str;
            $res = $this->postCurl($str, '', 'GET');
            $res = json_decode($res, true);
            //获取tickets失败时去读取redis
            if (!isset($res['tickets']['0']['value'])) {
                $queryTimes = 8;
                while ($queryTimes > 0) {
                    $re = $redis->get($this->parameters['app_id'] . '_ticket');
                    if (!$re) {
                        sleep(1);
                        $queryTimes--;
                        continue;
                    } else {
                        return $re;
                    }
                }
            }
            $re = $res['tickets']['0']['value'];
            $redis->set($this->parameters['app_id'] . '_ticket', $re, 3000);
            return $re;
        }
        return $re;
    }

}