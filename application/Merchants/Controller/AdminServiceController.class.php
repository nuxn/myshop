<?php
namespace  Merchants\Controller;
use Common\Controller\AdminbaseController;
use Think\Page;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/3
 * Time: 10:38
 */

class  AdminServiceController extends  AdminbaseController
{

    public $miniappModel;
    public $userModel;
    public $merchantsModel;
	public $merchants_levelModel;

    public function _initialize()
    {
        empty($_GET['upw'])?"":session("__SP_UPW__",$_GET['upw']);//设置后台登录加密码
        parent::_initialize();
        $this->initMenu();
        $this->miniappModel = M('miniapp');
        $this->userModel = M("merchants_users");
        $this->merchantsModel = M("merchants");
		$this->merchants_levelModel = M("merchants_level");
        $this->dc_set =M("merchants_dc_set");
    }

    //开通服务列表
    public function index()
    {
        $map =array();
        $id = I("id");
        if($id){
            $map['mc.id'] = array('eq',$id);
        }
        $merchant_name = I("merchant_name");
        if($merchant_name){
            $map['mc.merchant_name'] = array('like',"%$merchant_name%");
        }
        $type = I("type");
        if($type){
            $map['m.type'] = array('eq',$type);
        }
        $start_time=I("start_time");
        $end_time=I("end_time");
        if(strtotime($start_time)>strtotime($end_time)){
            $this->error("开始时间不能大于结束时间");
        }elseif (!empty($start_time) && empty($end_time)){
            $this->error("请选择结束时间");
        }elseif (empty($start_time) && !empty($end_time)){
            $this->error("请选择开始时间");
        }
        if(!empty($start_time) && !empty($end_time)){
            $map['m.end_time'] = array('between',array(strtotime($start_time),strtotime($end_time)+86399));
        }
        $this->miniappModel->alias('m');
        $this->miniappModel->join("LEFT JOIN __MERCHANTS__ mc ON m.mid = mc.uid");
        $this->miniappModel->where($map);
        $count = $this->miniappModel->count();
        $page = $this->page($count, 20);
        $this->assign("page", $page->show('Admin'));

        $this->miniappModel->alias('m');
        $this->miniappModel->join("LEFT JOIN __MERCHANTS__ mc ON m.mid = mc.uid");
        $field = 'mc.merchant_name,mc.id,m.type,m.order_price,m.pay_type,m.start_time,m.end_time';
        $this->miniappModel->where($map);
        $this->miniappModel->field($field);
        $this->miniappModel->order("id DESC");
        $this->miniappModel->limit($page->firstRow , $page->listRows);
        $data_lists = $this->miniappModel->select();
        $this->assign("data_lists",$data_lists);

        $this->display();
    }

    //详情页
    public function detail()
    {
        $id = I("id");
        $this->miniappModel->alias('m');
        $this->miniappModel->join("LEFT JOIN __MERCHANTS_USERS__ mu ON m.mid = mu.id");
        $this->miniappModel->join("LEFT JOIN __USER_CASH__ uc ON m.cash_id = uc.id");
        $field = 'mu.user_name,uc.title,uc.up_price,uc.price,m.*';
        $data = $this->miniappModel->field($field)->where(array("m.id" => $id))->find();
        $this->assign("data", $data);

        $this->display();
    }

    //商家列表
    public function openList()
    {
        $map = array();

        $id = trim(I("id"));
        if($id){
            $map['m.id'] = array('eq',$id);
        }
        $merchant_name = trim(I("merchant_name"));
        if($merchant_name){
            $map['m.merchant_name'] = array('like',"%$merchant_name%");
        }
        $user_phone = trim(I("user_phone"));
        if($user_phone){
            $map['u.user_phone'] = array('like',"%$user_phone%");
        }
        $openid = trim(I("openid"));
        if($openid){
            $map['u.openid'] = array('like',"%$openid%");
        }
        $count = $this->merchantsModel->alias('m')
            ->join("left join __MERCHANTS_USERS__ u on m.uid=u.id")
            ->where($map)
            ->count();
        $page = $this->page($count, 20);
        $this->assign("page", $page->show('Admin'));

        $data_lists = $this->merchantsModel->alias('m')
            ->join("left join __MERCHANTS_USERS__ u on m.uid=u.id")
            ->field("m.id,m.merchant_name,u.user_phone,u.openid")
            ->order('m.id DESC')
            ->where($map)
            ->select();
        $this->assign("data_lists",$data_lists);
        $this->display();
    }

