<?php

namespace Account\Controller;
use Common\Controller\AdminbaseController;
use Think\Page;
/**
 * 商户入驻资料填写
 * Class Account
 * @package Account\Controller
 */
class ZsBilladminController extends AdminbaseController
{
	protected $bill;
    protected $everbill;
    protected $banklogs;
    function _initialize() {
        parent::_initialize();
        $this->bill=M("zs_logs");
        $this->everbill=M('zs_daylogs');
        $this->banklogs=M('zs_bank_logs');
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
            $map['zs_order_sn']=$zs_order_sn;
        }
        if($mch_name){
            $map['mz.mch_name']=array('LIKE',"%$mch_name%");
        }
        if($start_time&&$end_time){
            $map['pay_time'] = array(array('EGT',$start_time),array('ELT',$end_time)) ;
        }

        $count=$this->bill->alias('b')
            ->where($map)
            ->join('left join ypt_merchants_zspay mz on b.mch_id=mz.ul_mchid')
            ->count();
        $page = $this->page($count, 20);

        $bills=$this->bill->alias('b')
            ->field('b.*,mz.mch_name')
            ->where($map)
            ->join('left join ypt_merchants_zspay mz on b.mch_id=mz.ul_mchid')
            ->limit($page->firstRow , $page->listRows)
            ->order("b.pay_time desc")
            ->select();
        $this->assign("formget", array_merge($_GET, $_POST));
        $this->assign("bills",$bills);
        $this->assign("page", $page->show('Admin'));
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
	
	//打款记录
	public function banklogs()
	{
		$need_pay_date=trim(I("need_pay_date"));
		$mch_name = trim(I("mch_name"));
		$mch_id = trim(I("mch_id"));
        if($need_pay_date)
        {
            $map['need_pay_date']=$need_pay_date;
			$this->assign('need_pay_date',$need_pay_date);
        }
        if($mch_name)
        {
            $map['mch_name']=array('like',"%$mch_name%");
			$this->assign('mch_name',$mch_name);
        }
        if($mch_id)
        {
            $map['mch_id']=$mch_id;
			$this->assign('mch_id',$mch_id);
        }
        $countbills=$this->banklogs
			->alias('b')
			->join('left join ypt_merchants_zspay mz on b.mch_id = mz.ul_mchid')
			->field('b.*,mz.mch_name,mz.mobile')
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

