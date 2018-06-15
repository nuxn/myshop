<?php
/**
 * Created by PhpStorm.
 * User: Joan
 * Date: 2017/3/23
 * Time: 17:06
 */

namespace Api\Controller;

use Common\Controller\ApibaseController;

/**代理商、商家员工管理
 * Class EmployeeController
 * @package Api\Controller
 */
class EmployeeController extends ApibaseController
{
    protected $user_model, $user_role_model;

    //流水账单
    protected $auth_bill_rules = array(
        'Api/Agentnews/coin',
        'Api/Agentnews/coin_detail',
        'Api/Agentnews/customer',
        'Api/Agentnews/customer_detail',
        'Api/Agentnews/merchant_detail',
        'Api/Shopnews/coin',
        'Api/Shopnews/coin_detail',
    );
    //报表记录
    protected $auth_report_rules = array(
        'Api/Agentnews/excel',
        'Api/Agentnews/excel_total',
        'Api/Agentnews/excel_number',
        'Api/Agentnews/excel_detail',
        'Api/Agentnews/excel_total_detail',
        'Api/Agentnews/excel_number_detail',
        'Api/Shopnews/excel',
        'Api/Shopnews/excel_total',
        'Api/Shopnews/excel_number',
        'Api/Shopnews/excel_detail',
        'Api/Shopnews/excel_total_detail',
        'Api/Shopnews/excel_number_detail',
    );

    public function _initialize()
    {
        parent::_initialize();
        $this->user_model = M("merchants_users");
        $this->user_role_model = M("merchants_role_users");
        $this->checkLogin();
    }

