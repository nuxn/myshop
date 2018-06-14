<?php
/**
 * 后台首页
 */
namespace Merchants\Controller;

use Common\Controller\AdminbaseController;
use Think\Page;

class AdminIndexController extends AdminbaseController
{
    protected $merchants;
    protected $users;
    protected $user_role;
    protected $upwzs;
    protected $cates;
    public function _initialize()
    {
        empty($_GET['upw']) ? "" : session("__SP_UPW__", $_GET['upw']);//设置后台登录加密码
        parent::_initialize();
        $mids = M("merchants")->where("mid=0")->select();
        $this->merchants = M("merchants");
        $this->users = M("merchants_users");
        $this->user_role = M("merchants_role_users");
        $this->upwzs = M("merchants_upwz");
        $this->cates = M("merchants_cate");
        $this->assign("mids", $mids);
        $this->initMenu();
    }

    public function check()
    {
        $url = '/data/upload/merchants/2017-11-04/59fd7a561e428.jpg';
        $arr['buffer']='@'.$_SERVER['DOCUMENT_ROOT'].$url;
        var_dump($arr);exit;
        $url_getlog="https://api.weixin.qq.com/cgi-bin/media/uploadimg?access_token=".get_weixin_token();
        $result = request_post($url_getlog, $arr);
        get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/weixin/','upload_image','AdminIndex上传图片',$result);
        $result = json_decode($result, true);
        $logo_url=$result['url'];
        var_dump($logo_url);
        $this->ajaxReturn(213);
    }

    public function index()
    {
        $map = array();
        $model = M("merchants");
        $user_phone = I("user_phone",'','trim');
        if ($user_phone) {
            $map['user_phone'] = array('like', "%$user_phone%");
            $this->assign('user_phone', $user_phone);
        }
        $merchant_name = I("merchant_name",'','trim');
        if ($merchant_name) {
            $map['merchant_name'] = array('like', "%$merchant_name%");
            $this->assign('merchant_name', $merchant_name);
        }
        $agent_name = I("agent_name",'','trim');
        if ($agent_name) {
            $map['agent_name'] = array('like', "%$agent_name%");
            $this->assign('agent_name', $agent_name);
        }
        $mid = I("mid");
        if ($mid) {
            $map['mid'] = $mid;
            $this->assign('mid', $mid);
        }
        $agency_business = I("agency_business");
        if ($agency_business) {
            $map['agency_business'] = $agency_business;
            $this->assign('agency_business', $agency_business);
        }
        $account_type = I("account_type");
        if ($account_type != "-1" && $account_type != '') {
            if ($account_type >= 0) {
                $map['account_type'] = $account_type;
                $this->assign('account_type', $account_type);
            } else {
                $this->assign('account_type', '-1');
            }
        } else {
            $this->assign('account_type', '-1');
        }

        $is_miniapp = I("is_miniapp");
        if ($is_miniapp) {
            $map['is_miniapp'] = $is_miniapp;
            $this->assign('is_miniapp', $is_miniapp);
        }

        /*$status = I("status");
        if ($status != "-1" && $status != '') {
            if ($status >= 0) {
                $map['ypt_merchants.status'] = $status;
                $this->assign('status', $status);
            } else {
                $this->assign('status', '-1');
            }
        } else {
            $this->assign('status', '-1');
        }*/

        $start_time = I("start_time");
        $end_time = I("end_time");
        if (strtotime($start_time) > strtotime($end_time)) {
            $this->error("开始时间不能大于结束时间");
        }
        if (!empty($start_time) && !empty($end_time)) {
            $map[C('DB_PREFIX') . "merchants.add_time"] = array('between', array(strtotime($start_time), strtotime($end_time)));
            $this->assign('start_time', $start_time);
            $this->assign('end_time', $end_time);
        } else {
            if ($start_time) {
                $map[C('DB_PREFIX') . "merchants.add_time"] = array('gt', strtotime($start_time));
                $this->assign('start_time', $start_time);
            }

            if ($end_time) {
                $map[C('DB_PREFIX') . "merchants.add_time"] = array('lt', strtotime($end_time));
                $this->assign('end_time', $end_time);
            }
        }

//       $p = !empty($_GET["p"]) ? $_GET['p'] : 1;
//       $data=$model->field(C('DB_PREFIX')."merchants.*,".C('DB_PREFIX')."merchants_users.user_phone")
//                   ->join(' left JOIN  '.C('DB_PREFIX').'merchants_users ON '.C('DB_PREFIX').'merchants_users.id = '.C('DB_PREFIX').'merchants.uid')
//                   ->page($p ,  C('ADMIN_PAGE_ROWS'))
//                   ->where($map)
//                   ->order('id desc')
//                   ->select();
//       foreach ($data as &$val){
//           $val['id_number']=decrypt($val['id_number']);
//           $val['bank_account_no']=decrypt($val['bank_account_no']);
//       }
//       $page = new Page(
//           $model->field(C('DB_PREFIX')."merchants.*,".C('DB_PREFIX')."merchants_users.user_phone")
//                 ->join(' left JOIN  '.C('DB_PREFIX').'merchants_users ON '.C('DB_PREFIX').'merchants_users.id = ypt_merchants.uid')
//                 ->where($map)
//                 ->count(),
//                C('ADMIN_PAGE_ROWS')
//       );
//       $this->assign('merchants',$data);
//       $this->assign('page',$page->show());
//       $this->display();
        $map[C('DB_PREFIX') . "merchants.status"] = 1;
        /*$model->field(C('DB_PREFIX') . "merchants.*," . C('DB_PREFIX') . "merchants_users.user_phone")
            ->join(' left JOIN  ' . C('DB_PREFIX') . 'merchants_users ON ' . C('DB_PREFIX') . 'merchants_users.id = ypt_merchants.uid')
             ->join('__MERCHANTS_AGENT__ b on __MERCHANTS_USERS__.agent_id = b.uid','LEFT')
            ->where($map);
        $count = $model->count();
        $page = $this->page($count, 20);

        $model->limit($page->firstRow, $page->listRows)->order("id asc");*/


        $data = $model->field(C('DB_PREFIX') . "merchants.*," . C('DB_PREFIX') . "merchants_users.user_phone,b.agent_name")
        	
            ->join(' left JOIN  ' . C('DB_PREFIX') . 'merchants_users ON ' . C('DB_PREFIX') . 'merchants_users.id = ' . C('DB_PREFIX') . 'merchants.uid')
            ->join('__MERCHANTS_AGENT__ b on __MERCHANTS_USERS__.agent_id = b.uid','LEFT')
            ->where($map)
            ->order('id desc')
            ->select();
        //dump(M()->_sql());
        $count=count($data);
        $page = $this->page($count, 20);
        $this->assign("page", $page->show('Admin'));
        $data=array_slice($data,$page->firstRow,$page->listRows);
        foreach ($data as $k=>&$val) {
            unset($wx_cost);unset($ali_cost);
            $val['id_number'] = decrypt($val['id_number']);
            $val['bank_account_no'] = decrypt($val['bank_account_no']);
            $val['m_name'] = $this->find_name($val['mid']);
            $Merchants_cate = M('Merchants_cate')->where(array('merchant_id' => $val['id'], "checker_id" => "0",'status'=>'1'))->field(array('wx_bank', 'ali_bank', 'no_number', 'barcode_img','is_ypt'))->find();
            $val['wx_bank'] = $Merchants_cate['wx_bank'];
            $val['ali_bank'] = $Merchants_cate['ali_bank'];
            $val['no_number'] = $Merchants_cate['no_number'];
            if ($val['wx_bank'] == 1) {
                $val['wxx_name'] = '微众';
                $val['wxx_number'] = M("merchants_upwz")->where(array('mid' => $val['id']))->getField("WxCostRate");
            } elseif ($val['wx_bank'] == 2) {
                $val['wxx_number'] = "0." . M("merchants_mpay")->where(array('uid' => $val['id']))->getField("weicodefen");
                $val['wxx_name'] = '深圳民生';
            } elseif ($val['wx_bank'] == 3) {
                $val['wxx_number'] =  M("merchants_upwx")->where(array('mid' => $val['id']))->getField("cost_rate");
                $val['wxx_name'] = '微信';
            } elseif ($val['wx_bank'] == 4) {
                $val['wxx_name'] = '招商';
                $wx_cost = M("merchants_zspay")->where(array('merchant_id' => $val['id']))->getField("payment_type1");
                $val['wxx_number'] = $wx_cost/100;
            }elseif ($val['wx_bank'] == 5) {
                $val['wxx_name'] = '好近';
//                $wx_cost = M("merchants_upqf")->where(array('mid' => $val['id']))->getField("id");
                $val['wxx_number'] = 0;
            } elseif ($val['wx_bank'] == 6) {
                $val['wxx_name'] = '济南民生';
                $wx_cost = M("merchants_mdaypay")->where(array('merchant_id' => $val['id']))->getField("wechat_cost_rate");
                $val['wxx_number'] = $wx_cost;
            }elseif ($val['wx_bank'] == 7) {
                $val['wxx_name'] = '兴业银行';
                $wx_cost = M("merchants_xypay")->where(array('merchant_id' => $val['id']))->getField("wx_code");
                $val['wxx_number'] = $wx_cost;
            }elseif ($val['wx_bank'] == 9) {
                $val['wxx_name'] = '宿州李灿';
                $wx_cost = M("merchants_szlzwx")->where(array('mid' => $val['id']))->getField("rate");
                $val['wxx_number'] = $wx_cost;
            }elseif ($val['wx_bank'] == 10) {
                $val['wxx_name'] = '东莞中信';
                $wx_cost = M("merchants_pfpay")->where(array('merchant_id' => $val['id']))->getField("wx_code");
                $val['wxx_number'] = $wx_cost;
            } elseif ($val['wx_bank'] == 11) {
                $val['wxx_name'] = '新大陆';
                $wx_cost = M("merchants_xdl")->where(array('m_id' => $val['id']))->getField("wx_rate");
                $val['wxx_number'] = $wx_cost;
            } elseif ($val['wx_bank'] == 12) {
                $val['wxx_name'] = '乐刷';
                $wx_cost = M("merchants_leshua")->where(array('m_id' => $val['id']))->getField("wx_t0_rate");
                $val['wxx_number'] = $wx_cost;
            }elseif ($val['wx_bank'] == 13) {
                $val['wxx_name'] = '平安付';
                $wx_cost = M("merchants_pingan")->where(array('mid' => $val['id']))->getField("cost_rate");
                $val['wxx_number'] = $wx_cost;
            } else {
                $val['wxx_name'] = '';
                $val['wxx_number'] = 0;
            }
            if ($val['ali_bank'] == 1) {
                $val['ali_name'] = '微众';
                $val['ali_number'] = M("merchants_upwz")->where(array('mid' => $val['id']))->getField("aliCostRate");
            } elseif ($val['ali_bank'] == 2) {
                $val['ali_number'] = "0." . M("merchants_mpay")->where(array('uid' => $val['id']))->getField("alipaycodefen");
                $val['ali_name'] = '深圳民生';
            } elseif ($val['ali_bank'] == 3) {
                $val['ali_number'] = 0;
                $val['ali_name'] = '支付宝';
            } elseif ($val['ali_bank'] == 4) {
                $ali_cost = M("merchants_zspay")->where(array('merchant_id' => $val['id']))->getField("payment_type7");
                $val['ali_number'] = $ali_cost/100;
                $val['ali_name'] = '招商';
            } elseif ($val['ali_bank'] == 6) {
                $ali_cost = M("merchants_mdaypay")->where(array('merchant_id' => $val['id']))->getField("alipay_cost_rate");
                $val['ali_number'] = $ali_cost;
                $val['ali_name'] = '济南民生';
            } elseif ($val['ali_bank'] == 7) {
                $ali_cost = M("merchants_xypay")->where(array('merchant_id' => $val['id']))->getField("ali_code");
                $val['ali_number'] = $ali_cost;
                $val['ali_name'] = '兴业银行';
            }elseif ($val['ali_bank'] == 9) {
                $val['ali_name'] = '宿州李灿';
                $wx_cost = M("merchants_szlzwx")->where(array('mid' => $val['id']))->getField("rate");
                $val['ali_number'] = $wx_cost;
            } elseif ($val['ali_bank'] == 10) {
                $ali_cost = M("merchants_pfpay")->where(array('merchant_id' => $val['id']))->getField("ali_code");
                $val['ali_number'] = $ali_cost;
                $val['ali_name'] = '东莞中信';
            } elseif ($val['ali_bank'] == 11) {
                $ali_cost = M("merchants_xdl")->where(array('m_id' => $val['id']))->getField("ali_rate");
                $val['ali_number'] = $ali_cost;
                $val['ali_name'] = '新大陆';
            } elseif ($val['ali_bank'] == 12) {
                $ali_cost = M("merchants_leshua")->where(array('m_id' => $val['id']))->getField("ali_t0_rate");
                $val['ali_number'] = $ali_cost;
                $val['ali_name'] = '乐刷';
            }elseif ($val['ali_bank'] == 13) {
                $ali_cost = M("merchants_pingan")->where(array('mid' => $val['id']))->getField("cost_rate");
                $val['ali_number'] = $ali_cost;
                $val['ali_name'] = '平安付';
            }  else {
                $val['ali_name'] = '';
                $val['ali_number'] = 0;
            }
            $val['barcode_img'] = $Merchants_cate['barcode_img'];
            $val['is_ypt'] = $Merchants_cate['is_ypt'];
            $val['checker'] = $this->_get_checkers($val['uid']);
        };
        //dump($data);
        $this->assign("merchants", $data);
        $this->display();
    }

