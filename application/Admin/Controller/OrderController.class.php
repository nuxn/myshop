<?php
namespace Admin\Controller;
use Common\Controller\AdminbaseController;

class OrderController extends AdminbaseController{

    public $orderModel;
    public $express;
    public $order_goods;
    public $express_log;

	public function _initialize() {
        empty($_GET['upw']) ? "" : session("__SP_UPW__", $_GET['upw']);//设置后台登录加密码
        parent::_initialize();
        $this->initMenu();
        $this->orderModel = M("order");
        $this->express = M("express");
        $this->order_goods = M("order_goods");
        $this->express_log = M("express_log");
	}

	//订单列表
	public function lists()
    {
        $count = $this->orderModel->count();
        $page = $this->page($count, 20);
        $this->assign("page", $page->show('Admin'));

        $this->orderModel->alias("o");
        $this->orderModel->join("LEFT JOIN __MERCHANTS_USERS__ mu ON o.user_id = mu.id");
        $this->orderModel->join("LEFT JOIN __SCREEN_MEM__ sm ON o.mid = sm.id");
        $this->orderModel->join("LEFT JOIN __EXPRESS__ e ON o.shipping_style = e.id");
        $this->orderModel->order("o.order_id DESC");
        $this->orderModel->limit($page->firstRow , $page->listRows);
        $field = 'o.order_id,o.order_sn,o.add_time,o.order_amount,o.total_amount,o.order_status,o.paystyle,mu.user_name,sm.openid,sm.nickname,e.e_name';
        $this->orderModel->field($field);
        $data_lists = $this->orderModel->select();
        $this->assign("data_lists", $data_lists);

        $expressList = $this->express->field("id,e_name")->select();
        $this->assign("expressList",$expressList);
        $this->display();
    }

	//订单详情
	public function detail(){
        $id = I("id");
        $this->orderModel->alias("o");
        $this->orderModel->join("LEFT JOIN __ORDER_GOODS__ og ON o.order_id = og.order_id");
        $this->orderModel->join("LEFT JOIN __MERCHANTS_USERS__ mu ON o.user_id = mu.id");
        $this->orderModel->join("LEFT JOIN __SCREEN_MEM__ sm ON o.mid = sm.id");
        $this->orderModel->join("LEFT JOIN __EXPRESS__ e ON o.shipping_style = e.id");
        $this->orderModel->join("LEFT JOIN __EXPRESS_LOG__ el ON o.order_id = el.order_id");
        $field = 'o.user_note,el.express_sn,o.order_id,o.order_sn,og.goods_name,og.goods_price,og.goods_num,o.total_amount,o.order_amount,o.order_benefit,o.add_time,sm.openid,mu.user_name,sm.nickname,o.order_status,o.province,o.city,o.district,o.twon,o.address,o.consignee,o.mobile,o.paystyle,o.pay_time,e.e_name,o.shipping_time,o.shipping_style,o.confirm_time';
        $this->orderModel->field($field);
        $data = $this->orderModel->where(array("o.order_id" => $id))->find();
        $this->assign("data", $data);

        $goodsData = $this->order_goods->field("goods_name,goods_num,goods_price,spec_key_name,goods_id,goods_img")->where(array("order_id" => $id))->select();
        foreach($goodsData as $k => $v){
            $goodsData[$k]['total_price'] = $goodsData[$k]['goods_num'] * $goodsData[$k]['goods_price'];
        }
        $this->assign("goodsData",$goodsData);

        $this->display();
    }


    //搜索结果
    public function res()
    {
        $map =array();
        $this->orderModel->alias("o");
        $this->orderModel->join("LEFT JOIN __MERCHANTS_USERS__ mu ON o.user_id = mu.id");
        $this->orderModel->join("LEFT JOIN __SCREEN_MEM__ sm ON o.mid = sm.id");
        $this->orderModel->join("LEFT JOIN __EXPRESS__ e ON o.shipping_style = e.id");

        $order_id = trim(I("order_id"));
        if($order_id){
            $map['o.order_id'] = array('eq',$order_id);
        }
        $order_sn = trim(I("order_sn"));
        if($order_sn){
            $map['o.order_sn'] = array('like',"%$order_sn%");
        }
        $nickname = trim(I("nickname"));
        if($nickname){
            $map['sm.nickname'] = array('like',"%$nickname%");
        }
        $user_name = trim(I("user_name"));
        if($user_name){
            $map['mu.user_name'] = array('like',"%$user_name%");
        }
        $order_status = I("order_status");
        if($order_status){
            $map['o.order_status'] = array('eq',$order_status);
        }
        $paystyle = I("paystyle");
        if($paystyle){
            $map['o.paystyle'] = array('eq',$paystyle);
        }
        $shipping_style = I("shipping_style");
        if($shipping_style != ""){
            $map['o.shipping_style'] = array('eq',$shipping_style);
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
            $map['o.add_time'] = array('between',array(strtotime($start_time),strtotime($end_time)+86400));
        }

        $this->orderModel->where($map);
        $count = $this->orderModel->count();
        $page = $this->page($count, 20);
        $this->assign("page", $page->show('Admin'));

        $this->orderModel->alias("o");
        $this->orderModel->join("LEFT JOIN __MERCHANTS_USERS__ mu ON o.user_id = mu.id");
        $this->orderModel->join("LEFT JOIN __SCREEN_MEM__ sm ON o.mid = sm.id");
        $this->orderModel->join("LEFT JOIN __EXPRESS__ e ON o.shipping_style = e.id");
        $this->orderModel->where($map);
        $field = 'o.order_id,o.order_sn,o.add_time,o.order_amount,o.total_amount,o.order_status,o.paystyle,mu.user_name,sm.openid,sm.nickname,e.e_name';
        $this->orderModel->field($field);
        $this->orderModel->order("o.order_id DESC");
        $this->orderModel->limit($page->firstRow , $page->listRows);
        $data_lists = $this->orderModel->select();
        $this->assign("data_lists", $data_lists);

        $expressList = $this->express->field("id,e_name")->select();
        $this->assign("expressList",$expressList);
        $this->display();
    }

    //发货页
    public function deliver()
    {
        if(IS_POST){
            $data = array();
            $order_id = I("order_id");
            $express_name = I("express_name");
            if($express_name == ""){
                $this->error("请选择配送方式");
            }else{
                $data['express_name'] = $express_name;
            }
            $express_sn = trim(I("express_sn"));
            if($express_sn == "" && $express_name != "1"){
               $this->error("请填写快递单号");
            }else{
                $data['express_sn'] = $express_sn;
            }
            $data['order_id'] = $order_id;
            $data['add_time'] = time();
            $res = $this->express_log->add($data);
            if($res){
                $orderData = array('order_status'=>'3','shipping_style'=>$express_name,'shipping_time'=>time());
                $this->orderModel->where(array("order_id" => $order_id))->setField($orderData);
                $this->success(L('发货成功!'), U("Order/lists"));
            }else{
                $this->error("发货失败!");
            }

        }else if(IS_GET){
            $order_id = I("id");
            $field = 'order_id,province,city,district,twon,address,consignee,mobile';
            $data = $this->orderModel->field($field)->where(array("order_id" => $order_id))->find();
            $this->assign("data",$data);

            $expressList = $this->express->field("id,e_name")->select();
            $this->assign("expressList",$expressList);
            $this->display();
        }else{
            $this->error("禁止非法操作");
        }
    }
}