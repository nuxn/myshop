<?php
namespace Pay\Controller;

use Common\Controller\AdminbaseController;

class AgentadminController extends AdminbaseController
{
    protected $merchant;
    protected $pay;
    protected $user;
    protected $agent;

    function _initialize()
    {
        parent::_initialize();
        $this->merchant = M("merchants");
        $this->pay = M('pay');
        $this->user = M("merchants_users");
        $this->agent = M("merchants_agent");
    }

//    过去的
    public function index1()
    {
        unset($_SESSION['id']);
        if (IS_POST) {
            $start_time = strtotime(I('start_time'));
            $end_time = strtotime(I('end_time'));
            if ((int)$end_time < (int)$start_time) {
                $this->error("开始时间不能小于结束时间");
            }
            if ($start_time && $start_time) {
                $map['paytime'] = array(array('EGT', $start_time), array('ELT', $end_time));
            }
        }

//        1先将数据进行整理对接
        $pays = $this->merchant->alias("a")
            ->join("left join __PAY__ p on a.id = p.merchant_id")
            ->where($map)
            ->join("left join __MERCHANTS_USERS__ u on a.uid = u.id")
            ->field("a.id,a.uid,p.price,u.agent_id,p.paystyle_id,ifnull(sum(if( p.status =1,p.price, 0)),0) as total_price,ifnull(sum(if( p.status =1,1, 0)),0) as total_num,
            sum( if( p.paystyle_id =1 And p.status =1, 1, 0)) as per_weixin_num,sum( if( p.paystyle_id =2 And p.status =1, 1, 0)) as per_ali_num,
            sum( if( p.paystyle_id =1 And p.status =1,p.price, 0)) as per_wei_price,sum( if( p.paystyle_id =2 And p.status =1,p.price, 0)) as per_ali_price")
            ->group('a.id')
            ->select();
//        2 无限循环排布到一级代理商,并且进行二位数组吧、排序
        foreach ($pays as $k => &$v) {
            $id = $v['agent_id'];
            $v['agent_first'] = $this->search_first($id);
            $users = M('merchants_users')->where("id = $id")->find();
            $v['agent_telphone'] = $users['user_phone'];
            if ($v['agent_telphone'] == "") {
                $v['agent_name'] = "";
            }
            $agent_phone = $v['agent_telphone'];
            $v['agent_name'] = $this->user->where("user_phone='$agent_phone'")->getField("user_name");
        }
        $sort_pay = array2sort($pays, "agent_first");
//        对代理商的信息进行整理输出
        $total_agent = $this->get_total($sort_pay);
        unset($total_agent[0]);
//        数组分页
        $count = count($total_agent);
        $page = $this->page($count, 20);
        $list = array_slice($total_agent, $page->firstRow, $page->listRows);
        $this->assign("page", $page->show('Admin'));
        $this->assign("total_agent", $list);
        $this->display();
    }

// 更改之后的
    public function index2()
    {
        //获得一级代理
        if (I('is_one')) {
            $where = 'b.role_id = 2 and a.agent_id = 0';
        } else {
            $where = 'b.role_id = 2';
        }
        //$agent_id = 1;
        if ($agent_id = I('agent_id')) {
            $merchant_id = M()->query('select  getchild(' . $agent_id . ') as a');
            $merchant_id = $merchant_id[0]['a'];
            $where = 'b.role_id = 2';
            $where .= ' and a.id in (' . $merchant_id . ')';
        }
        if ($agent_name = trim(I('agent_name'))) {
            $where .= ' and c.agent_name like' . "'%$agent_name%'";
        }
        if ($user_phone = trim(I('user_phone'))) {
            $where .= ' and a.user_phone=' . $user_phone;
        }
        $count = M('merchants_users')->alias('a')
            ->join('__MERCHANTS_ROLE_USERS__ b on a.id = b.uid')
            ->join('__MERCHANTS_AGENT__ c on a.id = c.uid')
            ->where($where)
            ->field('c.id')
            ->count();
        $page = $this->page($count, 20);
        $list = M('merchants_users')->alias('a')
            ->join('__MERCHANTS_ROLE_USERS__ b on a.id = b.uid')
            ->join('__MERCHANTS_AGENT__ c on a.id = c.uid')
            //->where('b.role_id = 2 and a.agent_id = 0')
            ->where($where)
            ->field('c.uid as id,a.user_phone,c.agent_name,c.juese')
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();

        foreach ($list as &$v) {
            //统计每个代理商的钱
            $v = array_merge($v, $this->month($v['id']));
        }
        $this->assign("page", $page->show('Admin'));
        $this->assign("agents", $list);
        $this->display();
    }

    // 更改之后的
    public function index(){
            //获得一级代理
        if (I('is_one')) {
            $where = 'b.role_id = 2 and a.agent_id = 0';
        } else {
            $where = 'b.role_id = 2';
        }
        //$agent_id = 1;
        if ($agent_id = I('agent_id')) {
            $merchant_id = M()->query('select  getchild(' . $agent_id . ') as a');
            $merchant_id = $merchant_id[0]['a'];
            $where = 'b.role_id = 2';
            $where .= ' and a.id in (' . $merchant_id . ')';
        }
        if ($agent_name = trim(I('agent_name'))) {
            $where .= ' and c.agent_name like' . "'%$agent_name%'";
        }
        if ($user_phone = trim(I('user_phone'))) {
            $where .= ' and a.user_phone=' . $user_phone;
        }
        $count = M('merchants_users')->alias('a')
            ->join('__MERCHANTS_ROLE_USERS__ b on a.id = b.uid')
            ->join('__MERCHANTS_AGENT__ c on a.id = c.uid')
            ->where($where)
            ->field('c.id')
            ->count();
        $page = $this->page($count, 20);
        $list = M('merchants_users')->alias('a')
            ->join('__MERCHANTS_ROLE_USERS__ b on a.id = b.uid')
            ->join('__MERCHANTS_AGENT__ c on a.id = c.uid')
            //->where('b.role_id = 2 and a.agent_id = 0')
            ->where($where)
            ->field('c.uid as id,a.user_phone,c.agent_name,c.juese')
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
            foreach($list as $k => $v){
                //统计每个代理商的钱
                // $v['time'] =date('Y-m',$v['add_time']);
                // $time2 = date('ym',$v['add_time']);
                $id = $v['id'];
                // $time = date('ym');
                // unset($list[$k]);
                // dump($time);dump($time2);
                // while ($time >= $time2) {
                //获取时间字符串
                $time = strtotime("-1 months",time());
                $time = date('ym',$time);
                $t = $this->time_string($time);
                $v['time'] = $t['time3'];  //当前月份
                $v['start_time'] = date('Y-m-d H:i:s',strtotime(date('Y-m',$t['time1'])));  //开始时间
                $start_time = strtotime($v['start_time']);
                $v['end_time'] = date('Y-m-d H:i:s',strtotime("+1 months",$start_time));    //结束时间
                $uid = M()->query('select getchild('.$v['id'].') as uids');
                $uids = $uid[0]['uids'];
                $uids = $this->get_merchant_id($uids); //获取商户id
                // dump($uids);
                $map['a.merchant_id'] = array('in',$uids);
                $map['a.status'] = '1';
                $map['paytime'] = array(array('EGT', strtotime($v['start_time'])), array('ELT', strtotime($v['end_time'])));
                //获取当月流水
                $list[$k] = array_merge($v,$this->month($id));

                $last_month = date('Y-m',strtotime('-1 month'));
                $list[$k]['pay_month'] = M('pay_month')->where(array('agent_id'=>$id,'date'=>$last_month))->getField('status');
                // dump($map);
                if($uids){
                    // dump('2222222222222222');
                    $rabate = $this->d1($map,$id);  //d1返佣
                    $rabate0 = $this->d0($map,$id);//d0返佣
                    $list[$k]['nums'] = $rabate['nums'];
                    $list[$k]['nums0'] = $rabate0['nums0'];
                    $list[$k]['price'] = $rabate['price'];
                    $list[$k]['price0'] = $rabate0['price0'];
                    $list[$k]['rebate'] = $rabate['rebate'];
                    $list[$k]['rebate0'] = $rabate0['rebate0'];
                }else{
                    $list[$k]['nums'] = 0;
                    $list[$k]['nums0'] = 0;
                    $list[$k]['price'] = 0;
                    $list[$k]['price0'] = 0;
                    $list[$k]['rebate'] = 0;
                    $list[$k]['rebate0'] = 0;
                }



                // dump($list);
            // }
                
            }
            
            //dump($list);
            $this->assign("page", $page->show('Admin'));
            $this->assign("agents", $list);
            $this->display();
    }
    public function change_pay_month_status()
    {
        $id = I('post.id');
        $res = M('pay_month')->where(array('agent_id'=>$id,'date'=>date('Y-m',strtotime('-1 month'))))->setField('status',2);
        //header('Content-Type:application/json; charset=utf-8');
        if($res){
            exit(json_encode(array('code'=>1)));
        }else{
            exit(json_encode(array('code'=>0)));
        }

    }
    public function get_merchant_id($uid){
            $where['uid'] = array('in',$uid);
            $id = M('merchants')->where($where)->getField('id',true);
            // echo M('merchants')->getLastSql();dump($id);
            if($id){
                $id = implode(',',$id);
            }
            return $id; 
    }
    /**
     * 获取时间字符串
     */
    public function time_string($time){
        $y = substr($time,0,2).'-';
        $string = $y.'截取年份<br>';
        $m = '-'.substr($time,2,4).'-';
        $string .= $m.'截取月份<br>';
        $t = date('Y-m-d H:i:s');
        $string .= $t .'现在时间<br>';
        $search= substr($t,2,2).'-';$string .= $search .'现在年份<br>';
        $search2= '-'.substr($t,5,2).'-';$string .= $search2 .'现在月份<br>';
        $str = str_replace($search, $y, $t);$string .= $str .'替换年份<br>';
        $time1 = str_replace($search2, $m, $str);$string .= $time1 .'替换月份<br>';
        $time3 = substr($time1,0,7);
        $time1 = strtotime($time3);
        return array('time1'=>$time1,'time3'=>$time3);
    }
    //返佣计算
    public function d1($map,$agent_id)
    {
        $pay = $this->pay->alias('a')
            ->field('price,cost_rate,paystyle_id')
            ->where($map)
            ->where('poundage=0 or poundage is null')
            ->select();
        // echo $this->pay->getLastSql(); echo "<br>"; 
        $agent = M('merchants_agent')->where(array('uid'=>$agent_id))->find();
        $rebate = 0;
        $price = 0;
        $num = 0;
        // dump($pay);
        foreach($pay as $vv){
            // dump($num);
            $num++;
            if($vv['cost_rate']==0){
                continue;
            }
            $price += $vv['price'];
            
            $rebate = bcadd($rebate,bcdiv($vv['price']*($vv['cost_rate']-$agent[$vv['paystyle_id']==1?'wx_rate':'ali_rate']),'100',5),5);
        }
        return array('nums'=>$num,'price'=>$price,'rebate'=>$rebate);
    }

    public function d0($map,$agent_id)
    {
        $pay0 = $this->pay->alias('a')
            ->field('price,cost_rate,paystyle_id,repaid_rate,min_repaid_amount,poundage')
            ->where($map)
            ->where('poundage>0')
            ->select();
        $rebate = 0;
        $price = 0;
        $num = 0;
        $poundage = 0;
        $agent = M('merchants_agent')->where(array('uid'=>$agent_id))->find();
        foreach($pay0 as $vv){
            $num++;
            
            $price += $vv['price'];
            //每笔手续费
            $poundage = $vv['poundage'] - $agent['poundage'];
            $m_rapaid = ($vv['price']*$agent['repaid_rate']/1000 - $agent['repaid_price'])>0?($vv['price']*$agent['repaid_rate']/1000 - $agent['repaid_price']):$agent['repaid_price'];
            // var_dump($vv['price'],$agent['repaid_rate'],$agent['repaid_price'],$m_rapaid);
            $d_rapaid = ($vv['price']*$vv['repaid_rate']/1000 - $vv['min_repaid_amount'])>0?($vv['price']*$vv['repaid_rate']/1000 - $vv['min_repaid_amount']):$vv['min_repaid_amount'];
            $rapaid = $d_rapaid-$m_rapaid ;
            
            $rebate0 += (bcdiv($vv['price']*($vv['cost_rate']-$agent[$vv['paystyle_id']==1?'wx_rate_0':'ali_rate_0']), '100', 5)+$rapaid+$poundage);
            // $rebate += bcdiv($vv['price']*($vv['cost_rate']-$agent[$vv['paystyle_id']==1?'wx_rate':'ali_rate']), '100', 5);
        }
        // dump($num);
        // dump($price);
        // dump($rebate0);
        return array('nums0'=>$num,'price0'=>$price,'rebate0'=>$rebate0,'poundage'=>$poundage);
    }

    public function month($agent_id, $month = 0)
    {
        $month = $month == 0 ? date('ym') : $month;
        $data = M('pay_day')->field('sum(price) as price,sum(nums) as nums,sum(rebate) as rebate,sum(price0) as price0,sum(nums0) as nums0,sum(rebate0) as rebate0')->where(array('agent_id' => $agent_id, 'date' => array('like', $month . '__')))->find();
        return $data;
    }

    public function index_old()
    {
        unset($_SESSION['id']);
//        S('agents',null);
        F("count", null);
        if (!S("agents")) {
            //上月
            $beginLastmonth = mktime(0, 0, 0, date("m") - 1, 1, date("Y"));
            $endLastmonth = mktime(23, 59, 59, date("m"), 0, date("Y")) + 1;
            $lastmonth = array($beginLastmonth, $endLastmonth);
            $agents = $this->agent
                ->alias("a")
                ->join("left join __MERCHANTS_USERS__ u on a.uid=u.id")
                ->field("a.id as aid,u.id as uid,u.user_phone,a.agent_name,a.juese")
                ->select();
            foreach ($agents as $k => &$v) {
                unset($mids);
                $mids = $this->get_merchant($v['uid']);
                $v['merchant_num'] = count($mids);
                $v['lastmonth'] = $this->get_merchant_detail1($lastmonth, $mids, $v['aid']); //上月收益
                // $v['cost_total'] = $this->get_merchant_detail("",$mids);        //总收益
                if (!empty($mids)) {
                    $v['total'] = $this->get_merchant_total($mids);
                } else {
                    $v['total'] = array("total_price" => 0, "total_num" => 0, "per_weixin_num" => 0, "per_ali_num" => 0,
                        "per_cash_num" => 0, "per_wei_price" => 0, "per_ali_price" => 0, "per_cash_price" => 0);
                }
            }
            S('agents', $agents, 3600);
        } else {
            $agents = S("agents");
        }
        $count = count($agents);
        $page = $this->page($count, 20);
        $list = array_slice($agents, $page->firstRow, $page->listRows);
        $this->assign("page", $page->show('Admin'));
        $this->assign("formget", array_merge($_GET, $_POST));
        $this->assign("agents", $list);
        $this->display();
    }

//    获取代理商上月和总返佣
    public function get_merchant_detail($time = "", $mids)
    {
        if ($time != "") {
            $map['paytime'] = array("between", $time);
        }
        $map['p.status'] = 1;
        $files = "p.paystyle_id,p.price,wz.aliCostRate,wz.wxCostRate";
        if ($mids) {
            $map['merchant_id'] = array('in', $mids);
        } else {
            return 0;
        }
        $pays = $this->pay->alias('p')
            ->join("left join __MERCHANTS__ m on m.id=p.merchant_id")
            ->join('left join __MERCHANTS_USERS__ u on m.uid = u.id')
            ->join('left join __MERCHANTS_UPWZ__ wz on wz.mid = p.merchant_id')
            ->field($files)
            ->where($map)
            ->select();

        $total = 0;
        foreach ($pays as $k => &$v) {
            if ($v['aliCostRate'] == null) $v['aliCostRate'] = 0.55;
            if ($v['wxCostRate'] == null) $v['wxCostRate'] = 0.6;
            if ($v['paystyle_id'] == 1) $v['agent_money'] = sprintf("%.3f", ($v['wxCostRate'] - 0.25) * $v['price'] * 0.01 * 0.8);
            if ($v['paystyle_id'] == 2) $v['agent_money'] = sprintf("%.3f", ($v['aliCostRate'] - 0.25) * $v['price'] * 0.01 * 0.8);
            if ($v['paystyle_id'] == 5) unset($pays[$k]);
            $total += $v['agent_money'];
        }
        $total = sprintf("%.3f", $total);
        return $total;
    }

    //    获取代理商上月和总返佣   2017/7/25
    public function get_merchant_detail1($time = "", $mids, $agent_id)
    {
        if ($time != "") {
            $map['paytime'] = array("between", $time);
        }
        $map['status'] = 1;
        $map['paystyle_id'] = array('in', array(1, 2));
        $files = "paystyle_id,price,cost_rate";
        if ($mids) {
            $map['merchant_id'] = array('in', $mids);
        } else {
            return array('agent_money' => 0, 'total' => 0);
        }
        $pays = $this->pay->where($map)->field($files)->select();
        $agent = $this->agent->where(array("id" => $agent_id))->find();
        $agent_money = 0;
        $total = $this->pay->where($map)->field($files)->sum("price");
        $rate = $this->get_rate($total);
        if ($agent['agent_style'] == 1) {//判断支付方式
            $wx_rate = $agent['wx_rate'];
            $ali_rate = $agent['ali_rate'];
            foreach ($pays as $k => &$v) {
                if ($v['paystyle_id'] == 1) { //判断微信的还是里支付宝的
                    if ($v['cost_rate'] == "0.00" || $v['cost_rate'] == null || $v['cost_rate'] < $wx_rate) {
                        $v['agent_money'] = "0.000";
                        $v['cost_rate'] = 0;
                    } else {
                        $v['agent_money'] = sprintf("%.3f", ($v['cost_rate'] - $wx_rate) * $v['price'] * 0.01 * $rate);
                    }
                    $agent_money += $v['agent_money'];
                }
                if ($v['paystyle_id'] == 2) {
                    if ($v['cost_rate'] == "0.00" || $v['cost_rate'] == null || $v['cost_rate'] < $ali_rate) {
                        $v['agent_money'] = "0.000";
                        $v['cost_rate'] = 0;
                    } else {
                        $v['agent_money'] = sprintf("%.3f", ($v['cost_rate'] - $ali_rate) * $v['price'] * 0.01 * $rate);
                    }
                    $agent_money += $v['agent_money'];
                }
            }
        }
        if ($agent['agent_style'] == 2) {
            $wx_rate = $agent['wx_rate'];
            $ali_rate = $agent['ali_rate'];
            foreach ($pays as $k => &$v) {
                if ($v['paystyle_id'] == 1) { //判断微信的还是里支付宝的
                    if ($v['cost_rate'] == "0.00" || $v['cost_rate'] == null || $v['cost_rate'] < $wx_rate) {
                        $v['agent_money'] = "0.000";
                        $v['cost_rate'] = 0;
                    } else {
                        $v['agent_money'] = sprintf("%.3f", ($v['cost_rate'] - $wx_rate) * $v['price'] * 0.01);
                    }
                    $agent_money += $v['agent_money'];
                }
                if ($v['paystyle_id'] == 2) {
                    if ($v['cost_rate'] == "0.00" || $v['cost_rate'] == null || $v['cost_rate'] < $ali_rate) {
                        $v['agent_money'] = "0.000";
                        $v['cost_rate'] = 0;
                    } else {
                        $v['agent_money'] = sprintf("%.3f", ($v['cost_rate'] - $ali_rate) * $v['price'] * 0.01);
                    }
                    $agent_money += $v['agent_money'];
                }
            }
        }
        $agent_money = sprintf("%.3f", $agent_money);
        if (!$agent_money || !$total) return array('agent_money' => 0, 'total' => 0);
        return array('agent_money' => $agent_money, 'total' => $total);
    }

//   获取扣率
    public function get_rate($total)
    {
        if (!$total) return 0.7;
        if ($total < 1000000) {
            return 0.7;
        } else if ($total >= 1000000 And $total <= 5000000) {
            return 0.8;
        } else if ($total > 10000000) {
            return 0.9;
        } else {
            return 0.9;
        }
    }


//  获取代理商流水汇总
    public function get_merchant_total($mids)
    {
        if ($mids) $map['merchant_id'] = array('in', $mids);
        $map['p.status'] = 1;

//        $files ="ifnull(sum(if( p.status =1,p.price, 0)),0) as total_price,ifnull(sum( if(p.status=1, 1, 0)),0) as total_num,
//            ifnull(sum( if( p.paystyle_id =1 And p.status=1, 1, 0)),0) as per_weixin_num,ifnull(sum( if( p.paystyle_id =2 And p.status=1, 1, 0)),0) as per_ali_num, ifnull(sum( if( p.paystyle_id =5 And p.status=1, 1, 0)),0) as per_cash_num,
//            ifnull(sum( if( p.paystyle_id =1 And p.status=1,p.price, 0)),0) as per_wei_price,ifnull(sum( if( p.paystyle_id =2 And p.status=1,p.price, 0)),0) as per_ali_price,ifnull(sum( if( p.paystyle_id =5 And p.status=1,p.price, 0)),0) as per_cash_price";

//  2017.7.25
        $files = "ifnull(sum(if( p.status =1,p.price, 0)),0) as total_price,
            ifnull(sum( if( p.paystyle_id =1 And p.status=1,p.price, 0)),0) as per_wei_price,
            ifnull(sum( if( p.paystyle_id =2 And p.status=1,p.price, 0)),0) as per_ali_price,
            ifnull(sum( if( p.paystyle_id =5 And p.status=1,p.price, 0)),0) as per_cash_price";

        $pays = $this->pay->alias('p')
            ->join("left join __MERCHANTS__ m on m.id=p.merchant_id")
            ->join('left join __MERCHANTS_USERS__ u on m.uid = u.id')
            ->join('left join __MERCHANTS_UPWZ__ wz on wz.mid = p.merchant_id')
            ->field($files)
            ->where($map)
            ->find();
        return $pays;
    }

    public function upload_excel()
    {
        $agents_fee = S("agents");
        Vendor("PHPExcel.PHPExcel");
        //引入phpexcel类文件
        $objPHPExcel = new \PHPExcel();

        // 设置文件的一些属性，在xls文件——>属性——>详细信息里可以看到这些值，xml表格里是没有这些值的
        $objPHPExcel
            ->getProperties()//获得文件属性对象，给下文提供设置资源
            ->setCreator("Maarten Balliauw")//设置文件的创建者
            ->setLastModifiedBy("Maarten Balliauw")//设置最后修改者
            ->setTitle("Office 2007 XLSX Test Document")//设置标题
            ->setSubject("Office 2007 XLSX Test Document")//设置主题
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")//设置备注
            ->setKeywords("office 2007 openxml php")//设置标记
            ->setCategory("Test result file");                //设置类别
        // 位置aaa  *为下文代码位置提供锚
        // 给表格添加数据
        $this_biao = $objPHPExcel->setActiveSheetIndex(0);             //设置第一个内置表（一个xls文件里可以有多个表）为活动的
        $excel_canshu = $objPHPExcel->getActiveSheet();

        $excel_canshu->getDefaultRowDimension()->setRowHeight(30);
        $excel_canshu->getColumnDimension('A')->setWidth(20);
        $excel_canshu->getColumnDimension('B')->setWidth(20);
        $excel_canshu->getColumnDimension('C')->setWidth(20);
        $excel_canshu->getColumnDimension('D')->setWidth(20);
        $excel_canshu->getColumnDimension('E')->setWidth(30);
        $excel_canshu->getColumnDimension('F')->setWidth(30);
        $excel_canshu->getColumnDimension('G')->setWidth(30);
        $excel_canshu->getColumnDimension('H')->setWidth(40);
        $excel_canshu->getColumnDimension('I')->setWidth(40);
        $this_biao->setCellValue('A1', '代理商的ID')
            ->setCellValue('B1', '代理商的电话')
            ->setCellValue('C1', '代理商的名称')
            ->setCellValue('D1', '交易的总商户数')
            ->setCellValue('E1', '交易总额(元)')
            ->setCellValue('F1', '微信交易总额(元)')
            ->setCellValue('G1', '支付宝交易总额(元):')
            ->setCellValue('H1', '上个月总流水(元):')
            ->setCellValue('I1', '上个月返佣值(元)');
        $i = 2;
        foreach ($agents_fee as $k => $v) {

            $objDrawing[$k] = new \PHPExcel_Worksheet_Drawing();
            $objDrawing[$k]->setWorksheet($objPHPExcel->getActiveSheet());

            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A' . $i, $v['aid'])
                ->setCellValue('B' . $i, $v['user_phone'])
                ->setCellValue('C' . $i, $v['agent_name'])
                ->setCellValue('D' . $i, $v['merchant_num'])
                ->setCellValue('E' . $i, $v['total']['total_price'])
                ->setCellValue('F' . $i, $v['total']['per_wei_price'])
                ->setCellValue('G' . $i, $v['total']['per_ali_price'])
                ->setCellValue('H' . $i, $v['lastmonth']['total'])
                ->setCellValue('I' . $i, $v['lastmonth']['agent_money']);
            //                ->setCellValue('D'.$i,$v['guige'])
            //                ->setCellValue('E'.$i,$v['pcode'])
            //                ->setCellValueExplicit('F'.$i,$v['goods_tiaoxm'],\PHPExcel_Cell_DataType::TYPE_STRING)
            //                ->setCellValue('G'.$i,fencheng($v['id']))//调用自定义函数计算价格
            //                ->setCellValue('H'.$i,$tax);
            $i++;
        }
        //得到当前活动的表,注意下文教程中会经常用到$objActSheet
        $objActSheet = $objPHPExcel->getActiveSheet();
        // 位置bbb  *为下文代码位置提供锚
        // 给当前活动的表设置名称
        $objActSheet->setTitle('洋仆淘代理商对账表');

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="洋仆淘代理商对账表.xls"');
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        $this->success("导出excel表格成功", U('index'));
    }

//    无限循环找到一级代理
    public function search_first($id)
    {
        if ($id != 0) {
            $user = M('merchants_users')->where("id = $id")->find();
            $id = $user['agent_id'] == 0 ? $id : $this->search_first($user['agent_id']);
        }
        return $id;
    }

    public function get_total($sort_pay)
    {
        $total_agent = array();
        $demo = 0;
        foreach ($sort_pay as $k => $v) {
            if ($k == 0) {
//                代理商信息
                $total_agent[$demo]['agent_first'] = $v['agent_first'];
                $total_agent[$demo]['agent_telphone'] = $v['agent_telphone'];
                $total_agent[$demo]['agent_name'] = $v['agent_name'];
//                代理商交易金额区分以及数量
                $total_agent[$demo]['totals_price'] = (float)$v['total_price'];
                $total_agent[$demo]['total_num'] = $v['total_num'];
                $total_agent[$demo]['shop_num'] = 1;
                $total_agent[$demo]['per_weixin_num'] = $v['per_weixin_num'];
                $total_agent[$demo]['per_ali_num'] = $v['per_ali_num'];
                $total_agent[$demo]['per_wei_price'] = $v['per_wei_price'];
                $total_agent[$demo]['per_ali_price'] = $v['per_ali_price'];
            } else {
                if ($total_agent[$demo]['agent_first'] != $v['agent_first']) {
                    $demo++;
                    $total_agent[$demo]['agent_first'] = $v['agent_first'];
                    $total_agent[$demo]['agent_telphone'] = $v['agent_telphone'];
                    $total_agent[$demo]['agent_name'] = $v['agent_name'];
                    $total_agent[$demo]['totals_price'] = (float)$v['total_price'];
                    $total_agent[$demo]['total_num'] = $v['total_num'];
                    $total_agent[$demo]['shop_num'] = 1;
                    $total_agent[$demo]['per_weixin_num'] = $v['per_weixin_num'];
                    $total_agent[$demo]['per_ali_num'] = $v['per_ali_num'];
                    $total_agent[$demo]['per_wei_price'] = $v['per_wei_price'];
                    $total_agent[$demo]['per_ali_price'] = $v['per_ali_price'];
                } else {
                    $total_agent[$demo]['totals_price'] += (float)$v['total_price'];
                    $total_agent[$demo]['total_num'] += $v['total_num'];
                    $total_agent[$demo]['shop_num']++;
                    $total_agent[$demo]['per_weixin_num'] += $v['per_weixin_num'];
                    $total_agent[$demo]['per_ali_num'] += $v['per_ali_num'];
                    $total_agent[$demo]['per_wei_price'] += $v['per_wei_price'];
                    $total_agent[$demo]['per_ali_price'] += $v['per_ali_price'];

                }
            }
        }
        return $total_agent;
    }

    public function get_detail()
    {
        ($uid = I('id')) || $this->error('uid is empty');
        ini_set('memory_limit', '1000M');
        $agent_id = $uid;
        $date = date('Y-m');
        if ($start_time = I('start_time')) {
            $start_time = strtotime($start_time);
        } else {
            $start_time = strtotime($date);
        }
        if ($end_time = I('end_time')) {
            $end_time = strtotime($end_time);
        } else {
            $end_time = strtotime("+1 month", $start_time);
        }
        $map['a.paytime'] = array('BETWEEN',array($start_time,$end_time));
        if($remark = I('remark')){
            $map['a.remark'] = $remark;
        }
        if($paystyle = I('paystyle')){
            $map['a.paystyle_id'] = $paystyle;
        }
        if($user_phone = I('user_phone')){
            $map['b.user_phone'] = $user_phone;
        }
        if($merchant_name = I('merchant_name')){
            $map['u.merchant_name'] = array('LIKE',"%$merchant_name%");;
        }
        $uid = M()->query('select getchild(' . $uid . ') as uids');
        $uids = $uid[0]['uids'];
        $uids = explode(',',$uids);
        foreach ($uids as $k => $v) {
            if(empty($v)){
                unset($uids[$k]);
            }
        }
        $uids = get_merchant_id(implode(',',$uids));

        //查看这些商家的流水
        //首先统计d1
//        $count = $this->pay
//                ->field('price,cost_rate,paystyle_id')
//                ->where('paytime >='.$start_time.' and paytime < '.$end_time.' and merchant_id in ('.$uids.') and status=1')
//                ->count();
//        $page = $this->page($count, 20);
//        $this->assign("page", $page->show('Admin'));
        // dump($uids);
        if ($uids) {
            $pay = $this->pay
                ->alias('a')
                ->field('a.id,a.merchant_id,a.mode,a.bank,a.paystyle_id,a.status,a.bill_id,a.paytime,a.remark,a.repaid_rate,a.price,a.min_repaid_amount,a.poundage,a.cost_rate,a.cardtype,a.paystyle_id,a.agent_status,a.min_repaid_amount,u.merchant_name as user_name,b.user_phone,c.agent_name')
                ->join("left join __MERCHANTS__ u on a.merchant_id = u.id")
                ->join("left join __MERCHANTS_USERS__ b on u.uid = b.id")
                ->join('left join __MERCHANTS_AGENT__ c on c.uid = b.pid')
                ->where('a.paystyle_id in (1,2,3,5) and a.merchant_id in (' . $uids . ') and a.status=1')
                ->where($map)
                ->order('a.id desc')
                ->select();
							// echo $this->pay->_SQL();

        } else {
            $pay = array();
        }
        $price = 0;
        $rebate = 0;
        $num = 0;
        //查出代理商的利率
        $agent = M('merchants_agent')->where(array('uid' => $agent_id))->find();
        foreach ($pay as &$v) {
            $string = '';

            //判断是否是d0还是d1的
            if (!($v['poundage'] > 0) || $v['bank'] == 7) {
                $string .= '银行:' . $v['bank'];
                $v['type'] = 1;
                if ($v['cost_rate'] > 0) {
                    if ($v['bank'] == 7) {
                        $v['agent_rate'] = ($agent[$v['paystyle_id'] == 1 ? 'wx_rate' : 'ali_rate']);
                        $string .= '费率:' . $v['cost_rate'] . '-' . $v['agent_rate'];
                        $v['rebate'] = $v['price'] * ($v['cost_rate'] - $v['agent_rate']) / 100;
                    } elseif ($v['bank'] == 11 && $v['paystyle_id'] == 3) {
                        if ($v['cardtype'] == '00' || $v['cardtype'] == '03') {
                            $v['agent_rate'] = '0.41';
                        } elseif ($v['cardtype'] == '01' || $v['cardtype'] == '02') {
                            $v['agent_rate'] = '0.53';
                        }
                        $string .= '费率:' . $v['cost_rate'] . '-' . $v['agent_rate'];
                        $v['rebate'] = $v['price'] * ($v['cost_rate'] - $v['agent_rate']) / 100;
                    } else {
                        $v['agent_rate'] = $agent[$v['paystyle_id'] == 1 ? 'wx_rate' : 'ali_rate'];
                        $string .= '费率:' . $v['cost_rate'] . '-' . $v['agent_rate'];
                        $v['rebate'] = $v['price'] * ($v['cost_rate'] - $v['agent_rate']) / 100;
                    }
                } else {
                    $v['rebate'] = 0;
                }
                $rebate += $v['rebate'];
                $price += $v['price'];
                $num++;
                $v['all_rebate'] += $rebate;
                $v['all_price'] += $price;
                $v['num'] = $num;
                $v['string'] = $string;
            } else {
                $v['type'] = 0;
                $v['agent_rate'] = $agent[$v['paystyle_id'] == 1 ? 'wx_rate_0' : 'ali_rate_0'];
                if ($v['cost_rate'] > 0) {
                    $string .= '代付费：' . $v['poundage'] . '-' . $agent['poundage'] . '<br>';
                    $string .= '代付垫资费(千分比)：' . ($agent['repaid_rate']) . ',' . $agent['repaid_price'] . '<br>';
                    $string .= '商户垫资费：' . ($v['repaid_rate']) . ',' . $v['min_repaid_amount'] . '<br>';
                    $string .= '费率(百分比):' . ($v['cost_rate']) . '-' . ($agent[$v['paystyle_id'] == 1 ? 'wx_rate_0' : 'ali_rate_0']);

                    $poundage = $v['poundage'] - $agent['poundage'];
                    $v['poundage_price'] = $poundage;

                    //每笔的代付费
                    $a = $v['price'] * $agent['repaid_rate'] / 1000;
                    $m_rapaid = $a - $agent['repaid_price'] > 0 ? $a : $agent['repaid_price'];
                    $a = $v['price'] * $v['repaid_rate'] / 1000;
                    $d_rapaid = $a - $v['min_repaid_amount'] > 0 ? $a : $v['min_repaid_amount'];
                    $string .= '<br>代付费' . $m_rapaid . ',' . $d_rapaid . '<br>';
                    $rapaid = $d_rapaid - $m_rapaid;
                    $v['rapaid'] = $rapaid;
                    $string .= '统计：' . ($v['price'] * ($v['cost_rate'] - $v['agent_rate']) / 100) . ',' . $rapaid . ',' . $poundage;

                    $v['rebate'] = ($v['price'] * ($v['cost_rate'] - $agent[$v['paystyle_id'] == 1 ? 'wx_rate_0' : 'ali_rate_0']) / 100 + $rapaid + $poundage);
                } else {
                    $v['rebate'] = 0;
                }
                $v['string'] = $string;
                $rebate += $v['rebate'];
                $price += $v['price'];
                $num++;
                $v['all_rebate'] += $rebate;
                $v['all_price'] += $price;
                $v['num'] = $num;
            }

        }
        S('pay_' . $agent_id, $pay);
        $formget = array_merge($_GET,$_POST);
        $formget['start_time'] = date('Y-m-d H:i:s', $start_time);
        $formget['end_time'] = date('Y-m-d H:i:s', $end_time);
        $this->assign('formget', $formget);

        $count=count($pay);
        $page = $this->page($count, 20);
        $list=array_slice($pay,$page->firstRow,$page->listRows);
        $this->assign("page", $page->show('Admin'));

        $this->assign('list', $list);
        $this->display('detail');
    }

//    某个代理商支付详情  修改2017.7.25
    public function get_detail1()
    {
        $count = I("total");
        if (F("count")) { //从上个月记录下总流水
            $count = F("count");
        } else {
            F("count", $count);
        }
        F("total", null);
        F("pays", null);

        if (!$count) $this->error("该代理商没有流水");
        if (session('id')) {
            $agent_id = session("id");
        } else {
            $agent_id = I("id"); //代理商的id
            session("id", $agent_id);
        }
        $agent = $this->agent->where(array("uid" => $agent_id))->find();
        $user_phone = (int)I('user_phone', "", "trim");
        $remark = trim(I('remark'));
        $paystyle_id = I('paystyle');
        $merchant_name = trim(I('merchant_name'));
        $start_time = strtotime(I('start_time'));
        $end_time = strtotime(I('end_time'));
        //上月
        $beginLastmonth = mktime(0, 0, 0, date("m") - 1, 1, date("Y"));
        $endLastmonth = mktime(23, 59, 59, date("m"), 0, date("Y")) + 1;
        if ($start_time && $end_time) {
//            if($start_time < $beginLastmonth ||$end_time > $endLastmonth){
//                $this->error("时间选择超出");
//            }
            $map['paytime'] = array(array('EGT', $start_time), array('ELT', $end_time));
        } else {
            $map['paytime'] = array(array('EGT', $beginLastmonth), array('ELT', $endLastmonth));
        }

        if ($paystyle_id) {
            $map['paystyle_id'] = $paystyle_id;
        } else {
            $map['paystyle_id'] = array('in', array(1, 2));
        }
        if ($merchant_name) {
            $map['m.merchant_name'] = array('LIKE', "%$merchant_name%");
        } else {
            //       获取代理商所选商户
            $merchants = $this->get_merchant($agent_id);
            if ($merchants) {
                $map['merchant_id'] = array('in', $merchants);
            } else {
                $this->error("该代理商没有流水");
            }
        }
        if ($user_phone) {
            $map['user_phone'] = $user_phone;
        }
        if ($remark) {
            $map['remark'] = $remark;
        }
        $map['a.status'] = 1; //只显示成功的
        $pays = $this->pay->alias('a')
            ->join("left join __MERCHANTS__ m on m.id=a.merchant_id")
            ->join('left join __MERCHANTS_USERS__ u on m.uid = u.id')
            ->field("u.id as u_id,u.user_phone,m.merchant_name,a.price,a.cost_rate,a.remark,a.status,a.paytime,a.agent_status,a.id,a.paystyle_id")
            ->where($map)
            ->order("paytime desc")
            ->select();
        $total = 0;
        if ($agent['agent_style'] == 1) {//判断支付方式
            $wx_rate = $agent['wx_rate'];
            $ali_rate = $agent['ali_rate'];
            $rate = $this->get_rate($count);//注意选择时间问题
            foreach ($pays as $k => &$v) {
                if ($v['paystyle_id'] == 1) { //判断微信的还是里支付宝的
                    if ($v['cost_rate'] == "0.00" || $v['cost_rate'] == null || $v['cost_rate'] < $wx_rate) {
                        $v['agent_money'] = "0.000";
                        $v['cost_rate'] = 0;
                    } else {
                        $v['agent_money'] = sprintf("%.3f", ($v['cost_rate'] - $wx_rate) * $v['price'] * 0.01 * $rate);
                    }
                    $total += $v['agent_money'];
                }
                if ($v['paystyle_id'] == 2) {
                    if ($v['cost_rate'] == "0.00" || $v['cost_rate'] == null || $v['cost_rate'] < $ali_rate) {
                        $v['agent_money'] = "0.000";
                        $v['cost_rate'] = 0;
                    } else {
                        $v['agent_money'] = sprintf("%.3f", ($v['cost_rate'] - $ali_rate) * $v['price'] * 0.01 * $rate);
                    }
                    $total += $v['agent_money'];
                }
            }
        }
        if ($agent['agent_style'] == 2) {
            $wx_rate = $agent['wx_rate'];
            $ali_rate = $agent['ali_rate'];
            foreach ($pays as $k => &$v) {
                if ($v['paystyle_id'] == 1) { //判断微信的还是里支付宝的
                    if ($v['cost_rate'] == "0.00" || $v['cost_rate'] == null || $v['cost_rate'] < $wx_rate) {
                        $v['agent_money'] = "0.000";
                        $v['cost_rate'] = 0;
                    } else {
                        $v['agent_money'] = sprintf("%.3f", ($v['cost_rate'] - $wx_rate) * $v['price'] * 0.01);
                    }
                    $total += $v['agent_money'];
                }
                if ($v['paystyle_id'] == 2) {
                    if ($v['cost_rate'] == "0.00" || $v['cost_rate'] == null || $v['cost_rate'] < $ali_rate) {
                        $v['agent_money'] = "0.000";
                        $v['cost_rate'] = 0;
                    } else {
                        $v['agent_money'] = sprintf("%.3f", ($v['cost_rate'] - $ali_rate) * $v['price'] * 0.01);
                    }
                    $total += $v['agent_money'];
                }
            }
        }

        $total = sprintf("%.3f", $total);
        F("total", $total);
        F("pays", $pays);
        $page = count($pays);
        $page = $this->page($page, 20);
        $list = array_slice($pays, $page->firstRow, $page->listRows);
        $this->assign("page", $page->show('Admin'));
        $this->assign("formget", array_merge($_GET, $_POST));
        $this->assign("pays", $list);
        $this->assign("total", $total);
        $this->assign("agent_id", $agent_id);
        $this->display("detail");
    }

    public function upload_excel_detail()
    {
        $agent_id = I('agent_id');
        $data = S('pay_' . $agent_id);

        $n = ceil(count($data) / 3000);
        $excel = array_slice($data, 0, 3000);

        ini_set('memory_limit', '2000M');
        Vendor("PHPExcel.PHPExcel");
        //引入phpexcel类文件
        $objPHPExcel = new \PHPExcel();
        // 设置文件的一些属性，在xls文件——>属性——>详细信息里可以看到这些值，xml表格里是没有这些值的
        $objPHPExcel
            ->getProperties()//获得文件属性对象，给下文提供设置资源
            ->setCreator("Maarten Balliauw")//设置文件的创建者
            ->setLastModifiedBy("Maarten Balliauw")//设置最后修改者
            ->setTitle("Office 2007 XLSX Test Document")//设置标题
            ->setSubject("Office 2007 XLSX Test Document")//设置主题
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")//设置备注
            ->setKeywords("office 2007 openxml php")//设置标记
            ->setCategory("Test result file");                //设置类别
        // 位置aaa  *为下文代码位置提供锚
        for ($a = 0; $a < $n; $a++) {
            if ($a != 0) {
                $objPHPExcel->createSheet();
                // 给表格添加数据
                $objPHPExcel->setactivesheetindex($a)
                    //->setCellValue('A1', '流水ID')
                    //->setCellValue('B1', '商户电话')
                    ->setCellValue('A1', '商户的名称')
                    ->setCellValue('B1', '支付方式')
                    ->setCellValue('C1', '支付金额(元)')
                    ->setCellValue('D1', '总金额(元)')
                    ->setCellValue('E1', '代理费率(%)')
                    ->setCellValue('F1', '商户费率(%)')
                    ->setCellValue('G1', '代理收入(元)')
                    ->setCellValue('H1', '总收入(元)');
                    //->setCellValue('K1', '支付样式')
                    //->setCellValue('L1', '流水号')
                    //->setCellValue('M1', '支付时间')
                    //->setCellValue('N1', '代理商')
                    //->setCellValue('O1', '算法');

//                    ->setCellValue('K1', '返佣和(元)');
                $excel_canshu = $objPHPExcel->getActiveSheet();
                $excel_canshu->getDefaultRowDimension()->setRowHeight(30);
                $excel_canshu->getColumnDimension('A')->setWidth(20);
                $excel_canshu->getColumnDimension('B')->setWidth(20);
                $excel_canshu->getColumnDimension('C')->setWidth(40);
                $excel_canshu->getColumnDimension('D')->setWidth(20);
                $excel_canshu->getColumnDimension('E')->setWidth(30);
                $excel_canshu->getColumnDimension('F')->setWidth(30);
                $excel_canshu->getColumnDimension('G')->setWidth(30);
                $excel_canshu->getColumnDimension('H')->setWidth(30);
                /*$excel_canshu->getColumnDimension('I')->setWidth(40);
                $excel_canshu->getColumnDimension('J')->setWidth(40);
                $excel_canshu->getColumnDimension('K')->setWidth(40);
                $excel_canshu->getColumnDimension('L')->setWidth(40);
                $excel_canshu->getColumnDimension('M')->setWidth(30);
                $excel_canshu->getColumnDimension('N')->setWidth(30);
                $excel_canshu->getColumnDimension('O')->setWidth(30);*/
            } else {
                // 给表格添加数据
                $this_biao = $objPHPExcel->setActiveSheetIndex();             //设置第一个内置表（一个xls文件里可以有多个表）为活动的
                $excel_canshu = $objPHPExcel->getActiveSheet();
                $excel_canshu->getDefaultRowDimension()->setRowHeight(30);
                $excel_canshu->getColumnDimension('A')->setWidth(20);
                $excel_canshu->getColumnDimension('B')->setWidth(20);
                $excel_canshu->getColumnDimension('C')->setWidth(40);
                $excel_canshu->getColumnDimension('D')->setWidth(20);
                $excel_canshu->getColumnDimension('E')->setWidth(30);
                $excel_canshu->getColumnDimension('F')->setWidth(30);
                $excel_canshu->getColumnDimension('G')->setWidth(30);
                $excel_canshu->getColumnDimension('H')->setWidth(30);
                /*$excel_canshu->getColumnDimension('I')->setWidth(40);
                $excel_canshu->getColumnDimension('J')->setWidth(40);
                $excel_canshu->getColumnDimension('K')->setWidth(40);
                $excel_canshu->getColumnDimension('L')->setWidth(40);
                $excel_canshu->getColumnDimension('M')->setWidth(30);
                $excel_canshu->getColumnDimension('N')->setWidth(30);
                $excel_canshu->getColumnDimension('O')->setWidth(30);*/

                $this_biao
                    //->setCellValue('A1', '流水ID')
                    //->setCellValue('B1', '商户电话')
                    ->setCellValue('A1', '商户的名称')
                    ->setCellValue('B1', '支付方式')
                    ->setCellValue('C1', '支付金额(元)')
                    ->setCellValue('D1', '总金额(元)')
                    ->setCellValue('E1', '代理费率(%)')
                    ->setCellValue('F1', '商户费率(%)')
                    ->setCellValue('G1', '代理收入(元)')
                    ->setCellValue('H1', '总收入(元)');
                    //->setCellValue('K1', '支付样式')
                    //->setCellValue('L1', '流水号')
                    //->setCellValue('M1', '支付时间')
                    //->setCellValue('N1', '代理商')
                    //->setCellValue('O1', '算法');
            }
            unset($excel);
            $excel = array_slice($agents_fee, $a * 3000, 3000);
            $i = 2;
            foreach ($data as $k => $v) {
                if ($v['bank'] == 3) {
                    $bank = '围餐银行';
                } else {
                    $bank = '';
                }
                $string = '';
                $objPHPExcel->setActiveSheetIndex($a)
                    //->setCellValue('A' . ($i), $v['id'])
                    //->setCellValue('B' . ($i), $v['user_phone'])
                    ->setCellValue('A' . ($i), $v['user_name'])
                    ->setCellValue('B' . ($i), $this->paystyle($v['paystyle_id']))
                    ->setCellValue('C' . ($i), sprintf("%.7f", $v['price']))
                    ->setCellValue('D' . ($i), sprintf("%.7f", $v['all_price']))
                    ->setCellValue('E' . ($i), $v['agent_rate'])
                    ->setCellValue('F' . ($i), $v['cost_rate'] . $bank)
                    ->setCellValue('G' . ($i), sprintf("%.7f", $v['rebate']))
                    ->setCellValue('H' . ($i), sprintf("%.7f", $v['all_rebate']));
                    //->setCellValue('K' . ($i), $this->numberstyle($v['mode']))
                    //->setCellValue('L' . ($i), "'" . $v['remark'])
                    //->setCellValue('M' . ($i), date("Y-m-d H:i:s", $v['paytime']))
                    //->setCellValue('N' . ($i), $v['agent_name'])
                    //->setCellValue('O' . ($i), ($v['poundage_price'] ?: 0) . '+' . ($v['rapaid'] ?: 0) . '+' . $v['price'] . '*' . (sprintf("%.2f", $v['cost_rate'] - $v['agent_rate'])) . '/100');

//                    ->setCellValue('k' . ($i), $total);
                $i++;
            }
            //得到当前活动的表,注意下文教程中会经常用到$objActSheet
            $objActSheet = $objPHPExcel->getActiveSheet();
            // 位置bbb  *为下文代码位置提供锚
            // 给当前活动的表设置名称
            $objActSheet->setTitle('洋仆淘代理对账表' . $a);
        }

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="洋仆淘代理对账表.xls"');
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');

        echo 213;
        exit;
//        $this->success("导出excel表格成功", U('index'));
    }
    //2018/4/8
    public function upload_excel_detail_old()
    {
        $agent_id = I('agent_id');
        $data = S('pay_' . $agent_id);

        $n = ceil(count($data) / 3000);
        $excel = array_slice($data, 0, 3000);

        ini_set('memory_limit', '2000M');
        Vendor("PHPExcel.PHPExcel");
        //引入phpexcel类文件
        $objPHPExcel = new \PHPExcel();
        // 设置文件的一些属性，在xls文件——>属性——>详细信息里可以看到这些值，xml表格里是没有这些值的
        $objPHPExcel
            ->getProperties()//获得文件属性对象，给下文提供设置资源
            ->setCreator("Maarten Balliauw")//设置文件的创建者
            ->setLastModifiedBy("Maarten Balliauw")//设置最后修改者
            ->setTitle("Office 2007 XLSX Test Document")//设置标题
            ->setSubject("Office 2007 XLSX Test Document")//设置主题
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")//设置备注
            ->setKeywords("office 2007 openxml php")//设置标记
            ->setCategory("Test result file");                //设置类别
        // 位置aaa  *为下文代码位置提供锚
        for ($a = 0; $a < $n; $a++) {
            if ($a != 0) {
                $objPHPExcel->createSheet();
                // 给表格添加数据
                $objPHPExcel->setactivesheetindex($a)
                    ->setCellValue('A1', '流水ID')
                    ->setCellValue('B1', '商户电话')
                    ->setCellValue('C1', '商户的名称')
                    ->setCellValue('D1', '支付方式')
                    ->setCellValue('E1', '支付金额(元)')
                    ->setCellValue('F1', '总金额(元)')
                    ->setCellValue('G1', '代理费率(%)')
                    ->setCellValue('H1', '商户费率(%)')
                    ->setCellValue('I1', '代理收入(元)')
                    ->setCellValue('J1', '总收入(元)')
                    ->setCellValue('K1', '支付样式')
                    ->setCellValue('L1', '流水号')
                    ->setCellValue('M1', '支付时间')
                    ->setCellValue('N1', '代理商')
                    ->setCellValue('O1', '算法');

//                    ->setCellValue('K1', '返佣和(元)');
                $excel_canshu = $objPHPExcel->getActiveSheet();
                $excel_canshu->getDefaultRowDimension()->setRowHeight(30);
                $excel_canshu->getColumnDimension('A')->setWidth(20);
                $excel_canshu->getColumnDimension('B')->setWidth(20);
                $excel_canshu->getColumnDimension('C')->setWidth(40);
                $excel_canshu->getColumnDimension('D')->setWidth(20);
                $excel_canshu->getColumnDimension('E')->setWidth(30);
                $excel_canshu->getColumnDimension('F')->setWidth(30);
                $excel_canshu->getColumnDimension('G')->setWidth(30);
                $excel_canshu->getColumnDimension('H')->setWidth(30);
                $excel_canshu->getColumnDimension('I')->setWidth(40);
                $excel_canshu->getColumnDimension('J')->setWidth(40);
                $excel_canshu->getColumnDimension('K')->setWidth(40);
                $excel_canshu->getColumnDimension('L')->setWidth(40);
                $excel_canshu->getColumnDimension('M')->setWidth(30);
                $excel_canshu->getColumnDimension('N')->setWidth(30);
                $excel_canshu->getColumnDimension('O')->setWidth(30);
            } else {
                // 给表格添加数据
                $this_biao = $objPHPExcel->setActiveSheetIndex();             //设置第一个内置表（一个xls文件里可以有多个表）为活动的
                $excel_canshu = $objPHPExcel->getActiveSheet();
                $excel_canshu->getDefaultRowDimension()->setRowHeight(30);
                $excel_canshu->getColumnDimension('A')->setWidth(20);
                $excel_canshu->getColumnDimension('B')->setWidth(20);
                $excel_canshu->getColumnDimension('C')->setWidth(40);
                $excel_canshu->getColumnDimension('D')->setWidth(20);
                $excel_canshu->getColumnDimension('E')->setWidth(30);
                $excel_canshu->getColumnDimension('F')->setWidth(30);
                $excel_canshu->getColumnDimension('G')->setWidth(30);
                $excel_canshu->getColumnDimension('H')->setWidth(30);
                $excel_canshu->getColumnDimension('I')->setWidth(40);
                $excel_canshu->getColumnDimension('J')->setWidth(40);
                $excel_canshu->getColumnDimension('K')->setWidth(40);
                $excel_canshu->getColumnDimension('L')->setWidth(40);
                $excel_canshu->getColumnDimension('M')->setWidth(30);
                $excel_canshu->getColumnDimension('N')->setWidth(30);
                $excel_canshu->getColumnDimension('O')->setWidth(30);

                $this_biao->setCellValue('A1', '流水ID')
                    ->setCellValue('B1', '商户电话')
                    ->setCellValue('C1', '商户的名称')
                    ->setCellValue('D1', '支付方式')
                    ->setCellValue('E1', '支付金额(元)')
                    ->setCellValue('F1', '总金额(元)')
                    ->setCellValue('G1', '代理费率(%)')
                    ->setCellValue('H1', '商户费率(%)')
                    ->setCellValue('I1', '代理收入(元)')
                    ->setCellValue('J1', '总收入(元)')
                    ->setCellValue('K1', '支付样式')
                    ->setCellValue('L1', '流水号')
                    ->setCellValue('M1', '支付时间')
                    ->setCellValue('N1', '代理商')
                    ->setCellValue('O1', '算法');
            }
            unset($excel);
            $excel = array_slice($agents_fee, $a * 3000, 3000);
            $i = 2;
            foreach ($data as $k => $v) {
                if ($v['bank'] == 3) {
                    $bank = '围餐银行';
                } else {
                    $bank = '';
                }
                $string = '';
                $objPHPExcel->setActiveSheetIndex($a)
                    ->setCellValue('A' . ($i), $v['id'])
                    ->setCellValue('B' . ($i), $v['user_phone'])
                    ->setCellValue('C' . ($i), $v['user_name'])
                    ->setCellValue('D' . ($i), $this->paystyle($v['paystyle_id']))
                    ->setCellValue('E' . ($i), sprintf("%.7f", $v['price']))
                    ->setCellValue('F' . ($i), sprintf("%.7f", $v['all_price']))
                    ->setCellValue('G' . ($i), $v['agent_rate'])
                    ->setCellValue('H' . ($i), $v['cost_rate'] . $bank)
                    ->setCellValue('I' . ($i), sprintf("%.7f", $v['rebate']))
                    ->setCellValue('J' . ($i), sprintf("%.7f", $v['all_rebate']))
                    ->setCellValue('K' . ($i), $this->numberstyle($v['mode']))
                    ->setCellValue('L' . ($i), "'" . $v['remark'])
                    ->setCellValue('M' . ($i), date("Y-m-d H:i:s", $v['paytime']))
                    ->setCellValue('N' . ($i), $v['agent_name'])
                    ->setCellValue('O' . ($i), ($v['poundage_price'] ?: 0) . '+' . ($v['rapaid'] ?: 0) . '+' . $v['price'] . '*' . (sprintf("%.2f", $v['cost_rate'] - $v['agent_rate'])) . '/100');

//                    ->setCellValue('k' . ($i), $total);
                $i++;
            }
            //得到当前活动的表,注意下文教程中会经常用到$objActSheet
            $objActSheet = $objPHPExcel->getActiveSheet();
            // 位置bbb  *为下文代码位置提供锚
            // 给当前活动的表设置名称
            $objActSheet->setTitle('洋仆淘代理对账表' . $a);
        }

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="洋仆淘代理对账表.xls"');
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');

        echo 213;
        exit;
//        $this->success("导出excel表格成功", U('index'));
    }

    public function upload_excel_detail_merchant()
    {
        $agent_id = I('agent_id');
        $data = S('pay_' . $agent_id);
        foreach ($data as $v) {
            if (isset($lists[$v['merchant_id']])) {
                $lists[$v['merchant_id']]['price'] += $v['price'];
                $lists[$v['merchant_id']]['rebate'] += $v['rebate'];
                $lists[$v['merchant_id']]['num'] += 1;
            } else {
                $lists[$v['merchant_id']] = array(
                    'user_name' => $v['user_name'],
                    'user_phone' => $v['user_phone'],
                    'price' => 0,
                    'rebate' => 0,
                    'num' => 0
                );
                $lists[$v['merchant_id']]['price'] += $v['price'];
                $lists[$v['merchant_id']]['rebate'] += $v['rebate'];
                $lists[$v['merchant_id']]['num'] += 1;
            }
        }
        $data = $lists;

        $n = ceil(count($data) / 3000);
        $excel = array_slice($data, 0, 3000);

        ini_set('memory_limit', '1000M');
        Vendor("PHPExcel.PHPExcel");
        //引入phpexcel类文件
        $objPHPExcel = new \PHPExcel();
        // 设置文件的一些属性，在xls文件——>属性——>详细信息里可以看到这些值，xml表格里是没有这些值的
        $objPHPExcel
            ->getProperties()//获得文件属性对象，给下文提供设置资源
            ->setCreator("Maarten Balliauw")//设置文件的创建者
            ->setLastModifiedBy("Maarten Balliauw")//设置最后修改者
            ->setTitle("Office 2007 XLSX Test Document")//设置标题
            ->setSubject("Office 2007 XLSX Test Document")//设置主题
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")//设置备注
            ->setKeywords("office 2007 openxml php")//设置标记
            ->setCategory("Test result file");                //设置类别
        // 位置aaa  *为下文代码位置提供锚
        for ($a = 0; $a < $n; $a++) {
            if ($a != 0) {
                $objPHPExcel->createSheet();
                // 给表格添加数据
                $objPHPExcel->setactivesheetindex($a)
                    ->setCellValue('A1', '流水ID')
                    ->setCellValue('B1', '商户电话')
                    ->setCellValue('C1', '商户的名称')
                    ->setCellValue('D1', '支付金额(元)')
                    ->setCellValue('E1', '代理收入(元)')
                    ->setCellValue('F1', '流水笔数');
//                    ->setCellValue('K1', '返佣和(元)');
                $excel_canshu = $objPHPExcel->getActiveSheet();

                $excel_canshu->getDefaultRowDimension()->setRowHeight(30);
                $excel_canshu->getColumnDimension('A')->setWidth(20);
                $excel_canshu->getColumnDimension('B')->setWidth(40);
                $excel_canshu->getColumnDimension('C')->setWidth(20);
                $excel_canshu->getColumnDimension('D')->setWidth(20);
                $excel_canshu->getColumnDimension('E')->setWidth(30);
                $excel_canshu->getColumnDimension('F')->setWidth(30);

            } else {
                // 给表格添加数据
                $this_biao = $objPHPExcel->setActiveSheetIndex();             //设置第一个内置表（一个xls文件里可以有多个表）为活动的
                $excel_canshu = $objPHPExcel->getActiveSheet();

                $excel_canshu->getDefaultRowDimension()->setRowHeight(30);
                $excel_canshu->getColumnDimension('A')->setWidth(20);
                $excel_canshu->getColumnDimension('B')->setWidth(20);
                $excel_canshu->getColumnDimension('C')->setWidth(40);
                $excel_canshu->getColumnDimension('D')->setWidth(20);
                $excel_canshu->getColumnDimension('E')->setWidth(30);
                $excel_canshu->getColumnDimension('F')->setWidth(30);


                $this_biao->setCellValue('A1', '流水ID')
                    ->setCellValue('A1', '流水ID')
                    ->setCellValue('B1', '商户电话')
                    ->setCellValue('C1', '商户的名称')
                    ->setCellValue('D1', '支付金额(元)')
                    ->setCellValue('E1', '代理收入(元)')
                    ->setCellValue('F1', '流水笔数');
            }
            unset($excel);
            $excel = array_slice($agents_fee, $a * 3000, 3000);
            $i = 2;

            foreach ($data as $k => $v) {
                $objPHPExcel->setActiveSheetIndex($a)
                    ->setCellValue('A' . ($i), $i)
                    ->setCellValue('B' . ($i), $v['user_phone'])
                    ->setCellValue('C' . ($i), $v['user_name'])
                    ->setCellValue('D' . ($i), $v['price'])
                    ->setCellValue('E' . ($i), $v['rebate'])
                    ->setCellValue('F' . ($i), $v['num']);
                $i++;
            }
//          		'user_name'=>$v['user_name'],
//  					'user_phone'=>$v['user_phone'],
//  					'price'=>0,
//  					'rebate'=>0,
//  					'num'=>0
            //得到当前活动的表,注意下文教程中会经常用到$objActSheet
            $objActSheet = $objPHPExcel->getActiveSheet();
            // 位置bbb  *为下文代码位置提供锚
            // 给当前活动的表设置名称
            $objActSheet->setTitle('洋仆淘代理对账表' . $a);

        }

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="洋仆淘代理对账表.xls"');
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');

    }

//    改变对账状态
    public function change_status()
    {
        $id = I('post.id');
        $cate = $this->pay->find($id);
        $status = $cate['agent_status'] == 0 ? 1 : 0;
        echo $status;
        $this->pay->where("id=$id")->setField('agent_status', $status);
    }

    /**
     * @param $agent_id  代理商在用户表里面的id
     * @return array  获取代理商下所有的商户
     */
    public function get_merchant($agent_id)
    {
        $users = $this->get_category($agent_id);
        $users = explode(",", $users);
        $count = count($users);
        $category_ids = "";
        $a = M();
        for ($i = 1; $i < $count - 1; $i++) {
            $id = $users[$i];
            $role_id = $a->query("select id from ypt_merchants_role_users where role_id = 3 And uid =$id");
            $merchant = $this->merchant->where("uid=$id")->find();
            if ($role_id[0]['id'] != "" && $merchant) {
                $merchant_id = $a->query("select id from ypt_merchants where uid = $id limit 1");
                $category_ids .= $merchant_id[0]['id'] . ",";
            }
        }
        $ids = explode(",", $category_ids);
        array_pop($ids);
        asort($ids);
        return $ids;
    }

    //    得到所有的子节点
    /**
     * @param $category_id 带入代理商户的id
     * @return string  代理商下所有的商户id
     */
    function get_category($category_id)
    {
        $db = M();
        $category_ids = $category_id . ",";
        $child_category = $db->query("select id from ypt_merchants_users where agent_id = '$category_id'");
        foreach ($child_category as $key => $val) {
            $category_ids .= $this->get_category($val["id"]);
        }
        return $category_ids;
    }


// 支付方式
    function pay_status($status)
    {
        switch ($status) {
            case -1:
                return "支付中";
            case 0:
                return "支付失败";
            case 1:
                return "支付成功";
            case 2:
                return "退款成功";
            case 3:
                return "退款失败";
            case 4:
                return "退款中";
            default:
                return "其他方式";
        }
    }

//支付方式判断
    function paystyle($paystyle_id)
    {
        switch ($paystyle_id) {
            case 1:
                return "微信支付";
            case 2:
                return "支付宝支付";
            case 5:
                return "现金支付";
            default:
                return "其他方式";
        }
    }

//支付样式判断
    function numberstyle($number)
    {
        switch ($number) {
            case 0:
                return "台签";
            case 1:
                return "App扫码支付";
            case 2:
                return "App刷卡支付";
            case 3:
                return "双屏收银支付";
            case 4:
                return "双屏现金支付";
            case 5:
                return "pos机主扫";
            case 6:
                return "pos机被扫";
            case 7:
                return "pos机现金支付";
            case 8:
                return "pos机其他支付";
            case 9:
                return "pos机刷银行卡";
            case 10:
                return "快速支付";
            case 11:
                return "小程序";
            case 12:
                return "会员充值";
            case 13:
                return "收银APP现金支付";
            case 14:
                return "收银APP余额支付";
            case 15:
                return "小白盒";
            case 16:
                return "台卡余额";
            case 17:
                return "双屏主扫";
            default:
                break;
        }
    }


}