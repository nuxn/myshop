<?php

namespace Account\Controller;
use Common\Controller\AdminbaseController;
use Think\Page;
/**
 * 商户入驻资料填写
 * Class Account
 * @package Account\Controller
 */
class JnmsBilladminController extends AdminbaseController
{
	protected $bill;
    protected $everbill;
    protected $banklogs;
    function _initialize() {
        parent::_initialize();
        $this->bill=M("jnms_daylogs");
        $this->everbill=M('jnms_bank_logs');
    }
	
	//流水列表
    public function index(){
    	$start_time=I('start_time');
        $end_time=I('end_time');
        $mch_id=trim(I('mch_id'));
        $mch_name=trim(I('mch_name'));
        $zs_order_sn=trim(I('zs_order_sn'));

        if($mch_id){
            $map['mch_id']=$mch_id;
        }
        if($zs_order_sn){
            $map['order_sn']=$zs_order_sn;
        }
        if($mch_name){
            $map['mch_name']=array('LIKE',"%$mch_name%");
        }
        if($start_time&&$end_time){
            $map['pay_time'] = array(array('EGT',$start_time),array('ELT',$end_time)) ;
        }
        $countbills=$this->bill
            ->where($map)
            ->order("id desc")
            ->select();
        $count=count($countbills);
        $page = $this->page($count, 20);
        $list=array_slice($countbills,$page->firstRow,$page->listRows);
        $this->assign("formget", array_merge($_GET, $_POST));
        $this->assign("page", $page->show('Admin'));
        $this->assign("bills",$list);
        $this->display();
    }
	
	//交易汇总
    public function daylogs(){
    	$pay_time=trim(I("pay_time"));
        if($pay_time)
        {
            $map['pay_time']=$pay_time;
			$this->assign('pay_time',$pay_time);
        }
        $countbills=$this->everbill
            ->where($map)
            ->order("id desc")
            ->select();
        $count=count($countbills);
        $page = $this->page($count, 20);
        $list=array_slice($countbills,$page->firstRow,$page->listRows);
        $this->assign("page", $page->show('Admin'));
        $this->assign("bills",$list);
        $this->display();
    }
}

