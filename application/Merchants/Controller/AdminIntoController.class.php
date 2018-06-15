<?php

namespace Merchants\Controller;
use Common\Controller\AdminbaseController;
/**
 * 商户入驻资料填写
 * Class Merchants
 * @package Merchants\Controller
 */
class AdminIntoController extends AdminbaseController
{
    public function index(){
        $sql="select ypt_merchants_into.id,merchant_name,uid,wpay,mpay,nowpay from ypt_merchants,ypt_merchants_into where ypt_merchants_into.merchants_id=ypt_merchants.uid order by ypt_merchants_into.id desc";
        $upwzs=M()->query($sql);
        $count=count($upwzs);
        $page = $this->page($count, 20);
        $list=array_slice($upwzs,$page->firstRow,$page->listRows);
        $this->assign("page", $page->show('Admin'));
        $this->assign("list",$list);
        $this->display();
    }
    public function check_into(){
        $id=I('id');
        M('merchants_mpay')->where(array('id'=>$id))->save(array('into_type'=>3));
        $this->success('审核成功');
    }
    public function update_nowpay(){
        $id=$_POST['id'];
        $data['nowpay']=$_POST['nowpay'];
        M('Merchants_into')->where("id='{$id}'")->save($data);
        $this->success("修改成功");
    }
    public function check_wpay(){
        $this->display();
    }
    public function check_mpay(){
        $id=$_GET['id'];
        $list=M('Merchants')->where("id='{$id}'")->find();
        $uid=$list['uid'];
        $phone=M('Merchants_users')->where("id='{$uid}'")->find();
        $this->assign('phone',$phone);
        $this->assign('list',$list);
        $this->assign('id',$id);
        $merchants_mpay_data=M('merchants_mpay')->where("uid='{$id}'")->find();
        $this->assign('data',$merchants_mpay_data);
        $this->display();
    }

