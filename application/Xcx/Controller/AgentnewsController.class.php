<?php
namespace Api\Controller;
use Common\Controller\ApibaseController;

class  AgentnewsController extends ApibaseController
{
    public $id;
    public function __construct()
    {
        exit('network error');
        parent::__construct();
        $this->id=$this->userInfo['uid'];
    }
//代理 总额
    public function service(){
        $this->checkLogin();
        $type=I("type");
        if($type == "")$type = 0;
        $id=$this->id;
        $time=$this->type_time($type);
        $pays = $this->count_agent($id,$time);
        $this->ajaxReturn(array("code" => "success","msg"=>"成功", "data"=>$pays));
//        dump($pays);
    }
//所有下级信息
    public function my_down()
    {
        $this->checkLogin();
        $id=$this->id;
        $juese=I("juese");
        $down =$this->agent_down($juese,1,$id);
        $page=I("page") == 0 ? 0 : I("page");
        $start=$page*10;
        $comment = array_slice($down,$start,10);
        $this->ajaxReturn(array("code" => "success","msg"=>"成功","data"=>$comment));
    }
//我的客户里面的按月分类
    public function my_customer(){
        $this->checkLogin();
        $u_id=I("id");
//        $u_id = 50;
        $type=I("type");
        $time=$this->type_month($type);
        $role=M()->query("SELECT ur.role_id FROM ypt_merchants_users u right join ypt_merchants_role_users ur on ur.uid=u.id WHERE ( u.id=$u_id )");
        if($role[0]['role_id'] ==2){
            $data=$this->count_agent($u_id,$time,1);
        }elseif ($role[0]['role_id'] ==3){
            $data=$this->count_merchant($u_id,$time,1);
        }
//        dump($data);
//        $this->ajaxReturn($data);
        $this->ajaxReturn(array("code" =>"success","msg"=>"成功","data"=>$data));

    }
// 得到用户当月按日进行汇总
    public function user_detail()
    {
        $this->checkLogin();

        $u_id=I("id");
        $role_id=I("role_id");
        $type=I("type");
//        代理
        $total=array();
        if($role_id == 2) {
            $total= $this->count_agent($u_id);
        }
        if($role_id == 3){
            $total= $this->count_merchant($u_id);
        }
        $page=I("page") == 0 ? 0 : I("page");
        $start= $page*10+1;
        $day=array();
        for ($i=$start;$i<=$start+9;$i++){
            $time=$this->get_day($type,$i);
            $day_detail=date("Y-m-d",$time[0]);
            if($time == "已经超过当前月份了") {
                $day_detail="";
                $count=array("paytime"=>null,"total_number"=>"0","total_price"=>"0");
            } else{
                if($role_id == 2) {
                    $count= $this->count_agent($u_id,$time);
//                    array_push($day[$i],$count);
                }
                if($role_id == 3){
                    $count= $this->count_merchant($u_id,$time);
//                    array_push($day[$i],$count);
                }
            }
            $count['time']=$day_detail;
            $day[]=$count;
        }
        $this->ajaxReturn(array("code"=>"success","msg"=>"成功","data"=>$day));
    }
//流水
    public function coin()
    {
        $this->checkLogin();

        $id=$this->id;
        $type=I("type");
//        2是代理 3是商户
        $role=I("role_id");
//        0是生序 1是降序
        $order=I("price_order");
        if($order != 0){
            $order="d";
        }
        if($type == 7)
        {
            $begin_time =strtotime(I("begin_time"));
            $end_time=strtotime(I("end_time"));
            $number =($end_time-$begin_time)/24/60/60;
            $time=$end_time+24*60*60-1;
        }else{
            $number=$this->get_number($type);
            $time=$this->type_time($type);
            $time=$time[1];
        }

        $data=array();
        for ($i=1;$i<=$number;$i++){
            $time_now=$this->day_detail($time,$i);
            $data[$i]= $this->agent_down($role,1,$id,$time_now);
            if($data[$i]==null){unset($data[$i]);}else{
            $data[$i]=array2sort($data[$i],'total_price',$order);
             if($data[$i][0]['id']==null)unset($data[$i][0]);
            }
        }
        $data=$this->shuzu($data);
        $this->ajaxReturn(array("code"=>"success","msg"=>"成功","data"=>$data));

    }
//流水里面的交易详情
    public function coin_detail()
    {
        $this->checkLogin();
        $u_id=I("id");
        $role_id=I("role_id");
        $paytime=I("paytime");
        $time[0]=strtotime($paytime);
        $time[1]=$time[0]+24*60*60-1;
        if($role_id ==2){
            $data=$this->count_agent($u_id,$time,1);
        }elseif ($role_id ==3){
            $data=$this->count_merchant($u_id,$time,1);
        }
        $this->ajaxReturn(array("code"=>"success","msg"=>"成功","data"=>$data));

    }
//    报表一接口
    public function excel()
    {
        $this->checkLogin();
        $id=$this->id;
        $type=I("type");
        $time=$this->type_month($type);
        $data=$this->count_agent($id,$time,1);
        $data['tab1']="http://sy.youngport.com.cn/index.php?g=Api&m=Agentnews&a=excel_total&type=".$type."&id=".$id;
        $data['tab2']="http://sy.youngport.com.cn/index.php?g=Api&m=Agentnews&a=excel_number&type=".$type."&id=".$id;
        $this->ajaxReturn(array("code" => "success","msg"=>"成功", "data"=>$data));
    }
//    代理商报表一 交易总额比较
    public function excel_total()
    {
        $id=I("id");
        $type=I("type");
        $time=$this->type_month($type);
        $data=$this->count_agent($id,$time,1);
//        dump($data);
        $data=json_encode($data);
        $this->assign('data',$data);
        $this->display();
    }
//    代理商 报表一 交易数量比较
    public function excel_number()
    {
        $id=I("id");
        $type=I("type");
        $time=$this->type_month($type);
        $data=$this->count_agent($id,$time,1);
//        dump($data);
        $data=json_encode($data);
        $this->assign('data',$data);
        $this->display();
    }
    //    代理商报表二
    public function excel_detail()
    {
        $this->checkLogin();
        $id=$this->id;
        $type=I("type");
        $data['tab1']="http://sy.youngport.com.cn/index.php?g=Api&m=Agentnews&a=excel_total_detail&type=".$type."&id=".$id;
        $data['tab2']="http://sy.youngport.com.cn/index.php?g=Api&m=Agentnews&a=excel_number_detail&type=".$type."&id=".$id;
        $this->ajaxReturn(array("code" => "success","msg"=>"成功", "data"=>$data));

//        $this->display();
    }

