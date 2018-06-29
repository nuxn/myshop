<?php
/**
 * Created by PhpStorm.
 * User: Joan
 * Date: 2017/3/23
 * Time: 17:06
 */

namespace Api\Controller;

use Common\Controller\ApibaseController;
use Think\Controller;
/**代理商、商家员工管理
 * Class EmployeeController
 * @package Api\Controller
 */
class EmployeeController extends ApibaseController
{
    protected $user_model, $user_role_model, $role_model;


    public function _initialize()
    {
        parent::_initialize();
        $this->user_model = M("merchants_users");
        $this->user_role_model = M("merchants_role_users");
        $this->role_model = M("merchants_role");
        $this->checkLogin();
    }

    /**
     * 员工列表；包括代理商员工，收银员
     */
    public function index()
    {
        $role_id = $this->userInfo['role_id'];
        $page = I("page", 0);
        $roleId = I("role_id", 0);//员工角色
        if ($this->userInfo['auth_add_cashier'] == '1' || in_array($role_id, array(2, 3))) $lists = D("MerchantsUsers")->get_all_employee($this->userId, $role_id, $roleId, $page, 2000);
        else $lists = '';
        $roles_list = D("MerchantsUsers")->get_roles_list($this->userId);
        $this->ajaxReturn(array(
            "code" => "success",
            "msg" => "成功",
            "data" => array(
                "total" => strval($lists['total']),
                "data" => $lists['data'] ? $lists['data'] : array(),
                "roles_list" => $roles_list ? $roles_list : array())
        ));
    }

    /**
     * 添加员工
     */
    public function add()
    {
        $role_id = $this->userInfo['role_id'];
        if (in_array($role_id, array(2, 3)) || $this->userInfo['auth_add_cashier'] == '1') {
            $user_phone = I("user_phone");
            $user_name = I("user_name");

            if (!$user_phone || !$user_name) $this->ajaxReturn(array("code" => "error", "msg" => '手机号或姓名不能为空'));
            if (!isMobile($user_phone)) $this->ajaxReturn(array("code" => "error", "msg" => '手机号格式不正确'));

            $old_info = $this->user_model->where(array("user_phone" => $user_phone))->field("id,status")->find();
            if ($old_info) {
                if ($old_info['status'] == '0') $this->ajaxReturn(array("code" => "error", "msg" => '该手机号已被占用'));
                else if ($old_info['status'] == '1') $this->ajaxReturn(array("code" => "error", "msg" => '该手机号已被禁用'));
                else   $this->user_model->where(array("id" => $old_info['id']))->delete();
            }
            $data = array(
                "user_phone" => $user_phone,
                "user_name" => $user_name,
                "add_time" => time(),
                "ip_address" => get_client_ip(),
                "pid" => $this->userId,
                "boss_id" => $this->userId,
                "agent_id" => $role_id==2 ? $this->userId : $this->user_model->where(array('id'=>$this->userId))->getField('agent_id'),
                "status" => 0,
                "is_employee" => 1,
                "user_pwd" => I('pwd') ? md5(I('pwd')) : md5(123456)
            );

            $user_role = I("role_id") ? I("role_id") : 7;
            $param = I("");
            $data['auth'] = M('merchants_role')->where(array('id'=>$user_role))->getField('app_auth');
            $data['is_employee'] = 1;
            if ($role_id == '3') {//商家
                //获得商户的上级代理
                $pid = $this->user_model->where(array("id" => $this->userId))->getField("pid");
                $role_id = $this->user_role_model->where(array("uid" => $pid))->getField("role_id");
                $data['agent_id'] = ($role_id == '2') ? $pid : 0;
            } else if ($role_id == '2') {//代理商
//                $user_role = 6;
            } else {//其他角色
                $data['agent_id'] = $this->user_model->where(array("id" => $this->userId))->getField("agent_id");
                $data['pid'] = $this->user_model->where(array("id" => $this->userId))->getField("pid");
            }

            if ($res = $this->user_model->add($data)) {
                // 加入后台用户表 ypt_users
                $this->addUsers($res, $data);
                $this->user_role_model->add(array("uid" => $res, "role_id" => $user_role, "add_time" => time()));
                $employeeModel=M('employee');
                $fid=$employeeModel->where(array("uid" => $this->userId))->getField("employee_id");
                if(!$fid)$fid=0;
                $employeeModel->add(array('uid'=>$res,'fid'=>$fid,'add_time'=>time(),'update_time'=>time()));
                if (isset($param['auth_bill_single']) && $param['auth_bill_single'] == '1') $this->user_model->where(array('id' => $res))->save(array('is_all' => 0));
                $this->write_log('添加员工'.$user_name,$res);
                $this->ajaxReturn(array("code" => "success", "msg" => "添加成功"));
            } else
                $this->ajaxReturn(array("code" => "error", "msg" => '添加失败'));
        }


    }

