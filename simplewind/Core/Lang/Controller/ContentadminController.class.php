<?php
namespace Pay\Controller;
use Common\Controller\AdminbaseController;

class ContentadminController extends AdminbaseController{
    protected $pay;

    function _initialize() {
        parent::_initialize();
        $this->pay = M("pay");
    }
/*
 * 支付信息详情页面
 * */
    public function index(){
        if($_POST){
            $start_time=strtotime(I('post.start_time'));
            $end_time=strtotime(I('post.end_time'));
            $select=I('select');
            $keyword=I('keyword');
            $paystyle_id=I('paystyle');
            if($paystyle_id){
                $map['paystyle_id']=$paystyle_id;
            }
            if($start_time&&$end_time){
                $map['paytime'] = array(array('EGT',$start_time),array('ELT',$end_time)) ;
            }
            if($select&&$keyword){
                switch ($select)
                {
                    case 1:
                        $map['id']=$keyword;
                        break;
                    case 2:
                        $map['merchant_id']=$keyword;
                        break;
                    case 3:
                        $map['customer_id']=$keyword;
                        break;
                    case 2:
                        $map['checker_id']=$keyword;
                        break;
                    default:
                        break;
                }
            }
        }
//        dump($map);
        $map['brash']=1;
        $pays=$this->pay;
        $count=$pays->where($map)->count();
/*
 * 查询sql语句
 * echo $Pays->where($map)->_sql();
 * */
        $page = $this->page($count, 20);
        $pays->limit($page->firstRow , $page->listRows)->order("paytime desc");

        $this->assign("page", $page->show('Admin'));
        $pays=$this->pay->where($map)->select();
        $this->assign("pays",$pays);
        $this->display();
    }

    public function add()
    {
        $this->display();
    }
    /*
     * 支付删除
     * */
    public function delete(){
        if(isset($_GET['id'])){
            $id = I("get.id",0,'intval');

            if ($this->pay->where(array('id'=>$id))->save(array('brash'=>0)) !==false) {
                $this->success("删除成功！");
            } else {
                $this->error("删除失败！");
            }
        }

        if(isset($_POST['ids'])){
            $ids = I('post.ids/a');

            if ($this->pay->where(array('id'=>array('in',$ids)))->save(array('brash'=>0))!==false) {
                $this->success("删除成功！");
            } else {
                $this->error("删除失败！");
            }
        }
    }
        /*
         *  改变支付状态
         * */
//    function change_status()
//    {
//        $pay = $this->pay;
//        $id 	= intval($_REQUEST['id']);
//        $status  =I("post.status") == 1 ? 0:1 ;
//        $this->ajaxReturn($status);
//        $this->Pay->where("id=$id")->setField('status', $status);
//    }
    public function change_status(){
        $id=I('post.id');
        $cate=$this->pay->find($id);
        $status=$cate['status']== 0 ? 1 : 0;
        echo $status;
        $this->pay->where("id=$id")->setField('status', $status);
    }
}