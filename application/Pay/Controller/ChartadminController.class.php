<?php
namespace Pay\Controller;
use Common\Controller\AdminbaseController;

class ChartadminController extends AdminbaseController{

    protected $pays;
    public function __construct()
    {
        parent::__construct();
        $this->pays=M('pay');
    }

    function index()
    {
        //昨天
        $beginYesterday=mktime(0,0,0,date('m'),date('d')-1,date('Y'));
        $endYesterday=mktime(0,0,0,date('m'),date('d'),date('Y'))-1;
        $data['p.paytime'] =array('between',array($beginYesterday,$endYesterday));
        $data['p.status'] =1;
        $data['p.paystyle_id'] = array('in',array(1,2));
        $pays = $this->count_merchant_total($data);
        $time_numer=array();
        for($i=0;$i<=11;$i++){
            $time_end = $endYesterday-60*60*2*$i;
            $time_start = $time_end-60*60*2+1;
            $time_numer[]=$this->count_time_total($time_start,$time_end);
        }
        $pays=json_encode($pays);
        $time_numer=json_encode($time_numer);
        $this->assign('pay',$pays);
        $this->assign('time_numer',$time_numer);
        $this->display();
    }

    public function count_merchant_total($map)
    {
        $field ="u.user_name,p.merchant_id,ifnull(sum(if( p.status =1,p.price, 0)),0) as total_price,ifnull(sum( if(p.status=1, 1, 0)),0) as total_num,
            ifnull(sum( if( p.paystyle_id =1 And p.status=1, 1, 0)),0) as per_weixin_num,ifnull(sum( if( p.paystyle_id =2 And p.status=1, 1, 0)),0) as per_ali_num,
            ifnull(sum( if( p.paystyle_id =1 And p.status=1,p.price, 0)),0) as per_wei_price,ifnull(sum( if( p.paystyle_id =2 And p.status=1,p.price, 0)),0) as per_ali_price";
//        $map['m.add_time'] = array("between",array(time()-60*60*24*50,time()));
        $pay = $this->pays->alias("p")
            ->join("left join __MERCHANTS__ m on m.id=p.merchant_id")
            ->join("left join __MERCHANTS_USERS__ u on u.id = m.uid")
            ->where($map)->field($field)
            ->group("merchant_id")
            ->order("total_price desc")
            ->limit(10)->select();
        foreach ($pay as $k =>&$v){
            if($v['user_name'] == null) $v['user_name'] = "no";
            else $v['user_name'] = mb_substr($v['user_name'],0,5,'utf-8');
        }

        return $pay;
    }

    public function count_time_total($time_start,$time_end)
    {
        $map['paytime'] = array('between',array($time_start,$time_end));
        $map['paystyle_id'] = array('in',array(1,2));
        $map['status'] = 1;
        $field="ifnull(sum( if(p.status=1, 1, 0)),0) as total_num,
            ifnull(sum( if( p.paystyle_id =1 And p.status=1, 1, 0)),0) as per_weixin_num,
            ifnull(sum( if( p.paystyle_id =2 And p.status=1, 1, 0)),0) as per_ali_num";
        $pay=$this->pays->alias("p")->where($map)->field($field)->find();
        return $pay;
    }


   }