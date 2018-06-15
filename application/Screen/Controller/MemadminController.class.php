<?php

namespace Screen\Controller;

use Common\Controller\AdminbaseController;
use Think\Page;

class MemadminController extends AdminbaseController
{

    public function index()
    {
        $model = M('screen_mem');

        $start_time = I("start_time");
        $end_time = I("end_time");
        if (strtotime($start_time) > strtotime($end_time)) {
            $this->error("开始时间不能大于结束时间");
        }
        if (!empty($start_time) && !empty($end_time)) {
            $map['add_time'] = array('between', array(strtotime($start_time), strtotime($end_time)));
            $this->assign('start_time', $start_time);
            $this->assign('end_time', $end_time);
        } else {
            if ($start_time) {
                $map['add_time'] = array('gt', strtotime($start_time));
                $this->assign('start_time', $start_time);
            }

            if ($end_time) {
                $map['add_time'] = array('lt', strtotime($end_time));
                $this->assign('end_time', $end_time);
            }
        }

        $openid = I("openid");
        if ($openid) {
            $map['openid'] = array('like', "%$openid%");
            $this->assign('openid', $openid);
        }

        $memphone = I("memphone");
        if ($memphone) {
            $map['memphone'] = array('like', "%$memphone%");
            $this->assign('memphone', $memphone);
        }

        $nickname = I("nickname");
        if ($nickname) {
            $map['nickname'] = array('like', "%$nickname%");
            $this->assign('nickname', $nickname);
        }
        $map['memimg'] = array('neq','');
        $map['delete'] = '0';
        $count = $model->where($map)->count();
        $page = $this->page($count, 20);
        $data = $model->where($map)->limit($page->firstRow, $page->listRows)->order("id desc")->select();
        $this->assign('data', $data);
        $this->assign("page", $page->show('Admin'));
        $this->display();
    }
}