    //给商家开通服务
    public function open()
    {
        if(IS_POST){
            $data = array();
            $id = I("id");
            $addTime = I("addTime");//select
            $endTime = I("end_time");//选择的到期时间(时间段)
            // $mini_type = I("mini_type");//小程序类型
            $type = I('trade');   //开通行业   1=便利店  2=餐饮
            $is_own = I('is_own');   //是否拥有独立小程序  1拥有  2=没有
            $is_enter = I('is_enter');   //是否加入商圈版  1加入  2不加入
            if(empty($addTime)){
                $this->error("请选择开通时长");
            }else if($addTime == "other" && empty($endTime)) {
                $this->error("请选择到期的时间",$_SERVER["HTTP_REFERER"]);
            }
            $over_time = I("over_time");//已开通商户的到期时间

            if($over_time){     /*开通中的用户*/
                if($endTime && strtotime($endTime) < $over_time){
                    $this->error("到期时间不能小于商家已开通服务的到期时间",$_SERVER["HTTP_REFERER"]);
                }
                if($addTime == "other"){
                    $data['end_time'] = strtotime($endTime)+86399;
                }elseif($addTime == "zero"){
                    $data['is_time']=0;
                }else{
                    $over_time = date("Y-m-d H:i:s", $over_time);
                    $data['end_time'] = strtotime("$over_time + $addTime month");
                }
                $data['start_time'] = strtotime($over_time)+1;
            }else{          /*未开通的用户*/
                if($endTime && strtotime($endTime) < time()){
                    $this->error("到期时间不能小于当天",$_SERVER["HTTP_REFERER"]);
                }
                if($addTime == "other"){
                    $data['end_time'] = strtotime($endTime)+86399;
                }elseif($addTime == "zero"){
                    $data['is_time']=0;
                }else{
                    $time = date("Y-m-d H:i:s", time());
                    $data['end_time'] = strtotime("$time + $addTime month");
                }
                $data['start_time'] = time();
            }
            //小程序类型 1=多店版便利店  2=平台版点餐  3=单店版便利店  4=单店点餐
            if($type==1){
                if($is_own==1){
                    $mini_type = 3;
                }elseif($is_own==2){
                    $mini_type = 1;
                }
            }elseif($type==2){
                if($is_own==1){
                    $mini_type = 4;
                }elseif($is_own==2){
                    $mini_type = 2;
                }
            }
            $data['add_time'] = time();
            $data['order_sn'] = date('YmdHis').$id.rand(1000000,9999999);
            $data['mid'] = $this->get_mch_uid($id);
            $data['type'] = $type;
            $data['is_own'] = $is_own;
            $data['is_enter'] = $is_enter;
            $data['remark'] = I("remark");
            $data['price'] = I("price");
            $data['pay_type'] = I("pay_type");
            $res = $this->miniappModel->add($data);
            if($res){
                $mData = array('is_miniapp'=>'2','end_time'=>$data['end_time'],'mini_type'=>$mini_type,'is_open'=>'1');
                $merchants = M("merchants");
                $merchants->where("id = $id")->setField($mData);
                if ($type==2) {
                    $this->dc_set->add(array('mid'=>$id));
                }
                $this->success("开通成功");
            }else{
                $this->error("开通失败");
            }
        }else{
            $id = I("id");
            $uid = $this->get_mch_uid($id);
            $this->userModel->alias('u');
            $this->userModel->where(array("u.id" => $uid));
            $this->userModel->join("LEFT JOIN __MINIAPP__ m ON u.id = m.mid");
            $this->userModel->join("LEFT JOIN __MERCHANTS__ mc ON u.id = mc.uid");
            $field = 'mc.id,mc.merchant_name,m.end_time,m.is_time';
            $this->userModel->field($field);
            $this->userModel->order("m.id DESC");
            $data = $this->userModel->find();
            if ($data['is_time']===null) {
                $data['is_time']=1;
            }
            $data['now'] = time();
            $data['end_time'] = intval($data['end_time']);
            $this->assign("data", $data);
            $this->display();
        }

    }
	
	//服务列表
    public function serverList()
    {
        $data = $this->merchants_levelModel->select();
        $this->assign("data", $data);
        $this->display();
    }

    //服务详情
    public function serverDetail()
    {
        $id = I('id');
        $data = $this->merchants_levelModel->where(array('id'=>$id))->find();
        $this->assign("data", $data);
        $this->display();
    }

    //服务修改
    public function serverEdit()
    {
        if(IS_GET){
            $id = I('id');
            $data = $this->merchants_levelModel->where(array('id'=>$id))->find();
            $this->assign("data", $data);
            $this->display();
        }elseif(IS_POST){
            $res = $this->merchants_levelModel->where(array('id'=>$_POST['id']))->save($_POST);
            if($res){
                $this->success("修改成功",U('serverList'));
            }else{
                $this->error("修改失败");
            }
        }
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
            if($info['face_img']){
                $data['name'] = 'face_img';
                $data['thumb']=$info['face_img']['savepath'].$info['face_img']['savename'];
            }
            if($info['img1']){
                $data['name'] = 'img1';
                $data['thumb']=$info['img1']['savepath'].$info['img1']['savename'];
            }
            if($info['img2']){
                $data['name'] = 'img2';
                $data['thumb']=$info['img2']['savepath'].$info['img2']['savename'];
            }
            if($info['img3']){
                $data['name'] = 'img3';
                $data['thumb']=$info['img3']['savepath'].$info['img3']['savename'];
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

    //改变状态
    public function change_status(){
        $id=I('post.id');
        $cate=$this->merchants_levelModel->find($id);
        $status=$cate['is_show']== 0 ? 1 : 0;
        echo $status;
        $this->merchants_levelModel->where("id=$id")->setField('is_show', $status);
    }

    //通过商户ID获取uid
    public function get_mch_uid($id)
    {
        $uid = $this->merchantsModel->where("id=$id")->getField('uid');
        return $uid;
    }
}