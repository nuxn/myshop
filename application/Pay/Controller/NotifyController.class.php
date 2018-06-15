<?php

namespace Pay\Controller;

use Think\Controller;

/**
 * Class ApiController
 * @package Pay\Controller
 */
class NotifyController extends Controller
{
    private $order_api;
    private $path;
    private $pay_model;

    public function __construct()
    {
        parent::__construct();
        $this->path = $_SERVER['DOCUMENT_ROOT'] . '/data/log/Pay/';
        $this->order_api = M('order_api');
        $this->pay_model = M('pay');
    }

    # 乐刷回调
    public function leshua_notify()
    {
        $data = file_get_contents('php://input');
        $result_arr = $this->xmlToArray($data);
        if ($result_arr['error_code'] == '0' && $result_arr['status'] == '2') {
            $order_sn = $result_arr['third_order_id'];
            $orderData = M("order_api")->where(array('remark' => $order_sn))->find();
            if($orderData){
                if ($orderData['status'] == 0) {
                    if(bccomp($orderData['amount']*100, $result_arr['amount'], 3) === 0){
                        # 操作数据
                        get_date_dir($this->path, "Notify_leshua", "支付成功", json_encode($result_arr));
                        $this->api_pay_succ($result_arr['leshua_order_id'],$orderData);
                        $this->notify_client($orderData, 1);
                        exit("000000");
                    } else {
                        get_date_dir($this->path, "Notify_leshua", "金额不等", json_encode($result_arr));
                        echo "error";
                    }
                } else if($orderData['status'] == 1){
                    $this->notify_client($orderData, 1);
                    get_date_dir($this->path, "Notify_leshua", "二次通知", json_encode($result_arr));
                    exit("000000");
                } else {
                    get_date_dir($this->path, "Notify_leshua", "订单状态异常", json_encode($result_arr));
                    echo "error";
                }
            } else {
                get_date_dir($this->path, "Notify_leshua", "订单不存在", json_encode($result_arr));
                get_date_dir($this->path, "Notify_leshua", "SQl", M()->_sql());
                exit("000000");
            }
        }else {
            get_date_dir($this->path, "Notify_leshua", "支付失败", $data);
            echo "error";
        }
    }

    # 新大陆回调
    public function xdl_notify()
    {
        header('Content-type: application/json');
        $result_arr = $_GET;
        $order_sn = $result_arr['TxnLogId'];
        $transId = $result_arr['OfficeId'];
        $orderData = M("order_api")->where(array('remark' => $order_sn))->find();
        if($orderData){
            if ($orderData['status'] == 0) {
                if($result_arr['TxnAmt']*100 == $orderData['amount']*100){
                    get_date_dir($this->path, "Notify_xdl", "支付成功", json_encode($result_arr));
                    $this->api_pay_succ($transId,$orderData);
                    $this->notify_client($orderData, 1);
                    exit(json_encode(array('RspCode'=>'000000','RspDes'=>'success')));
                } else {
                    get_date_dir($this->path, "Notify_xdl", "金额不符", json_encode($result_arr));
                    $this->writlog('notify.log', ' 金额不符');
                }
            } else if($orderData['status'] == 1){
                get_date_dir($this->path, "Notify_xdl", "二次通知", json_encode($result_arr));
                $this->notify_client($orderData, 1);
                exit(json_encode(array('RspCode'=>'000000','RspDes'=>'success')));
            } else {
                get_date_dir($this->path, "Notify_xdl", "订单状态异常", json_encode($orderData));
                exit(json_encode(array('RspCode'=>'111111','RspDes'=>'error')));
            }
        } else {
            get_date_dir($this->path, "Notify_xdl", "未找到订单", json_encode($result_arr));
            exit(json_encode(array('RspCode'=>'222222','RspDes'=>'error')));
        }
    }

