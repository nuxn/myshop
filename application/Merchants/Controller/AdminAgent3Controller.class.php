<?php
/**
 * 后台首页
 */
namespace Merchants\Controller;

use Common\Controller\AdminbaseController;
use Think\Page;

class AdminAgent3Controller extends AdminbaseController
{

    public function _initialize()
    {

        empty($_GET['upw']) ? "" : session("__SP_UPW__", $_GET['upw']);//设置后台登录加密码
        parent::_initialize();
        $this->initMenu();
    }

    public function index()
    {

        $map = array();
        $model = M("merchants_agent");

        $user_phone = I("user_phone");
        if ($user_phone) {
            $map['user_phone'] = array('like', "%$user_phone%");
            $this->assign('user_phone', $user_phone);
        }

        $agent_name = I("agent_name");
        if ($agent_name) {
            $map['agent_name'] = array('like', "%$agent_name%");
            $this->assign('agent_name', $agent_name);
        }

        $agent_mode = I("agent_mode");
        if ($agent_mode != "-1" && $agent_mode != '') {
            if ($agent_mode >= 0) {
                $map['agent_mode'] = $agent_mode;
                $this->assign('agent_mode', $agent_mode);
            } else {
                $this->assign('agent_mode', '-1');
            }
        } else {
            $this->assign('agent_mode', '-1');
        }

        $is_first_agent = I("is_first_agent");
        if ($is_first_agent != "-1" && $is_first_agent != '') {
            if ($is_first_agent >= 0) {
                $map['is_first_agent'] = $is_first_agent;
                $this->assign('is_first_agent', $is_first_agent);
            } else {
                $this->assign('is_first_agent', '-1');
            }
        } else {
            $this->assign('is_first_agent', '-1');
        }

        $status = I("status");
        if ($status != "-1" && $status != '') {
            if ($status >= 0) {
                $map['status'] = $status;
                $this->assign('status', $status);
            } else {
                $this->assign('status', '-1');
            }
        } else {
            $this->assign('status', '-1');
        }

        $start_time = I("start_time");
        $end_time = I("end_time");
        if (strtotime($start_time) > strtotime($end_time)) {
            $this->error("开始时间不能大于结束时间");
        }
        if (!empty($start_time) && !empty($end_time)) {
            $map[C('DB_PREFIX') . "merchants_agent.add_time"] = array('between', array(strtotime($start_time), strtotime($end_time)));
            $this->assign('start_time', $start_time);
            $this->assign('end_time', $end_time);
        } else {
            if ($start_time) {
                $map[C('DB_PREFIX') . "merchants_agent.add_time"] = array('gt', strtotime($start_time));
                $this->assign('start_time', $start_time);
            }

            if ($end_time) {
                $map[C('DB_PREFIX') . "merchants_agent.add_time"] = array('lt', strtotime($end_time));
                $this->assign('end_time', $end_time);
            }
        }

//        $p = !empty($_GET["p"]) ? $_GET['p'] : 1;
//        $data = $model->field(C('DB_PREFIX') . "merchants_agent.*," . C('DB_PREFIX') . "merchants_users.user_phone")
//            ->join(' left JOIN  ' . C('DB_PREFIX') . 'merchants_users ON ' . C('DB_PREFIX') . 'merchants_users.id = ' . C('DB_PREFIX') . 'merchants_agent.uid')
//            ->page($p, C('ADMIN_PAGE_ROWS'))
//            ->where($map)
//            ->order('id desc')
//            ->select();
//
//        $page = new Page(
//            $model->field(C('DB_PREFIX') . "merchants_agent.*," . C('DB_PREFIX') . "merchants_users.user_phone")
//                ->join(' left JOIN  ' . C('DB_PREFIX') . 'merchants_users ON ' . C('DB_PREFIX') . 'merchants_users.id = ypt_merchants_agent.uid')
//                ->where($map)
//                ->count(),
//            C('ADMIN_PAGE_ROWS')
//        );

        $model->field(C('DB_PREFIX') . "merchants_agent.*," . C('DB_PREFIX') . "merchants_users.user_phone")
            ->join(' left JOIN  ' . C('DB_PREFIX') . 'merchants_users ON ' . C('DB_PREFIX') . 'merchants_users.id = ypt_merchants_agent.uid')
            ->where($map);
        $count=$model->count();
        $page = $this->page($count, 20);

        $model->limit($page->firstRow , $page->listRows)->order("id asc");
        $this->assign("page", $page->show('Admin'));

        $data = $model->field(C('DB_PREFIX') . "merchants_agent.*," . C('DB_PREFIX') . "merchants_users.user_phone")
            ->join(' left JOIN  ' . C('DB_PREFIX') . 'merchants_users ON ' . C('DB_PREFIX') . 'merchants_users.id = ' . C('DB_PREFIX') . 'merchants_agent.uid')
//            ->page($p, C('ADMIN_PAGE_ROWS'))
            ->where($map)
            ->order('id desc')
            ->select();
//        dump($data);
        $agency_business = array("二维码收款", "双屏收款系统", "POS机");
        foreach ($data as $k => $val) {
            $agency_business_arr = explode(",", $val['agency_business']);
            foreach ($agency_business as $key => $value) {
                if (in_array($key, $agency_business_arr)) {
                    $data[$k]['agency_business_str'].= $value.",";
                }
            }
            $val[$k]['id_number']=decrypt($val['id_number']);
            $val[$k]['bank_account_no']=decrypt($val['bank_account_no']);
            $data[$k]['agency_business']= rtrim($data[$k]['agency_business_str'],",");
        }
//        dump($data);

        $this->assign('agent_list', $data);
//        $this->assign('page', $page->show());
        $this->display();
    }



