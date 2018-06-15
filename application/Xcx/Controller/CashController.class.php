<?php
namespace Xcx\Controller;

use Xcx\Controller\ApibaseController;
use Think\Controller;

class  CashController extends  ApibaseController
{
    public function lists(){
 				($status = I('status',0,'intval')) || err('status is empty');
 				($page = I('page',0)) || err('page is empty');
 				$data = D('UserCash')->_lists(UID,$status,$page);
 				succ($data);
 				
	}
	public function save(){
		
	}
}