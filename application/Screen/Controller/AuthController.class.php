<?php

namespace Screen\Controller;

use Common\Controller\AdminbaseController;

class AuthController extends AdminbaseController
{

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $auth = M('screen_auth')->select();
        $arr = array();
        foreach ($auth as $v) {
            if($v['pid'] == 0){
                foreach ($auth as $va) {
                    if($v['id'] == $va['pid']){
                        $v['child'][] = $va;
                    }
                    $arr[$v['id']] = $v;
                }
            }
        }
        $pauth = M('screen_auth')->field('id,auth_name')->where('pid=0')->select();
        $this->assign('auth', $arr);
        $this->assign('pauth', $pauth);
        $this->display();
    }

    public function add_auth()
    {
        $input = I('');
        $auth_load = $input['module'] . '/' . $input['controller'] . '/' . $input['method'];
        $input['auth_load'] = strtolower($auth_load);
        $res = M('screen_auth')->add($input);
        if($res){
            $this->ajaxReturn(array('code' => '0000'));
        } else {
            $this->ajaxReturn(array('code' => '0001'));
        }
    }
}
