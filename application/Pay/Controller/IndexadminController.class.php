<?php
namespace Pay\Controller;
use Common\Controller\AdminbaseController;

class IndexadminController extends AdminbaseController{
    protected $pay;
    protected $merchants;
    function _initialize() {
        parent::_initialize();
        $this->pay = M('pay');
        $this->merchants=M("merchants");
    }
	public function index(){
        /**
         * 实例化流水表和商户表
         */
        $pay=$this->pay;
        $merchants=$this->merchants;
//        echo time().'<br/>';
        $yesterday_start=strtotime("yesterday");
        $yesterday_end=strtotime("today");
//        echo $yesterday_end.'<br/>';
//        echo $yesterday_start.'<br/>';
        $data['tomo_total_price']=$pay->where("paytime >$yesterday_start And paytime < $yesterday_end And status=1")->sum('price');
        $data['tomo_total_number']=$pay->where("paytime >$yesterday_start And paytime < $yesterday_end And status=1")->count('id');
        $data['tomo_total_wxnumber']=$pay->where("paytime >$yesterday_start And paytime < $yesterday_end And paystyle_id=1 And status=1")->count('id');
        $data['tomo_total_zfbnumber']=$pay->where("paytime >$yesterday_start And paytime < $yesterday_end And paystyle_id=2 And status=1")->count('id');
        $data['tomo_total_merchants']=$merchants->where("add_time >$yesterday_start And add_time < $yesterday_end")->count('id');

        $data['total_pay']=$pay->where('status=1')->sum('price');
        $data['merchants']=$merchants->where('status=1')->count('id');
        $data['total_number']=$pay->where('status=1')->count('id');
        if( $data['tomo_total_price'] ==""){$data['tomo_total_price']=0;}
        $this->assign('data',$data);
//        echo $total_pay;
		$this->display();
	}
}