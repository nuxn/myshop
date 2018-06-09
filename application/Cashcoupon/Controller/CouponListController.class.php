<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/24
 * Time: 18:24
 */

namespace CashCoupon\Controller;

use Common\Controller\AdminbaseController;
/**后台现金券列表管理控制器
 * CouponListClass Controller
 * @package CashCoupon\Controller
 */
class CouponListController extends AdminbaseController
{
    public $cashModel;
    public $userModel;
    public $userCash;

    public function _initialize() {
        empty($_GET['upw']) ? "" : session("__SP_UPW__", $_GET['upw']);//设置后台登录加密码
        parent::_initialize();
        $this->initMenu();
        $this->cashModel = M("cash");
        $this->userModel = M("merchants_users");
        $this->userCash = M("user_cash");
    }
    //现金券列表
    public function lists()
    {
        $count = $this->cashModel->count();
        $page = $this->page($count, 20);

        $field = 'id,title,up_price,price,start_time,end_time,type,description,add_time,ban';
        $this->cashModel->field($field);
        $this->cashModel->order("id DESC");
        $this->cashModel->limit($page->firstRow , $page->listRows);
        $data_lists = $this->cashModel->select();

        $now = time();
        $this->assign("now",$now);
        $this->assign("data_lists",$data_lists);
        $this->assign("page", $page->show('Admin'));
        $this->display();
    }

    //搜索结果
    public function res()
    {
        $map =array();
        $id = trim(I("id"));
        if($id){
            $map['id'] = array('eq',$id);
        }
        $title = trim(I("title"));
        if($title){
            $map['title'] = array('like',"%$title%");
        }
        $type = I("type");
        if($type != ""){
            $map['type'] = array('eq',$type);
        }
        $over = I("over");
        if($over){
            if($over == 1){
                $map['end_time'] = array('lt',time());
            }else if($over == 2){
                $map['end_time'] = array('egt',time());
            }
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
            $map['add_time'] = array('between',array(strtotime($start_time),strtotime($end_time)+86400));//包括结束日当天
        }

        $this->cashModel->where($map);
        $count = $this->cashModel->count();
        $page = $this->page($count, 20);
        $this->assign("page", $page->show('Admin'));

        $field = 'id,title,up_price,price,start_time,end_time,type,description,add_time,ban';
        $this->cashModel->field($field);
        $this->cashModel->where($map);
        $this->cashModel->order("id DESC");
        $this->cashModel->limit($page->firstRow , $page->listRows);
        $data_lists = $this->cashModel->select();
        $now = time();
        $this->assign("now",$now);
        $this->assign("data_lists", $data_lists);
        $this->display();
    }

    //现金券添加
    public function add()
    {
        $data = array();
        if (IS_POST) {
            $title = trim(I("title"));
            if(empty($title)){
                $this->error('标题不能为空');
            }else{
                $data['title'] = $title;
            }

            $up_price = trim(I("up_price"));
            if(empty($up_price)){
                $this->error('现金券面额不能为空');
            }else{
                $data['up_price'] = $up_price;
            }

            $price = trim(I("price"));
            if(empty($price)){
                $this->error('抵扣金额不能为空');
            }else if($price>$up_price){
                $this->error('抵扣金额不能大于现金券面额');
            }else{
                $data['price'] = $price;
            }

            $start_time = I("start_time");
            if(empty($start_time)){
                $this->error('开始时间不能为空');
            }else{
                $data['start_time'] = strtotime($start_time);
            }

            $end_time = I("end_time");
            if(empty($end_time)){
                $this->error('结束时间不能为空');
            }else if(strtotime($end_time)<strtotime($start_time)){
                $this->error('结束时间不能小于开始时间');
            }else{
                $data['end_time'] = strtotime($end_time)+86399;         //包括结束日当天
            }

            $type = I("type");
            if($type==""){
                $this->error('使用范围不能为空');
            }else{
                $data['type'] = $type;
            }

            $description = I("description");
            if($description){
                $data['description'] = $description;
            }else{
                if($type == 1){
                    $data['description'] = '此现金券仅限购买小程序(满'.number_format($up_price, 2, '.', '').'元减'.number_format($price, 2, '.', '').'元)';
                }else{
                    $data['description'] = '此现金券全场通用(满'.number_format($up_price, 2, '.', '').'元减'.number_format($price, 2, '.', '').'元)';
                }

            }
            $data['add_time'] = time();
            $data['admin_name'] = $_SESSION['name'];
            if ($this->cashModel->add($data)) $this->success(L('添加成功'), U("CouponList/lists"));
            else  $this->error("添加失败!");
        } else {
            $this->display();
        }
    }

    //优惠券详情页
    public function detail()
    {
        $id = I("id");
        $data = $this->cashModel->where(array("id" => $id))->find();
        $this->assign("data", $data);
        $this->display();
    }

