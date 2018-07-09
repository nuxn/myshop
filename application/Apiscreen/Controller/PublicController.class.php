<?php
namespace Apiscreen\Controller;

use Common\Controller\ScreenbaseController;
use Think\Upload;

class  PublicController extends ScreenbaseController
{
    public function checksign()
    {
        $this->ajaxReturn(array('code' => 'success', 'msg' => '检测签名成功'));
    }

    public function checknumber()
    {

        $number=I("number");
        if(empty($number)){
            $this->ajaxReturn(array('code' => 'error', 'msg' => '失败','data'=>'number参数获取失败'));
        }
        $this->ajaxReturn(array('code' => 'success', 'msg' => '成功'));
    }
//双屏获取随机数（App）
    public function two_saologin()
    {
        $token = I("token");
        $data['random'] = I("random");
        $user=M("token")->where("token='$token'")->find();
        if(!$user){
            $this->ajaxReturn(array("code" => "error", "msg" => "失败","data"=>"请用app为登录"));
        }
        if(!$this->checkLoginAuth($user['uid'])){
            $this->ajaxReturn(array("code" => "error", "msg" => "没有权限","data"=>"没有权限！请联系管理员！"));
        }
//        判断双屏token表里面是否已经存在改用户了
//        $uid=$user['uid'];

        if(substr(I("random"),0,9) == "youngport"){
//            双屏
//            $twouser=M("twotoken")->where("uid='$uid'")->find();
//            if($twouser)M("twotoken")->where("uid='$uid'")->delete();
        }else{
//            $posuser=M("post_token")->where("uid='$uid'")->find();
//            if($posuser)M("post_token")->where("uid='$uid'")->delete();
        }

        $time=time();
        $data['value']=$user['value'];
        $data['uid']=$user['uid'];
        $data['time_start'] = $time;
        $data['token']=$this->build_token($data);
        if(substr(I("random"),0,9) == "youngport"){
//            双屏
            M("twotoken")->add($data);
        }else{
            M("post_token")->add($data);
        }
        $this->ajaxReturn(array("code" => "success", "msg" => "成功"));

    }

    public function login_auth()
    {
        $token = I("token");
        $user = M("token")->where("token='$token'")->getField('uid');
        if (!$user) {
            $this->ajaxReturn(array("code" => "error", "msg" => "失败", "data" => "请用app为登录"));
        }
        if (!$this->checkLoginAuth($user)) {
            $this->ajaxReturn(array("code" => "success", "msg" => "没有权限", "data" => "0"));
        } else {
            $this->ajaxReturn(array("code" => "success", "msg" => "有权限", "data" => "1"));
        }
    }

    private function checkLoginAuth($uid)
    {
        $auth_id = 7;   // 登录权限id
        $role_id = M('merchants_role_users')->where(array('uid'=>$uid))->getField('role_id');
        if(!$role_id || $role_id == '3') return true;
        $screen_auth = M('merchants_role')->where("id=$role_id")->getField('screen_auth');
        if(!$screen_auth) return false;
        $screen_auth = explode(',', $screen_auth);
        if(in_array($auth_id, $screen_auth)) return true;

        return false;
    }

//   扫码传递token（双屏）
    public function get_token()
    {
        $random=I("random");
        $mac=I("mac");
        $mac_id=M("screen_pos")->where("mac='$mac'")->getField("id");
        $token =M("twotoken")->where("random='$random'")->find();
        if(!$token)$this->ajaxReturn(array("code"=>"error","msg"=>"登录失败,未找到该用户"));
        $uid=$token['uid'];
        $user=M("merchants_users")->where("id=$uid")->find();
        if (!$mac_id){
            //添加机器
            $merchant = M("merchants")->where("uid=$uid")->field('id,province,city,county,address')->find();
            $res= array(
                'mid'=>$merchant['id'],
                'mac'=>$mac,
                'province'=>$merchant['province'],
                'city'=>$merchant['city'],
                'county'=>$merchant['county'],
                'address'=>$merchant['address'],
                'add_time'=>time()
            );
            M("screen_pos")->data($res)->add();
            $mac_id=M("screen_pos")->where("mac='$mac'")->getField("id");
        }
        $role_id=M("merchants_role_users")->where("uid=$uid")->getField("role_id");
        if(!in_array($role_id, array(2, 3))){
            $merchant_id=$user['pid'];
            //$merchant_name=M("merchants_users")->where("id=$merchant_id")->find();
            $two_type=M("merchants")->where("uid=$merchant_id")->getField('two_type');
            $merchant_name=M("merchants_users")->where("id=$merchant_id")->getField('user_name');
            $user_name=$user['user_name'];
            $user_id=$user['id'];
        }
        if($role_id ==3){
            $two_type=M("merchants")->where("uid=$uid")->getField('two_type');
            $merchant_name=$user['user_name'];
            $user_name=$user['user_name'];
            $user_id="";
        }
        $auth = $this->getAuth($role_id);
        $data=array(
            "merchant_name" =>$merchant_name,
            "user_name" =>$user_name,
            "user_id" =>$user_id,
            "mac_id" =>$mac_id,
            "token" =>$token,
            "role_id" => $role_id,
            "two_type" => $two_type,
            "auth" => $auth,
        );
        if($token) $this->ajaxReturn(array("code"=>"success","msg"=>"登录成功","data"=>$data));
        else $this->ajaxReturn(array("code"=>"error","msg"=>"登录失败"));
    }


