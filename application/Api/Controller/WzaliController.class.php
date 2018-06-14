<?php
/**
 * Date: 2017/8/31
 * Time: 16:22
 */

namespace Api\Controller;

use Think\Controller;

class WzaliController extends Controller
{
    //请求参数
    private $parameters;

    //=======支付宝配置=====================================
    //【证书路径】,注意应该填写绝对路径
    const SSLCERT_PATH = '/youngshop/simplewind/Core/Library/Vendor/Wzpay/cert/apiclient_cert.pem';

    const SSLKEY_PATH = '/youngshop/simplewind/Core/Library/Vendor/Wzpay/cert/apiclient_key.pem';
    //商户入驻
    const REGMCH_RUL = 'https://svrapi.webank.com/api/acq/server/alipay/regmch';
    //获取access_token
    const ACCESS_TOKEN = 'https://svrapi.webank.com/api/oauth2/access_token';
    //获取api_ticket
    const TICKETS = 'https://svrapi.webank.com/api/oauth2/api_ticket';
    //扫码支付
    const PAY_TO = 'https://svrapi.webank.com/api/acq/server/alipay/precreatetrade';
    //退款
    const RE_FUND ='https://svrapi.webank.com/api/acq/server/alipay/refund';

    //支付订单查询
    const QUERY_TRADE = 'https://svrapi.webank.com/api/acq/server/alipay/querytrade';

    //条码支付
    const PAY_BARCODE = 'https://svrapi.webank.com/api/acq/server/alipay/pay';

    //取消订单
    const PAY_CANCEL = 'https://svrapi.webank.com/api/acq/server/alipay/cancel';

    //渠道号
    const APP_ID = 'W9816632';
    //密匙
    const SECRET = '3Bb5UBtEZQCdzrKg9y3FZjjPj7Ik64p8ncGyu07hjgAraqgGHymCYSet4pOCuSVM';
    //商户号,商户入驻所得
    const  MERCHANTID = '103584000030000';

