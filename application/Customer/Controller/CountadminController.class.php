<?php
namespace Customer\Controller;
use Common\Controller\AdminbaseController;

class CountadminController extends AdminbaseController{

    protected $pay;
    protected $merchant;
    protected $user;
    protected $merchant_user;
    public function _initialize()
    {
        parent::_initialize();
        $this->pay=M('pay');
        $this->merchant=M('merchants');
        $this->user=M('users');
        $this->merchant_user=M('merchants_users');
    }
    public function index(){
        $id=session('ADMIN_ID');
        if(!$id)$this->error("未找到用户");
//        根据天下的商户的id找到对应的用户里面的电话
        $user=$this->user->where(array("id"=>$id))->find();
        $user_phone=$user['mobile'];
//        根据电话找到商户的id
        $merchant_user=$this->merchant_user->where(array("user_phone"=>$user_phone))->find();
        if(!$merchant_user)exit("非有关人员");
        $uid=$merchant_user['id'];
        $yesterday_start=strtotime("yesterday");
//        echo $yesterday_start;
        $yesterday_end=strtotime("today");
//        echo $yesterday_end;
//        根据商户的id找到商户的具体信息
        $merchants=$this->merchant
            ->alias("m")
            ->join("right join __PAY__ p on m.id=p.merchant_id")
            ->field('p.paystyle_id,p.remark,p.price,p.paytime,p.id,p.status,sum(price)as total_price,count(p.id) as total_num,sum( if( p.paystyle_id =1, 1, 0)) as total_weixin_num ,
            sum( if( p.paystyle_id =2,1, 0)) as total_ali_num,sum( if( p.paystyle_id =1,p.price, 0)) as total_wei_price,sum( if( p.paystyle_id =2,p.price, 0)) as total_ali_price');

        $total=$merchants->where("p.status=1 And uid=$uid ")->find();
        $merchants=$this->merchant
            ->alias("m")
            ->join("right join __PAY__ p on m.id=p.merchant_id")
            ->field('p.paystyle_id,p.remark,p.price,p.paytime,p.id,p.status,sum(price)as total_price,count(p.id) as total_num,sum( if( p.paystyle_id =1, 1, 0)) as total_weixin_num ,
            sum( if( p.paystyle_id =2,1, 0)) as total_ali_num,sum( if( p.paystyle_id =1,p.price, 0)) as total_wei_price,sum( if( p.paystyle_id =2,p.price, 0)) as total_ali_price');

        $data=$merchants->where("p.paytime >$yesterday_start And p.paytime < $yesterday_end And p.status=1 And uid=$uid ")->find();
        $this->assign('total',$total);
        $this->assign('data',$data);
//        dump($total);
//        dump($data);
//        echo $total_pay;
        $this->display();
    }
}
