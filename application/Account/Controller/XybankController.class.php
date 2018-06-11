<?php

namespace Account\Controller;

use Common\Controller\AdminbaseController;

class XybankController extends AdminbaseController
{

    protected $bill;
    protected $everbill;

    function _initialize()
    {
        parent::_initialize();
        $this->bill = M("bill_xy");
        $this->everbill = M('bill_xy_count');
    }

    public function index()
    {
        $start_time = strtotime(I('start_time'));
        $end_time = strtotime(I('end_time'));
        $mch_id = trim(I('mch_id'));
        $merchant_name = trim(I('merchant_name'));
        $order_sn = trim(I('order_sn'));

        if ($mch_id) {
            $map['mch_id'] = $mch_id;
        }
        if ($order_sn) {
            $map['mch_order_sn'] = "$order_sn";
        }
        if ($merchant_name) {
            $map['mch_name'] = array('LIKE', "%$merchant_name%");
        }
        if ($start_time && $end_time) {
            $map['bill_time'] = array(array('EGT', $start_time), array('ELT', $end_time));
        }

        $count = $this->bill->where($map)->count();
        $page = $this->page($count, 20);

        $bills = $this->bill
            ->where($map)
            ->limit($page->firstRow, $page->listRows)
            ->order("bill_time desc")
            ->select();
        $this->assign("formget", array_merge($_GET, $_POST));
        $this->assign("bills", $bills);
        $this->assign("page", $page->show('Admin'));
        $this->display();
    }

    /**
     * 每天的汇总
     */
    public function day()
    {
        $bill_date = I("bill_date");
        if ($bill_date) {
            $map['bill_date'] = $bill_date;
        }
        $countbills = $this->everbill
            ->where($map)
            ->order('id desc')
            ->select();

        $count = count($countbills);
        $page = $this->page($count, 20);
        $list = array_slice($countbills, $page->firstRow, $page->listRows);
        $this->assign("page", $page->show('Admin'));
        $this->assign("formget", array_merge($_GET, $_POST));
        $this->assign("bills", $list);
        $this->display();
    }

}