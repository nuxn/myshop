<?php
namespace Api\Controller;

use Common\Controller\ApibaseController;
//use Think\Controller;

class  MerchantsController extends  ApibaseController
{
    #申请提现
    public function withdraw()
    {
        if(IS_POST){
            //今日提现次数
            $today_num = M('withdraw')->where(array('uid'=>$this->userId,'add_time'=>array('EGT',strtotime(date('Y-m-d',time())))))->count();
            if($today_num>0) $this->err('今日提现次数已上限');

            $merchantsInfo = M('merchants_users')->where(array('id' => $this->userId))->find();
            $amount = I('post.amount');
            $balance = $merchantsInfo['card_balance'];
            if(!$amount) $this->err('price error');
            if($balance < $amount) $this->err('储值余额不足');
            //$rate = $merchantsInfo['card_rate'];
            //if(!$rate)  $this->err('代理未设置');

            # 入库数据
            $insertData['price'] = $amount;

            //$insertData['rate'] = $rate;
            //$insertData['real_price'] = $amount * $rate/100;
            //$insertData['rate_price'] = $amount - $insertData['real_price'];
            $insertData['rate'] = 100;
            $insertData['real_price'] = $amount;#提现不扣手续费
            $insertData['rate_price'] = 0;#提现不扣手续费

            $insertData['uid'] = $this->userId;
            $insertData['balance'] = $balance - $amount;
            $insertData['status'] = '0';
            $insertData['bank_id'] = I('id','0');//收款卡id
            $insertData['add_time'] = time();
            $res = M('withdraw')->add($insertData);
            if($res){
                $now_card_balance = $merchantsInfo['card_balance']-$amount;
                $saveRes = M('merchants_users')->where(array('id' => $this->userId))->save(array('card_balance' => $now_card_balance));
                if($saveRes){
                    $data = M('withdraw w')->join('left join ypt_merchants_bank b on b.id=w.bank_id')->where(array('w.id'=>$res))
                        ->field("w.price,w.rate_price,w.bank_id,w.uid,ifnull(bank_account,'') as bank_account,ifnull(bank_account_no,'') as bank_account_no")
                        ->find();
                    if($data['bank_id']==0) {
                        $bank = M('merchants')->where(array('uid'=>$data['uid']))->field('bank_account,bank_account_no')->find();
                        $data['bank_account'] = $bank['bank_account'];
                        $data['bank_account_no'] = decrypt($bank['bank_account_no']);
                    }
                    if($data['bank_account_no']) $data['bank_account_no'] = substr($data['bank_account_no'],-4,4);
                    M('balance_log')->add(array('price'=>-$amount,'ori_price'=>-$amount,'rate_price'=>'0','add_time'=>time(),'remark'=>'提现扣除余额','mid'=>$this->userId,'balance'=>$insertData['balance']));
                    $this->ajaxReturn(array("code" => "success","msg"=>"成功", "data"=>$data));
                } else {
                    $this->err('insert u error');
                }
            } else {
                $this->err('insert w error');
            }
        }
    }

    #提现列表
    public function withdrawList()
    {
        $page = I('post.page','1');
        $data = M('withdraw')->where(array('uid' => $this->userId))->order('id desc')->limit(($page-1)*10,10)->select();
        if($data){
            $this->succ($data);
        } else {
            $this->succ();
        }
    }

    #余额变动列表
    public function balance_log()
    {
        $page = I('post.page','1');
        $status = I('post.status');
        #1是支出，2是收入
        if ($status==1) {
            $map['price'] = array('lt','0');
        } elseif ($status==2) {
            $map['price'] = array('gt','0');
        }
        $map['mid'] = $this->userId;
        $data = M('balance_log')->where($map)
            ->field('id,ori_price,rate_price,add_time,remark')
            ->order('id desc')->limit(($page-1)*10,10)->select();
        if($data){
            $this->ajaxReturn(array('code'=>'success','msg'=>'成功','data'=>$data));
        } else {
            $this->ajaxReturn(array('code'=>'success','msg'=>'成功','data'=>array()));
        }
    }