    public function addUsers($mu_id, $input)
    {
        $data['user_login'] = $input['user_phone'];
        $data['mobile'] = $input['user_phone'];
        $data['user_pass'] = '';
        $data['user_nicename'] = $input['user_name'];
        $data['create_time'] = time();
        $data['pid'] = $this->userId;
        $data['boss_id'] = $this->userId;
        $data['platform'] = '2';
        $data['muid'] = $mu_id;
        $uid = M('users')->add($data);
        $role_id = M('role_user')->join('ru left join ypt_users u on ru.user_id=u.id')->where(array('u.muid'=>$this->userId))->getField('ru.role_id');
        if($uid){
            $role_data['role_id'] = $role_id;
            $role_data['user_id'] = $uid;
            M('role_user')->add($role_data);
            return $uid;
        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => '添加失败'));
        }
    }


    /**
     * 设置流水,报表权限
     */
    public function set()
    {
        if (($this->userInfo['role_id'] == '3' && $id = I("id")) || $this->userInfo['auth_add_cashier'] == '1') {
            $param = I("");
            $auth_str = $this->get_module_auth($param);
            //权限数组转为字符串保存用户表
            if ($this->user_model->where(array("id" => $param['id']))->save(array('auth' => $auth_str))) $this->update_token_auth($param['id'], $param);
            $is_all = $param['auth_bill_single'] == '1' ? 0 : 1;
            $this->user_model->where(array('id' => $param['id']))->save(array('is_all' => $is_all));
            $this->ajaxReturn(array("code" => "success", "msg" => "设置权限成功"));
        } else
            $this->ajaxReturn(array("code" => "error", "msg" => 'ID为空或角色无权限'));

    }

    /**
     *
     * 获取开启的对应模块权限
     * @param array $param
     * @return mixed
     */
    public function get_module_auth($param = array())
    {
        $map = array();//存储开通的权限
        if ($param['auth_report']) $map[] = 'auth_report';//报表
        if ($param['auth_add_cashier']) $map[] = 'auth_add_cashier';//添加收银员
        if ($param['auth_bill']) $map[] = 'auth_bill';    //所有流水
        if ($param['auth_goods']) $map[] = 'auth_goods';  //商品
        if ($param['auth_member']) $map[] = 'auth_member';//会员
        if ($param['auth_coupon']) $map[] = 'auth_coupon';//卡券
        if ($param['auth_confirm_delivery']) $map[] = 'auth_confirm_delivery';//确认发货
        if ($param['auth_bill_single']) $map[] = 'auth_bill_single'; //单个流水
        if ($param['auth_taiqian']) $map[] = 'auth_taiqian';//台签
        if ($param['auth_shoukuan']) $map[] = 'auth_shoukuan';//收款
        if ($param['auth_pay_back']) $map[] = 'auth_pay_back';//退款
        if ($param['auth_dz']) $map[] = 'auth_dz';//到账查询
        if ($param['auth_dyj']) $map[] = 'auth_dyj';//打印机
        if ($param['auth_add_merchant']) $map[] = 'auth_add_merchant';//添加商户
        //获取开通的所有权限
        if (!$map) return '';
        $auth_arr = M("nav")->where(array('module' => array('in', $map)))->getField('href', true);
        return implode(';', $auth_arr);
    }

    /**
     * 更新token权限
     * @param $uid
     * @param array $param
     */
    public function update_token_auth($uid, $param = array())
    {
        $user_info = M("token")->where(array("uid" => $uid))->getField("value");
        $user_info = json_decode($user_info, true);
        $user_info['auth_report'] = $param['auth_report'];
        $user_info['auth_add_cashier'] = $param['auth_add_cashier'];
        $user_info['auth_bill'] = $param['auth_bill'];
        $user_info['auth_goods'] = $param['auth_goods'];
        $user_info['auth_member'] = $param['auth_member'];
        $user_info['auth_coupon'] = $param['auth_coupon'];
        $user_info['auth_confirm_delivery'] = $param['auth_confirm_delivery'];
        $user_info['auth_bill_single'] = $param['auth_bill_single'];
        $user_info['auth_taiqian'] = $param['auth_taiqian'];
        $user_info['auth_shoukuan'] = $param['auth_shoukuan'];
        $user_info['auth_pay_back'] = $param['auth_pay_back'];
        $user_info['auth_dz'] = $param['auth_dz'];
        $user_info['auth_dyj'] = $param['auth_dyj'];
        $user_info['auth_add_merchant'] = $param['auth_add_merchant'];
        M("token")->where(array("uid" => $uid))->save(array("value" => json_encode($user_info)));
    }

    /**
     * 修改
     */
    public function edit()
    {
        $user_name = I("user_name");
        $id = I("id");
        if (!$id || !$user_name) $this->ajaxReturn(array("code" => "error", "msg" => 'ID或姓名不能为空'));
        $save['user_name'] = $user_name;
        if(I('role_id')){
            $this->user_role_model->where(array("uid" => $id))->save(array('role_id'=>I('role_id')));
        }
        $this->write_log('编辑员工'.$user_name,$id);
        $this->user_model->where(array("id" => $id))->save($save);
        $this->ajaxReturn(array("code" => "success", "msg" => "成功"));
    }


    /**
     * 员工资料
     */
    public function detail()
    {
        $id = I("id");
        if (!$id) $this->ajaxReturn(array("code" => "error", "msg" => 'id不能为空'));
        $info = $this->user_model->where(array("id" => $id))->field("id,user_phone,user_name,auth")->find();
        $old_auth_arr = explode(";", $info['auth']);

        $role_id = $this->user_role_model->where(array("uid" => $id))->getField("role_id");

        $auth_arr = M("nav")->where(array('parentid' => 0))->getField('module,href', true);

        foreach ($auth_arr as $k => $v) $info[$k] = in_array($v, $old_auth_arr) ? '1' : '0';
        unset($info['auth']);
        $role_name = D("MerchantsUsers")->get_role_name($role_id);
        $info['role_name'] = $role_name ? : '';
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array($info)));
    }

    /**
     * 删除员工
     */
    public function del()
    {
        $id = I("id");
        if (!$id) $this->ajaxReturn(array("code" => "error", "msg" => 'id不能为空'));
        if ($this->user_model->where(array("id" => $id))->save(array("status" => '-1'))) {
            M("token")->where(array("uid" => $id))->delete();
            $user_name=$this->user_model->where(array('id'=>$id))->getfield('user_name');
            $this->write_log('删除员工'.$user_name,$id);
            $this->ajaxReturn(array("code" => "success", "msg" => "成功"));
        }
        $this->ajaxReturn(array("code" => "error", "msg" => "失败"));
    }


    /**
     * 角色列表
     */
    public function roles_list()
    {
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => D("MerchantsUsers")->get_roles_list($this->userId)));
    }

    /**
     * 角色添加
     */
    public function roles_add()
    {
        ($role_name = I('role_name','','strip_tags')) || err('角色名称不能为空');
        ($role_desc = I('role_desc')) || err('角色介绍不能为空');
        if (mb_strlen($role_name,'UTF8') > '10') err('角色名称不能超过10个字');
        if (mb_strlen($role_desc,'UTF8') > '20') err('角色介绍不能超过20个字');
        if (in_array($role_name, array('店长', '收银员', '财务', '营业员'))) err('要添加的角色已存在!');
        if ($this->role_model->where(array('role_name' => $role_name, 'mu_id' => $this->userId))->getField('id')) err('要添加的角色已存在!');
        $roles_arr = array(
            'role_name' => $role_name,
            'role_desc' => $role_desc,
            'add_time' => time(),
            'pid' => '3',
            'mu_id' => $this->userId
        );
        ($id = $this->role_model->add($roles_arr)) ? succ(array('id' => $id), '角色添加成功') : err('角色添加失败');
    }
}