    public function add()
    {
        if (IS_POST) {
            $uid = I("uid");
            if (empty($uid)) {
                $this->ajaxReturn(array("code" => '2', 'msg' => '请先添加用户'));
            }
            if(!I("agent_style"))$this->ajaxReturn(array("code" => '2', 'msg' => '请选择代理方式'));
            if(!I("wx_rate"))$this->ajaxReturn(array("code" => '2', 'msg' => '微信的基准费率'));
            if(!I("ali_rate"))$this->ajaxReturn(array("code" => '2', 'msg' => '支付宝的基准费率'));
            $merchant_name = I("agent_name");
            if (empty($merchant_name)) {
                $this->ajaxReturn(array("code" => '3', 'msg' => '代理名称不能为空'));
            }

            $province = I("province");
            if ($province == "--请选择省份--") {
                $this->ajaxReturn(array("code" => '4', 'msg' => '--请选择省份--'));
            }

            $city = I("city");
            if ($city == "--请选择城市--") {
                $this->ajaxReturn(array("code" => '5', 'msg' => '--请选择城市--'));
            }

            $county = I("county");
            if ($county == "--请选择地区--") {
                $this->ajaxReturn(array("code" => '6', 'msg' => '--请选择地区--'));
            }

            $address = I("address");
            if (empty($address)) {
                $this->ajaxReturn(array("code" => '7', 'msg' => '请填写详细地址'));
            }

            $agent_mode = I("agent_mode");
            if (!isset($agent_mode) || $agent_mode == "-1") {
                $this->ajaxReturn(array("code" => '8', 'msg' => '请选择代理类型'));
            }

            $agency_business = I("agency_business");
            if (!isset($agency_business)) {
                $this->ajaxReturn(array("code" => '9', 'msg' => '请选择代理业务'));
            }

            $agent_type = I("agent_type");
            if (!isset($agent_type)) {
                $this->ajaxReturn(array("code" => '10', 'msg' => '请选择新增类型'));
            } else {
                if ($agent_type == 2) {
                    $referrer = I("referrer", "trim");
                    if (empty($referrer)) {
                        $this->ajaxReturn(array("code" => '11', 'msg' => '请填写推荐人'));
                    }
                }
            }

            $model = M("merchants_agent");
            $data = $model->create();
            
            if ($data) {
                $data['add_time'] = time();
                $data['status'] = '1';
                $data['is_first_agent']=1;
                $data['agency_business'] = implode(",", $agency_business);
                if ($model->add($data)) {
                    $this->ajaxReturn(array('code' => '1', 'msg' => '添加成功'));
                } else {
                    $this->ajaxReturn(array('code' => '0', 'msg' => '添加失败'));
                }
            }
        } else {
            $this->display();
        }
    }


