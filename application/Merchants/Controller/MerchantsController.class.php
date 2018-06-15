<?php

namespace Merchants\Controller;
use Common\Controller\HomebaseController;
/**
 * 商户入驻资料填写
 * Class Merchants
 * @package Merchants\Controller
 */
class MerchantsController extends HomebaseController
{
    public function _initialize()
    {
        parent::_initialize();
        if(!in_array(ACTION_NAME, array('register','getMsmcode','reg_intro','reg_intro_ylzf','reg_intro_hd','finish'))){
            //判断用户是否已经注册
            if(!session('uid')){
                $this->error('请先注册',U('merchants/register'));
            }
        }
    }

    public function index()
    {
        if (I('post.imageID')) {
            session('positive_id_card_img', I('post.imageID'));
        }
        if (I('post.header_interior_img')) {
            session('header_interior_img', I('post.header_interior_img'));
        }

        if (I('post.business_license')) {
            session('business_license', I('post.business_license'));
        }

        if (I('post.radio1') || I('post.radio1') === '0') {
            session('isdoor_header', I('post.radio1'));
        }

        $this->display();
    }

    public function postData()
    {
        $uid = (int)session('uid');
        $merchantsModel = D('merchants');
        $referrer=I("referrer");
        $res=$this->checkReferrer($referrer);
        if($res['code']==0){
            $this->error($res['msg']);
        }

        if ($merchantsModel->create()) {
            $merchantsModel->uid = $uid;
            if ($merchantsModel->add()) {
                //$this->display('finish');
                //exit();
                $this->redirect(U('Merchants/finish'));
            }
        } else {
            $this->error($merchantsModel->getError());
        }
    }

    public function uploadFile()
    {
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize = 10485760;// 设置附件上传大小
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath = './data/upload/merchants/'; // 设置附件上传根目录
        // 上传单个文件
        $info = $upload->uploadOne($_FILES['file']);
        if (!$info) {// 上传错误提示错误信息
            $this->ajaxReturn(array('message' => $upload->getError(), 'status' => -1));
        } else {// 上传成功 获取上传文件信息
            $this->ajaxReturn(array('message' => 'merchants/' . $info['savepath'] . $info['savename'], 'status' => 1));
        }
    }

    public function certificate()
    {
        $this->display();
    }

    public function idcard()
    {
        $this->display();
    }

    //注册
    public function register()
    {
        session('positive_id_card_img',null);
        session('header_interior_img',null);
        session('business_license',null);
        session('isdoor_header',null);
        session("uid", null);
        $this->ajaxReturn(array("code" => 0, "msg" => "微信注册被禁用!!!"));
        if(IS_GET){
            $openid=I("get.openid");
            $res=$this->checkUserByOpenid($openid);
            if($res['code']==true){
                session("uid", $res['uid']);
                header("Location:http://sy.youngport.com.cn/index.php?g=Merchants&m=merchants&a=openway");
            }else{
                $this->assign('openid',$openid);
                $this->display();
            }
        }

        if(IS_POST ){
            $user_phone = I('post.user_phone', '', 'trim');
            $user_pwd = I('post.user_pwd', '', 'trim');
            $sms_code = I('sms_code', '', 'trim');
            $openid = I('openid', '', 'trim');
            if (empty($user_phone)) {
                $this->ajaxReturn(array("code" => 2, "msg" => "手机号为空"));
            }

            if (empty($user_pwd)) {
                $this->ajaxReturn(array("code" => 3, "msg" => "密码为空"));
            }

            if (empty($sms_code)) {
                $this->ajaxReturn(array("code" => 5, "msg" => "短信验证码为空"));
            }

            if ($sms_code != S('sms_msg')) {
                $this->ajaxReturn(array("code" => 6, "msg" => "短信验证码不正确", "code5" => $sms_code . "==" . S('sms_msg')));
            }

            //检查手机号是否已注册
            if ($this->checkUser($user_phone) == true) {
                $this->ajaxReturn(array("code" => 4, "msg" => "手机号已被注册"));
            }

            $data["user_phone"] = $user_phone;
            $data["user_pwd"] = md5($user_pwd);
            $data['ip_address'] = get_client_ip();
            $data['add_time'] = time();
            $data['openid']=$openid;

            $data = M("merchants_users")->add($data);
            if ($data) {
                session("uid", $data);
                $role_arr=array();
                $role_arr['uid']=$data;
                $role_arr['role_id']='3'; // 商户角色
                $role_arr['add_time']=time();
                M("merchants_role_users")->add($role_arr);
                $this->ajaxReturn(array("code" => 1, "msg" => "注册成功"));
            } else {
                $this->ajaxReturn(array("code" => 0, "msg" => "注册失败"));
            }
        }
    }