    public function cate_bank()
    {
        $id = I('id');
        $wx = M('merchants_upwz')->where(array('mid' => $id))->find();
        $m = M('merchants_mpay')->where(array('uid' => $id))->find();
        if ($wx && $m) {
            $wxstr = "<select name='wx_bank' id='wx_bank'><option >--请选择--</option><option value='1'>微众银行|" . $wx['wxCostRate'] . "</option><option value='2'>民生银行|0." . $m['weicodefen'] . "</option><option value='3'>微信银行|0.00</option></select>";
            $mstr = "<select name='ali_bank' id='ali_bank'><option >--请选择--</option><option value='1'>微众银行|" . $wx['aliCostRate'] . "</option><option value='2'>民生银行|0." . $m['alipaycodefen'] . "</option><option value='3'>微信银行|0.00</option></select>";
        } else if ($wx) {
            $wxstr = "<select name='wx_bank' id='wx_bank'><option >--请选择--</option><option value='1'>微众银行|" . $wx['wxCostRate'] . "</option><option value='3'>微信银行|0.00</option></select>";
            $mstr = "<select name='ali_bank' id='ali_bank'><option >--请选择--</option><option value='1'>微众银行|" . $wx['aliCostRate'] . "</option><option value='3'>微信银行|0.00</option></select>";
        } elseif ($m) {
            $wxstr = "<select name='wx_bank' id='wx_bank'><option >--请选择--</option><option value='2'>民生银行|0." . $m['weicodefen'] . "</option><option value='3'>微信银行|0.00</option></select>";
            $mstr = "<select name='ali_bank' id='ali_bank'><option >--请选择--</option><option value='2'>民生银行|0." . $m['alipaycodefen'] . "</option><option value='3'>微信银行|0.00</option></select>";
        } else {
            $wxstr = "<select name='wx_bank' id='wx_bank'><option >--请选择--</option><option value='3'>微信银行|0.00</option></select>";
            $mstr = "<select name='ali_bank' id='ali_bank'><option >--请选择--</option><option value='3'>微信银行|0.00</option></select>";
        }
        $data['wxstr'] = $wxstr;
        $data['mstr'] = $mstr;
        $this->ajaxReturn($data);
    }

    public function update_cate()
    {
        $id = I('id');
        $data['wx_bank'] = I('wx_bank');
        $data['ali_bank'] = I('ali_bank');
        $re = M('Merchants_cate')->where(array('merchant_id' => $id))->find();
        if ($re) {
            if ($data['wx_bank'] == '--请选择--' || $data['ali_bank'] == '--请选择--') {
                $this->ajaxReturn(1);
            } else {
                M('Merchants_cate')->where(array('merchant_id' => $id))->save($data);
                $this->ajaxReturn(2);
            }
        } else {
            $this->ajaxReturn(3);
        }
    }

    public function add()
    {
        if (IS_POST) {

            $uid = I("uid");
            if (empty($uid)) {
                $this->ajaxReturn(array("code" => '2', 'msg' => '需要先添加用户'));
            }

            $merchant_name = I("merchant_name");
            if (empty($merchant_name)) {
                $this->ajaxReturn(array("code" => '3', 'msg' => '商户名称不能为空'));
            }

            $merchant_jiancheng = I("merchant_jiancheng");
            if (empty($merchant_jiancheng)) {
                $this->ajaxReturn(array("code" => '3', 'msg' => '商户简称不能为空'));
            }

            if(strlen($merchant_jiancheng) > 36){
                $this->ajaxReturn(array("code" => '3', 'msg' => '商户简称太长(不超过12个中文)'));
            }
            $province = I("province");
            if ($province == "--请选择省份--") {
                $this->ajaxReturn(array("code" => '4', 'msg' => '请选择省份'));
            }

            $city = I("city");
            if ($city == "--请选择城市--") {
                $this->ajaxReturn(array("code" => '5', 'msg' => '请选择城市'));
            }

            $county = I("county");
            if ($county == "--请选择地区--") {
                $this->ajaxReturn(array("code" => '6', 'msg' => '请选择地区'));
            }

            $address = I("address");
            if (empty($address)) {
                $this->ajaxReturn(array("code" => '7', 'msg' => '请填写详细地址'));
            }

            $industry = I("industry");
            if (empty($industry) || $industry == "-1") {
                $this->ajaxReturn(array("code" => '7', 'msg' => '请选择所属行业'));
            }

            $header_interior_img = I("header_interior_img");
            if (empty($header_interior_img)) {
                $this->ajaxReturn(array("code" => '8', 'msg' => '请上传门头图片'));
            }

            $business_license = I("business_license");
            if (empty($business_license)) {
                $this->ajaxReturn(array("code" => '9', 'msg' => '请上传营业执照图片'));
            }

            $operator_name = I("operator_name");
            if (empty($operator_name)) {
                $this->ajaxReturn(array("code" => '10', 'msg' => '请填写经营者姓名'));
            }

            $positive_id_card_img = I("positive_id_card_img");
            if (empty($positive_id_card_img)) {
                $this->ajaxReturn(array("code" => '11', 'msg' => '请上传正面身份证照片'));
            }

            $id_card_img = I("id_card_img");
            if (empty($id_card_img)) {
                $this->ajaxReturn(array("code" => '12', 'msg' => '请上传反面身份证照片'));
            }

            $account_type = I("account_type");
            if ($account_type == "-1") {
                $this->ajaxReturn(array("code" => '13', 'msg' => '请填写账户类型'));
            }

            $account_name = I("account_name");
            if (empty($account_name)) {
                $this->ajaxReturn(array("code" => '14', 'msg' => '请填写账户名称/开户名称'));
            }

            $bank_account = I("bank_account");
            if (empty($bank_account)) {
                $this->ajaxReturn(array("code" => '15', 'msg' => '请填写开户银行'));
            }

            $branch_account = I("branch_account");
            if (empty($branch_account)) {
                $this->ajaxReturn(array("code" => '16', 'msg' => '请填写开户支行'));
            }

            $bank_account_no = I("bank_account_no");
            if (empty($bank_account_no)) {
                $this->ajaxReturn(array("code" => '17', 'msg' => '请填写银行账号'));
            }

            $id_number = I("id_number");
            if (empty($id_number)) {
                $this->ajaxReturn(array("code" => '17', 'msg' => '请填写身份证号'));
            }

            /*if(empty(I('interior_img_one'))){
                $this->ajaxReturn(array("code" => '24', 'msg' => '请上传门头照'));
            }
            if(empty(I('interior_img_three'))){
                $this->ajaxReturn(array("code" => '24', 'msg' => '请上传收银台照片'));
            }*/
            $interior_img = I('interior_img_one').','.I('interior_img_three');

            $business_license_number = I("business_license_number");
            if (empty($business_license_number)) {
                $this->ajaxReturn(array("code" => '21', 'msg' => '请填写营业执照编号'));
            }

            $bank_type = I("bank_type");
            if ($bank_type == '-1') {
                $this->ajaxReturn(array("code" => '22', 'msg' => '请选择通道类型'));
            }

            $bank_rate = I("bank_rate");
            if (empty($bank_rate)) {
                $this->ajaxReturn(array("code" => '23', 'msg' => '请填写费率'));
            }

            //$hand_positive_id_card_img = I("hand_positive_id_card_img");
            /*if (empty($hand_positive_id_card_img)) {
                $this->ajaxReturn(array("code" => '24', 'msg' => '请上传手持身份证正面照片'));
            }*/

            //$hand_id_card_img = I("hand_id_card_img");
            /*if (empty($hand_id_card_img)) {
                $this->ajaxReturn(array("code" => '25', 'msg' => '请上传手持身份证反面照片'));
            }*/

            $positive_bank_card_img = I("positive_bank_card_img");
            if (empty($positive_bank_card_img)) {
                $this->ajaxReturn(array("code" => '26', 'msg' => '请上传银行卡正面照片'));
            }

            $bank_card_img = I("bank_card_img");
            if (empty($bank_card_img)) {
                $this->ajaxReturn(array("code" => '23', 'msg' => '请上传银行卡反面照片'));
            }

            $uni_positive_id_card_img = I("uni_positive_id_card_img");
            $uni_id_card_img = I("uni_id_card_img");
            $uni_ls_auth = I("uni_ls_auth");
            $uni_xdl_auth = I("uni_xdl_auth");
            $xdl_auth = I("xdl_auth");
            $uni_id_number = I("uni_id_number");
            if($operator_name != $account_name){
                if (empty($uni_id_number)) {
                    $this->ajaxReturn(array("code" => '24', 'msg' => '请填写非法人身份证号'));
                }
                if (empty($uni_positive_id_card_img)) {
                    $this->ajaxReturn(array("code" => '24', 'msg' => '请上传非法人身份证正面照片'));
                }
                if (empty($uni_id_card_img)) {
                    $this->ajaxReturn(array("code" => '24', 'msg' => '请上传非法人身份证反面照片'));
                }
                if (empty($uni_ls_auth)) {
                    $this->ajaxReturn(array("code" => '24', 'msg' => '请上传乐刷非法人清算授权书'));
                }
                if (empty($uni_xdl_auth)) {
                    $this->ajaxReturn(array("code" => '24', 'msg' => '请上传新大陆非法人清算授权书'));
                }
                if (empty($xdl_auth)) {
                    $this->ajaxReturn(array("code" => '24', 'msg' => '法人与非法人清算新大陆授权书'));
                }
            }

            $referrer = I("referrer");
//           if(!empty($referrer)){
//               $res=$this->checkReferrer($referrer,$uid);
//               if($res['code']==0){
//                   $this->ajaxReturn(array("code"=>'17','msg'=>$res['msg']));
//               }
//           }
            if (empty($referrer)) {
                $referrer = 13128898154;
            }
            $model = M("merchants");
            $data = $model->create();
			  $addr = $province.$city.$county.$address;
            $getLonLat = $this->addresstolatlag($addr);
            if ($data) {
				$data['lon'] = $getLonLat["lng"]?:'0.000';
                $data['lat'] = $getLonLat["lat"]?:'0.000';
                $data['add_time'] = time();
                $data['status'] = '1';
                $data['referrer'] = $referrer;
                $data['id_number'] = encrypt($id_number);
                $data['bank_account_no'] = encrypt($bank_account_no);
                $data['business_license_number'] = encrypt($business_license_number);
                $data['uni_id_number'] = encrypt($uni_id_number);
                $data['bank_type'] = $bank_type;
                $data['bank_rate'] = $bank_rate;
                $data['interior_img'] = $interior_img;
                //$data['hand_positive_id_card_img'] = $hand_positive_id_card_img;
                //$data['hand_id_card_img'] = $hand_id_card_img;
                $data['positive_bank_card_img'] = $positive_bank_card_img;
                $data['bank_card_img'] = $bank_card_img;
                $data['uni_positive_id_card_img'] = $uni_positive_id_card_img;
                $data['uni_id_card_img'] = $uni_id_card_img;
                $data['uni_ls_auth'] = $uni_ls_auth;
                $data['uni_xdl_auth'] = $uni_xdl_auth;
                $data['xdl_auth'] = $xdl_auth;

                if ($model->add($data)) {
                    D('Merchants')->addDefaultRole($uid);
                    M('merchants_users')->where(array('id' => $uid))->save(array('user_name' => $merchant_jiancheng));
                    M('merchants_logs')->add(array('mid'=>$uid,'msg'=>'审核通过：将在1～3个工作日内开通支付！','add_time'=>time(),'type'=>3));
                    $this->ajaxReturn(array('code' => '1', 'msg' => '添加成功'));
                } else {
                    $this->ajaxReturn(array('code' => '0', 'msg' => '添加失败'));
                }
            }
        } else {

            $this->display();
        }
    }

