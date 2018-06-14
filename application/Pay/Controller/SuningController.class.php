<?php

namespace Api\Controller;

use Think\Controller;

/**
 * 苏宁易付宝付款接口
 * Class SuningController
 * @package Api\Controller
 */
class SuningController extends Controller
{
    private $path;          // 日志写入路径
    private $url;           // 付款请求地址
    private $notifyUrl;     // 异步通知地址
    private $queryurl;      // 查询付款状态地址
    private $signAlgorithm; // 签名方式
    private $inputCharset;  // 字符编码
    private $merchantNo;    // 商户号
    private $publicKeyIndex;// 用于验签
    private $currency;      // 币种编码
    private $privateKey;    // 私钥
    private $publicKey;     // 公钥
    private $productCode;   // 产品编码
    private $totalNum;      // 总笔数
    private $totalAmount;   // 总付款金额
    private $batchNo;       // 批次号
    private $payDate;       // 付款时间
    private $goodsType;     // 商品类型编码

    public function __construct()
    {
        parent::__construct();
        $this->path = $_SERVER['DOCUMENT_ROOT'] . '/data/log/SuNing/';
        # 生产环境URL: https://wag.yifubao.com/epps-wag/withdraw.htm
        # 测试环境URL: https://wagtestpre.suning.com/epps-wag/withdraw.htm
        $this->url = 'https://wag.yifubao.com/epps-wag/withdraw.htm';
        $this->queryurl = 'https://wag.yifubao.com/epps-wag/withdrawQuery.htm';
        $this->signAlgorithm    = 'RSA';            // 签名算法
        $this->inputCharset     = 'UTF-8';          // 编码类型
//        $this->merchantNo       = '70057278';       // 70179317交易发起方的商户号，即 接入易付宝的卖家或者 平台商的商户号  ---------测试
        $this->merchantNo       = '70179317';           // 交易发起方的商户号，即 接入易付宝的卖家或者 平台商的商户号
        $this->publicKeyIndex   = '0001';           // 商户上传公钥时由易付宝统一分配，用于验签
        $this->productCode      = '01070000042';    // 产品编码(易付宝分配) 01070000042
        $this->goodsType        = '220029';         // 商品类型编码
        $this->currency         = 'CNY';            // 币种编码
        $this->notifyUrl        = 'http://api.youngport.com.cn/Api/Suning/notify';
        $this->privateKey       = '-----BEGIN RSA PRIVATE KEY-----
MIICXQIBAAKBgQCp/uFnCBHoWeKAnEJDe8rHTIPxvRZ5FKWhe8P21xDjZB8K9axg
tISRpq45z8Hg/cV65/hROWSKi81yx9FJnfaq2gECSuTB+k68oY2i6B9hBSP6aA4R
uYf/kVwOPO/sEI5m605k+pCgyg6ae5QLBd5iyZ08cHAMoXH2uAl+HbcFXQIDAQAB
AoGBAKDdee0m3NNUI5vywl3bykMYrA9ZEOgZWrdaSFHQqMGVoC6d1rZYfM7bvSkl
6eEAJB0vYHN0bSkSLKN+ZRVV7vGpsSywwMmTZ5nn2+ei8gRGsOgoRQXN6r5a82ev
Odr6F9kUV3KPd77Kc4KWFNHNVmRPkbhYDhZZVJI3WqIhGxCBAkEA0bj22fKxl//9
wF+xOXJAlWFT+du7plzbKW0v7iW1+Cq2ABap1rxXqm3eqRt+m8QO3+sgghHO3asa
U7q4BSh74QJBAM+Bx7HDliwvm4r6MMckzlaEee9h9aKOoKtgLkZlSIbMkWSsxAHN
O+8gv3stL5b3222JYBZ7NY6IBhRt6JgHmP0CQCqwV67tc7DY7rHlyJFE7Fh7wzgs
vfmTFRlNnGABVRT6vKkv88o99CpAyv3pFtBmDBEQL0HKli0Q0v8QFr0WDIECQDxa
7TCBSQ61EjLMLp/dzr5Pbf/4qC+N+KGgKhWDpCLBIZD8x04W2dXl2owDSpJIqWQk
zvP7BkrcuZf7l+mQXCkCQQCjmW4uvQO9uaEbEzPweRivDx1/SCWLxHeGbC8AwB19
doPag8pcDk+p4t8GyabzxFXIxATkJIfmK5EVsFi4uLhU
-----END RSA PRIVATE KEY-----
';
//        $this->publicKey        = '-----BEGIN PUBLIC KEY-----
//MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCmlcnbcT+nOWsRwCNMdJdCfGhW
//1WmYRfBJpUJGQRtuQsJRtRqe1XvTzkn7H2z9x4pRyKA7k9R63ZilYBMIUVgaR4zD
//GOQqb5O8RrWa/8o/obQH8cZ/be0vd0IXni7gDeVjtaU441tuXQdGpUC4BLuLGM4U
//8TvNLzPiZxlVi3eJ+wIDAQAB
//-----END PUBLIC KEY-----';  //测试公钥
        $this->publicKey = '-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDCbFR1mQQxAnXtzEZIp/Lo4RVz
U2c/FGCc7QoRHqBQTAxRXtn+n94ldgQBauDNm+nMu5UtsS0r+hXfaeTdJrhJ7pMZ
Uy90kjLdvmzJ5EbjoQGoJdCzmthWBNvRD+m2tAAxYbDb0mcCpvor93RIkbkcphZu
dCvkG8+/xAfNmJdyZQIDAQAB
-----END PUBLIC KEY-----';  // 生产公钥
    }