    /**
     * 发短信
     */
    public function getMsmcode()
    {
        if(IS_POST) {
            $phone = I("post.phone", '', 'trim');
            if (empty($phone)) {
                $this->ajaxReturn(array("code" => "4", "msg" => "手机号码为空"));
            }
            Vendor("SMS.CCPRestSmsSDK");
            $config_arr = C('SMS_CONFIG'); // 读取短信配置
            $rest = new \REST($config_arr['serverIP'], $config_arr['serverPort'], $config_arr['softVersion']);
            $rest->setAccount($config_arr['accountSid'], $config_arr['accountToken']);
            $rest->setAppId($config_arr['appId']);

            $sms_msg = rand(100000, 999999); //生成短信信息
            S('sms_msg', $sms_msg, 600);// 缓存$str数据3600秒过期
            $result = $rest->sendTemplateSMS($phone, array($sms_msg, '5'), $config_arr['RegTemplateId']); // 发送模板短信
            if ($result == NULL) {
                $this->ajaxReturn(array("code" => 0, "msg" => "result error!"));
            }
            if ($result->statusCode != 0) { // 错误
                $this->ajaxReturn(array("code" => $result->statusCode, "msg" => $result->statusMsg));
            } else {
                $this->ajaxReturn(array("code" => "1", "msg" => "短信发送成功"));
            }

        }else{
            $this->ajaxReturn(array("code" => "3", "msg" => "没传进参数错误"));
        }

    }

    function openway(){

        //检查用户是否已经提交过资料
        $user=$this->checkMerchantApplicate();
        if($user){
            $this->assign("url",U("Merchants/finish"));
        }else{
            $this->assign("url",U("Merchants/index"));
        }
        $this->display();
    }

    public  function finish(){
        $this->display();
    }

    /**
     * @param $phone
     * @return bool
     * 手机号码是否已经注册
     */
    private function checkUser($phone){
        $model=M("merchants_users");
        $data=$model->where(array("user_phone"=>$phone))->count();
        if($data >0){
            return true;
        }else{
            return false;
        }
    }

    /**
     * @param $openid
     * @return bool
     *  根据openid 判断用户是否存在
     */
    private function checkUserByOpenid($openid){
        $model=M("merchants_users");
        $data=$model->field(array('id'))->where(array("openid"=>$openid))->find();

        if(!empty($data)){
            return array('code'=>true,'uid'=>$data['id']);
        }else{
            return array('code'=>false,'uid'=>'');
        }
    }

    /**
     * @return bool
     * 判断是否已经填写商户资料
     */
    private function checkMerchantApplicate(){
        $model=M("merchants");
        $uid=(int)session('uid');
        $count=$model->where(array('uid'=>$uid))->count();

        if(intval($count) >0){
           return true;
        }else{
            return false;
        }
    }


    /**
     * @param $referrer
     * @return array
     * @instruction 检查推荐人是否在用户表，存在则修改用户的上级，不存在则返回
     */
    private  function  checkReferrer($referrer){
        $model=M("merchants_users");
        $uid=(int)session('uid');
        $data=$model->field(array('id'))->where(array("user_phone"=>$referrer))->find();
        if($data){
            $res=$model->where(array('id'=>$uid))->setField(array('pid'=>$data['id']));
            if($res!==false){
                return array('code'=>1,'msg'=>'修改上级成功');
            }
        }else{
            return array('code'=>0,'msg'=>'推荐人不存在或者错误');
        }
    }


    public function reg_intro(){
        $this->assign('company', "洋仆淘科技有限公司");
        $this->display();
    }

    public function reg_intro_ylzf(){
        $this->assign('company', "深圳市云来电子商务有限公司");
        $this->display("reg_intro");
    }
	
    public function reg_intro_hd(){
        $this->display();
    }

}