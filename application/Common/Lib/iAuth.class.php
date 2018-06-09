<?php
// +---------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +---------------------------------------------------------------------
// | Copyright (c) 2013-2014 http://www.thinkcmf.com All rights reserved.
// +---------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +---------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +---------------------------------------------------------------------

namespace Common\Lib;

/**
 * ThinkCMF权限认证类
 */
class iAuth
{

    //默认配置
    protected $_config = array();

    public function __construct()
    {
    }

    /**
     * 检查权限
     * @param name string|array  需要验证的规则列表,支持逗号分隔的权限规则或索引数组
     * @param uid  int           认证用户的id
     * @param relation string    如果为 'or' 表示满足任一条规则即通过验证;如果为 'and'则表示需满足所有规则才能通过验证
     * @return boolean           通过验证返回true;失败返回false
     */
    public function check($uid, $name, $relation = 'or')
    {
        if (empty($uid)) {
            return false;
        }
        if ($uid == 1) {
            return true;
        }
        if (is_string($name)) {
            $name = strtolower($name);
            if (strpos($name, ',') !== false) {
                $name = explode(',', $name);
            } else {
                $name = array($name);
            }
        }
        $list = array(); //保存验证通过的规则名

        $role_user_model = M("RoleUser");

        $role_user_join = '__ROLE__ as b on a.role_id =b.id';

        $groups = $role_user_model->alias("a")->join($role_user_join)->where(array("user_id" => $uid, "status" => 1))->getField("role_id", true);

        if (in_array(1, $groups)||in_array(6, $groups)) {
            return true;
        }

        if (empty($groups)) {
            return false;
        }

        $auth_access_model = M("AuthAccess");

        $join = '__AUTH_RULE__ as b on a.rule_name =b.name';

        $rules = $auth_access_model->alias("a")->join($join)->where(array("a.role_id" => array("in", $groups), "b.name" => array("in", $name)))->select();

        foreach ($rules as $rule) {
            if (!empty($rule['condition'])) { //根据condition进行验证
                $user = $this->getUserInfo($uid);//获取用户信息,一维数组

                $command = preg_replace('/\{(\w*?)\}/', '$user[\'\\1\']', $rule['condition']);
                //dump($command);//debug
                @(eval('$condition=(' . $command . ');'));
                if ($condition) {
                    $list[] = strtolower($rule['name']);
                }
            } else {
                $list[] = strtolower($rule['name']);
            }
        }

        if ($relation == 'or' and !empty($list)) {
            return true;
        }

        $diff = array_diff($name, $list);
        if ($relation == 'and' and empty($diff)) {
            return true;
        }
        return false;
    }

    /**
     * 检查app接口权限
     * @param name string|array  需要验证的规则列表,支持逗号分隔的权限规则或索引数组
     * @param uid  int           认证用户的id
     * @param relation string    如果为 'or' 表示满足任一条规则即通过验证;如果为 'and'则表示需满足所有规则才能通过验证
     * @return boolean           通过验证返回true;失败返回false
     */
    public function check_auth($uid, $name)
    {
        if (empty($uid)) {
            return false;
        }

        if (is_string($name)) {
            //$name = strtolower($name);
            if (strpos($name, ',') !== false) {
                $name = explode(',', $name);
            } else {
                $name = array($name);
            }
        }

        $where['status'] = 1;
        $where['href'] = array('like', "%$name[0]%");//判断是否需要验证权限
        if (!M("nav")->where($where)->find()) return true;
        $role_user_model = M("merchants_role_users");

        $role_user_join = '__MERCHANTS_ROLE__ as b on a.role_id =b.id';

        $groups = $role_user_model->alias("a")->join($role_user_join)->where(array("a.uid" => $uid))->getField("role_id", true);

        if (in_array(1, $groups)) {
            return true;
        }
        if (in_array($groups[0], array(1, 2, 3))) {
            return true;
        }

        if (empty($groups)) {
            return false;
        }

        $user_model = M("merchants_users");

        $join = '__MERCHANTS_ROLE_USERS__ as b on a.id =b.uid';

        $rules = $user_model->alias("a")->join($join)->where(array("b.uid" => $uid, "b.role_id" => array("in", $groups)))->getField("a.auth");

        if (!$rules) return false;

        $list = explode(";", $rules);//保存验证通过的规则名

        foreach ($name as $action) if (!in_array($action, $list)) return false;

        return true;
    }

    /**
     * 获得用户资料
     */
    private function getUserInfo($uid)
    {
        static $userinfo = array();
        if (!isset($userinfo[$uid])) {
            $userinfo[$uid] = M("Users")->where(array('id' => $uid))->find();
        }
        return $userinfo[$uid];
    }

}
