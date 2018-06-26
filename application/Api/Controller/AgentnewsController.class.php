<?php

namespace Api\Controller;

use Common\Controller\ApibaseController;

class  AgentnewsController extends ApibaseController
{
    public $id;
    protected $merchants;
    protected $users;
    protected $roles;
    protected $pays;
    protected $cates;
    protected $payBack;
    protected $page = null;

    public function __construct()
    {
        parent::__construct();
        $this->id = $this->userInfo['uid'];
        $this->merchants = M("merchants");
        $this->users = M("merchants_users");
        $this->roles = M("merchants_role_users");
        $this->cates = M("merchants_cate");
        $this->pays = M("pay");
        $this->payBack = M("pay_back");
    }

//代理 总额
    public function service()
    {
        $this->checkLogin();
        $type = I("type");
        if ($type == "") $type = 0;
        $id = $this->id;
        $time = $this->type_time($type);
        $pays = $this->count_agent($id, $time);
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $pays));
//        dump($pays);
    }

    //1.3.4代理 总额
    public function service1()
    {
        $this->checkLogin();
        if (!$this->checkAuth()) {
            $pays = array("paytime" => time(), 'total_num' => "0", 'total_price' => "0", 'per_weixin_num' => "0", 'per_ali_num' => "0", 'per_wei_price' => "0", 'per_ali_price' => "0");
            $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $pays));
            exit;
        }
        $type = I("type");
        if ($type == "") $type = 0;
        $id = $this->id;
        $time = $this->type_time($type);
        $pays = $this->count_agent($id, $time);
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $pays));
    }

    public function checkAuth()
    {
        $token_time = $this->userInfo['token_add_time'];
        if ($token_time > '1524537923') {
            if ($this->userInfo['auth_index']) {
                return true;
            } else {
                return false;
            }
        } else {
            return true;
        }
    }

//所有下级信息
    public function my_down()
    {
        $this->checkLogin();
        $id = $this->id;
        $juese = I("juese");
        $page = I("page", 0);
        $start = $page * 10;
        $this->page = $start;
        $down = $this->agent_down($juese, 1, $id);
        $comment = array_slice($down, $start, 10);
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $comment));
    }

//我的客户里面的按月分类
    public function my_customer()
    {
        $this->checkLogin();
        $u_id = I("id");
//        $u_id = 50;
        $type = I("type");
        $time = $this->type_month($type);
        $role = M()->query("SELECT ur.role_id FROM ypt_merchants_users u right join ypt_merchants_role_users ur on ur.uid=u.id WHERE ( u.id=$u_id )");
        if ($role[0]['role_id'] == 2) {
            $data = $this->count_agent($u_id, $time, 1);
        } elseif ($role[0]['role_id'] == 3) {
            $data = $this->count_merchant($u_id, $time, 1);
        }
        if (!$data) {
            $data = array();
        }
//        dump($data);
//        $this->ajaxReturn($data);
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $data));

    }

// 得到用户当月按日进行汇总
    public function user_detail()
    {
        $this->checkLogin();

        $u_id = I("id");
        $role_id = I("role_id");
        $type = I("type");
//        代理
        $total = array();
        if ($role_id == 2) {
            $total = $this->count_agent($u_id);
        }
        if ($role_id == 3) {
            $total = $this->count_merchant($u_id);
        }
        $page = I("page") == 0 ? 0 : I("page");
        $start = $page * 10 + 1;
        $day = array();
        for ($i = $start; $i <= $start + 9; $i++) {
            $time = $this->get_day($type, $i);
            $day_detail = date("Y-m-d", $time[0]);
            if ($time == "已经超过当前月份了") {
                $day_detail = "";
                $count = array("paytime" => null, "total_number" => "0", "total_price" => "0");
            } else {
                if ($role_id == 2) {
                    $count = $this->count_agent($u_id, $time);
//                    array_push($day[$i],$count);
                }
                if ($role_id == 3) {
                    $count = $this->count_merchant($u_id, $time);
//                    array_push($day[$i],$count);
                }
            }
            $count['time'] = $day_detail;
            $day[] = $count;
        }
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $day));
    }

//流水 
    public function coin()
    {
        $this->checkLogin();

        $id = $this->userId;
        $type = I("type");
//        2是代理 3是商户
        $role = I("role_id");
//        0是生序 1是降序 
        $order = I("price_order");
        if ($order != 0) {
            $order = null;
        } else {
            $order = "d";
        }
        if ($type == 7) {
            $begin_time = strtotime(I("begin_time"));
            $end_time = strtotime(I("end_time"));
            $number = ($end_time - $begin_time) / 24 / 60 / 60;
            $time = $end_time + 24 * 60 * 60 - 1;
        } else {
            $number = $this->get_number($type);
            $time = $this->type_time($type);
            $time = $time[1];
        }
        $data = array();
        //$i=2;
        for ($i = 1; $i <= $number; $i++) {
            $time_now = $this->day_detail($time, $i);
            $data[$i] = $this->agent_down($role, 1, $id, $time_now);
            if ($data[$i] == null) {
                unset($data[$i]);
            } else {
                $data[$i] = array2sort($data[$i], 'total_price', $order);
                if ($data[$i][0]['id'] == null) unset($data[$i][0]);
            }
        }
        $data = $this->shuzu($data);
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $data));

    }

