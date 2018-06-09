<?php
namespace Api\Controller;

use Common\Controller\ApibaseController;
use Think\Controller;

class  AddagentController extends  ApibaseController
{

    public function index()
    {
//        $id=43;
        $this->checkLogin();
        $id = $this->userInfo['uid'];
        $users = M("merchants_users")->alias("m")
            ->join("right join __MERCHANTS_ROLE_USERS__ ur on ur.uid = m.id")
            ->where("agent_id=$id And ur.role_id =6")
            ->field("m.id,m.user_name,m.user_phone,m.agent_id,ur.role_id")
            ->select();
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $users));
    }

    public function add()
    {

//        if (IS_POST) {
//            $this->ajaxReturn(dump($_POST));
//            $id=43;
        $this->checkLogin();
        $id = $this->userInfo['uid'];
        $agent_mode = I("agent_mode");  // 代理类型
//            $this->ajaxReturn($_GET);
        if ($agent_mode == "") {
            $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("status" => 2, "data" => '请添加代理类型')));
        }

        $agent_name = I("agent_name");
        if ($agent_name == "") {
            $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("status" => 3, "data" => '请添加代理名称')));

        }
        $agent_phone = I("agent_phone");

        if ($agent_phone == "") {

            $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("status" => 4, "data" => '请先添加代理手机号')));
        } else {
            $ab = M("merchants_users")->where("user_phone = $agent_phone")->find();
            if ($ab != null) {
                $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("status" => 5, "data" => '手机号已经存在')));
            }
        }

        $province = I("province");
        if ($province == "") {
            $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("status" => 6, "data" => '--请选择省份--')));
        }

        $city = I("city");
        if ($city == "") {
            $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("status" => 7, "data" => '--请选择城市--')));
        }

        $county = I("county");
        if ($county == "") {
            $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("status" => 8, "data" => '--请选择地区--')));
        }
        $address = I("address");
        if ($address == "") {
            $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("status" => 9, "data" => '请填写详细地址')));
        }
        $pid = I("pid"); // 发展商户的id
        if ($pid == "") {
            $agent_type = 0;  //新增类型
            $pid = $id;
        } else {
            $agent_type = 1;
        }
        $user = M("merchants_users")->where("id=$pid")->find();
        $referrer = $user['user_name'];  //推荐人

        $user_one = array(
            'user_name' => $agent_name,
            'user_phone' => $agent_phone,
            'user_pwd' => md5(123456),
            'ip_address' => get_client_ip(),
            'agent_id' => $id,
            'pid' => $pid,
            'add_time' => time(),
        );
//            添加用户表
        $u_id = M("merchants_users")->add($user_one);
        if ($u_id) {
            $agent_one = array(
                'uid' => $u_id,
                'agent_name' => $agent_name,
                'province' => $province,
                'city' => $city,
                'county' => $county,
                'address' => $address,
                'agency_business' => -1,
                'agent_type' => $agent_type,
                'agent_mode' => $agent_mode,
                'referrer' => $referrer,
                'status' => 1,
                'add_time' => time(),
            );
            //                添加代理商表
            $agent_id = M("merchants_agent")->add($agent_one);
            if ($agent_id) {
                $role_one = array(
                    'uid' => $u_id,
                    'role_id' => 2,
                    'add_time' => time(),
                );
//                    添加角色权限表
                M("merchants_role_users")->add($role_one);
            }
            $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("status" => 1, "data" => '添加成功')));

        }
    }
