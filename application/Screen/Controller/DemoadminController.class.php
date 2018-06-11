<?php
namespace Screen\Controller;
use Common\Controller\AdminbaseController;

class DemoadminController extends AdminbaseController{
	
	public function index(){
	    echo 213;
        exit;
		$this->display();
	}
}