//流水里面的交易详情
    public function coin_detail()
    {
        $this->checkLogin();
        $u_id = I("id");
        $role_id = I("role_id");
        $paytime = I("paytime");
        $time[0] = strtotime($paytime);
        $time[1] = $time[0] + 24 * 60 * 60 - 1;
        if ($role_id == 2) {
            $data = $this->count_agent($u_id, $time, 1);
        } elseif ($role_id == 3) {
            $data = $this->count_merchant($u_id, $time, 1);
        }
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $data));

    }

//    报表一接口
    public function excel()
    {
        $this->checkLogin();
        $id = $this->id;
        $type = I("type");
        $time = $this->type_month($type);
        $data = $this->count_agent($id, $time, 1);
        $data['tab1'] = "http://sy.youngport.com.cn/index.php?g=Api&m=Agentnews&a=excel_total&type=" . $type . "&id=" . $id;
        $data['tab2'] = "http://sy.youngport.com.cn/index.php?g=Api&m=Agentnews&a=excel_number&type=" . $type . "&id=" . $id;
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $data));
    }

//    代理商报表一 交易总额比较
    public function excel_total()
    {
        $id = I("id");
        $type = I("type");
        $time = $this->type_month($type);
        $data = $this->count_agent($id, $time, 1);
//        dump($data);
        $data = json_encode($data);
        $this->assign('data', $data);
        $this->display();
    }

//    代理商 报表一 交易数量比较
    public function excel_number()
    {
        $id = I("id");
        $type = I("type");
        $time = $this->type_month($type);
        $data = $this->count_agent($id, $time, 1);
//        dump($data);
        $data = json_encode($data);
        $this->assign('data', $data);
        $this->display();
    }

    //    代理商报表二
    public function excel_detail()
    {
        $this->checkLogin();
        $id = $this->id;
        $type = I("type");
        $data['tab1'] = "http://sy.youngport.com.cn/index.php?g=Api&m=Agentnews&a=excel_total_detail&type=" . $type . "&id=" . $id;
        $data['tab2'] = "http://sy.youngport.com.cn/index.php?g=Api&m=Agentnews&a=excel_number_detail&type=" . $type . "&id=" . $id;
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $data));

//        $this->display();
    }

    //    代理商报表二  总值
    public function excel_total_detail()
    {
        $id = I("id");
        $type = I("type");
        $time = $this->get_mark($type);
        $count = array();
        foreach ($time[0] as $k => $v) {
            $array = array($time[0][$k]['begin_time'], $time[0][$k]['end_time']);
            $count[$k]['pay'] = $this->count_agent($id, $array, 1);
            $count[$k]['time'] = date("n.d", $v['begin_time']) . "~" . date("n.d", $v['end_time']);
        }
        $count = json_encode($count);
        $this->assign("count", $count);
        $this->display();
    }

    //    代理商报表二  总量
    public function excel_number_detail()
    {
        $id = I("id");
        $type = I("type");
        $time = $this->get_mark($type);
        $count = array();
        foreach ($time[0] as $k => $v) {
            $array = array($time[0][$k]['begin_time'], $time[0][$k]['end_time']);
            $count[$k]['pay'] = $this->count_agent($id, $array, 1);
            $count[$k]['time'] = date("n.d", $v['begin_time']) . "~" . date("n.d", $v['end_time']);
        }
        $count = json_encode($count);
        $this->assign("count", $count);
        $this->display();
    }

//    员工流水
    public function customer()
    {
        $this->checkLogin();
        $id = I("id");
        $type = I("type");
//      0全部  2是代理 3是商户
        $role = I("role_id");
//        0是生序 1是降序
        $order = I("price_order");
        if ($order == "1") {
            $order = "d";
        }
        if ($type == 7) {
            $begin_time = strtotime(I("begin_time"));
            $end_time = strtotime(I("end_time"));
            $number = ($end_time - $begin_time) / 24 / 60 / 60;
            $time = $end_time + 24 * 60 * 60 - 1;
        } else {
            $number = $this->get_number($type);
            $time = $this->type_time($type);
            $time = $time[1];
        }
        $data = array();
        for ($i = 1; $i <= $number; $i++) {
            $time_now = $this->day_detail($time, $i);
            $data[$i] = $this->agent_down($role, 2, $id, $time_now);
            if (empty($data[$i])) {
                unset($data[$i]);
            } else {
                $data[$i] = array2sort($data[$i], 'total_price', $order);
            }
            if ($data[$i][0]['id'] == null) {
                unset($data[$i][0]);
            }
        }
        $data = $this->shuzu($data);
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $data));

    }

