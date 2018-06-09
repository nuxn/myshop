<?php
namespace Merchants\Controller;

use Common\Controller\AdminbaseController;

/**
 * 商户进件(入驻)
 * 获取微众分配的商户ID用于支付
 * Class UpwzadminController
 * @package Merchants\Controller
 */
class PfbankintoController extends AdminbaseController
{
    protected $shopcates;
    protected $merchants;
    protected $merchants_pfpay;
    protected $merchants_users;

    function _initialize()
    {
        parent::_initialize();
        $this->shopcates = M("merchants_cate");
        $this->merchants = M("merchants");
        $this->merchants_users =M("merchants_users");
        $this->merchants_pfpay = M("merchants_pfpay");
    }


    /**
     * 进件列表
     */
    public function index()
    {
        $merchant_name = I('merchant_name');
        if($merchant_name){
            $map['m.merchant_name'] = array('like', "%{$merchant_name}%");
            $formget['merchantAlis'] = $merchant_name;
        }
        $count = $this->merchants_pfpay->join('w left join ypt_merchants m on w.merchant_id=m.id')->where($map)->order('w.id desc')->count();

        $page = $this->page($count, 20);
        $xypays = $this->merchants_pfpay
            ->field('w.id,w.merchant_id,w.mch_id,m.merchant_name,w.wx_code,w.ali_code')
            ->join('w left join ypt_merchants m on w.merchant_id=m.id')
            ->where($map)
            ->order('w.merchant_id desc')
            ->limit($page->firstRow , $page->listRows)
            ->select();
        $this->assign("page", $page->show('Admin'));
        $this->assign("xypays",$xypays);
        $this->assign("formget",$formget);
        $this->display();
    }


    /**
     * 添加进件
     */
    public function add()
    {
        if (IS_POST) {
            $data = I("post.");
            if(!$data['merchant_id']){
                $this->error('参数不全');
            }
            $check = $this->merchants_pfpay->where(array('merchant_id' => $data['merchant_id']))->find();
            $find = $this->merchants->where(array('id'=>$data['merchant_id']))->find();
            if($check){
                $this->error('已存在');
            }
            if(!$find){
                $this->error('系统中不存在该商户');
            }
            $res = $this->merchants_pfpay->add($data);
            if($res){
                $this->redirect(U('Pfbankinto/index'));
            } else{
                $this->success('未作改动');
            }
        }else{
            $merchant_id=$_GET['id'];
            //$merchant_id=53;
            $list=M('Merchants')->where("id='{$merchant_id}'")->find();
            $uid=$list['uid'];
            $phone=M('Merchants_users')->where("id='{$uid}'")->find();
            $this->assign('phone',$phone);
            $this->assign('list',$list);
            $this->assign('id',$merchant_id);
            $merchants_mpay_data=M('merchants_mdaypay')->where(array('merchant_id'=>$merchant_id))->find();
            $this->assign('data',$merchants_mpay_data);
            $this->display("add1");
        }
    }

    /**
     * 编辑
     */
    public function edit()
    {
        if(IS_POST){
            $data = I('post.');
            $id = I('id');
            if(!$data['merchant_id'] || !$data['mch_id']){
                $this->error('参数不全');
            }
            unset($data['id']);
            $this->merchants_pfpay->where(array('id'=>$id))->save($data);
            $this->redirect(U('Pfbankinto/index'));
        } else {
            $id = I('id');
            $info = $this->merchants_pfpay->where(array('id' => $id))->find();
            $this->assign('data', $info);
            $this->assign('id', $id);
            $this->display();
        }
    }

}
