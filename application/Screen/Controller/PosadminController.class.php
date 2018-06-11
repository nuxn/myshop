<?php
namespace Screen\Controller;
use Common\Controller\AdminbaseController;

class PosadminController extends AdminbaseController{
	protected $poses;
    public function __construct()
    {
        parent::__construct();
        $this->poses=M("screen_pos");
    }

    public function index()
    {
        $start_time=strtotime(I('start_time'));
        $end_time=strtotime(I('end_time'));
        $id=trim(I('id'));
        $mid=trim(I('mid'));
        if($mid){
            $map['po.mid']=$mid;
        }
        if($id){
            $map['po.id']=$id;
        }
        if($start_time&&$end_time){
            $map['po.create_time'] = array(array('EGT',$start_time),array('ELT',$end_time)) ;
        }
        $map['brash']=1;
        $poses=$this->poses
            ->alias("po")
            ->where($map);

        $count=$poses->count();
        $page = $this->page($count, 20);
        $poses->limit($page->firstRow , $page->listRows);
        $this->assign("page", $page->show('Admin'));

        $select_poses=$this->poses
            ->alias("po")
            ->field("po.*,m.merchant_name")
            ->join('left join ypt_merchants m on po.mid=m.id')
            ->where($map)
            ->order("id asc")
            ->select();

	    $this->assign('poses',$select_poses);
        $this->assign("formget", array_merge($_GET, $_POST));
        $this->display();
	}


    public function add()
    {
        if(IS_POST){
            $input = I('');
            $now = time();
            if($input['deadline'] != '0'){
                $input['deadline'] += $now;
            }
            $input['add_time'] = $now;
            $input['status'] = 1;
            $res = $this->poses->add($input);
            if($res){
                $this->ajaxReturn(array('code' => '0000'));
            } else {
                $this->ajaxReturn(array('code' => '0001'));
            }
        }else{
            $this->display();
        }

    }

    public function edit()
    {
        $id=I("id",'trim');
        $pos=$this->poses
            ->alias("po")
            ->join("left join __MERCHANTS__ m on po.mid = m.id")
            ->join("left join __MERCHANTS_USERS__ u on m.uid = u.id")
            ->field("po.*,u.user_phone")
            ->where("po.id=$id")
            ->find();
        $this->assign("pos",$pos);
        $this->display();
    }

    public function edit_post()
    {
        $check=$this->check_phone($_POST['post']['user_phone']);
        $id=I("id");
        if($check['code'] !="0"){
            $this->error($check['msg']);
        };
        $data['mid']=$this->find_merchant($_POST['post']['user_phone']);
        $data['mac']=$_POST['post']['mac'];
        $data['province']=$_POST['post']['province'];
        $data['city']=$_POST['post']['city'];
        $data['county']=$_POST['post']['county'];
        $data['address']=$_POST['post']['address'];
        $data['add_time']=time();
        $data['status']=0;
        $data['brash']=0;
        $this->poses->where("id=$id")->save($data);
        $this->success("恭喜你编辑成功");
    }

    public function delete()
    {
        $id=I("id",'trim');
        $data['brash']=1;
        $this->poses->where("id=$id")->save($data);
        $this->success('恭喜你删除成功',U('Posadmin/index'));
    }
//   通过手机号码找商户的id
    public function find_merchant($user_phone)
    {
        $mid=M("merchants")->alias("m")->join("left join __MERCHANTS_USERS__ u on u.id = m.uid")->field("m.id")->where("u.user_phone=$user_phone")->find();
        return $mid['id'];
    }

//    检查手机号码的可用性
    public function check_phone($user_phone)
    {
        if(!isMobile($user_phone))
        {
            return array("code"=>1,"msg"=>"你输入的电话不正确");
        }
        $uid=M("merchants_users")->where("user_phone=$user_phone")->getField("id");
        if(!$uid){
            return array("code"=>2,"msg"=>"你输入的手机号还未申请");
        }
        $role_id=M("merchants_role_users")->where("uid=$uid")->getField("role_id");
        if($role_id != "3"){
            return array("code"=>3,"msg"=>"你输入的手机号码非商户的");
        }
        return array("code" =>0);
    }

    //    改变上线状态
    public function change_status(){
        $id=I('post.id');
        $cate=$this->poses->find($id);
        $status=$cate['status']== 0 ? 1 : 0;
        echo $status;
        if($cate){
            $this->poses->where("id=$id")->setField('status', $status);
        }
    }
}