    # 付款方法
    public function withdraw($param)
    {
        M()->startTrans();
        $postData = array();
        $postData['merchantNo']     = $this->merchantNo;
        $postData['publicKeyIndex'] = $this->publicKeyIndex;
        $postData['inputCharset']   = $this->inputCharset;
        # 组织付款详细数据
        $body = $this->getBody($param);
        $postData['body'] = json_encode($body);
        # 获取签名
        $signature = $this->getSign($postData);
        if (!$signature) err('内部系统签名错误');

        $postData['signature']      = $signature;
        $postData['signAlgorithm']  = $this->signAlgorithm;
        # 发送请求
        $curlData = http_build_query($postData);
        get_date_dir($this->path, "withdraw", 'SendData', $curlData);
        $result = $this->sendRequest($this->url, $curlData);
        get_date_dir($this->path, "withdraw", 'ReturnData', $result);
        # 将返回结果转化为数组
        $resultArray = json_decode($result, true);
        # 判断返回信息
        if ($resultArray['responseCode'] == '0000') {
            # 苏宁已受理
            $return = array("info" => array('result_code' => 'SUCCESS','message' => '已受理'));
            M()->commit();
            succ($return);
        } else {
            # 返回错误信息
            M()->rollback();
            $msg = $resultArray['responseMsg'];
            if(!$msg){
                $msg = $resultArray[$this->batchNo.'_'.$this->merchantNo]['responseMsg'];
            }
            err($msg);
        }
    }

    public function query($serialNo)
    {
        $orderInfo = M('api_withdraw_suning')->field('batchNo,id')->where(array('serialNo' => $serialNo))->find();
        $id = $orderInfo['id'];
        if($orderInfo){
            $postData = array();
            $postData['merchantNo']     = $this->merchantNo;
            $postData['publicKeyIndex'] = $this->publicKeyIndex;
            $postData['inputCharset']   = $this->inputCharset;
            $postData['batchNo']        = $orderInfo['batchno'];
            $postData['payMerchantNo']  = $this->merchantNo;
            # 获取签名
            $signature = $this->getSign($postData);
            if (!$signature) err('系统签名错误');

            $postData['signature']      = $signature;
            $postData['signAlgorithm']  = $this->signAlgorithm;
            # 发送请求
            $curlData = http_build_query($postData);
            # 写入请求参数日志
            get_date_dir($this->path, "query_status", 'SendData', $curlData);
            # 发送请求
            $result = $this->sendRequest($this->queryurl, $curlData);
            # 写入接口返回数据日志
            get_date_dir($this->path, "query_status", 'ReturnData', $result);
            # 将返回结果转化为数组
            $resultArray = json_decode($result, true);
            # 判断返回信息
            if ($resultArray['responseCode'] == '0000') {
                # 查询成功
                $content = $resultArray['content'];
                # 付款详情
                $detailArr = $content['transferOrders'];
                $info = array();        // 接口返回数据
                $updateData = array();  // 更改数据库数据
                foreach ($detailArr as $v) {
                    if($v['serialNo'] == $serialNo){
                        # 付款状态字段
                        $info['success'] = $v['success'];
                        $updateData['success'] = $v['success'];
                        if(isset($v['payTime'])){
                            # 付款时间 付款成功才会返回
                            $updateData['pay_time'] = strtotime($v['payTime']);
                        }
                    }
                }
                # 将查询结果更改到数据库
                $updateData['status'] = $content['status']; // 该批次付款状态
                M('api_withdraw_suning')->where(array('id' => $id))->save($updateData);
                # 返回查询结果
                $info['serialNo']  = $serialNo;
                $info['message'] = '查询成功';
                $info['result_code'] = 'SUCCESS';
                $return = array("info" => $info);
                succ($return);
            } else {
                # 返回错误信息
                err($resultArray['responseMsg']);
            }
        } else {
            err('订单不存在');
        }
    }

