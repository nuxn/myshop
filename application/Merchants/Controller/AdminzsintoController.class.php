<?php

namespace Merchants\Controller;
use Common\Controller\AdminbaseController;
/**
 * 商户入驻资料填写
 * Class Merchants
 * @package Merchants\Controller
 */
class AdminzsintoController extends AdminbaseController
{
	protected $shopcates;
    protected $merchants;
    protected $merchants_zspay;
    protected $merchants_users;

    function _initialize()
    {
        parent::_initialize();
        $this->shopcates = M("merchants_cate");
        $this->merchants = M("merchants");
        $this->merchants_users = M("merchants_users");
        $this->merchants_zspay = M("merchants_zspay");
    }


    /**
     * 进件列表
     */
    public function index()
    {
        $user_phone = trim(I('user_phone'));
        $merchant_name = trim(I('merchant_name'));
        if ($user_phone) {
            $map['u.user_phone'] = $user_phone;
			$this->assign('user_phone',$user_phone);
        }
        if ($merchant_name) {
            $map['m.merchant_name'] = array('like',"%$merchant_name%");
			$this->assign('merchant_name',$merchant_name);
        }
        //$map['brash'] = 1;

        $zspay = $this->merchants_zspay->alias('z')
            ->join("left join __MERCHANTS__ m on z.merchant_id = m.id")
            ->join("left join __MERCHANTS_USERS__ u on m.uid = u.id")
            ->field("z.id,z.merchant_id,z.ul_mchid,m.merchant_name,u.user_phone,z.mch_pay_key")
            ->where($map)
            ->order("id desc")
            ->select();
			//dump($zspay);exit;
        $count = count($zspay);
        $page = $this->page($count, 20);
        $list = array_slice($zspay, $page->firstRow, $page->listRows);
        $this->assign("page", $page->show('Admin'));
        $this->assign("formget", array_merge($_GET, $_POST));
        $this->assign("zspay", $list);
        $this->display();
    }
	
    public function zsshow(){
        $id=$_GET['id'];
        $list=M('Merchants')->where("id='{$id}'")->find();
        $uid=$list['uid'];
        $phone=M('Merchants_users')->where("id='{$uid}'")->find();
        $this->assign('phone',$phone);
        $this->assign('list',$list);
        $this->assign('id',$id);
        $merchants_mpay_data=M('merchants_zspay')->where(array('merchant_id'=>$id))->find();
        $this->assign('data',$merchants_mpay_data);
        $this->display();
    }
    public function upload_into(){
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize   =     3145728 ;// 设置附件上传大小
        $upload->exts      =      array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath  =     C('_WEB_UPLOAD_'); // 设置附件上传根目录
        $upload->savePath  =     'msinto/'; // 设置附件上传（子）目录
        // 上传文件 
        $info   =   $upload->upload();
        if($info){
            $data['type']=1;
            if($info['id_card_img_f']){
                $data['back']=1;
                $data['id_card_img_f']=$info['id_card_img_f']['savepath'].$info['id_card_img_f']['savename'];
            }else if($info['id_card_img_b']){
                $data['back']=2;
                $data['id_card_img_b']=$info['id_card_img_b']['savepath'].$info['id_card_img_b']['savename'];
            }else if($info['license_img']){
                $data['back']=3;
                $data['license_img']=$info['license_img']['savepath'].$info['license_img']['savename'];
            }else if($info['annex_img1']){
                $data['back']=4;
                $data['annex_img1']=$info['annex_img1']['savepath'].$info['annex_img1']['savename'];
            }else if($info['annex_img2']){
                $data['back']=5;
                $data['annex_img2']=$info['annex_img2']['savepath'].$info['annex_img2']['savename'];
            }else if($info['annex_img3']){
                $data['back']=6;
                $data['annex_img3']=$info['annex_img3']['savepath'].$info['annex_img3']['savename'];
            }else if($info['annex_img4']){
                $data['back']=7;
                $data['annex_img4']=$info['annex_img4']['savepath'].$info['annex_img4']['savename'];
            }else if($info['annex_img5']){
                $data['back']=8;
                $data['annex_img5']=$info['annex_img5']['savepath'].$info['annex_img5']['savename'];
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
    public function upload_zspay(){
        $merchant_id=$_GET['merchant_id'];
        $data=$_POST;
        $data['payment_type2']=$_POST['payment_type1'];
        $data['payment_type3']=$_POST['payment_type1'];
        $data['payment_type8']=$_POST['payment_type7'];
        $data['payment_type9']=$_POST['payment_type7'];
        // var_dump($data);
        // exit();
        $row=M('merchants_zspay')->where(array('merchant_id'=>$merchant_id))->find();
        if($row){
            $re=M('merchants_zspay')->where(array('merchant_id'=>$merchant_id))->save($data);
            $this->success('保存成功!');
        }else{
            $data['merchant_id']=$merchant_id;
            $re=M('merchants_zspay')->add($data);
            $this->success('添加成功!');
        }
    }
}