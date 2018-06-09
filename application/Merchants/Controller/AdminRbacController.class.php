<?php
/**
 * Created by PhpStorm.
 * User: Joan
 * Date: 2017/3/1
 * Time: 20:32
 */

namespace Merchants\Controller;

use Common\Controller\AdminbaseController;

/**前台用户权限
 * Class AdminRbacController
 * @package Merchants\Controller
 */
class AdminRbacController extends AdminbaseController
{

    protected $role_model, $menu_model, $user_model;

    public function _initialize()
    {
        parent::_initialize();
        $this->role_model = M("merchants_role");
        $this->menu_model = M("Nav");
        $this->user_model = M("merchants_users u");
    }


    // 角色授权列表
    public function authorize()
    {
        //角色ID
        $roleid = I("get.id", 0, 'intval');
        $uid = I("get.uid", 0, 'intval');
        if (empty($roleid)) {
            $this->error("参数错误！");
        }
        import("Tree");
        $menu = new \Tree();
        $menu->icon = array('│ ', '├─ ', '└─ ');
        $menu->nbsp = '&nbsp;&nbsp;&nbsp;';
        $result = $this->menu_model->field('id,parentid,label name,href')->select();

        $newmenus = array();
        $priv_data = $this->role_model->where(array("id" => $roleid))->getField("auth", true);//获取权限表数据

        if ($priv_data[0]) $priv_data = explode(';', $priv_data[0]);

        foreach ($result as $m) {
            $newmenus[$m['id']] = $m;
        }

        foreach ($result as $n => $t) {
            $result[$n]['checked'] = ($this->_is_checked($t, $roleid, $priv_data)) ? ' checked' : '';
            $result[$n]['level'] = $this->_get_level($t['id'], $newmenus);
            $result[$n]['style'] = empty($t['parentid']) ? '' : 'display:none;';
            $result[$n]['parentid_node'] = ($t['parentid']) ? ' class="child-of-node-' . $t['parentid'] . '"' : '';
        }

        $str = "<tr id='node-\$id' \$parentid_node  style='\$style'>
                   <td style='padding-left:30px;'>\$spacer<input type='checkbox' name='menuid[]' value='\$id' level='\$level' \$checked onclick='javascript:checknode(this);'> \$name</td>
    			</tr>";

        $menu->init($result);
        $categorys = $menu->get_tree(0, $str);
        $this->assign("categorys", $categorys);
        $this->assign("roleid", $roleid);
        $this->display();
    }

    //角色授权提交
    public function authorize_post()
    {
        if (IS_POST) {
            $roleid = I("post.roleid", 0, 'intval');
            if (!$roleid) {
                $this->error("需要授权的角色不存在！");
            }

            if (is_array($_POST['menuid']) && count($_POST['menuid']) > 0) {
                $str = $this->_get_action($_POST['menuid']);
                //获取当前角色下面的所有用户
                $list = M("merchants_role_users", "ypt_")->where(array("role_id" => $roleid))->getField('uid', true);
                $uid_arr = implode(',', $list);
                $condition['_string'] = 'FIND_IN_SET(id,"' . $uid_arr . '")';
                //更改角色下面的权限
                $this->role_model->where(array("id" => $roleid))->save(array("auth" => $str));
                $this->user_model->where(array($condition))->save(array("auth" => $str));
                $this->success("授权成功！", U("AdminRole/index"));
            } else {
                $this->error("没有接收到数据，执行清除授权成功！");
            }
        }
    }

