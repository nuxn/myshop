<?php
namespace Api\Controller;
use Common\Controller\ApibaseController;

class  ShopnewsController extends  ApibaseController
{
//  用户表里面的id
    public $id;
    public function __construct()
    {
        parent::__construct();
        $this->id=$this->userInfo['uid'];
    }

//    商户服务
    public function service()
    {
        $this->checkLogin();
        $id=$this->id;
        $type=I("type");
        $time=$this->type_time($type);
        $count=$this->count_merchant($id,$time);
        $this->ajaxReturn(array("code" => "success","msg"=>"成功", "data"=>$count));

    }

//    流水
    public function coin()
    {
        $this->checkLogin();
        $id=$this->id;
        $type=I("type");
        $paystyle=I("paystyle");

        $status=I("status");
        $time=$this->type_time($type);
        $pays=$this->merchant_detail($id,$time,$paystyle,$status);
        $this->ajaxReturn(array("code" => "success","msg"=>"成功", "data"=>$pays));
    }
//商户某条交易信息详情
    public function coin_detail()
    {
        $this->checkLogin();
        $p_id=I("id");
        $pay=M("pay")
            ->where("id = $p_id")
            ->find();
        $checker_id=$pay['checker_id'];
        if($checker_id !=0){
            $checker_name = M()->query("select user_name from ypt_merchants_users where id=$checker_id");
            $pay['checker_name']=$checker_name[0]['user_name'];
        }else{
            $pay['checker_name']="";
        }
        $this->ajaxReturn(array("code" => "success","msg"=>"成功", "data" => $pay));

    }

//    台签
    public function cart()
    {
        $this->checkLogin();
        $id=$this->get_merchant($this->id);
        $m_id=M()->query("select id FROM ypt_merchants where uid =$id");
        $m_id=$m_id[0]['id'];
        $cart=M("merchants_cate")->where("merchant_id=$m_id")->find();
        $cart['barcode_img']="http://sy.youngport.com.cn/".$cart['barcode_img'];
        $this->ajaxReturn(array("code" => "success","msg"=>"成功", "data"=>$cart));
    }

//    收银员流水
    public function customer_coin()
    {
        $this->checkLogin();
        $id=I("id");
        $type=I("type");
        $paystyle=I("paystyle");
        $status=I("status");
        $time=$this->type_time($type);
        $pays=$this->customer_detail($id,$time,$paystyle,$status);
        $this->ajaxReturn(array("code" => "success","msg"=>"成功", "data"=>$pays));
    }
//    报表一
    public function excel()
    {
        $this->checkLogin();
        $id=$this->id;
        $type=I("type");
        $time=$this->type_month($type);
        $data=$this->count_merchant($id,$time,1);
        $data['tab1']="http://sy.youngport.com.cn/index.php?g=Api&m=Shopnews&a=excel_total&type=".$type."&id=".$id;
        $data['tab2']="http://sy.youngport.com.cn/index.php?g=Api&m=Shopnews&a=excel_number&type=".$type."&id=".$id;
        $this->ajaxReturn(array("code" => "success","msg"=>"成功", "data"=>$data));

    }
//    报表一  总值
    public function excel_total()
    {
        $id=I("id");
        $type=I("type");
        $time=$this->type_month($type);
        $data=$this->count_merchant($id,$time,1);
//        dump($data);
        $data=json_encode($data);
        $this->assign('data',$data);
        $this->display();
    }

//    商户 报表一 交易数量比较
    public function excel_number()
    {
        $id=I("id");
        $type=I("type");
        $time=$this->type_month($type);
        $data=$this->count_merchant($id,$time,1);
//        dump($data);
        $data=json_encode($data);
        $this->assign('data',$data);
        $this->display();
    }

//    报表二
    public function excel_detail()
    {
        $this->checkLogin();
        $id=$this->id;
        $type=I("type");
        $time=$this->get_mark($type);
        $count=array();
        foreach ($time[0] as $k=>$v)
        {
            $array=array($time[0][$k]['begin_time'],$time[0][$k]['end_time']);
            $count[$k]['pay']=$this->count_merchant($id,$array,1);
            $count[$k]['time']=date("n.d",$v['begin_time'])."~".date("n.d",$v['end_time']);
        }
        $data['tab1']="http://sy.youngport.com.cn/index.php?g=Api&m=Shopnews&a=excel_total_detail&type=".$type."&id=".$id;
        $data['tab2']="http://sy.youngport.com.cn/index.php?g=Api&m=Shopnews&a=excel_number_detail&type=".$type."&id=".$id;
        $this->ajaxReturn(array("code" => "success","msg"=>"成功", "data"=>$data));
    }

    //    商户报表二 交易总额
    public function excel_total_detail()
    {
        $id=I("id");
        $type=I("type");
        $time=$this->get_mark($type);
        $count=array();
        foreach ($time[0] as $k=>$v)
        {
            $array=array($time[0][$k]['begin_time'],$time[0][$k]['end_time']);
            $count[$k]['pay']=$this->count_merchant($id,$array,1);
            $count[$k]['time']=date("n.d",$v['begin_time'])."~".date("n.d",$v['end_time']);
        }
        $count=json_encode($count);
//        print_r($count);
//        echo $count;
//        exit;
        $this->assign("count",$count);
        $this->display();
    }

