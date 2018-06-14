<<<<<<< Updated upstream
<?php

namespace Api\Controller;

use Common\Controller\ApibaseController;

class RoleController extends ApibaseController
{

    private $is_all = 0;
    private $mch_uid;
    private $app_auth;
    private $role_model;
    private $screen_auth;
    private $role_user_model;

    public function _initialize()
    {
        parent::_initialize();
        $this->checkLogin();
        $this->role_user_model = M('merchants_role_users');
        $this->role_model = M('merchants_role');
        $this->screen_auth = M('screen_auth');
        $this->app_auth = M('app_auth');

        $this->mch_uid = get_mch_uid($this->userId);
    }

    # 商户角色列表
    public function role_list()
    {
        if (IS_POST) {
            $data = $this->role_model->field('id as role_id,role_name,role_desc')->where("mu_id=$this->mch_uid")->select();
            if ($data) {
                succ_ajax($data);
            } else {
                succ_ajax(array());
            }
        }
    }

    # 添加角色
    public function role_add()
    {
        if (IS_POST) {
            $role_name = I('role_name');
            $role_desc = I('role_desc');
            if (!$role_name || !$role_desc) err('参数不能为空');
            $data['role_name'] = $role_name;
            $data['role_desc'] = $role_desc;
            $data['add_time'] = time();
            $data['pid'] = $this->role_user_model->where("uid=$this->mch_uid")->getField('role_id');
            $data['mu_id'] = $this->mch_uid;
            $id = $this->role_model->add($data);
            if ($id) {
                $info['role_id'] = "$id";
                succ_ajax($info);
            } else {
                err('新增失败');
            }
        }
    }
    # 添加角色
    public function role_edit()
    {
        if (IS_POST) {
            $role_id = I('role_id');
            $role_name = I('role_name');
            $role_desc = I('role_desc');
            if (!$role_name || !$role_desc || !$role_id) err('参数不能为空');
            $data['role_name'] = $role_name;
            $data['role_desc'] = $role_desc;
            $re = $this->role_model->where(array('id' => $role_id))->save($data);
            if ($re !== false) {
                succ_ajax();
            } else {
                err('修改失败');
            }
        }
    }

    # 删除角色
    public function role_delete()
    {
        if (IS_POST) {
            $role_id = I('role_id');
            if (!$role_id) err('请选择角色');
            if ($this->role_user_model->where(array('role_id' => $role_id))->find()) err('该角色已有员工,无法删除');
            if ($this->role_model->delete($role_id)) {
                succ_ajax();
            } else {
                err('删除失败');
            }
        }
    }

    private function get_app_auth()
    {
        if (IS_POST) {
            $role_id = I('role_id');
            if (!$role_id) err('请选择角色');
            $role = $this->role_user_model->where(array('uid' => $this->mch_uid))->getField('role_id');
            $data = $this->app_auth->field('id,auth_name,auth,pid')->where(array('role' => $role))->select();

            $app_auths = $this->role_model->where(array("id" => $role_id))->getField('app_auth');
            if($app_auths){
                $old_auth_arr = explode(";", $app_auths);
            } else {
                $old_auth_arr = false;
            }

            $auth_arr = M("nav")->where(array('parentid' => 0))->getField('module,href', true);
            foreach ($data as &$val) {
                $val['status'] = $this->get_status($val['auth'], $auth_arr, $old_auth_arr);
                unset($val['auth']);
            }
            $data = getTree($data, 0);
            foreach($data as &$value) {
                $subs = $value['subs'];
                if($subs && is_array($subs)){
                    $value['status'] = $this->get_tstatus($subs);
                }
            }
            succ_ajax($data);
        }
    }
    private function get_tstatus($arr)
    {
        $status = 0;
        foreach ($arr as $v) {
            $status += $v['status'];
        }
        return $status > 0 ? '1' : '0';
    }

    public function get_auth()
    {
        $type = I('type');
        switch ($type) {
            case 'app':
                $this->get_app_auth();
                break;
            case 'screen':
                $this->get_screen_auth();
                break;
            case 'pos':
                succ_ajax(array());
                break;
            default:
                err('未知设备');
                break;
        }
    }

    public function set_auth()
    {
        $type = I('type');
        if($_POST['role_id'] == '7'){
            err('默认角色禁止修改权限');
        }
        switch ($type) {
            case 'app':
                $this->set_app_auth();
                break;
            case 'screen':
                $this->set_screen_auth();
                break;
            case 'pos':
                succ_ajax();
                break;
            default:
                err('未知设备');
                break;
        }
    }

