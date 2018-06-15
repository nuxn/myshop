<?php

namespace Merchants\Controller;
use Common\Controller\AdminbaseController;
use Think\Page;
/**
 * 商户入驻资料填写
 * Class Merchants
 * @package Merchants\Controller
 */
class MsintoController extends AdminbaseController
{
    public function index(){
    	$where="AND 1=1";
    	if($_POST['user_phone']){
    		$user_phone=$_POST['user_phone'];
    		$where.=" AND ypt_merchants_users.user_phone like '%".$user_phone."%'";
    		$this->assign('user_phone',$user_phone);
    	}
    	if($_POST['merchant_name']){
    		$merchant_name=$_POST['merchant_name'];
    		$where.=" AND ypt_merchants.merchant_name like '%".$merchant_name."%'";
    		$this->assign('merchant_name',$merchant_name);
    	}
    	$sql="select ypt_merchants_mpay.id,ypt_merchants_mpay.bankMchtId,ypt_merchants_mpay.wechat,ypt_merchants_mpay.alipay,ypt_merchants_mpay.uid,ypt_merchants.merchant_name,ypt_merchants_users.user_phone from ypt_merchants,ypt_merchants_users,ypt_merchants_mpay where ypt_merchants_mpay.uid=ypt_merchants.id AND ypt_merchants.uid=ypt_merchants_users.id ".$where." order by ypt_merchants_mpay.id desc";
   		$agents      =M("")->query($sql);// 查询满足要求的总记录数
		$count = count($agents);
		$page = $this->page($count, 20);
     	$data = array_slice($agents, $page->firstRow, $page->listRows);

  		$this->assign('data',$data);// 赋值数据集
		$this->assign('page',$page->show('Admin'));// 赋值分页输出
    	$this->display();
    }
    public function sms_index(){
        if($_POST['phone']){
            $phone=$_POST['phone'];
           $where['phone']=array('like',"%$phone%");
           $this->assign('phone',$_POST['phone']);
        }
        $start_time = I("start_time");
        $end_time = I("end_time");
        if (strtotime($start_time) > strtotime($end_time)) {
            $this->error("开始时间不能大于结束时间");
        }
        if (!empty($start_time) && !empty($end_time)) {
            $where['sms_time']= array('between', array($start_time, $end_time));
            $this->assign('start_time', $start_time);
            $this->assign('end_time', $end_time);
        }
        $agents=M('sms_logs')->where($where)->order('id desc')->select();
        $count = count($agents);
        $page = $this->page($count, 20);
        $data = array_slice($agents, $page->firstRow, $page->listRows);

        $this->assign('data',$data);// 赋值数据集
        $this->assign('page',$page->show('Admin'));// 赋值分页输出
        $this->display();
    }
}