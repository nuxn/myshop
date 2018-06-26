<?php
namespace Api\Controller;

use Common\Controller\ApibaseController;
use Think\Upload;


class  PublicController extends ApibaseController
{


    /***
     * @function register 
     * @intro 用户注册商户
     * @parme  $phone 手机号码
     * @parme $sms  短信验证码
     * @parme $pwd  密码 
     * @return  code: success|error , msg
     */
    public function register()
    {
        if (IS_POST) {
            $phone = I("phone");
            if (!$phone) {
                $this->ajaxReturn(array("code" => "error", "msg" => L('PHONE_EMPTY')));
            }

            $sms = I("sms");
            if (!$sms) {
                $this->ajaxReturn(array("code" => "error", "msg" => L('SMS_EMPTY')));
            }

            $pwd = I("pwd");
            if (!$pwd) {
                $this->ajaxReturn(array("code" => "error", "msg" => L('PWD_EMPTY')));
            }

//            if ($sms != S('sms_reg')) {
//                $this->ajaxReturn(array("code" => "error", "msg" => L('SMS_ERROR')));
//            }

            $this->checkSms($phone,$sms);

            if ($this->checkUser($phone)) {
                $this->ajaxReturn(array("code" => "error", "msg" => L('USER_EXIT')));
            }
            $data = array();
            $data['user_phone'] = $phone;
            $data['user_pwd'] = md5($pwd);
            $data['add_time'] = time();
            $data['ip_address'] = get_client_ip();
            $data['user_name'] = 'ypt_' . $phone;
            $data['edit_pwd'] = '1';
            $data['agent_id'] = '1';
            $data['pid'] = '0';
            $res = M("Merchants_users")->add($data);
            if ($res) {
                $role_arr = array();
                $role_arr['uid'] = $res;
                $role_arr['role_id'] = '3'; // 商户角色
                $role_arr['add_time'] = time();
                if (M("merchants_role_users")->add($role_arr)) {
                    //存储登录信息
                    $login_arr = array(
                        "uid" => $res,
                        "role_id" => $role_arr['role_id'],
                        "user_phone" => $phone,
                        "user_name" => $data['user_name'],
                        "agent_id" => $data['agent_id'],
                        "token_add_time" => time(),
                    );
                    $TOKEN = $this->build_token($login_arr);
                    $token_info = M("token")->where(array("uid" => $login_arr['uid']))->find();
                    if (!$token_info) M("token")->add(array("uid" => $login_arr['uid'], "token" => $TOKEN, "value" => json_encode($login_arr)));
                    else  M("token")->where(array("uid" => $login_arr['uid']))->save(array("token" => $TOKEN, "value" => json_encode($login_arr)));

                    $this->ajaxReturn(array("code" => "success", "msg" => L('REG_SUCCESS'), "data" => array('uid' => $res,'token' => $TOKEN)));
                }
            } else {
                $this->ajaxReturn(array("code" => "error", "msg" => L('REG_FAIL')));
            }
        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => L('HACKER_MSG')));
        }
    }

    #验证注册短信
    private function checkSms($phone,$sms)
    {
        /*$sms_logs = M('sms_logs')->where(array('phone'=>$phone,'code'=>$sms,'type'=>1))->order('id desc')->find();
        if(!$sms_logs){
            $this->ajaxReturn(array("code" => "error", "msg" => '验证码有误'));
        }
        $sms_time = strtotime($sms_logs['sms_time']);
        #30分钟有效期
        if($sms_time+1800 < time()){
            $this->ajaxReturn(array("code" => "error", "msg" => '验证码已过期'));
        }*/
        if(S($phone) != $sms){
            $this->ajaxReturn(array("code" => "error", "msg" => '验证码有误'));
        }else{
            S($phone,null);
        }
    }

    /**
     * @function getSms
     * @auth weiyouhai
     * @intro app发送短信接口
     * @parme $phone 手机号码
     * @parme $type  短信类型 1代表注册短信模板id ，2代表设置密码的短信模板id
     * @return  code: success|error , msg
     */
    public function getSms()
    {
        if (IS_POST) {
            $phone = I("phone", $this->userInfo['user_phone']);
            $type = I("type");
            if (empty($phone)) {
                $this->ajaxReturn(array("code" => "error", "msg" => L('PHONE_EMPTY')));
            }

            if (!$type) {
                $this->ajaxReturn(array("code" => "error", "msg" => L('PARAME_ERROR')));
            }

            Vendor("SMS.CCPRestSmsSDK");
            $config_arr = C('SMS_CONFIG'); // 读取短信配置

            // 选择短信模板
            switch ($type) {
                case 1:
                    $tempId = $config_arr['RegTemplateId'];
                    break;
                case 2:
                    $tempId = $config_arr['PwdTemplateId'];
                    break;
                case 3:
                    $tempId = '180395';//$config_arr['ChangePhoneTemplateId'];
                    break;
                case 4:
                    $tempId = '188595';//$config_arr['setPayPwdTemplateId'];
                    break;
                case 6:
                    $tempId = '245484';//通用验证码模板
                    break;
            }
            $rest = new \REST($config_arr['serverIP'], $config_arr['serverPort'], $config_arr['softVersion']);
            $rest->setAccount($config_arr['accountSid'], $config_arr['accountToken']);
            $rest->setAppId($config_arr['appId']);
            $sms_msg = rand(100000, 999999); //生成短信信息
            $data['code'] = $sms_msg;
            $data['sms_time'] = date("Y-m-d H:i:s", time());
            $data['sms_type'] = $type;
            $data['phone'] = $phone;
            M('sms_logs')->add($data);
            // 把验证码保存到缓存中 300s后失效
            switch ($type) {
                case 1:
                    S($phone, $sms_msg, 300);// 缓存$sms_msg数据300秒过期
                    break;
                case 2:
                    S($phone, $sms_msg, 300);// 缓存$sms_msg数据300秒过期
                    break;
                case 3:
                    S($phone, $sms_msg, 300);// 缓存$sms_msg数据300秒过期
                    break;
                case 4:
                    S($phone, $sms_msg, 300);// 缓存$sms_msg数据300秒过期
                    break;
                case 6:
                    S($phone, $sms_msg, 300);// 缓存$sms_msg数据300秒过期
                    break;
            }

            $result = $rest->sendTemplateSMS($phone, array($sms_msg, $type==6 ? '5分钟' : '5'), $tempId); // 发送模板短信

            if (empty($result)) {
                $this->ajaxReturn(array("code" => "error", "msg" => "result error!"));
            }
            if ($result->statusCode != '000000') { // 错误
                $this->ajaxReturn(array("code" => "error", "msg" => L('SEND_SMS_ERROR'), 'data' => json_encode($config_arr))); //$result->statusCode
                //$this->ajaxReturn(array("code" => "error", "msg111" =>$result->statusCode)); //$result->statusCode
            } else {
                $this->ajaxReturn(array("code" => "success", "msg" => L('SEND_SMS_SUCCESS')));
            }
        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => L('HACKER_MSG')));
        }
    }

    /***
     * @function editPwd
     * @auth weiyouhai
     * @intro 重置密码
     * @parme $phone 手机号码
     * @parme $msm  短信
     * @parme $pwd 密码
     * @return  code: success|error , msg
     */
    public function editPwd()
    {
        if (IS_POST) {
            $phone = I("phone");
            if (!$phone) {
                $this->ajaxReturn(array("code" => "error", "msg" => L('PHONE_EMPTY')));
            }

            $sms = I("sms");
            if (!$sms) {
                $this->ajaxReturn(array("code" => "error", "msg" => L('SMS_EMPTY')));
            }

            $pwd = I("pwd");
            if (!$pwd) {
                $this->ajaxReturn(array("code" => "error", "msg" => L('PWD_EMPTY')));
            }
            if ($pwd == '123456') $this->ajaxReturn(array("code" => "error", "msg" => "密码不能改为123456"));
            if ($sms != S($phone)) {
                $this->ajaxReturn(array("code" => "error", "msg" => L('SMS_ERROR')));
            }else{
                S($phone,null);
            }
            $data = array();
            $data['user_phone'] = $phone;
            $data['user_pwd'] = md5($pwd);
            $data['ip_address'] = get_client_ip();
            $data['edit_pwd'] = 1;
            M("merchants_users")->where(array('user_phone' => $phone))->save($data);
            file_put_contents('./data/log/user/' . date("Y_") . 'editpwd.log', date("Y-m-d H:i:s") . $phone . '_修改密码' . json_encode($data) . PHP_EOL, FILE_APPEND | LOCK_EX);
            $this->ajaxReturn(array("code" => "success", "msg" => L('RESET_PWD_SUCCESS')));
        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => L('HACKER_MSG')));
        }
    }

    /**
     * @param $phone
     * @return bool
     * 手机号码是否已经注册
     */
    private function checkUser($phone)
    {
        $model = M("merchants_users");
        $data = $model->where(array("user_phone" => $phone))->count();
        if ($data > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 检测token有效期
     */
    public function checkToken()
    {
        //$user_info = session($this->token);
        $user_info = M("token")->where(array("token" => $this->token))->getField("value");
        $big_info = M("token")->where(array("token" => $this->token))->getField("userinfo");
        $posinfo = M("post_token")->where(array("token" => $this->token))->getField("value");
        $twoinfo = M("twotoken")->where(array("token" => $this->token))->getField("value");
        if($twoinfo){  //双屏的
            $twoinfo = json_decode($twoinfo, true);
            foreach ($twoinfo as $k => $v) {
                $twoinfo[$k] = "$v";
            }
            if ($twoinfo) {
                if ($twoinfo['token_add_time'] + 7 * 24 * 3600 < time()) {//超过1周未登录,销毁登录信息
                    M("twotoken")->where(array("token" => $this->token))->delete();
                    $this->ajaxReturn(array("code" => "error", "msg" => '未登录'));
                } else
                    $data = M('merchants')->alias('m')
                    ->join('left join __MERCHANTS_CATE__ mc on m.id=mc.merchant_id')
                    ->where("m.uid=$twoinfo[uid]")
                    ->getField('jianchen');
                    if($data){
                        $shortName = $data;
                    }else{
                        $shortName = $this->shortName($twoinfo['role_full_name']);
                    }
                    $twoinfo['role_short_name'] = $shortName?$shortName:"";
                    $this->ajaxReturn(array("code" => "success", "msg" => "token有效", "data" => $twoinfo));
            } else {
                $this->ajaxReturn(array("code" => "error", "msg" => '未登录'));
            }
        }
        if($posinfo){  //pos机的
            $user_info = json_decode($posinfo, true);
            foreach ($user_info as $k => $v) {
                $user_info[$k] = "$v";
            }
            if ($user_info) {
                if ($user_info['token_add_time'] + 7 * 24 * 3600 < time()) {//超过1周未登录,销毁登录信息
                    M("post_token")->where(array("token" => $this->token))->delete();
                    $this->ajaxReturn(array("code" => "error", "msg" => '未登录'));
                } else
					$data = M('merchants')->alias('m')
                    ->join('left join __MERCHANTS_CATE__ mc on m.id=mc.merchant_id')
                    ->where("m.uid=$user_info[uid]")
                    ->getField('jianchen');
					if($data){
						$shortName = $data;
					}else{
						$shortName = $this->shortName($user_info['role_full_name']);
					}
					$user_info['role_short_name'] = $shortName?$shortName:"";
                    $this->ajaxReturn(array("code" => "success", "msg" => "token有效", "data" => $user_info));
            } else {
                $this->ajaxReturn(array("code" => "error", "msg" => '未登录'));
            }
        }
        if($big_info){   //大商户
            $user_info = json_decode($big_info, true);
            foreach ($user_info as $k => $v) {
                $user_info[$k] = "$v";
            }
            if ($user_info) {
                if ($user_info['token_add_time'] + 7 * 24 * 3600 < time()) {//超过1周未登录,销毁登录信息
                    M("token")->where(array("token" => $this->token))->save(array("userinfo" => ""));
                    $this->ajaxReturn(array("code" => "error", "msg" => '未登录'));
                } else
					$data = M('merchants')->alias('m')
                    ->join('left join __MERCHANTS_CATE__ mc on m.id=mc.merchant_id')
                    ->where("m.uid=$user_info[uid]")
                    ->getField('jianchen');
					if($data){
						$shortName = $data;
					}else{
						$shortName = $this->shortName($user_info['role_full_name']);
					}
					$user_info['role_short_name'] = $shortName?$shortName:"";
					$user_info['merchants_phone'] = $user_info['user_phone'];
                    $this->ajaxReturn(array("code" => "success", "msg" => "token有效", "data" => $user_info));
            } else {
                $this->ajaxReturn(array("code" => "error", "msg" => '未登录'));
            }
        }else{ //APP的
            $user_info = json_decode($user_info, true);
            foreach ($user_info as $k => $v) {
                $user_info[$k] = "$v";
            }
            if ($user_info) {
                if ($user_info['token_add_time'] + 7 * 24 * 3600 < time()) {//超过1周未登录,销毁登录信息
                    //session($this->token, '');
                    M("token")->where(array("token" => $this->token))->delete();
//                $this->ajaxReturn(array("code" => "error", "msg" => 'token过期'));
                    $this->ajaxReturn(array("code" => "error", "msg" => '未登录'));
                } else
                    $user_info['open_loan'] = M("merchants_users")->where(array('id'=>$user_info['uid']))->getField('open_loan');
                    $data = M('merchants')->alias('m')
                    ->join('left join __MERCHANTS_CATE__ mc on m.id=mc.merchant_id')
                    ->where("m.uid=$user_info[uid]")
                    ->getField('jianchen');
					if($data){
						$shortName = $data;
					}else{
						$shortName = $this->shortName($user_info['role_full_name']);
					}
					$user_info['role_short_name'] = $shortName?$shortName:"";
					if(in_array($user_info['role_id'],array(1,2,3))){
                        $user_info['merchant_phone'] = $user_info['user_phone'];
                    }else{
                        $pid = M("merchants_users")->where(array('user_phone'=>$user_info['user_phone']))->getField('pid');
                        $user_info['merchant_phone'] = M('merchants_users')->where(array('id'=>$pid))->getField('user_phone');
                    }
                    $this->ajaxReturn(array("code" => "success", "msg" => "token有效", "data" => $user_info));
            } else {
//            $msg = $this->checkSso();
//            if ($msg) $this->ajaxReturn(array("code" => "error", "msg" => $msg));
//            $this->ajaxReturn(array("code" => "error", "msg" => 'token不存在'));
                $this->ajaxReturn(array("code" => "error", "msg" => '未登录'));
            }
        }


    }
	
	//缩短商户名称
    private function shortName($merchant)
    {
        if (strpos($merchant, "镇")) {
            $merchant_name = substr(strstr($merchant, '镇'), 3);
        } elseif (strpos($merchant, "区")) {
            $merchant_name = substr(strstr($merchant, '区'), 3);
        } elseif (strpos($merchant, "县")) {
            $merchant_name = substr(strstr($merchant, '县'), 3);
        } elseif (strpos($merchant, "市")) {
            $merchant_name = substr(strstr($merchant, '市'), 3);
        } elseif (strpos($merchant, "省")) {
            $merchant_name = substr(strstr($merchant, '省'), 3);
        } else {
            $merchant_name = $merchant;
        }
        return $merchant_name;
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
            $phone = I("phone");
            if (!$phone) {
                $this->ajaxReturn(array("code" => "error", "msg" => L('PHONE_EMPTY')));
            }
            if($phone == '13128898154'){
                $this->ajaxReturn(array("code" => "error", "msg" => '该账户在维护'));
            }
            $pwd = I("pwd");
            if (!$pwd) {
                $this->ajaxReturn(array("code" => "error", "msg" => L('PWD_EMPTY')));
            }
            if (!($this->checkUser($phone))) {
                $this->ajaxReturn(array("code" => "error", "msg" => L('USER_NOT_EXIT')));
            }

            $model = M("merchants_users");
            $users = $model->alias("u")
                //->field("ru.uid,ru.role_id,r.role_name,u.user_phone,u.user_pwd")
                ->field("u.status,ru.uid,u.edit_pwd,ru.role_id,r.pid,u.user_phone,u.user_name,u.user_pwd,u.voice_open,u.cloud_voice,u.cash_pay,u.auth,mini.type as miniapp_type,open_loan")
                ->join("left join " . C('DB_PREFIX') . "merchants_role_users as ru on ru.uid=u.id")
				->join("left join " . C('DB_PREFIX') . "miniapp as mini on mini.mid=u.id")
                ->join("left join ".C('DB_PREFIX')."merchants_role as r on ru.role_id=r.id")
                ->where(array("user_phone" => $phone))
                ->find();
            //暂时关掉首次登陆发送短信改密码
            $users['edit_pwd'] = '1';
            if($users['pid'] == 2 && $users['role_id'] == 6){//代理商员工不能登录
                $this->ajaxReturn(array("code" => "error", "msg" => '该角色不允许登录'));
            }
            // 进入APP界面 2为代理商APP，3为商户APP，4为供应商APP
            if(in_array($users['role_id'], array(2,3,77))){
                if($users['role_id'] == 77){
                    $users['paltform'] = 4;
                } else {
                    $users['paltform'] = $users['role_id'];
                }
            } else {
                if($users['pid'] == 77){
                    $users['paltform'] = 4;
                } else {
                    $users['paltform'] = $users['pid'];
                }
            }
            if($users['status'] == 1)$this->ajaxReturn(array("code" => "error", "msg" => '该账户被禁用'));
            if($users['status'] == -1)$this->ajaxReturn(array("code" => "error", "msg" => '用户已被删除'));
            if ($users['user_pwd'] == md5($pwd)) {
                if ($users['role_id'] == 3) {//商户
                    $res = M('merchants')->field("id,status,first_examine,update_time,type,is_miniapp,end_time")->where(array('uid' => $users['uid']))->find(); //查看是否已经填写过商户资料
                    if (empty($res)) {
                        $users['is_open'] = "0";
                        $users['status'] = '5';//待提交资料
                        $users['first_examine'] = '0';
                    } else {
                        $users['is_open'] = "1";
                        if($res['status']==6){
                            $users['is_open'] = "0";
                        }
                        $users['status'] = $res['status'];
                        #如果总部还未审核通过，代理校验通过，可以登录，但是20天过后总部还没通过就不能登录
                        if($res['status']!=1 && $res['first_examine']==1 && $res['update_time']+1728000<time()){
                            $users['first_examine'] = '0';
                        }else{
                            $users['first_examine'] = $res['first_examine'];
                        }
                    }
                    $users['type'] = $res['type'];
                    $users['is_miniapp'] = $res['is_miniapp'];
                    $users['end_time'] = $res['end_time'];
                    $users['merchant_phone'] = $phone;
                } elseif($users['role_id'] == 77) {//供应商
                    
                } else {
                    $users['is_open'] = "0";
                    if($users['role_id'] != 1 && $users['role_id'] != 2){
                        $pid = M("merchants_users")->where(array('user_phone'=>$phone))->getField('pid');
                        $users['merchant_phone'] = M('merchants_users')->where(array('id'=>$pid))->getField('user_phone');
                        $users['is_miniapp'] = M('merchants')->where(array('uid' => $pid))->getField('is_miniapp');
                        //如果是代理的员工，查看员工发展的商户是否有多门店模式的商户
                        $agent_uids = M('merchants_users')->where(array('pid'=>$users['uid']))->getField('id',true);
                        if($agent_uids){
                            $users['big_store_count'] = M('merchants')->where(array('uid'=>array('in',$agent_uids),'mid'=>array('neq',2)))->count();
                        }else{
                            $users['big_store_count'] = '0';
                        }
                    }else{
                        $uid = M("merchants_users")->where(array('user_phone'=>$phone))->getField('id');
                        //修改时间 2018/3/5
                        $users['card_auth'] = M('merchants_agent')->where(array('uid'=>$uid))->getField('card_auth');
                        $users['merchant_phone'] = $phone;
                        //获取代理下的所有商户是否有多门店模式的商户
                        $agent_uids = M('merchants_users')->where(array('agent_id'=>$uid))->getField('id',true);
                        if($agent_uids){
                            $users['big_store_count'] = M('merchants')->where(array('uid'=>array('in',$agent_uids),'mid'=>array('neq',2)))->count();
                        }else{
                            $users['big_store_count'] = '0';
                        }
                    }
                }

                /*if ($users['user_pwd'] == md5("123456")) {
                    $users['reset_pwd'] = "1";
                } else {
                    $users['reset_pwd'] = "0";
                }*/
                $users['reset_pwd'] = "0";
                //返回当前商家或代理的名称
                $users["role_full_name"] = $this->get_role_full_name($users['role_id'], $users['uid']);
                if (!$users["role_full_name"]) $users["role_full_name"] = '';

                //返回员工权限
                $this->check_employee_auth($users);
                unset($users['user_pwd']);

                //存储登录信息
                $users['token_add_time'] = time();
                $TOKEN = $this->build_token($users);
                //session($TOKEN, $users);
                $token_info = M("token")->where(array("uid" => $users['uid']))->find();
                $version = I("version","1.2");
                $login_arr = array(
                    'last_login_ip' => get_client_ip(),
                    'last_login_time' => time(),
                    'device' => I('device', '0'),
                    'login_num' => array('exp', 'login_num+1')
                );
                $users = null_to_string($users);
                M("merchants_users")->where(array("id" => $users['uid']))->save($login_arr);
                if (!$token_info) M("token")->add(array("uid" => $users['uid'], "token" => $TOKEN, "value" => json_encode($users), "version" => $version));
                else {
//                    Vendor('Cache.MyRedis');
//                    $redis = new \MyRedis();
//                    $Ip = get_client_ip();
//                    $IpLocation = new \Org\Net\IpLocation('UTFWry.dat'); // 实例化类 参数表示IP地址库文件
//                    $area = $IpLocation->getlocation($Ip); // 获取某个IP地址所在的位置
//                    $redis->set($token_info['token'], json_encode(array("login_ip" => $Ip, "login_time" => date("Y-m-d H:i:s"), "address" => $area['country'], "network" => $area['area'])));
                    M("token")->where(array("uid" => $users['uid']))->save(array("token" => $TOKEN, "value" => json_encode($users), "version" => $version));
                }
                $users['token'] = $TOKEN;
                unset($users['token_add_time']);
                //检查订单
                $this->checkOrder($users['uid']);
                $this->ajaxReturn(array("code" => "success", "msg" => L('LOGIN_SUCCESS'), 'userInfo' => $users));
            } else {
                $this->ajaxReturn(array("code" => "error", "msg" => L('LOGIN_FAIL')));
            }
        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => L('HACKER_MSG')));
        }
    }

    /**
     * 检查订单是否发货   604800
     * @return [type] [description]
     */
    private function checkOrder($uid)
    {
        $order = M('order')->where(array('user_id'=>$uid,'type'=>1,'order_status'=>3))->field('update_time,order_sn,order_id')->select();
        $time = time();
        foreach ($order as $key => $value) {
            if (($time-$value['update_time'])>=604800) {
                M('order')->where(array('user_id'=>$uid,'type'=>1,'order_status'=>3,'order_id'=>$value['order_id']))->setField('order_status',4);
                $path = $_SERVER['DOCUMENT_ROOT'] . '/data/log/order/';
                $data = array('user_id'=>$uid,'type'=>1,'order_status'=>3,'order_id'=>$value['order_id']);
                get_date_dir($path, 'order_status', '更改订单状态为4', json_encode($data));
            }
        }
    }

    /**
     * 此方法未被使用 2018年4月24日11:07:08（可删吗）
     * @function login
     * @auth weiyouhai
     * @intro 登录接口
     * @parme $phone 手机号码
     * @parme $pwd  密码
     * @return  code: success|error , msg， userInfo
     */
    public function login1666()
    {
        if (IS_POST) {
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

            $model = M("merchants_users");
            $users = $model->alias("u")
                //->field("ru.uid,ru.role_id,r.role_name,u.user_phone,u.user_pwd")
                ->field("ru.uid,ru.role_id,r.pid,u.user_phone,u.user_name,u.user_pwd,u.voice_open,u.cloud_voice,u.cash_pay,u.auth,mini.type as miniapp_type")
                ->join("left join " . C('DB_PREFIX') . "merchants_role_users as ru on ru.uid=u.id")
                ->join("left join " . C('DB_PREFIX') . "miniapp as mini on mini.mid=u.id")
                ->join("left join ".C('DB_PREFIX')."merchants_role as r on ru.role_id=r.id")
                ->where(array("user_phone" => $phone))
                ->find();
            if($users['pid'] == 2){//代理商员工不能登录
                $this->ajaxReturn(array("code" => "error", "msg" => '该角色不允许登录'));
            }
            if ($users['user_pwd'] == md5($pwd)) {
                /*switch ($users['role_id']) {
                    case 2:  // 代理
                        $users['userInfo'] = M('merchants_agent')->where(array('uid' => $users['uid']))->find();
                        break;
                    case 3: // 商户
                        $users['userInfo'] = M('merchants')->where(array('uid' => $users['uid']))->find();
                        break;
                }*/

                if ($users['role_id'] == 3) {
                    $res = M('merchants')->field("id,status,type,is_miniapp,end_time")->where(array('uid' => $users['uid']))->find(); //查看是否已经填写过商户资料
                    if (empty($res)) {
                        $users['is_open'] = "0";
                    } else {
                        $users['is_open'] = "1";
                        $users['status'] = $res['status'];
                    }
                    $users['type'] = $res['type'];
                    $users['is_miniapp'] = $res['is_miniapp'];
                    $users['end_time'] = $res['end_time'];
                    $users['merchant_phone'] = $phone;
                } else {
                    $users['is_open'] = "0";
                    if($users['role_id'] != 1 && $users['role_id'] != 2){
                        $pid = M("merchants_users")->where(array('user_phone'=>$phone))->getField('pid');
                        $users['merchant_phone'] = M('merchants_users')->where(array('id'=>$pid))->getField('user_phone');
                        $users['is_miniapp'] = M('merchants')->where(array('uid' => $pid))->getField('is_miniapp');
                    }else{
                        $users['merchant_phone'] = $phone;
                    }
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
                $this->check_employee_auth($users);
                unset($users['user_pwd']);

                //存储登录信息
                $users['token_add_time'] = time();
                $TOKEN = $this->build_token($users);
                //session($TOKEN, $users);
                $token_info = M("token")->where(array("uid" => $users['uid']))->find();
                $version = I("version") ? I("version") : "1.2";
                $login_arr = array(
                    'last_login_ip' => get_client_ip(),
                    'last_login_time' => time(),
                    'device' => I('device', '0'),
                    'login_num' => array('exp', 'login_num+1')
                );
                $users = null_to_string($users);
                M("merchants_users")->where(array("id" => $users['uid']))->save($login_arr);
                if (!$token_info) M("token")->add(array("uid" => $users['uid'], "token" => $TOKEN, "value" => json_encode($users), "version" => $version));
                else {
//                    Vendor('Cache.MyRedis');
//                    $redis = new \MyRedis();
//                    $Ip = get_client_ip();
//                    $IpLocation = new \Org\Net\IpLocation('UTFWry.dat'); // 实例化类 参数表示IP地址库文件
//                    $area = $IpLocation->getlocation($Ip); // 获取某个IP地址所在的位置
//                    $redis->set($token_info['token'], json_encode(array("login_ip" => $Ip, "login_time" => date("Y-m-d H:i:s"), "address" => $area['country'], "network" => $area['area'])));
                    M("token")->where(array("uid" => $users['uid']))->save(array("token" => $TOKEN, "value" => json_encode($users), "version" => $version));
                }
                $users['token'] = $TOKEN;
                unset($users['token_add_time']);

                $this->ajaxReturn(array("code" => "success", "msg" => L('LOGIN_SUCCESS'), 'userInfo' => $users));
            } else {
                $this->ajaxReturn(array("code" => "error", "msg" => L('LOGIN_FAIL')));
            }
        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => L('HACKER_MSG')));
        }
    }

    /**
     * 获取用户权限
     * @param $info
     */
    private function check_employee_auth(&$info)
    {
        $old_auth_arr = explode(";", $info['auth']);

        $auth_arr = M("nav")->where(array('parentid' => 0))->getField('module,href', true);

        //foreach ($auth_arr as $k => $v) $info[$k] = in_array($v, $old_auth_arr) ? '1' : '0';
        foreach ($auth_arr as $k => $v) $info[$k] = (in_array($info['role_id'], array(2, 3)) || in_array($v, $old_auth_arr)) ? '1' : '0';
        unset($info['auth']);
    }

    private function get_role_full_name($role_id, $uid)
    {
        if (in_array($role_id, array(2, 3))) {//商家代理
            if ($role_id == '3') return M("merchants")->where(array("uid" => $uid))->getField("merchant_name");
            else return M("merchants_agent")->where(array("uid" => $uid))->getField("agent_name");
        } else {//其他
            $pid = M("merchants_users")->where(array("id" => $uid))->getField("pid");
            return M("merchants")->where(array("uid" => $pid))->getField("merchant_name");
        }

    }

    public function logout()
    {
        if ($this->userId) {
            M("token")->where(array("token" => $this->token))->delete();
            $this->ajaxReturn(array("code" => "success", "msg" => "成功"));
        } else
            $this->ajaxReturn(array("code" => "error", "msg" => "还未登陆"));
    }


    public function uploadImages()
    {
        if (IS_POST) {

            $type = I("type");
            if (empty($type)) {
                $this->ajaxReturn(array("code" => "error", "msg" => L('TYPE_EMPTY')));
            }
            $uid = I("uid");
            if (empty($uid)) {
                $this->ajaxReturn(array("code" => "error", "msg" => L('UID_EMPTY')));
            }
            $merchantsModel = M('merchants');
            $id_data = $merchantsModel->field("id")->where(array('uid' => $uid))->find();
            $merchantsModel->uid = $uid;
            if ($merchantsModel->create()) {
                if ($_FILES) {
                    $config = array(
                        "rootPath" => C('_WEB_UPLOAD_'),
                        "savePath" => "merchants/",
                        "maxSize" => 10240000, // 单位B
                        "exts" => explode(",", 'gif,png,jpg,jpeg,bmp'),
                        "subName" => array('date', 'Y-m-d'),
                        'saveName' => array('uniqid', ''),
                    );

                    $upload = new Upload($config);
                    $info = $upload->upload();
                    //file_put_contents("./log.txt", "msg:".var_export($info,true)."config".var_export($config,true)."uploadErro:".var_export($upload->getError(),true).PHP_EOL, FILE_APPEND);
                    file_put_contents("./log.txt", date("Y-m-d H:i:s",time())."file" . var_export($_FILES, true) . PHP_EOL, FILE_APPEND);
                    /*if (!$info) {
                        $this->ajaxReturn(array("code" => "error", "msg" => L('UPLOAD_FAIL')));
                    }*/
                    switch ($type) {
                        case 'mt':
                            //处理门头--
                            if ($merchantsModel->isdoor_header == 1) {
                                $str = $info['header_interior_img1'] ? $info['header_interior_img1']['savepath']. $info['header_interior_img1']['savename'] : '';
                                if (!empty($str)) {
                                    $merchantsModel->header_interior_img = $str;
                                }
                            } else {
                                $merchantsModel->header_interior_img = $info['header_interior_img1'] ? $info['header_interior_img1']['savepath'] . $info['header_interior_img1']['savename'] : '';
                                $str = $info['header_interior_img2'] ? $info['header_interior_img1']['savepath'] . $info['header_interior_img2']['savename'] : '';
                                if (!empty($str)) {
                                    $merchantsModel->header_interior_img .= "," . $str;
                                }
                                $str = $info['header_interior_img3'] ? $info['header_interior_img3']['savepath'] . $info['header_interior_img3']['savename'] : '';
                                if (!empty($str)) {
                                    $merchantsModel->header_interior_img .= "," . $str;
                                }
                            }
                            //营业执照
                            $merchantsModel->business_license = $info['business_license'] ? $info['business_license']['savepath']. $info['business_license']['savename'] : '';
                            if ($id_data['id'] > 0) {
                                $res = $merchantsModel->where(array('id' => $id_data['id']))->save();
                                //file_put_contents("./log.txt", "res:".var_export($res,true)."sql:".var_export($merchantsModel->_sql(),true).PHP_EOL, FILE_APPEND);
                                if ($res !== false) {
                                    $this->ajaxReturn(array("code" => "success", "msg" => L('HEADER_UPLOAD_SUCCESS')));
                                } else {
                                    $this->ajaxReturn(array("code" => "error", "msg" => L('HEADER_UPLOAD_FAIL')));
                                }
                            } else {
                                $res = $merchantsModel->add();
                                if ($res) {
                                    $this->ajaxReturn(array("code" => "success", "msg" => L('HEADER_UPLOAD_SUCCESS')));
                                } else {
                                    $this->ajaxReturn(array("code" => "error", "msg" => L('HEADER_UPLOAD_FAIL')));
                                }
                            }
                            break;
                        case 'sfz':
                            //身份证正面
                            $merchantsModel->positive_id_card_img = $info['positive_id_card_img'] ? $info['positive_id_card_img']['savepath']. $info['positive_id_card_img']['savename'] : '';
                            //身份证反面
                            $merchantsModel->id_card_img = $info['id_card_img'] ? $info['id_card_img']['savepath']. $info['id_card_img']['savename'] : '';
                            if ($id_data['id'] > 0) {
                                $res = $merchantsModel->where(array('id' => $id_data['id']))->save();
                                //file_put_contents("./log.txt", "res:".var_export($res,true)."sql:".var_export($merchantsModel->_sql(),true).PHP_EOL, FILE_APPEND);
                                if ($res !== false) {
                                    $this->ajaxReturn(array("code" => "success", "msg" => L('IDCARD_UPLOAD_SUCCESS')));
                                } else {
                                    $this->ajaxReturn(array("code" => "error", "msg" => L('IDCARD_UPLOAD_FAIL')));
                                }
                            } else {
                                $res = $merchantsModel->add();
                                //file_put_contents("./log.txt", "msg:".$merchantsModel->getLastSql().PHP_EOL, FILE_APPEND);
                                if ($res) {
                                    $this->ajaxReturn(array("code" => "success", "msg" => L('IDCARD_UPLOAD_SUCCESS')));
                                } else {
                                    $this->ajaxReturn(array("code" => "error", "msg" => L('IDCARD_UPLOAD_FAIL')));
                                }
                            }
                            break;
                    }
                }
            }

        }
    }

    public function test()
    {
        $phone = "18777151347";
        $model = M("merchants_users");
        $users = $model->alias("u")
            ->field("ru.uid,ru.role_id,r.role_name,u.user_phone")
            ->join("left join " . C('DB_PREFIX') . "merchants_role_users as ru on ru.uid=u.id")
            ->join("left join " . C('DB_PREFIX') . "merchants_role as r on ru.role_id=r.id")
            ->where(array("user_phone" => $phone))
            ->find();
        switch ($users['role_id']) {
            case 2:
                $users['userInfo'] = M('merchants_agent')->where(array('uid' => $users['uid']))->find();
                break;
            case 3:
                $users['userInfo'] = M('merchants')->where(array('uid' => $users['uid']))->find();
                break;
        }
        dump($users);
    }


    public function test2()
    {
        $uid = "28";
        $this->findFirstAgent($uid);
    }


