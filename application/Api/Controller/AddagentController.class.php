<?php

namespace Api\Controller;

use Common\Controller\ApibaseController;
use Think\Controller;

class  AddagentController extends ApibaseController
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
            $this->ajaxReturn(array("code" => "error", "msg" => "请添加代理类型", "data" => array("status" => 2, "data" => '请添加代理类型')));
        }

        $agent_name = I("agent_name");
        if ($agent_name == "") {
            $this->ajaxReturn(array("code" => "error", "msg" => "请添加代理名称", "data" => array("status" => 3, "data" => '请添加代理名称')));

        }
        $agent_phone = I("agent_phone");

        if ($agent_phone == "") {

            $this->ajaxReturn(array("code" => "error", "msg" => "请先添加代理手机号", "data" => array("status" => 4, "data" => '请先添加代理手机号')));
        } else {
            $ab = M("merchants_users")->where("user_phone = $agent_phone")->find();
            if ($ab != null) {
                $this->ajaxReturn(array("code" => "error", "msg" => "手机号已经存在", "data" => array("status" => 5, "data" => '手机号已经存在')));
            }
        }

        $province = I("province");
        if ($province == "") {
            $this->ajaxReturn(array("code" => "error", "msg" => "请选择省份", "data" => array("status" => 6, "data" => '--请选择省份--')));
        }

        $city = I("city");
        if ($city == "") {
            $this->ajaxReturn(array("code" => "error", "msg" => "请选择城市", "data" => array("status" => 7, "data" => '--请选择城市--')));
        }

        $county = I("county");
        if ($county == "") {
            $this->ajaxReturn(array("code" => "error", "msg" => "请选择地区", "data" => array("status" => 8, "data" => '--请选择地区--')));
        }
        $address = I("address");
        if ($address == "") {
            $this->ajaxReturn(array("code" => "error", "msg" => "请填写详细地址", "data" => array("status" => 9, "data" => '请填写详细地址')));
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
                $user_data = array(
                    'user_login' => $agent_phone,
                    'user_pass' => sp_password('123456'),
                    'user_nicename' => $agent_phone,
                    'create_time' => date('Y-m-d H:i:s'),
                    'mobile' => $agent_phone,
                    'platform' => 2,
                    'muid' => $u_id,
                    'pid' => $pid,
                );
                $ag_id = M('users')->add($user_data);
                $ro['role_id'] = 3;
                $ro['user_id'] = $ag_id;
                M('role_user')->add($ro);
            }
            $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("status" => 1, "data" => '添加成功')));

        }
    }

    //备份add
    public function add1()
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
                $this->ajaxReturn(array("code" => "error", "msg" => "手机号已经存在", "data" => array("status" => 5, "data" => '手机号已经存在')));
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

            if (empty($uid)) {
                $this->ajaxReturn(array("code" => "error", "msg" => '用户不存在'));
            }
            $data['uid'] = $uid;

            $merchant_name = I("merchant_name");
            if (empty($merchant_name)) {
                $this->ajaxReturn(array("code" => "error", "msg" => '商户名称不能为空'));
            }
            $data['merchant_name'] = $merchant_name;

            $province = I("province");
            if (empty($province)) {
                $this->ajaxReturn(array("code" => "error", "msg" => '省不能为空'));
            }
            $data['province'] = $province;

            $city = I("city");
            if (empty($city)) {
                $this->ajaxReturn(array("code" => "error", "msg" => '市不能为空'));
            }
            $data['city'] = $city;

            $county = I("county");
            if (empty($county)) {
                $this->ajaxReturn(array("code" => "error", "msg" => '县不能为空'));
            }
            $data['county'] = $county;

            $address = I("address");
            if (empty($address)) {
                $this->ajaxReturn(array("code" => "error", "msg" => '详细地址不能为空'));
            }
            $data['address'] = $address;

            $industry = I("industry");
            if (empty($industry)) {
                $this->ajaxReturn(array("code" => "error", "msg" => '行业不能为空'));
            }
            $data['industry'] = $industry;

            $operator_name = I("operator_name");
            if (empty($operator_name)) {
                $this->ajaxReturn(array("code" => "error", "msg" => '经营者姓名不能为空'));
            }
            $data['operator_name'] = $operator_name;

            $id_number = I("id_number");
            if (empty($id_number)) {
                $this->ajaxReturn(array("code" => "error", "msg" => '身份证不能为空'));
            }
            $data['id_number'] = encrypt($id_number);

            $account_type = I("account_type");
            if (!isset($account_type)) {
                $this->ajaxReturn(array("code" => "error", "msg" => '账户类型不能为空'));
            }
            $data['account_type'] = $account_type;

            $id_number = I("id_number");
            if (empty($id_number)) {
                $this->ajaxReturn(array("code" => "error", "msg" => '身份证不能为空'));
            }
            $data['id_number'] = $id_number;

            $user_phone = I("referrer", "trim");
            if (empty($user_phone)) {
//                手机号码的检测
                $user['agent_id'] = 1;
                $user['pid'] = 0;
                $data['referrer'] = 13128898154;
            } else {
                if (!isMobile($user_phone)) $this->ajaxReturn(array("code" => "error", "msg" => '你的手机号码不存在'));
                $p_id = M("merchants_users")->where("user_phone=$user_phone")->getField("id");
                if (!$p_id) {
                    $this->ajaxReturn(array("code" => "error", "msg" => '你添加的上级手机号码不存在'));
                }
                $role_id = M("merchants_role_users")->where("uid=$p_id")->getField("role_id");
                if ($role_id == 3 || $role_id == 7) {
                    $this->ajaxReturn(array("code" => "error", "msg" => '不能填写收银员或者商户的手机号码'));
                }
                if ($role_id == 1 || $role_id == 4 || $role_id == 5) {
                    $user['agent_id'] = 0;
                    $user['pid'] = $p_id;
                }
                if ($role_id == 2) {
                    $user['agent_id'] = $p_id;
                    $user['pid'] = $p_id;
                }
                if ($role_id == 6) {
                    $u_id = M("merchants_users")->where("id=$p_id")->getField("pid");
                    $user['agent_id'] = $u_id;
                    $user['pid'] = $p_id;
                }
//                商户的推荐人,作为备用
                $data['referrer'] = $user_phone;
            }
            $account_name = I("account_name");

            if (empty($account_name)) {
                $this->ajaxReturn(array("code" => "error", "msg" => '账户名称/开户名称不能为空'));
            }
            $data['account_name'] = $account_name;
            $bank_account = I("bank_account");
            if (empty($bank_account)) {
                $this->ajaxReturn(array("code" => "error", "msg" => '开户银行不能为空'));
            }
            $data['bank_account'] = $bank_account;

            $branch_account = I("branch_account");
            if (empty($branch_account)) {
                $this->ajaxReturn(array("code" => "error", "msg" => '开户支行不能为空'));
            }
            $data['branch_account'] = $branch_account;

            $bank_account_no = I("bank_account_no");
            if (empty($bank_account_no)) {
                $this->ajaxReturn(array("code" => "error", "msg" => '银行账号不能为空'));
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

            $data['status'] = 0;

            $addr = $province . $city . $county . $address;
            $getLonLat = $this->addresstolatlag($addr);
            if ($getLonLat) {
                $data['lon'] = $getLonLat["lng"];
                $data['lat'] = $getLonLat["lat"];
            }
            $user_data = array(
                'user_login' => $user_phone,
                'user_pass' => sp_password('123456'),
                'user_nicename' => $user_phone,
                'create_time' => date('Y-m-d H:i:s'),
                'mobile' => $user_phone,
                'platform' => 2,
                'agent_id' => $uid,
                'pid' => $user['agent_id'],
            );
            $id = M('users')->add($user_data);
            $ro['role_id'] = 3;
            $ro['user_id'] = $id;
            M('role_user')->add($ro);
            $count_data = $merchantsModel->field("id")->where(array('uid' => $uid))->find();
            if (!empty($count_data)) {
                $res = $merchantsModel->where(array('id' => $count_data['id']))->save($data);
                if ($res !== false) {
                    if (M("merchants_users")->where("id=$uid")->find()) M("merchants_users")->where("id=$uid")->save($user);
                    $this->ajaxReturn(array("code" => "success", "msg" => L('MERCHANTS_ADD_SUCCESS')));
                } else {
                    $this->ajaxReturn(array("code" => "success", "msg" => L('MERCHANTS_ADD_SUCCESS')));
                    //$this->ajaxReturn(array("code" => "error", "msg" =>  L('MERCHANTS_ADD_FAIL')));
                }
            } else {
                $data['add_time'] = time();
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

    public function newAddMerchants()
    {
        if (IS_POST) {
            $this->checkLogin();
            get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Api/','newAddMerchants','请求人uid'.$this->userId.',请求数据', json_encode($_POST));
            $merchantsModel = M('merchants');
            $uid = I("uid");
            if (empty($uid)) {
                $this->ajaxReturn(array("code" => "error", "msg" => '用户不存在'));
            }
            $data['uid'] = $uid;

            //如果是暂存信息则不验证提交的参数
            $is_ver = true;
            if(I('status')==6){
                $is_ver = false;
            }

            $merchant_name = I("merchant_name");
            if (empty($merchant_name) && $is_ver) {
                $this->ajaxReturn(array("code" => "error", "msg" => '商户名称不能为空'));
            }
            $data['merchant_name'] = $merchant_name;

            $merchant_jiancheng = I("merchant_jiancheng");
            /*if (empty($merchant_jiancheng) && $is_ver) {
                $this->ajaxReturn(array("code" => "error", "msg" => '商户简称不能为空'));
            }*/
            $data['merchant_jiancheng'] = $merchant_jiancheng;

            $province = I("province");
            /*if (empty($province) && $is_ver) {
                $this->ajaxReturn(array("code" => "error", "msg" => '省不能为空'));
            }*/
            $data['province'] = $province;

            $city = I("city");
            /*if (empty($city) && $is_ver) {
                $this->ajaxReturn(array("code" => "error", "msg" => '市不能为空'));
            }*/
            $data['city'] = $city;

            $county = I("county");
            /*if (empty($county) && $is_ver) {
                $this->ajaxReturn(array("code" => "error", "msg" => '县/区不能为空'));
            }*/
            $data['county'] = $county;

            $address = I("address");
            if (empty($address) && $is_ver) {
                $this->ajaxReturn(array("code" => "error", "msg" => '详细地址不能为空'));
            }
            $data['address'] = $address;

            $industry = I("industry");
            if (empty($industry) && $is_ver) {
                $this->ajaxReturn(array("code" => "error", "msg" => '行业不能为空'));
            }
            $data['industry'] = $industry;

            $operator_name = I("operator_name");
            if (empty($operator_name) && $is_ver) {
                $this->ajaxReturn(array("code" => "error", "msg" => '经营者姓名不能为空'));
            }
            $data['operator_name'] = $operator_name;

            $id_number = I("id_number");
            if (empty($id_number) && $is_ver) {
                $this->ajaxReturn(array("code" => "error", "msg" => '身份证不能为空'));
            }
            $data['id_number'] = encrypt($id_number);

            $account_type = I("account_type");
            if (!isset($account_type) && $is_ver) {
                $this->ajaxReturn(array("code" => "error", "msg" => '账户类型不能为空'));
            }
            $data['account_type'] = $account_type;


            $user_phone = I("referrer");
            if (empty($user_phone)) {
//                手机号码的检测
                if(I('status')!=6){
                    $data['referrer'] = 13128898154;
                    if($this->userId == 1) {
                        $user['agent_id'] = 0;
                        $user['pid'] = 0;
                    }else{
                        $user['agent_id'] = 1;
                        $user['pid'] = 0;
                    }
                }
                #如果商户没有代理商直接提交给总部审核
                $data['status'] = I('status','0');
                $msg = '提交了资料';
                $type=1;
            } else {
                //if (!isMobile($user_phone)) $this->ajaxReturn(array("code" => "error", "msg" => '你的手机号码不存在'));
                if (!is_numeric($user_phone) || strlen($user_phone)!=11) $this->ajaxReturn(array("code" => "error", "msg" => '用户的上级手机号码输入错误'));

                $p_id = M("merchants_users")->where("user_phone=$user_phone")->getField("id");
                if (!$p_id) {
                    $this->ajaxReturn(array("code" => "error", "msg" => '你添加的上级手机号码不存在'));
                }
                /*if($p_id == $uid){
                    $this->ajaxReturn(array("code" => "error", "msg" => '推荐人不能商户自己'));
                }*/
                #如果是代理（role_id==2）或者是代理的员工（pid==2），给商户提交资料 接收传过来的status
                #如果商户有代理商直接提交给代理（分部）审核 3
                #type，1商户，2代理，3总部
                if($this->userInfo['role_id']==2 || $this->userInfo['pid']==2){
                    $data['status'] = I('status');
                    #status==4代理审核通过
                    if($data['status']==0){
                        $data['first_examine']=1;
                        $msg = '资料校验通过';
                    }elseif($data['status']==3){
                        $msg = '仅保存资料';
                        //unset($data['status']);
                    }elseif($data['status']==4){
                        $msg = '校验不通过：'.I('msg');
                    }
                    $type=2;
                }else{
                    $data['status'] = I('status')=='6' ? '6' : '3';
                    $msg = '提交了资料';
                    $type=1;
                }
                #is_employee 是否是员工
                $is_employee = M("merchants_users")->where("user_phone=$user_phone")->getField("is_employee");
                $role_id = M("merchants_role_users")->where("uid=$p_id")->getField("role_id");
                if ($is_employee){
                    $employee_agent_id = M("merchants_users")->where("id=$p_id")->getField("agent_id");
                    $user['agent_id'] = $employee_agent_id;
                    $user['pid'] = $p_id;
                }elseif ($role_id == 3 || $role_id == 7) {
                    $this->ajaxReturn(array("code" => "error", "msg" => '不能填写收银员或者商户的手机号码'));
                }elseif ($role_id == 1 || $role_id == 4 || $role_id == 5) {
                    $user['agent_id'] = 0;
                    $user['pid'] = $p_id;
                }elseif ($role_id == 2) {
                    $user['agent_id'] = $p_id;
                    $user['pid'] = $p_id;
                }elseif ($role_id == 6) {
                    $u_id = M("merchants_users")->where("id=$p_id")->getField("agent_id");
                    $user['agent_id'] = $u_id;
                    $user['pid'] = $p_id;
                }
                if($user['pid'] == $uid || $uid == $user['agent_id']){
                    $this->ajaxReturn(array("code" => "error", "msg" => '参数错误'));
                }
                M("merchants_users")->where("id=$uid")->save($user);
//                商户的推荐人,作为备用
                $data['referrer'] = $user_phone;
            }

            $account_name = I("account_name");
            if (empty($account_name) && $is_ver) {
                $this->ajaxReturn(array("code" => "error", "msg" => '账户名称/开户名称不能为空'));
            }
            $data['account_name'] = $account_name;

            //检查收款人姓名和经营者姓名，不相同则是非法人
            if ($operator_name != $account_name) {
                $data['uni_positive_id_card_img'] = I('uni_positive_id_card_img','');//非法人身份证正面照片路径
                $data['uni_id_card_img'] = I('uni_id_card_img','');//非法人身份证反面照片路径
                $data['uni_id_number'] = encrypt(I('uni_id_number',''));//非法人身份证号
                $data['uni_ls_auth'] = I('uni_ls_auth','');//非法人清算授权书——乐刷
                $data['uni_xdl_auth'] = I('uni_xdl_auth','');//非法人清算授权书——新大陆
                $data['xdl_auth'] = I('xdl_auth','');//法人与非法人清算新大陆授权书
            }

            $bank_account = I("bank_account");
            if (empty($bank_account) && $is_ver) {
                $this->ajaxReturn(array("code" => "error", "msg" => '开户银行不能为空'));
            }
            $data['bank_account'] = $bank_account;

            $branch_account = I("branch_account");
            if (empty($branch_account) && $is_ver) {
                $this->ajaxReturn(array("code" => "error", "msg" => '开户支行不能为空'));
            }
            $data['branch_account'] = $branch_account;

            $bank_account_no = I("bank_account_no");
            if (empty($bank_account_no) && $is_ver) {
                $this->ajaxReturn(array("code" => "error", "msg" => '银行账号不能为空'));
            }
            $data['bank_account_no'] = encrypt($bank_account_no);

            $header_interior_img = I("header_interior_img");
            if (empty($header_interior_img) && $is_ver) {
                $this->ajaxReturn(array("code" => "error", "msg" => '门头照未上传'));
            }
            $data['header_interior_img'] = $header_interior_img;

            $interior_img = I("interior_img");
            if (empty($interior_img) && $is_ver) {
                $this->ajaxReturn(array("code" => "error", "msg" => '内景照未上传'));
            } elseif (count(explode(',', $interior_img)) < 2 && $is_ver) {
                $this->ajaxReturn(array("code" => "error", "msg" => '内景照未上传齐2张'));
            }
            $data['interior_img'] = $interior_img;

            $business_license = I("business_license");
            if (empty($business_license) && $is_ver) {
                $this->ajaxReturn(array("code" => "error", "msg" => '营业执照照片未上传'));
            }
            $data['business_license'] = $business_license;

            $business_license_number = I("business_license_number");
            if (empty($business_license_number) && $is_ver) {
                $this->ajaxReturn(array("code" => "error", "msg" => '营业执照编号不能为空'));
            }
            $data['business_license_number'] = encrypt($business_license_number);

            $positive_id_card_img = I("positive_id_card_img");
            if (empty($positive_id_card_img) && $is_ver) {
                $this->ajaxReturn(array("code" => "error", "msg" => '收款人身份证正面未上传'));
            }
            $data['positive_id_card_img'] = $positive_id_card_img;

            $id_card_img = I("id_card_img");
            if (empty($id_card_img) && $is_ver) {
                $this->ajaxReturn(array("code" => "error", "msg" => '收款人身份证反面未上传'));
            }
            $data['id_card_img'] = $id_card_img;

            $hand_positive_id_card_img = I("hand_positive_id_card_img");
            if (!empty($hand_positive_id_card_img)) {
                $data['hand_positive_id_card_img'] = $hand_positive_id_card_img;
                //$this->ajaxReturn(array("code" => "error", "msg" => '收款人手持身份证正面未上传'));
            }


            $hand_id_card_img = I("hand_id_card_img");
            if (!empty($hand_id_card_img)) {
                $data['hand_id_card_img'] = $hand_id_card_img;
//                $this->ajaxReturn(array("code" => "error", "msg" => '收款人手持身份证反面未上传'));
            }


            $positive_bank_card_img = I("positive_bank_card_img");
            if (empty($positive_bank_card_img) && $is_ver) {
                $this->ajaxReturn(array("code" => "error", "msg" => '银行卡正面照未上传'));
            }
            $data['positive_bank_card_img'] = $positive_bank_card_img;

            $bank_card_img = I("bank_card_img");
            if (empty($bank_card_img) && $is_ver) {
                $this->ajaxReturn(array("code" => "error", "msg" => '银行卡反面照未上传'));
            }
            $data['bank_card_img'] = $bank_card_img;

            $bank_type = I("bank_type");
            if (!isset($bank_type) && $is_ver) {
                $this->ajaxReturn(array("code" => "error", "msg" => '业务类型未选择'));
            }
            $data['bank_type'] = $bank_type;

            $bank_rate = I("bank_rate");
            /*if (empty($bank_rate) && $is_ver) {
                $this->ajaxReturn(array("code" => "error", "msg" => '费率不能为空'));
            }*/
            $data['bank_rate'] = $bank_rate;

            //$data['status'] = 0;
            $data['is_merchant_certificate'] = 1;
            $data['update_time'] = time();
            $addr = $province . $city . $county . $address;
            $getLonLat = $this->addresstolatlag($addr);
            if ($getLonLat) {
                $data['lon'] = $getLonLat["lng"];
                $data['lat'] = $getLonLat["lat"];
            }

            $count_data = $merchantsModel->field("id")->where(array('uid' => $uid))->find();

            //编辑商户
            if (!empty($count_data)) {
                //代理或代理员工编辑资料仅保存 或者 暂存，不改变status
                if (($data['status'] == 3 && ($this->userInfo['role_id']==2 || $this->userInfo['pid']==2)) || $data['status'] == 6){
                    unset($data['status']);
                }
                if($data['status']==5){
                    if($this->userInfo['role_id']==2 || $this->userInfo['pid']==2){
                        $msg = '驳回至商户：'.I('msg');
                    }else{
                        $msg = '待商户提交资料';
                    }
                }
                $res = $merchantsModel->where(array('id' => $count_data['id']))->save($data);
                if ($res !== false) {
                    /*if($uid == $user['agent_id'] || $uid == $user['pid']){
                        $this->ajaxReturn(array("code" => "error", "msg" => '推荐人不能为用户自己'));exit;
                    }*/
                    if (M("merchants_users")->where("id=$uid")->find()) M("merchants_users")->where("id=$uid")->save($user);
                }
                //如果是暂存则不添加到审核动态
                if(I('status') != 6){
                    #添加到审核动态
                    M('merchants_logs')->add(array('mid'=>$uid,'msg'=>$msg,'add_time'=>time(),'type'=>$type));
                }
                $this->ajaxReturn(array("code" => "success", "msg" => L('MERCHANTS_ADD_SUCCESS')));
            } else {
                //添加到后台user表
                if(!M('users')->where(array('user_login'=>$user_phone))->find()){
                    $user_data = array(
                        'user_login' => $user_phone,
                        'user_pass' => sp_password('123456'),
                        'user_nicename' => $user_phone,
                        'create_time' => date('Y-m-d H:i:s'),
                        'mobile' => $user_phone,
                        'platform' => 1,
                        'agent_id' => $uid,
                        'pid' => $user['agent_id'],
                    );
                    $id = M('users')->add($user_data);
                    $ro['role_id'] = 3;
                    $ro['user_id'] = $id;
                    M('role_user')->add($ro);
                }

                if($data['status']==5){
                    $msg = '待商户提交资料';
                }
                $data['add_time'] = time();
                if ($mid = $merchantsModel->add($data)) {
                    //如果是暂存则不添加到审核动态
                    if(I('status') != 6){
                        #添加到审核动态
                        M('merchants_logs')->add(array('mid'=>$uid,'msg'=>$msg,'add_time'=>time(),'type'=>$type));
                    }
                    $this->ajaxReturn(array("code" => "success", "msg" => L('MERCHANTS_ADD_SUCCESS')));
                } else {
                    $this->ajaxReturn(array("code" => "error", "msg" => L('MERCHANTS_ADD_FAIL')));
                }
            }
        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => L('HACKER_MSG')));
        }
    }

    public function upload_img()
    {
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize = 3145728;// 设置附件上传大小
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath = C('_WEB_UPLOAD_'); // 设置附件上传根目录
        $upload->savePath = 'merchants/'; // 设置附件上传（子）目录
        $upload->saveName = uniqid();//保持文件名不变
        // 上传文件
        $info = $upload->upload();
        if ($info) {
            $url = C('_WEB_UPLOAD_') . $info['img']['savepath'] . $info['img']['savename'];
            $this->ajaxReturn(array('code' => 'success', 'msg' => '上传成功', 'data' => $url));
        } else {
            $message = $upload->getError();
            $this->ajaxReturn(array('code' => 'error', 'msg' => $message));
        }
    }

    //获取经纬度
    public function addresstolatlag($address)
    {
        //$url='http://api.map.baidu.com/geocoder/v2/?address='.$address.'&output=json&ak=gMupUTCEfz8cOxfIW8ZX1xZMiphQLbDL';
        $url = 'http://apis.map.qq.com/ws/geocoder/v1/?address=' . $address . '&key=LANBZ-62HHF-TSWJM-N5JQT-XE4I3-ZUFIL';
        if ($result = file_get_contents($url)) {
            $res = json_decode($result, true);
            return $res["result"]["location"];
        }
    }

    /**
     * @param $referrer
     * @return array
     * @instruction 检查推荐人是否在用户表，存在则修改用户的上级，不存在则返回
     */
    private function checkReferrer($referrer)
    {
        $model = M("merchants_users");
        $uid = (int)session('uid');
        $data = $model->field(array('id'))->where(array("user_phone" => $referrer))->find();
        if ($data) {
            $res = $model->where(array('id' => $uid))->setField(array('pid' => $data['id']));
            if ($res !== false) {
                return array('code' => 1, 'msg' => '修改上级成功');
            }
        } else {
            return array('code' => 0, 'msg' => '推荐人不存在或者错误');
        }
    }

    /**
     * 更改商户logo
     */
    public function changeMerchantsLogo()
    {
        if (IS_POST) {
            if ($_FILES) {
                $upload = new \Think\Upload();// 实例化上传类
                //$upload->maxSize = 3145728;
                $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
                $upload->rootPath = C('_WEB_UPLOAD_');
                $upload->savePath = 'merchants/';
                $upload->saveName = uniqid();//保持文件名不变

                $info = $upload->upload();
                if (!$info) $this->ajaxReturn(array("code" => "error", "msg" => $upload->getError()));
            }
            $map['uid'] = $this->userId;
            $data['base_url'] = '/data/upload/' . $info['uploadfile']['savepath'] . $info['uploadfile']['savename']; // 保存上传的照片根据需要自行组装;

            $arr = array();
            $arr['buffer'] = '@' . $_SERVER['DOCUMENT_ROOT'] . $data['base_url'];
            $url_getlog = "https://api.weixin.qq.com/cgi-bin/media/uploadimg?access_token=" . get_weixin_token();
            $result = request_post($url_getlog, $arr);
            get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/weixin/','upload_image','Addagent上传图片',$result);
            $result = json_decode($result, true);
            $data['logo_url'] = $result['url'];

            $model = M("merchants");
            if ($data['logo_url'] && $model->where($map)->save($data)) $this->ajaxReturn(array("code" => "success", "msg" => '成功'));
            else  $this->ajaxReturn(array("code" => "error", "msg" => '上传失败'));
        }
    }

    /**
     * 商户资料
     */
    public function merchant_info()
    {
        if (IS_POST) {
            $uid = I('uid', $this->userId);
            if (!$uid) {
                $this->ajaxReturn(array("code" => "error", "msg" => 'uid不能为空'));
            }
            $data = M('merchants m')
                ->join('ypt_merchants_users u on u.id=m.uid','left')
                ->field('m.id,uid,u.user_phone,m.status,merchant_name,merchant_jiancheng,province,city,county,address,industry,referrer,header_interior_img,interior_img,business_license,business_license_number,operator_name,id_number,positive_id_card_img,id_card_img,account_type,account_name,bank_account,branch_account,bank_account_no,bank_type,bank_rate,hand_positive_id_card_img,hand_id_card_img,positive_bank_card_img,bank_card_img,uni_positive_id_card_img,uni_id_card_img,uni_id_number,uni_ls_auth,uni_xdl_auth,xdl_auth')
                ->where(array('uid' => $uid))
                ->find();
            $data = $this->_unsetNull($data);
            $merchants_logs = M('merchants_logs')->where(array('mid'=>$uid))->field('msg,type,add_time')->order('add_time desc')->find();
            if (!$merchants_logs){
                $data['new_log'] = '';
            }else{
                $logs_time = date('Y-m-d',$merchants_logs['add_time']);
                if ($merchants_logs['type']==1){
                    $type='商户';
                }elseif ($merchants_logs['type']==2){
                    $type='分部';
                }else{
                    $type='总部';
                }
                $data['new_log'] = $logs_time.$type.$merchants_logs['msg'];
            }
            if ($data['header_interior_img'] && !strpos($data['header_interior_img'], 'upload')) $data['header_interior_img'] = './data/upload/' . $data['header_interior_img'];
            if ($data['business_license'] && !strpos($data['business_license'], 'upload')) $data['business_license'] = './data/upload/' . $data['business_license'];
            if ($data['positive_id_card_img'] && !strpos($data['positive_id_card_img'], 'upload')) $data['positive_id_card_img'] = './data/upload/' . $data['positive_id_card_img'];
            if ($data['id_card_img'] && !strpos($data['id_card_img'], 'upload')) $data['id_card_img'] = './data/upload/' . $data['id_card_img'];
            if ($data['hand_positive_id_card_img'] && !strpos($data['hand_positive_id_card_img'], 'upload')) $data['hand_positive_id_card_img'] = './data/upload/' . $data['hand_positive_id_card_img'];
            if ($data['hand_id_card_img'] && !strpos($data['hand_id_card_img'], 'upload')) $data['hand_id_card_img'] = './data/upload/' . $data['hand_id_card_img'];
            if ($data['positive_bank_card_img'] && !strpos($data['positive_bank_card_img'], 'upload')) $data['positive_bank_card_img'] = './data/upload/' . $data['positive_bank_card_img'];
            if ($data['bank_card_img'] && !strpos($data['bank_card_img'], 'upload')) $data['bank_card_img'] = './data/upload/' . $data['bank_card_img'];
            if ($data['uni_positive_id_card_img'] && !strpos($data['uni_positive_id_card_img'], 'upload')) $data['uni_positive_id_card_img'] = './data/upload/' . $data['uni_positive_id_card_img'];
            if ($data['uni_id_card_img'] && !strpos($data['uni_id_card_img'], 'upload')) $data['uni_id_card_img'] = './data/upload/' . $data['uni_id_card_img'];
            if ($data['uni_ls_auth'] && !strpos($data['uni_ls_auth'], 'upload')) $data['uni_ls_auth'] = './data/upload/' . $data['uni_ls_auth'];
            if ($data['uni_xdl_auth'] && !strpos($data['uni_xdl_auth'], 'upload')) $data['uni_xdl_auth'] = './data/upload/' . $data['uni_xdl_auth'];
            if ($data['xdl_auth'] && !strpos($data['xdl_auth'], 'upload')) $data['xdl_auth'] = './data/upload/' . $data['xdl_auth'];
            $data['interior_img'] = explode(',', $data['interior_img']);
            foreach ($data['interior_img'] as &$v) {
                if ($v && !strpos($v, 'upload')) {
                    $v = './data/upload/' . $v;
                }
            }
            //$data = null_to_string($data);
            $data['id_number'] = decrypt($data['id_number'])?:'';
            $data['bank_account_no'] = decrypt($data['bank_account_no'])?:'';
            $data['business_license_number'] = decrypt($data['business_license_number'])?:'';
            $data['uni_id_number'] = decrypt($data['uni_id_number'])?:'';
            $this->ajaxReturn(array('code' => 'success', 'msg' => '成功', 'data' => $data));
        }
    }

    public function _unsetNull($arr){
        if($arr !== null){
            if(is_array($arr)){
                if(!empty($arr)){
                    foreach($arr as $key => $value){
                        if($value === null){
                            $arr[$key] = '';
                        }else{
                            $arr[$key] = $this->_unsetNull($value);      //递归再去执行
                        }
                    }
                }else{ $arr = ''; }
            }else{
                if($arr === null){ $arr = ''; }         //注意三个等号
            }
        }else{ $arr = ''; }
        return $arr;
    }

    public function merchant_list()
    {
        $this->checkLogin();
        $keywords = I('keywords');
        if ($keywords) $map['_string'] = '(u.user_name like "%' . $keywords . '%")  OR (u.user_phone like "%' . $keywords . '%") OR (m.merchant_name like "%' . $keywords . '%")';
        if($this->userInfo['role_id'] == 2){
            $map['agent_id'] = $this->userId;
        }else{
            if($this->userInfo['auth_all_merchants']){
                $map['agent_id'] = M('merchants_users')->where(array('id'=>$this->userId))->getField("boss_id");
            } else if($this->userInfo['auth_own_merchants']){
                $map['pid'] = $this->userId;
            } else {
                $this->ajaxReturn(array('code'=>'success','msg'=>'没有数据','data'=>array()));
            }
        }

        if ($status = I('status')){
            //审核状态 0总部待审核,1总部审核通过,2总部审核失败,3代理待校验,4代理校验未过,5待提交资料
            if ($status==1) $map['m.status'] = array('IN','0,1');//总部审核页
            if ($status==2) $map['m.status'] = array('IN','4,5,6');//待提交资料页
            if ($status==3) $map['m.status'] = array('IN','2,3');//待处理页
        }
        $merchants = M('merchants_users')
            ->field('m.merchant_name as user_name,u.`add_time`,m.referrer,
            (CASE WHEN m.`status` IS NULL THEN 3 ELSE m.`status` END) `status`,u.id,
            (CASE WHEN m.`bank_type` IS NULL THEN "" ELSE m.`bank_type` END) `bank_type`,
            (CASE WHEN m.`bank_rate` IS NULL THEN "" ELSE m.`bank_rate` END) `bank_rate`')
            ->join('u right join ypt_merchants m on u.id=m.uid')
            ->where($map)
            ->order('add_time desc')
            ->select();
        foreach($merchants as &$v){
            $referrer_name = M('merchants_users')->where(array('user_phone'=>$v['referrer']))->getField('user_name');
            $v['rejected'] = M('merchants_logs')->where('mid='.$v['id']." and type=2 and (msg like '%校验不通过%' or msg like '%驳回至商户%')")->count();
            $v['referrer_name'] = $referrer_name ? : '';
        }
        if($merchants){
            $this->ajaxReturn(array('code'=>'success','msg'=>'请求成功','data'=>$merchants));
        } else {
            $this->ajaxReturn(array('code'=>'success','msg'=>'没有数据','data'=>array()));
        }
    }

    #商户资料审核动态
    public function merchant_logs()
    {
        $this->checkLogin();
        if ($this->userInfo['role_id']==2 || $this->userInfo['pid']==2){
            $map['mid'] = I('id');
            $status = M('merchants')->where(array('uid'=>$map['mid']))->getField('status');
            $can_sub_info = in_array($status,array(2,3,4,5)) ? '1' : '0';//可否重新提交资料
        }else{
            $map['mid'] = $this->userId;
            $mer = M('merchants')->where(array('uid'=>$map['mid']))->field('status,first_examine')->find();
            if(in_array($mer['status'],array(4,5))){
                $can_sub_info = '1';
            }else{
                $can_sub_info = '0';
            }
        }

        $data = M('merchants_logs')->field('msg,add_time,type')->where($map)->order('add_time desc')->select();
        if($data){
            $this->ajaxReturn(array('code'=>'success','msg'=>'请求成功','data'=>$data,'can_sub_info'=>$can_sub_info));
        } else {
            $this->ajaxReturn(array('code'=>'success','msg'=>'没有数据','data'=>array(),'can_sub_info'=>$can_sub_info));
        }
    }

    #检查推荐人手机号
    public function check_referrer()
    {
        $phone = I('referrer','','trim');
        if(empty($phone)){
            $this->ajaxReturn(array('code'=>'success','msg'=>'ok'));
        }elseif(!isMobile($phone)){
            $this->ajaxReturn(array('code'=>'error','msg'=>'推荐人手机号格式不正确'));
        }
        $info = M('merchants_users')->where(array('user_phone'=>$phone))->find();
        if(!$info){
            $this->ajaxReturn(array('code'=>'error','msg'=>'未查到该推荐人信息'));
        }else{
            $role_users_info = M('merchants_role_users')->where(array('uid'=>$info['id']))->find();
            $role_info = M('merchants_role')->where(array('id'=>$role_users_info['role_id']))->find();
            if($role_info['id'] == 2 || $role_info['pid'] == 2){
                $this->ajaxReturn(array('code'=>'success','msg'=>'ok'));
            }else{
                $this->ajaxReturn(array('code'=>'error','msg'=>'该推荐人不是代理商或代理商员工'));
            }
        }
        $this->ajaxReturn(array('code'=>'success','msg'=>'ok'));
    }
}