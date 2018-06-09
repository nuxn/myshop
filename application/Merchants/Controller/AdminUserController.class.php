<?php
/**
 * 后台首页
 */
namespace Merchants\Controller;

use Common\Controller\AdminbaseController;
use Think\Page;

class AdminUserController extends AdminbaseController
{

    public function _initialize()
    {

        empty($_GET['upw']) ? "" : session("__SP_UPW__", $_GET['upw']);//设置后台登录加密码
        parent::_initialize();
        $this->initMenu();
    }

    public function index()
    {
        $map =array();
        $model = M("merchants_users");

        $user_phone=I("user_phone");
        if($user_phone){
            $map['user_phone']=array('like',"%$user_phone%");
            $this->assign("user_phone",$user_phone);
        }

        $p = !empty($_GET["p"]) ? $_GET['p'] : 1;

        //拼接子查询字符串
        $query_str= "(select user_phone from ".C('DB_PREFIX')."merchants_users where id=mu.agent_id) as p_user";
// 修改分页 3.22
//        $data = $model->alias("mu")
//                      ->join(" left join ".C('DB_PREFIX')."merchants_role_users ru on ru.uid=mu.id")
//                      ->join(" left join ".C('DB_PREFIX')."merchants_role mr on mr.id=ru.role_id")
//                      ->field("mu.id,mu.user_phone,mr.role_name,mu.add_time,mu.ip_address,".$query_str)
//                      ->page($p, C('ADMIN_PAGE_ROWS'))
//                      ->where($map)
//                      ->order('id desc')
//                      ->select();
//
//        $page = new Page(
//            $model->alias("mu")
//                  ->join(" left join ".C('DB_PREFIX')."merchants_role_users ru on ru.uid=mu.id")
//                  ->join(" left join ".C('DB_PREFIX')."merchants_role mr on mr.id=ru.role_id")
//                  ->field("mu.id,mu.user_phone,mr.role_name,mu.add_time,mu.ip_address,".$query_str)
//                  ->where($map)
//                  ->count(),
//            C('ADMIN_PAGE_ROWS')
//        );
//        $this->assign('users', $data);
//        $this->assign('page', $page->show());
//        $this->display();

        $model->alias("mu")
            ->join(" left join ".C('DB_PREFIX')."merchants_role_users ru on ru.uid=mu.id")
            ->join(" left join ".C('DB_PREFIX')."merchants_role mr on mr.id=ru.role_id")
            ->field("mu.id,mu.user_phone,mu.user_name,mr.role_name,mu.add_time,mu.ip_address,".$query_str)
            ->where($map);
        $count=$model->count();
        $page = $this->page($count, 20);

        $model->limit($page->firstRow , $page->listRows)->order("id asc");
        $this->assign("page", $page->show('Admin'));

        $data=$model->alias("mu")
            ->join(" left join ".C('DB_PREFIX')."merchants_role_users ru on ru.uid=mu.id")
            ->join(" left join ".C('DB_PREFIX')."merchants_role mr on mr.id=ru.role_id")
            ->field("mu.id,mu.user_phone,mu.user_name,mr.role_name,mu.add_time,mu.ip_address,".$query_str)
            ->where($map)->select();
//        dump($data);
//        exit;
        $this->assign("users",$data);
        $this->display();

    }

    public function add()
    {
        if (IS_POST) {

            $user_phone = I("user_phone");

            $dd=M("merchants_users")->where(array('user_phone'=>$user_phone))->count();
            if($dd >0){
                $this->ajaxReturn(array("code" => '4', 'msg' => '用户已存在'));
            }
            $user_name = trim(I("user_name"));
            if(!$user_name){
                $this->ajaxReturn(array("code" => '6', 'msg' => '用户简称不能为空'));
            }
            if (empty($user_phone)) {
                $this->ajaxReturn(array("code" => '2', 'msg' => '用户手机号码不能为空'));
            }

            if (!preg_match("/^1[34578]\d{9}$/", $user_phone)) {
                $this->ajaxReturn(array("code" => '3', 'msg' => '用户手机号码格式不正确'));
            }

            if(I("user_pwd") == ""){
                $user_pwd=123456;
            }else{
                $user_pwd=I("user_pwd");
            }

            $role_id=I('role_id');
            if (!$role_id){
                $this->ajaxReturn(array("code" => '5', 'msg' => '请选择角色'));
            }
            $model = M("merchants_users");
            $data = $model->create();
            if ($data) {
                $data['user_pwd'] = md5($user_pwd);
                $data['add_time'] = time();
                $data['ip_address'] = get_client_ip();
//                公司的内部员工
                if($role_id == 4){
                    $data['pid']=2;
                    $data['agent_id']=0;
                }
//                公司的外部员工
                if($role_id == 5){
                    $data['pid']=2;
                    $data['agent_id']=0;
                }
                $res=$model->add($data);
                if ($res) {
                    $arr_role=array();
                    $arr_role['uid']=$res;
                    $arr_role['role_id']=$role_id;
                    $arr_role['add_time']=time();
                    if(M("merchants_role_users")->add($arr_role)) {
                        $this->ajaxReturn(array('code' => '1', 'msg' => '添加成功'));
                    }else{
                        $this->ajaxReturn(array('code' => '2', 'msg' => '添加用户成功，未添加角色成功'));
                    }
                } else {
                    $this->ajaxReturn(array('code' => '0', 'msg' => '添加失败'));
                }
            }
        } else {
            //用户角色
            $role_list=M("merchants_role")->field("id,role_name")->select();
            $this->assign('roles',$role_list);
            $this->display();
        }
    }


