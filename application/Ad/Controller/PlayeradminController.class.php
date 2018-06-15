<?php
namespace Ad\Controller;
use Common\Controller\AdminbaseController;

class PlayeradminController extends AdminbaseController{

    protected $player;
    function _initialize() {
        parent::_initialize();
        $this->player = M("adver");
    }

    public function index(){
        if($_POST){
            $start_time=strtotime(I('start_time'));
            $end_time=strtotime(I('end_time'));
            $keyword=I('keyword');
            if($start_time){
                $map['start_time']=array('EGT',$start_time);
            }
            if($end_time){
                $map['end_time']=array('ELT',$end_time);
            }
            if(I('road')){
                $map['road']=I('road');
            }
            if(I('outin')){
                $map['outin']=I('outin');
            }
            if(I('callstyle')){
                $map['callstyle']=I('callstyle');
            }
            if(I('keyword')){
                $map['titlel']=array('like',"%$keyword%");
            }
        }
        $map['kind']=2;
        $count=$this->player->where($map)->count();
//        dump($players);
        $page = $this->page($count, 20);
        $this->player->limit($page->firstRow , $page->listRows)->order("start_time desc");

        $this->assign("page", $page->show('Admin'));
        $players=$this->player->where($map)->select();
        $this->assign('players',$players);
        $this->display();
    }

    public function add()
    {
        $this->display();
    }
//新增成功功能实现
    public function add_post()
    {

        if($_POST){
            if($this->player->create()){
                $this->player->status=1;
                $this->player->kind=2;
                $this->player->start_time=$_POST['start_time'];
                $this->player->end_time=$_POST['end_time'];
                $this->player->title=$_POST['post']['title'];
                $this->player->add();

                $this->success("恭喜你新增成功");
            }else{
                $this->error("系统错误,添加失败,请与彭鼎互通友谊");
            }
        }
    }
//    编辑图片广告
    public function edit()
    {
        $id=I("get.id");
        $player=$this->player->find($id);
        $this->assign('player',$player);
//        dump($player)
        $this->display();
    }
//    编辑广告成功
    public function edit_post()
    {
        if($_POST){
            $id=$_POST['id'];
            $data['road']=$_POST['road'];
            $data['outin']=$_POST['outin'];
            $data['callstyle']=$_POST['callstyle'];
            $data['url']=$_POST['url'];
            $data['sort']=$_POST['sort'];
            $data['title']=$_POST['post']['title'];
            $data['content']=$_POST['content'];
            $data['thumb']=$_POST['thumb'];
            $data['start_time']=strtotime($_POST['start_time']);
            $data['end_time']=strtotime($_POST['end_time']);
            $this->player->where("id=$id")->save($data);

            $this->success("恭喜你编辑成功",U('Pictureadmin/index'));
        }
    }
//    删除图片广告
    public function delete()
    {
        if($_GET){
            $id=I("get.id");
            $this->player->where("id=$id")->delete();
            $this->success("恭喜你删除成功");
        }
    }

    //    改变上线状态
    public function change_status(){
        $id=I('post.id');
        $cate=$this->player->find($id);
        $status=$cate['status']== 0 ? 1 : 0;
        echo $status;
        $this->player->where("id=$id")->setField('status', $status);
    }


}