    public  function  detail(){
        $id = I("id");
        $data = M("merchants_agent")->field(C('DB_PREFIX') ."merchants_agent.* ,".C('DB_PREFIX')."merchants_users.user_phone")
                                    ->join(' left JOIN  ' . C('DB_PREFIX') . 'merchants_users ON ' . C('DB_PREFIX') . 'merchants_users.id = ypt_merchants_agent.uid')
                                    ->where(array(C('DB_PREFIX') ."merchants_agent.id" => $id))
                                    ->find();
        $agency_business_str = $data['agency_business'];
        $agency_business_arr = explode(",", $agency_business_str);
        $agency_business = array("二维码收款", "双屏收款系统", "POS机");
        $arr = array();
        foreach ($agency_business as $k => $val) {
            if (in_array($k, $agency_business_arr)) {
                $arr[$k]['name'] = $val;
                $arr[$k]['key'] = $k;
            }
        }
        $this->assign('agency_business', $arr);
        $this->assign("data", $data);
        $this->display();
    }


    public function add_user()
    {
        $user_phone = I("user_phone", "trim");
//        预留到可以添加下级商户
//        $pid_phone=I("pid_phone","trim");
//        $user=M("merchants_users")->alias("m")
//            ->join("right join __MERCHANTS_ROLE_USERS__ ur on ur.uid=m.id")
//            ->field("m.*,ur.role_id")
//            ->where("user_phone=$pid_phone")
//            ->find();
//        if($user['role_id'] == 4||$user['role_id'] == 5||$user['role_id'] == 6){
//            $agent_id=$user['agent_id'];
//            $pid=$user['id'];
//        }elseif ($user['role_id'] == 2){
//            $agent_id=$user['id'];
//            $pid=$user['id'];
//        }

        if ($user_phone) {
            if(!isMobile($user_phone)){$this->ajaxReturn(array("code" => '0', "msg" => "用户的手机号码输入不正确"));}
            if ($this->checkUser($user_phone)) {
                $this->ajaxReturn(array("code" => '0', "msg" => "用户已存在"));
            } else {
                $data['user_phone'] = $user_phone;
                $data['user_pwd'] = md5("123456");
                $data['ip_address'] = get_client_ip();
                $data['user_name'] =I("agent_name");
                $data['add_time'] = time();
                $data['agent_id']=0;
//                $data['agent_id']=$agent_id;
//                $data['pid']=$pid;
                $res = M("merchants_users")->add($data);
                if ($res) {
                    //添加进角色表
                    $role_arr = array();
                    $role_arr['uid'] = $res;
                    $role_arr['role_id'] = '2'; // 代理角色
                    $role_arr['add_time'] = time();
                    if (M("merchants_role_users")->add($role_arr)) {
                        $this->ajaxReturn(array("code" => 1, "msg" => "添加用户成功", "uid" => $res));
                    } else {
                        $this->ajaxReturn(array("code" => 3, "msg" => "添加用户成功,添加用户角色不成功", "uid" => $res));
                    }
                } else {
                    $this->ajaxReturn(array("code" => 4, "msg" => "添加用户失败"));
                }
            }
        } else {
            $this->ajaxReturn(array("code" => '2', "msg" => "请填写用户手机号"));
        }
    }