    //版本号
    const VERSION = '1.0.0';
    //类型
    const GRANT_TYPE = 'client_credential';

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
        curl_setopt($ch, CURLOPT_SSLCERT, self::SSLCERT_PATH);
        //默认格式为PEM，可以注释
        curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLKEY, self::SSLKEY_PATH);
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
        $str = self::ACCESS_TOKEN . '?' . $str;
        //S(array('type' => 'xcache', 'prefix' => 'think_wz', 'expire' => 6600));

        $model = M("pay_token");
        $result = $model->lock(true)->where(array("type" => "1"))->find();
        if (!$result['access_token'] || $result['a_time'] + 6600 < time()) {//
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
        $str = self::TICKETS . '?' . $str;

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
    public function pay_for($parameter='')
    {
        $parameter = array(
            'wbMerchantId'=>11, //商户id
            'orderId' => md5(time()), //订单号
            'totalAmount'=>0.01,
            'subject'=> 'test'
        );
        $parameter = json_encode($parameter);
        $url = self::PAY_TO;
        $pay_info = $this->getParameters($url, $parameter);
        $pay_info = json_decode($pay_info, true);
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

        $url = self::RE_FUND;
        $pay_info = $this->getParameters($url, $parameter);
        $pay_info = json_decode($pay_info, true);
        file_put_contents('./data/log/wz/ali/' . date("Y_m_") . 'tuikuan.log', date("Y-m-d H:i:s") . '   ' . "退款结果" . json_encode($pay_info) . PHP_EOL, FILE_APPEND | LOCK_EX);

        if ($pay_info['code'] == "0"&&$pay_info['success']==true) {
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

        $url = self::PAY_BARCODE;
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
            'wbMerchantId' => self::MERCHANTID,
            'orderId' => $orderId,
        );

        $url = self::PAY_CANCEL;
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

        $this->setParameter('app_id', self::APP_ID);
        $this->setParameter('secret', self::SECRET);
        $this->setParameter('grant_type', self::GRANT_TYPE);
        $this->setParameter('version', self::VERSION);

        $this->setParameter('Jsonbody', $this->encode_json($parameter));

        $access_token = $this->get_access_token();//获取access_token

        $this->parameters["ticket"] = $this->get_sign_ticket($access_token);//获取ticket

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
    public function queryOrder($orderId)
    {
        $arr = array(
            'wbMerchantId' => self::MERCHANTID,
            'orderId' => $orderId,
        );

        $url = self::QUERY_TRADE;
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
        $this->getParameters(self::REGMCH_RUL, $parameter);
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
        if(mb_strlen($orderId)>='23')$orderId=mb_substr($orderId,4,22,'utf-8');
        $info = $this->pay_model->lock(true)->where(array('remark' => $orderId, 'status' => 0))->find();
        return $info;
    }

    public function regmch($res)
    {
        //实例
        //判断账号性质
        if ($res['companyFlag'] == '00') $res['companyFlag'] = '02';

        $parameter = array(

            'productType' => '003',//1支付类型*

            'registerType' => '01',//2普通模式商户有代理商填写“01”商户无代理商(商户直连模式)填写“02”*

            'merchantInfo' => array(

                //'agencyId' => '1070755003',//3.1代理商编号，微众银行提供。（商户直连模式不用填写）*//1035840014
                'agencyId' => '1035840014',//3.1代理商编号，微众银行提供。（商户直连模式不用填写）*//1035840005

                'appId' =>self::APP_ID,   //3.2渠道号，微众银行提供*

                'idType' => '01',//3.3商户法人的证件类型（如：身份证，军人军官证)，*

                'idNo' => $res['idNo'] ? $res['idNo'] : '142431197406040055',//3.4商户法人证件号码*

                'merchantName' => $res['merchantName'] ? $res['merchantName'] : '深圳前海洋仆淘电子商务有限公司',//3.5商户名称*

                'legalRepresent' => $res['legalRepresent'] ? $res['legalRepresent'] : '郭卫栋',//3.6法人代表*

                'licenceNo' => $res['licenceNo'] ? $res['licenceNo'] : '91440300360065211Y',//3.7营业执照编号*

                'licenceBeginTime' => '',//3.8执照开始时间，格式“2012-12-12”

                'licenceEndTime' => '',//3.9执照结束时间，格式“2015-12-12”

//                'taxRegisterNo' => $res['taxRegisterNo'] ? $res['taxRegisterNo'] : '91440300360065211Y',//3.10税务登记号*
                'taxRegisterNo' => $res['taxRegisterNo'] ? $res['taxRegisterNo'] : '',//3.10税务登记号*

                'positionCode' => '0',//3.11单位代码，如果没有填“0”*

                'contactName' => $res['contactName'] ? $res['contactName'] : '蒋莉芬',//3.12联系人姓名*

                'contactPhoneNo' => $res['contactPhoneNo'] ? $res['contactPhoneNo'] : '13912341234',//3.13联系人电话， 格 式“13912341234”*

                'mainBusiness' => $res['mainBusiness'] ? $res['mainBusiness'] : '电子商务',//3.14主营业务*

                'businessRange' => $res['businessRange'] ? $res['businessRange'] : '在网上从事商贸活动；母婴用品、化妆品、玩具、文具用品、日用品、成人用品、健身器材、体育用品、珠宝首饰、工艺礼品、电脑及配件、家用电器、服装、鞋帽、针纺织品、箱包、厨房和卫生间用具、包装材料的销售；国内贸易、经营进出口业务；从事广告业务（以上根据法律、行政法规、国务院决定等规定需要审批的，依法取得相关审批文件后方可经营）。^婴儿辅食、乳粉、乳制品（含婴幼儿配方奶粉）、保健食品、预包装食品的销售。',//3.15经营范围*

                'registerAddr' => $res['registerAddr'] ? $res['registerAddr'] : '深圳市前海深港合作区前湾一路1号A栋201室（入驻深圳市前海商务秘书有限公司）',//3.16注册地址*

                'merchantTypeCode' => $res['merchantTypeCode'] ? $res['merchantTypeCode'] : '0003',//3.17添加商户类别码（经营类目，填类目号，根据类目标填写），*

                'merchantLevel' => '2',//3.18默认填 1*

                'parentMerchantId' => '',//3.19可不填

                'merchantNature' => $res['merchantNature'] ? $res['merchantNature'] : '私营企业',//3.20商户性质（国有企业，三资企业，私营企业，集体企业)*

                'contractNo' => '',//3.21合同编号

                'openYear' => '',//3.22商户开业时间，格式“2012-12-12”

                'categoryId' => $res['categoryId'] ? $res['categoryId'] : '0003',//3.23类目（支付宝，见数据字典）*

            ),
            'merchantAccount' => array(

                'accountNo' => $res['bankAccout'] ? $res['bankAccout'] : '755929903810201',//4.1商户银行账号*

                'accountOpbankNo' => $res['revactBankNo'] ? $res['revactBankNo'] : '308584000013',//4.2账户开户行号*

                'accountName' => $res['bankAccoutName'] ? $res['bankAccoutName'] : '深圳前海洋仆淘电子商务有限公司',//4.3开户户名*

                'accountOpbank' => $res['bankName'] ? $res['bankName'] : '招商银行',//4.4开户行*

                'accountSubbranchOpbank' => '',//4.5开户支行

                'accountOpbankAddr' => '',//4.6开户地址

                'acctType' => $res['companyFlag'] ? $res['companyFlag'] : '01',//4.7账户类型（01 对公，02 对私）*

                'settlementCycle' => '1',//4.8清算周期（默认填为 1）
            ),
            'merchantRateList' => array(

                array(
                    'paymentType' => '20',//5.1支付类型不允许重复填写*

                    'settlementType' => '11',//5.2结算方式（默认 01）*

                    'chargeType' => '02',//5.3计费算法:01 固定金额、02 固定费率（默认填写 02）*

                    'commissionRate' => $res['wxCostRate'] ? $res['wxCostRate'] : 0.8,//5.4回拥费率（chargeType 为 02时必填）（0.6%代表千分之六）

                ),
                array(
                    'paymentType' => '21',//5.1支付类型不允许重复填写*

                    'settlementType' => '11',//5.2结算方式（默认 01）*

                    'chargeType' => '02',//5.3计费算法:01 固定金额、02 固定费率（默认填写 02）*

                    'commissionRate' => $res['wxCostRate'] ? $res['wxCostRate'] : 0.8,//5.4回拥费率（chargeType 为 02时必填）（0.6%代表千分之六）

                ),
            ),
            'aliasName' => $res['merchantAlis'] ? $res['merchantAlis'] : '洋仆淘跨境商城',//6商户简称（ registerType 为“02”时必填）

            'servicePhone' => $res['servicePhone'] ? $res['servicePhone'] : '075566607274',//7客服电话（ registerType 为“02”时必填）

            'contactPhone' => $res['contactPhone'] ? $res['contactPhone'] : '075566607274',//8联系人座机（registerType 为“02”时选填）

            'district' => $res['merchantArea'] ? $res['merchantArea'] : '5840',//13地区号，请参考数据字典（如深圳：0755）*
        );

        $rs = $this->get_merchantId($parameter);
    }

    public function test()
    {
        $json = '';
        $url = 'http://www.baidu.com';
        //初始化
        //
        $ch = curl_init($url);
        ////设置选项，包括URL
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, true);
//        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
//        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

//        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
//                'Content-Type: application/json',
//                'Content-Length: ' . strlen($json))
//        );
        //执行并获取HTML文档内容
        $output = curl_exec($ch);


        curl_close($ch);

        //打印获得的数据
        print_r($output);


    }
}