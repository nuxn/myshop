<?php
namespace Pay\Controller;
use Common\Controller\AdminbaseController;

class MessageadminController extends AdminbaseController{
	protected $shopcates;

    function _initialize() {
        parent::_initialize();
        $this->shopcates = M("shopcate");
    }

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
                        $map['shop_id']=$keyword;
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
        $shopcates=$this->shopcates;
        $count=$shopcates->where($map)->count();
        /*
         * 查询sql语句
         * echo $Pays->where($map)->_sql();
         * */
        $page = $this->page($count, 20);
        $shopcates->limit($page->firstRow , $page->listRows)->order("id asc");

        $this->assign("page", $page->show('Admin'));
        $shopcates=$this->shopcates->where($map)->select();
        $this->assign("shopcates",$shopcates);
        $this->display();

	}

	public function add()
    {
	    $this->display();
    }

    public function edit()
    {
        $this->display();
    }
}