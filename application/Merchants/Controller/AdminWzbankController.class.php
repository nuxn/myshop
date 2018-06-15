<?php
/**
 * Created by PhpStorm.
 * User: zgf
 * Date: 2017/3/3
 * Time: 16:29
 */
namespace Merchants\Controller;

use Common\Controller\AdminbaseController;

class AdminWzbankController extends AdminbaseController
{
    protected $model;
    public function _initialize()
    {

        empty($_GET['upw']) ? "" : session("__SP_UPW__", $_GET['upw']);//设置后台登录加密码
        parent::_initialize();
        $this->initMenu();
        $this->model = M('wzbank');
    }
    public function index()
    {
        $map['id'] = array('gt',0);
        if($_POST['bank']){
            $map['bank'] = array('like','%'.$_POST['bank'].'%');
            $this->assign('bank',$_POST['bank']);
        }
        if($_POST['number']){
            $map['number'] = array('like','%'.$_POST['number'].'%');
            $this->assign('number',$_POST['number']);
        }
        $res = $this->model->where($map)->select();
        $this->assign('banks',$res);
        $this->display();
    }
    public function add()
    {
        if($_POST){
            $data['bank'] = $_POST['bank'];
            $data['number'] = $_POST['number'];
            $data['create_time'] = time();
            $res = $this->model->add($data);
            if($res){
                $this->success('添加成功！',U('AdminWzbank/index'));
            }else{
                $this->error('添加失败！');
            }
        }
        $this->display();
    }
    public function edit()
    {
        if($_GET){
            $id = $_GET['id'];
            $res = $this->model->where('id='.$id)->find();
        }


        if($_POST){
            $id = $_POST['id'];
            $bank = $_POST['bank'];
            $number = $_POST['number'];
            $res = $this->model->where('id='.$id)->setField(array('bank'=>$bank,'number'=>$number));
            if($res){
                $this->success('修改成功！',U('AdminWzbank/index'));
            }else{
                $this->error('修改失败！');
            }
        }
        $this->assign('bank',$res);
        $this->display();
    }
    public function del()
    {
        if($_GET){
            $id = $_GET['id'];
            $res = $this->model->delete($id);
            if($res){
                $this->success('删除成功！',U('AdminWzbank/index'));
            }else{
                $this->error('删除失败！');
            }
        }
    }
}