<?php
namespace  Merchants\Controller;
use Common\Controller\AdminbaseController;
use Think\Page;

/***
 * Class AdminRoleController
 * @package Merchants\Controller
 * @auth 534244896@qq.com
 */

class  AdminRoleController extends  AdminbaseController {

    public function _initialize() {

        empty($_GET['upw'])?"":session("__SP_UPW__",$_GET['upw']);//设置后台登录加密码
        parent::_initialize();
        $this->initMenu();
    }

    public  function  index(){

        $map =array();
        $model = M("merchants_role");

        $role_name=I("role_name");
        if($role_name){
            $map['role_name']=array('like',"%$role_name%");
            $this->assign("role_name",$role_name);
        }

        $p = !empty($_GET["p"]) ? $_GET['p'] : 1;
        $data = $model->page($p, C('ADMIN_PAGE_ROWS'))
            ->where($map)
            ->order('id desc')
            ->select();

        $page = new Page(
            $model->where($map)
                ->count(),
            C('ADMIN_PAGE_ROWS')
        );
        $this->assign('role_list', $data);
        $this->assign('page', $page->show());
        $this->display();
    }

    public  function  add(){
        if(IS_POST){
            $model=D('AdminRole');
            $data_arr=$model->create();
            if($model->create()){
                $data_arr['add_time']=time();
                if($model->add($data_arr)) {
                    $this->ajaxReturn(array("msg" => "添加成功","status"=>"success"));
                }else{
                    $this->ajaxReturn(array("msg" => $model->getError(),"status"=>"fail"));
                }
            }else{
              $this->ajaxReturn(array("msg"=>$model->getError()));
            }
        }else{
            $this->display();
        }

    }

    public  function  edit(){
        $model=D('AdminRole');
        if(IS_GET){
            $id=I("id",0);
            $data=$model->where(array('id'=>$id))->find();
            $this->assign("data",$data);
            $this->display();
        }

        if(IS_POST){
            $data_arr=$model->create();
            if($model->create()){
                if($model->save($data_arr) !== false) {
                    $this->ajaxReturn(array("msg" => "修改成功","status"=>"success"));
                }else{
                    $this->ajaxReturn(array("msg" => $model->getError(),"status"=>"fail"));
                }
            }else{
                $this->ajaxReturn(array("msg"=>$model->getError()));
            }
        }

    }
    public  function  del(){
        if(IS_GET){
            $id=I("id");
            $data=M("merchants_role")->where(array('id'=>$id))->delete();
            if($data){
                $this->success('删除成功',U("adminRole/index"));
            }else{
                $this->success('删除失败');
            }
        }
    }
}