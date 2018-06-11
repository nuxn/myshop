<?php
namespace Xcx\Controller;

use Xcx\Controller\ApibaseController;
use Think\Controller;

class  BannerController extends  ApibaseController
{
    public function lists(){
 			$Banner = D('banner');
 			succ($Banner->lists(UID));
	}
	public function save(){
			($img = I('img'));
			($sort = I('sort')) || err('sort is empty');
			$Banner = D('banner');
			if($Banner->update(UID,$img,$sort)!==false){
					succ();
			}else{
					err($Banner->getError());
			}
	}
	
}