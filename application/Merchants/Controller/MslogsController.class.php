<?php

namespace Merchants\Controller;
use Common\Controller\AdminbaseController;
use Think\Page;
/**
 * 商户入驻资料填写
 * Class Merchants
 * @package Merchants\Controller
 */
class MslogsController extends AdminbaseController
{
    public function index(){
   
    }
    public function logs(){
    	$start_time= date("YmdHis",strtotime(I('start_time')));
        $end_time=date("YmdHis",strtotime(I('end_time')));
        $merchant_no=trim(I('merchant_no'));
        $merchant_name=trim(I('merchant_name'));
        $transId=trim(I('transId'));
        $where=" 1=1";
        if($merchant_no){
            $map['merchant_no']=$merchant_no;
            $where.=" AND (b.bankMchtId like '%".$merchant_no."%' or c.bankMchtId like '%".$merchant_no."%')";
            $this->assign('merchant_no',$merchant_no);
        }
        if($merchant_name){
            $where.=" AND (d.merchant_name like '%".$merchant_name."%' or f.merchant_name like '%".$merchant_name."%')";
            $this->assign('merchant_name',$merchant_name);
        }
        if(I('start_time')&&I('end_time')){
          $where.=" AND ypt_ms_logs.refund_time BETWEEN ".$start_time." and ".$end_time;
          $this->assign('end_time',I('end_time'));
          $this->assign('start_time',I('start_time'));
        }
        if($transId){
            $where.=" AND ypt_ms_logs.transId like '%".$transId."%'";
            $this->assign('transId',$transId);
        }
        //select * from ypt_ms_logs  

        $sql="select ypt_ms_logs.id,ypt_ms_logs.pay_type,ypt_ms_logs.pay_status,ypt_ms_logs.order_sn,ypt_ms_logs.pay_time,ypt_ms_logs.pay_price,ypt_ms_logs.pay_reprice,ypt_ms_logs.transId,ypt_ms_logs.type,ypt_ms_logs.price,ypt_ms_logs.refund_time,
        case when ypt_ms_logs.pay_type= 'alipay' then b.bankMchtId else c.bankMchtId end as bankMchtId,
        case when ypt_ms_logs.pay_type= 'alipay' then d.merchant_name else f.merchant_name end as merchant_name,
        case when ypt_ms_logs.pay_type= 'alipay' then b.alipay else c.wechat end as pay_id
        from ypt_ms_logs 
        left join ypt_merchants_mpay b on ypt_ms_logs.pay_id = b.alipay 
        left join ypt_merchants_mpay c on ypt_ms_logs.pay_id = c.wechat 
        left join ypt_merchants d on b.uid=d.id
        left join ypt_merchants f on c.uid=f.id
        where ".$where." group by ypt_ms_logs.id order by ypt_ms_logs.refund_time desc";
        //$sql="select * from ytp_ms_logs"
		$agents      =M("")->query($sql);// 查询满足要求的总记录数
		$count = count($agents);
		$page = $this->page($count, 20);
     	$data = array_slice($agents, $page->firstRow, $page->listRows);

    	foreach ($data as $key => $value) {
    		$pay_id=$data[$key]['pay_id'];
    		$pay_type=$data[$key]['pay_type'];
    		if($pay_type=='wechat'){
    			$uid=M('merchants_mpay')->where(array('wechat'=>$pay_id))->find();
    			$uid=$uid['uid'];
    			$merchants_data=M('Merchants')->where(array('id'=>$uid))->find();
    			$data[$key]['merchant_name']=$merchants_data['merchant_name'];
    			$data[$key]['refund_time']= date("Y-m-d H:i:s",strtotime($data[$key]['refund_time']));
    			$data[$key]['pay_time']=date("Y-m-d H:i:s",strtotime($data[$key]['pay_time']));
    			$data[$key]['code']=$uid['bankMchtId'];
    		}elseif ($pay_type=='alipay') {
    			$uid=M('merchants_mpay')->where(array('alipay'=>$pay_id))->find();
    			$uid=$uid['uid'];
    			$merchants_data=M('Merchants')->where(array('id'=>$uid))->find();
    			$data[$key]['merchant_name']=$merchants_data['merchant_name'];
    			$data[$key]['refund_time']= date("Y-m-d H:i:s",strtotime($data[$key]['refund_time']));
    			$data[$key]['pay_time']=date("Y-m-d H:i:s",strtotime($data[$key]['pay_time']));
    			$data[$key]['code']=$uid['bankMchtId'];
    		}
    	}
  		$this->assign('data',$data);// 赋值数据集
		$this->assign('page',$page->show('Admin'));// 赋值分页输出
    	$this->display();
    }
    public function daylogs(){
    	if($_POST){
    		$time=I('time');
    		$where['time']= array('like',"%".$time."%");
            $this->assign('time',$time);
    		$agents      =M("ms_daylogs")->where($where)->select();// 查询满足要求的总记录数
    	}else{
    		$agents      =M("ms_daylogs")->select();// 查询满足要求的总记录数
    	}
    	
		$count = count($agents);
		$page = $this->page($count, 20);
     	$data = array_slice($agents, $page->firstRow, $page->listRows);
     	$this->assign('data',$data);// 赋值数据集
		$this->assign('page',$page->show('Admin'));// 赋值分页输出
    	$this->display();
    }
}