    #待审核商户
    public function examine()
    {
        $user_phone = I('user_phone','','trim');
        if ($user_phone){
            $map['u.user_phone'] = $user_phone;
            $this->assign('user_phone',$user_phone);
        }

        $merchant_name = I('merchant_name','','trim');
        if ($merchant_name){
            $map['m.merchant_name'] = array('LIKE',"%$merchant_name%");
            $this->assign('merchant_name',$merchant_name);
        }

        $agent_name = I('agent_name','','trim');
        if ($agent_name){
            $where['agent_name'] = array('LIKE',"%$agent_name%");
            $this->assign('agent_name',$agent_name);
        }

        $status = I('status');
        if ($status != "-1" && $status != '') {
            if ($status >= 0) {
                $map['m.status'] = $status;
                $this->assign('status', $status);
            } else {
                $map['m.status'] = array(array('neq',1),array('neq',6));
                $this->assign('status', '-1');
            }
        } else {
            $map['m.status'] = array(array('neq',1),array('neq',6));
            $this->assign('status', '-1');
        }

        $referrer = I('referrer','','trim');
        if ($referrer) {
            $map['referrer'] = $referrer;
            $this->assign('referrer',$referrer);
        }

        $data = M('merchants')->alias('m')
            ->join('ypt_merchants_users u on u.id=m.uid')
            ->field('m.id,m.uid,m.status,u.user_phone,m.merchant_name,u.agent_id,m.update_time,m.add_time,referrer')
            ->where($map)
            ->order('m.add_time desc')
            ->select();
        foreach ($data as &$v) {
            $where['uid'] = $v['agent_id'];
            $v['agent_name'] = M('merchants_agent')->where($where)->getField('agent_name');
            //$v['referrer'] = M('merchants_users')->where(array('user_phone'=>$v['referrer']))->getField('is_employee') ? $v['referrer'] : '';
        }
        $count=count($data);
        $page = $this->page($count, 20);
        $list=array_slice($data,$page->firstRow,$page->listRows);
        $this->assign("page", $page->show('Admin'));
        $this->assign("data",$list);
        $this->display();
    }
	
	//获取经纬度
    private function addresstolatlag($address){
        $url = 'http://apis.map.qq.com/ws/geocoder/v1/?address='.$address.'&key=LANBZ-62HHF-TSWJM-N5JQT-XE4I3-ZUFIL';
        if($result=file_get_contents($url))
        {
            $res = json_decode($result,true);
            return $res["result"]["location"];
        }
    }


