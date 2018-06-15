<?php
/**
 * Created by PhpStorm.
 * User: Joan
 * Date: 2017/3/27
 * Time: 15:32
 */

namespace Xcx\Model;

use Think\Model;

class UserCashModel extends Model
{
	//查询商家的兑换券
    public function lists($uid)
    {
    		//查询
    		$where = array();
    		$where['uid']=$uid;
    		$where['status']=1;
    		$where['start_time'] = array('lt',time());
    		$where['end_time'] = array('gt',time());
    		$data = $this->field('id,title,up_price,price,end_time,start_time')->where($where)->select();
    	
    		//echo $this->_sql();
    		return $data;
    }
    //查询卖家兑换券 0未使用 ，1过期 
    public function _lists($uid,$status,$page)
    {
    		$where = array();
    		switch($status){
    			case 1:
    			$where['status']=1;
    			break;
    			case 2:
    			$where['end_time'] = array('lt',time());
    			$where['status']=1;
    			break;
    			case 3:
    			$where['status']=2;
    			break;
    		}
    		//查询
    		$where['uid']=$uid;
    		$data = $this->field('id,title,up_price,price,type,user_name,end_time,start_time,description')->page($page,5)->where($where)->select();
    		//echo $this->_sql();
    		return $data;
    }
    
    //查询兑换券信息
    public function info($cash_id,$uid){
    		$where['uid']=$uid;
    		$where['id'] = $cash_id;
    		$data = $this->where($where)->find();
    		if(empty($data)){
    			return $this->err('优惠券不存在');
    		}
    		if($data['status']==0){
    				return $this->err('优惠券已经无效');
    		}elseif($data['status']==2){
    				return $this->err('优惠券已经使用');
    		}
    		if($data['start_time'] > time()){
    				return $this->err('还没有到使用时间');
    		}
    		if($data['end_time'] < time()){
    				return 	$this->err('该优惠券已经过期');
    		}
    		return $data;	
    }
	public function err($msg){
				$this->error = $msg;
				return false;
	}
}