    #余额变动详情
    public function balance_detail()
    {
        $id = I('post.id');
        if(!$id) $this->ajaxReturn(array('code'=>'error','msg'=>'id is empty'));
        $data = M('balance_log')->where(array('id'=>$id))
            ->field('price,ori_price,rate_price,add_time,remark,order_sn,balance')->find();
        if($data){
            $this->ajaxReturn(array('code'=>'success','msg'=>'成功','data'=>$data));
        } else {
            $this->ajaxReturn(array('code'=>'success','msg'=>'成功','data'=>array()));
        }
    }

    public function qrcodeLogin()
    {
        get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/test/','qrcodeLogin','qrcodeLogin', json_encode($_POST));
        $client = I('post.client');
        if($client){
            $addData['status'] = 1;
            $addData['uid'] = $this->userId;
            $addData['login_time'] = date('Y-m-d H:i:s');
            $res = M("users_login_log")->where(array('client'=>$client))->save($addData);
            if($res){
                $this->succ("login success");
            } else {
                $this->err("login failure");
            }
        } else {
            $this->err('parameter not enough');
        }
    }

    public function appPay()
    {
        ($type = I('type')) || $this->err('type is empty');
        if(IS_POST){
            ($price = I('price')) || $this->err('price is empty');
            ($pay_way = I('pay_way')) || $this->err('pay_way is empty');// zfb  wx

            $data['u_id'] = $this->userId;
            $data['order_sn'] = date('YmdHis').mt_rand(100000,999999);
            $data['price'] = $price;
            $order_id = M('order_loan')->add($data);
            if($order_id){
                # 判断支付所购买的服务
                switch ($type) {
                    # 贷款服务
                    case 'loan':
                        $res = A("Pay/Alipay")->payOpenLoan($data);
                        $this->succ($res);
                        break;
                    default:
                        $this->err('支付异常');
                }
            } else {
                $this->err();
            }
        } else {
            $this->err('请求异常');
        }
    }

    # 获取开通贷款的价格
    public function getPrice()
    {  $this->userInfo['']
        ($type = I('type')) || $this->err('type is empty');
        if($type == 'loan'){
            $price = '99.00';
        } else {
            $this->err('请求异常');
        }
        $this->succ(array('price'=>$price));
    }

    # 获取供应商信息
    public function get_sup_info()
    {
        $this->userId=3241;
        $data = M('merchants_supplier')
            ->where(array('mu_id'=>$this->userId))
            ->field('supplier_name,logo,send_min_price,send_day,max_send_day,send_time_range')
            ->find();
        $data['send_time_range'] = explode(',',$data['send_time_range']);
        $this->ajaxReturn(array('code'=>'success','msg'=>'成功','data'=>$data));
    }
    # 设置供应商信息
    public function set_sup_info()
    {
        ($logo = I('logo')) || $this->err('logo is empty');
        ($send_min_price = I('send_min_price')) || $this->err('send_min_price is empty');
        ($send_day = I('send_day')) || $this->err('send_day is empty');
        ($max_send_day = I('max_send_day')) || $this->err('max_send_day is empty');
        ($send_time_range = I('send_time_range')) || $this->err('send_time_range is empty');
        $data = array(
            'logo' => $host = 'http://' . $_SERVER['HTTP_HOST'].$logo,
            'send_min_price' => $send_min_price,
            'send_day' => $send_day,
            'max_send_day' => $max_send_day,
            'send_time_range' => $send_time_range
        );
        $res = M('merchants_supplier')->where(array('mu_id'=>$this->userId))->save($data);
        if ($res !== false) {
            $this->ajaxReturn(array('code'=>'success','msg'=>'成功'));
        }else{
            $this->err('保存失败');
        }
    }

