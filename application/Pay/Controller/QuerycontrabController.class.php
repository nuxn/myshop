<?php
/**
 * Created by PhpStorm.
 * User: Joan
 * Date: 2017/8/2
 * Time: 17:06
 */

namespace Pay\Controller;

use Common\Controller\HomebaseController;

/**支付回调处理
 * Class NotifyController
 * @package Api\Controller
 */
class QuerycontrabController extends HomebaseController
{
    private $pay_model;
    public function _initialize()
    {
        $this->contrab = $_SERVER['DOCUMENT_ROOT'] . "/data/log/query/";
        $this->pay_model = $this->pay_model;
    }

    private function get_date_dir($path = '/data/log/query/', $name, $title, $json)
    {
        $Y = $path . date("Y-m");
        $d = $Y . '/' . date("Y-m-d");
        if (!file_exists($Y)) mkdir($Y, 0777);
        if (!file_exists($d)) mkdir($d, 0777);
        $re = file_put_contents($d . "/" . $name . '.log', date("H:i:s") . '  ' . $title . ": " . $json . PHP_EOL . PHP_EOL, FILE_APPEND | LOCK_EX);
        return $re;
    }

    public function index()
    {
        $where['a.status'] = 0;
        $where['a.paytime'] = array('between', array(time() - 3 * 60, time()));
        $payInfo = $this->pay_model
            ->alias("a")
            ->join("left join ypt_merchants_cate b on a.cate_id=b.id")
            ->field("a.paystyle_id,a.bank,a.status,a.paytime,a.remark,a.transId,b.alipay_partner,b.alipay_private_key,b.alipay_public_key,b.wx_mchid,b.wx_key")
            ->where($where)
            ->select();
        $this->get_date_dir($this->contrab, "common", "全部结果集", json_encode($payInfo));
        $re = $this->getChannel($payInfo);
    }

    private function getChannel($payInfo)
    {
        foreach ($payInfo as $key => $value) {
            switch ($value['bank']) {
                case 4:
                    $result = $this->zs_query($value);
                    if ($result == true) {
                        $this->get_date_dir($this->contrab, "success", "招商结果集:", json_encode($value));
                        $this->pay_model->where(array('remark' => $value['remark']))->save(array('transId' => $result, 'status' => 1));
                        A("App/PushMsg")->push_pay_message($value['remark']);
                    }
                    break;
                case 7:
                    $result = $this->xy_query($value);
                    if ($result == true) {
                        $this->get_date_dir($this->contrab, "success", "兴业结果集:", json_encode($value));
                        $this->pay_model->where(array('remark' => $value['remark']))->save(array('transId' => $result, 'status' => 1));
                        A("App/PushMsg")->push_pay_message($value['remark']);
                    }
                    break;
                case 1:
                    $result = $this->wx_query($value);
                    if ($result) {
                        $this->get_date_dir($this->contrab, "success", "微众结果集:", json_encode($value));
                        $this->pay_model->where(array('remark' => $value['remark']))->save(array('transId' => $result, 'status' => 1));
                        A("App/PushMsg")->push_pay_message($value['remark']);
                    }
                    break;
                default:
                    # code...
                    break;
            }
        }
        return $result;
    }

    //微众查询
    private function wx_query($orderInfo)
    {
        $data['merchant_code'] = $orderInfo['wx_mchid'];//  "107100000420001";  //商户入驻微众提供
        $data['terminal_serialno'] = $orderInfo['remark']; //"2017090417023789481"; //洋仆淘订单号(商户自定订单)
        if ($orderInfo['mode'] == 2) {
            $orderInfo['mode'] = 4;
        } else {
            $orderInfo['mode'] = 1;
        }
        $data['_statu'] = $orderInfo['mode']; // (默认0,1公众号支付,2h5支付,3扫码支付,4条码(刷码)支付,5app支付)
        $key = $orderInfo['wx_key'];
        vendor('Wzpay.Wzpay');
        $wzPay = new \Wzpay();
        $returnData = $wzPay->order_pay($data, $key);
        $this->get_date_dir($this->contrab, "query", "微众结果集:" . $orderInfo['remark'], json_encode($returnData));
        if ($returnData['result']['errmsg'] == '0' && $returnData['result_code'] == '0' && $returnData['payment'] = 'SUCCESS') {
            $out_trade_no = $returnData['orderid'];
            return $out_trade_no;
        } else {
            return false;
        }
    }

