<?php
namespace  Merchants\Controller;
use Common\Controller\AdminbaseController;
use Think\Page;

/***
 * Class AdminRoleController
 * @package Merchants\Controller
 * @auth 534244896@qq.com
 */

class  AdminMessageController extends  AdminbaseController {

    public function _initialize() {

        empty($_GET['upw'])?"":session("__SP_UPW__",$_GET['upw']);//设置后台登录加密码
        parent::_initialize();
        $this->initMenu();
    }

    public  function  index(){

        $map =array();
        $model = M("message_log");

        $user_phone=I("user_phone");
        if($user_phone){
            $map['u.user_phone']=array('like',"%$user_phone%");
            $this->assign("user_phone",$user_phone);
        }

        $p = !empty($_GET["p"]) ? $_GET['p'] : 1;
        $data = $model->alias('l')
                        ->field("l.id, l.msg_tpl_id,l.add_time,u.user_phone,t.tpl_name,l.msg_tpl_contens")
                        ->join("left join ".C('DB_PREFIX')."merchants_users u on l.uid= u.id" )
                        ->join("left join ".C('DB_PREFIX')."message_tpl t on t.id= l.msg_tpl_id" )
                        ->page($p, C('ADMIN_PAGE_ROWS'))
                        ->where($map)
                        ->order('id desc')
                        ->select();

        $page = new Page(
                $model->alias('l')
                      ->field("l.id, l.msg_tpl_id,l.add_time,u.user_phone,t.tpl_name,l.msg_tpl_contens")
                      ->join("left join ".C('DB_PREFIX')."merchants_users u on l.uid= u.id" )
                      ->join("left join ".C('DB_PREFIX')."message_tpl t on t.id= l.msg_tpl_id" )
                      ->where($map)
                      ->count(),
                     C('ADMIN_PAGE_ROWS')
        );
        $this->assign('log_list', $data);
        $this->assign('page', $page->show());
        $this->display();
    }

    public  function  add(){
        if(IS_POST){
            $model=D('AdminMessage');
            $data_arr=$model->create();
            if($model->create()){

                // 查询用户 是否存在
                $uid=I("uid");
                $users=M("merchants_users")->where(array("id"=>$uid))->find();
                if(empty($users)){
                    $this->ajaxReturn(array("msg" => "用户不存在","status"=>"fail"));
                }

                $data_arr['add_time']=time();
                $msg_tpl_id=$data_arr['msg_tpl_id'];
                $tpl_data=M("message_tpl")->field("msg_contents")->where(array('id'=>$msg_tpl_id))->find();
                $data_arr['msg_tpl_contens']=$tpl_data['msg_contents'];
                if($model->add($data_arr)) {
                    $this->ajaxReturn(array("msg" => "添加成功","status"=>"success"));
                }else{
                    $this->ajaxReturn(array("msg" => $model->getError(),"status"=>"fail"));
                }
            }else{
              $this->ajaxReturn(array("msg"=>$model->getError()));
            }
        }else{
            $model=M("message_tpl");
            $data=$model->field("id,tpl_name")->where(array('status'=>1))->select();
            $this->assign("data",$data);
            $this->display();
        }

    }



    public  function  msg_tpl_list(){
        $map =array();
        $model = M("message_tpl");

        $tpl_name=I("tpl_name");
        if($tpl_name){
            $map['tpl_name']=array('like',"%$tpl_name%");
            $this->assign("tpl_name",$tpl_name);
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
        $this->assign('tpl_list', $data);
        $this->assign('page', $page->show());
        $this->display();


    }


    public  function msg_tpl_add(){
        if(IS_POST){
            $model=D("AdminMessageTpl");
            $data=$model->create();
            if ($data){
                $data['add_time']=time();
                if($model->add($data)){
                    $this->ajaxReturn(array("status"=>'success',"msg"=>"添加成功"));
                }else{
                    $this->ajaxReturn(array("status"=>'fail',"msg"=>$model->getError()));
                }
            }else{
                $this->ajaxReturn(array("status"=>'fail',"msg"=>$model->getError()));
            }


        }else{
            $this->display();
        }

    }

    public  function  msg_tpl_edit(){
        $model=D("AdminMessageTpl");
        if(IS_GET){
            $id=I("id",'intaval',0);
            $data=$model->where(array('id'=>$id))->find();
            $this->assign("data",$data);
            $this->display();
        }

        if(IS_POST){
            $data_arr=$model->create();
            if($data_arr){
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

    public  function msg_tpl_del(){
        if ($_GET){
            $id=I("id");
            $model=M("message_tpl");
            $res=$model->where(array("id"=>$id))->delete();
            if($res){
                $this->success("删除成功",U("msg_tpl_list"));
            }else{
                $this->error("删除失败");
            }
        }else{
            $this->error("记录不存在");
        }
    }
}