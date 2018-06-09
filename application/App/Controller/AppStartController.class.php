<?php
/**
 * Created by PhpStorm.
 * User: joan
 * Date: 2017/2/22
 * Time: 18:06
 */
namespace App\Controller;

use Common\Controller\AdminbaseController;

/**手机短信
 * Class AdminSmsController
 * @package Message\Controller
 */
class AppStartController extends AdminbaseController
{

    protected $appModel;

    function _initialize()
    {
        parent::_initialize();
        $this->appModel = M("app_start");
    }

    public function index()
    {
        if($_POST){
            $start_time=I('start_time');
            $end_time=I('end_time');
            $name=I('name');
            if($start_time){
                $map['start_time']=array('EGT',strtotime($start_time));
                $this->assign("start_time",$start_time);
            }
            if($end_time){
                $map['start_time']=array('ELT',strtotime($end_time));
                $this->assign("end_time",$end_time);
            }
            if($name){
                $map['name']=array('like',"%$name%");
                $this->assign("name",$name);
            }
        }
        $count=$this->appModel->where($map)->count();
        $page = $this->page($count, 20);
        $this->appModel->limit($page->firstRow , $page->listRows);
        $this->assign("page", $page->show('Admin'));
        $list=$this->appModel->where($map)->select();
        $this->assign('lists', $list);
        $this->display();
    }

    public function add()
    {
        if($_POST){
            if(empty($_POST['name'])){
                $this->error('添加失败，请输入名称');
            }
            if(empty($_POST['thumb'])){
                $this->error('添加失败，请上传图片');
            }
            if(empty($_POST['start_time']) && empty($_POST['end_time'])){
                $this->error('添加失败，请选择开始时间和结束时间');
            }elseif (strtotime($_POST['start_time'])>strtotime($_POST['end_time'])){
                $this->error('添加失败，开始时间不能大于结束时间');
            }
            if($this->appModel->create()){
                $this->appModel->start_time=strtotime($_POST['start_time']);
                $this->appModel->end_time=strtotime($_POST['end_time']);
                $this->appModel->add_time=time();
                $this->appModel->update_time=time();
                $this->appModel->add();
                $this->success("恭喜你新增成功",U('AppStart/index'));
            }else{
                $this->error("系统错误,添加失败");
            }
        }
        $this->display();
    }

    public function edit()
    {
        if($_POST){
            $id=$_POST['id'];
            $data['name']=$_POST['name'];
            $data['thumb']=$_POST['thumb'];
            $data['update_time']=time();
            $data['start_time']=strtotime($_POST['start_time']);
            $data['end_time']=strtotime($_POST['end_time']);
            $this->appModel->where("id=$id")->save($data);

            $this->success("恭喜你编辑成功",U('AppStart/index'));
        }elseif ($_GET){
            $id=I("get.id");
            $app=$this->appModel->find($id);
            if(!$app){$this->error("禁止非法操作");}
            $this->assign('app',$app);
            $this->display();
        }else{
            $this->error("禁止非法操作");
        }
    }

    public function detail()
    {
        if($_GET){
            $id=I('id');
            $app = $this->appModel->where("id=$id")->find();
            $this->assign('app',$app);
            $this->display();
        }else{
            $this->error('禁止非法操作');
        }
    }

    //删除启动页
    public function delete()
    {
        if($_GET){
            $id=I("get.id");
            $this->appModel->where("id=$id")->delete();
            $this->success("恭喜你删除成功");
        }
    }

    //改变状态
    public function change_status(){
        $id=I('post.id');
        $cate=$this->appModel->find($id);
        $status=$cate['status']== 0 ? 1 : 0;
        echo $status;
        $this->appModel->where("id=$id")->setField('status', $status);
    }

}