//    改变登录的小商户
    public function change_phone()
    {
        $model = M("merchants_users");
        $phone = I("phone");
        $token = I("token");
        $uid = M("token")->where("token='$token'")->getField("uid");
        $users = $model->alias("u")
            //->field("ru.uid,ru.role_id,r.role_name,u.user_phone,u.user_pwd")
            ->field("ru.uid,ru.role_id,u.user_phone,u.user_pwd,u.voice_open,u.cloud_voice,u.cash_pay,u.auth")
            ->join("left join " . C('DB_PREFIX') . "merchants_role_users as ru on ru.uid=u.id")
            //->join("left join ".C('DB_PREFIX')."merchants_role as r on ru.role_id=r.id")
            ->where(array("user_phone" => $phone))
            ->find();

        if ($users['role_id'] == 3) {
            $res = M('merchants')->field("id,status")->where(array('uid' => $users['uid']))->find(); //查看是否已经填写过商户资料
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
        if (in_array($users['role_id'], array(2, 3))) $users["role_full_name"] = $this->get_role_full_name($users['role_id'], $users['uid']);
        if (!$users["role_full_name"]) $users["role_full_name"] = '';

        //返回员工权限
        $this->check_employee_auth($users);
        unset($users['user_pwd']);
        //存储登录信息
        $users['token_add_time'] = time();
        //session($TOKEN, $users);
        $token_info = M("token")->where(array("uid" => $uid))->find();
        if ($token_info) M("token")->where(array("uid" => $uid))->save(array("userinfo" => json_encode($users)));
        $this->ajaxReturn(array("code" => "success", "msg" => L('LOGIN_SUCCESS')));
    }

//    切换门店
    public function store_down()
    {
        // 大商户模式
        $token = I("token");
        $u_id = M("token")->where("token='$token'")->getField("uid");
        $model = M("merchants_users");
        $merchants = M("merchants");
        $merchant = $merchants->where("uid=$u_id")->find();
        if ($merchant['mid'] == 0) {
            $mid = $merchant['id'];
            $uids = $merchants->where("mid='$mid'")->field("uid")->order("id asc")->select();
            $phones = array();
            $phones[] = array("user_phone" => $model->where("id=$u_id")->getField("user_phone"), "user_name" => $merchant['merchant_name']);
            foreach ($uids as $k => $v) {
                unset($ab);
                $ab = $v['uid'];
                $user_phone = $model->where("id=$ab")->getField("user_phone");
                $user_name = $merchants->where(array('uid' => $ab))->getField("merchant_name");
                if ($user_phone) $phones[] = array("user_phone" => $user_phone, "user_name" => $user_name);
            }
            $users['name'] = $phones;
            $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $users));
        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => "未开通多门店信息"));
        }
    }

    /**
     * 验证原有手机号
     */
    public function checkUserPhone()
    {
        if (IS_POST) {
            $this->checkLogin();
            $sms = I("sms");
            $phone = I("phone");
            $old_phone = M('merchants_users')->where(array('id' => $this->userId))->getField('user_phone');
            if ($old_phone != $phone) {
                $this->ajaxReturn(array("code" => "error", "msg" => '原手机号码错误'));
            }
            if (!$phone)
                $this->ajaxReturn(array("code" => "error", "msg" => L('PHONE_EMPTY')));
            // 验证验证码
            if (!$sms)
                $this->ajaxReturn(array("code" => "error", "msg" => L('SMS_EMPTY')));
            if ($sms != S($phone))
                $this->ajaxReturn(array("code" => "error", "msg" => L('SMS_ERROR')));

            $this->ajaxReturn(array("code" => "success", "msg" => '验证通过'));
        }
    }

    /**
     * 检查号码是否存在
     */
    public function checkExist()
    {
        if (IS_POST) {
            $phone = I('phone');
            $res = $this->checkUser($phone);
            if ($res) {
                $this->ajaxReturn(array('code' => 'error', 'msg' => '该手机号已存在!'));
            } else {
                $this->ajaxReturn(array('code' => 'success', 'msg' => 'not exist'));
            }
        }
    }

    /**
     * 更改用户手机号码
     */
    public function changeUserPhone()
    {
        // 执行修改手机号
        if (IS_POST) {
            $this->checkLogin();
            $phone = I("phone");    // 电话号码
            $sms = I("sms");        // 短信验证码
            $pwd = I("pwd");        // 登录密码
            $msg = '';
            // 验证手机号码
            if (!$phone) {
                $this->ajaxreturn_(L('PHONE_EMPTY'));
            }
            // 验证号码存在与否
            if ($this->checkUser($phone)) {
                $this->ajaxreturn_(L('USER_EXIT'));
            }
            // 验证验证码
            if (!$sms) {
                $this->ajaxreturn_(L('SMS_EMPTY'));
            }
            if ($sms != S($phone)) {
                $this->ajaxreturn_(L('SMS_ERROR'));
            }
            // 判断密码
            if (!$pwd) {
                $this->ajaxreturn_(L('PWD_EMPTY'));
            }
            $data['user_phone'] = $phone;
            $data['user_pwd'] = md5($pwd);
            $data['update_time'] = time();
            // 更新数据库
            $res = M("merchants_users")->where(array('id' => $this->userId))->save($data);
            if ($res) {
                $this->ajaxreturn_('号码更改成功', 'success');
            } else {
                $this->ajaxreturn_('号码更改失败');
            }
        }
    }

    /**
     * @param $code
     * @param $msg
     */
    public function ajaxreturn_($msg, $code = 'error')
    {
        $this->ajaxReturn(array("code" => "$code", "msg" => "$msg"));
    }

    /**
     * 设置余额支付密码
     */
    public function setPayPwd()
    {
        if (IS_POST) {
            $this->checkLogin();
            $pay_pwd = I('pay_pwd');
            if (empty($pay_pwd)) {
                $this->ajaxReturn(array("code" => "error", "msg" => L('PWD_EMPTY')));
            }
            if (strlen($pay_pwd) != 6) {
                $this->ajaxReturn(array("code" => "error", "msg" => L('PARAME_ERROR')));
            }

            $data['pay_pwd'] = md5(strtoupper(md5($pay_pwd)));
            $map['id'] = $this->userId;

            $res = M("Merchants_users")->where($map)->save($data);
            if ($res) {
                $this->ajaxReturn(array("code" => "success", "msg" => '设置成功'));
            } else {
                $this->ajaxReturn(array("code" => "error", "msg" => '密码未修改'));
            }
        }
    }

    /**
     * 微信回调
     */
    public function wx_pay_notify()
    {
        include_once("./log_.php");
        Vendor('WxPayPubHelper.WxPayPubHelper');

        //使用通用通知接口
        $notify = new \Notify_pub();

        //存储微信的回调
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        $notify->saveData($xml);

        //验证签名，并回应微信。
        //对后台通知交互时，如果微信收到商户的应答不是成功或超时，微信认为通知失败，
        //微信会通过一定的策略（如30分钟共8次）定期重新发起通知，
        //尽可能提高通知的成功率，但微信不保证通知最终能成功。
        if ($notify->checkSign() == FALSE) {
            $notify->setReturnParameter("return_code", "FAIL"); //返回状态码
            $notify->setReturnParameter("return_msg", "签名失败"); //返回信息
        } else {
            $notify->setReturnParameter("return_code", "SUCCESS"); //设置返回码
        }
        $returnXml = $notify->returnXml();
        echo $returnXml;

        //==商户根据实际情况设置相应的处理流程，此处仅作举例=======

        //以log文件形式记录回调信息
        //         $log_ = new Log_();
        $log_name = __ROOT__ . "./wx_notify_url.log";//log文件路径

        $this->log_result($log_name, "【接收到的notify通知】:\n" . $xml . "\n");

        if ($notify->checkSign() == TRUE) {
            if ($notify->data["return_code"] == "FAIL") {
                //此处应该更新一下订单状态，商户自行增删操作
                //log_result($log_name,"【通信出错】:\n".$xml."\n");
            } elseif ($notify->data["result_code"] == "FAIL") {
                //此处应该更新一下订单状态，商户自行增删操作
                //log_result($log_name,"【业务出错】:\n".$xml."\n");
            } else {
                //此处应该更新一下订单状态，商户自行增删操作,//在这里操作订单表
                //log_result($log_name,"【支付成功】:\n".$xml."\n");

                $out_trade_no = $notify->data["out_trade_no"];//回调的订单号
                $tradesn = $notify->data["transaction_id"];//回调的交易号

                //将订单状态变为已付款
            }
        }
    }

    /**
     * @param $file
     * @param $word
     */
    public function log_result($file, $word)
    {
        $fp = fopen($file, "a");
        flock($fp, LOCK_EX);
        fwrite($fp, "执行日期：" . strftime("%Y-%m-%d-%H：%M：%S", time()) . "\n" . $word . "\n\n");
        flock($fp, LOCK_UN);
        fclose($fp);
    }

    /**
     * 验证设置支付密码的短信验证码
     */
    public function checkPayPwdSms()
    {
        if (IS_POST) {
            $sms = I('sms');
            // 获取用户电话号码
            $phone = $this->userInfo['user_phone'];
            // 验证验证码
            if (!$sms)
                $this->ajaxReturn(array("code" => "error", "msg" => L('SMS_EMPTY')));
            if ($sms != S($phone))
                $this->ajaxReturn(array("code" => "error", "msg" => L('SMS_ERROR')));
            $this->ajaxReturn(array("code" => "success", "msg" => '验证通过'));
        }
    }
}