    //用户授权列表
    public function user_authorize()
    {

        if (IS_POST) {
            $uid = I("post.uid", 0, 'intval');
            if (!$uid) {
                $this->error("需要授权的用户不存在！");
            }

            if (is_array($_POST['menuid']) && count($_POST['menuid']) > 0) {

                $str = $this->_get_action($_POST['menuid']);
                //更改用户权限
                $this->user_model->where(array("id" => $uid))->save(array("auth" => $str));
                $this->success("授权成功！", U("AdminUser/index"));
            } else {
                $this->error("没有接收到数据，执行清除授权成功！");
            }
        } else {
            //用户ID
            $uid = I("get.id", 0, 'intval');
            if (empty($uid)) {
                $this->error("参数错误！");
            }
            import("Tree");
            $menu = new \Tree();
            $menu->icon = array('│ ', '├─ ', '└─ ');
            $menu->nbsp = '&nbsp;&nbsp;&nbsp;';

            $result = M("nav")->field('id,parentid,label name,href')->select();

            $newmenus = array();
            $priv_data = $this->_get_user_auth($uid);

            foreach ($result as $m) {
                $newmenus[$m['id']] = $m;
            }

            foreach ($result as $n => $t) {
                $result[$n]['checked'] = ($this->_is_checked($t, $uid, $priv_data)) ? ' checked' : '';
                $result[$n]['level'] = $this->_get_level($t['id'], $newmenus);
                $result[$n]['style'] = empty($t['parentid']) ? '' : 'display:none;';
                $result[$n]['parentid_node'] = ($t['parentid']) ? ' class="child-of-node-' . $t['parentid'] . '"' : '';
            }

            $str = "<tr id='node-\$id' \$parentid_node  style='\$style'>
                   <td style='padding-left:30px;'>\$spacer<input type='checkbox' name='menuid[]' value='\$id' level='\$level' \$checked onclick='javascript:checknode(this);'> \$name</td>
    			</tr>";
            $menu->init($result);

            $categorys = $menu->get_tree(0, $str);

            $this->assign("categorys", $categorys);

            $this->assign("uid", $uid);
            $this->display();
        }

    }

    /**
     *  检查指定菜单是否有权限
     * @param array $menu menu表中数组
     * @param int $roleid 需要检查的角色ID
     */
    private function _is_checked($menu, $roleid, $priv_data)
    {
        $name = $menu['href'];
        $name = strtolower("$name");

        if ($priv_data) {
            if (in_array($name, $priv_data)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }

    }

    /**获取菜单对应的控制器方法
     * @param $menu_arr
     * @return string
     */
    private function _get_action($menu_arr)
    {
        $str = '';
        //获取菜单对应的控制器方法
        foreach ($menu_arr as $menuid) {
            $menu = $this->menu_model->where(array("id" => $menuid))->field("href")->find();
            if ($menu) {
                $href = $menu['href'];
                $name = strtolower("$href");
                if ($str) $str .= ';' . $name;
                else $str .= $name;
            }
        }
        return $str;
    }

    /**获取前台用户已有权限
     * @param $uid
     * @return array 返回前台用户权限
     */
    private function _get_user_auth($uid)
    {
        $auth_arr = array();
        if (!$uid) return $auth_arr;
//        $this->user_model->join("__MERCHANTS_ROLE_USERS__ ru on u.id=ru.uid");
//        $this->user_model->join("__MERCHANTS_ROLE__ r on ru.role_id=r.id");
//        $this->user_model->where(array("u.id" => $uid))->field("concat(u.auth,';',r.auth)auth");
        $this->user_model->where(array("u.id" => $uid))->field("u.auth");
        $priv_data = $this->user_model->find();

        if ($priv_data['auth']) {
            $auth_arr = explode(';', $priv_data['auth']);
            return array_unique($auth_arr);
        } else
            return $auth_arr;

    }

    /**
     * 获取菜单深度
     * @param $id
     * @param $array
     * @param $i
     */
    protected function _get_level($id, $array = array(), $i = 0)
    {

        if ($array[$id]['parentid'] == 0 || empty($array[$array[$id]['parentid']]) || $array[$id]['parentid'] == $id) {
            return $i;
        } else {
            $i++;
            return $this->_get_level($array[$id]['parentid'], $array, $i);
        }

    }


}