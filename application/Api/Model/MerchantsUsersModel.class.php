<?php
/**
 * Created by PhpStorm.
 * User: Joan
 * Date: 2017/6/3
 * Time: 20:22
 */
namespace Api\Model;

use Think\Model;

/**用户模型
 * Class MerchantsUsersModel
 * @package Api\Model
 */
class MerchantsUsersModel extends Model
{
    public $user_model, $user_role_model, $role_model;

    public function _initialize()
    {
        $this->user_model = M("merchants_users");
        $this->user_role_model = M("merchants_role_users");
        $this->role_model = M("merchants_role");
    }


    /**
     * 获取商户所有角色
     * @param $userId
     * @return mixed
     */
    public function get_roles_list($userId)
    {
        $role_id = $this->user_role_model->where(array('uid'=>$userId))->getField('role_id');
        return $this->role_model->where(array('mu_id' => array('IN', (array($userId, 0))), 'pid' => $role_id))->field('id,role_name,role_desc')->select();
    }


    /**
     *
     * 获取所有员工列表
     * @param $userId //app用户ID，用户表主键
     * @param $role_id //当前登录用户角色ID
     * @param $roleId //接收的员工角色id
     * @param int $page //分页
     * @param int $per_page //每页数量
     * @return array
     *
     */
    public function get_all_employee($userId, $role_id, $roleId, $page = 0, $per_page = 20)
    {
        $return_arr = array('total' => 0, 'data' => '');
        //if (in_array($role_id, array(2, 3))) {
        if ($role_id == '2') {//代理商员工
            //$agent_role_ids = M('merchants_role')->where(array('mu_id'=>$userId))->getField('id',true);
            $agent_role_ids = M('merchants_role')->where('mu_id='.$userId.' or (pid=2 and mu_id=0)')->getField('id',true);
            $where = array("u.boss_id" => $userId, "ru.role_id" => array('IN',$agent_role_ids));
        } else {//商家员工
            if (!in_array($role_id, array(2, 3))) {
//                $res = $this->get_userOne($userId);
//                $userId = $res['pid'];
                $fid = M("employee")->where(array("uid" => $userId))->getField("employee_id");
                if ($fid) $where['e.fid'] = $fid;
            } else
                $where = array("u.boss_id" => $userId);
        }
        if ($roleId) $where['ru.role_id'] = $roleId;
        $where['u.status'] = '0';
        $this->user_model->alias("u")->where($where);
        $this->user_model->join("LEFT JOIN __MERCHANTS_ROLE_USERS__ ru ON u.id = ru.uid");
        $this->user_model->join("LEFT JOIN  __EMPLOYEE__ e ON u.id = e.uid");
        $count = $this->user_model->count();//总条数
        $return_arr['total'] = ceil($count / $per_page);//总页数

        $this->user_model
            ->alias("u")
            ->where($where)
            ->limit($page * $per_page, $per_page)
            ->order("u.id asc");
        $this->user_model->field('u.id,u.user_phone,u.user_name,ru.role_id');
        $this->user_model->join("LEFT JOIN __MERCHANTS_ROLE_USERS__ ru ON u.id = ru.uid");
        $this->user_model->join("LEFT JOIN  __EMPLOYEE__ e ON u.id = e.uid");
        $data_lists = $this->user_model->select();
        foreach ($data_lists as $k => $v) $data_lists[$k]['role_name'] = $this->get_role_name($v['role_id']);
        $return_arr['data'] = $data_lists;

        //}
        return $return_arr;
    }


    /**
     * 获取角色名称
     * @param $role_id
     * @return mixed
     */
    public function get_role_name($role_id)
    {
        $role_name = $this->role_model->where(array('id' => $role_id))->getField('role_name');
        if(is_null($role_name)) $role_name='';
        return $role_name;
    }

    public function get_userOne($uid = 0, $field = '*')
    {
        $res = $this->user_model->where(array('id' => $uid))->field($field)->find();
        return $res;

    }
}