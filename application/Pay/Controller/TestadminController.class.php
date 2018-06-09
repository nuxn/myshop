<?php
namespace Pay\Controller;
use Common\Controller\AdminbaseController;

class TestadminController extends AdminbaseController{

    protected $pays;
    public function __construct()
    {
        parent::__construct();
        $this->pays=M('pay');
    }

    function index()
    {
        $id = 1 ;
        $a=D("Pay/BadeBank")->choose_bank($id);
        $this->display();
        echo json_encode($a);exit;

        $people = array("Bill", "Steve", "Mark", "David");

        function king($n, $m){
            $monkeys = range(1, $n);
            $i=0;
            $k=$n;
            while (count($monkeys)>1) {
                if(($i+1)%$m==0) {
                    unset($monkeys[$i]);
                } else {
                    array_push($monkeys,$monkeys[$i]);
                    unset($monkeys[$i]);
                }
                $i++;
            }
            return current($monkeys);
        }

        $a = king(5, 2);
        exit;
        //昨天
        $beginYesterday=mktime(0,0,0,date('m'),date('d')-1,date('Y'));
        $endYesterday=mktime(0,0,0,date('m'),date('d'),date('Y'))-1;
        $data['p.paytime'] =array('between',array($beginYesterday,$endYesterday));
        $data['p.status'] =1;
        $pays = $this->count_merchant_total($data);
        $time_numer=array();
        for($i=0;$i<=11;$i++){
            $time_end = $endYesterday-60*60*2*$i;
            $time_start = $time_end-60*60*2+1;
            $time_numer[]=$this->count_time_total($time_start,$time_end);
            var_dump(array($time_start,$time_start));
        }
//        var_dump($time_numer);
//        var_dump($pays);

        $pays=json_encode($pays);
        $time_numer=json_encode($time_numer);
        $this->assign('pay',$pays);
        $this->assign('time_numer',$time_numer);
        $this->display();
    }

    function add()
    {
//        $users=M("bade_user")->where(array('status'=>1))->field("id,short_name")->select();
//        $this->assign("users",$users);
        $this->display();
    }
    function add_post()
    {
        header("content-type:text/html;charset=utf-8");
        $file = $_FILES;
        var_dump($file);
            if ($_FILES) {
                $upload = new \Think\Upload();
                $upload->maxSize = 3145728;
                $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
                $upload->rootPath = C('_WEB_UPLOAD_');
                $upload->savePath = 'merchant/';
                $upload->saveName = uniqid;//保持文件名不变

                $info = $upload->upload();
                var_dump($info);exit;

                if (!$info) {
                    $this->error($upload->getError());
                }
            }
            var_dump($_FILES);
            echo 213;
            exit;
            $data = I("");
            if (M("bade_merchant")->add($data)) $this->success(L('添加成功'), U("index"));
            else  $this->error("添加失败!");
        var_dump($_POST);exit;
    }

    public function upload_into(){
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize   =     3145728 ;// 设置附件上传大小
        $upload->exts      =      array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath  =     C('_WEB_UPLOAD_'); // 设置附件上传根目录
        $upload->savePath  =     'merchants/'; // 设置附件上传（子）目录
        // 上传文件
        $info   =   $upload->upload();
        if($info){
            $data['type']=1;
            $data['url']=$info['id_card_img_b']['savepath'].$info['id_card_img_b']['savename'];
            echo json_encode($data);
            exit();
        }else{
            $data['type']=2;
            $data['message']=$upload->getError();
            echo json_encode($data);
            exit();
        }
    }


    public function count_merchant_total($map)
    {
        $field ="u.user_name,p.merchant_id,ifnull(sum(if( p.status =1,p.price, 0)),0) as total_price,ifnull(sum( if(p.status=1, 1, 0)),0) as total_num,
            ifnull(sum( if( p.paystyle_id =1 And p.status=1, 1, 0)),0) as per_weixin_num,ifnull(sum( if( p.paystyle_id =2 And p.status=1, 1, 0)),0) as per_ali_num,
            ifnull(sum( if( p.paystyle_id =1 And p.status=1,p.price, 0)),0) as per_wei_price,ifnull(sum( if( p.paystyle_id =2 And p.status=1,p.price, 0)),0) as per_ali_price";
        $pay = $this->pays->alias("p")
            ->join("left join __MERCHANTS__ m on m.id=p.merchant_id")
            ->join("left join __MERCHANTS_USERS__ u on u.id = m.uid")
            ->where($map)->field($field)
            ->group("merchant_id")
            ->order("total_price desc")
            ->limit(10)->select();
        foreach ($pay as $k =>&$v){
            $v['user_name'] = mb_substr($v['user_name'],0,5,'utf-8');;
        }
        return $pay;
    }