    public function add_user()
    {
//        找到当前用户的上级
        $user_phone = I("user_phone",'', "trim");
        $referrer = I("referrer",'', "trim");
        $merchant_name = I("merchant_name",'', "trim");

        if (!isMobile($user_phone)) {
            $this->ajaxReturn(array("code" => '0', "msg" => "商户手机号码格式不正确"));
        }elseif(M('merchants_users')->where(array('user_phone'=>$user_phone))->find()){
            $this->ajaxReturn(array("code" => '0', "msg" => "商户手机号码已存在"));
        }
        if (empty($referrer)) {
//                手机号码的检测
            $user['agent_id'] = 1;
            $user['pid'] = 0;
        } else {
            //if (!isMobile($referrer)) $this->ajaxReturn(array("code" => "error", "msg" => '用户的上级手机号码输入错误'));
            if (!is_numeric($referrer) || strlen($referrer)!=11) $this->ajaxReturn(array("code" => "error", "msg" => '用户的上级手机号码输入错误'));
            $p_id = M("merchants_users")->where("user_phone=$referrer")->getField("id");
            if (!$p_id) {
                $this->ajaxReturn(array("code" => "error", "msg" => '你添加的上级手机号码不存在'));
            }
            #is_employee 是否是员工
            $is_employee = M("merchants_users")->where("id=$p_id")->getField("is_employee");
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
                $u_id = M("merchants_users")->where("id=$p_id")->getField("pid");
                $user['agent_id'] = $u_id;
                $user['pid'] = $p_id;
            }
        }

