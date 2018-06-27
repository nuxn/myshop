<?php

namespace Api\Controller;

use Think\Controller;

class  MerchantsRateController extends Controller
{
    /**
     *
     * 1为微众银行 2为民生银行 3为微信围餐 4招商银行  6济南民生
     */
    public $bank = array(
        '1' => 'merchants_upwz',
        '2' => 'merchants_mpay',
        '3' => 'merchants_upwx',
        '4' => 'merchants_zspay',
        '6' => 'merchants_mdaypay',
    );
    private $pay_model;

    public function __construct()
    {
        parent::__construct();
        $this->pay_model = M('pay');
    }

    /**
     * D0银行
     */
    public $d0 = array('6');

    public function index()
    {
        $bank_id = I('bank', 0, 'intval');
        $merchants_id = I('merchants_id', 0, 'intval');
        switch ($bank_id) {
            case 0:

                foreach ($this->bank as $key => $v) {
                    $action = $v;
                    $this->$action($key, $merchants_id);
                }
                break;
            default:
                $bank = $this->bank;
                $this->$bank[$bank_id]($bank_id, $merchants_id);
                break;
        }
    }

    public function merchants_upwz($bank_id, $merchants_id = 0)
    {

        $data = M('merchants_upwz')->select();
        foreach ($data as $v) {
            $this->common($bank_id, $v['mid'], $v['wxCostRate'], $v['aliCostRate']);
        }
    }

    public function merchants_upwx($bank_id, $merchants_id = 0)
    {
        $data = M('merchants_upwx')->select();
        foreach ($data as $v) {
            $this->common($bank_id, $v['mid'], 0, 0);
        }
    }

    public function merchants_mpay($bank_id, $merchants_id = 0)
    {
        $data = M('merchants_mpay')->select();
        foreach ($data as $v) {
            $this->common($bank_id, $v['uid'], $v['weicodefen'], $v['alipaycodefen']);
        }
    }

    public function merchants_zspay($bank_id, $merchants_id = 0)
    {
        $data = M('merchants_zspay')->select();
        foreach ($data as $v) {
            $this->common($bank_id, $v['merchant_id'], $v['payment_type1'] / 100, $v['payment_type8'] / 100);
        }
    }

    public function merchants_mdaypay($merchants_id = 0)
    {

    }

    private function common($bank, $merchants_id, $wx_rate, $alipay_rate, $repaid_rate = 0, $min_amount = 0, $poundage = 0, $min_repaid_amount = 0)
    {

        //如果是d0银行
        if (in_array($bank, $d0)) {
            if (!$repaid_rate && $min_amount && $poundage && $min_repaid_amount) {
                //记录错误日志
                return false;
            }
            $data['repaid_rate'] = $repaid_rate;
            $data['min_amount'] = $min_amount;
            $data['poundage'] = $poundage;
            $data['min_repaid_amount'] = $min_repaid_amount;
        }
        if (!$merchants_id) {
            //记录错误日志
            return false;
        }
        $data['merchants_id'] = $merchants_id;
        $data['wx_rate'] = $wx_rate;
        $data['alipay_rate'] = $alipay_rate;
        $data['add_time'] = time();
        $data['bank'] = $bank;

        //查询是否存在该数据
        $rate_id = M('merchants_rate')->where(array('merchants_id' => $merchants_id, 'bank' => $bank))->getField('id');
        if ($rate_id) {
            M('merchants_rate')->where(array('id' => $rate_id))->save($data);
        } else {
            M('merchants_rate')->add($data);
        }
    }