    public function count_time_total($time_start,$time_end)
    {
        $map['paytime'] = array('between',array($time_start,$time_end));
        $map['status'] = 1;
        $field="ifnull(sum( if(p.status=1, 1, 0)),0) as total_num,
            ifnull(sum( if( p.paystyle_id =1 And p.status=1, 1, 0)),0) as per_weixin_num,
            ifnull(sum( if( p.paystyle_id =2 And p.status=1, 1, 0)),0) as per_ali_num";
        $pay=$this->pays->alias("p")->where($map)->field($field)->find();
        return $pay;
    }



    /**
     * @param $id   商户的id
     * @param $time  时间区间
     * return 返回商户所有的流水
     */
    public function merchant_detail($id,$checker_id,$time,$paystyle="",$status="")
    {
        $map['u.id']=$id;
        if($time != null)$map['p.paytime']=array("between",$time);
        if($paystyle !== "")$map['paystyle_id']=$paystyle;
        if($status !== "")$map['p.status']=$status ;
        if($checker_id !== "")$map['p.checker_id']=$checker_id ;
        $filed="sum( if( p.status =1, p.price, 0)) as pay_money ,sum( if( p.status =2, p.price_back, 0)) as back_money,p.paytime";
        $pays['total']=M('merchants_users')->alias("u")
            ->join("__MERCHANTS__ m on m.uid=u.id")
            ->join("__PAY__ p on p.merchant_id=m.id")
            ->order("paytime desc")
            ->where($map)
            ->field($filed)
            ->find();
        $pays['detail']=M('merchants_users')->alias("u")
            ->join("__MERCHANTS__ m on m.uid=u.id")
            ->join("__PAY__ p on p.merchant_id=m.id")
            ->order("paytime desc")
            ->where($map)
            ->field("p.*")
            ->select();


        return $pays;
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

    public function get_status($status_type)
    {
        if($status_type ==""){
            return array('in',array(1,2,3,4));
        }
        if($status_type =="1"){
            return array('in',array(1));
        }
        if($status_type =="2"){
            return array('in',array(2,3,4));
        }
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


    function check_data()
    {
        $this->display();
    }

    protected function makesign()
    {

        $ab=array("merchant_code"=> "107584000030001",
            "terminal_code"=> "web",
            "orderid"=> "201703311547111490946431493753");

        var_dump($ab);
//        unset($this->values['sign']);
//        var_dump($this->values);

        //签名步骤一：按字典序排序参数
//        echo $this->memessage['key'];
        ksort($ab);

        $string = $this->wToUrlParams();
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=" . "326545";
//        $string = $string . "&key=" . $this->memessage['key'];
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        //file_put_contents('./data/log/wz/micropay.log', date("Y-m-d H:i:s") . $result . PHP_EOL, FILE_APPEND | LOCK_EX);
        var_dump($result);
        echo 213;
        exit;
//        return $result;
    }

    protected function wToUrlParams()
    {
        //过滤掉'',null字段,不包括0
        $params = array_filter($this->values, function ($v) {
            if ($v === null || $v === '') {
                return false;
            }

            return true;
        });
        //微众返回的json result字段里面也是一个json直接把里面的json拼接在url上
        $buff = '';
        foreach ($params as $key => $p) {
            if ($key != 'sign' && !is_array($p)) {
                $buff .= $key . '=' . $p . '&';
            }
            if (is_array($p)) {
                //不对中文进行转换
                foreach ($p as $k => $v) {
                    $p[$k] = urlencode($v);
                }
                $buff .= $key . '=' . urldecode(json_encode($p)) . '&';
            }
        }

        $buff = trim($buff, '&');

        return $buff;

    }

    public function getform()
    {
        if(IS_POST){
            dump($_POST);
        }else{
            $this->display();
        }
    }

    public function wx_confirm_pay()
    {
        header("content-type:text/html;charset=utf-8");
        vendor("Wzpay.Wzpay");
        $ab=new \Wzpay();
        $c=$ab->apply();
        var_dump($c);exit;
        $url="https://svrapi.webank.com/wbap-bbfront/SelectMrch";
        $mch_id="103100073330000";
        $agency="1075840001";
        $ab->setParameter('agency', $agency);
        $ab->setParameter('mch_id', $mch_id);
        $c=$ab->getParameters($url,$mch_id);
        var_dump($c);
    }

    /**
     * @param $arr 要加密的数组
     * @param $sign 当前使用的key
     * @return string 生成签名
     */
    private function getSign($arr)
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
//        echo '【string1】' . $String . '</br>';
        //签名步骤二：在string后加入KEY
        $key = "youngPort4a21";
        $String = $String . "&key=" . $key;
//        echo "【string2】" . $String . "</br>";
        //签名步骤三：MD5加密
        $String = md5($String);
//        echo "【string3】 " . $String . "</br>";
        //签名步骤四：所有字符转为大写
        $result_ = strtoupper($String);
//        echo "【result】 " . $result_ . "</br>";
        return $result_;
    }

    /**
     *    作用：格式化参数，签名过程需要使用
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

}