    private function getBody($param)
    {
        $body['batchNo']        = $this->getOrderSn();      // 批量付款批次号
        $body['merchantNo']     = $this->merchantNo;        // 付款方商户号
        $body['productCode']    = $this->productCode;       // 产品编码(易付宝分配， 长度为11位)
        $body['totalNum']       = $param['totalNum'];       // 批量付款总笔数（至少1 笔）
        $body['totalAmount']    = $param['totalAmount'];    // 付款总金额，单位：分
        $body['currency']       = $this->currency;          // 币种编码，目前必须传 递”CNY”
        $body['payDate']        = date('Ymd');       // 支付时间。格式：8位字 符，yyyyMMdd
        $body['notifyUrl']      = $this->notifyUrl;         // 易付宝服务器主动通知 商户网站里指定的页面 URL路径
//        $body['tunnelData']     = array();         // 可不传 用来和业务方协商的业 务扩展字段(最大容量512{“welfarismDesc”: “春节福利"}
//        $body['batchOrderName'] = '';              // 可不传 批次订单名称（最大长度 256字节) 春节福利费
        $body['goodsType']      = $this->goodsType;              // 可不传 商品类型编码（最大长度8字 节）
        $this->getInsertData($body);
        $detailData = $this->getDetailData($param['detail']);
        $body['detailData'] = $detailData;         //  json 对象数组。数组的长 度应和 totalNum 的值匹 配
        get_date_dir($this->path, "withdraw", 'SendData', $body['batchNo']);

        return array($body);
    }

    private function getDetailData($detail)
    {
        # 将付款详情转化为数组
        get_date_dir($this->path, "sign", 'SendData', $detail);
        $detailArr = json_decode($detail, true);
        $detailData = array();  // 请求数据
        $insertData = array();  // 入库数据
        foreach ($detailArr as $k => $param) {
            $data['serialNo']           = $param['serialNo'];  //流水号
            $data['receiverCardNo']     = $param['receiverCardNo'];  //收款方卡号
            $data['receiverName']       = urldecode($param['receiverName']);  //收款方户名
            $data['receiverType']       = $param['receiverType'];  //收款方类型（PERSON：个人， CORP：企业）
            $data['receiverCurrency']   = $this->currency;  //收款方币种（默认：CNY 人民币）
            $data['bankName']           = urldecode($param['bankName']);  //开户行名称
            $data['bankCode']           = $param['bankCode'];  //开户行编号
            $data['amount']             = $param['amount'];  //付款金额， （单位：分）
            if(isset($param['bankProvince']) && !empty($param['bankProvince'])){
                $data['bankProvince']       = urldecode($param['bankProvince']);  //开户行省
            }
            if(isset($param['bankCity']) && !empty($param['bankCity'])){
                $data['bankCity']           = urldecode($param['bankCity']);  //开户行市
            }
            if(isset($param['payeeBankLinesNo']) && !empty($param['payeeBankLinesNo'])){
                $data['payeeBankLinesNo']   = $param['payeeBankLinesNo'];  //联行号
            }
            if(isset($param['remark']) && !empty($param['remark'])){
                $data['remark']         = urldecode($param['remark']);
            }
            $detailData[] = $data;  // 请求数据
            $insertData[] = $this->getInsertDatas($data);   // 入库数据
        }
        # 数据入库
        M('api_withdraw_suning')->addAll($insertData);

        return $detailData;
    }

    # 组织入库数据公共部分
    private function getInsertData($param)
    {
        $this->totalNum     = $param['totalNum'];       // 批量付款总笔数（至少1 笔）
        $this->totalAmount  = $param['totalAmount'];    // 付款总金额，单位：分
        $this->batchNo      = $param['batchNo'];        // 批量付款批次号
        $this->payDate      = $param['payDate'];        // 批量付款批次号
    }

