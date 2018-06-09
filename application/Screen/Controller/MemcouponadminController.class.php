<?php
namespace Screen\Controller;
use Common\Controller\AdminbaseController;

class MemcouponadminController extends AdminbaseController
{

    protected $coupons;
    protected $coupon_users;
    protected $merchants;
    protected $merchants_users;
    public function __construct()
    {
        parent::__construct();
        $this->coupons = M("screen_coupons");
        $this->coupon_users =M("screen_user_coupons");
        $this->merchants=M("merchants");
        $this->merchants_users=M("merchants_users");
        $colors = get_color();
        $this->assign("colors", $colors);
    }

    public function index()
    {
        $select=I("");
        if($select['start_time'] ||$select['end_time']){
            $start_time = strtotime($select['start_time']);
            $end_time = strtotime($select['end_time']);
            $map['cu.create_time'] = array(array('EGT', $start_time), array('ELT', $end_time));
        }
        if($select['status'] !=""){
            $map['cu.status'] =$select['status'];
        }
        if($select['merchant_name'] !=""){
            $merchant_name=$select['merchant_name'];
            $map['m.merchant_name'] =array('like',"%$merchant_name%") ;
        }
        if($select['title'] !=""){
            $title=$select['title'];
            $map['c.title'] =array('like',"%$title%") ;
        }
        $users=$this->coupon_users
            ->alias("cu")
            ->join("left join __SCREEN_COUPONS__ c on c.card_id = cu.card_id")
            ->join("left join __MERCHANTS__ m on m.id=c.mid")
            ->field("cu.*,c.title,m.merchant_name")
            ->where($map)
            ->select();
        foreach ($users as $k=>&$v){
            switch ($v['status'])
            {
                case 0:
                    $v['status']="已使用";
                    break;
                case 1:
                    $v['status']="未使用";
                    break;
                default:
                    break;
            }
        }
        $count=count($users);
        $page = $this->page($count, 20);
        $list=array_slice($users,$page->firstRow,$page->listRows);
        $this->assign("page", $page->show('Admin'));
        $this->assign("formget", array_merge($_GET, $_POST));
        $this->assign("users",$list);
        $this->display();
    }

    protected function get_merchant_name($card_id)
    {
        $mid=$this->coupons->where("card_id='$card_id'")->getField("mid");
        if($mid)$uid=$this->merchants->where("id=$mid")->getField("uid");
        if($uid)$user_name=$this->merchants_users->where("id=$uid")->getField("user_name");
        return $user_name;
    }

    protected function get_card_name($card_id)
    {
        return $this->coupons->where("card_id='$card_id'")->getField("title");
    }

}