    public function tj($day = 1)
    {
        $d0 = implode(',', $this->d0);
        //默认统计当天
        $start_time = strtotime("-" . $day . " day", strtotime(date('y-m-d')));
        $date = date('ymd', $start_time);
        $end_time = strtotime("+1 day", $start_time);
        echo '---------------开始时间：' . $start_time . '-' . $end_time . '---------<br><br>';
        //查询一级代理
        $agents = M("merchants_agent")
            ->alias("a")
            ->join("join __MERCHANTS_USERS__ u on a.uid=u.id")
            ->field("a.id as aid,u.id as uid,u.user_phone,a.agent_name")
            ->select();
        //算出所有商户
        foreach ($agents as $v) {
            echo '---开始统计代理商' . $v['uid'] . '--';
            echo "<br>";
            $uid = M()->query('select getchild(' . $v['uid'] . ') as uids');

            echo '---商户：' . $uid[0]['uids'];
            echo "<br>";
            $uids = $uid[0]['uids'];

            $uids = get_merchant_id($uids);
            if (empty($uids)) {
                continue;
            }
            //查看这些商家的流水
            //首先统计d1
            $pay = $this->pay_model
                ->field('price,cost_rate,paystyle_id')
                ->where('paytime >=' . $start_time . ' and paytime < ' . $end_time . ' and merchant_id in (' . $uids . ') and status =1 and paystyle_id in(1,2)  and (poundage=0 or poundage is null)')
                ->select();

            $price = 0;
            $rebate = 0;
            $i = 0;
            //统计出这个月的
            //查出代理商的利率
            $agent = M('merchants_agent')->where(array('uid' => $v['uid']))->find();
            $all_price = 0;
            foreach ($pay as $vv) {
                $all_price += $vv['price'];
            }
//			 		if($all_price<1000000){
//			 			$agent_rate = $agent['one_rate'];
//			 		}else if(1000000<=$all_price&&$all_price<5000000){
//			 			$agent_rate = $agent['two_rate'];
//			 		}else if(5000000<=$all_price&&$all_price<10000000){
//			 			$agent_rate = $agent['three_rate'];
//			 		}else{
//			 			$agent_rate = $agent['four_rate'];
//			 		}
            foreach ($pay as $vv) {
                $i++;
                if ($vv['cost_rate'] == 0) {
                    continue;
                }
                $price += $vv['price'];
                echo '订单价格:' . $vv['price'] . '费率:' . $vv['cost_rate'] . '-' . $agent[$vv['paystyle_id'] == 1 ? 'wx_rate' : 'ali_rate'];
                $rebate += $vv['price'] * ($vv['cost_rate'] - $agent[$vv['paystyle_id'] == 1 ? 'wx_rate' : 'ali_rate']) / 100;
                echo '收益：' . $rebate;
                echo "<br>";
            }
            $data['agent_id'] = $v['uid'];
            $data['date'] = $date;
            $data['price'] = $price;
            $data['rebate'] = $rebate;
            $data['nums'] = $i;
            //首先统计d0
            $pay0 = $this->pay_model
                ->field('price,cost_rate,paystyle_id,repaid_rate,min_repaid_amount,poundage')
                ->where('paytime >=' . $start_time . ' and paytime < ' . $end_time . ' and merchant_id in (' . $uids . ') and status =1 and paystyle_id in(1,2) and poundage>0')
                ->select();
            $price0 = 0;
            $rebate0 = 0;
            $i = 0;
            $poundage = 0;
            foreach ($pay0 as $vv) {
                $i++;
                $price0 += $vv['price'];
                //每笔手续费
                $poundage = $vv['poundage'] - $agent['poundage'];
                $m_rapaid = ($vv['price'] * $agent['repaid_rate'] / 1000 - $agent['repaid_price']) > 0 ? ($vv['price'] * $agent['repaid_rate'] / 1000 - $agent['repaid_price']) : $agent['repaid_price'];
                var_dump($vv['price'], $agent['repaid_rate'], $agent['repaid_price'], $m_rapaid);
                $d_rapaid = ($vv['price'] * $vv['repaid_rate'] / 1000 - $vv['min_repaid_amount']) > 0 ? ($vv['price'] * $vv['repaid_rate'] / 1000 - $vv['min_repaid_amount']) : $vv['min_repaid_amount'];
                $rapaid = $d_rapaid - $m_rapaid;

                $rebate0 += ($vv['price'] * ($vv['cost_rate'] - $agent[$vv['paystyle_id'] == 1 ? 'wx_rate_0' : 'ali_rate_0']) / 100 + $rapaid + $poundage);

                echo '订单价格:' . $vv['price'] . '费率:' . $vv['cost_rate'] . '-' . $agent[$vv['paystyle_id'] == 1 ? 'wx_rate_0' : 'ali_rate_0'];

                echo '每笔交易费:' . $poundage;
                echo '每笔最少付款数' . $d_rapaid . '-' . $m_rapaid . ':' . $rapaid;
                echo '利润:' . $rebate0;
                echo "<br>";
            }
            $data['price0'] = $price0;
            $data['rebate0'] = $rebate0;
            $data['nums0'] = $i;
            $data['poundage'] = $poundage;
            //查询是否已经存在
            if ($id = M('pay_day')->where(array('agent_id' => $v['uid'], 'date' => $date))->getField('id')) {
                M('pay_day')->where(array('id' => $id))->save($data);
            } else {
                M('pay_day')->add($data);
            }
        }
    }

