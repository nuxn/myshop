<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/24
 * Time: 18:24
 */

namespace CashCoupon\Controller;

use Common\Controller\AdminbaseController;
class CouponLogController extends AdminbaseController
{
    public $logModel;

    public function _initialize() {
        empty($_GET['upw']) ? "" : session("__SP_UPW__", $_GET['upw']);//设置后台登录加密码
        parent::_initialize();
        $this->initMenu();
        $this->logModel = M("user_cash");
    }
    //现金券领取、使用列表
    public function index()
    {
        $count = $this->logModel->count();
        $page = $this->page($count, 20);
        $this->assign("page", $page->show('Admin'));

        $this->logModel->order("id DESC");
        $this->logModel->limit($page->firstRow , $page->listRows);
        $data_lists = $this->logModel->select();
        $now = time();
        $this->assign("now",$now);
        $this->assign("data_lists", $data_lists);
        $this->display();
    }



}