    //兴业查询
    public function xy_query($param)
    {
        if ($param['paystyle_id'] == 1) {
            $data['mch_id'] = $param['wx_mchid'];//商户号
            $param['key'] = $param['wx_key'];
        } else if ($param['paystyle_id'] == 2) {
            $data['mch_id'] = $param['alipay_partner'];//商户号
            $param['key'] = $param['alipay_public_key'];
        }
        $data['service'] = 'unified.trade.query';
        $data['out_trade_no'] = $param['remark'];//商户系统内部的订单号
        // $data['transaction_id'] = $param['out_trade_no'];//UCHANG订单号，优先使用
        $data['nonce_str'] = date('YmdHis') . rand(10000, 99999);//随机字符串
        $data['sign'] = $this->getSignVeryfyXybank($data, $param['key']);
        $xmlData = $this->arrayToXml($data);
        $url = "https://pay.swiftpass.cn/pay/gateway";
        $res = $this->httpRequstXybank($url, $xmlData);
        $res = $this->xmlToArray($res);
        $this->get_date_dir($this->contrab, "query", "兴业结果集:" . $param['remark'], json_encode($res));
        if ($res['status'] == 0 && $res['result_code'] == 0 && $res['trade_state'] == 'SUCCESS') {
            $out_trade_no = $res['transaction_id'];
            return $out_trade_no;
        } else {
            return false;
        }
    }

    //招商银行查询
    private function zs_query($param)
    {
        if ($param['paystyle_id'] == 1) {
            $data['mch_id'] = $param['wx_mchid'];//商户号
            $data['out_trade_no'] = $param['remark'];//商户系统内部的订单号

            $data['nonce_str'] = date('YmdHis') . rand(10000, 99999);//随机字符串
            $data['sign'] = $this->getSignVeryfy($data, $param['wx_key']);
            $xmlData = $this->arrayToXml($data);
            $url = "http://api.cmbxm.mbcloud.com/wechat/orders/query";
            $result = $this->httpRequstZsbank($url, $xmlData);
            $codeData = $this->xmlToArray($result);
            $this->get_date_dir($this->contrab, "query", "招行结果集:" . $param['remark'], json_encode($codeData));
            if ($codeData['result_code'] == 'SUCCESS' && $codeData['return_code'] == 'SUCCESS' && $codeData['trade_state'] == 'SUCCESS') {
                $out_trade_no = $codeData['transaction_id'];
                return $out_trade_no;
            } else {
                return false;
            }
        } else if ($param['paystyle_id'] == 2) {
            $data['mch_id'] = $param['alipay_partner'];//商户号，由UCHANG分配
            $data['nonce_str'] = date('YmdHis') . rand(10000, 99999);//随机字符串
            $data['out_trade_no'] = $param['remark'];//商户系统内部的订单号,32个字符内、可包含字母, 其他说明见商户订单号

            $data['sign'] = $this->getSignVeryfy($data, $param['alipay_public_key']);
            $xmlData = $this->arrayToXml($data);
            $url = "http://api.cmbxm.mbcloud.com/alipay/orders/query";
            $result = $this->httpRequstZsbank($url, $xmlData);
            $codeData = $this->xmlToArray($result);
            $this->get_date_dir($this->contrab, "query", "招行结果集:" . $param['remark'], json_encode($codeData));
            if ($codeData['result_code'] == 'SUCCESS' && $codeData['return_code'] == 'SUCCESS' && $codeData['trade_state'] == 'TRADE_SUCCESS') {
                $out_trade_no = $codeData['transaction_id'];
                return $out_trade_no;
            } else {
                return false;
            }
        }
    }
    // //济南民生查询
    //   	public function jnms_check($bank){
    //   		//商户号
    //    	$param['merNo']=$bank['wx_mchid'];
    //    	//平台交易号
    //    	$param['orgTransId']=$bank['transId'];
    //    	$url="http://scp.yufu99.com/scanpay-api/api/d0Query20";
    //    	$apikey=$bank['apikey'];
    //    	$data=$this->httpRequst($url,$param,$apikey);
    //    	get_date_dir($this->contrab,"check","济南民生查询结果",$data);
    //    	$data=json_decode($data,true);
    //    	if($data['result']=='0000' && $data['status']=='1'){
    //            $out_trade_no=$data['transId'];
    //    		return $out_trade_no;
    //    	}else{
    //    		return false;
    //    	}
    //   	}