    public function tjs($start = 15, $nums = 30)
    {
        $nums++;
        while ($nums--) {
            $this->tj($start);
            $start++;
        }
    }

    public function tj_merchant($day = 1)
    {
        //统计昨天
        $time = strtotime(date('y-m-d')) - 3600 * 24 * $day;
        $start_time = $time;
        $end_time = $start_time + 3600 * 24;
        $lists = $this->pay_model->field('sum(price) as price,merchant_id,paytime,count(id) as num')->group('merchant_id')->where('status =1 and paystyle_id in(1,2)  and  paytime >= ' . $start_time . ' and price>0 and paytime < ' . $end_time . '')->select();

        foreach ($lists as &$v) {
            $v['paytime'] = date('Ymd', $v['paytime']);
            var_dump($v);
            if (M('pay_merchant_day')->where(array('paytime' => $v['paytime'], 'merchant_id' => $v['merchant_id']))->find()) {

            } else {
                M('pay_merchant_day')->add($v);
            }
        }
    }

    //定时执行对账单
    public function bill()
    {
        $time = time() - 3600 * 24;
        $Pay = $this->pay_model;
        $lists = $Pay->where('bill_id = 0  and  status = 1 and paytime < ' . $time)->field('id,bank,remark,paystyle_id')->order('id desc')->select();
        //echo $Pay->_SQL();
        foreach ($lists as $v) {
            echo $v['id'] . '<br>';
            switch ($v['bank']) {
                case 1:
                    if ($v['paystyle_id'] == 1) {
                        $id = M('bill_record')->where(array('merchant_order_sn' => $v['remark']))->getField('id');
                    } else {
                        $id = 1;
                    }
                    break;
                case 2:
                    $id = M('ms_logs')->where(array('order_sn' => $v['remark']))->getField('id');
                    break;
                case 3:
                    $id = M('bill_wx')->where(array('wx_order_sn' => $v['remark']))->getField('id');
                    break;
                case 4:
                    $id = M('zs_logs')->where(array('order_sn' => $v['remark']))->getField('id');
                    break;
                case 5:
                    //	$id = M('zs_logs')->where(array('order_sn'=>$v['order_sn']))->getField('id');
                    break;
                case 6:
                    break;
                case 7:
                    $id = M('bill_xy')->where(array('mch_order_sn' => $v['remark']))->getField('id');
                    break;
            }
            //更新pay表
            if ($id) {
                $Pay->where(array('id' => $v['id']))->setField('bill_id', $id);
            }
        }
    }