    //编辑页
    public function edit()
    {
        if (IS_POST) {
            $data = I("");
            if (!$data['title']) $this->error("请输入现金券标题!");
            if (!$data['up_price']) $this->error("请输入现金券面额!");
            if (!$data['price']) $this->error("请输入现金券抵扣金额!");
            if ($data['price']>$data['up_price']) $this->error("抵扣金额不能大于现金券面额");
            //if ($data['send_nums']=='') $this->error("请输入发出量!");
            $data['start_time'] = strtotime($data['start_time']);
            $data['end_time'] = strtotime($data['end_time'])+86399;
            if($data['end_time']<$data['start_time']) $this->error("结束时间不能小于开始时间");
            if(empty($data['description'])){
                if($data['type'] == 1){
                    $data['description'] = '此现金券仅限购买小程序(满'.$data['up_price'].'元减'.$data['price'].'元)';
                }else{
                    $data['description'] = '此现金券全场通用(满'.$data['up_price'].'元减'.$data['price'].'元)';
                }
            }
            $res=$this->cashModel->where(array('id'=>$data['id']))->setField($data);
            if($res){
                $this->success("修改成功");
            }else{
                $this->error("修改失败");
            }
        }else{
            $id = I("id");
            $data = $this->cashModel->where(array("id" => $id))->find();
            $this->assign("data", $data);
            $this->display();
        }
    }

    //可发送的商家列表
    public function sendList()
    {
        $cashID = $_GET['id'];//获取现金券ID
        $this->assign("cashID",$cashID);
        $this->userModel->alias('u');
        $this->userModel->join("LEFT JOIN __MERCHANTS_ROLE_USERS__ ru ON u.id = ru.uid");
        $this->userModel->where(array("ru.role_id" => 3));
        $count = $this->userModel->count();
        $page = $this->page($count, 20);
        $this->assign("page", $page->show('Admin'));

        $this->userModel->alias('u');
        $this->userModel->join("LEFT JOIN __MERCHANTS_ROLE_USERS__ ru ON u.id = ru.uid");
        $this->userModel->where(array("ru.role_id" => 3));
        $this->userModel->order("u.id DESC");
        $this->userModel->limit($page->firstRow , $page->listRows);
        $field = 'u.id,u.user_name,u.user_phone,u.openid';
        $this->userModel->field($field);
        $data_lists = $this->userModel->select();
        $this->assign("data_lists", $data_lists);
        $this->display();
    }

    //可发送商家搜索结果
    public function sendListRes()
    {
        $map = array();
        $cashID = $_GET['cashID'];//获取现金券ID
        $this->assign("cashID",$cashID);

        $user_id = trim(I("id"));
        if($user_id){
            $map['u.id'] = array('eq',$user_id);
        }
        $user_name = trim(I("user_name"));
        if($user_name){
            $map['u.user_name'] = array('like',"%$user_name%");
        }
        $user_phone = trim(I("user_phone"));
        if($user_phone){
            $map['u.user_phone'] = array('like',"%$user_phone%");
        }
        $openid = trim(I("openid"));
        if($openid){
            $map['u.openid'] = array('like',"%$openid%");
        }

        $this->userModel->alias('u');
        $this->userModel->join("LEFT JOIN __MERCHANTS_ROLE_USERS__ ru ON u.id = ru.uid");
        $this->userModel->where(array("ru.role_id" => 3));
        $this->userModel->where($map);
        $count = $this->userModel->count();
        $page = $this->page($count, 20);
        $this->assign("page", $page->show('Admin'));

        $this->userModel->alias('u');
        $this->userModel->join("LEFT JOIN __MERCHANTS_ROLE_USERS__ ru ON u.id = ru.uid");
        $this->userModel->where(array("ru.role_id" => 3));
        $this->userModel->where($map);
        $this->userModel->order("u.id DESC");
        $this->userModel->limit($page->firstRow , $page->listRows);
        $field = 'u.id,u.user_name,u.user_phone,u.openid';
        $this->userModel->field($field);
        $data_lists = $this->userModel->select();
        $this->assign("data_lists", $data_lists);
        $this->display();

    }

    //现金券发送
    public function send()
    {
        //批量发送
        if(IS_POST){
            if(isset($_POST['sends'])){
                $ids=I("ids");
                $cashID = $_POST['cashID'];
                foreach ($ids as $k=>$v){
                    $userData = $this->userModel->field('id,user_name')->where(array('id'=>$v))->find();
                    $data = $this->cashModel->field('title,up_price,price,start_time,end_time,admin_name,type,description')->where(array('id'=>$cashID))->find();
                    $data['uid'] = $userData['id'];
                    $data['user_name'] = $userData['user_name'];
                    $data['add_time'] = time();
                    $data['cash_id'] = $cashID;
                    $data['admin_name'] = $_SESSION['name'];
                    $this->userCash->data($data)->add();
                }
                $this->success("批量发送成功");
            }else{
                $this->error("非法操作");
            }
        }else if(IS_GET){           //单独发送
            $uid = $_GET['id'];//商家ID
            $cashID = $_GET['cashID'];//现金券ID
            $userData = $this->userModel->field('id,user_name')->where(array('id'=>$uid))->find();
            $data = $this->cashModel->field('title,up_price,price,start_time,end_time,admin_name,type,description')->where(array('id'=>$cashID))->find();
            $data['uid'] = $userData['id'];
            $data['user_name'] = $userData['user_name'];
            $data['add_time'] = time();
            $data['cash_id'] = $cashID;
            $data['admin_name'] = $_SESSION['name'];
            $res = $this->userCash->data($data)->add();
            if($res){
                $this->success("发送成功");
            }else{
                $this->error("发送失败");
            }
        }else{
            $this->error("非法操作");
        }
    }
}