    //    代理商报表二  总值
    public function excel_total_detail()
    {
        $id=I("id");
        $type=I("type");
        $time=$this->get_mark($type);
        $count=array();
        foreach ($time[0] as $k=>$v)
        {
            $array=array($time[0][$k]['begin_time'],$time[0][$k]['end_time']);
            $count[$k]['pay']=$this->count_agent($id,$array,1);
            $count[$k]['time']=date("n.d",$v['begin_time'])."~".date("n.d",$v['end_time']);
        }
        $count=json_encode($count);
//        print_r($count);
//        dump($count);
        $this->assign("count",$count);
        $this->display();
    }
    //    代理商报表二  总量
    public function excel_number_detail()
    {
        $id=I("id");
        $type=I("type");
        $time=$this->get_mark($type);
        $count=array();
        foreach ($time[0] as $k=>$v)
        {
            $array=array($time[0][$k]['begin_time'],$time[0][$k]['end_time']);
            $count[$k]['pay']=$this->count_agent($id,$array,1);
            $count[$k]['time']=date("n.d",$v['begin_time'])."~".date("n.d",$v['end_time']);
        }
        $count=json_encode($count);
        $this->assign("count",$count);
        $this->display();
    }

//    员工流水
    public function customer()
    {
        $this->checkLogin();
        $id=I("id");
        $type=I("type");
//      0全部  2是代理 3是商户
        $role=I("role_id");
//        0是生序 1是降序
        $order=I("price_order");
        if($order == "1"){
            $order="d";
        }
        if($type == 7)
        {
            $begin_time =strtotime(I("begin_time"));
            $end_time=strtotime(I("end_time"));
            $number =($end_time-$begin_time)/24/60/60;
            $time=$end_time+24*60*60-1;
        }else{
            $number=$this->get_number($type);
            $time=$this->type_time($type);
            $time=$time[1];
        }
        $data=array();
        for ($i=1;$i<=$number;$i++){
            $time_now=$this->day_detail($time,$i);
            $data[$i]= $this->agent_down($role,2,$id,$time_now);
            if(empty($data[$i])){unset($data[$i]);}else{
                $data[$i]=array2sort($data[$i],'total_price',$order);}
                if($data[$i][0]['id']==null){unset($data[$i][0]);}
        }
        $data=$this->shuzu($data);
        $this->ajaxReturn(array("code"=>"success","msg"=>"成功","data"=>$data));

    }
//    员工流水详情
    public function customer_detail()
    {
        $this->checkLogin();
        $u_id=I("id");
        $role_id=I("role_id");
        $paytime=I("paytime");
        $time[0]=strtotime($paytime);
        $time[1]=$time[0]+24*60*60-1;
        if($role_id ==2){
            $data=$this->count_agent($u_id,$time,1);
        }elseif ($role_id ==3){
            $data=$this->count_merchant($u_id,$time,1);
        }
        $this->ajaxReturn(array("code"=>"success","msg"=>"成功","data"=>$data));
    }
    /**
     * @param $id  用户表里面的id
     * @param $time 按时间区分
     * @param $is_detail 是否需要微信和支付宝支付的细节
     * 返回该商户交易的总额
     */
    public function count_merchant($id,$time="",$is_detail=0)
    {
        if($time !="")$map['paytime']=array("between",$time);
        $map['uid']=$id;
        $map['p.status']=1;
        if($is_detail == 1){
            $field="p.paytime,ifnull(sum(price),0) as total_price,count(p.id) as total_num,ifnull(sum( if( p.paystyle_id =1, 1, 0)),0) as per_weixin_num,ifnull(sum( if( p.paystyle_id =2, 1, 0)),0) as per_ali_num,
            ifnull(sum( if( p.paystyle_id =1,p.price, 0)),0) as per_wei_price,ifnull(sum( if( p.paystyle_id =2,p.price, 0)),0) as per_ali_price";
        }else{
            $field="p.paytime,count(p.id) as total_num,ifnull(sum(price),0) as total_price";
        }
        $pay=M('merchants')->alias("m")
            ->join("right join __PAY__ p on p.merchant_id=m.id")
            ->field($field)
            ->where($map)
            ->find();
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
    function count_agent($id,$time="",$is_detail=0)
    {
        $users = $this->get_category($id);
//        $user_id="43,44,45,46,47,48,49,52,50,53,51,54,";
        $users= explode(",",$users);
        $count= count($users);
        $category_ids="";
        $a=M();
        for($i=1;$i < $count-1;$i++){
            $id=$users[$i];
            $role_id=$a->query("select id from ypt_merchants_role_users where role_id = 3 And uid =$id");
            if($role_id[0]['id'] != ""){
                $merchant_id = $a->query("select id from ypt_merchants where uid = $id limit 1");
                $category_ids .=$merchant_id[0]['id'].",";
            }
        }
        $ids=explode(",",$category_ids);
        $map['merchant_id']=array('in',$ids);
        $map['p.status']=1;
        if($time !=""){ $map['paytime']=array("between",$time);}
        if($is_detail == 1){
            $field="p.paytime,ifnull(sum(price),0) as total_price,count(p.id) as total_num,ifnull(sum( if( p.paystyle_id =1, 1, 0)),0) as per_weixin_num,ifnull(sum( if( p.paystyle_id =2, 1, 0)),0) as per_ali_num,
            ifnull(sum( if( p.paystyle_id =1,p.price, 0)),0) as per_wei_price,ifnull(sum( if( p.paystyle_id =2,p.price, 0)),0) as per_ali_price";
        }else{
            $field="p.paytime,count(p.id) as total_num,ifnull(sum(price),0) as total_price";
        }
        $pay=M("pay")->alias("p")->field($field)->where($map)->find();
//        return M("pay")->getLastSql();
//        if($pay['paytime']!= null){return $pay;}
        return $pay;
    }

    /**
     * @param $users 以字符串拼接所有的商户
     * @return  返回所有商户的支付详情
     */
    function agent_detail($users)
    {
//        $user_id="43,44,45,46,47,48,49,52,50,53,51,54,";
        $users= explode(",",$users);
        $count= count($users);
        $category_ids="";
        $a=M();
        for($i=1;$i < $count-1;$i++){
            $id=$users[$i];
            $role_id=$a->query("select id from ypt_merchants_role_users where role_id = 3 And uid =$id");
            if($role_id[0]['id'] != ""){
                $merchant_id = $a->query("select id from ypt_merchants where uid = $id limit 1");
                $category_ids .=$merchant_id[0]['id'].",";
            }
        }
        $ids=explode(",",$category_ids);
        $map['merchant_id']=array('in',$ids);
        $pays=M("pay")->where($map)->order('paytime desc')->select();
        return $pays;
    }

    /**
     * 测试时间信息
     */
    public function checkdata()
    {
        $type=4;
        dump($this->type_time($type));
        exit;

    }
    /**
     * @param $type   支付的类型;
     * @return int    0: 所有1 :今日 2:昨日 3:本周 4:本月 5:上周 6:上月 7 为自定义时间
     */
    function type_time($type){
        switch ($type){
            case 0:
                return ;
            case 1:
                //  今天
                $beginToday= mktime(0,0,0,date('m'),date('d'),date('Y'));
                $endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
                return array($beginToday,$endToday);
            case 2:
                //昨天
                $beginYesterday=mktime(0,0,0,date('m'),date('d')-1,date('Y'));
                $endYesterday=mktime(0,0,0,date('m'),date('d'),date('Y'))-1;
                return array($beginYesterday,$endYesterday);
            case 3:
                //        本周
                $beginThisweek=mktime(0,0,0,date('m'),date('d')-date('w')+1,date('Y'));
                $endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;

//                $endThisweek=mktime(23,59,59,date('m'),date('d')-date('w')+7,date('Y'));
                return array($beginThisweek,$endToday);
            case 4:
                //        本月
                $beginThismonth = mktime(0,0,0,date('m'),1,date('Y'));
                $endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;

//                $endThismonth = mktime(23,59,59,date('m'),date('t'),date('Y'));
                return array($beginThismonth,$endToday);
            case 5:
                //上周
                $beginLastweek=mktime(0,0,0,date('m'),date('d')-date('w')+1-7,date('Y'));
                $endLastweek=mktime(23,59,59,date('m'),date('d')-date('w')+7-7,date('Y'));
                return array($beginLastweek,$endLastweek);
            case 6:
                //上月
                $beginLastmonth =  mktime(0, 0 , 0,date("m")-1,1,date("Y"));
                $endLastmonth =  mktime(23,59,59,date("m") ,0,date("Y"));
                return array($beginLastmonth,$endLastmonth);
        }
    }

    /**
     * @param $type  选择的为负几月份
     * 0:全部  1:当前月份  2:-1月份 3:-2月份 4:-3月份 5:-4月份 6:-5月份
     * @return array|  负月份的时间戳
     */
    function type_month($type)
    {
        switch ($type)
        {
            case 0:
                return;
            case 1:
                $begin_time = mktime ( 0, 0, 0, date ( "m" ), 1, date ( "Y" ) ) ;
                $end_time = mktime ( 23, 59, 59, date ( "m" ), date ( "t" ), date ( "Y" ) ) ;
                return array($begin_time,$end_time);
            case 2:
                $begin_time =  mktime(0, 0 , 0,date("m")-1,1,date("Y"));
                $end_time =  mktime(23,59,59,date("m") ,0,date("Y"));
                return array($begin_time,$end_time);
            case 3:
                $begin_time =  mktime(0, 0 , 0,date("m")-2,1,date("Y"));
                $end_time =  mktime(23,59,59,date("m")-1 ,0,date("Y"));
                return array($begin_time,$end_time);
            case 4:
                $begin_time =  mktime(0, 0 , 0,date("m")-3,1,date("Y"));
                $end_time =  mktime(23,59,59,date("m")-2 ,0,date("Y"));
                return array($begin_time,$end_time);
            case 5:
                $begin_time =  mktime(0, 0 , 0,date("m")-4,1,date("Y"));
                $end_time =  mktime(23,59,59,date("m")-3 ,0,date("Y"));
                return array($begin_time,$end_time);
            case 6:
                $begin_time =  mktime(0, 0 , 0,date("m")-5,1,date("Y"));
                $end_time =  mktime(23,59,59,date("m")-4 ,0,date("Y"));
                return array($begin_time,$end_time);
        }
    }


    /**
     * @param $number  选择支付距离现在几天 0 只全部 1 最后一天 2 最后第二天
     * @param $type  是否是全部数据 不是的话判断最后的时间 0是全部
     * @return array
     */
    function get_day($type,$number=0)
    {
//        区分是否是本月或者全部
        if($type==0||$type==1){
            $time=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
        }else {
            $time= $this->type_month($type);
            $time=$time[1];
        }
//       这个是判断总金额的
        if($number ==0){
            return ;
        }
        $begin_time = $time-24*60*60*($number)+1;
        $end_time = $time-24*60*60*($number-1);
//        全部时间不用判断是或否会超过本月第一天
        if($type == 0){
            return array($begin_time,$end_time);
        }
        $last_time=$this->type_month($type);
        $last_time=$last_time[0];
        if($begin_time < $last_time){
            return "已经超过当前月份了";
        }
        return array($begin_time,$end_time);
    }

    /**
     * @param $type    时间类型
     * @param $number  往前推进一天  1为选中当前天
     * @return string
     */
    function day_detail($time,$number)
    {

//        $time=$this->type_time($type);
//        $time_start=$time[0];
        $time_end=$time;
        $begin_time = $time_end-24*60*60*($number)+1;
        $end_time = $time_end-24*60*60*($number-1);
//        if($begin_time < $time_start) return "超过时间无数据显示";
        return array($begin_time,$end_time);
    }

    /**
     * @param $type  选择的时间分类
     * @return array
     */
    function get_mark($type)
    {
        $time=time();
        switch ($type)
        {
            case 1:
                $day[7]['end_time'] =  $time;
                $day[7]['begin_time'] =  strtotime("today");
                $day[6]['end_time'] =  $day[7]['begin_time'] -1;
                $day[6]['begin_time'] =  $day[7]['begin_time']-24*60*60;
                $day[5]['end_time'] =  $day[6]['end_time'] -24*60*60;
                $day[5]['begin_time'] =  $day[6]['begin_time']-24*60*60;
                $day[4]['end_time'] =  $day[5]['end_time'] -24*60*60;
                $day[4]['begin_time'] =  $day[5]['begin_time']-24*60*60;
                $day[3]['end_time'] =  $day[4]['end_time'] -24*60*60;
                $day[3]['begin_time'] =  $day[4]['begin_time']-24*60*60;
                $day[2]['end_time'] =  $day[3]['end_time'] -24*60*60;
                $day[2]['begin_time'] =  $day[3]['begin_time']-24*60*60;
                $day[1]['end_time'] =  $day[2]['end_time'] -24*60*60;
                $day[1]['begin_time'] =  $day[2]['begin_time']-24*60*60;
                return array($day);
            case 2:
                $day[7]['end_time'] =  $time;
                $day[7]['begin_time'] =  strtotime("yesterday");
                $day[6]['end_time'] =  $day[7]['begin_time'] -1;
                $day[6]['begin_time'] =  $day[7]['begin_time']-24*60*60*2;
                $day[5]['end_time'] =  $day[6]['end_time'] -24*60*60*2;
                $day[5]['begin_time'] =  $day[6]['begin_time']-24*60*60*2;
                $day[4]['end_time'] =  $day[5]['end_time'] -24*60*60*2;
                $day[4]['begin_time'] =  $day[5]['begin_time']-24*60*60*2;
                $day[3]['end_time'] =  $day[4]['end_time'] -24*60*60*2;
                $day[3]['begin_time'] =  $day[4]['begin_time']-24*60*60*2;
                $day[2]['end_time'] =  $day[3]['end_time'] -24*60*60*2;
                $day[2]['begin_time'] =  $day[3]['begin_time']-24*60*60*2;
                $day[1]['end_time'] =  $day[2]['end_time'] -24*60*60*2;
                $day[1]['begin_time'] =  $day[2]['begin_time']-24*60*60*2;
                return array($day);
            case 3:
                $day[7]['end_time'] =  "$time";
                $day[7]['begin_time'] =  "$time";
                $day[6]['end_time'] =  $time;
                $day[6]['begin_time'] =  strtotime("yesterday")-24*60*60*4;
                $day[5]['end_time'] =  $day[6]['begin_time'] -1;
                $day[5]['begin_time'] =  $day[6]['begin_time']-24*60*60*5;
                $day[4]['end_time'] =  $day[5]['end_time'] -24*60*60*5;
                $day[4]['begin_time'] =  $day[5]['begin_time']-24*60*60*5;
                $day[3]['end_time'] =  $day[4]['end_time'] -24*60*60*5;
                $day[3]['begin_time'] =  $day[4]['begin_time']-24*60*60*5;
                $day[2]['end_time'] =  $day[3]['end_time'] -24*60*60*5;
                $day[2]['begin_time'] =  $day[3]['begin_time']-24*60*60*5;
                $day[1]['end_time'] =  $day[2]['end_time'] -24*60*60*5;
                $day[1]['begin_time'] =  $day[2]['begin_time']-24*60*60*5;
                return array($day);
            case 4:
                $day[7]['end_time'] =  $time;
                $day[7]['begin_time'] =  $time;
                $day[6]['end_time'] =  $time;
                $day[6]['begin_time'] =  strtotime("yesterday")-24*60*60*9;
                $day[5]['end_time'] =  $day[6]['begin_time'] -1;
                $day[5]['begin_time'] =  $day[6]['begin_time']-24*60*60*10;
                $day[4]['end_time'] =  $day[5]['end_time'] -24*60*60*10;
                $day[4]['begin_time'] =  $day[5]['begin_time']-24*60*60*10;
                $day[3]['end_time'] =  $day[4]['end_time'] -24*60*60*10;
                $day[3]['begin_time'] =  $day[4]['begin_time']-24*60*60*10;
                $day[2]['end_time'] =  $day[3]['end_time'] -24*60*60*10;
                $day[2]['begin_time'] =  $day[3]['begin_time']-24*60*60*10;
                $day[1]['end_time'] =  $day[2]['end_time'] -24*60*60*10;
                $day[1]['begin_time'] =  $day[2]['begin_time']-24*60*60*10;
                return array($day);
        }
    }
    /**
     * @param $time   unix时间
     * @return 星期几
     */
    function  weekday($time){
        $weekday=array('星期日','星期一','星期二','星期三','星期四','星期五','星期六');
        return $weekday[date('w',$time)];
    }

//    得到所有的子节点
    /**
     * @param $category_id 带入代理商户的id
     * @return string  代理商下所有的商户id
     */
    function get_category( $category_id ){
        $db = M();
        $category_ids = $category_id.",";
        $child_category = $db -> query("select id from ypt_merchants_users where agent_id = '$category_id'");
        foreach( $child_category as $key => $val ){
            $category_ids .= $this->get_category( $val["id"] );
        }
        return $category_ids;
    }

    /**
     * @param int $juese     代理商下的商户还是代理 0表示所有的
     * @param $id            代理的id 或者员工的id
     * @param $style          1 代表代理商 2代表员工
     * @param string $time   时间段
     * @return mixed         获得代理一级下的情况
     */
    public function agent_down($juese=2,$style=1,$id,$time="")
    {
//        $type=I("get.type");
//        $id=43;
        if($style == 1)$map['agent_id'] = $id;
        if($style == 2)$map['pid']=$id;
//       根据选择的类型判断属于全部0，商户3，代理2
        switch ($juese){
            case 0:
                break;
            case 2;
                $map['role_id']=2;
                break;
            case 3;
                $map['role_id']=3;
                break;
        }
        $down=M('merchants_users')->alias("u")
            ->join("left join __MERCHANTS_ROLE_USERS__ ur on ur.uid=u.id")
            ->field("u.*,ur.role_id")
            ->where($map)
            ->order("role_id asc,add_time desc")
            ->select();
//       给商户添加其上代理发展的员工
        foreach ($down as $k=>&$v){
            if($style == 1){
                if($v['agent_id']==$v['pid']){
                    $v['staff']="0";
                    $v['staff_name']="";
                }else{
                    $user=M("merchants_users")->where('id='.$v['pid'])->find();
                    $v['staff']=$v['pid'];
                    $v['staff_name'] = $user['user_name'];
                }
            }
//           判断是否是代理商
            if($v['role_id']==2){
                $total_price=$this->count_agent($v['id'],$time);
                $v['paytime']=date("Y-m-d",$total_price['paytime']);
                $v['week']=$this->weekday($total_price['paytime']);
                $v['total_num'] = $total_price['total_num'];
                $v['total_price'] = $total_price['total_price'];
//                if($total_price['paytime']== null) unset($down[$k]);
            }
//           判断是否是商户
            if($v['role_id']==3){
                $total_price=$this->count_merchant($v['id'],$time);
                $v['paytime']=date("Y-m-d",$total_price['paytime']);
                $v['week']=$this->weekday($total_price['paytime']);
                $v['total_num'] = $total_price['total_num'];
                $v['total_price'] = $total_price['total_price'];
//                if($total_price['paytime']== null) unset($down[$k]);
            }
//           其他的
            if($v['role_id']==6||$v['role_id']==4||$v['role_id']==5||$v['role_id']==7){
                unset($down[$k]);
            }
        }
        return $down;

//       $this->ajaxReturn(array("code" => "success", "msg" => L('LOGIN_SUCCESS'),'userInfo'=>$down));

    }

    /**
     * @param $type  时间分类
     * @return array 0: 所有1 :今日 2:昨日 3:本周 4:本月 5:上周 6:上月
     */
    public function get_number($type)
    {
        switch ($type){
            case 0:
                return ;
            case 1:
                //  今天
                return 1;
            case 2:
                //昨天
                return 1;
            case 3:
                //        本周
                $time=time();
                $number=date('w',$time);
                 if($number ==0)$number=7;
                return $number;
            case 4:
                //        本月
                $beginThismonth = mktime(0,0,0,date('m'),1,date('Y'));
                $endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'));
                return  ($endToday-$beginThismonth)/24/60/60;
            case 5:
                //上周
                return 7;
            case 6:
                //上月
                $beginLastmonth =  mktime(0, 0 , 0,date("m")-2,1,date("Y"));
                $endLastmonth =  mktime(23,59,59,date("m")-1 ,0,date("Y"))+1;
                return ($endLastmonth-$beginLastmonth)/24/60/60;
        }

    }

    public function shuzu($data)
    {
        $total=array();
        foreach ($data as $key=>$value){
            foreach ($value as $k=>$v){
                $total[]=$v;
            }
        }
        return $total;
    }
}