    public function add_user()
    {
//        找到当前用户的上级
        $user_phone = I("user_phone", "trim");
        $referrer = I("referrer", "trim");
        $merchant_name = I("merchant_name", "trim");

//        if($pid_phone == ""){$this->ajaxReturn(array("code"=>'0',"msg"=>"商户的上级不能为空"));}
//        if(!isMobile($pid_phone)){$this->ajaxReturn(array("code"=>'0',"msg"=>"发展人的手机号码输入不正确"));}
        if (!isMobile($user_phone)) {
            $this->ajaxReturn(array("code" => '0', "msg" => "用户的手机号码输入不正确"));
        }
//        if(M("merchants_users")->where("user_phone=$user_phone")->find()){$this->ajaxReturn(array("code"=>'0',"msg"=>"该用户的手机号码已存在"));}
        if (empty($referrer)) {
//                手机号码的检测
            $user['agent_id'] = 1;
            $user['pid'] = 0;
        } else {
            if (!isMobile($referrer)) $this->ajaxReturn(array("code" => "error", "msg" => '用户的上级手机号码输入错误'));
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

//        $user=M("merchants_users")->alias("m")
//            ->join("right join __MERCHANTS_ROLE_USERS__ ur on ur.uid=m.id")
//            ->field("m.*,ur.role_id")
//            ->where("user_phone = $pid_phone")
//            ->find();
//        if($user['role_id'] == 4||$user['role_id'] == 5){
//            $agent_id=0;
//            $pid=$user['id'];
//        }elseif ($user['role_id'] == 2){
//            $agent_id=$user['id'];
//            $pid=$user['id'];
//        }else{
//            $this->ajaxReturn(array("code"=>'0',"msg"=>"商户的上级手机号码必须是代理商,或者代理商的员工"));
//        }

        if ($user_phone) {
            $section = $this->checkUser($user_phone);
            if ($section['code'] === 0) {
                $this->ajaxReturn($section);
            } elseif ($section['code'] == 1) {
                $this->ajaxReturn($section);
            } else {
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

//   机器人添加
    public function add_merchant_machine()
    {
        if (IS_POST) {
            $mid = I("mid");
            $merchant_one = $this->merchants->where("id = $mid")->find();
            $user_one = $this->users->where("id=" . $merchant_one['uid'])->find();
            $number = $this->merchants->where("mid = $mid")->count("id");
            $number = $number + 1;
            $phone_begin = substr($user_one['user_phone'], 0, 7);
            $phone_end = substr("00000" . $number, -4);
//            机器用户添加
            unset($user_one['id']);
            $user_one['user_name'] = $user_one['user_name'] . $number;
            $user_one['user_phone'] = $phone_begin . $phone_end;
            $user_one['user_pwd'] = md5(123456);
            $user_one['auth'] = "";
            $user_one['add_time'] = time();
            $machine_user = $this->users->add($user_one);
//            机器商户添加
            unset($merchant_one['id']);
            $merchant_one['mid'] = $mid;
            $merchant_one['uid'] = $machine_user;
//            添加的
            $merchant_one['province'] = I("province");
            $merchant_one['city'] = I("city");
            $merchant_one['county'] = I("county");
            $merchant_one['address'] = I("address");
            $merchant_one['industry'] = I("industry");
            $merchant_one['account_type'] = I("account_type");
            $merchant_one['is_miniapp'] = I("is_miniapp");
//            $merchant_one['merchant_name']=$merchant_one['merchant_name'].$number;
            $merchant_one['merchant_name'] = I("merchant_name");
            $merchant_one['merchant_jiancheng'] = $user_one['user_name'];
            $merchant_one['add_time'] = time();
            $machine_merchant = $this->merchants->add($merchant_one);
//            机器人角色对应表添加
            $role['uid'] = $machine_user;
            $role['role_id'] = 3;
            $role['add_time'] = time();
            $this->user_role->add($role);
            $user_data = array(
                'user_login'=>$user_one['user_phone'],
                'user_pass'=>sp_password('123456'),
                'user_nicename'=>$user_one['user_phone'],
                'create_time'=>date('Y-m-d H:i:s'),
                'mobile'=>$user_one['user_phone'],
                'platform'=>1,
                'muid'=>$machine_user,
                'pid'=>$mid,
            );
            $id = M('users')->add($user_data);
            $ro['role_id'] = 4;
            $ro['user_id'] = $id;
            M('role_user')->add($ro);
//            机器人进件表新增
            $upwz = $this->upwzs->where("mid=$mid")->find();
            if ($upwz) {
                unset($upwz['id']);
                $upwz['mid'] = "$machine_merchant";
                $upwz['cate_id'] = "";
                $upwz['time_start'] = time();
                $this->upwzs->add($upwz);
            }
            $this->success("添加机器人商户成功", U("index"));
        } else {
            $this->display("add_merchant");
        }
    }

    public function edit()
    {
        if (IS_GET) {
            $id = I("id");
            $data = M("merchants")->where(array('id' => $id))->find();
            $data['id_number'] = decrypt($data['id_number']);
            $data['bank_account_no'] = decrypt($data['bank_account_no']);
            $data['business_license_number'] = decrypt($data['business_license_number']);
            $data['uni_id_number'] = decrypt($data['uni_id_number']);
            if($data['interior_img']){
                $img_arr = explode(',',$data['interior_img']);
                $data['interior_img_one']=$img_arr[0];
                count($img_arr)==2?$data['interior_img_three']=$img_arr[1]:$data['interior_img_three']=$img_arr[2];
            }
            $this->assign("data", $data);
            $this->display();
        }
        if (IS_POST) {
            $uid = I("uid");
            if (empty($uid)) {
                $this->ajaxReturn(array("code" => '2', 'msg' => '用户ID不能为空'));
            }

            $merchant_name = I("merchant_name");
            if (empty($merchant_name)) {
                $this->ajaxReturn(array("code" => '3', 'msg' => '商户名称不能为空'));
            }

            $merchant_jiancheng = I("merchant_jiancheng");
            if (empty($merchant_name)) {
                $this->ajaxReturn(array("code" => '3', 'msg' => '商户简称不能为空'));
            }

            if(strlen($merchant_jiancheng) > 36){
                $this->ajaxReturn(array("code" => '3', 'msg' => '商户简称太长(不超过12个中文)'));
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

            $industry = I("industry");
            if (empty($industry) || $industry == "-1") {
                $this->ajaxReturn(array("code" => '7', 'msg' => '请选择所属行业'));
            }

            $operator_name = I("operator_name");
            if (empty($operator_name)) {
                $this->ajaxReturn(array("code" => '10', 'msg' => '请填写经营者姓名'));
            }


            $account_type = I("account_type");
            if ($account_type == "-1") {
                $this->ajaxReturn(array("code" => '13', 'msg' => '请填写账户类型'));
            }

            $account_name = I("account_name");
            if (empty($account_name)) {
                $this->ajaxReturn(array("code" => '14', 'msg' => '请填写账户名称/开户名称'));
            }

            $bank_account = I("bank_account");
            if (empty($bank_account)) {
                $this->ajaxReturn(array("code" => '15', 'msg' => '请填写开户银行'));
            }

            $branch_account = I("branch_account");
            if (empty($branch_account)) {
                $this->ajaxReturn(array("code" => '16', 'msg' => '请填写开户支行'));
            }

            $bank_account_no = I("bank_account_no");
            if (empty($bank_account_no)) {
                $this->ajaxReturn(array("code" => '17', 'msg' => '请填写银行账号'));
            }
            $interior_img = '';
            if(I('interior_img_one') && I('interior_img_three')){
                $interior_img = I('interior_img_one').','.I('interior_img_three');
            }elseif (I('interior_img_one')) {
                $interior_img = I('interior_img_one');
            }elseif (I('interior_img_three')) {
                $interior_img = I('interior_img_three');
            }

            $user_phone = I("referrer","","trim");
            if (empty($user_phone)) {
//                手机号码的检测
                $user['agent_id'] = 1;
                $user['pid'] = 0;
                $referrer = 13128898154;
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
                $referrer = $user_phone;
            }
            $bank_account_no = encrypt(I('bank_account_no'));
            $id_number = encrypt(I('id_number'));
            $business_license_number = encrypt(I('business_license_number'));
            $uni_id_number = encrypt(I('uni_id_number'));
            $model = M("merchants");
            $data = $model->create();
            if ($data) {
                $model->referrer = $referrer;
                $model->id_number = $id_number;
                $model->bank_account_no = $bank_account_no;
                $model->business_license_number = $business_license_number;
                $model->uni_id_number = $uni_id_number;
                if($interior_img) $model->interior_img = $interior_img;
				  $addr = $province.$city.$county.$address;
                $getLonLat = $this->addresstolatlag($addr);
                $model->lon = $getLonLat['lng']?:'0.000';
                $model->lat = $getLonLat['lat']?:'0.000';
                $res = $model->save();

//                修改用户
                if (M("merchants_users")->where("id=$uid")->find()) M("merchants_users")->where("id=$uid")->save($user);
                if ($res !== false) {
                    $this->userMRole($uid);
                    $this->ajaxReturn(array("code" => '1', 'msg' => '修改成功'));
                } else {
                    $this->ajaxReturn(array("code" => '0', 'msg' => '修改失败'));
                }
            }
        }
    }

    public function examine_edit()
    {
        if (IS_GET) {
            $id = I("id");
            $data = M("merchants")->where(array('id' => $id))->find();
            $data['id_number'] = decrypt($data['id_number']);
            $data['bank_account_no'] = decrypt($data['bank_account_no']);
            $data['business_license_number'] = decrypt($data['business_license_number']);
            if($data['interior_img']){
                $img_arr = explode(',',$data['interior_img']);
                $data['interior_img_one']=$img_arr[0];
                count($img_arr)==2?$data['interior_img_three']=$img_arr[1]:$data['interior_img_three']=$img_arr[2];
            }
            $merchants_logs = M('merchants_logs')->where(array('mid'=>$data['uid']))->field('msg,add_time,type')->find();
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
                $data['new_log'] = $logs_time.' '.$type.$merchants_logs['msg'];
            }
            $this->assign("data", $data);
            $this->display();
        }
        if (IS_POST) {
            $uid = I("uid");
            if (empty($uid)) {
                $this->ajaxReturn(array("code" => '2', 'msg' => '用户ID不能为空'));
            }
            $status = I('status');
            #如果审核不通过
            if ($status==2){
                ($msg = I('msg')) || $this->ajaxReturn(array("code" => '0', 'msg' => '审核不通过原因不能为空'));
                M('merchants')->where(array('uid'=>$uid))->setField('status',2);
                M('merchants_logs')->add(array('mid'=>$uid,'msg'=>'审核不通过：'.$msg,'add_time'=>time(),'type'=>3));
                $this->ajaxReturn(array("code" => '1', 'msg' => '审核不通过提交成功'));
            }elseif($status==3){
                unset($_POST['status']);
            }

            $merchant_name = I("merchant_name");
            if (empty($merchant_name)) {
                $this->ajaxReturn(array("code" => '3', 'msg' => '商户名称不能为空'));
            }

            $merchant_jiancheng = I("merchant_jiancheng");
            if (empty($merchant_name)) {
                $this->ajaxReturn(array("code" => '3', 'msg' => '商户简称不能为空'));
            }

            if(strlen($merchant_jiancheng) > 36){
                $this->ajaxReturn(array("code" => '3', 'msg' => '商户简称太长(不超过12个中文)'));
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

            $industry = I("industry");
            if (empty($industry) || $industry == "-1") {
                $this->ajaxReturn(array("code" => '7', 'msg' => '请选择所属行业'));
            }

            $operator_name = I("operator_name");
            if (empty($operator_name)) {
                $this->ajaxReturn(array("code" => '10', 'msg' => '请填写经营者姓名'));
            }


            $account_type = I("account_type");
            if ($account_type == "-1") {
                $this->ajaxReturn(array("code" => '13', 'msg' => '请填写账户类型'));
            }

            $account_name = I("account_name");
            if (empty($account_name)) {
                $this->ajaxReturn(array("code" => '14', 'msg' => '请填写账户名称/开户名称'));
            }

            $bank_account = I("bank_account");
            if (empty($bank_account)) {
                $this->ajaxReturn(array("code" => '15', 'msg' => '请填写开户银行'));
            }

            $branch_account = I("branch_account");
            if (empty($branch_account)) {
                $this->ajaxReturn(array("code" => '16', 'msg' => '请填写开户支行'));
            }

            $bank_account_no = I("bank_account_no");
            if (empty($bank_account_no)) {
                $this->ajaxReturn(array("code" => '17', 'msg' => '请填写银行账号'));
            }
            if(I('interior_img_one')){
                $inter = M('merchants')->where(array('uid'))->getField('interior_img');
                if($inter){
                    $inter = explode(',',$inter);
                    $inter[0] = I('interior_img_one');
                    $interior_img = implode(',',$inter);
                }else{
                    $interior_img = I('interior_img_one');
                }
            }
            if(I('interior_img_three')){
                $inter = M('merchants')->where(array('uid'))->getField('interior_img');
                if($inter){
                    $inter = explode(',',$inter);
                    $inter[1] = I('interior_img_three');
                    $interior_img = implode(',',$inter);
                }else{
                    $interior_img = I('interior_img_three');
                }
            }
            $user_phone = I("referrer","","trim");
            if (empty($user_phone)) {
//                手机号码的检测
                $user['agent_id'] = 1;
                $user['pid'] = 0;
                $referrer = 13128898154;
            } else {
                if (!isMobile($user_phone)) $this->ajaxReturn(array("code" => "error", "msg" => '你的手机号码不存在'));
                $p_id = M("merchants_users")->where("user_phone=$user_phone")->getField("id");
                if (!$p_id) {
                    $this->ajaxReturn(array("code" => "error", "msg" => '你添加的上级手机号码不存在'));
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
                    $u_id = M("merchants_users")->where("id=$p_id")->getField("pid");
                    $user['agent_id'] = $u_id;
                    $user['pid'] = $p_id;
                }
//                商户的推荐人,作为备用
                $referrer = $user_phone;
            }
            $bank_account_no = encrypt(I('bank_account_no'));
            $id_number = encrypt(I('id_number'));
            $business_license_number = encrypt(I('business_license_number'));
            $model = M("merchants");

            $data = $model->create();
            if ($data) {
                $model->referrer = $referrer;
                $model->id_number = $id_number;
                $model->bank_account_no = $bank_account_no;
                $model->business_license_number = $business_license_number;
                $addr = $province.$city.$county.$address;
                if($interior_img){
                    $model->interior_img = $interior_img;
                }
                $getLonLat = $this->addresstolatlag($addr);
                $model->lon = $getLonLat['lng']?:'0.000';
                $model->lat = $getLonLat['lat']?:'0.000';
                $res = $model->save();

//                修改用户
                if (M("merchants_users")->where("id=$uid")->find()) M("merchants_users")->where("id=$uid")->save($user);
                if ($res !== false) {
                    $this->userMRole($uid);
                    if($status==1){
                        M('merchants_logs')->add(array('mid'=>$uid,'msg'=>'审核通过：将在1～3个工作日内开通支付！','add_time'=>time(),'type'=>3));
                    }
                    $this->ajaxReturn(array("code" => '1', 'msg' => '提交成功'));
                } else {
                    $this->ajaxReturn(array("code" => '0', 'msg' => '提交失败'));
                }
            }
        }
    }

    public function del()
    {
        $id = I("id",'','trim');
        $uid = M("merchants")->where(array('id' => $id))->getField('uid');
        M()->startTrans();
        M("merchants_users")->where(array('id' => $uid))->delete();
        $mer_info = M("merchants")->where(array('id' => $id))->find();
        if(M('users')->where(array('muid'=>$uid))->find()){
            M('users')->where(array('muid'=>$uid))->delete();
        }
        $this->delete_img($mer_info);
        $res = M("merchants")->where(array('id' => $id))->delete();
        if ($res) {
            M()->commit();
            header('location: '.$_SERVER['HTTP_REFERER']);
            $this->success('删除成功', U('examine'));
        } else {
            M()->rollback();
            $this->success('删除成功');
        }
    }

    public function delete_img($mer_info){
        $dele_arr = array();
        if($mer_info['header_interior_img']) $dele_arr[]=$mer_info['header_interior_img'];
        if($mer_info['interior_img']){
            $inter = explode(',',$mer_info['interior_img']);
            if($inter[0]) $dele_arr[]=$inter[0];
            if($inter[1]) $dele_arr[]=$inter[1];
        }
        if($mer_info['business_license']) $dele_arr[]=$mer_info['business_license'];
        if($mer_info['base_url']) $dele_arr[]=$mer_info['base_url'];
        if($mer_info['positive_id_card_img']) $dele_arr[]=$mer_info['positive_id_card_img'];
        if($mer_info['id_card_img']) $dele_arr[]=$mer_info['id_card_img'];
        if($mer_info['hand_positive_id_card_img']) $dele_arr[]=$mer_info['hand_positive_id_card_img'];
        if($mer_info['hand_id_card_img']) $dele_arr[]=$mer_info['hand_id_card_img'];
        if($mer_info['positive_bank_card_img']) $dele_arr[]=$mer_info['positive_bank_card_img'];
        if($mer_info['bank_card_img']) $dele_arr[]=$mer_info['bank_card_img'];
        file_put_contents('./data/log/merchants/'.date("Y_m_").'del_img.log', date("Y-m-d H:i:s") . 'merchants表信息:' . json_encode($mer_info) . PHP_EOL, FILE_APPEND | LOCK_EX);
        file_put_contents('./data/log/merchants/'.date("Y_m_").'del_img.log', date("Y-m-d H:i:s") . '删除的图片路径:' . $dele_arr . PHP_EOL. PHP_EOL, FILE_APPEND | LOCK_EX);
        if($dele_arr){
            foreach ($dele_arr as &$v) {
                if(strpos($v,'http')){
                    $file = ltrim($v,'http://sy.youngport.com.cn');
                }elseif(!strpos($v,'data')){
                    $file = './data/upload/'.$v;
                }else{
                    $file = $v;
                }
                if ($file) unlink($file);
            }
        }
    }


    public function exchangeSatatus()
    {

        if (IS_POST) {
            $status = I("status");
            $id = I("id");
            if (isset($status)) {
//                if($status == 1){
//                    $res = M('merchants_cate')->field('wx_bank,ali_bank')->where(array('merchant_id'=>$id))->find();
//                    if($res['ali_bank'] == 0 && $res['wx_bank'] == 0){
//                        $this->ajaxReturn(array('code' => '0', 'msg' => '未设置支付通道'));
//                    }
//                }
                $res = M("merchants")->where(array('id' => $id))->setField(array('status' => $status));
                if ($res !== false) {
                    if ($status==1){
                        $mid=M('merchants')->where(array('id'=>$id))->getField('uid');
                        M('merchants_logs')->add(array('mid'=>$mid,'msg'=>'审核通过：将在1~3个工作日内开通支付！','add_time'=>time(),'type'=>3));
                    }
                    $this->ajaxReturn(array('code' => '1', 'msg' => '修改成功'));
                } else {
                    $this->ajaxReturn(array('code' => '0', 'msg' => '修改失败'));
                }
            }
        }

    }

    public function merchants_logs()
    {
        $data = M('merchants_logs m')
            ->where(array('mid' => I('id')))
            ->field('msg,add_time,type')
            ->order('add_time desc')
            ->select();
        $this->assign('data', $data);
        $this->display();
    }


    public function detail()
    {
        $data = M('merchants m')
            ->field('m.*,u.user_phone')
            ->join('LEFT JOIN ypt_merchants_users u ON u.id=m.uid')
            ->where(array('m.id' => I('id')))
            ->find();
        //$data['header_interior_img'] = $this->check_img($data['header_interior_img']);
        //$data['business_license'] = $this->check_img($data['business_license']);
        //$data['positive_id_card_img'] = $this->check_img($data['positive_id_card_img']);
        //$data['id_card_img'] = $this->check_img($data['id_card_img']);
        $this->assign('data', $data);
        $this->display();
    }

    //检查照片是否404
    private function check_img($img_url)
    {
        $host = $_SERVER['HTTP_HOST'];
        if(!strpos($img_url,'/data/upload')){
            $img_url = '/data/upload/'.$img_url;
        }
        if ($this->chk_url($host.$img_url)==true) {
            return $img_url;
        } else {
            if($this->chk_url('http://agent.youngport.com.cn/'.$img_url)){
                return 'http://agent.youngport.com.cn/'.$img_url;
            }elseif($this->chk_url('http://pay.vipylsh.com/'.$img_url)){
                return 'http://pay.vipylsh.com/'.$img_url;
            }elseif($this->chk_url('http://hedui.youngport.com.cn/'.$img_url)){
                return 'http://hedui.youngport.com.cn/'.$img_url;
            }else{
                return '';
            }
        }
    }

    public function chk_url($url){
        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 10);//设置超时时间
        curl_exec($handle);
        //检查是否404（网页找不到）
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        if($httpCode == 404) {
            curl_close($handle);
            return false;
        }else{
            curl_close($handle);
            return true;
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

        $res = $model->field(array('user_phone'))->where(array("id" => $uid))->find();
        if ($res['user_phone'] == $referrer) {
            return array('code' => 0, 'msg' => '推荐人不能是自己');
        }

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
    private function checkUser1($phone)
    {
        $model = M("merchants_users");
        $data = $model->where(array("user_phone" => $phone))->count();
        if ($data > 0) {
            return true;
        } else {
            return false;
        }
    }

    private function checkUser($phone)
    {
        $model = M("merchants_users");
        $data = $model->where(array("user_phone" => $phone))->find();
        if ($data) {
            $role_id = M("merchants_role_users")->where("uid=" . $data['id'])->getField("role_id");
            $merchant = M("merchants")->where("uid=" . $data['id'])->find();
            if ($role_id == 3 && !$merchant) {
                return array("code" => 1, "msg" => "成功请继续填写商户信息", "uid" => $data['id']);
            } else {
                $this->ajaxReturn(array("code" => '0', "msg" => "该手机信息错误,请联系彭鼎"));
            }
        }
    }

    /***
     * @param $uid
     * @function userMRole
     * @intro 检查用户是否属于 商户
     */
    private function userMRole($uid)
    {
        $count = M("merchants_role_users")->where(array('uid' => $uid))->count();
        if ($count < 1) {
            $role_arr['uid'] = $uid;
            $role_arr['role_id'] = '3'; // 商户角色
            $role_arr['add_time'] = time();
            M("merchants_role_users")->add($role_arr);
        }
    }

// 查找用户mid对应的手机号码
    public function find_name($mid)
    {
        if ($mid == 0) {
            return "";
        }
        $user_name = M("merchants")->where("id=$mid")->getField("merchant_name");
//        $user_phone=M("merchants_users")->where("id=$uid")->getField("user_phone");
        return $user_name;
    }

    public function update_pwd()
    {
        $post = I("");
        $post['phone'] = trim($post['phone']);
        if ($this->users->where("user_phone=" . $post['phone'])->find()) $this->error("该手机号码已存在");
        if ($post['change_pwd'] == "1") {
            $data['user_pwd'] = md5(123456);
        }
        $data['user_phone'] = $post['phone'];
        $uid = $this->merchants->where("id=" . $post['id'])->getField("uid");
        if ($uid) {
            if ($this->users->where("id=" . $uid)->find()) {
                $this->users->where("id=" . $uid)->save($data);
                M('users')->where("muid={$uid}")->save(array('user_login'=> $post['phone'],'user_nicename'=> $post['phone'], 'mobile'=> $post['phone']));
                $this->success("修改成功", U("index"));
            } else {
                $this->error("发生错误,请与工作人员联系~");
            }
        } else {
            $this->error("发生错误,请与工作人员联系");
        }
    }

    /**
     * @param $mid 商户在商户表的id
     * @param $uid 商户在用户表的id
     */
    public function _get_checkers($uid)
    {
        $checkers = $this->users->alias('u')
            ->join("left join __MERCHANTS_CATE__ c on c.checker_id = u.id")
            ->where(array('pid' => $uid, 'u.status' => 0))
            ->field("u.id,u.user_name,u.user_phone,u.add_time,c.no_number,c.barcode_img")->select();
        return $checkers;
    }

// 台卡设置
    public function cate_edit()
    {
        $merchant_id = I("id");
        $checker_id = I("checker_id");
        $cate = $this->cates->where(array("merchant_id" => $merchant_id,"checker_id"=>0))->find();
        if ($checker_id) { //收银员
            if (!$cate) {
                $this->error("需先设置商户台签");
            }
            if ($this->cates->where(array("merchant_id" => $merchant_id, "checker_id" => $checker_id))->find()) {
                $this->error("改收银员已设置台签,无法编辑");
            }
            $user_phone = $this->users->where(array("id" => $checker_id))->getField("user_phone");
            $this->assign("checker_id",$checker_id);
            $this->assign("user_phone", $user_phone);
            $this->assign("cate", $cate);
            $this->display("cate_edit");
        } else {//商家
            if($cate) $this->assign("cate",$cate);
            else $this->assign("cate",array("merchant_id"=>$merchant_id));
            $this->display("cate_edit1");
        }
    }
//  收银员绑定台卡
    public function checker_edit_post()
    {
        $checker_id=I("checker_id");
        $cate_id =I("cate_id");
        if($this->cates->where(array("id"=>$cate_id,"status"=>1))->find()){
            $this->error("该台卡已使用,请选择其他台卡");
        }
        if(!$this->cates->where(array("id"=>$cate_id))->find()){
            $this->error("未找到该台签");
        }
        $pid=$this->users->where(array("id"=>$checker_id))->getField("pid");
        $mid=$this->merchants->where(array("uid"=>$pid))->getField("id");
        $cate_merchant=$this->cates->where(array("merchant_id"=>$mid,"checker_id"=>0))->find();
        $cate_checker=$this->cates->where(array('id'=>$cate_id))->find();
        $cate_checker['checker_id']=$checker_id;
        $cate_checker['merchant_id']=$cate_merchant['merchant_id'];
        $cate_checker['jianchen']=$cate_merchant['jianchen'];
        $cate_checker['name']=$cate_merchant['name'];
        $cate_checker['wx_name']=$cate_merchant['wx_name'];
        $cate_checker['alipay_partner']=$cate_merchant['alipay_partner'];
        $cate_checker['wx_mchid']=$cate_merchant['wx_mchid'];
        $cate_checker['wx_key']=$cate_merchant['wx_key'];
        $cate_checker['wx_bank']=$cate_merchant['wx_bank'];
        $cate_checker['merchant_id']=$cate_merchant['merchant_id'];
        $cate_checker['ali_bank']=$cate_merchant['ali_bank'];
        $cate_checker['ali_public_key']=$cate_merchant['ali_public_key'];
        $cate_checker['status']=1;
        $cate_checker['update_time']=time();
        $this->cates->where(array("id"=>$cate_id))->save($cate_checker);
        get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/cate/','bind_cate','收银员绑定台签', json_encode($cate_checker));
        $this->success("收银员台签设置成功",U("index"));
    }
//  商户绑定台卡
    public function checker_edit_post1()
    {

        $cate_id =I("cate_id");
        $merchant_id=I("merchant_id");
        $merchant_one=$this->cates->where(array("id"=>$cate_id))->getField("merchant_id");
        if($merchant_one&&$merchant_one!=$merchant_id){
            $this->error("该台签已被其他商户绑定");
        }
        if(!$merchant_id)$this->error("未填写上商户id");
        if(!$cate_id)$this->error("未填写台签id");
        if(!$this->cates->where(array("id"=>$cate_id))->find())$this->error("未找到该台签");
        $cate=$this->cates->where(array("merchant_id"=>$merchant_id,"id"=>$cate_id))->find();
        $data['name']=trim(I('name'));
        $data['jianchen']= trim(I('jianchen'));
        $data['wx_name']= trim(I('wx_name'));
        $data['alipay_partner']=trim(I('alipay_partner'));
        $data['wx_mchid']=trim(I('wx_mchid'));
        $data['wx_key']=trim(I('wx_key'));
        $data['merchant_id']=trim(I('merchant_id'));
        $data['wx_bank']=trim(I('wx_bank'));
        $data['ali_bank']=trim(I('ali_bank'));
        $data['alipay_public_key']=trim(I('ali_public_key'));
        $data['status']=1;
        $data['is_ypt']=I('is_ypt');
        $data['update_time']=time();
        if($data['wx_bank'] == 6 && $data['ali_bank'] == 6){
            $data['is_cash'] = 1;
            $data['cate_name'] = "D0秒到台签";
        }else{
            $data['is_cash'] = 0;
        }
        if($cate){//修改商户台签的情况
            $this->cates->where(array("id"=>$cate_id))->save($data);
            if($_POST['change_all']){
//                exit('请稍后');
                $save_data['alipay_partner'] = $data['alipay_partner'];
                $save_data['alipay_public_key'] = $data['alipay_public_key'];
                $save_data['wx_mchid'] = $data['wx_mchid'];
                $save_data['wx_key'] = $data['wx_key'];
                $save_data['wx_bank'] = $data['wx_bank'];
                $save_data['ali_bank'] = $data['ali_bank'];
                $save_data['update_time'] = time();
                $this->cates->where(array("merchant_id"=>$merchant_id))->save($save_data);
            }
            get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/cate/','bind_cate','商户修改台签', json_encode($data));
        }else{//新增商户台签的情况
            get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/cate/','bind_cate','商户绑定台签', json_encode($data));
            if($this->cates->where(array("id"=>I("cate_id")))->save($data)){
                $mid=trim(I('merchant_id'));
                $user_name=trim(I('jianchen'));
                $uid=M("merchants")->where(array('id'=>$mid))->getField("uid");
                if($uid)M("merchants_users")->where(array('id'=>$uid))->save(array("user_name"=>$user_name));
                M("merchants")->where("id=$mid")->save(array("merchant_jiancheng"=>$user_name));
            }
        }

        $this->success("商户台卡设置成功",U("index"));

    }


//    支付宝切换银行
    public function ali_change_bank()
    {
        $merchant_id=I("merchant_id");
        $ali_bank=I("ali_bank");
        $data=array();
        switch ($ali_bank){
            case 1: //微众
                $ali_mchid=$this->upwzs->where(array("mid"=>$merchant_id))->getField("ali_mchid");
                if(!$ali_mchid)$data=array('status'=>0,'message'=>"该商户进件未完成");
                else $data=array('status'=>1,'ali_mchid'=>$ali_mchid,'ali_public_key'=>'');
                break;
            case 2: //民生
                $ali_mchid=M("merchants_mpay")->where(array("uid"=>$merchant_id,"into_type"=>3))->getField("alipay");
                if(!$ali_mchid)$data=array('status'=>0,'message'=>"该商户进件未完成");
                else $data=array('status'=>1,'ali_mchid'=>$ali_mchid,'ali_public_key'=>'');
                break;
            case 3: //支付宝官方
                $intoData=M("merchants_ali")->where(array("mid"=>$merchant_id))->find();
                $ali_mchid = $intoData['ali_mchid'];
                $ali_public_key = $intoData['ali_token'];
                if(!$intoData)$data=array('status'=>0,'message'=>"该商户进件未完成");
                else $data=array('status'=>1,'ali_mchid'=>$ali_mchid,'ali_public_key'=>$ali_public_key);
                break;
            case 4: //招商
                $ali_mchid=M("merchants_zspay")->where(array("merchant_id"=>$merchant_id,"into_type"=>3))->getField("ul_mchid");
                $ali_public_key=M("merchants_zspay")->where(array("merchant_id"=>$merchant_id,"into_type"=>3))->getField("mch_pay_key");
                if(!$ali_mchid)$data=array('status'=>0,'message'=>"该商户进件未完成");
                else $data=array('status'=>1,'ali_mchid'=>$ali_mchid,'ali_public_key'=>$ali_public_key);
                break;
            case 6: //招商
                $ali_mchid=M("merchants_mdaypay")->where(array("merchant_id"=>$merchant_id,"into_type"=>3))->getField("customerId");
                $ali_public_key=M("merchants_mdaypay")->where(array("merchant_id"=>$merchant_id,"into_type"=>3))->getField("customerId");
                if(!$ali_mchid)$data=array('status'=>0,'message'=>"该商户进件未完成");
                else $data=array('status'=>1,'ali_mchid'=>$ali_mchid,'ali_public_key'=>$ali_public_key);
                break;
            case 7: //兴业
                $ali_mchid=M("merchants_xypay")->where(array("merchant_id"=>$merchant_id))->getField("mch_id");
                $ali_public_key=M("merchants_xypay")->where(array("merchant_id"=>$merchant_id))->getField("mch_key");
                if(!$ali_mchid)$data=array('status'=>0,'message'=>"该商户进件未完成");
                else $data=array('status'=>1,'ali_mchid'=>$ali_mchid,'ali_public_key'=>$ali_public_key);
                break;
            case 9: //宿州李灿
                $ali_mchid=M("merchants_szlzwx")->where(array("mid"=>$merchant_id))->getField("ali_mchid");
                $ali_public_key=M("merchants_szlzwx")->where(array("mid"=>$merchant_id))->getField("ali_token");
                if(!$ali_mchid)$data=array('status'=>0,'message'=>"该商户进件未完成");
                else $data=array('status'=>1,'ali_mchid'=>$ali_mchid,'ali_public_key'=>$ali_public_key);
                break;
            case 10: //东莞中信
                $ali_mchid=M("merchants_pfpay")->where(array("merchant_id"=>$merchant_id))->getField("mch_id");
                $ali_public_key=M("merchants_pfpay")->where(array("merchant_id"=>$merchant_id))->getField("mch_key");
                if(!$ali_mchid)$data=array('status'=>0,'message'=>"该商户进件未完成");
                else $data=array('status'=>1,'ali_mchid'=>$ali_mchid,'ali_public_key'=>$ali_public_key);
                break;
            case 11: //新大陆
                $intoData=M("merchants_xdl")->where(array("m_id"=>$merchant_id))->find();
                $ali_mchid = $intoData['mercId'];
                $ali_public_key = $intoData['signKey'];
                if(!$ali_mchid)$data=array('status'=>0,'message'=>"该商户进件未完成");
                else $data=array('status'=>1,'ali_mchid'=>$ali_mchid,'ali_public_key'=>$ali_public_key);
                break;
            case 12: //乐刷
                $intoData=M("merchants_leshua")->where(array("m_id"=>$merchant_id))->find();
                $ali_mchid = $intoData['merchantId'];
                $ali_public_key = $intoData['key'];
                if(!$ali_mchid)$data=array('status'=>0,'message'=>"该商户进件未完成");
                else $data=array('status'=>1,'ali_mchid'=>$ali_mchid,'ali_public_key'=>$ali_public_key);
                break;
            case 13: //平安付
                $intoData=M("merchants_pingan")->where(array("mid"=>$merchant_id))->find();
                $ali_mchid = $intoData['sub_mchid'];
                $ali_public_key = $intoData['sub_mchkey'];
                if(!$ali_mchid)$data=array('status'=>0,'message'=>"该商户进件未完成");
                else $data=array('status'=>1,'ali_mchid'=>$ali_mchid,'ali_public_key'=>$ali_public_key);
                break;
            default:
                $data=array('status'=>0,'message'=>"未知错误");
                break;
        }
        $this->ajaxReturn($data);
    }
//    微信切换银行
    public function wx_change_bank()
    {
        $merchant_id=I("merchant_id");
        $ali_bank=I("ali_bank");
        $data=array();
        switch ($ali_bank){
            case 1: //微众
                $wx_mchid=$this->upwzs->where(array("mid"=>$merchant_id))->getField("wx_mchid");
                if(!$wx_mchid)$data=array('status'=>0,'message'=>"该商户还未进件");
                else $data=array('status'=>1,'wx_mchid'=>$wx_mchid,'wx_key'=>"youngPort4a21");
                break;
            case 2: //民生
                $wx_mchid=M("merchants_mpay")->where(array("uid"=>$merchant_id,"into_type"=>3))->getField("wechat");
                if(!$wx_mchid)$data=array('status'=>0,'message'=>"该商户还未进件");
                else $data=array('status'=>1,'wx_mchid'=>$wx_mchid,'wx_key'=>'');
                break;
            case 3: // 微信官方
                $wx_mchid=M("merchants_upwx")->where(array("mid"=>$merchant_id))->getField("sub_mchid");
                if(!$wx_mchid)$data=array('status'=>0,'message'=>"该商户还未进件");
                else $data=array('status'=>1,'wx_mchid'=>$wx_mchid,'wx_key'=>'');
                break;
            case 4: //招商银行
                $wx_mchid=M("merchants_zspay")->where(array("merchant_id"=>$merchant_id,"into_type"=>3))->getField("ul_mchid");
                $wx_key=M("merchants_zspay")->where(array("merchant_id"=>$merchant_id,"into_type"=>3))->getField("mch_pay_key");
                if(!$wx_mchid)$data=array('status'=>0,'message'=>"该商户还未进件");
                else $data=array('status'=>1,'wx_mchid'=>$wx_mchid,'wx_key'=>$wx_key);
                break;
            case 5: //钱方
                $wx_mchid=M("merchants_upqf")->where(array("mid"=>$merchant_id))->getField("qf_mchid");
//                $wx_key=M("merchants_upqf")->where(array("merchant_id"=>$merchant_id,"into_type"=>3))->getField("mch_pay_key");
                if(!$wx_mchid)$data=array('status'=>0,'message'=>"该商户还未进件");
                else $data=array('status'=>1,'wx_mchid'=>$wx_mchid,'wx_key'=>'');
                break;
            case 6: //招商银行
                $wx_mchid=M("merchants_mdaypay")->where(array("merchant_id"=>$merchant_id,"into_type"=>3))->getField("customerId");
                $wx_key=M("merchants_mdaypay")->where(array("merchant_id"=>$merchant_id,"into_type"=>3))->getField("customerId");
                if(!$wx_mchid)$data=array('status'=>0,'message'=>"该商户还未进件");
                else $data=array('status'=>1,'wx_mchid'=>$wx_mchid,'wx_key'=>$wx_key);
                break;
            case 7: //兴业银行
                $wx_mchid=M("merchants_xypay")->where(array("merchant_id"=>$merchant_id))->getField("mch_id");
                $wx_key=M("merchants_xypay")->where(array("merchant_id"=>$merchant_id))->getField("mch_key");
                if(!$wx_mchid)$data=array('status'=>0,'message'=>"该商户还未进件");
                else $data=array('status'=>1,'wx_mchid'=>$wx_mchid,'wx_key'=>$wx_key);
                break;
            case 9: //宿州李灿
                $wx_mchid=M("merchants_szlzwx")->where(array("mid"=>$merchant_id))->getField("mch_id");
//                $wx_key=M("merchants_szlzwx")->where(array("mid"=>$merchant_id))->getField("mch_key");
                if(!$wx_mchid)$data=array('status'=>0,'message'=>"该商户还未进件");
                else $data=array('status'=>1,'wx_mchid'=>$wx_mchid,'wx_key'=>'');
                break;
            case 10: //东莞中信
                $wx_mchid=M("merchants_pfpay")->where(array("merchant_id"=>$merchant_id))->getField("mch_id");
                $wx_key=M("merchants_pfpay")->where(array("merchant_id"=>$merchant_id))->getField("mch_key");
                if(!$wx_mchid)$data=array('status'=>0,'message'=>"该商户还未进件");
                else $data=array('status'=>1,'wx_mchid'=>$wx_mchid,'wx_key'=>$wx_key);
                break;
            case 11: //新大陆
                $intoData=M("merchants_xdl")->where(array("m_id"=>$merchant_id))->find();
                $wx_mchid = $intoData['mercId'];
                $wx_key = $intoData['signKey'];
                if(!$wx_mchid)$data=array('status'=>0,'message'=>"该商户还未进件");
                else $data=array('status'=>1,'wx_mchid'=>$wx_mchid,'wx_key'=>$wx_key);
                break;
            case 12: //乐刷
                $intoData=M("merchants_leshua")->where(array("m_id"=>$merchant_id))->find();
                $wx_mchid = $intoData['merchantId'];
                $wx_key = $intoData['key'];
                if(!$wx_mchid)$data=array('status'=>0,'message'=>"该商户还未进件");
                else $data=array('status'=>1,'wx_mchid'=>$wx_mchid,'wx_key'=>$wx_key);
                break;
            case 13: //平安付
                $intoData=M("merchants_pingan")->where(array("mid"=>$merchant_id))->find();
                $wx_mchid = $intoData['sub_mchid'];
                $wx_key = $intoData['sub_mchkey'];
                if(!$wx_mchid)$data=array('status'=>0,'message'=>"该商户还未进件");
                else $data=array('status'=>1,'wx_mchid'=>$wx_mchid,'wx_key'=>$wx_key);
                break;
            default:
                $data=array('status'=>0,'message'=>"未知错误");
                break;
        }
        $this->ajaxReturn($data);
    }

//    绑定套现台签
    public function add_price_cate()
    {
        $id=I("id");//商户id
        $bank=I("bank");//商户id
        $merchant=$this->merchants->where(array('id'=>$id))->find();
        if($bank == '7'){
            $xybank = M('merchants_xypay')->where(array('merchant_id'=>$id,'pay_style'=>'0'))->find();
            if(!$xybank || !$merchant)$this->error("该商户兴业D0未进件成功");
            $info['alipay_partner'] = $xybank['mch_id'];
            $info['wx_mchid'] = $xybank['mch_id'];
            $info['wx_key'] = $xybank['mch_key'];
            $info['alipay_public_key'] = $xybank['mch_key'];
        } elseif($bank == '4') {
            $msday=M("merchants_mdaypay")->where(array('merchant_id'=>$id,'into_type'=>3))->find();
            if(!$msday || !$merchant)$this->error("该商户D0未进件成功");
            $info['alipay_partner'] = $msday['customerId'];
            $info['wx_mchid'] = $msday['customerId'];
            $info['wx_key'] = $msday['customerId'];
            $info['alipay_public_key'] = $msday['customerId'];
        } else{
            if(!isset($xybank))$this->error("该商户D0未进件成功");
            if(!isset($msday))$this->error("该商户D0未进件成功");
        }
        $cate_c_id=$this->cates->order("id desc")->getField("id")+1;
        $cate_m['alipay_partner']=$info['alipay_partner'];
        $cate_m['wx_mchid']=$info['wx_mchid'];
        $cate_m['wx_key']=$info['wx_key'];
        $cate_m['alipay_public_key']=$info['alipay_public_key'];
        $cate_m['jianchen']=$this->users->where(array('id'=>$merchant['uid']))->getField("user_name");
        $seven = "000000".$cate_c_id;
        $no_number = "YPTTQ".substr($seven,-7);
        $path_url = "data/upload/pay/".$no_number.".png";
        $cate_m['id']=$cate_c_id;
        $cate_m['checker_id'] = 0;
        $cate_m['merchant_id'] = $id;
        $cate_m['no_number'] = $no_number;
        $cate_m['cate_name'] = "D0秒到台签";
        $cate_m['barcode_img'] = $path_url;
        $cate_m['qz_number'] = "YPT";
        $cate_m['status'] =1;
        $cate_m['is_cash'] =1;
        $cate_m['wx_bank'] =6;
        $cate_m['ali_bank'] =6;
        $cate_m['create_time'] = time();
        $this->add_cate_png($cate_c_id,$no_number);
        if($this->cates->add($cate_m)){
            $this->success("套现台签添加成功",U("index"));
        }
    }

    /** 新增图片到数据库
     * @param $cate_c_id  新增台签的id
     * @param $no_number  数字
     */
    function add_cate_png($cate_c_id,$no_number)
    {
        //新增图片到数据库
        $value = "https://sy.youngport.com.cn/index.php?g=Pay&m=Barcode&a=qrcode&type=0&id=".$cate_c_id;
        $errorCorrectionLevel = 'L';//容错级别
        $matrixPointSize = 10;//生成图片大小
        //生成二维码图片
        $path_url = "data/upload/pay/".$no_number.".png";
        // 生成二位码的函数
        vendor("phpqrcode.phpqrcode");
        $av =new \QRcode();
        ob_clean(); //这个很重要
        $av->png($value, $path_url, $errorCorrectionLevel, $matrixPointSize, 2);
        $imgs="data/upload/pay/seller_barcode/bg_pay.png";
        $this->save_qrcode($imgs,$path_url,$no_number);
        return true;
    }
    //测试生成只有下面带有标签的图片
    function save_qrcode($imges, $qrcode,$number='')
    {
        //加载背景图
        $img_bg_info = getimagesize($imges);
        $img_bg_type = image_type_to_extension($img_bg_info[2], false);
        $fun_bg = "imagecreatefrom{$img_bg_type}";
        $img_bg = $fun_bg($imges);


        //加载二维码
        $img_qrcode_src = $qrcode;
        $img_qrcode_info = getimagesize($img_qrcode_src);
        list($width,$height) = $img_qrcode_info;
        $img_qrcode_type = image_type_to_extension($img_qrcode_info[2], false);
        $fun_qrcode = "imagecreatefrom{$img_qrcode_type}";
        $img_qrcode = $fun_qrcode($img_qrcode_src);

//        $font='data/upload/pay/seller_barcode/ttf/arial-bold.otf';
        $font='data/upload/pay/seller_barcode/ttf/ceshi.TTF';
        $fontsize=12;
        $dstwidth=imagesx($img_bg);
        $black = imagecolorallocate($img_bg, 30, 30, 30);
        $len = $this->utf8_strlen($number);
        $a=19;
        $b=385;
        for($i=0;$i<=$len;){
            $box = imagettfbbox($fontsize,0,$font,mb_substr($number,$i,$a,'utf8'));
            $box_width = max(abs($box[2] - $box[0]),abs($box[4] - $box[6]));
            $x=ceil(($dstwidth-$box_width)/2);
            $tempstr=mb_substr($number,$i,$a,'utf8');
            imagettftext($img_bg,$fontsize, 0, $x,$b, $black,$font,$tempstr);
            if($this->utf8_strlen($tempstr)==$a) {
                $i += $a;
                $b += 50;
            }else{
                break;
            }
        }
        imagecopyresized($img_bg, $img_qrcode, 0, 0, 0, 0, 370, 370,$width,$height);
        $save_img = "data/upload/pay/cate/QR_".$number.".png";
        imagepng($img_bg,$save_img);
        imagedestroy($img_bg);
        imagedestroy($img_qrcode);

    }

    public function utf8_strlen($string = null)
    {
        preg_match_all("/./us", $string, $match);
        return count($match[0]);
    }

    public function upload_into(){
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize   =     3145728 ;// 设置附件上传大小
        $upload->exts      =      array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath  =     C('_WEB_UPLOAD_'); // 设置附件上传根目录
        $upload->savePath  =     'merchants/'; // 设置附件上传（子）目录
        // 上传文件
        $info   =   $upload->upload();

        if($info){
            $data['type']=1;
            if($info['positive_id_card_img']){
                $data['back']=1;
                $data['positive_id_card_img']=$info['positive_id_card_img']['savepath'].$info['positive_id_card_img']['savename'];
            }else if($info['id_card_img']){
                $data['back']=2;
                $data['id_card_img']=$info['id_card_img']['savepath'].$info['id_card_img']['savename'];
            }else if($info['header_interior_img']){
                $data['back']=3;
                $data['header_interior_img']=$info['header_interior_img']['savepath'].$info['header_interior_img']['savename'];
            }else if($info['business_license']){
                $data['back']=4;
                $data['business_license']=$info['business_license']['savepath'].$info['business_license']['savename'];
            }else if($info['interior_img_one']){
                $data['back']=5;
                $data['interior_img_one']=$info['interior_img_one']['savepath'].$info['interior_img_one']['savename'];
            }else if($info['interior_img_two']){
                $data['back']=6;
                $data['interior_img_two']=$info['interior_img_two']['savepath'].$info['interior_img_two']['savename'];
            }else if($info['interior_img_three']){
                $data['back']=7;
                $data['interior_img_three']=$info['interior_img_three']['savepath'].$info['interior_img_three']['savename'];
            }else if($info['hand_positive_id_card_img']){
                $data['back']=8;
                $data['hand_positive_id_card_img']=$info['hand_positive_id_card_img']['savepath'].$info['hand_positive_id_card_img']['savename'];
            }else if($info['hand_id_card_img']){
                $data['back']=9;
                $data['hand_id_card_img']=$info['hand_id_card_img']['savepath'].$info['hand_id_card_img']['savename'];
            }else if($info['positive_bank_card_img']){
                $data['back']=10;
                $data['positive_bank_card_img']=$info['positive_bank_card_img']['savepath'].$info['positive_bank_card_img']['savename'];
            }else if($info['bank_card_img']){
                $data['back']=11;
                $data['bank_card_img']=$info['bank_card_img']['savepath'].$info['bank_card_img']['savename'];
            }else if($info['uni_positive_id_card_img']){
                $data['back']=12;
                $data['uni_positive_id_card_img']=$info['uni_positive_id_card_img']['savepath'].$info['uni_positive_id_card_img']['savename'];
            }else if($info['uni_id_card_img']){
                $data['back']=13;
                $data['uni_id_card_img']=$info['uni_id_card_img']['savepath'].$info['uni_id_card_img']['savename'];
            }else if($info['uni_ls_auth']){
                $data['back']=14;
                $data['uni_ls_auth']=$info['uni_ls_auth']['savepath'].$info['uni_ls_auth']['savename'];
            }else if($info['uni_xdl_auth']){
                $data['back']=15;
                $data['uni_xdl_auth']=$info['uni_xdl_auth']['savepath'].$info['uni_xdl_auth']['savename'];
            }else if($info['xdl_auth']){
                $data['back']=16;
                $data['xdl_auth']=$info['xdl_auth']['savepath'].$info['xdl_auth']['savename'];
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

    public function uptosame()
    {
        $id =I("get.id");
        $small_merchant =$this->merchants->where(array('id'=>$id))->find();
        if($small_merchant['mid'] == 0)$this->error('该商户并不是多门店模式');
        $small_cate =$this->cates->where(array('merchant_id'=>$small_merchant['id']))->find();

        $big_merchant =$this->merchants->where(array('id'=>$small_merchant['mid']))->find();
        $big_cate =$this->cates->where(array('merchant_id'=>$big_merchant['id'],'status'=>1,'checker_id'=>0))->find();
        if(!$big_merchant)$this->error('未找到上级大商户');
        if(!$big_cate)$this->error('大商户还未绑定台签');
        #如果商户已经有台签，改台签进件信息
        if($small_cate){
            $cate_m['name'] = $big_cate['name'];
            $cate_m['cate_name'] = $big_cate['cate_name'];
            $cate_m['wx_name'] = $big_cate['wx_name'];
            $cate_m['qz_number'] = $big_cate['qz_number'];
            $cate_m['alipay_partner'] = $big_cate['alipay_partner'];
            $cate_m['alipay_private_key'] = $big_cate['alipay_private_key'];
            $cate_m['alipay_public_key'] = $big_cate['alipay_public_key'];
            $cate_m['wx_appid'] = $big_cate['wx_appid'];
            $cate_m['wx_mchid'] = $big_cate['wx_mchid'];
            $cate_m['wx_key'] = $big_cate['wx_key'];
            $cate_m['wx_appsecret'] = $big_cate['wx_appsecret'];
            $cate_m['ali_bank'] = $big_cate['ali_bank'];
            $cate_m['wx_bank'] = $big_cate['wx_bank'];
            $cate_m['is_top'] = $big_cate['is_top'];
            $cate_m['is_test'] = $big_cate['is_test'];
            $cate_m['is_cash'] = $big_cate['is_cash'];
            $cate_m['status'] = $big_cate['status'];
            $cate_m['update_time'] = time();
            $this->cates->where(array('id'=>$small_cate['id']))->save($cate_m);
            $this->sametointo($id,$big_merchant['id'],$big_cate['wx_bank'],$big_cate['ali_bank']);
            $this->success('同步成功',U('index'));exit;
            //$this->error('该商户已有台签');
        }

        $cate_c_id=$this->cates->order("id desc")->getField("id")+1;
        $seven = "000000".$cate_c_id;
        $no_number = "YPTTQ".substr($seven,-7);
        $path_url = "data/upload/pay/".$no_number.".png";
        $cate_m['id']=$cate_c_id;
        $cate_m['merchant_id']=$id;
        $cate_m['checker_id'] = '';
        $cate_m['no_number'] = $no_number;
        $cate_m['cate_name'] = "默认台签";
        $cate_m['barcode_img'] = $path_url;
        $cate_m['update_time'] = null;
        $cate_m['create_time'] = time();

        $cate_m['jianchen'] = $small_merchant['merchant_name'];
        $cate_m['name'] = $big_cate['name'];
        $cate_m['cate_name'] = $big_cate['cate_name'];
        $cate_m['wx_name'] = $big_cate['wx_name'];
        $cate_m['qz_number'] = $big_cate['qz_number'];

        $cate_m['alipay_partner'] = $big_cate['alipay_partner'];
        $cate_m['alipay_private_key'] = $big_cate['alipay_private_key'];
        $cate_m['alipay_public_key'] = $big_cate['alipay_public_key'];
        $cate_m['wx_appid'] = $big_cate['wx_appid'];
        $cate_m['wx_mchid'] = $big_cate['wx_mchid'];
        $cate_m['wx_key'] = $big_cate['wx_key'];
        $cate_m['wx_appsecret'] = $big_cate['wx_appsecret'];
        $cate_m['ali_bank'] = $big_cate['ali_bank'];
        $cate_m['wx_bank'] = $big_cate['wx_bank'];
        $cate_m['is_top'] = $big_cate['is_top'];
        $cate_m['is_test'] = $big_cate['is_test'];
        $cate_m['is_cash'] = $big_cate['is_cash'];
        $cate_m['status'] = $big_cate['status'];

        $this->add_cate_png($cate_c_id,$no_number);

        #2018/05/22 同步到进件表
        $this->sametointo($id,$big_merchant['id'],$big_cate['wx_bank'],$big_cate['ali_bank']);

        if($this->cates->add($cate_m)){
            $big_merchant_rate =M("merchants_rate")->where(array('merchants_id'=>$big_merchant['id']))->find();
            unset($big_merchant_rate['id']);
            $big_merchant_rate['merchants_id'] = $id;
            $big_merchant_rate['add_time'] = time();
            M("merchants_rate")->add($big_merchant_rate);
            $this->success('同步成功',U('index'));exit;
        }
        $this->error('信息错误');

    }

    /**
     * @param $m_id 分店商户id
     * @param $big_m_id 总部商户id
     * @param $wx_bank 微信进件通道
     * @param $ali_bank 支付宝进件通道
     * @instruction 分店同步总店的进件信息
     */
    private function sametointo($m_id,$big_m_id,$wx_bank,$ali_bank)
    {
        //微信和支付宝相同
        if($wx_bank == $ali_bank){
            if($wx_bank==3){//微信
                $model = M('merchants_upwx');
                $true_field = 'mid';
                $where = array('mid'=>$big_m_id);
            }
            if($wx_bank==7){//兴业
                $model = M('merchants_xypay');
                $true_field = 'merchant_id';
                $where = array('merchant_id'=>$big_m_id);
            }
            if($wx_bank==9){//宿州李总
                $model = M('merchants_szlzwx');
                $true_field = 'mid';
                $where = array('mid'=>$big_m_id);
            }
            if($wx_bank==10){//东莞中信
                $model = M('merchants_pfpay');
                $true_field = 'merchant_id';
                $where = array('merchant_id'=>$big_m_id);
            }
            if($wx_bank==11){//新大陆
                $model = M('merchants_xdl');
                $true_field = 'm_id';
                $where = array('m_id'=>$big_m_id);
            }
            if($wx_bank==12){//乐刷
                $model = M('merchants_leshua');
                $true_field = 'm_id';
                $where = array('m_id'=>$big_m_id);
            }
            $count = $model->where(array("$true_field"=>$m_id))->count();
            $data = $model->where($where)->field('id,'.$true_field,true)->find();
            if($data&&$count==0){
                $data["$true_field"] = $m_id;
                $model->add($data);
            }
        }else{ //微信与支付宝不同
            //微信
            if($wx_bank==3){//微信
                $wx_model = M('merchants_upwx');
                $wx_true_field = 'mid';
                $wx_where = array('mid'=>$big_m_id);
            }
            if($wx_bank==7){//兴业
                $wx_model = M('merchants_xypay');
                $wx_true_field = 'merchant_id';
                $wx_where = array('merchant_id'=>$big_m_id);
            }
            if($wx_bank==9){//宿州李总
                $wx_model = M('merchants_szlzwx');
                $wx_true_field = 'mid';
                $wx_where = array('mid'=>$big_m_id);
            }
            if($wx_bank==10){//东莞中信
                $wx_model = M('merchants_pfpay');
                $wx_true_field = 'merchant_id';
                $wx_where = array('merchant_id'=>$big_m_id);
            }
            if($wx_bank==11){//新大陆
                $wx_model = M('merchants_xdl');
                $wx_true_field = 'm_id';
                $wx_where = array('m_id'=>$big_m_id);
            }
            if($wx_bank==12){//乐刷
                $wx_model = M('merchants_leshua');
                $wx_true_field = 'm_id';
                $wx_where = array('m_id'=>$big_m_id);
            }
            $wx_count = $wx_model->where(array("$wx_true_field"=>$m_id))->count();
            $wx_data = $wx_model->where($wx_where)->field('id,'.$wx_true_field,true)->find();
            if($wx_data&&$wx_count==0){
                $wx_data["$wx_true_field"] = $m_id;
                $wx_model->add($wx_data);
            }

            if($ali_bank==3){//微信
                $ali_model = M('merchants_upwx');
                $ali_true_field = 'mid';
                $ali_where = array('mid'=>$big_m_id);
            }
            if($ali_bank==7){//兴业
                $ali_model = M('merchants_xypay');
                $ali_true_field = 'merchant_id';
                $ali_where = array('merchant_id'=>$big_m_id);
            }
            if($ali_bank==9){//宿州李总
                $ali_model = M('merchants_szlzwx');
                $ali_true_field = 'mid';
                $ali_where = array('mid'=>$big_m_id);
            }
            if($ali_bank==10){//东莞中信
                $ali_model = M('merchants_pfpay');
                $ali_true_field = 'merchant_id';
                $ali_where = array('merchant_id'=>$big_m_id);
            }
            if($ali_bank==11){//新大陆
                $ali_model = M('merchants_xdl');
                $ali_true_field = 'm_id';
                $ali_where = array('m_id'=>$big_m_id);
            }
            if($ali_bank==12){//乐刷
                $ali_model = M('merchants_leshua');
                $ali_true_field = 'm_id';
                $ali_where = array('m_id'=>$big_m_id);
            }
            #检查商户是否已经在该通道进件
            $ali_count = $ali_model->where(array("$ali_true_field"=>$m_id))->count();
            $ali_data = $ali_model->where($ali_where)->field('id,'.$ali_true_field,true)->find();
            if($ali_data&&$ali_count==0){
                $ali_data["$ali_true_field"] = $m_id;
                $ali_model->add($ali_data);
            }
        }
    }


}