    # 组织入库数据
    private function getInsertDatas($data)
    {
        $insertData = $data;
        $insertData['batchNo']      = $this->batchNo;
        $insertData['merchantNo']   = $this->merchantNo;
        $insertData['productCode']  = $this->productCode;
        $insertData['totalNum']     = $this->totalNum;
        $insertData['totalAmount']  = $this->totalAmount;
        $insertData['payDate']      = $this->payDate;
        $insertData['status']       = '01';

        return $insertData;
    }

    public function notify()
    {
        $notifyData = file_get_contents("php://input");
        get_date_dir($this->path, "notify", '通知数据', $notifyData);
        parse_str($notifyData, $notifyData);
        get_date_dir($this->path, "notify", 'Content', $notifyData['content']);
        $sign = str_replace(array('-','_'),array('+','/'),$notifyData['sign']);
        # 删除不需要签名的字段
        unset($notifyData['sign']);
        unset($notifyData['sign_type']);
        unset($notifyData['vk_version']);
        $verify = $this->getVerifySign($notifyData, $sign);
        if($verify){
            $content = json_decode($notifyData['content'], true);
            $status = $content['status']; // 该批次付款状态
            # 付款详情
            $detailArr = $content['transferOrders'];
            foreach ($detailArr as $v) {
                $updateData = array();  // 更改数据库数据
                if(isset($v['payTime'])){
                    # 付款时间 付款成功才会返回
                    $updateData['pay_time'] = strtotime($v['payTime']);
                }
                # 将查询结果更改到数据库
                $updateData['success'] = $v['success'];     // 付款状态字段
                $updateData['amount'] = $v['amount'];     // 付款状态字段
                $updateData['status'] = $status; // 该批次付款状态
                M('api_withdraw_suning')->where(array('serialNo' => $v['serialNo']))->save($updateData);
            }
            exit("true");
        } else {
            get_date_dir($this->path, "notify", 'verify', '验签失败');
            exit("false");
        }
    }

    # 生成签名字符串
    private function getVerifySign($data, $sign)
    {
        $flag = 1;
        $str = '';
        foreach ($data as $key => $val) {
            if ($flag) {
                $str .= $key . '=' . $val;
                $flag = 0;
            }
        }
        # 将字符串MD5加密并转为大写
        $signStr = strtoupper(md5($str));
        # RSA签名
        $resStr = $this->verifyRSASign($signStr,base64_decode($sign),$this->publicKey);
        if (!$resStr) return false;

        return $resStr;
    }

    # 生成验签字符串
    private function getSign($data)
    {
        ksort($data);
        $flag = 1;
        $str = '';
        foreach ($data as $key => $val) {
            if ($flag) {
                $str .= $key . '=' . $val;
                $flag = 0;
            } else {
                $str .= '&' . $key . '=' . $val;
            }
        }
        # 将字符串MD5加密并转为大写
        $signStr = strtoupper(md5($str));
        # RSA签名
        $resStr = $this->getRSASign($signStr,$this->privateKey);
        if (!$resStr) return false;

        return $resStr;
    }

    /**
     * @param $str 待签名字符串
     * @param $privateKey RSA私钥
     * @return bool|string
     */
    private function getRSASign($str, $privateKey)
    {
        if (empty($str)) return False;
        # 验证密钥 得到资源id
        $pkeyid = openssl_pkey_get_private($privateKey);
        if (empty($pkeyid)) return False;
        # 得到签名
        openssl_sign($str, $signature, $pkeyid);
        # 释放资源
        openssl_free_key($pkeyid);

        return base64_encode($signature);
    }

    /**
     * RSA验证签名
     * @param string $data 数据
     * @param string $signature 签名
     * @param $publicKey RSA公钥
     * @return bool
     */
    private function verifyRSASign($data = '', $signature = '', $publicKey)
    {
        if (empty($data) || empty($signature)) return False;
        # 验证密钥 得到资源id
        $pkeyid = openssl_get_publickey($publicKey);
        if (empty($pkeyid)) return False;
        # 验证签名
        $ret = openssl_verify($data, $signature, $pkeyid);
        # 释放资源
        openssl_free_key($pkeyid);
        if ($ret == 1) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * 发送请求
     * @param $url
     * @param $post_data
     * @param $time
     * @return bool|mixed
     */
    private function sendRequest($url, $post_data, $time = 10)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $time);               //设置超时
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($ch);
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            get_date_dir($this->path, "request_err", "请求错误", "curl出错，错误码:$error");
            curl_close($ch);
            return false;
        }
        return $data;
    }

    # 获取订单号
    private function getOrderSn()
    {
        $str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $index = rand(0, 25);

        return date("Ymd"). 'T' . rand(100000, 999999) . $str[$index];
    }
}