    //    商户报表二 交易总数量
    public function excel_number_detail()
    {
        $id=I("id");
        $type=I("type");
        $time=$this->get_mark($type);
        $count=array();
        foreach ($time[0] as $k=>$v)
        {
            $array=array($time[0][$k]['begin_time'],$time[0][$k]['end_time']);
            $count[$k]['pay']=$this->count_merchant($id,$array,1);
            $count[$k]['time']=date("n.d",$v['begin_time'])."~".date("n.d",$v['end_time']);
        }
        $count=json_encode($count);
//        print_r($count);
//        dump($count);
        $this->assign("count",$count);
        $this->display();
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
     * @param $time  时间区间
     * return 返回商户所有的流水
     */
    public function merchant_detail($id,$time,$paystyle="",$status="")
    {
        $map['u.id']=$id;
        if($time != null)$map['p.paytime']=array("between",$time);
        if($paystyle !== "0")$map['paystyle_id']=$paystyle;
        if($status !== "")$map['p.status']=$status;

        $pays=M('merchants_users')->alias("u")
            ->join("__MERCHANTS__ m on m.uid=u.id")
            ->join("__PAY__ p on p.merchant_id=m.id")
            ->order("paytime desc")
            ->where($map)
            ->field("p.*")
            ->select();
//        return M('merchants_users')->getLastSql();
        return $pays;
    }

    /**
     * @param $id   收银员的id
     * @param $time  时间区间
     * return 返回商户所有的流水
     */
    public function customer_detail($id,$time,$paystyle="",$status="")
    {
        $map['u.id']=$id;
        if($time != null)$map['p.paytime']=array("between",$time);
        if($paystyle !== "0")$map['paystyle_id']=$paystyle;
        if($status !== "")$map['p.status']=$status;

        $pays=M('merchants_users')->alias("u")
            ->join("__PAY__ p on p.checker_id = u.id")
            ->where($map)
            ->field("p.*")
            ->select();
        return $pays;
    }

    /**
     * 测试时间信息
     */
    public function checkdata()
    {
        $cip = getenv ( 'HTTP_CLIENT_IP' );//
        $xip = getenv ( 'HTTP_X_FORWARDED_FOR' );
        $rip = getenv ( 'REMOTE_ADDR' );
        echo $cip;
        echo "//";
        echo $xip;
        echo "//";
        echo $rip;
        exit;
        $type=I("type");
        dump($this->get_mark($type));
        exit;

    }
    /**
     * @param $type   支付的类型;
     * @return int    0: 所有1 :今日 2:昨日 3:本周 4:本月 5:上周 6:上月
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

    /**
     * 使用curl获取远程数据
     * @param  string $url url连接
     * @return string      获取到的数据
     */
    private function _curl_get_contents($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);                //设置访问的url地址
        // curl_setopt($ch,CURLOPT_HEADER,1);               //是否显示头部信息
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);               //设置超时
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);   //用户访问代理 User-Agent
        curl_setopt($ch, CURLOPT_REFERER, $_SERVER['HTTP_HOST']);        //设置 referer
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);          //跟踪301
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);        //返回结果
        $r = curl_exec($ch);
        curl_close($ch);

        return $r;
    }


    /**
     * 获取jssdk需要用到的数据
     * @return array jssdk需要用到的数据
     */
    private function _get_openid()
    {
        // 获取配置项
        $config = C('WEIXINPAY_CONFIG');
        // 如果没有get参数没有code；则重定向去获取openid；
        if (!isset($_GET['code'])) {
            // 返回的url
//            $redirect_uri = U('Pay/Barcode/qr_weixipay', '', '', true);
            $redirect_uri = 'https://sy.youngport.com.cn' . $_SESSION['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            $redirect_uri = urlencode($redirect_uri);
            $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $config['APPID'] . '&redirect_uri=' . $redirect_uri . '&response_type=code&scope=snsapi_base#wechat_redirect';
            redirect($url);
        } else {
            //如果有code参数；则表示获取到openid
            $code = I('code');
            //组合获取prepay_id的url
            $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $config['APPID'] . '&secret=' . $config['APPSECRET'] . '&code=' . $code . '&grant_type=authorization_code';
            //curl获取prepay_id
            $result = $this->_curl_get_contents($url);
            $result = json_decode($result, true);

            return $result['openid'];

        }
    }

    /**
     * @param $uid   商户或者收银员在用户表的id
     * @return mixed   商户的id
     */
    private function get_merchant($uid)
    {
        $role_id=M("merchants_role_users")->where("uid=$uid")->getField('role_id');
        if($role_id == 3){
            return $uid;
        }else{
            return M("merchants_users")->where("id=$uid")->getField("pid");
        }
    }
}