    public function set_app_auth()
    {
        if (IS_POST) {
            $auth_ids = I('auth_ids');
            $role_id = I('role_id');
            if (!$auth_ids) err('未选择权限');
            if (!$role_id) err('请选择角色');
            $auth = $this->app_auth->where(array('id' => array('in', $auth_ids)))->getField('auth', true);
            if (in_array('auth_bill')) $this->is_all = '1';
            //获取开通的所有权限
            if (!$auth) err('未选择权限');
            $auth_arr = M("nav")->where(array('module' => array('in', $auth)))->getField('href', true);
            $app_auth = implode(';', $auth_arr);

            $this->role_model->where(array("id" => $role_id))->save(array('app_auth' => $app_auth));
            $role_users = $this->role_user_model->where(array("role_id" => $role_id))->getField('uid', true);
            if ($role_users) {
                $res = M('merchants_users')->where(array("id" => array('IN', $role_users)))->save(array('auth' => $app_auth, 'is_all' => $this->is_all));
                if ($res !== false) {
                    succ_ajax();
                }
            } else {
                succ_ajax();
            }
            succ_ajax();

        }
    }

    private function get_screen_auth()
    {
        if (IS_POST) {
            $role_id = I('role_id');
            if (!$role_id) err('请选择角色');
            $data = $this->screen_auth->field('id,auth_name,pid')->where(array('status' => '1'))->select();
            $screen_auth = $this->role_model->where(array("id" => $role_id))->getField('screen_auth');
            $screen_auth = explode(',', $screen_auth);
            foreach ($data as &$val) {
                $val['status'] = in_array($val['id'], $screen_auth) ? '1' : '0';
            }
            $data = getTree($data, 0);
            succ_ajax($data);
        }
    }

    public function set_screen_auth()
    {
        if (IS_POST) {
            $screen_ids = I('auth_ids');
            $role_id = I('role_id');
            if (!$screen_ids) err('未选择权限');
            if (!$role_id) err('请选择角色');
            $this->role_model->where(array("id" => $role_id))->save(array('screen_auth' => $screen_ids));

            succ_ajax();
        }
    }

    private function get_status($auth, $auth_arr, $have_auth)
    {
        if(!$have_auth) return '0';
        if (in_array($auth_arr[$auth], $have_auth)) {
            return '1';
        }
        return '0';
    }

=======
<?php

namespace Api\Controller;

use Common\Controller\ApibaseController;

class RoleController extends ApibaseController
{

    private $is_all = 0;
    private $mch_uid;
    private $app_auth;
    private $role_model;
    private $screen_auth;
    private $role_user_model;

    public function _initialize()
    {
        parent::_initialize();
        $this->checkLogin();
        $this->role_user_model = M('merchants_role_users');
        $this->role_model = M('merchants_role');
        $this->screen_auth = M('screen_auth');
        $this->app_auth = M('app_auth');

        $this->mch_uid = get_mch_uid($this->userId);
    }

    # 商户角色列表
    public function role_list()
    {
        if (IS_POST) {
            $data = $this->role_model->field('id as role_id,role_name,role_desc')->where("mu_id=$this->mch_uid")->select();
            if ($data) {
                succ_ajax($data);
            } else {
                succ_ajax(array());
            }
        }
    }

    # 添加角色
    public function role_add()
    {
        if (IS_POST) {
            $role_name = I('role_name');
            $role_desc = I('role_desc');
            if (!$role_name || !$role_desc) err('参数不能为空');
            $data['role_name'] = $role_name;
            $data['role_desc'] = $role_desc;
            $data['add_time'] = time();
            $data['pid'] = $this->role_user_model->where("uid=$this->mch_uid")->getField('role_id');
            $data['mu_id'] = $this->mch_uid;
            $id = $this->role_model->add($data);
            if ($id) {
                $info['role_id'] = "$id";
                succ_ajax($info);
            } else {
                err('新增失败');
            }
        }
    }
    # 添加角色
    public function role_edit()
    {
        if (IS_POST) {
            $role_id = I('role_id');
            $role_name = I('role_name');
            $role_desc = I('role_desc');
            if (!$role_name || !$role_desc || !$role_id) err('参数不能为空');
            $data['role_name'] = $role_name;
            $data['role_desc'] = $role_desc;
            $re = $this->role_model->where(array('id' => $role_id))->save($data);
            if ($re !== false) {
                succ_ajax();
            } else {
                err('修改失败');
            }
        }
    }

