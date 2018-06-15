<?php
namespace Account\Controller;
use Common\Controller\AdminbaseController;

class CountadminController extends AdminbaseController{

    protected $everbill;
    function _initialize() {
        parent::_initialize();
        $this->everbill=M("everyday_bill_count");
    }
	public function index()
    {
        $bill_date=I("bill_date");
        if($bill_date)
        {
            $map['bill_date']=$bill_date;
        }
        $countbills=$this->everbill
                    ->field("bill_date,SUM(total_deal)as total_deal,SUM(consume_deal)as consume_deal,SUM(return_deal)as return_deal,SUM(reverse_deal)as reverse_deal,SUM(total_money)as total_money,SUM(poundage)as poundage,SUM(anency_poudage)as anency_poudage,SUM(pay_money)as pay_money")
                    ->group('bill_date')
                    ->where($map)
                    ->order("id desc")
                    ->select();

        $count=count($countbills);
        $page = $this->page($count, 20);
        $list=array_slice($countbills,$page->firstRow,$page->listRows);
        $this->assign("page", $page->show('Admin'));
        $this->assign("formget", array_merge($_GET, $_POST));
        $this->assign("bills",$list);
//        dump($bills);
		$this->display();
	}
}