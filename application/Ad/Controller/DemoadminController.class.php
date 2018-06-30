<?php
namespace Ad\Controller;
use Common\Controller\AdminbaseController;
use Common\Lib\Subtable;

class DemoadminController extends AdminbaseController{

    function _initialize() {
        parent::_initialize();
        $this->pay = M(Subtable::getSubTableName('pay'));
        $this->merchants=M("merchants");
    }
	public function index(){
		$this->display();
	}
}