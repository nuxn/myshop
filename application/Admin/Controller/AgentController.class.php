<?php
/**
 * 验证码处理
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class AgentController extends AdminbaseController{
	public function _initialize() {
		parent::_initialize();
	}
	public function agentmerchant(){
		$this->display();
	}
	public function get_agent(){
		$merchant_user_sql="select c.id as merchant_id from ypt_users as a,ypt_merchants_agent as b,ypt_merchants_users as c
							  where
							    a.id=b.admin_user_id 
							  AND
							    b.uid=c.id
							  AND 
							    a.id='1' limit 1";
		$merchantuserlimit=M()->query($merchant_user_sql);
		$merchant_user_id=$merchantuserlimit['0']['merchant_id'];
		$merchants_users_sql='select a.id,a.pid as pId,d.agent_name as name from 
								  ypt_merchants_users as a,ypt_merchants_role_users as b,ypt_merchants_role as c,ypt_merchants_agent as d
								where 
								  a.id=b.uid
								AND
								  b.role_id=c.id  
								AND
								  d.uid=a.id
								AND
								  c.id = 2';
		$merchant_user_data=M()->query($merchants_users_sql);
		foreach ($merchant_user_data as $key => $value) {
			$data[$key]['id']=$value['id'];
			$data[$key]['pId']=$value['pId'];
			$data[$key]['name']=$value['name'];
		}
		$this->ajaxReturn(array('data'=>$data));
	}
    private function get_child($list,$pk='id',$pid='pid',$child='child',$root=0){
	    $tree=array();
	    $packData=array();
	    foreach ($list as  $data) {
	        $packData[$data[$pk]] = $data;
	    }
	    foreach ($packData as $key =>$val){     
	        if($val[$pid]==$root){//代表跟节点       
	            $tree[]=& $packData[$key];
	        }else{
	            //找到其父类
	            $packData[$val[$pid]][$child][]=& $packData[$key];
	        }
	    }
	    return $tree;
	}
}
