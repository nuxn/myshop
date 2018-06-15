<?php

namespace Pay\Controller;

use Common\Controller\PayapiController;

/**
 * Class ApiController
 * @package Pay\Controller
 */
class ApiController extends PayapiController
{
    private $order_api;
    private $cate_id;
    private $remark;
    private $bank;
    private $file;

    public function __construct()
    {
        parent::__construct();
        # 限制访问方法
        $action = strtolower(ACTION_NAME);
        $rule = array('scanpay');
        if(!in_array($action, $rule)) $this->errJson('网络异常');

        $this->order_api = M('order_api');
        # 接收参数验证参数
        $data = file_get_contents('php://input');
        get_date_dir($this->path,'Payapi_sign','接收数据', $data);
        $this->params = json_decode($data, true);
        $this->checkSign($this->params);
        # 常用变量
        $this->mch_id = $this->params['mch_id'];
    }

    public function scanPay()
    {
        $this->checkScanParams();
        $pay_style = $this->params['pay_style'];
        $this->chooseBank($pay_style);
        $this->remark = getRemark().'PA';
        $this->params['remark'] = $this->remark;
        $result = $this->scanReauest();

        get_date_dir($this->path,$this->file,'参数', json_encode($this->params));
        get_date_dir($this->path,$this->file,'返回', json_encode($result));

        if($result['code'] == '0000'){
            # 订单入库
            $this->addDb($result);
            $return['pay_url'] = $result['url'];
            $this->succJson($return);
        } else {
            $this->errJson($result['msg']);
        }
    }

    private function scanReauest()
    {
        switch ($this->bank) {
            case 3:
                if($this->params['pay_style'] == '1'){
                    $result = A('Wxpay')->precreate($this->params);
                } else if($this->params['pay_style'] == '2'){
                    $this->errJson("支付方式异常");
                } else {
                    $this->errJson("支付方式异常");
                }
                break;
            case 11:
                $result = A('Barcodexdlbank')->precreate($this->params);
                break;
            case 12:
                $result = A('Leshuabank')->precreate($this->params);
                break;
            default:
                $this->errJson('通道异常，请联系客服');
                break;
        }

        return $result;
    }

    private function chooseBank($pay_style)
    {
        switch ($pay_style) {
            case 1:
                $this->file = 'Api_wxScanPay';
                $cate_data = M('merchants_cate')->field('id,wx_bank as bank_style')->where(array('merchant_id'=>$this->mch_id))->find();
                break;
            case 2:
                $this->file = 'Api_aliScanPay';
                $cate_data = M('merchants_cate')->field('id,ali_bank as bank_style')->where(array('merchant_id'=>$this->mch_id))->find();
                break;
            case 3:
                $this->file = 'Api_qqScanPay';
                $cate_data = M('merchants_cate')->field('id,ali_bank as bank_style')->where(array('merchant_id'=>$this->mch_id))->find();
                break;
            case 5:
                $this->file = 'Api_UnionpayScanPay';
                $cate_data = M('merchants_cate')->field('id,ali_bank as bank_style')->where(array('merchant_id'=>$this->mch_id))->find();
                break;
            default:
                $this->errJson("支付方式异常");
                break;
        }
        if($cate_data){
            $this->cate_id = $cate_data['id'];
            $this->bank = $cate_data['bank_style'];
        } else {
            $this->errJson("商户异常");
        }
    }

    private function addDb($input)
    {
        $inset['cate_id'] = $this->cate_id;
        $inset['mch_id'] = $this->mch_id;
        $inset['pay_style'] = $this->params['pay_style'];
        $inset['amount'] = $this->params['amount'];
        $inset['body'] = $this->params['body'];
        $inset['order_sn'] = $this->params['order_sn'];
        $inset['remark'] = $this->remark;
        $inset['bank'] = $this->bank;
        $inset['rate'] = $input['rate'];
        $inset['notify_url'] = $this->params['notify_url'];
        $inset['add_time'] = time();

        $res = M('order_api')->add($inset);
        if($res){
            return true;
        } else {
            $this->errJson('网络异常!');
        }

    }

    private function checkScanParams()
    {
        $data['mch_id'] = '84';
        $data['order_sn'] = '201805ad31214666800AAE99';
        $data['pay_style'] = '1';
        $data['amount'] = '0.01';
        $data['body'] = '测试';
        $data['notify_url'] = 'https://www.wxpay.com/notify.html';
        $data['timestamp'] = time();
        if(!$this->params['mch_id']) $this->errJson('mch_id参数异常');
        if(!$this->params['order_sn']) $this->errJson('order_sn参数异常');
        if(!$this->params['pay_style']) $this->errJson('pay_style参数异常');
        if(!$this->params['amount']) $this->errJson('amount参数异常');
        if(!$this->params['body']) $this->errJson('body参数异常');
        if(!$this->params['notify_url']) $this->errJson('notify_url参数异常');
        if(!$this->params['timestamp']) $this->errJson('timestamp参数异常');
        if($this->order_api->where(array('order_sn'=>$this->params['order_sn']))->getField('id')) $this->errJson('订单号已存在');
    }
}
