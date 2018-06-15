<?php
namespace Account\Controller;
use Common\Controller\AdminbaseController;

class BilladminController extends AdminbaseController{

    protected $bill;
    function _initialize() {
        parent::_initialize();
        $this->bill=M("bill_record");
    }

	public function index()
    {
        $start_time=strtotime(I('start_time'));
        $end_time=strtotime(I('end_time'));
        $merchant_no=trim(I('merchant_no'));
        $merchant_name=trim(I('merchant_name'));
        $wz_order_zn=trim(I('wz_order_zn'));

        if($merchant_no){
            $map['merchant_no']=$merchant_no;
        }
        if($wz_order_zn){
            $map['wz_order_sn']="$wz_order_zn";
        }
        if($merchant_name){
            $map['merchant_name']=array('LIKE',"%$merchant_name%");
        }
        if($start_time&&$end_time){
            $map['create_time'] = array(array('EGT',$start_time),array('ELT',$end_time)) ;
        }

        $bills=$this->bill;
        $count=$bills->where($map)->count();

        $page = $this->page($count, 20);
        $bills->limit($page->firstRow , $page->listRows)->order("id desc");
        $bills=$this->bill->where($map)->select();
        foreach ($bills as $k =>&$v){
            $v['agent_bill'] = $v['poundage'] == "0.00" ? "0.00":sprintf("%.2f", $v['poundage']-$v['deal_money']*0.0004);
        }
        $this->assign("formget", array_merge($_GET, $_POST));
        $this->assign("bills",$bills);
        $this->assign("page", $page->show('Admin'));
        $this->display();
	}

}