//    员工流水详情
    public function customer_detail()
    {
        $this->checkLogin();
        $u_id = I("id");
        $role_id = I("role_id");
        $paytime = I("paytime");
        $time[0] = strtotime($paytime);
        $time[1] = $time[0] + 24 * 60 * 60 - 1;
        if ($role_id == 2) {
            $data = $this->count_agent($u_id, $time, 1);
        } elseif ($role_id == 3) {
            $data = $this->count_merchant($u_id, $time, 1);
        }
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $data));
    }

    /**
     *  代理申请提现相关信息，可提现月份
     */
    public function withdraw_info()
    {
        $agent_uid = $this->userId;
        $date_list = M('pay_month')->where(array('agent_id'=>$agent_uid))->order('date asc')->getField('date',true);
        $time = date('Y-m',M('merchants_agent')->where(array('uid'=>$agent_uid))->getField('add_time'));
        $uid = M()->query('select getchild('.$agent_uid.') as uids');
        $uids = $uid[0]['uids'];
        $mer_ids = $this->get_merchant_id($uids); //获取商户id
        $map['merchant_id'] = array('in',$mer_ids);
        $map['status'] = '1';
        #$not_data 未插入到pay_month表数据
        $not_data = array();
        if($mer_ids){
            while ($time<date('Y-m')){
                if(!in_array($time,$date_list)){
                    $info = $this->calc_maid($map,$this->get_appoint_month($time));
                    if($info){
                        $not_data[] = array('date'=>$time,'rebate'=>$info['rebate']);
                    }
                }
                $time = date('Y-m',strtotime("$time +1 month"));
            }
        }
        $logs = M('pay_month')
            ->field('date,rebate')
            ->where(array('agent_id'=>$agent_uid,'status'=>array(array('eq',0),array('eq',3),'or')))
            ->select();
        //可提现月份及金额
        $data['withdraw_data'] = array_merge($logs,$not_data);
        //近三十天提现记录
        $data['withdraw_log'] = M('pay_month')->where(array('agent_id'=>$agent_uid,'status'=>array('gt','0'), 'add_time' => array('EGT', time() - 86400 * 30)))->field('id,date,rebate,status')->order('add_time desc')->select();//近三十天提现记录
        $this->ajaxReturn(array('code'=>'success','msg'=>'成功','data'=>$data));
    }

    /**
     * 代理申请提现
     */
    public function withdraw()
    {
        //申请提现的日期，格式2018-06
        ($date = I('date')) || $this->ajaxReturn(array('code'=>'error','msg'=>'date is empty'));
        (substr_count($date,'-') == 1 && strlen($date) == 7) || $this->ajaxReturn(array('code'=>'error','msg'=>'date格式错误'));

        ($bank_id = I('bank_id')) || $this->ajaxReturn(array('code'=>'error','msg'=>'bank_id is empty'));

        $pay_month_id = M('pay_month')->where(array('agent_id'=>$this->userId,'date'=>$date))->getField('id');
        if($pay_month_id){
            #如果pay_month表有该条数据则改status
            M('pay_month')->where(array('id'=>$pay_month_id))->save(array('status'=>1,'bank_id'=>$bank_id));
            $this->ajaxReturn(array("code" => "success", "msg" => "提现申请已提交"));
        }else{
            $uid = M()->query('select getchild('.$this->userId.') as uids');
            $uids = $uid[0]['uids'];
            $mer_ids = $this->get_merchant_id($uids); //获取商户id
            $map['merchant_id'] = array('in',$mer_ids);
            $map['status'] = '1';
            if($mer_ids){
                $info = $this->calc_maid($map,$this->get_appoint_month($date));
                if($info){
                    $add_data = array(
                        'agent_id'=>$this->userId,
                        'date'=>$date,
                        'price'=>$info['price'],
                        'nums'=>$info['num'],
                        'rebate'=>$info['rebate'],
                        'price0'=>'0.00',
                        'nums0'=>'0',
                        'rebate0'=>'0.00000',
                        'status'=>1,
                        'add_time'=>time()
                    );
                    $res = M('pay_month')->add($add_data);
                    if ($res) {
                        $this->ajaxReturn(array("code" => "success", "msg" => "提现申请已提交"));
                    } else {
                        $this->ajaxReturn(array("code" => "error", "msg" => "提现申请提交失败"));
                    }
                }
            }else{
                $this->ajaxReturn(array("code" => "error", "msg" => "无数据"));
            }

        }
    }

    /**
     * @param $id 用户表里面的id
     * @param string $time 按时间区分
     * @param int $is_detail 是否需要微信和支付宝支付的细节
     * @return string 返回该商户交易的总额
     */
    public function count_merchant($id, $time = "", $is_detail = 0)
    {
        if ($time != "") $map['paytime'] = array("between", $time);
        $mch_id = M('merchants')->where(array('uid'=>$id))->getField('id');
        $map['p.merchant_id'] = $mch_id;
        $map['uid'] = $id;
        $map['p.status'] = 1;
        $map['p.paystyle_id'] = array('in', array(1, 2, 3));// 修改时间 2017/10/26

        if ($is_detail == 1) {
            $field = "p.paytime,ifnull(sum(price),0) as total_price,count(p.id) as total_num,ifnull(sum( if( p.paystyle_id =1, 1, 0)),0) as per_weixin_num,ifnull(sum( if( p.paystyle_id =2, 1, 0)),0) as per_ali_num,
            ifnull(sum( if( p.paystyle_id =1,p.price, 0)),0) as per_wei_price,ifnull(sum( if( p.paystyle_id =2,p.price, 0)),0) as per_ali_price";
        } else {
            $field = "p.paytime,count(p.id) as total_num,ifnull(sum(price),0) as total_price";
        }
        $pay = M('merchants')->alias("m")
            ->join("right join __PAY__ p on p.merchant_id=m.id")
            ->field($field)
            ->where($map)
            ->find();
//        get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/test/','count_merchant','sql', M()->_sql());
        return $pay;
    }

    /**
     * @param $id   商户的id
     * return 返回商户所有的流水
     */
    public function merchant_detail($id)
    {
        M('merchants')->alias("m")
            ->where("id=$id")
            ->join("right join __PAY__ p on p.merchant_id=m.id")
            ->join("right join __MERCHANTS_USERS__ u on u.id=m.uid")
            ->order("paytime desc")
            ->select();
    }

    /**
     * @param $id  用户表里面的id
     * @param int $time 按时间区分
     * @param int $is_detail 是否需要微信和支付宝支付的细节
     * 返回该商户交易的总额
     */
    function count_agent($id, $time = "", $is_detail = 0)
    {
//        $users_str = $this->get_category($id);
        $uses = M()->query('select getagentchild(' . $id . ') as uids');
        $users_str = $uses[0]['uids'];

        # 只查询角色是商户的信息
        $uids = M('merchants_role_users')->field('uid')->where(array('role_id' => 3, 'uid' => array('IN', $users_str)))->select();
        unset($users_str);

        $uids = array_map(function ($a) {
            return $a['uid'];
        }, $uids);
        if (!$uids) {
            return array("paytime" => time(), 'total_num' => "0", 'total_price' => "0", 'per_weixin_num' => "0", 'per_ali_num' => "0", 'per_wei_price' => "0", 'per_ali_price' => "0");
        }
        # 获取商户id
        $mch_ids = M('merchants')->field('id')->where(array('uid' => array('IN', $uids)))->select();
        unset($uids);

        $mch_ids = array_map(function ($a) {
            return $a['id'];
        }, $mch_ids);
        if (!$mch_ids) {
            return array("paytime" => time(), 'total_num' => "0", 'total_price' => "0", 'per_weixin_num' => "0", 'per_ali_num' => "0", 'per_wei_price' => "0", 'per_ali_price' => "0");
        }
        # 查询条件
        $map['merchant_id'] = array('in', $mch_ids);
        unset($mch_ids);
        $map['p.status'] = 1;
        $map['p.paystyle_id'] = array('in', array(1, 2, 3));// 1是微信 2是支付宝 5是现金支付 3是刷储蓄卡或信用卡
        if ($time != "") {
            $map['paytime'] = array("between", $time);
        }
        if ($is_detail == 1) {
            $field = "p.paytime,ifnull(sum(price),0) as total_price,count(p.id) as total_num,ifnull(sum( if( p.paystyle_id =1, 1, 0)),0) as per_weixin_num,ifnull(sum( if( p.paystyle_id =2, 1, 0)),0) as per_ali_num,
            ifnull(sum( if( p.paystyle_id =1,p.price, 0)),0) as per_wei_price,ifnull(sum( if( p.paystyle_id =2,p.price, 0)),0) as per_ali_price";
        } else {
            $field = "p.paytime,count(p.id) as total_num,ifnull(sum(price),0) as total_price";
        }
        $pay = $this->pays->alias("p")->field($field)->where($map)->find();

        return $pay;
    }

    //count_agent(实现部分退款,做好部分退款可以使用)
    function count_agent1($id, $time = "", $is_detail = 0)
    {
        $users = $this->get_category($id);
        $users = explode(",", substr($users, 0, strlen($users) - 1));
        $count = count($users);
        $category_ids = "";
        for ($i = 0; $i < $count - 1; $i++) {
            $id = $users[$i];
            $role_id = M()->query("select id from ypt_merchants_role_users where role_id = 3 And uid =$id");
            if ($role_id[0]['id'] != "") {
                $merchant_id = M()->query("select id from ypt_merchants where uid = $id limit 1");
                $category_ids .= $merchant_id[0]['id'] . ",";
            }
        }

        if (!$category_ids) return array("paytime" => time(), 'total_num' => "0", 'total_price' => "0");
        $ids = explode(",", substr($category_ids, 0, strlen($category_ids) - 1));
        $map['merchant_id'] = array('in', $ids);
        $map['p.status'] = array('in', array('1', '2', '5'));
        if ($time != "") {
            $map['paytime'] = array("between", $time);
        }
        if ($is_detail == 1) {
            $field = "p.paytime,ifnull(sum(price),0) as total_price,ifnull(count( if( p.status=1, p.id, 0)),0) as total_num,ifnull(sum( if( p.status=1 and p.paystyle_id =1, 1, 0)),0) as per_weixin_num,ifnull(sum( if(p.status=1 and p.paystyle_id =2, 1, 0)),0) as per_ali_num,
            ifnull(sum( if( p.status=1 and p.paystyle_id =1,p.price, 0)),0) as per_wei_price,ifnull(sum( if(p.status=1 and p.paystyle_id =2,p.price, 0)),0) as per_ali_price";
            $field2 = "ifnull(sum(price_back),0) as back_price,count(p.id) as total_num,ifnull(sum( if( p.paystyle_id =1, 1, 0)),0) as per_weixin_num,ifnull(sum( if( p.paystyle_id =2, 1, 0)),0) as per_ali_num,
            ifnull(sum( if( p.paystyle_id =1,p.price_back, 0)),0) as per_wei_price,ifnull(sum( if( p.paystyle_id =2,p.price_back, 0)),0) as per_ali_price";
            $pay = $this->pays->alias("p")->field($field)->where($map)->find();
            $payBack = M('pay_back')->alias('p')->field($field2)->where($map)->find();
            $pay['total_price'] = $pay['total_price'] - $payBack['back_price'];
            $pay['total_num'] = $pay['total_num'] + $payBack['total_num'];
            $pay['per_weixin_num'] = $pay['per_weixin_num'] + $payBack['per_weixin_num'];
            $pay['per_ali_num'] = $pay['per_ali_num'] + $payBack['per_ali_num'];
            $pay['per_wei_price'] = $pay['per_wei_price'] - $payBack['per_wei_price'];
            $pay['per_ali_price'] = $pay['per_ali_price'] + $payBack['per_ali_price'];
        } else {
            $field = "p.paytime,count(p.id) as total_num,ifnull(sum(price),0) as total_price";
            $field2 = "ifnull(sum(price_back),0) as back_price";
            $pay = $this->pays->alias("p")->field($field)->where($map)->find();
            $payBack = M('pay_back')->alias('p')->where($map)->field($field2)->find();
            $pay['total_price'] = $pay['total_price'] - $payBack['back_price'];
        }
        return $pay;
    }

    /**
     * @param $users 以字符串拼接所有的商户
     * @return  返回所有商户的支付详情
     */
    function agent_detail($users)
    {
//        $user_id="43,44,45,46,47,48,49,52,50,53,51,54,";
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
        $map['merchant_id'] = array('in', $ids);
        $map['paystyle_id'] = array('in', array(1, 2));  // 修改时间 2017/10/11
        $pays = M("pay")->where($map)->order('paytime desc')->select();
        return $pays;
    }

    /**
     * 测试时间信息
     */
    public function checkdata()
    {
        $type = 4;
        dump($this->type_time($type));
        exit;

    }

    /**
     * @param $type   支付的类型;
     * @return int    0: 所有1 :今日 2:昨日 3:本周 4:本月 5:上周 6:上月 7 为自定义时间
     */
    function type_time($type)
    {
        switch ($type) {
            case 0:
                return;
            case 1:
                //  今天
                $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
                $endToday = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
                return array($beginToday, $endToday);
            case 2:
                //昨天
                $beginYesterday = mktime(0, 0, 0, date('m'), date('d') - 1, date('Y'));
                $endYesterday = mktime(0, 0, 0, date('m'), date('d'), date('Y')) - 1;
                return array($beginYesterday, $endYesterday);
            case 3:
                //        本周
                $beginThisweek = mktime(0, 0, 0, date('m'), date('d') - date('w') + 1, date('Y'));
                $endToday = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;

//                $endThisweek=mktime(23,59,59,date('m'),date('d')-date('w')+7,date('Y'));
                return array($beginThisweek, $endToday);
            case 4:
                //        本月
                $beginThismonth = mktime(0, 0, 0, date('m'), 1, date('Y'));
                $endToday = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;

//                $endThismonth = mktime(23,59,59,date('m'),date('t'),date('Y'));
                return array($beginThismonth, $endToday);
            case 5:
                //上周
                $beginLastweek = mktime(0, 0, 0, date('m'), date('d') - date('w') + 1 - 7, date('Y'));
                $endLastweek = mktime(23, 59, 59, date('m'), date('d') - date('w') + 7 - 7, date('Y'));
                return array($beginLastweek, $endLastweek);
            case 6:
                //上月
                $beginLastmonth = mktime(0, 0, 0, date("m") - 1, 1, date("Y"));
                $endLastmonth = mktime(23, 59, 59, date("m"), 0, date("Y"));
                return array($beginLastmonth, $endLastmonth);
        }
    }

    /**
     * @param $type  选择的为负几月份
     * 0:全部  1:当前月份  2:-1月份 3:-2月份 4:-3月份 5:-4月份 6:-5月份
     * @return array|  负月份的时间戳
     */
    function type_month($type)
    {
        switch ($type) {
            case 0:
                return;
            case 1:
                $begin_time = mktime(0, 0, 0, date("m"), 1, date("Y"));
                $end_time = mktime(23, 59, 59, date("m"), date("t"), date("Y"));
                return array($begin_time, $end_time);
            case 2:
                $begin_time = mktime(0, 0, 0, date("m") - 1, 1, date("Y"));
                $end_time = mktime(23, 59, 59, date("m"), 0, date("Y"));
                return array($begin_time, $end_time);
            case 3:
                $begin_time = mktime(0, 0, 0, date("m") - 2, 1, date("Y"));
                $end_time = mktime(23, 59, 59, date("m") - 1, 0, date("Y"));
                return array($begin_time, $end_time);
            case 4:
                $begin_time = mktime(0, 0, 0, date("m") - 3, 1, date("Y"));
                $end_time = mktime(23, 59, 59, date("m") - 2, 0, date("Y"));
                return array($begin_time, $end_time);
            case 5:
                $begin_time = mktime(0, 0, 0, date("m") - 4, 1, date("Y"));
                $end_time = mktime(23, 59, 59, date("m") - 3, 0, date("Y"));
                return array($begin_time, $end_time);
            case 6:
                $begin_time = mktime(0, 0, 0, date("m") - 5, 1, date("Y"));
                $end_time = mktime(23, 59, 59, date("m") - 4, 0, date("Y"));
                return array($begin_time, $end_time);
        }
    }


    /**
     * @param $number  选择支付距离现在几天 0 只全部 1 最后一天 2 最后第二天
     * @param $type  是否是全部数据 不是的话判断最后的时间 0是全部
     * @return array
     */
    function get_day($type, $number = 0)
    {
//        区分是否是本月或者全部
        if ($type == 0 || $type == 1) {
            $time = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
        } else {
            $time = $this->type_month($type);
            $time = $time[1];
        }
//       这个是判断总金额的
        if ($number == 0) {
            return;
        }
        $begin_time = $time - 24 * 60 * 60 * ($number) + 1;
        $end_time = $time - 24 * 60 * 60 * ($number - 1);
//        全部时间不用判断是或否会超过本月第一天
        if ($type == 0) {
            return array($begin_time, $end_time);
        }
        $last_time = $this->type_month($type);
        $last_time = $last_time[0];
        if ($begin_time < $last_time) {
            return "已经超过当前月份了";
        }
        return array($begin_time, $end_time);
    }

    /**
     * @param $type    时间类型
     * @param $number  往前推进一天  1为选中当前天
     * @return string
     */
    function day_detail($time, $number)
    {

//        $time=$this->type_time($type);
//        $time_start=$time[0];
        $time_end = $time;
        $begin_time = $time_end - 24 * 60 * 60 * ($number) + 1;
        $end_time = $time_end - 24 * 60 * 60 * ($number - 1);
//        if($begin_time < $time_start) return "超过时间无数据显示";
        return array($begin_time, $end_time);
    }

    /**
     * @param $type  选择的时间分类
     * @return array
     */
    function get_mark($type)
    {
        $time = time();
        switch ($type) {
            case 1:
                $day[7]['end_time'] = $time;
                $day[7]['begin_time'] = strtotime("today");
                $day[6]['end_time'] = $day[7]['begin_time'] - 1;
                $day[6]['begin_time'] = $day[7]['begin_time'] - 24 * 60 * 60;
                $day[5]['end_time'] = $day[6]['end_time'] - 24 * 60 * 60;
                $day[5]['begin_time'] = $day[6]['begin_time'] - 24 * 60 * 60;
                $day[4]['end_time'] = $day[5]['end_time'] - 24 * 60 * 60;
                $day[4]['begin_time'] = $day[5]['begin_time'] - 24 * 60 * 60;
                $day[3]['end_time'] = $day[4]['end_time'] - 24 * 60 * 60;
                $day[3]['begin_time'] = $day[4]['begin_time'] - 24 * 60 * 60;
                $day[2]['end_time'] = $day[3]['end_time'] - 24 * 60 * 60;
                $day[2]['begin_time'] = $day[3]['begin_time'] - 24 * 60 * 60;
                $day[1]['end_time'] = $day[2]['end_time'] - 24 * 60 * 60;
                $day[1]['begin_time'] = $day[2]['begin_time'] - 24 * 60 * 60;
                return array($day);
            case 2:
                $day[7]['end_time'] = $time;
                $day[7]['begin_time'] = strtotime("yesterday");
                $day[6]['end_time'] = $day[7]['begin_time'] - 1;
                $day[6]['begin_time'] = $day[7]['begin_time'] - 24 * 60 * 60 * 2;
                $day[5]['end_time'] = $day[6]['end_time'] - 24 * 60 * 60 * 2;
                $day[5]['begin_time'] = $day[6]['begin_time'] - 24 * 60 * 60 * 2;
                $day[4]['end_time'] = $day[5]['end_time'] - 24 * 60 * 60 * 2;
                $day[4]['begin_time'] = $day[5]['begin_time'] - 24 * 60 * 60 * 2;
                $day[3]['end_time'] = $day[4]['end_time'] - 24 * 60 * 60 * 2;
                $day[3]['begin_time'] = $day[4]['begin_time'] - 24 * 60 * 60 * 2;
                $day[2]['end_time'] = $day[3]['end_time'] - 24 * 60 * 60 * 2;
                $day[2]['begin_time'] = $day[3]['begin_time'] - 24 * 60 * 60 * 2;
                $day[1]['end_time'] = $day[2]['end_time'] - 24 * 60 * 60 * 2;
                $day[1]['begin_time'] = $day[2]['begin_time'] - 24 * 60 * 60 * 2;
                return array($day);
            case 3:
                $day[7]['end_time'] = "$time";
                $day[7]['begin_time'] = "$time";
                $day[6]['end_time'] = $time;
                $day[6]['begin_time'] = strtotime("yesterday") - 24 * 60 * 60 * 4;
                $day[5]['end_time'] = $day[6]['begin_time'] - 1;
                $day[5]['begin_time'] = $day[6]['begin_time'] - 24 * 60 * 60 * 5;
                $day[4]['end_time'] = $day[5]['end_time'] - 24 * 60 * 60 * 5;
                $day[4]['begin_time'] = $day[5]['begin_time'] - 24 * 60 * 60 * 5;
                $day[3]['end_time'] = $day[4]['end_time'] - 24 * 60 * 60 * 5;
                $day[3]['begin_time'] = $day[4]['begin_time'] - 24 * 60 * 60 * 5;
                $day[2]['end_time'] = $day[3]['end_time'] - 24 * 60 * 60 * 5;
                $day[2]['begin_time'] = $day[3]['begin_time'] - 24 * 60 * 60 * 5;
                $day[1]['end_time'] = $day[2]['end_time'] - 24 * 60 * 60 * 5;
                $day[1]['begin_time'] = $day[2]['begin_time'] - 24 * 60 * 60 * 5;
                return array($day);
            case 4:
                $day[7]['end_time'] = $time;
                $day[7]['begin_time'] = $time;
                $day[6]['end_time'] = $time;
                $day[6]['begin_time'] = strtotime("yesterday") - 24 * 60 * 60 * 9;
                $day[5]['end_time'] = $day[6]['begin_time'] - 1;
                $day[5]['begin_time'] = $day[6]['begin_time'] - 24 * 60 * 60 * 10;
                $day[4]['end_time'] = $day[5]['end_time'] - 24 * 60 * 60 * 10;
                $day[4]['begin_time'] = $day[5]['begin_time'] - 24 * 60 * 60 * 10;
                $day[3]['end_time'] = $day[4]['end_time'] - 24 * 60 * 60 * 10;
                $day[3]['begin_time'] = $day[4]['begin_time'] - 24 * 60 * 60 * 10;
                $day[2]['end_time'] = $day[3]['end_time'] - 24 * 60 * 60 * 10;
                $day[2]['begin_time'] = $day[3]['begin_time'] - 24 * 60 * 60 * 10;
                $day[1]['end_time'] = $day[2]['end_time'] - 24 * 60 * 60 * 10;
                $day[1]['begin_time'] = $day[2]['begin_time'] - 24 * 60 * 60 * 10;
                return array($day);
        }
    }

    /**
     * @param $time   unix时间
     * @return 星期几
     */
    function weekday($time)
    {
        $weekday = array('星期日', '星期一', '星期二', '星期三', '星期四', '星期五', '星期六');
        return $weekday[date('w', $time)];
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

    /**
     * @param int $juese 代理商下的商户还是代理 0表示所有的
     * @param $id            代理的id 或者员工的id
     * @param $style 1 代表代理商 2代表员工
     * @param string $time 时间段
     * @return mixed         获得代理一级下的情况
     */
    public function agent_down($juese = 2, $style = 1, $id, $time = "")
    {
//        $type=I("get.type");
//        $id=43;
        if ($style == 1) $map['agent_id'] = $id;
        if ($style == 2) $map['pid'] = $id;
//       根据选择的类型判断属于全部0，商户3，代理2 
        switch ($juese) {
            case 0:
                break;
            case 2;
                $map['role_id'] = 2;
                break;
            case 3;
                $map['role_id'] = 3;
                break;
        }
        $down = $this->users->alias("u")
            ->join("left join __MERCHANTS_ROLE_USERS__ ur on ur.uid=u.id")
            ->field("u.*,ur.role_id")
            ->where($map)
            ->order("role_id asc,add_time desc");
        if ($this->page !== null) {
            $down = $down->select();
            // $down = $down->limit($this->page,10)->select();
        } else {
            $down = $down->select();
        }
        $new_message = array();
//       给商户添加其上代理发展的员工
        foreach ($down as $k => &$v) {
            if ($style == 1) {
                if ($v['agent_id'] == $v['pid']) {
                    $v['staff'] = "0";
                    $v['staff_name'] = "";
                } else {
                    $user = $this->users->where('id=' . $v['pid'])->find();
                    $v['staff'] = $v['pid'];
                    $v['staff_name'] = $user['user_name'];
                }
            }

//           判断是否是代理商 
            if ($v['role_id'] == 2) {
                $total_price = $this->count_agent($v['id'], $time);
                $v['paytime'] = date("Y-m-d", $total_price['paytime']);
                $v['week'] = $this->weekday($total_price['paytime']);
                $v['total_num'] = $total_price['total_num'];
                $v['total_price'] = $total_price['total_price'];
//                if($total_price['paytime']== null) unset($down[$k]);
            } else if ($v['role_id'] == 3) {//           判断是否是商户
                $total_price = $this->count_merchant($v['id'], $time);
                $v['paytime'] = date("Y-m-d", $total_price['paytime']);
                $v['week'] = $this->weekday($total_price['paytime']);
                $v['total_num'] = $total_price['total_num'];
                $v['total_price'] = $total_price['total_price'];
                //if($total_price['total_price']== 0) unset($down[$k]);
                if ($total_price['total_num'] == 0) unset($down[$k]);
            }
            if ($v['role_id'] == 6 || $v['role_id'] == 4 || $v['role_id'] == 5 || $v['role_id'] == 7) {//           其他的
                unset($down[$k]);
            }
            if ($down[$k]) {
                $new_message[] = $down[$k];
            }
        }
        return $new_message;

//       $this->ajaxReturn(array("code" => "success", "msg" => L('LOGIN_SUCCESS'),'userInfo'=>$down));

    }

    /**
     * @param $type  时间分类
     * @return array 0: 所有1 :今日 2:昨日 3:本周 4:本月 5:上周 6:上月
     */
    public function get_number($type)
    {
        switch ($type) {
            case 0:
                return;
            case 1:
                //  今天
                return 1;
            case 2:
                //昨天
                return 1;
            case 3:
                //        本周
                $time = time();
                $number = date('w', $time);
                if ($number == 0) $number = 7;
                return $number;
            case 4:
                //        本月
                $beginThismonth = mktime(0, 0, 0, date('m'), 1, date('Y'));
                $endToday = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y'));
                return ($endToday - $beginThismonth) / 24 / 60 / 60;
            case 5:
                //上周
                return 7;
            case 6:
                //上月
                $beginLastmonth = mktime(0, 0, 0, date("m") - 2, 1, date("Y"));
                $endLastmonth = mktime(23, 59, 59, date("m") - 1, 0, date("Y")) + 1;
                return ($endLastmonth - $beginLastmonth) / 24 / 60 / 60;
        }

    }

    public function shuzu($data)
    {
        $total = array();
        foreach ($data as $key => $value) {
            foreach ($value as $k => $v) {
                $total[] = $v;
            }
        }
        return $total;
    }

    /**
     * 代理上月，本月返佣
     */
    public function get_agent_maid()
    {
        if($this->userInfo['role_id'] != 3){
            $this->ajaxReturn(array('code'=>'error','msg'=>'role_id error'));
        }
        $uid = M()->query('select getchild('.$this->userId.') as uids');
        $uids = $uid[0]['uids'];
        $mer_ids = $this->get_merchant_id($uids); //获取商户id
        $map['merchant_id'] = array('in',$mer_ids);
        $map['status'] = '1';
        $last_month = $this->calc_maid($map,$this->type_time(6));//上月
        $current_month = $this->calc_maid($map,$this->type_time(4));//本月
        $this->ajaxReturn(array('code'=>'success','msg'=>'ok','data'=>array('last_mouth'=>$last_month['rebate'],'current_month'=>$current_month['rebate'])));
    }

    /**
     * 获取代理下某月的交易汇总和返佣详情
     */
    public function get_merchants_maid_detail()
    {
        if($this->userInfo['role_id'] != 3){
            $this->ajaxReturn(array('code'=>'error','msg'=>'role_id error'));
        }
        ($date = I('date')) || $this->ajaxReturn(array('code'=>'error','msg'=>'date is empty'));
        #date格式必须是yyyy-mm
        (substr_count($date,'-') == 1 && strlen($date) == 7) || $this->ajaxReturn(array('code'=>'error','msg'=>'date格式错误'));
        $uid = M()->query('select getchild('.$this->userId.') as uids');
        $uids = $uid[0]['uids'];
        $mer_ids = $this->get_merchant_id($uids); //获取商户id
        $map['merchant_id'] = array('in',$mer_ids);
        $map['status'] = '1';
        $agent = $this->calc_maid($map,$this->get_appoint_month($date));
        $mer_ids_arr = explode(',',$mer_ids);
        $merchant = array();
        foreach ($mer_ids_arr as &$v) {
            $map['merchant_id'] = $v;
            $calc = $this->calc_maid($map,$this->get_appoint_month($date));
            if($calc){
                $calc['merchant_jiancheng'] = M('merchants')->where('id='.$v)->getField('merchant_jiancheng');
                $merchant[] = $calc;
            }
        }
        $this->ajaxReturn(array('code'=>'success','msg'=>'ok','agent_rebate'=>$agent['rebate'],'merchant_data'=>$merchant));
    }

    /** 获取指定年月的开始时间戳和结束时间戳
     * @param $y_m 年月,yyyy-mm格式
     * @return array
     */
    public function get_appoint_month($y_m)
    {
        ($start_time = strtotime( $y_m )) || $this->ajaxReturn(array('code'=>'error','msg'=>'时间格式有误'));
        $mdays = date( 't', $start_time );
        $end_time = strtotime(date( 'Y-m-' . $mdays . ' 23:59:59', $start_time ));

        return array($start_time,$end_time);
    }

    /** 计算返佣
     * @param $map 查询条件
     * @param $time_array 时间戳区间
     * @return array
     */
    private function calc_maid($map,$time_array)
    {
        $map['paytime'] = array('BETWEEN',$time_array);
        $month_pay = M('pay')->where($map)->field('price,cost_rate,paystyle_id,bank,cardtype')->select();
        $agent_rate = M('merchants_agent')->where(array('uid'=>$this->userId))->field('wx_rate,ali_rate')->find();
        $rebate = '0';//费率总计
        $count = count($month_pay);//交易总笔数

        $price = '0';//交易总金额
        foreach ($month_pay as &$v) {
            $price += $v['price'];
            if ($v['bank'] == 11 && $v['paystyle_id'] == 3) {
                if ($v['cardtype'] == '00' || $v['cardtype'] == '03') {
                    $v['agent_rate'] = '0.41';
                } elseif ($v['cardtype'] == '01' || $v['cardtype'] == '02') {
                    $v['agent_rate'] = '0.53';
                }
                $bcdiv = $v['price'] * ($v['cost_rate'] - $v['agent_rate']);
            }else{
                if(!$v['cost_rate']){
                    $bcdiv = 0;
                }else{
                    $bcdiv = $v['price']*($v['cost_rate']-$agent_rate[$v['paystyle_id']==1?'wx_rate':'ali_rate']);
                }
            }
            $rebate = bcadd($rebate,bcdiv(($bcdiv),'100',2),2);
        }
        #条件不是数组就是商户，如果返佣为0则不显示
        if(!is_array($map['merchant_id']) && $rebate==0){
            return false;
        }else{
            return array('rebate'=>"$rebate",'num'=>"$count",'price'=>"$price");
        }
    }

    /**通过uid获取商户id
     * @param $uid 商户uid
     * @return mixed|string
     */
    public function get_merchant_id($uid){
        $where['uid'] = array('in',$uid);
        $id = M('merchants')->where($where)->getField('id',true);
        if($id){
            $id = implode(',',$id);
        }
        return $id;
    }
}