    # 微信回调
    public function weixin_notify()
    {
        Vendor('WxPayPubHelper.WxPayPubHelper');
        //使用通用通知接口
        $notify = new \Notify_pub();

        //存储微信的回调
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        $notify->saveData($xml);
        if ($notify->checkSign() == FALSE) {
            $return = array('return_code' => "FAIL", 'return_msg' => "签名失败");
            get_date_dir($this->path,'Notify_weixin','签名失败',$xml);
        } else {
            $data = $notify->data;
            $out_trade_no = $data["out_trade_no"];//回调的订单号
            if ($data['return_code'] == 'SUCCESS' && $data['result_code'] == 'SUCCESS') {
                // 读取订单信息
                $orderData = M("order_api")->where(array('remark' => $out_trade_no))->find();
                // 如果订单已支付返回成功
                if($orderData['status'] == 1){
                    $return = array('return_code' => "SUCCESS",'return_msg' => "");
                    $returnXml = $notify->returnnotifyXml($return);
                    $this->notify_client($orderData, 1);
                    echo $returnXml;
                    exit;
                }
                $orderPrice = $orderData['amount'];
                // 比较订单价格是否一致
                if (bccomp($orderPrice * 100, $data['total_fee'], 3) === 0) {
                    // 更改订单状态
                    $transId = $data['transaction_id'];
                    $this->api_pay_succ($transId,$orderData);
                    $this->notify_client($orderData, 1);
                    $return = array('return_code' => "SUCCESS",'return_msg' => "");
                    get_date_dir($this->path,'Notify_weixin','支付成功',json_encode($data));
                } else {
                    get_date_dir($this->path,'Notify_weixin','金额对比异常',json_encode($data));
                    get_date_dir($this->path,'Notify_weixin','金额对比异常',$orderPrice * 100);
                    get_date_dir($this->path,'Notify_weixin','金额对比异常',$data['total_fee']);
                    $return = array('return_code' => "FAIL");
                }
            } else {
                get_date_dir($this->path,'Notify_weixin','重复回调或不存在',json_encode($data));
                $return = array('return_code' => "FAIL");
            }
        }

        $returnXml = $notify->returnNotifyXml($return);
        echo $returnXml;
    }

    private function notify_client($input,$status)
    {
        get_date_dir($this->path, "http_post", "数据", json_encode($input));
        $post_data['code'] = '0000';
        if($status == 1){
            $post_data['message'] = '支付成功';
            $post_data['status'] = '1';
            $post_data['amount'] = $input['amount'];
            $post_data['order_sn'] = $input['order_sn'];
            $post_data['pay_style'] = $input['pay_style'];
        } else {
            $post_data['message'] = '支付失败';
            $post_data['status'] = '0';
        }
        $mch_id = $input['mch_id'];
        $post_data['mch_id'] = "$mch_id";

        $key = M('merchants_key')->where(array('mch_id'=>$input['mch_id']))->getField('key');
        $post_data['sign'] = $this->getSign($post_data, $key);
        $post_data=json_encode($post_data);
        $url = $input['notify_url'];
        $time = 5;
        while ($time) {
            $res = $this->http_post($url,$post_data);
            if($res == '0000'){
                return 'success';
            } else {
                $time--;
                sleep(5);
            }
        }
        return 'fail';
    }

    private function http_post($url, $post_data)
    {
        get_date_dir($this->path, "http_post", "回调网址", $url);
        get_date_dir($this->path, "http_post", "回调信息", $post_data);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    //xml转数组
    private function xmlToArray($xml)
    {
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $values;
    }

    private function api_pay_succ($transId,$orderData)
    {
        $save['trans_id'] = $transId;
        $save['status'] = 1;
        $save['pay_time'] = time();
        $this->order_api->where(array('id'=>$orderData['id']))->save($save);

        $data = array(
            'merchant_id' => $orderData['mch_id'],
            'order_id' => $orderData['id'],
            'customer_id' => '',
            'buyers_account' => '',
            'phone_info' => '',
            'wx_remark' => '',
            'wz_remark' => '',
            'new_order_sn' => '',
            'no_number' => '',
            'transId' => $transId,
            'la_ka_la' => 0,
            'add_time' => time(),
            'paytime' => time(),
            'bill_date' => date('Ymd'),
            'checker_id' => 0,
            'paystyle_id' => $orderData['pay_style'],
            'price' => $orderData['amount'],
            'remark' => $orderData['remark'],
            'status' => 1,
            'cate_id' => $orderData['cate_id'],
            'mode' => 26,
            'bank' => $orderData['bank'],
            'cost_rate' => $orderData['rate'],
            'subject' => $orderData['body'],
            'remark_mer' => '',
            'jmt_remark' => '',
        );

        return $this->pay_model->add($data);

    }

    function getSign($arr, $key)
    {
        get_date_dir($this->path,'Notify_sign','签名数据', json_encode($arr)."key:$key");
        //过滤null和空
        $Parameters = array_filter($arr, function ($v) {
            if ($v === null || $v === '') {
                return false;
            }
            return true;
        });
        //签名步骤一：按字典序排序参数
        ksort($Parameters);
        $String = $this->formatBizQueryParaMap($Parameters, false);
        //签名步骤二：在string后加入KEY
        $String = $String . "&key=" . $key;
        //签名步骤三：MD5加密
        $String = md5($String);

        //签名步骤四：所有字符转为大写
        $result_ = strtoupper($String);

        get_date_dir($this->path,'Notify_sign','签名结果', $result_);
        return $result_;
    }


    protected function formatBizQueryParaMap($paraMap, $urlencode)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if ($urlencode) {
                $v = json_encode($v);
            }
            $buff .= $k . "=" . $v . "&";
        }
        $reqPar = '';
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }

        return $reqPar;
    }

}
