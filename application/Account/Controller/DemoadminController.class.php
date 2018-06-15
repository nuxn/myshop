<?php
namespace Ad\Controller;
use Common\Controller\AdminbaseController;

class DemoadminController extends AdminbaseController{

    function _initialize() {
        parent::_initialize();
        $this->pay = M('pay');
        $this->merchants=M("merchants");
    }
	public function index(){
		$this->display();
	}
}