    public function edit()
    {
        if (IS_GET) {
            $id = I("id");
            $data = M("merchants_users")->alias('mu')->join("left join ".C('DB_PREFIX')."merchants_role_users ru on ru.uid=mu.id")->field("mu.id,mu.user_phone,mu.user_pwd,ru.role_id,mu.user_name")->where(array('mu.id' => $id))->find();
//            用户角色
            $role_list=M("merchants_role")->field("id,role_name")->select();
            $this->assign('roles',$role_list);
            $this->assign("data", $data);
            $this->display();
        }
        if (IS_POST) {
            $model = M("merchants_users");
            $data['user_phone'] =$user_phone=I("user_phone");
            if (empty($user_phone)) {
                $this->ajaxReturn(array("code" => '2', 'msg' => '用户手机号码不能为空'));
            }

            if (!preg_match("/^1[34578]\d{9}$/", $user_phone)) {
                $this->ajaxReturn(array("code" => '3', 'msg' => '用户手机号码格式不正确'));
            }
            $user_pwd =I("user_pwd");
            if($user_pwd){
                $data['user_pwd']=$user_pwd;
            }
            $data['id'] =I("id");

            $role_id=I("role_id");
            if(!$role_id){
                $this->ajaxReturn(array("code" => '4', 'msg' => '请选择角色'));
            }
            if ($data) {
                $res = $model->save($data);
                if ($res !== false) {
                    $arr_role=array();
                    $arr_role['role_id']=$role_id;
                    $role_count=M("merchants_role_users")->where(array('uid'=>$data['id']))->Count();
                    if($role_count>0) {
                        if (M("merchants_role_users")->where(array('uid' => $data['id']))->save($arr_role)) {
                            $this->ajaxReturn(array("code" => '1', 'msg' => '修改成功'));
                        } else {
                            $this->ajaxReturn(array("code" => '5', 'msg' => '用户修改成功，角色未修改成功'));
                        }
                    }else{
                        $arr_role['uid']=$data['id'];
                        if (M("merchants_role_users")->add($arr_role)) {
                            $this->ajaxReturn(array("code" => '1', 'msg' => '修改成功'));
                        } else {
                            $this->ajaxReturn(array("code" => '6', 'msg' => '用户修改成功，角色未添加成功'));
                        }
                    }
                } else {
                    $this->ajaxReturn(array("code" => '0', 'msg' => '修改失败'));
                }
            }
        }
    }

    public  function  del(){
        if(IS_GET) {
            $id = I("id");
            $res = M("merchants_users")->where(array('id' => $id))->delete();
            if ($res) {
                $this->success('删除成功', U('adminUser/index'));
                //$this->redirect('adminUser/index','',3,"删除成功");
            } else {
                $this->error('删除成功');
            }
        }
    }
//    修改用户的名字
    public function change_name()
    {
        $id=I("id");
        $new_name=I("new_name");
        $old_name=M("merchants_users")->where("id=$id")->getField("user_name");
        if($old_name != $new_name){
            $data['user_name']=$new_name;
            M("merchants_users")->where("id=$id")->save($data);
        }
    }

    public function change_phone()
    {
        $id =I("id");
        $user=M("merchants_users")->where(array('id'=>$id))->find();
        if($user){
            file_put_contents('./data/log/change_phone.log', date("Y-m-d H:i:s") .  '修改用户手机号' . json_encode($user) . PHP_EOL, FILE_APPEND | LOCK_EX);
            $phone_change=mb_substr($user['user_phone'],0,3)."XXXX".mb_substr($user['user_phone'],7);
            M("merchants_users")->where(array('id'=>$id))->save(array('user_phone'=>$phone_change));
            $this->success("用户删除成功");
        }else{
            $this->error("请联系彭鼎");
        }
    }
}