    /**生成全局唯一TOKEN
     * @param array $arr
     * @return mixed
     */
    protected function build_token($arr = array())
    {
        $arr['salt'] = build_order_no();
        $arr['time'] = time();
        sort($arr);
        $String = implode($arr);
        $result_ = sha1($String);
        $TOKEN = strtoupper($result_);
        return $TOKEN;
    }

    /**
     * @function login
     * @auth weiyouhai
     * @intro 登录接口
     * @parme $phone 手机号码
     * @parme $pwd  密码
     * @return  code: success|error , msg， userInfo
     */
    public function login()
    {
        if (IS_POST) {
            get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/login/','login','login_data', json_encode(I("")));
            $phone = I("phone");
            if (!$phone) {
                $this->ajaxReturn(array("code" => "error", "msg" => L('PHONE_EMPTY')));
            }
            $pwd = I("pwd");
            if (!$pwd) {
                $this->ajaxReturn(array("code" => "error", "msg" => L('PWD_EMPTY')));
            }
            if (!($this->checkUser($phone))) {
                $this->ajaxReturn(array("code" => "error", "msg" => L('USER_NOT_EXIT')));
            }
            $random = I('random');
            $users = M("merchants_users")->alias("u")
                ->field("ru.uid,ru.role_id,u.user_phone,u.user_pwd,u.voice_open,u.auth,u.pid,u.user_name,u.id")
                ->join("left join " . C('DB_PREFIX') . "merchants_role_users as ru on ru.uid=u.id")
                ->where(array("user_phone" => $phone))
                ->find();
            $_SESSION['uid']=$users['uid']?$users['uid']:$this->userId;
            if ($users['user_pwd'] == md5($pwd)) {  
                if(!$this->checkLoginAuth($users['uid'])){
                    $this->ajaxReturn(array("code" => "error", "msg" => "失败","data"=>"没有权限！请联系管理员！"));
                }
                if(!in_array($users['role_id'], array(2, 3))){
                    $merchant_id=$users['pid'];
                    $two_type=M("merchants")->where("uid=$merchant_id")->getField('two_type');
                    $merchant_name=M("merchants_users")->where("id=$merchant_id")->getField('user_name');
                    $user_name=$users['user_name'];
                    $user_id=$users['id'];
                }
                if($users['role_id'] ==3){
                    $two_type=M("merchants")->where(array('uid'=>$users['uid']))->getField('two_type');
                    $merchant_name=$users['user_name'];
                    $user_name= $users['user_name'];
                    $user_id= "";
                }
                if ($users['role_id'] == 3) {
                    $res = M("merchants")->field("id,status")->where(array('uid' => $users['uid']))->find(); //查看是否已经填写过商户资料
                    if (empty($res)) {
                        $users['is_open'] = "0";
                    } else {
                        $users['is_open'] = "1";
                        $users['status'] = $res['status'];
                    }

                } else {
                    $users['is_open'] = "0";
                }

                if ($users['user_pwd'] == md5("123456")) {
                    $users['reset_pwd'] = "1";
                } else {
                    $users['reset_pwd'] = "0";
                }

                //返回当前商家或代理的名称
                $users["role_full_name"] = $this->get_role_full_name($users['role_id'], $users['uid']);
                if (!$users["role_full_name"]) $users["role_full_name"] = '';
                 //返回员工权限
                //$this->check_employee_auth($users);
                unset($users['user_pwd']);
                $users["random"] =$random;
                //存储登录信息
                $users['token_add_time'] = time();
                $TOKEN = $this->build_token($users);
                $token_info = M("twotoken")->where(array("uid" => $users['uid'],'random'=>$random))->find();

                if (!$token_info) M("twotoken")->add(array("uid" => $users['uid'], "token" => $TOKEN,"time_start"=>$users['token_add_time'], "value" => json_encode($users),'random'=>$users["random"]));
                else {
                    M("twotoken")->where(array("uid" => $users['uid'],'random'=>$random))->save(array("token" => $TOKEN, "time_start"=>$users['token_add_time'],"value" => json_encode($users)));
                }
                $users['token'] = $TOKEN;
                unset($users['token_add_time']);
                $uid = $users['uid'];
                
                $mac=I("mac");
                $mac_id=M("screen_pos")->where("mac='$mac'")->getField("id");
                if (!$mac_id){
                    //添加机器
                    $merchant = M("merchants")->where("uid=$uid")->field('id,province,city,county,address')->find();
                    $res= array(
                        'mid'=>$merchant['id'],
                        'mac'=>$mac,
                        'province'=>$merchant['province'],
                        'city'=>$merchant['city'],
                        'county'=>$merchant['county'],
                        'address'=>$merchant['address'],
                        'add_time'=>time()
                    );
                    M("screen_pos")->data($res)->add();
                    $mac_id=M("screen_pos")->where("mac='$mac'")->getField("id");
                }
                $token =M("twotoken")->where("random='$random'")->find();
                $auth = $this->getAuth($users['role_id']);
                $data=array(
                    "merchant_name" =>$merchant_name,
                    "user_name" =>$user_name,
                    "user_id" =>$user_id,
                    "mac_id" =>$mac_id?$mac_id:'',
                    "token" =>$token,
                    "role_id" => $users['role_id'],
                    "two_type" => $two_type,
                    "auth" => $auth,
                );
                $this->ajaxReturn(array("code" => "success", "msg" => L('LOGIN_SUCCESS'), 'userInfo' => $data));
            } else {
                $this->ajaxReturn(array("code" => "error", "msg" => L('LOGIN_FAIL')));
            }
        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => L('HACKER_MSG')));
        }
    }

    public function logout()
    {
        $token = I('token');
        if ($this->userId) {
            M("twotoken")->where(array("token" => $token))->delete();
            $this->ajaxReturn(array("code" => "success", "msg" => "成功"));
        } else
            $this->ajaxReturn(array("code" => "error", "msg" => "还未登陆"));
    }

    /**
     * @param $phone
     * @return bool
     * 手机号码是否已经注册
     */
    private function checkUser($phone)
    {

        $data =  M("merchants_users")->where(array("user_phone" => $phone))->count();
        if ($data > 0) {
            return true;
        } else {
            return false;
        }
    }

    private function get_role_full_name($role_id, $uid)
    {
        if (in_array($role_id, array(2, 3))) {//商家代理
            if ($role_id == '3') return M("merchants")->where(array("uid" => $uid))->getField("merchant_name");
            else return M("merchants_agent")->where(array("uid" => $uid))->getField("agent_name");
        } else if ($role_id != '3') {//收银员
            $pid = M("merchants_users")->where(array("id" => $uid))->getField("pid");
            return M("merchants")->where(array("uid" => $pid))->getField("merchant_name");
        } else {//其他
            return '';
        }

    }

    private function getAuth($role_id)
    {
        $role1 = 12;    // 整单优惠权限id
        $role2 = 13;    // 点击设置权限id
        $role_auth = M('merchants_role')->where("id=$role_id")->getField('screen_auth');
        $screen_auth = explode(',', $role_auth);
        $auth = array(
            'zdyouhui' => 0,
            'setting' => 0,
        );
        if($role_id == '3'){
            $auth = array(
                'zdyouhui' => 1,
                'setting' => 1,
            );
            return $auth;
        }
        if(in_array($role1, $screen_auth)){
            $auth['zdyouhui'] = 1;
        }
        if(in_array($role2, $screen_auth)){
            $auth['setting'] = 1;
        }

        return $auth;
    }

}