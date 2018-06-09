<?php
namespace Xcx\Controller;

use Think\Controller;

class  ApibaseController extends  Controller
{
	
    function _initialize()
    {	
    		add_log();
			if(defined('UID')) return;
			$data[] = 'common';
			$data[] = 'zfb_notify_url';
			$data[] = 'wx_notify_url';
			if(in_array(ACTION_NAME,$data))return;
			if($token=I('token')){
							$value = M("token")->where(array("token" => $token))->getField("value");
							$value = json_decode($value,true);
							$uid = $value['uid'];
							$uid || err('token is wrong');
							
							//$role_id = M('merchants_role_users')->where(array('uid'=>$uid))->getField('role_id');
							define('ROLE_ID',$value['role_id']);
							define('ROLEID',$value['uid']);
							if($value['role_id'] != 3){
									if(ACTION_NAME == 'fahuo'){
											($value['auth_confirm_delivery'] == 1) || err('没有发货权限');
									}
									$uid = M('merchants_users')->where(array('id'=>$uid))->getField('pid');	
							}
							$agent_id = $uid;
							if($pid = M('merchants_users')->where(array('id'=>$agent_id))->getField('pid')){
										$agent_id = $pid;
							}	
							
							add_log($agent_id==$uid?0:$agent_id.' '.$uid);
							add_log();
			}
				
			define('AGENT_ID',$agent_id==$uid?0:$agent_id);
			define('UID',$uid);
			if(!UID){
		    			err('token is empty');
		    }
    }
}