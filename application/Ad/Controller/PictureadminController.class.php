<?php
namespace Ad\Controller;
use Common\Controller\AdminbaseController;

class PictureadminController extends AdminbaseController{

    protected $adver;
    function _initialize() {
        parent::_initialize();
        $this->adver = M("adver");
    }

	public function index(){
        //dump($_SESSION);exit;
        if($_POST){
            $start_time=I('start_time');
            $end_time=I('end_time');
            $title=I('title');
            if($start_time){
                $map['start_time']=array('EGT',strtotime($start_time));
                $this->assign("start_time",$start_time);
            }
            if($end_time){
                $map['end_time']=array('ELT',strtotime($end_time));
                $this->assign("end_time",$end_time);
            }
            if(I('road')){
                $map['road']=I('road');
                $this->assign("road",$map['road']);
            }
            /*if(I('outin')){
                $map['outin']=I('outin');
            }*/
            if(I('callstyle')){
                $map['callstyle']=I('callstyle');
                $this->assign("callstyle",$map['callstyle']);
            }
            if($title){
                $map['title']=array('like',"%$title%");
                $this->assign("title",$title);
            }
        }
        //$map['kind']=1;1111
        //$map['muid']=$_SESSION['muid'];
        $count=$this->adver->where($map)->count();
//        dump($advers);
        $page = $this->page($count, 20);
        $this->adver->limit($page->firstRow , $page->listRows)->order("sort desc");

        $this->assign("page", $page->show('Admin'));
        $advers=$this->adver->where($map)->select();
        $this->assign('advers',$advers);
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
            $regex = '/^(http:\/\/|https:\/\/|ftp:\/\/).*$/';
            if(!preg_match($regex,$_POST['url'])){
                $this->error('添加失败，输入的跳转地址不完整');
            }
            if(empty($_POST['thumb'])){
                $this->error('添加失败，请上传图片');
            }
            if($this->adver->create()){
                $this->adver->status=1;
                $this->adver->kind=1;
                $this->adver->is_ypt=1;
                $this->adver->muid=$_SESSION['muid'];
                $this->adver->start_time=strtotime($_POST['start_time']);
                $this->adver->end_time=strtotime($_POST['end_time']);
                $this->adver->title=$_POST['post']['title'];
                $this->adver->add();
                $this->success("恭喜你新增成功",U('Pictureadmin/index'));
            }else{
                $this->error("系统错误,添加失败");
            }
        }
    }
//    编辑图片广告
    public function edit()
    {
        $id=I("get.id");
        $adver=$this->adver->find($id);
        $this->assign('adver',$adver);
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
            $regex = '/^(http:\/\/|https:\/\/|ftp:\/\/).*$/';
            if(!preg_match($regex,$_POST['url'])){
                $this->error('编辑失败，输入的跳转地址不完整');
            }else{
                $data['url']=$_POST['url'];
            }
            $data['sort']=$_POST['sort'];
            $data['title']=$_POST['post']['title'];
            $data['content']=$_POST['content'];
            $data['thumb']=$_POST['thumb'];
            $data['start_time']=strtotime($_POST['start_time']);
            $data['end_time']=strtotime($_POST['end_time']);
            $this->adver->where("id=$id")->save($data);

            $this->success("恭喜你编辑成功",U('Pictureadmin/index'));
        }
    }
//    删除图片广告
    public function delete()
    {
        if($_GET){
            $id=I("get.id");
            $this->adver->where("id=$id")->delete();
            $this->success("恭喜你删除成功");
        }
    }

    //    改变上线状态
    public function change_status(){
        $id=I('post.id');
        $cate=$this->adver->find($id);
        $status=$cate['status']== 0 ? 1 : 0;
        echo $status;
        $this->adver->where("id=$id")->setField('status', $status);
    }
	
	public function upload_into(){
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize   =     3145728 ;// 设置附件上传大小
        $upload->exts      =      array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath  =     C('_WEB_UPLOAD_'); // 设置附件上传根目录
        $upload->savePath  =     'ad/'; // 设置附件上传（子）目录
        // 上传文件
        $info   =   $upload->upload();

        if($info){
            $data['type']=1;
            if($info['thumb']){
                $data['thumb']=$info['thumb']['savepath'].$info['thumb']['savename'];
            }
            echo json_encode($data);
            exit();
        }else{
            $data['type']=2;
            $data['message']=$upload->getError();
            echo json_encode($data);
            exit();
        }
    }

    public function zzz()
    {
        $data = M('users')->field('id,mobile')->select();
        $a=0;
        foreach($data as $k => $v){
            $id = $this->ppp($v['mobile']);
            if($id){
                $map['id'] = $v['id'];
                M('users')->where($map)->setField('muid',$id);
            }
            $a+=1;

        }
        echo $a;

    }

    public function ppp($m)
    {
        $map['user_phone'] = $m;
        $id = M('merchants_users')->field('id')->where($map)->find();
        return $id['id'];
    }

}