    public function check_hdmpay(){
        $id=$_GET['id'];
        $list=M('Merchants')->where("id='{$id}'")->find();
        $uid=$list['uid'];
        $phone=M('Merchants_users')->where("id='{$uid}'")->find();
        $this->assign('phone',$phone);
        $this->assign('list',$list);
        $this->assign('id',$id);
        $merchants_mpay_data=M('ms_hd_into')->where("uid='{$id}'")->find();
        $this->assign('data',$merchants_mpay_data);
        $this->display();
    }
    public function into_success(){
        $id=I('uid');
        $data['into_type']=3;
        M('merchants_mpay')->where(array('id'=>$id))->save();
        return 1;
    }
    public function upload_mpay(){
        $data['expanderCd']=I('expanderCd');
        $data['mchtName']=I('mchtName');
        $data['mchtShortName']=I('mchtShortName');
        $data['mchtTypename']=I('mchtTypename');
        $data['mchtType']=I('mchtType');
        $data['parentMchtId']=I('parentMchtId');
        $data['gszcName']=I('gszcName');
        $data['bizLicense']=I('bizLicense');
        $data['legalIdExpiredTime']=I('legalIdExpiredTime');
        $data['IdNo']=I('IdNo');
        $data['province']=I('province');
        $provinceData=M('ms_address')->where(array("id"=>$data['province']))->find();
        $data['provincename']=$provinceData['city_name'];
        $data['city']=I('city');
        $cityData=M('ms_address')->where(array("id"=>$data['city']))->find();
        $data['cityname']=$cityData['city_name'];
        $data['area']=I('area');
        $areaData=M('ms_address')->where(array("id"=>$data['area']))->find();
        $data['areaname']=$areaData['city_name'];
        $data['accountType']=I('accountType');
        $data['account']=I('account');
        $data['accountName']=I('accountName');
        $data['bankName']=I('bankName');
        $data['bankCode']=I('bankCode');
        $data['openBranchname']=I('openBranchname');
        $data['openBranch']=I('openBranch');
        $data['contactName']=I('contactName');
        $data['contactMobile']=I('contactMobile');
        $data['contactEmail']=I('contactEmail');
        $data['mchtLevel']=I('mchtLevel');
        $data['openType']=I('openType');
        $data['openName']=I('openName');
        $data['qqcode']=I('qqcode');
        $data['qqcodefen']=I('qqcodefen');
        $data['weicode']=I('weicode');
        $data['weicodefen']=I('weicodefen');
        $data['alipaycode']=I('alipaycode');
        $data['alipaycodefen']=I('alipaycodefen');
        $data['mchtAddr']=I('mchtAddr');
        $data['legalIdExpiredTime']=I('legalIdExpiredTime');
        $data['uid']=I('uid');
        $merchant_Data=M('merchants_mpay')->where(array('uid'=>$data['uid']))->find();

        if($merchant_Data){
            $id=$merchant_Data['id'];
            M('merchants_mpay')->where("id='{$id}'")->save($data);
        }else{
            M('merchants_mpay')->add($data);
        }

        $this->success('保存成功');

    }
    public function upload_hdmpay(){
        $data['expanderCd']=I('expanderCd');
        $data['mchtName']=I('mchtName');
        $data['mchtShortName']=I('mchtShortName');
        $data['mchtTypename']=I('mchtTypename');
        $data['mchtType']=I('mchtType');
        $data['parentMchtId']=I('parentMchtId');
        $data['gszcName']=I('gszcName');
        $data['bizLicense']=I('bizLicense');
        $data['legalIdExpiredTime']=I('legalIdExpiredTime');
        $data['IdNo']=I('IdNo');
        $data['province']=I('province');
        $provinceData=M('ms_address')->where(array("id"=>$data['province']))->find();
        $data['provincename']=$provinceData['city_name'];
        $data['city']=I('city');
        $cityData=M('ms_address')->where(array("id"=>$data['city']))->find();
        $data['cityname']=$cityData['city_name'];
        $data['area']=I('area');
        $areaData=M('ms_address')->where(array("id"=>$data['area']))->find();
        $data['areaname']=$areaData['city_name'];
        $data['accountType']=I('accountType');
        $data['account']=I('account');
        $data['accountName']=I('accountName');
        $data['bankName']=I('bankName');
        $data['bankCode']=I('bankCode');
        $data['openBranchname']=I('openBranchname');
        $data['openBranch']=I('openBranch');
        $data['contactName']=I('contactName');
        $data['contactMobile']=I('contactMobile');
        $data['contactEmail']=I('contactEmail');
        $data['mchtLevel']=I('mchtLevel');
        $data['openType']=I('openType');
        $data['openName']=I('openName');
        $data['qqcode']=I('qqcode');
        $data['qqcodefen']=I('qqcodefen');
        $data['weicode']=I('weicode');
        $data['weicodefen']=I('weicodefen');
        $data['alipaycode']=I('alipaycode');
        $data['alipaycodefen']=I('alipaycodefen');
        $data['mchtAddr']=I('mchtAddr');
        $data['legalIdExpiredTime']=I('legalIdExpiredTime');
        $data['uid']=I('uid');
        $data['appid']=I('appid');
        $data['secret']=I('secret');
        $merchant_Data=M('ms_hd_into')->where(array('uid'=>$data['uid']))->find();

        if($merchant_Data){
            $id=$merchant_Data['id'];
            M('ms_hd_into')->where("id='{$id}'")->save($data);
        }else{
            M('ms_hd_into')->add($data);
        }

        $this->success('保存成功');

    }
    public function test(){
        $uri = "http://a.ypt5566.com/paytest/webdoc/dataApi.php?act=1&mod=3";
        $data = array (
         'name' => 'tanteng'
        );
        $ch = curl_init ();
        // print_r($ch);
        curl_setopt ( $ch, CURLOPT_URL, $uri );
        curl_setopt ( $ch, CURLOPT_POST, 1 );
        curl_setopt ( $ch, CURLOPT_HEADER, 0 );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $data );
        $return = curl_exec ( $ch );
        curl_close ( $ch );
        print_r($return);
    }
}
