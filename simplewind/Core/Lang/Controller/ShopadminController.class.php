<?php
namespace Pay\Controller;
use Common\Controller\AdminbaseController;

class ShopadminController extends AdminbaseController{
	protected $merchant;
    protected $pay;
    function _initialize() {
        parent::_initialize();
        $this->merchant = M("merchants");
        $this->pay = M("pay");

    }

	public function index(){
	    if($_POST){
            $start_time=strtotime(I('post.start_time'));
            $end_time=strtotime(I('post.end_time'));
            $keyword=I('keyword');
            $paystyle_id=I('paystyle');
            if($paystyle_id){
                $map['paystyle_id']=$paystyle_id;
            }
            if($keyword){
                $map['merchant_name']=array('like',"%$keyword%");
            }
            if($start_time&&$end_time){
                $map['paytime'] = array(array('EGT',$start_time),array('ELT',$end_time)) ;
            }
        }
        $map['b.status']=1;
	    $this->merchant->alias("a")->join('__PAY__ b on a.id=b.merchant_id')->field('a.*,b.price,b.merchant_id,b.paytime,b.paystyle_id,b.status')->order("a.id asc");
        $merchant=$this->merchant->where($map)->select();
        $totals=array();
        $demo=0;
        /*
        * 引入$total 对数组进行重新组装，返回商家的流水数据
        * */
        foreach ($merchant as $k=>$v){
            if($k==0){
                $totals[$demo]['id']=$v['id'];
                $totals[$demo]['number']=0;
                $totals[$demo]['name']=$v['merchant_name'];
                $totals[$demo]['totals_price']=$v['price'];
            }
            if($totals[$demo]['id'] != $v['id']){
                $demo++;
                $totals[$demo]['id']=$v['id'];
                $totals[$demo]['number']=1;
                $totals[$demo]['name']=$v['merchant_name'];
                $totals[$demo]['totals_price']=$v['price'];
            }else{
                $totals[$demo]['number']++;
                $totals[$demo]['totals_price']+=$v['price'];
            }
        }
        $this->assign('totals',$totals);
		$this->display();
	}
}