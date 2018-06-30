<?php

namespace Api\Controller;

use Think\Controller;
use Common\Controller\ApibaseController;
use Common\Lib\Subtable;

class  BillController extends ApibaseController
{
    /**
     * 1为微众银行 2为民生银行 3为微信围餐 4招商银行  6济南民生
     */

    protected $pay_model;

    public $bank = array(
        '1' => 'merchants_upwz',
        '2' => 'merchants_mpay',
        '3' => 'merchants_upwx',
        '4' => 'merchants_zspay',
        '6' => 'merchants_mdaypay',
        '10' => 'merchants_pfpay',
        '11' => 'merchants_xdl',
    );

    public function __construct()
    {
        parent::__construct();
        $this->pay_model = M(Subtable::getSubTableName('pay'));

    }

    public function index()
    {

        $per_page = 10;
        ($this->userId = get_merchants_id($this->userInfo['role_id'], $this->userId)) || err('userid is empty');
        $this->userId = M('merchants')->where(array('uid' => $this->userId))->getField('id');
        add_log(json_encode($this->userId));
        ($page = I('page')) || err('page is empty');
        $page--;
        $paytime = M('pay_merchant_day')->where(array('merchant_id' => $this->userId))
            ->limit($page * $per_page, $per_page)->order('paytime desc')->getField('paytime', true);
        rsort($paytime);
        add_log(json_encode($paytime));
        $data = array();
        foreach ($paytime as $v) {
            $start_time = strtotime($v);
            $end_time = $start_time + 3600 * 24;

            //$lists = $this->pay_model->field('sum(price) as price,sum(case when bill_id > 0 then 0 else 1 end) as bill_id')->where('status =1 and paystyle_id in(1,2,3) and  merchant_id = '.$this->userId.' and  paytime >= '.$start_time.' and price>0 and  paytime < '.$end_time.'')->find();
            $lists = $this->pay_model->field('sum(price) as price,if(unix_timestamp(now())-86400>paytime,0,sum(case when bill_id > 0 then 0 else 1 end)) as bill_id')->where('status =1 and paystyle_id in(1,2,3) and  merchant_id = ' . $this->userId . ' and  paytime >= ' . $start_time . ' and price>0 and  paytime < ' . $end_time . '')->find();

            $lists['price'] = $lists['price'] ?: '0';
            $lists['bill_id'] = $lists['bill_id'] ?: '0';
            $list = array('time' => $start_time);
            $list = array_merge($list, $lists);
            $data[] = $list;
        }
        succ($data);
    }

    public function index1()
    {
        ($this->userId = get_merchants_id($this->userInfo['role_id'], $this->userId)) || err('userid is empty');
        $this->userId = M('merchants')->where(array('uid' => $this->userId))->getField('id');
        ($page = I('page')) || err('page is empty');
        $start_time = strtotime(date('y-m-d')) - 3600 * 24 * ($page - 1) * 11;
        $end_time = $start_time + 3600 * 24;
        $count = 10;
        do {
            //统计
            $lists = $this->pay_model->field('sum(price) as price,sum(case when bill_id > 0 then 0 else 1 end) as bill_id')->where('status =1 and  merchant_id = ' . $this->userId . ' and  paytime >= ' . $start_time . ' and paytime < ' . $end_time . '')->find();

            $lists['price'] = $lists['price'] ?: '0';
            $lists['bill_id'] = $lists['bill_id'] ?: '0';
            $list = array('time' => $start_time);
            $list = array_merge($list, $lists);
            $data[] = $list;
            $end_time = $start_time;
            $start_time = $start_time - 3600 * 24;
        } while ($count--);
        succ($data);
    }

    public function detail()
    {

        ($time = I('time')) || err('time is empty');
        ($this->userId = get_merchants_id($this->userInfo['role_id'], $this->userId)) || err('userid is empty');
        //寻找
        $mid = M('merchants')->where(array('uid' => $this->userId))->getField('id');
        $start_time = $time;
        $end_time = $start_time + 3600 * 24;
        $lists = $this->pay_model->field('id,remark,paystyle_id,price,if(unix_timestamp(now())-86400>paytime,1,0) as bill_id,paytime,bank,cost_rate')->where('status =1 and paystyle_id in(1,2)  and  merchant_id = ' . $mid . ' and   price>0 and paytime >= ' . $start_time . ' and paytime < ' . $end_time . '')->select();
        $all_cost_price = $all_price = 0;

        foreach ($lists as $v) {
            switch ($v['bank']) {
                case 1:
                    $bill = M('bill_record')->field('deal_money as price,poundage as cost_price,clearing_money as real_price')->where(array('id' => $v['bill_id']))->find();

                    break;
                case 2:
                    $bill = M('ms_logs')->field('pay_price as price,pay_reprice as cost_price,price as real_price')->where(array('id' => $v['bill_id']))->getField('id');
                    break;
                case 3:
                    $bill = M('bill_wx')->field('deal_money as price')->where(array('id' => $v['bill_id']))->getField('id');
                    $bill['cost_price'] = '0';
                    $bill['real_price'] = $bill['price'];
                    break;
                case 4:
                    $bill = M('zs_logs')->field('price,sx_price as cost_price')->where(array('id' => $v['bill_id']))->getField('id');
                    $bill['real_price'] = $bill['price'] - $bill['cost_price'];
                    break;
                case 5:
                    //$bill = M('zs_logs')->where(array('order_sn'=>$v['order_sn']))->getField('id');
                    break;
                case 6:
                    break;
            }
//						if($bill){
//						//总金额
//								$re = $bill;
//						//手续费
//						}else{
            $re['price'] = number_format($v['price'], 2, '.', '');
            $re['cost_price'] = number_format($v['price'] * $v['cost_rate'] / 100, 2, '.', '');
            $re['real_price'] = number_format($v['price'] - $re['cost_price'], 2, '.', '');
            //}
            $re['paystyle_id'] = $v['paystyle_id'];
            $re['bill_id'] = $v['bill_id'];
            $re['paytime'] = $v['paytime'];
            $re['remark'] = $v['remark'];
            $re['id'] = $v['id'];
            //$re['cost_rate'] = $re['cost_rate'].'%';
            $all_cost_price += $re['cost_price'];
            $all_price += $re['price'];

            //支付方式
            $data[] = $re;
        }
        add_log(json_encode($data));
        //查看商户信息
        $res = M('merchants_cate')->where(array('merchant_id' => $mid))->find();

        $rate = M('merchants_rate')->where(array('merchants_id' => $mid, 'bank' => $res['wx_bank']))->find();
        if (!$rate) {
            $r = M('merchants')->where(array('id' => $mid))->getField('bank_rate');
            $rate['wx_rate'] = $r;
            $rate['alipay_rate'] = $r;
        }
        add_log(M()->_SQL());


        $rate['wx_rate'] = $rate['wx_rate'] . '%';
        $rate['alipay_rate'] = $rate['alipay_rate'] . '%';
        add_log(json_encode($rate));
        succ($data ?: array(), 'succ', array('rate' => $rate ?: array(), 'price' => $all_price, 'cost_price' => $all_cost_price));
    }

}