    public function httpRequst($url, $post_data, $apikey)
    {

        ksort($post_data);
        $o = "";
        foreach ($post_data as $k => $v) {
            $o .= "$k=" . ($v) . "&";
        }
        $post_data = substr($o, 0, -1);
        $post_data_temp = $post_data . $apikey;
        $signIn = strtoupper(md5($post_data_temp));
        $post_data = $post_data . "&signIn=" . $signIn;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
        //显示获得的数据   
    }

    private function http_post($url, $post_data)
    {
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

    private function argSort($para)
    {
        ksort($para);
        reset($para);
        return $para;
    }

    private function getSign($arr, $appkey)
    {
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
        $String = $String . "&key=" . $appkey;
        //签名步骤三：MD5加密
        $String = md5($String);
        //签名步骤四：所有字符转为大写
        $result_ = strtoupper($String);
        return $result_;
    }

    /**
     * 作用：格式化参数，签名过程需要使用
     * @param $paraMap
     * @param $urlencode
     * @return string
     */
    private function formatBizQueryParaMap($paraMap, $urlencode)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if ($urlencode) {
                $v = json_encode($v);
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

    //数组转xml
    public function arrayToXml($arr)
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
    public function xmlToArray($xml)
    {
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $values;
    }

    //支付接口 curl
    public function httpRequstZsbank($url, $post_data)
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

//        get_date_dir($this->path,"curl","curl返回参数",json_encode($this->xmlToArray($data)));

        return $data;
        //显示获得的数据   
    }

    //支付接口统一签名
    public function getSignVeryfy($para_temp, $paykey)
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

    public function createLinkstring($para)
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

    //除去空字符串
    public function paraFilter($para)
    {
        $para_filter = array();
        while (list ($key, $val) = each($para)) {
            if ($key == "sign" || $key == "sign_type" || $val == "") continue;
            else    $para_filter[$key] = $para[$key];
        }
        return $para_filter;
    }

    //支付接口 curl
    private function httpRequstXybank($url, $post_data)
    {
        $headers = array("Accept-Charset: utf-8");
        //初始化
        $curl = curl_init();
        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //设置post方式提交
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        //设置post数据
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
        //执行命令
        $data = curl_exec($curl);
        curl_close($curl);
        return $data;
        //显示获得的数据   
    }

    //支付接口统一签名
    private function getSignVeryfyXybank($para_temp, $key)
    {
        //除去待签名参数数组中的空值和签名参数
        $para_filter = $this->xy_paraFilter($para_temp);

        //对待签名参数数组排序
        $para_sort = $this->xy_argSort($para_filter);

        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $this->xy_createLinkstring($para_sort);
        //拼接apikey
        $prestr = $prestr . "&key=" . $key;
        //MD5 转大写
        $prestr = strtoupper(md5($prestr));
        return $prestr;
    }

    //除去空字符串
    private function xy_paraFilter($para)
    {
        $para_filter = array();
        while (list ($key, $val) = each($para)) {
            if ($key == "sign" || $key == "sign_type" || $val == "") continue;
            else    $para_filter[$key] = $para[$key];
        }
        return $para_filter;
    }

    //数组排序
    private function xy_argSort($para)
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
    private function xy_createLinkstring($para)
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

    /**
     * 获取随机字符串
     * @return string
     */
    public function xy_getNonceStr()
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < 32; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }

        return strtoupper($str);
    }
}