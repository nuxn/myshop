<?php
namespace Pay\Controller;
use Common\Controller\AdminbaseController;

class CashadminController extends AdminbaseController{
    protected $cash;

    function __construct() {
        parent::__construct();
        $this->cash = M("pay_cash");
    }
/*
 * 支付信息详情页面
 * */
    public function index(){
//        if($_POST){
            $start_time=strtotime(I('post.sta   784fq846131ra23g.rt_time'));
            $end_time=strtotime(I('post.end_time'));
            $status=I('status');
            $user_phone=I('user_phone');
            $paystyle_id=I('paystyle');
            $merchant_name=I("merchant_name");

            if($start_time > $end_time){
                $this->error("开始时间不能小于结束时间");
            }
            if($paystyle_id){
                $map['paystyle_id']=$paystyle_id;
            }
            if($merchant_name){
                $map['m.merchant_name']=array('LIKE',"%$merchant_name%");
            }
            if($status !== "-1"&& $status!=""){
                if($status == "0"){
                    $map['a.status'] = 0;
                }
                $map['a.status']=$status;
            }
            if($user_phone){
                $map['user_phone']=$user_phone;
            }

            if($start_time&&$end_time){
                $map['paytime'] = array(array('EGT',$start_time),array('ELT',$end_time)) ;
            }

//        }
        $caches=$this->cash->alias('a')
                        ->join("left join __MERCHANTS__ m on m.id=a.mid")
                         ->join('left join __MERCHANTS_USERS__ u on m.uid = u.id')
                         ->field("u.id as u_id,u.user_phone,m.merchant_name,a.*")
                         ->where($map);
        $count=$caches->count();
        /*
         * 查询sql语句
         * echo $Pays->where($map)->_sql();
         * */
        $page = $this->page($count, 20);
        $caches->limit($page->firstRow , $page->listRows);
        $this->assign("page", $page->show('Admin'));

//        join方法将数组进行变换了，得重新定义join
        $caches=$this->cash->alias('a')
            ->join("left join __MERCHANTS__ m on m.id=a.mid")
            ->join('left join __MERCHANTS_USERS__ u on m.uid = u.id')
            ->field("u.id as u_id,u.user_phone,m.merchant_name,a.*")
            ->where($map)->order("paytime desc")->select();
        $this->assign("pays",$caches);
        $this->display();
    }

    public function add()
    {
        $this->display();
    }
    /*
     * 支付删除
     * */
    public function delete(){

        if($_POST){
            $ids=I("ids");
            foreach ($ids as $k=>$v){
                $this->pay->where("id=$v")->delete();
            }
            $this->success("恭喜你删除成功");
        }
        if($_GET){
            $id=I("id");
            $this->pay->where("id=$id")->delete();
            $this->success("恭喜你删除成功");
        }
    }
        /*
         *  改变支付状态
         * */
//    function change_status()
//    {
//        $pay = $this->pay;
//        $id 	= intval($_REQUEST['id']);
//        $status  =I("post.status") == 1 ? 0:1 ;
//        $this->ajaxReturn($status);
//        $this->Pay->where("id=$id")->setField('status', $status);
//    }
    public function change_status(){
        $id=I('post.id');
        $cate=$this->pay->find($id);
        $status=$cate['status']== 0 ? 1 : 0;
        echo $status;
        $this->pay->where("id=$id")->setField('status', $status);
    }

    //    检查支付
    public function check_pay()
    {
        $id=I("id");
        $pay_one=$this->pay->where("id=$id ")->find();
        if(!$pay_one){$this->error("订单号信息不符合");}
        $mid=$pay_one['merchant_id'];
        $mch_id=M("merchants_cate")->where("merchant_id=$mid")->getField("wx_mchid");
        $out_trade_no = $pay_one['remark'];
        if(!$mch_id){$this->error("订单号信息不符合");}
        if(!$out_trade_no){$this->error("订单号信息不符合");}
        $result = A("Pay/Barcode")->wz_query_order($out_trade_no,$mch_id);
        file_put_contents('./data/log/wz/check_pay.log', date("Y-m-d H:i:s") . json_encode($result) . PHP_EOL, FILE_APPEND | LOCK_EX);
        if($result['trade_state'] == "SUCCESS"){$this->success("改订单号支付为成功",U('Contentadmin/index'));}
        else{$this->error("订单支付不为成功");}
    }


}