    //定时日流水统计
    public function pay_task()
    {
//        echo date('Y-m-d H:s',1523899235);die;
        //默认统计昨天
        //统计对象  微信,支付宝，现金，银联，储值，异业联盟等支付金额和笔数，支付优惠，微信充值，支付宝充值，充值码充值，原路退款，现金退款
        error_reporting (E_ALL & ~E_NOTICE);
        set_time_limit(0); //执行时间无限
//        ignore_user_abort();
        $i=1;
//        for($i=200;$i>0;$i--){
            $start_time = strtotime("-" . $i . " day", strtotime(date('y-m-d')));
            $date = date('Ymd',$start_time);

            $end_time  =  strtotime("+1 day",$start_time);
            echo '---------------开始时间：' . $start_time . '-' . $end_time . '---------<br><br>';
            $merchants = M('merchants')->where(array('status'=>1))->field('id,uid,merchant_name')->select();
            $nums = 0;
            foreach ($merchants as $key => $value) {
                if (M('pay_statistics')->where(array('date'=>$date,'uid'=>$value['uid']))->getField('id')) {
                    continue;
                }
                //            echo $value['id'].'<br>';
                $pay = M('pay')
                    ->alias("p")
                    ->where('p.paytime >='.$start_time.' and p.paytime < '.$end_time)
                    ->where(array('p.merchant_id'=>$value['id'],'p.status'=>1))
                    ->field('p.price,p.paystyle_id,p.remark,p.mode')
                    ->select();
                //            echo M('pay')->getLastSql().'<br>';
                $cdk = M('screen_memcard_cdk_log')
                    ->alias("l")
                    ->join("join ypt_screen_memcard_cdk c on c.id=l.cdk_id")
                    ->where('l.use_time >='.$start_time.' and l.use_time < '.$end_time)
                    ->where(array('l.uid'=>$value['uid']));
                $cdk_price =  $cdk->sum('c.price');
                $cdk_price = $cdk_price?$cdk_price:0;
                //            echo $cdk->getLastSql();
                $cdk_nums =  M('screen_memcard_cdk_log')
                    ->alias("l")
                    ->join("join ypt_screen_memcard_cdk c on c.id=l.cdk_id")
                    ->where('l.use_time >='.$start_time.' and l.use_time < '.$end_time)
                    ->where(array('l.uid'=>$value['uid']))
                    ->count();
                if (!$pay&&$cdk_nums==0){
                    continue;
                }
                //            echo M('screen_memcard_cdk_log')->getLastSql();
                $pay_back = M('pay_back')
                    ->where('paytime >='.$start_time.' and paytime < '.$end_time)
                    ->where(array('merchant_id'=>$value['id']))
                    ->field('price_back,mode')
                    ->select();

                //            echo M('screen_memcard_cdk_log')->getLastSql();
                $wx_price = $ali_price = $union_price =$cash_price = $double_back = $cash_back = $merchant_price = $agent_price =$order_benefit=$wx_recharge = $ali_recharge=0;
                $wx_nums = $merchant_nums = $agent_nums =$ali_nums = $union_nums = $cash_nums = $double_back_nums = $cash_back_nums =$wx_recharge_nums = $ali_recharge_nums=$order_benefit_nums=0;
                $num = 0;
                foreach ($pay as $k => $v) {
                    $order = M('order')
                        ->where(array('order_sn'=>$v['remark']))
                        ->field('user_money,order_amount,total_amount,card_code,order_benefit')
                        ->find();
                    if ($order['order_benefit']>0){
                        $order_benefit += $order['order_benefit'];   //支付优惠
                        $order_benefit_nums++;
                    }

                    if ($v['paystyle_id']==1) {
                        if(!$order||$order['order_amount']>0){
                            //微信支付
                            $wx_price += $v['price'];
                            $wx_nums++;
                        }else{
                            //判断储值支付类型  1=普卡  2=异业联盟盟卡
                            $type = $this->check_yue($order['card_code']);
                            if ($type==1){
                                //1=普卡
                                $merchant_price += $order['user_money'];
                                $merchant_nums++;
                            }elseif($type=2){
                                //2=异业联盟盟卡
                                $agent_price += $order['user_money'];
                                $agent_nums++;
                            }
                        }
                        if($v['mode']==12){
                            //会员充值
                            $wx_recharge += $v['price'];
                            $wx_recharge_nums++;
                        }
                    }
                    if ($v['paystyle_id']==2) {
                        //支付宝
                        $ali_price += $v['price'];
                        $ali_nums++;
                        if($v['mode']==12){
                            //会员充值
                            $ali_recharge += $v['price'];
                            $ali_recharge_nums++;
                        }
                    }
                    if ($v['paystyle_id']==5) {
                        //银联
                        $union_price += $v['price'];
                        $union_nums++;
                    }
                    if ($v['paystyle_id']==3) {
                        //现金
                        $cash_price += $v['price'];
                        $cash_nums++;
                    }

                }
                foreach ($pay_back as $kv => $va) {
                    if ($va['mode']==98) {
                        $double_back += $va['price_back'];
                        $double_back_nums++;
                    }
                    if ($va['mode']==99) {
                        $cash_back += $va['price_back'];
                        $cash_back_nums++;
                    }
                }
                echo $date.'<br>';
                echo "微信支付：".$wx_price.'数量：'.$wx_nums.'<br>';
                echo "支付宝：".$ali_price.'数量：'.$ali_nums.'<br>';
                echo "银联支付：".$union_price.'数量：'.$union_nums.'<br>';
                echo "现金支付：".$cash_price.'数量：'.$cash_nums.'<br>';
                echo "商户储值：".$merchant_price.'数量：'.$merchant_nums.'<br>';
                echo "代理储值：".$agent_price.'数量：'.$agent_nums.'<br>';
                echo "原路退款：".$double_back.'数量：'.$double_back_nums.'<br>';
                echo "现金退款：".$cash_back.'数量：'.$cash_back_nums.'<br>';
                echo "支付优惠：".$order_benefit.'<br>';
                echo "支付优惠笔数：".$order_benefit_nums.'<br>';
                echo "微信充值：".$wx_recharge.'<br>';
                echo "微信笔数：".$wx_recharge_nums.'<br>';
                echo "支付宝充值：".$ali_recharge.'<br>';
                echo "支付宝笔数：".$ali_recharge_nums.'<br>';
                echo "充值码充值：".$cdk_price.'<br>';
                echo "充值码笔数：".$cdk_nums.'<br>';
                $num = $wx_nums + $ali_nums +$union_nums+$cash_nums+$merchant_nums+$agent_nums;
                $nums +=  $num;
                echo "总笔数：".$num.'<br>';
                echo $key.'---'.$value['id'].'<br>'.'<br>';

                $data = array(
                    'uid'=>$value['uid'],
                    'wx_price'=>$wx_price,
                    'ali_price'=>$ali_price,
                    'union_price'=>$union_price,
                    'cash_price'=>$cash_price,
                    'double_back'=>$double_back,
                    'cash_back'=>$cash_back,
                    'wx_nums'=>$wx_nums,
                    'ali_nums'=>$ali_nums,
                    'union_nums'=>$union_nums,
                    'cash_nums'=>$cash_nums,
                    'double_back_nums'=>$double_back_nums,
                    'cash_back_nums'=>$cash_back_nums,
                    'merchant_price'=>$merchant_price,
                    'merchant_nums'=>$merchant_nums,
                    'agent_price'=>$agent_price,
                    'agent_nums'=>$agent_nums,
                    'order_benefit'=>$order_benefit,
                    'order_benefit_nums'=>$order_benefit_nums,
                    'wx_recharge'=>$wx_recharge,
                    'wx_recharge_nums'=>$wx_recharge_nums,
                    'ali_recharge'=>$ali_recharge,
                    'ali_recharge_nums'=>$ali_recharge_nums,
                    'cdk_price'=>$cdk_price,
                    'cdk_nums'=>$cdk_nums,
                    'add_time'=>time(),
                    'date'=>$date,
                    'month'=>date('Ym',$start_time));
                M('pay_statistics')->add($data);
            }
            echo "总总总总笔数：".$nums.'<br>';
//        }
        exit();
    }

    protected function check_yue($card_code)
    {
        $type = M('screen_memcard_use')->alias('mu')
            ->join("join ypt_screen_memcard m on m.id=mu.memcard_id")
            ->where(array('mu.card_code'=>$card_code))
            ->field('m.is_agent')
            ->find();
        if($type['is_agent']){
            return 2;
        }else{
            return 1;
        }

    }

}