//    }

    /**
     * 下级商户信息、下级代理信息、代理资料
     */
    public function profile()
    {
        $id = I("post.id", 0);
        $result = '';
        if ($id) {//下级商户信息、下级代理信息
            $role_id = M("merchants_role_users")->where(array("uid" => $id))->getField("role_id");
            if (in_array($role_id, array(2, 3))) {
                if ($role_id == '2') $result = D("MerchantsAgent")->getAgentInfo($id);
                else $result = D("MerchantsAgent")->getMerchantsInfo($id);
            }
        } else {//自身资料
            if ($this->userInfo['role_id'] == '2') {//代理资料
                $result = D("MerchantsAgent")->getAgentInfo($this->userId);

            } else if ($this->userInfo['role_id'] == '3') {//商家
                $result = D("MerchantsAgent")->getMerchantsInfo($this->userId);
            }

        }
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("data" => array($result))));
    }

    public function addMerchants()
    {
        if (IS_POST) {
            $this->checkLogin();
            $merchantsModel = M('merchants');
            $uid = I("uid");

            if(empty($uid)){
                $this->ajaxReturn(array("code" => "error", "msg" =>'用户不存在'));
            }
            $data['uid']=$uid;

            $merchant_name=I("merchant_name");
            if (empty($merchant_name)){
                $this->ajaxReturn(array("code" => "error", "msg" =>'商户名称不能为空'));
            }
            $data['merchant_name']=$merchant_name;

            $province=I("province");
            if (empty($province)){
                $this->ajaxReturn(array("code" => "error", "msg" =>'省不能为空'));
            }
            $data['province']=$province;

            $city=I("city");
            if (empty($city)){
                $this->ajaxReturn(array("code" => "error", "msg" =>'市不能为空'));
            }
            $data['city']=$city;

            $county=I("county");
            if (empty($county)){
                $this->ajaxReturn(array("code" => "error", "msg" =>'县不能为空'));
            }
            $data['county']=$county;

            $address=I("address");
            if (empty($address)){
                $this->ajaxReturn(array("code" => "error", "msg" =>'详细地址不能为空'));
            }
            $data['address']=$address;

            $industry=I("industry");
            if (empty($industry)){
                $this->ajaxReturn(array("code" => "error", "msg" =>'行业不能为空'));
            }
            $data['industry']=$industry;

            $operator_name=I("operator_name");
            if (empty($operator_name)){
                $this->ajaxReturn(array("code" => "error", "msg" =>'经营者姓名不能为空'));
            }
            $data['operator_name']=$operator_name;
            $id_number=I("id_number");
            if (empty($id_number)){
                $this->ajaxReturn(array("code" => "error", "msg" =>'身份证不能为空'));
            }
            $data['id_number']=encrypt($id_number);

            $account_type=I("account_type");
            if (!isset($account_type)){
                $this->ajaxReturn(array("code" => "error", "msg" =>'账户类型不能为空'));
            }
            $data['account_type']=$account_type;

            $id_number=I("id_number");
            if (empty($id_number)){
                $this->ajaxReturn(array("code" => "error", "msg" =>'身份证不能为空'));
            }
            $data['id_number']=$id_number;
            $user_phone=I("referrer","trim");
            if(empty($user_phone)){
//                手机号码的检测
                $user['agent_id']=1;
                $user['pid']=0;
                $data['referrer']=13128898154;
            }else{
                if(!isMobile($user_phone))$this->ajaxReturn(array("code" => "error", "msg" =>'你的手机号码不存在'));
                $p_id=M("merchants_users")->where("user_phone=$user_phone")->getField("id");
                if(!$p_id){$this->ajaxReturn(array("code" => "error", "msg" =>'你添加的上级手机号码不存在'));}
                $role_id=M("merchants_role_users")->where("uid=$p_id")->getField("role_id");
                if($role_id ==3||$role_id ==7){
                    $this->ajaxReturn(array("code" => "error", "msg" =>'不能填写收银员或者商户的手机号码'));
                }
                if($role_id == 1||$role_id == 4||$role_id == 5){
                    $user['agent_id']= 0;
                    $user['pid']=$p_id;
                }
                if($role_id == 2){
                    $user['agent_id']=$p_id;
                    $user['pid']=$p_id;
                }
                if($role_id == 6){
                    $u_id=M("merchants_users")->where("id=$p_id")->getField("pid");
                    $user['agent_id']=$u_id;
                    $user['pid']=$p_id;
                }
//                商户的推荐人,作为备用
                $data['referrer']=$user_phone;
            }
            $account_name=I("account_name");

            if (empty($account_name)){
                $this->ajaxReturn(array("code" => "error", "msg" =>'账户名称/开户名称不能为空'));
            }
            $data['account_name']=$account_name;
            $bank_account=I("bank_account");
            if (empty($bank_account)){
                $this->ajaxReturn(array("code" => "error", "msg" =>'开户银行不能为空'));
            }
            $data['bank_account']=$bank_account;

            $branch_account=I("branch_account");
            if (empty($branch_account)){
                $this->ajaxReturn(array("code" => "error", "msg" =>'开户支行不能为空'));
            }
            $data['branch_account']=$branch_account;

            $bank_account_no=I("bank_account_no");
            if (empty($bank_account_no)){
                $this->ajaxReturn(array("code" => "error", "msg" =>'银行账号不能为空'));
            }
            $data['bank_account_no'] = encrypt($bank_account_no);

//            $referrer=I("referrer");
//            if(!empty($referrer)){
//                $referrer_res=$this->checkReferrer($referrer);
//                if($referrer_res['code']==0){
//                    $this->ajaxReturn(array("code" => "error", "msg" =>$referrer_res['msg']));
//                }
//                $data['referrer']=$referrer;
//            }

            $data['status']=0;

            $count_data=$merchantsModel->field("id")->where(array('uid'=>$uid))->find();
            if (!empty($count_data)) {
                $res=$merchantsModel->where(array('id'=>$count_data['id']))->save($data);
                if ($res !== false) {
                    if(M("merchants_users")->where("id=$uid")->find()) M("merchants_users")->where("id=$uid")->save($user);
                    $this->ajaxReturn(array("code" => "success", "msg" => L('MERCHANTS_ADD_SUCCESS')));
                } else {
                    $this->ajaxReturn(array("code" => "success", "msg" => L('MERCHANTS_ADD_SUCCESS')));
                    //$this->ajaxReturn(array("code" => "error", "msg" =>  L('MERCHANTS_ADD_FAIL')));
                }
            } else {
                $data['add_time']=time();
                if ($merchantsModel->add($data)) {
                    $this->ajaxReturn(array("code" => "success", "msg" => L('MERCHANTS_ADD_SUCCESS')));
                } else {
                    $this->ajaxReturn(array("code" => "error", "msg" => L('MERCHANTS_ADD_FAIL')));
                }
            }
        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => L('HACKER_MSG')));
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

}