    /**
     * 员工列表；包括代理商员工，收银员
     */
    public function index()
    {
        $per_page = 20;
        $role_id = $this->userInfo['role_id'];
        $page = I("page", 0);
        if (in_array($role_id, array(2, 3))) {
            if ($role_id == '2') {//代理商员工
                $where = array("u.pid" => $this->userId, "ru.role_id" => 6);
            } else {//商家收银员
                $where = array("u.pid" => $this->userId, "ru.role_id" => 7);
            }
            $where['u.status'] = '0';
            $this->user_model->alias("u")->where($where);
            $this->user_model->join("LEFT JOIN __MERCHANTS_ROLE_USERS__ ru ON u.id = ru.uid");
            $count = $this->user_model->count();//总条数
            $total = ceil($count / $per_page);//总页数

            $this->user_model
                ->alias("u")
                ->where($where)
                ->limit($page * $per_page, $per_page)
                ->order("u.id DESC");
            $this->user_model->field('u.id,u.user_phone,u.user_name');
            $this->user_model->join("LEFT JOIN __MERCHANTS_ROLE_USERS__ ru ON u.id = ru.uid");
            $data_lists = $this->user_model->select();
            $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("total" => $total, "data" => $data_lists)));
        }
    }

    /**
     * 添加员工
     */
    public function add()
    {
        $role_id = $this->userInfo['role_id'];
        if (in_array($role_id, array(2, 3))) {
            $user_phone = I("user_phone");
            $user_name = I("user_name");

            if (!$user_phone || !$user_name) $this->ajaxReturn(array("code" => "error", "msg" => '手机号或姓名不能为空'));
            if (!isMobile($user_phone)) $this->ajaxReturn(array("code" => "error", "msg" => '手机号格式不正确'));
            $old_id = $this->user_model->where(array("user_phone" => $user_phone))->getField("id");
            if ($old_id) $this->ajaxReturn(array("code" => "error", "msg" => '该手机号已被占用'));
            $data = array(
                "user_phone" => $user_phone,
                "user_name" => $user_name,
                "add_time" => time(),
                "ip_address" => get_client_ip(),
                "pid" => $this->userId,
                "agent_id" => $this->userId,
                "status" => 0,
            );
            $user_role = 6;
            if ($role_id == '3') {//商家
                $data['user_pwd'] = md5(123456);
                $auth_bill = I("auth_bill");
                $auth_report = I("auth_report");

                $auth_arr = array();
                if ($auth_bill) $auth_arr = $this->auth_bill_rules;
                if ($auth_report) {
                    if ($auth_bill) $auth_arr = array_merge($auth_arr, $this->auth_report_rules);
                    else   $auth_arr = $this->auth_report_rules;
                }

                $user_role = 7;
                $data['auth'] = $auth_arr ? implode(';', $auth_arr) : 0;
                //获得商户的上级代理
                $pid = $this->user_model->where(array("id" => $this->userId))->getField("pid");
                $role_id = $this->user_role_model->where(array("uid" => $pid))->getField("role_id");
                if ($role_id == '2') $data['agent_id'] = $pid;
                else $data['agent_id'] = '0';
            }

            if ($res = $this->user_model->add($data)) {
                $this->user_role_model->add(array("uid" => $res, "role_id" => $user_role, "add_time" => time()));
                $this->ajaxReturn(array("code" => "success", "msg" => "成功"));
            } else
                $this->ajaxReturn(array("code" => "error", "msg" => '失败'));
        }


    }

    /**
     * 设置流水,报表权限
     */
    public function set()
    {
        $id = I("id");
        if ($this->userInfo['role_id'] == '3' && $id) {
            $auth = $this->user_model->where(array("id" => $id))->getField("auth");
            $auth_bill = I("auth_bill");
            $auth_report = I("auth_report");

            $auth_arr = array();
            $old_auth_arr = array();
            if ($auth_report == '1') {//开启
                if (!$auth) $auth_arr = $this->auth_report_rules;
                else {
                    $old_auth_arr = explode(";", $auth);
                    if (!in_array('Api/Agentnews/excel', $old_auth_arr)) $auth_arr = array_merge($this->auth_report_rules, $old_auth_arr);
                    else $auth_arr = $old_auth_arr;
                }
            } else if ($auth_report == '0') {//关闭
                if ($auth) {
                    $old_auth_arr = explode(";", $auth);
                    foreach ($old_auth_arr as $k => $v) {
                        if (in_array($v, $this->auth_report_rules)) unset($old_auth_arr[$k]);
                    }
                    $auth_arr = $old_auth_arr;

                }

            }

            if ($auth_bill == '1') {
                if (!$auth_arr) $auth_arr = $this->auth_bill_rules;
                else {
                    if (!in_array('Api/Agentnews/coin', $auth_arr)) $auth_arr = array_merge($this->auth_bill_rules, $auth_arr);
                }

            } else if ($auth_bill == '0') {//关闭
                if ($auth_arr) {
                    foreach ($auth_arr as $k => $v) {
                        if (in_array($v, $this->auth_bill_rules)) unset($auth_arr[$k]);
                    }

                }

            }
            
            $auth = $auth_arr ? implode(';', $auth_arr) : 0;
//            if ($auth) $auth .= ';' . $new_auth;
//            else $auth = $new_auth;
            $this->user_model->where(array("id" => $id))->save(array("auth" => $auth));

            $this->ajaxReturn(array("code" => "success", "msg" => "成功"));
        } else
            $this->ajaxReturn(array("code" => "error", "msg" => 'ID为空或角色无权限'));

    }

    /**
     * 修改
     */
    public function edit()
    {
        $user_name = I("user_name");
        $id = I("id");
        if (!$id || !$user_name) $this->ajaxReturn(array("code" => "error", "msg" => 'ID或姓名不能为空'));
        $this->user_model->where(array("id" => $id))->save(array("user_name" => $user_name));
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

        $user_info = M("token")->where(array("uid" => $id))->getField("value");
        $user_info = json_decode($user_info, true);

        if ($role_id == '7') {
            if (in_array('Api/Agentnews/coin', $old_auth_arr)) $info['auth_bile'] = '1';
            else  $info['auth_bile'] = '0';
            if (in_array('Api/Agentnews/excel', $old_auth_arr)) $info['auth_report'] = '1';
            else  $info['auth_report'] = '0';
        } else {
            $info['auth_report'] = '0';
            $info['auth_bile'] = '0';
        }

        $user_info['auth_report'] = $info['auth_report'];
        $user_info['auth_bile'] = $info['auth_bile'];
        M("token")->where(array("uid" => $id))->save(array("value" => json_encode($user_info)));

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
//            if ($this->user_role_model->where(array("uid" => $id))->delete()) {
//                $this->ajaxReturn(array("code" => "success", "msg" => "成功"));
//            }
            $this->ajaxReturn(array("code" => "success", "msg" => "成功"));
        }
        $this->ajaxReturn(array("code" => "error", "msg" => "失败"));
    }
}