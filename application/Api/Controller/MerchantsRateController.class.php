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

}