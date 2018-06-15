<?php
/**
 * Created by PhpStorm.
 * User: Joan
 * Date: 2017/3/27
 * Time: 15:32
 */

namespace Api\Model;

use Think\Model;

class MerchantsAgentModel extends Model
{
    protected $users_model, $agent_model, $merchants_model;

    public function _initialize()
    {
        $this->users_model = M("merchants_users");
        $this->merchants_model = M("merchants");
        $this->agent_model = M("merchants_agent");
    }

    /**获取代理信息
     * @param int $uid
     * @return mixed
     */
    public function getAgentInfo($uid = 0)
    {
        $this->agent_model->alias("a")->where(array("uid" => $uid));
        $this->agent_model->field('a.id,agent_name,referrer,if(agent_mode=0,"个人","企业")agent_mode,province,city,county,address,a.add_time,u.user_phone');
        $this->agent_model->join("LEFT JOIN __MERCHANTS_USERS__ u ON a.uid = u.id");
        $res = $this->agent_model->find();

        $uid = $this->users_model->where(array("user_name" => $res['referrer']))->getField('id');
        $role_id = M("merchants_role_users")->where(array("uid" => $uid))->getField('role_id');
        if (in_array($role_id, array(1, 2, 3))) $res['referrer'] = '';
      
        return $res;
    }

    /**获取商户信息
     * @param int $uid
     * @return mixed
     */
    public function getMerchantsInfo($uid = 0)
    {
        $this->merchants_model->alias("m")->where(array("m.uid" => $uid));
        $this->merchants_model->field('referrer,merchant_name,m.id,if(account_type=0,"个人","企业")account_type,province,city,county,address,industry,u.user_name,ru.role_id,ru.uid');
        $this->merchants_model->join("LEFT JOIN __MERCHANTS_USERS__ u ON m.referrer = u.user_phone");
        $this->merchants_model->join("LEFT JOIN __MERCHANTS_ROLE_USERS__ ru ON u.id = ru.uid");
        $res = $this->merchants_model->find();
      
        //判断推荐人是否是员工
        if (!in_array($res['role_id'], array(1, 2, 3))) {
            $res['referrer_contact'] = $res['referrer']?$res['referrer']:'';//业务联系
            $res['referrer'] = $res['user_name'] ? $res['user_name'] : $res['referrer_contact'];//推荐人

            $pid = $this->users_model->where(array("id" => $res['uid']))->getField('pid');//推荐员工父级
            $agent_name = $this->agent_model->where(array("uid" => $pid))->getField('agent_name');
            $res['contracted_agents'] = $agent_name ? $agent_name : '';//签约代理
        } else {
            $res['referrer'] = '';
            if ($res['role_id'] == '2') {
                $agentInfo = $this->agent_model->where(array("uid" => $res['uid']))->find();;
                $res['contracted_agents'] = $agentInfo['agent_name']?$agentInfo['agent_name']:'';
            } else
                $res['contracted_agents'] = '';
        }
        unset($res['uid']);
        unset($res['role_id']);
        unset($res['user_name']);
        return $res;
    }
}
