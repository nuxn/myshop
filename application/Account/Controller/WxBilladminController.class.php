<?php
namespace Account\Controller;
use Common\Controller\AdminbaseController;

class WxBilladminController extends AdminbaseController{

    protected $bill;
    protected $everbill;
    function _initialize() {
        parent::_initialize();
        $this->bill=M("bill_wx");
        $this->everbill=M('everyday_wx_bill');
    }

    public function index()
    {
        $start_time=strtotime(I('start_time'));
        $end_time=strtotime(I('end_time'));
        $merchant_no=trim(I('merchant_no'));
        $merchant_name=trim(I('merchant_name'));
        $wx_order_zn=trim(I('wx_order_zn'));

        if($merchant_no){
            $map['a.sub_mchid']=$merchant_no;
        }
        if($wx_order_zn){
            $map['wx_order_sn']="$wx_order_zn";
        }
        if($merchant_name){
            $map['c.merchant_name']=array('LIKE',"%$merchant_name%");
        }
        if($start_time&&$end_time){
            $map['bill_date'] = array(array('EGT',$start_time),array('ELT',$end_time)) ;
        }

        $count=$this->bill
            ->where($map)
            ->join('a left join ypt_merchants_upwx b on a.sub_mchid=b.sub_mchid')
            ->join('left join ypt_merchants c ON b.mid=c.id')
            ->count();
        $page = $this->page($count, 20);

        $bills=$this->bill
            ->field('a.*,c.merchant_name')
            ->where($map)
            ->join('a left join ypt_merchants_upwx b on a.sub_mchid=b.sub_mchid')
            ->join('left join ypt_merchants c ON b.mid=c.id')
            ->limit($page->firstRow , $page->listRows)
            ->order("a.bill_date desc")
            ->select();
        $this->assign("formget", array_merge($_GET, $_POST));
        $this->assign("bills",$bills);
        $this->assign("page", $page->show('Admin'));
        $this->display();
    }


    /**
     * 每天的汇总
     */
    public function count()
    {
        $bill_date=I("bill_date");
        if($bill_date)
        {
            $map['bill_date']=$bill_date;
        }
        $countbills=$this->everbill
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
        $this->display();
    }

}