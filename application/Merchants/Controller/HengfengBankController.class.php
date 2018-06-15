<?php

namespace Merchants\Controller;

use Common\Controller\AdminbaseController;

/**
 * 恒丰久运昌进件
 * Class HengfengBankController
 * @package Merchants\Controller
 */
class HengfengBankController extends AdminbaseController
{
    public function index()
    {
        $this->display();
    }

    public function into()
    {
        $id=$_GET['id'];
        $list=M('Merchants')->where("id='{$id}'")->find();
        $uid=$list['uid'];
        $phone=M('Merchants_users')->where("id='{$uid}'")->find();
        $this->assign('phone',$phone);
        $this->assign('list',$list);
        $this->assign('id',$id);
        $merchants_data=M('merchants_hfjy')->where(array('mch_id'=>$id))->find();
        $this->assign('data',$merchants_data);
        $this->display();
    }

    public function upload_into(){
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize   =     3145728 ;// 设置附件上传大小
        $upload->exts      =      array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath  =     C('_WEB_UPLOAD_'); // 设置附件上传根目录
        $upload->savePath  =     'hfjyinto/'; // 设置附件上传（子）目录
        // 上传文件
        $info   =   $upload->upload();
        if($info){
            $data['type']=1;
            if($info['cert_correct']){
                $data['back']=1;
                $data['cert_correct']=$info['cert_correct']['savepath'].$info['cert_correct']['savename'];
            }else if($info['cert_opposite']){
                $data['back']=2;
                $data['cert_opposite']=$info['cert_opposite']['savepath'].$info['cert_opposite']['savename'];
            }else if($info['cert_meet']){
                $data['back']=3;
                $data['cert_meet']=$info['cert_meet']['savepath'].$info['cert_meet']['savename'];
            }else if($info['card_correct']){
                $data['back']=4;
                $data['card_correct']=$info['card_correct']['savepath'].$info['card_correct']['savename'];
            }else if($info['card_opposite']){
                $data['back']=5;
                $data['card_opposite']=$info['card_opposite']['savepath'].$info['card_opposite']['savename'];
            }else if($info['bl_img']){
                $data['back']=6;
                $data['bl_img']=$info['bl_img']['savepath'].$info['bl_img']['savename'];
            }else if($info['door_img']){
                $data['back']=7;
                $data['door_img']=$info['door_img']['savepath'].$info['door_img']['savename'];
            }else if($info['cashier_img']){
                $data['back']=8;
                $data['cashier_img']=$info['cashier_img']['savepath'].$info['cashier_img']['savename'];
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
}