    public function edit()
    {
        if (IS_GET) {
            $id = I("id");
            $data = M("merchants_agent")->where(array('id' => $id))->find();
            $agency_business_str = $data['agency_business'];
            $agency_business_arr = explode(",", $agency_business_str);
            $agency_business = array("二维码收款", "双屏收款系统", "POS机");
            $arr = array();
            foreach ($agency_business as $k => $val) {
                $arr[$k]['name'] = $val;
                $arr[$k]['key'] = $k;
                if (in_array($k, $agency_business_arr)) {
                    $arr[$k]['checked'] = 1;
                } else {
                    $arr[$k]['checked'] = 0;
                }
            }
            $agent=M("merchants_users")->where(array('id'=>$data['uid']))->getField('agent_id');
            $this->assign('agent_first',$agent);
            $this->assign('agency_business', $arr);
            $this->assign("data", $data);
            $this->display();
        }
        if (IS_POST) {
            $uid = I("uid");
            if (empty($uid)) {
                $this->ajaxReturn(array("code" => '2', 'msg' => '请先添加用户'));
            }

            $merchant_name = I("agent_name");
            if (empty($merchant_name)) {
                $this->ajaxReturn(array("code" => '3', 'msg' => '代理名称不能为空'));
            }

            $province = I("province");
            if ($province == "--请选择省份--") {
                $this->ajaxReturn(array("code" => '4', 'msg' => '--请选择省份--'));
            }

            $city = I("city");
            if ($city == "--请选择城市--") {
                $this->ajaxReturn(array("code" => '5', 'msg' => '--请选择城市--'));
            }

            $county = I("county");
            if ($county == "--请选择地区--") {
                $this->ajaxReturn(array("code" => '6', 'msg' => '--请选择地区--'));
            }

            $address = I("address");
            if (empty($address)) {
                $this->ajaxReturn(array("code" => '7', 'msg' => '请填写详细地址'));
            }

            $agent_mode = I("agent_mode");
            if (!isset($agent_mode) || $agent_mode == "-1") {
                $this->ajaxReturn(array("code" => '8', 'msg' => '请选择代理方式'));
            }

            $agency_business = I("agency_business");
            if (!isset($agency_business)) {
                $this->ajaxReturn(array("code" => '9', 'msg' => '请选择代理业务'));
            }

            $agent_type = I("agent_type");
            if (!isset($agent_type)) {
                $this->ajaxReturn(array("code" => '10', 'msg' => '请选择新增类型'));
            } else {
                if ($agent_type == 2) {
                    $referrer = I("referrer", "trim");
                    if (empty($referrer)) {
                        $this->ajaxReturn(array("code" => '11', 'msg' => '请填写推荐人'));
                    }
                } else {
                    $data['referrer'] = "";
                }
            }

            $model = M("merchants_agent");
            $agent_old =$model->where(array('uid'=>$uid))->find();
            file_put_contents('./agent.log', date("Y-m-d H:i:s")."原来的代理" . json_encode($agent_old) . PHP_EOL.PHP_EOL, FILE_APPEND | LOCK_EX);
            $data = $model->create();
            
            if ($data) {
                $data['agency_business'] = implode(',', $data['agency_business']);
                $data['is_first_agent']=1;
                $res = $model->save($data);
                if ($res !== false) {
                    $data['update_time'] = time();
                    file_put_contents('./agent.log', date("Y-m-d H:i:s") ."新的代理". json_encode($data) . PHP_EOL.PHP_EOL, FILE_APPEND | LOCK_EX);
                    $this->ajaxReturn(array("code" => '1', 'msg' => '修改成功'));
                } else {
                    $this->ajaxReturn(array("code" => '0', 'msg' => '修改失败'));
                }
            }
        }
    }

    public function del()
    {
        $id = I("id");
        $res = M("merchants_agent")->where(array('id' => $id))->delete();
        if ($res) {
            $this->success('删除成功', U('adminAgent/index'));
        } else {
//            $this->ajaxReturn(array("states" => 'fail','referer'=>'adminIndex/index','info'=>'删除成功'));
            $this->success('删除成功');
        }
    }

    public function exchangeSatatus()
    {

        if (IS_POST) {
            $status = I("status");
            $id = I("id");
            if (isset($status)) {
                $res = M("merchants_agent")->where(array('id' => $id))->setField(array('status' => $status));
                if ($res !== false) {
                    $this->ajaxReturn(array('code' => '1', 'msg' => '修改成功'));
                } else {
                    $this->ajaxReturn(array('code' => '0', 'msg' => '修改失败'));
                }
            }
        }

    }


    /**
     * @param $referrer
     * @param $uid
     * @return array
     * @instruction 检查推荐人是否在用户表，存在则修改用户的上级，不存在则返回
     */
    private function checkReferrer($referrer, $uid)
    {
        $model = M("merchants_users");
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

}