        if ($user_phone) {
            $data['user_phone'] = $user_phone;
            $data['user_name'] = $merchant_name;
            $data['user_pwd'] = md5("123456");
            $data['ip_address'] = get_client_ip();
            $data['add_time'] = time();
//                用户的上级信息
            $data['agent_id'] = $user['agent_id'];
            $data['pid'] = $user['pid'];
            $res = M("merchants_users")->add($data);
            if ($res) {
                D('Merchants/Merchants')->addDefaultRole($res);
                //添加进商户表
                $merchant_add['uid'] = $res;
                $merchant_add['merchant_name'] = $merchant_name;
                $merchant_add['referrer'] = $referrer;
                $merchant_add['add_time'] = $merchant_add['update_time'] = time();
                $merchant_add['status'] = 5;
                M('merchants')->add($merchant_add);
                //添加进角色表
                $role_arr = array();
                $role_arr['uid'] = $res;
                $role_arr['role_id'] = '3'; // 商户角色
                $role_arr['add_time'] = time();
                if (M("merchants_role_users")->add($role_arr)) {
                    $user_data = array(
                        'user_login'=>$user_phone,
                        'user_pass'=>sp_password('123456'),
                        'user_nicename'=>$user_phone,
                        'create_time'=>date('Y-m-d H:i:s'),
                        'mobile'=>$user_phone,
                        'platform'=>1,
                        'muid'=>$res,
                        'pid'=>$user['agent_id']?:1,
                    );
                    $id = M('users')->add($user_data);
                    $ro['role_id'] = 4;
                    $ro['user_id'] = $id;
                    M('role_user')->add($ro);
                    $this->ajaxReturn(array("code" => 'success', "msg" => "添加用户成功", "uid" => $res));
                } else {
                    $this->ajaxReturn(array("code" => 'success', "msg" => "添加用户成功,添加用户角色不成功", "uid" => $res));
                }
            } else {
                $this->ajaxReturn(array("code" => 'error', "msg" => "添加用户失败"));
            }
        } else {
            $this->ajaxReturn(array("code" => 'error', "msg" => "请填写用户手机号"));
        }
    }

    /**
     * 商品图片上传编辑
     */
    public function upload_picture()
    {
        $info = array();//存储图片
        $pic_root_path = C('_WEB_UPLOAD_');
        if ($_FILES) {
            $upload = new \Think\Upload();
            $upload->maxSize = 0;
            $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
            $upload->rootPath = C('_WEB_UPLOAD_');
            $upload->savePath = 'merchants/';
            $upload->saveName = uniqid();//保持文件名不变
            $info = $upload->upload();
            if (!$info)$this->error($upload->getError());
        }
        if($info['img']){
            $img = $pic_root_path . $info['img']['savepath'] . $info['img']['savename'];
        }
        $this->ajaxReturn(array('code'=>'success','msg'=>'上传成功','data'=>$img));
    }

    private function succ($data='')
    {
        header('Content-Type:application/json; charset=utf-8');
        $return = array(
            'code' => 'success',
            'msg' => "成功",
        );
        if(!empty($data)){
            $return['data'] = $data;
        }
        $this->ajaxReturn($return);
    }
    
    private function err($msg = '网络错误，请重试')
    {
        header('Content-Type:application/json; charset=utf-8');
        $return['code'] = 'error';
        $return['msg'] = $msg;
        exit(json_encode($return));
    }

    public function get_wx_category()
    {
        if(IS_POST){
            $this->checkLogin();
            $data =  D('MerchantsWxstore')->get_wx_category();
            $data = $this->getTree($data, 0);
            $this->succ($data);
        }
    }

    function getTree($data, $pId)
    {
        $tree = '';
        foreach($data as $k => $v)
        {
            if($v['pid'] == $pId)
            {
                //父亲找到儿子
                $subs = $this->getTree($data, $v['id']);
                if($subs){
                    $v['subs'] = $subs;
                } else {
                    $v['subs'] = array();
                }
                $tree[] = $v;
            }
        }
        return $tree;
    }

    public function get_mch_uid($userId)
    {
        $role_id = M('merchants_role_users')->where("uid=$userId")->getField('role_id');
        $mu_id = $userId;
        if($role_id != 3){
            $this->ajaxReturn(array('code'=>'error', 'msg'=>'员工无法操作'));
            $mu_id = M('merchants_users')->where("id=$userId")->getField('boss_id');
        }

        return $mu_id;
    }

    public function create_wxstore()
    {
        if(IS_POST){
            $this->checkLogin();
            $this->userId = $this->get_mch_uid($this->userId);
            $wxstore = D('MerchantsWxstore');
            // 判断是否已经创建了门店
            if($wxstore->checkStore($this->userId))
                $this->err("商户已存在门店");

            $input = I('');
            $a = strpos($input['categories'],'--');
            if($a){
                $input['categories'] = substr($input['categories'],0,$a-1);
            }
            get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Api/','Merchants_wxstore','创建门店参数', json_encode($input));
//            if($this->userId == '494'){
//                $this->err("测试账户");
//            }
            // 从商户在洋仆淘系统中的进件信息中获取创建门店所需
            $mch_info = $wxstore->get_mch_info($this->userId);
            if(!$mch_info)
                $this->err("商户资料不齐全");
            
            $into = array_merge($input,$mch_info);
            $wx_res = $wxstore->addpio($input,$into);
            if($wx_res->errcode == 0){
                $into['poi_id'] = $wx_res->poi_id;
                $into['mu_id'] = $this->userId;
                $wxstore->into_wxstore($into);
                // 门店创建成功后加入会员卡的适用门店列表
                if(!$input['id']){
                    $this->err("缺少必要参数");
                }
                $wxstore->card_addpoi($input['id'], $wx_res->poi_id);
                $this->succ();
                exit;
            } else {
                $this->err(get_store_error($wx_res->errcode));
            }

        }
    }

    public function get_wxstore()
    {
        if(IS_POST){
            $this->checkLogin();
            $wxstore = D('MerchantsWxstore');
            $data = $wxstore->field("business_name,telephone,categories,open_time,introduction")->where("mu_id=$this->userId")->find();
            if($data){
                $this->succ($data);
            } else {
                $return = array(
                    'code' => 'success',
                    'msg' => urlencode("没有门店"),
                    'data' => (object)null,
                );
                header('Content-Type:application/json; charset=utf-8');
                exit(urldecode(json_encode($return)));
            }
        }
    }

    public function edit_wxstore()
    {
        if(IS_POST){
            $this->checkLogin();
            $this->userId = $this->get_mch_uid($this->userId);
            $input = I();
            $wxstore = D('MerchantsWxstore');
            $poi_id = $wxstore->get_poi_id($this->userId);
            if(!$poi_id)  $this->err("商户未创建微信门店");
            $wx_res = $wxstore->updatepoi($input,$poi_id);
            if($wx_res->errcode == 0){
                $wxstore->where("mu_id=$this->userId")->save($input);
                $this->succ();
            } else {
                $this->err(get_store_error($wx_res->errcode));
            }
        }
    }

    public function delete_wxstore()
    {
        $wxstore = D('MerchantsWxstore');
        $res = $wxstore->del_wxstore($this->userId);
        exit($res);
    }
}