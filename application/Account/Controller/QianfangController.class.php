<?php

namespace Account\Controller;

use Common\Controller\AdminbaseController;

/**
 * 钱方交易流水
 * BY LXL
 * Class QianfangController
 * @package Account\Controller
 */
class QianfangController extends AdminbaseController
{

    protected $bill;

    function _initialize()
    {
        parent::_initialize();
        $this->bill = M("bill_qf");
    }

    public function index()
    {
        $start_time = strtotime(I('start_time'));
        $end_time = strtotime(I('end_time'));
        $name = trim(I('name'));
        $syssn = trim(I('syssn'));
        $out_trade_no = trim(I('out_trade_no'));

        if ($out_trade_no) {
            $map['out_trade_no'] = "$out_trade_no";
        }
        if ($syssn) {
            $map['syssn'] = "$syssn";
        }
        if ($name) {
            $map['name'] = array('LIKE', "%$name%");
        }
        if ($start_time && $end_time) {
            $map['sysdtm'] = array(array('EGT', $start_time), array('ELT', $end_time));
        }

        $count = $this->bill
            ->where($map)
            ->count();
        $page = $this->page($count, 20);

        $bills = $this->bill
            ->where($map)
            ->limit($page->firstRow, $page->listRows)
            ->order("id desc")
            ->select();
        $this->assign("formget", array_merge($_GET, $_POST));
        $this->assign("bills", $bills);
        $this->assign("page", $page->show('Admin'));
        $this->display();
    }


    /**
     * 每天的汇总
     */
    public function count()
    {

    }

}