    # 删除角色
    public function role_delete()
    {
        if (IS_POST) {
            $role_id = I('role_id');
            if (!$role_id) err('请选择角色');
            if ($this->role_user_model->where(array('role_id' => $role_id))->find()) err('该角色已有员工,无法删除');
            if ($this->role_model->delete($role_id)) {
                succ_ajax();
            } else {
                err('删除失败');
            }
        }
    }

    private function get_app_auth()
    {
        if (IS_POST) {
            $role_id = I('role_id');
            if (!$role_id) err('请选择角色');
            $role = $this->role_user_model->where(array('uid' => $this->mch_uid))->getField('role_id');
            $data = $this->app_auth->field('id,auth_name,auth,pid')->where(array('role' => $role))->select();

            $app_auths = $this->role_model->where(array("id" => $role_id))->getField('app_auth');
            if($app_auths){
                $old_auth_arr = explode(";", $app_auths);
            } else {
                $old_auth_arr = false;
            }

            $auth_arr = M("nav")->where(array('parentid' => 0))->getField('module,href', true);
            foreach ($data as &$val) {
                $val['status'] = $this->get_status($val['auth'], $auth_arr, $old_auth_arr);
                unset($val['auth']);
            }
            $data = getTree($data, 0);
            foreach($data as &$value) {
                $subs = $value['subs'];
                if($subs && is_array($subs)){
                    $value['status'] = $this->get_tstatus($subs);
                }
            }
            succ_ajax($data);
        }
    }
    private function get_tstatus($arr)
    {
        $status = 0;
        foreach ($arr as $v) {
            $status += $v['status'];
        }
        return $status > 0 ? '1' : '0';
    }

    public function get_auth()
    {
        $type = I('type');
        switch ($type) {
            case 'app':
                $this->get_app_auth();
                break;
            case 'screen':
                $this->get_screen_auth();
                break;
            case 'pos':
                succ_ajax(array());
                break;
            default:
                err('未知设备');
                break;
        }
    }

    public function set_auth()
    {
        $type = I('type');
        if($_POST['role_id'] == '7'){
            err('默认角色禁止修改权限');
        }
        switch ($type) {
            case 'app':
                $this->set_app_auth();
                break;
            case 'screen':
                $this->set_screen_auth();
                break;
            case 'pos':
                succ_ajax();
                break;
            default:
                err('未知设备');
                break;
        }
    }

    public function set_app_auth()
    {
        if (IS_POST) {
            $auth_ids = I('auth_ids');
            $role_id = I('role_id');
            if (!$auth_ids) err('未选择权限');
            if (!$role_id) err('请选择角色');
            $auth = $this->app_auth->where(array('id' => array('in', $auth_ids)))->getField('auth', true);
            if (in_array('auth_bill')) $this->is_all = '1';
            //获取开通的所有权限
            if (!$auth) err('未选择权限');
            $auth_arr = M("nav")->where(array('module' => array('in', $auth)))->getField('href', true);
            $app_auth = implode(';', $auth_arr);

            $this->role_model->where(array("id" => $role_id))->save(array('app_auth' => $app_auth));
            $role_users = $this->role_user_model->where(array("role_id" => $role_id))->getField('uid', true);
            if ($role_users) {
                $res = M('merchants_users')->where(array("id" => array('IN', $role_users)))->save(array('auth' => $app_auth, 'is_all' => $this->is_all));
                if ($res !== false) {
                    succ_ajax();
                }
            } else {
                succ_ajax();
            }
            succ_ajax();

        }
    }

    private function get_screen_auth()
    {
        if (IS_POST) {
            $role_id = I('role_id');
            if (!$role_id) err('请选择角色');
            $data = $this->screen_auth->field('id,auth_name,pid')->where(array('status' => '1'))->select();
            $screen_auth = $this->role_model->where(array("id" => $role_id))->getField('screen_auth');
            $screen_auth = explode(',', $screen_auth);
            foreach ($data as &$val) {
                $val['status'] = in_array($val['id'], $screen_auth) ? '1' : '0';
            }
            $data = getTree($data, 0);
            succ_ajax($data);
        }
    }

    public function set_screen_auth()
    {
        if (IS_POST) {
            $screen_ids = I('auth_ids');
            $role_id = I('role_id');
            if (!$screen_ids) err('未选择权限');
            if (!$role_id) err('请选择角色');
            $this->role_model->where(array("id" => $role_id))->save(array('screen_auth' => $screen_ids));

            succ_ajax();
        }
    }

    private function get_status($auth, $auth_arr, $have_auth)
    {
        if(!$have_auth) return '0';
        if (in_array($auth_arr[$auth], $have_auth)) {
            return '1';
        }
        return '0';
    }

>>>>>>> Stashed changes
}