<?php
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class MainController extends AdminbaseController {

    protected $pays;
    protected $users;
    protected $merchants;
    protected $mems;
    public function __construct()
    {
        parent::__construct();
        $this->pays=M('pay');
        $this->users = M("merchants_users");
        $this->merchants = M("merchants");
        $this->mems = M("screen_mem");
    }


    public function index1(){
    	
    	$mysql= M()->query("select VERSION() as version");
    	$mysql=$mysql[0]['version'];
    	$mysql=empty($mysql)?L('UNKNOWN'):$mysql;
    	
    	//server infomaions
    	$info = array(
            L('OPERATING_SYSTEM') => PHP_OS,
            L('OPERATING_ENVIRONMENT') => $_SERVER["SERVER_SOFTWARE"],
            L('PHP_VERSION') => PHP_VERSION,
            L('PHP_RUN_MODE') => php_sapi_name(),
            L('PHP_VERSION') => phpversion(),
            L('MYSQL_VERSION') =>$mysql,
            L('PROGRAM_VERSION') => THINKCMF_VERSION,
            L('UPLOAD_MAX_FILESIZE') => ini_get('upload_max_filesize'),
            L('MAX_EXECUTION_TIME') => ini_get('max_execution_time') . "s",
            L('DISK_FREE_SPACE') => round((@disk_free_space(".") / (1024 * 1024)), 2) . 'M',
    	);
    	$this->assign('server_info', $info);
    	$this->display("index1");
    }

    function index()
    {
        $id = session('ADMIN_ID');
        $info =$this->check_id($id);
        if(!$info){
            $this->index1();
        }else{
            //        @1 登录次数
            $row['login_number'] = $this->users->sum("login_num");
//        @2 用户数
            $row['user_number'] = $this->users->count("id");
//        @3 商户数
            $row['merchant_number'] = $this->merchants->count("id");
//        @4 微信用户数
            $row['mem_number'] = $this->mems->count("id");

            if(F("endYesterday")&&(F("endYesterday")+24*60*60+1)>time()){
                $time_numer =F("time_numer");
                $pay_top =F("pay_top");
            }else{
                //昨天
                $beginYesterday=mktime(0,0,0,date('m'),date('d')-1,date('Y'));
                $endYesterday=mktime(0,0,0,date('m'),date('d'),date('Y'))-1;
                $data['p.paytime'] =array('between',array($beginYesterday,$endYesterday));
                $data['p.status'] =1;
                $time_numer=array(); //昨日支付数量列表
                for($i=0;$i<=11;$i++){
                    $time_end = $endYesterday-60*60*2*$i;
                    $time_start = $time_end-60*60*2+1;
                    $time_numer[]=$this->count_time_total($time_start,$time_end);
                }
                $pay_top=$this->pays->alias("p")->where($data)->order("p.price desc")->limit(15)->field("p.paytime,p.price,p.paystyle_id,p.remark")->select();
                $time_numer=json_encode($time_numer);
//        缓存
                F("time_numer",$time_numer);
                F("pay_top",$pay_top);
                F("endYesterday",$endYesterday);
            }
            if(S('pays')){
                $pays=S('pays');
            }else{
                $pays = $this->count_merchant_total();//        支付总流水排名
                $pays=json_encode($pays);
                S('pays',$pays,60*60);
            }
            if(S('bank')){
                $bank=S('bank');
            }else{
                $bank=$this->count_bank_total(); //     银行流水排行
                $bank=json_encode($bank);
                S('bank',$bank,60*60);
            }
            $this->assign("bank",$bank);
            $this->assign("row",$row);
            $this->assign('pay',$pays);
            $this->assign('time_numer',$time_numer);
            $this->assign('pay_top',$pay_top);
            $this->display();
        }
    }

    public function check_id($id){
        $phone=M("users")->where(array("id"=>$id))->getField("mobile");
        if(!$phone)return true;
        if($phone == "17771507422") return true;
        $uid=$this->users->where(array("user_phone"=>$phone))->getField("id");
        if(!$uid)return true;
        $role=M("merchants_role_users")->where(array("uid"=>$uid))->getField("role_id");
        if(in_array($role,array('3','7'))){
            return false;
        }else{
            return true;
        }
    }

    public function count_merchant_total()
    {
        $map['p.status'] =1;
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

    public function count_bank_total()
    {
//        $field="
//        ifnull(sum( if( p.bank =1, p.price, 0)),0) as wz_bank,
//        ifnull(sum( if( p.bank =2, p.price, 0)),0) as ms_bank,
//        ifnull(sum( if( p.bank =3, p.price, 0)),0) as wx_bank,
//        ifnull(sum( if( p.bank =4, p.price, 0)),0) as zs_bank,
//        ifnull(sum( if( p.bank =7, p.price, 0)),0) as xy_bank,
//        ifnull(sum( if( p.bank =9, p.price, 0)),0) as sz_bank,
//        ifnull(sum( if( p.bank =11, p.price, 0)),0) as xdl_bank,
//        ifnull(sum( if( p.bank =12, p.price, 0)),0) as ls_bank";
        $field="
        ifnull(sum( if( p.bank =3, p.price, 0)),0) as wx_bank,
        ifnull(sum( if( p.bank =7, p.price, 0)),0) as xy_bank,
        ifnull(sum( if( p.bank =9, p.price, 0)),0) as sz_bank,
        ifnull(sum( if( p.bank =11, p.price, 0)),0) as xdl_bank,
        ifnull(sum( if( p.bank =12, p.price, 0)),0) as ls_bank,
        ifnull(sum( if( p.bank =13, p.price, 0)),0) as pa_bank";
        $banks=$this->pays->alias("p")->where(array('status'=>1))->field($field)->find();
        return $banks;
    }

    public function count_time_total($time_start,$time_end)
    {
        $map['paytime'] = array('between',array($time_start,$time_end));
        $map['status'] = 1;
        $field="ifnull(sum( if(p.status=1, 1, 0)),0) as total_num,
            ifnull(sum( if( p.paystyle_id =1 And p.status=1, 1, 0)),0) as per_weixin_num,
            ifnull(sum( if( p.paystyle_id =2 And p.status=1, 1, 0)),0) as per_ali_num";
        $pay=$this->pays->alias("p")->where($map)->field($field)->limit(10)->find();
        return $pay;
    }
}