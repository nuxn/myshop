<?php

namespace Common\Controller;

use Think\Controller;

/**
 * Class ApiController
 * @package Pay\Controller
 */
class PayapiController extends Controller
{
    protected $params;
    protected $mch_id;
    protected $path;
    protected $key;

    public function __construct()
    {
        parent::__construct();
        $this->path = $_SERVER['DOCUMENT_ROOT'] . '/data/log/Pay/';
    }

    //空方法
    function _empty() {
        $this->errJson('非法操作');
    }

    protected function checkSign($params)
    {
        if(!$params['sign']) $this->errJson('signature failed','4000');
        $this->mch_id = $params['mch_id'];
        $this->key = M('merchants_key')->where(array('mch_id'=>$this->mch_id))->getField('key');
        if(!$this->key){
            $this->errJson('商户号不存在');
        }
        $sign = $params['sign'];
        unset($params['sign']);
        $newSign = $this->getSign($params,$this->key);
        if($sign === $newSign){
            return true;
        } else {
            $this->errJson('signature failed','4000');
        }
    }

    protected function errJson($msg, $code='1000')
    {
        $return['code'] = $code;
        $return['message'] = urlencode($msg);
        header('Content-Type:application/json; charset=utf-8');
        exit(urldecode(json_encode($return)));
    }

    function getSign($arr, $key)
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
        $String = $String . "&key=" . $key;
        get_date_dir($this->path,'Payapi_sign','签名字符串', $String);
        //签名步骤三：MD5加密
        $String = md5($String);

        //签名步骤四：所有字符转为大写
        $result_ = strtoupper($String);

        get_date_dir($this->path,'Payapi_sign','签名结果', $result_);
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


    protected function succJson($return)
    {
        $return['code'] = '0000';
        $return['message'] = '请求成功';
        $return['mch_id'] = $this->mch_id;
        $return['order_sn'] = $this->params['order_sn'];
        $return['amount'] = $this->params['amount'];
        $return['transaction_time'] = date('YmdHis');
        $return['sign'] = $this->getSign($return, $this->key);
        header('Content-Type:application/json; charset=utf-8');
        exit(json_encode($return));
    }

}
