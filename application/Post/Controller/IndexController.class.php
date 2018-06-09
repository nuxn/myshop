<?php

namespace Post\Controller;

use Common\Controller\PostbaseController;

class IndexController extends PostbaseController
{
    public $cates;
    public $role_users;
    public $order_goods;
    public $order;
    public $category;
    public $agent;
    public $merchants;
    public $memcardModel;
    public $users;
    public $userId;
    public $http;
    public $pays;

    public function _initialize()
    {
        parent::_initialize();
        $this->userId = $this->userInfo['uid'];
        $this->users = M("merchants_users");
        $this->merchants = M("merchants");
        $this->agent = M("merchants_agent");
        $this->category = M("category");
        $this->order = M("order");
        $this->pays = M('pay');
        $this->order_goods = M("order_goods");
        $this->cates = M("merchants_cate");
        $this->memcardModel = M("screen_memcard");
        $this->role_users = M("merchants_role_users");
        $this->http = 'http';
    }

    /**
     * @function 发送手机验证
     * @parme $phone 手机号码
     * @return  code: success|error , msg
     */
    public function getSms()
    {
        $phone = I("phone");
        //$phone = 18218609182;
        if (empty($phone)) {
            $this->ajaxReturn(array("code" => "error", "msg" => L('PHONE_EMPTY')));
        }

        Vendor("SMS.CCPRestSmsSDK");
        $config_arr = C('SMS_CONFIG'); // 读取短信配置
        $tempId = $config_arr['PwdTemplateId'];

        $rest = new \REST($config_arr['serverIP'], $config_arr['serverPort'], $config_arr['softVersion']);
        $rest->setAccount($config_arr['accountSid'], $config_arr['accountToken']);
        $rest->setAppId($config_arr['appId']);

        $sms_msg = rand(100000, 999999); //生成短信信息

        // 把缓存保存到缓存中 6s后失效
        S('sms_pwd', $sms_msg, 600);// 缓存$str数据3600秒过期

        $result = $rest->sendTemplateSMS($phone, array($sms_msg, '5'), $tempId); // 发送模板短信

        if ($result == NULL) {
            $this->ajaxReturn(array("code" => "error", "msg" => "result error!", "data" => array()));
        }
        if ($result->statusCode != 0) { // 错误
            $this->ajaxReturn(array("code" => "error", "msg" => L('SEND_SMS_ERROR'))); //$result->statusCode
            //$this->ajaxReturn(array("code" => "error", "msg111" =>$result->statusCode)); //$result->statusCode
        } else {
            $this->ajaxReturn(array("code" => "success", "msg" => L('SEND_SMS_SUCCESS')));
        }

    }

    /***
     * @function editPwd
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

            if ($sms != S('sms_pwd')) {
                $this->ajaxReturn(array("code" => "error", "msg" => L('SMS_ERROR')));
            }
            $data = array();
            $data['user_phone'] = $phone;
            $data['user_pwd'] = md5($pwd);
            $data['ip_address'] = get_client_ip();
            if (M("Merchants_users")->where(array('user_phone' => $phone))->save($data)) {

                $this->ajaxReturn(array("code" => "success", "msg" => L('RESET_PWD_SUCCESS')));
            } else {
                $this->ajaxReturn(array("code" => "error", "msg" => L('RESET_PWD_ERROR')));
            }
        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => L('HACKER_MSG')));
        }
    }


    //   扫码传递token
    public function get_token()
    {
        $random = I("random");
        $mac = I("mac");
        $mac_id = M("screen_pos")->where("mac='$mac'")->getField("id");
        $token = M("post_token")->where("random='$random'")->find();
        if (!$token) $this->ajaxReturn(array("code" => "error", "msg" => "登录失败,未找到该用户"));
        $uid = $token['uid'];
        $token['value'] = json_decode($token['value']);
        $user = M("merchants_users")->where("id=$uid")->find();
        $role_id = M("merchants_role_users")->where("uid=$uid")->getField("role_id");
        if ($role_id == 7) {
            $merchant_id = $user['pid'];
            $merchant_name = M("merchants_users")->where("id=$merchant_id")->getField('user_name');
            $user_name = $user['user_name'];
            $user_id = $user['id'];
        }
        if ($role_id == 3) {
            $merchant_name = $user['user_name'];
            $user_name = "";
            $user_id = "";
        }
        $data = array(
            "merchant_name" => $merchant_name,
            "user_name" => $user_name,
            "user_id" => $user_id,
            "mac_id" => $mac_id,
            "token" => $token
        );
        if ($token) $this->ajaxReturn(array("code" => "success", "msg" => "登录成功", "data" => $data));
        else $this->ajaxReturn(array("code" => "error", "msg" => "登录失败"));
    }


    /**
     * @param $phone
     * @return bool
     * 手机号码是否已经注册
     */
    private function checkUser($phone)
    {

        $data = $this->users->where(array("user_phone" => $phone))->count();
        if ($data > 0) {
            return true;
        } else {
            return false;
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

        foreach ($auth_arr as $k => $v) $info[$k] = in_array($v, $old_auth_arr) ? '1' : '0';
        unset($info['auth']);
    }

    private function get_role_full_name($role_id, $uid)
    {
        if (in_array($role_id, array(2, 3))) {//商家代理
            if ($role_id == '3') return M("merchants")->where(array("uid" => $uid))->getField("merchant_name");
            else return M("merchants_agent")->where(array("uid" => $uid))->getField("agent_name");
        } else if ($role_id == '7') {//收银员
            $pid = $this->users->where(array("id" => $uid))->getField("pid");
            return $this->merchants->where(array("uid" => $pid))->getField("merchant_name");
        } else {//其他
            return '';
        }

    }

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

            $pwd = I("pwd");
            if (!$pwd) {
                $this->ajaxReturn(array("code" => "error", "msg" => L('PWD_EMPTY')));
            }
            if (!($this->checkUser($phone))) {
                $this->ajaxReturn(array("code" => "error", "msg" => L('USER_NOT_EXIT')));
            }
            $mac = I("mac");
            $mac_id = M("screen_pos")->where("mac='$mac'")->getField("id");

            $users = $this->users->alias("u")
                //->field("ru.uid,ru.role_id,r.role_name,u.user_phone,u.user_pwd")
                ->field("ru.uid,ru.role_id,u.user_phone,u.user_pwd,u.voice_open,u.auth")
                ->join("left join " . C('DB_PREFIX') . "merchants_role_users as ru on ru.uid=u.id")
                //->join("left join ".C('DB_PREFIX')."merchants_role as r on ru.role_id=r.id")
                ->where(array("user_phone" => $phone))
                ->find();
            $_SESSION['uid'] = $users['uid'] ? $users['uid'] : $this->userId;
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
                    $res = $this->merchants->field("id,status")->where(array('uid' => $users['uid']))->find(); //查看是否已经填写过商户资料
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
                /* $user=M("merchants_users")->where("id=$uid")->find();
                $role_id=M("merchants_role_users")->where("uid=$uid")->getField("role_id");
                if($role_id ==7){
                    $merchant_id=$user['pid'];
                    $merchant_name=M("merchants_users")->where("id=$merchant_id")->find();
                    $user_name=$user['user_name'];
                    $user_id=$user['id'];
                }
                if($role_id ==3){
                    $merchant_name=$user['user_name'];
                    $user_name="";
                    $user_id="";
                }
                $data=array(
                    "merchant_name" =>$merchant_name,
                    "user_name" =>$user_name,
                    "user_id" =>$user_id,
                    "mac_id" =>$mac_id,
                    "token" =>$token
                );*/

                //返回当前商家或代理的名称
                $users["role_full_name"] = $this->get_role_full_name($users['role_id'], $users['uid']);
                if (!$users["role_full_name"]) $users["role_full_name"] = '';

                //返回员工权限
                //$this->check_employee_auth($users);
                unset($users['user_pwd']);

                //存储登录信息
                $users['mac_id'] = $mac_id;
                $users['token_add_time'] = time();
                $TOKEN = $this->build_token($users);
                //session($TOKEN, $users);
                $token_info = M("post_token")->where(array("uid" => $users['uid']))->find();

                if (!$token_info) M("post_token")->add(array("uid" => $users['uid'], "token" => $TOKEN, "time_start" => $users['token_add_time'], "value" => json_encode($users)));
                else {
//                    Vendor('Cache.MyRedis');
//                    $redis = new \MyRedis();
//                    $Ip = get_client_ip();
//                    $IpLocation = new \Org\Net\IpLocation('UTFWry.dat'); // 实例化类 参数表示IP地址库文件
//                    $area = $IpLocation->getlocation($Ip); // 获取某个IP地址所在的位置
//                    $redis->set($token_info['token'], json_encode(array("login_ip" => $Ip, "login_time" => date("Y-m-d H:i:s"), "address" => $area['country'], "network" => $area['area'])));
                    M("post_token")->where(array("uid" => $users['uid']))->save(array("token" => $TOKEN, "time_start" => $users['token_add_time'], "value" => json_encode($users)));
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

    //随机流水号
    public function get_order_sn()
    {
        $order_sn = date('YmdHis') . mt_rand(10000, 99999);
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $order_sn));
    }

    //账户资料
    public function zhanghu()
    {
        $uid = $this->userId;
        //$uid = '115';
        $mch = $this->get_merchant1($uid);
        $id = $mch['mid'];
        $users = $this->users->field('id,user_name,user_phone,auth')->where(array('id' => $uid))->find();
        $this->check_employee_auth($users);
        $jinri = $this->type_time(1);
        $benyue = $this->type_time(4);
        //$jinri  = array('1491321600','1491407999');
        //p($users);
        $role_id = $this->role_users->where(array('uid' => $uid))->getField('role_id');
        if ($role_id == 7) {
            $map['paytime'] = array('between', $jinri);
            $map['status'] = 1;
            $map['checker_id'] = $id;
            $jintian = $this->pays->field('sum(price) jinri')->where($map)->select();
            $users['jinri'] = $jintian[0]['jinri'];
            $where['paytime'] = array('between', $benyue);
            $where['checker_id'] = $id;
            $where['status'] = 1;
            $benyue1 = $this->pays->field('sum(price) benyue')->where($where)->select();
            $users['benyue'] = $benyue1[0]['benyue'];
        } else {
            $map['paytime'] = array('between', $jinri);
            $map['status'] = 1;
            $map['merchant_id'] = $id;
            $jintian = $this->pays->field('sum(price) jinri')->where($map)->select();
            $users['jinri'] = $jintian[0]['jinri'];
            $where['paytime'] = array('between', $benyue);
            $where['merchant_id'] = $id;
            $where['status'] = 1;
            $benyue1 = $this->pays->field('sum(price) benyue')->where($where)->select();
            $users['benyue'] = $benyue1[0]['benyue'];
        }
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $users));
    }

    //    获取商户logo
    public function logo_url()
    {
        $uid = $this->userId;
        $role_id = $this->role_users->where(array('uid' => $uid))->getField('role_id');
        if ($role_id == 7) {
            $mid = $this->users->where(array('id' => $uid))->getField('pid');
        } else {
            $mid = $uid;
        }
        $coupon = M("merchants")->where("uid='$mid'")->getField("base_url");
        if ($coupon) $data['logo_url'] = $this->http . "://" . $_SERVER['HTTP_HOST'] . $coupon;
        else {
            $data['logo_url'] = "";
        }
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $data));
    }


    // 商品检索
    public function goods_list()
    {
        ($bar_code = I("bar_code")) || $this->ajaxReturn(array("code" => "error", "msg" => "bar_code is empty", "data" => ""));
        //$bar_code  = '64343104133';
        $user_id = $this->userId;
        //$user_id  = '26';
        $role_id = $this->role_users->where(array('uid' => $user_id))->getField('role_id');
        if ($role_id == 7) {
            $uid = $this->users->where(array('id' => $user_id))->getField('pid');
        } else {
            $uid = $user_id;
        }
        $goods_id = M("goods")->where("bar_code=$bar_code AND mid=$uid")->getField("goods_id");
        //p(M()->_sql());
//        $goods_list = M("goods g")
//            ->join("__GOODS_SKU__ gs on g.goods_id=gs.goods_id")
//            ->where('g.goods_id = '.$goods_id)
//            ->field("g.goods_name,g.shop_price,g.bar_code,g.discount,gs.properties")
//            ->select();
        if ($goods_id == "") {
            $this->ajaxReturn(array("code" => "success", "msg" => "false"));
        } else {
            $goods = M('goods')->where('goods_id=' . $goods_id)->field('goods_id,goods_name,shop_price,bar_code')->find();
            //dump($goods);
            $ress = M('goods_sku')->where('goods_id=' . $goods_id)->field('discount,price,sku_id,properties')->select();
            if ($ress) {
                $goods['properties'] = $ress;
                /*foreach($goods['properties'] as $k=>$v){
                    //dump($v["properties"]);
                    $goods['properties'][$k]["properties"] = $v["properties"];
                }*/
            } else {
                $aa = array();
                $aa['price'] = $goods['shop_price'];
                $aa['discount'] = $goods['discount'];
                //$aa['discount']='11111';
                $goods['properties'][] = $aa;
                //unset($goods['discount']);
            }
            $this->ajaxReturn(array("code" => "success", "msg" => "true", "data" => $goods));
        }


    }

    //商品分页
    public function fenye()
    {
        $user_id = $this->userId;
        //$user_id  = '26';
        $group_id = I('group_id');
        //$group_id  = '357';
        //$p  = '1';
        $p = I('p');
        $role_id = $this->role_users->where(array('uid' => $user_id))->getField('role_id');
        if ($role_id == 7) {
            $mid = $this->users->where(array('id' => $user_id))->getField('pid');
        } else {
            $mid = $user_id;
        }
        $map['mid'] = $mid;
        if ($group_id) $map['group_id'] = $group_id;
        $list = M('goods g')->field('g.goods_id,g.goods_name,g.goods_img1,g.shop_price')->where($map)->page($p . ',10')->select();
        //p($list[1]['goods_img1']);
        //$img1= array_column($list,'goods_img1');
        foreach ($list as $k => $v) {
            $list[$k]['goods_img1'] = $this->http . "://" . $_SERVER['HTTP_HOST'] . substr($v['goods_img1'], 1);
        }
        //p($list);
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $list));
    }

    //进入选择商品首页
    public function choice()
    {
        $user_id = $this->userId;
        //$user_id  = 26;
        $role_id = $this->role_users->where(array('uid' => $user_id))->getField('role_id');
        if ($role_id == 7) {
            $mid = $this->users->where(array('id' => $user_id))->getField('pid');
        } else {
            $mid = $user_id;
        }
        $group = M("goods_group");
        $g_info['group'] = $group->where(array('mid' => $mid))->field('group_id,group_name')->select();
        //p($g_info);
        if ($g_info['group'] == "") {
            $this->ajaxReturn(array("code" => "error", "msg" => "请添加商品！", "data" => ""));
        } else {
            if (!empty($g_info['group'])) {
                $map['group_id'] = array('in', array_column($g_info['group'], 'group_id'));
            }
            //$list = $User->where('status=1')->order('create_time')->page($_GET['p'].',25')->select();
            $map['mid'] = $mid;
            //p($group_id);
            $goods = M("goods g")->where($map)->field('g.goods_id,g.goods_name,g.goods_img1,g.shop_price')->select();
            //p(M()->_sql());
            //p($goods);
            foreach ($goods as $k => $v) {
                $goods[$k]['goods_img1'] = $this->http . "://" . $_SERVER['HTTP_HOST'] . substr($v['goods_img1'], 1);
            }
            //p(M()->_sql());
            //p($goods);
            $g_info['goods_info'] = $goods;
            // p($g_info);
            $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $g_info));
        }
    }

    /**现金支付作废
     * @param $order_sn
     */
    public function del_order()
    {
        $a = array(1, 2, 45, 53, 9, 3);
        $b = array(1, 2, 9, 45, 46, 3);
        p(array_merge(array_diff($a, $b), array_diff($b, $a)));
    }

    /**现金退款
     * @param $order_sn
     * @param $price_back
     */
    public function tuikaun()
    {
        $order_sn = I('order_sn');
        $price_back = I('price_back');
        $pay = $this->pays; // 实例化对象/
        // 要修改的数据对象属性赋值
        $data['price_back'] = $price_back;
        $data['status'] = '2';
        $res = $pay->where(array('remark' => $order_sn))->data($data)->save();
        if ($res) $this->ajaxReturn(array("code" => "success", "msg" => "退款成功！"));
        else$this->ajaxReturn(array("code" => "error", "msg" => "失败"));
    }


    /**
     * 图片上传
     */
    private function upload_pic()
    {
        $info = array();//存储图片
        $pic_root_path = C('_WEB_UPLOAD_');
        if ($_FILES) {
            $upload = new \Think\Upload();
            $upload->maxSize = 3145728;
            $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
            $upload->rootPath = C('_WEB_UPLOAD_');
            $upload->savePath = 'memcard/';
            $upload->saveName = uniqid;//保持文件名不变
            $info = $upload->upload();
        }
        $img = $info['logoimg'] ? $pic_root_path . $info['logoimg']['savepath'] . $info['logoimg']['savename'] : '';
        return $img;
    }

    /**
     * 微信会员卡添加
     */
    public function add_memcard()
    {

        $post = I("");
        $img = $this->upload_pic();
        $post['logoimg'] = $img ? $this->http . $img : 'http://sy.youngport.com.cn/themes/simplebootx/Public/pay/images/smalllogo.png';
        //if (!$post['merchant_name']) $post['merchant_name'] = '洋仆淘商城' . date("is");
        $jianchen = M("merchants_cate mc")->where(array("m.uid" => $this->userId))->join("LEFT JOIN __MERCHANTS__ m ON mc.merchant_id= m.id")->getField("jianchen");
        $post['merchant_name'] = $jianchen ? $jianchen : mb_substr($post['merchant_name'], 0, 10, 'utf-8') || "洋仆淘";
        if (!$post['cardname']) $post['cardname'] = '洋仆淘会员卡';
        if (mb_strlen($post['cardname'], 'utf8') > 9) $this->ajaxReturn(array("code" => "error", "msg" => "会员卡名称不能超过9个汉字"));
        if (mb_strlen($post['merchant_name'], 'utf8') > 12) $this->ajaxReturn(array("code" => "error", "msg" => "商家简称不能超过12个汉字"));
        if (!$post['color']) $post['color'] = 'Color010';
        if (M('screen_memcard')->where(array("cardname" => $post['cardname'], "mid" => $this->userId))->getField("id")) {
            $this->ajaxReturn(array("code" => "error", "msg" => "会员卡不能重复创建"));
        }
        if (!$post['service_phone']) $post['service_phone'] = '400-888-3658';
        if (!$post['description']) $post['description'] = '1.会员卡仅限申请者本人使用,不可转让与他人;\n2.会员结账时,请主动提供会员卡号或注册手机号。';

        $post['custom_url_name'] = '立即使用';
        $post['custom_url'] = 'http://m.hz41319.com/wei/index.php';
        $post['custom_url_sub_title'] = '点击激活';

        $post['promotion_url_name'] = '更多推荐';
        $post['promotion_url'] = 'http://m.hz41319.com/wei/index.php';
        $post['url'] = $this->http . '/index.php?s=Api/Member/get_member_level';//会员等级

        if (!$post['prerogative']) $post['prerogative'] = '领卡后会员享专属优惠!';
        if (!$post['cardnum']) $post['cardnum'] = '10000000';//发卡总量
        if (!$post['expense']) $post['expense'] = '10';//消费10元
        if (!$post['expense_credits']) $post['expense_credits'] = '1';//消费10元送1积分
        if (!$post['activate_credits']) $post['activate_credits'] = '10';//激活送10积分
        if (!$post['credits_use']) $post['credits_use'] = '10';//使用10积分
        if (!$post['credits_discount']) $post['credits_discount'] = '1';//使用10积分抵扣1块钱
        $post['max_reduce_bonus'] = '10000';//单笔最多使用xx积分
        $post['max_increase_bonus'] = '10000';//单次赠送最大积分

        if (!$post['level1']) $post['level1'] = 0;
        if (!$post['level2']) $post['level2'] = 100;
        if (!$post['level3']) $post['level3'] = 101;
        if (!$post['level4']) $post['level4'] = 1000;
        if (!$post['level5']) $post['level5'] = 1001;
        if (!$post['level6']) $post['level6'] = 10000;
        if ($post['level1'] >= $post['level2']) $this->ajaxReturn(array("code" => "error", "msg" => "银卡的积分上限值必须大于下限值"));
        if ($post['level3'] - 1 != $post['level2']) $this->ajaxReturn(array("code" => "error", "msg" => "金卡的积分下限值必须等于银卡积分上限值+1"));
        if ($post['level3'] >= $post['level4']) $this->ajaxReturn(array("code" => "error", "msg" => "金卡的积分上限值必须大于下限值"));
        if ($post['level5'] - 1 != $post['level4']) $this->ajaxReturn(array("code" => "error", "msg" => "白金卡的积分下限值必须等于金卡积分上限值+1"));
        if ($post['level5'] >= $post['level6']) $this->ajaxReturn(array("code" => "error", "msg" => "白金卡的积分上限值必须大于下限值"));
        $curl_datas = $this->create_jsonstr($post);
        $token = get_weixin_token();
        $create_card_url = "https://api.weixin.qq.com/card/create?access_token=$token";
        $result = request_post($create_card_url, $curl_datas);
        $result = object2array(json_decode($result));
        // p($result);
        if ($result['errmsg'] == 'ok' && $result['errcode'] == 0) {
            $post['card_id'] = $result['card_id'];
            $post['add_time'] = time();
            $post['update_time'] = time();
            $post['mid'] = $this->userId;
            if (!M('screen_memcard')->where(array("card_id" => $post['card_id']))->getField("id")) {
                $res = M('screen_memcard')->add($post);
                $this->memcard_query($post['card_id']);
                $this->activateuserform($post);
                if (!$res) $this->ajaxReturn(array("code" => "error", "msg" => "创建会员卡失败"));
            }
            $this->ajaxReturn(array("code" => "success", "msg" => "创建会员卡成功"));
        } else {
            get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/weixin/', 'member_card', 'post/index上传图片', json_encode($result));
            $this->ajaxReturn(array("code" => "error", "msg" => "创建会员卡失败"));
        }


    }

    /**查询微信会员卡是否创建成功
     * @param string $card_id
     */
    public function memcard_query($card_id)
    {
        $card_id = $card_id ? $card_id : I("card_id");
        $status_arr = array(
            "CARD_STATUS_NOT_VERIFY" => 1,
            "CARD_STATUS_VERIFY_FALL" => 2,
            "CARD_STATUS_VERIFY_OK" => 3,
            "CARD_STATUS_USER_DELETE" => 5,
            "CARD_STATUS_USER_DISPATCH" => 6,
        );
        if (!$card_id) $this->ajaxReturn(array("code" => "error", "msg" => "card_id为空"));
        $token = get_weixin_token();
        $mem_card_query_url = "https://api.weixin.qq.com/card/get?access_token=$token";
        $result = request_post($mem_card_query_url, json_encode(array("card_id" => $card_id)));
        get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/weixin/', 'member_card', 'post/index查询会员卡', $result);
        $result = json_decode($result, true);
        if ($result['errmsg'] == 'ok' && $result['errcode'] == 0) {
            $status = $status_arr[$result['card']['member_card']['base_info']['status']];
            if (!$status) $status = 1;
            $this->memcardModel->where(array("card_id" => $card_id))->save(array("cardstatus" => $status));
        } else {

        }
    }

    /**会员卡一键开卡
     * @param array $param
     */
    public function activateuserform($param = array())
    {
        if (!$param["card_id"]) $param["card_id"] = 'pyaFdwHr69B3DJjAe8VAvN8F8jwY';
        if (!$param) $this->ajaxReturn(array("code" => "error", "msg" => "ID不能为空"));
        $token = get_weixin_token();
        $arr = array(
            "card_id" => $param["card_id"],
            "required_form" => array(
                "common_field_id_list" => array(
                    "USER_FORM_INFO_FLAG_MOBILE",
                    "USER_FORM_INFO_FLAG_NAME",
                    "USER_FORM_INFO_FLAG_BIRTHDAY"
                )
            )
        );

        $mem_card_query_url = "https://api.weixin.qq.com/card/membercard/activateuserform/set?access_token=$token";
        $result = request_post($mem_card_query_url, json_encode($arr));
        $result = json_decode($result, true);
        file_put_contents('./huiyuanka.log', date("Y-m-d H:i:s") . '创建会员卡时一键开卡' . json_encode($result) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    /**
     * @function choice_goods
     * @intro 选择商品接口
     * @parme $cat_id  分类ID
     * @return  code: success|error , msg， data
     */
    public function choice_goods()
    {
        //默认返回热销商品
        $is_hot = I('is_hot', '0');
        $group_id = I('group_id');
        $user_id = $this->userId;
        //$user_id =209;
        //$cat_id = 1;
        $role_id = $this->role_users->where(array('uid' => $user_id))->getField('role_id');
        if ($role_id == 7) {
            $uid = $this->users->where(array('id' => $user_id))->getField('pid');
        } else {
            $uid = $user_id;
        }
        $map['mid'] = $uid;
        if ($is_hot == '1') $map['is_hot'] = $is_hot;
        if ($group_id) $map['group_id'] = $group_id;
        $goods = M("goods g")
            ->where($map)->field('g.goods_id,g.goods_name,g.goods_img1,g.shop_price')
            ->select();
        foreach ($goods as $k => $v) {
            $goods[$k]['goods_img1'] = $this->http . "://" . $_SERVER['HTTP_HOST'] . substr($v['goods_img1'], 1);
        }
        //$goods_id =array_column($goods,'goods_id');
        //p($goods_id );
        /*$where['goods_id']=array('in',array_column($goods,'goods_id'));
        //p(M()->_sql());
        $ress = M("goods_sku")->where($where)->select();*/
        /*echo '<pre>';
        var_dump($goods_info);*/
        /*if($ress){
            $goods['properties']=$ress;*/
        /*foreach($ress as $k=>$v){
                //dump($v['goods_id']);
                dump($goods[$k]['goods_id']);
                if($v['goods_id']==$goods[$k]['goods_id'])$goods[$k]['properties'][$k]=$v;
                //dump($k);
                //$goods['properties'][$k]["properties"] = $v["properties"];

            }*/
        /*}else {
            $aa = array();
            $aa['price'] = $goods['shop_price'];
            $aa['discount'] = $goods['discount'];
            //$aa['discount']='11111';
            $goods['properties'][] = $aa;
           //unset($goods['discount']);
        }*/
        if ($goods == "") {
            $this->ajaxReturn(array("code" => "error", "msg" => "失败", "data" => ""));
        } else {
            $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $goods));
        }
    }

    /**
     * 获取会员卡
     */
    public function get_memcard()
    {
        $status_arr = array(
            "1" => "审核中",
            "2" => "审核失败",
            "3" => "审核成功",
            "4" => "已投放",
        );
        $user_id = $this->userId;
        //$user_id =209;
        //$cat_id = 1;
        $role_id = $this->role_users->where(array('uid' => $user_id))->getField('role_id');
        if ($role_id == 7) {
            $uid = $this->users->where(array('id' => $user_id))->getField('pid');
        } else {
            $uid = $user_id;
        }
        $map['mid'] = $uid;
        $res = $this->memcardModel->where($map)->field("cardnum,drawnum,cardstatus,color,show_qrcode_url,card_id,id,logoimg")->find();
        if ($res) {
            $res['desc'] = $status_arr[$res['cardstatus']];
            if (!$res['show_qrcode_url']) $res['show_qrcode_url'] = '';
            $res['remain'] = strval($res['cardnum'] - $res['drawnum']);
            $res['activate_num'] = M('screen_memcard_use')->where(array("card_id" => $res['card_id'], "status" => "1"))->count();
        } else
            $res = (object)null;
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $res));
    }


    /**
     * 会员卡详情
     */
    public function get_memcard_info()
    {
        $id = I("id");
        if (!$id) $this->ajaxReturn(array("code" => "error", "msg" => "编号为空"));
        $map['id'] = $id;
        $res = $this->memcardModel->where($map)->field("*")->find();
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $res));
    }


    /**
     * @function choice_goods
     * @intro 选择商品规格接口
     * @parme $goods_id  分类ID
     * @return  code: success|error , msg， data
     */
    public function choice_goods_sku()
    {
        $goods_id = I("goods_id");
        //$goods_id = 27;
        $ress = M("goods_sku")->where(array('goods_id' => $goods_id))->select();
        //p($ress);
        if (!$ress) {
            $ress = M('goods')->where(array('goods_id' => $goods_id))->field('shop_price as price,discount,goods_id')->find();
            $this->ajaxReturn(array("code" => "success", "msg" => "该商品没有规格可选", "data" => array(0 => $ress)));
        } else {
            $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $ress));
        }
    }

    //点击挂单
    public function res_order()
    {
        if (IS_POST) {
            $order_info = array();
            $order_info["order_sn"] = I("order_sn");//流水号
            $paystyle_id = I('paystyle_id');//支付方式
            $order_info["paystyle_id"] = $paystyle_id;
            $order_info["order_amount"] = I("order_amount");//应收金额
            $order_info["pay_status"] = I("pay_status");//支付状态为0
            $order_info["type"] = "3";//3为pos机订单
            //$order_info["pay_time"]  = I("pay_time");//支付时间
            $order_info['integral'] = I('dikoufen');//该订单使用积分
            $order_info['integral_money'] = I('dikoujin');//该订单使用积分抵扣金额
            $order_info["coupon_code"] = I("coupon_code", "");//优惠券ID
            $order_info["coupon_price"] = I("coupon_price");//使用优惠券抵扣多少金额
            $order_info["order_goods_num"] = I("order_goods_num");
            $order_info["total_amount"] = I("total_amount");//原订单总价
            $order_info["user_id"] = I('uid') ? I('uid') : $this->userId;//当前使用双屏的用户ID
            $order_info["add_time"] = I("timestamp");
            $order_info["order_benefit"] = I("order_benefit");//整单优惠金额
            $order_info["card_code"] = I("card_id", "");//会员卡号
            /* $order_info["order_sn"] = date('YmdHis').rand(1000,9999).UID;
             $order_info["goods_num"]  = 4;
             $order_info["goods_price"]  = 32;
             $order_info["total_amount"]  = 30;
             $order_info["user_id"]  = 71;*/
            M()->startTrans(); // 开启事务
            $data = M('order');
            $res = $data->add($order_info);
            //echo $res;die;
            if ($res) { // 加入订单表
                $order_goods = array();
                $goods = M("order_goods");
                $bar_code = explode(",", I("bar_code"));
                $goods_num = explode(",", I("goods_num"));
                $goods_name = explode(",", I("goods_name"));
                $goods_price = explode(",", I("goods_price"));
                $discount = explode(",", I("goods_discount"));
                $sku = explode(",", I("sku"));
                /* $bar_code = "5588585,5668885,11111111";
                 $bar_code = explode(",",$bar_code);
                 $discount = "50,90,10";
                 $discount = explode(",",$discount);
                 $goods_num = "5,3,14";
                 $goods_num = explode(",",$goods_num);*/
                foreach ($bar_code as $key => $val) {
                    //$goods = array();
                    $order_goods[$key]["bar_code"] = $val;
                    $order_goods[$key]['order_id'] = $res;
                    $order_goods[$key]["discount"] = $discount[$key];
                    $order_goods[$key]["goods_name"] = $goods_name[$key];
                    $order_goods[$key]["goods_price"] = $goods_price[$key];
                    $order_goods[$key]["goods_num"] = $goods_num[$key];
                    $order_goods[$key]["sku"] = $sku[$key];
                };
                $result = $goods->addAll($order_goods);
                /* $pay_info=array(
                    "remark"=>$order_sn,
                    "mode"=>5,
                    "merchant_id" =>$merchant_id,
                    "checker_id" =>$checker_id,
                    "paystyle_id" => $paystyle_id,
                    "price"=>$order_amount,
                    "status"=>0,
                    "cate_id"=>1,
                    "paytime" =>time()
                );
                $pay = $this->pays;
                $pay->add($pay_info);*/
                if ($result) {
                    M()->commit();
                    $this->ajaxReturn(array("code" => "success", "msg" => "挂单成功"));
                } else {
                    M()->rollback();
                    $this->ajaxReturn(array("code" => "error", "msg" => "挂单失败", "data" => "网络错误！"));
                }
            } else {
                M()->rollback();
                $this->ajaxReturn(array("code" => "error", "msg" => "失败", "data" => "网络错误！"));
            }
        } else {
            $this->ajaxReturn(array("code" => "error", 'msg' => '参数错误'));
        }

    }

    //获取台签
    public function cart()
    {
        $m_info = $this->get_merchant1($this->userId);
        $mid = $m_info['mid'];
        $cart = $this->cates->where("merchant_id='$mid'")->find();
        if (!$cart) {
            $this->ajaxReturn(array("code" => "error", "msg" => "失败", "data" => "还没有绑定台签"));
        }
        $value = "https://" . $_SERVER['HTTP_HOST'] . "/index.php?g=Pay&m=Barcode&a=qrcode&id=" . $cart['id'] . "&checker_id=" . $m_info['checker'];
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array('url' => $value, 'message' => $cart)));
    }

    //扫台签支付查询
    public function find_pay()
    {
        $cate_id = I("cate_id");
        $time = I("timestamp");
        $where['paytime'] = array('between', array($time - 10, $time));
        $pay = $this->pays->where("cate_id=$cate_id")->where($where)->field('paytime,paystyle_id,remark,price,merchant_id,status')->order('paytime DESC')->find();
        if (!$pay) {
            $this->ajaxReturn(array("code" => "error", "msg" => "未找到订单"));
        }
        if ($pay['status'] == "1") {
            $pay['merchant_name'] = M('merchants')->where("id=$pay[merchant_id]")->getField('merchant_name');
            $this->ajaxReturn(array("code" => "success", "msg" => "支付成功", "data" => $pay));
        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => "还未支付成功", "data" => $pay));
        }

    }

    /**创建会员卡添加json字符串
     * @param $post
     * @return mixed
     */
    private function create_jsonstr($post)
    {
        $post['expense'] = $post['expense'] * 100;
        $post['credits_discount'] = $post['credits_discount'] * 100;
        $curl_datas = array(
            "card" => array(
                "card_type" => "MEMBER_CARD",
                "member_card" => array(
                    "base_info" => array(
                        "logo_url" => urlencode($post['logoimg']),
                        "brand_name" => urlencode($post['merchant_name']),
                        "code_type" => "CODE_TYPE_TEXT",
                        "title" => urlencode($post['cardname']),
                        "color" => urlencode($post['color']),
                        "notice" => urlencode($post['notice']),
                        "service_phone" => urlencode($post['service_phone']),
                        "description" => urlencode($post['description']),
                        "date_info" => array(
                            "type" => "DATE_TYPE_PERMANENT"
                        ),
                        "sku" => array(
                            "quantity" => urlencode($post['cardnum']),
                        ),
                        "get_limit" => 1,
                        "use_custom_code" => false,
                        "can_give_friend" => false,
                        "location_id_list" => array(
                            123,
                            12321,
                            345345),
//                        "custom_url_name" => urlencode($post['custom_url_name']),
//                        "custom_url" => urlencode($post['custom_url']),
//                        "custom_url_sub_title" => urlencode($post['custom_url_sub_title']),
                        "promotion_url_name" => urlencode($post['promotion_url_name']),
                        "promotion_url" => urlencode($post['promotion_url']),
                        "need_push_on_view" => true
                    ),
                    "supply_bonus" => true,
                    "supply_balance" => false,
                    "prerogative" => urlencode($post['prerogative']),
                    "wx_activate" => true,
                    "custom_field1" => array(
                        "name_type" => "FIELD_NAME_TYPE_LEVEL",
                        "url" => urlencode($post['url']),
                    ),
                    //"activate_url" => "http://www.xxx.com",
//                    "custom_cell1" => array(
//                        "name" => urlencode($post['name']),
//                        "tips" => urlencode($post['tips']),
//                        "url" => "http://www.xxx.com"
//                    ),
                    "bonus_rule" => array(
                        "cost_money_unit" => urlencode($post['expense']),
                        "increase_bonus" => urlencode($post['expense_credits']),
                        "max_increase_bonus" => urlencode($post['max_increase_bonus']),
                        "init_increase_bonus" => urlencode($post['activate_credits']),
                        "cost_bonus_unit" => urlencode($post['credits_use']),
                        "reduce_money" => urlencode($post['credits_discount']),
                        "least_money_to_use_bonus" => urlencode($post['expense']),
                        "max_reduce_bonus" => urlencode($post['max_reduce_bonus']),
                    ),
                    // "discount" => 10
                )
            )
        );

        return urldecode(json_encode($curl_datas));
    }

    /**
     * 版本1.3
     * @param $uid   商户或者收银员在用户表的id
     * @return mixed   商户的信息
     */
    private function get_merchant1($uid)
    {
        $data = array();
        $role_id = $this->role_users->where("uid='$uid'")->getField('role_id');
        $data['role'] = $role_id;
        if ($role_id == 3) {
            $m_uid = $uid;
            $data['checker'] = 0;
            $data['is_all'] = 1;
        } else {
            $user = $this->users->where("id='$uid'")->find();
            $m_uid = $user['pid'];
            $data['checker'] = $uid;
            $data['is_all'] = $user['is_all'];
        }
        $data['mid'] = $this->merchants->where("uid='$m_uid'")->getField("id");
        return $data;
    }

    //点击支付
    public function pos_pay_order()
    {
        if (IS_POST) {
            file_put_contents('./data/log/test.log', date("Y-m-d H:i:s") . json_encode(I()) . PHP_EOL, FILE_APPEND | LOCK_EX);
            $order_info = array();
            //$order_sn = I("order_sn");//流水号
            $order_sn = I("order_sn") ? I("order_sn") : $this->pos_get_order_sn();
            $order_info["order_sn"] = $order_sn;
            $order_amount = I("order_amount");
            $order_info["order_amount"] = $order_amount;//应收金额
            $order_info["discount"] = I('discount');
            //$order_info["pay_status"]  = I("pay_status");//支付状态为1
            // $order_info["order_status"]  = 5;//订单状态为1
            $order_info["pay_status"] = I('paystyle_id') ? 1 : 0;//支付状态为1
            $order_info["type"] = "3";//3为pos机订单
            $order_info['integral'] = I('dikoufen');//该订单使用积分
            $order_info['integral_money'] = I('dikoujin');//该订单使用积分抵扣金额
            $code = I("coupon_code", "");//优惠券code
            $order_info["coupon_code"] = $code;//优惠券ID
            $order_info["coupon_price"] = I("coupon_price");//使用优惠券抵扣多少金额
            $order_info["order_goods_num"] = I("order_goods_num");//订单总数
            $order_info["total_amount"] = I("total_amount");//订单总价
            $user_id = I('uid') ? I('uid') : $this->userId;
            $order_info["user_id"] = $user_id;//当前使用双屏的用户ID
            $order_info["pay_time"] = I("timestamp");
            $order_info["order_benefit"] = I("order_benefit");//整单优惠金额
            $card_code = I("card_id", "");
            $order_info["card_code"] = $card_code;//会员卡号
            $paystyle_id = I('paystyle_id');
            $order_info["user_money"] = I('user_money');  //使用余额
            $order_info["discount_money"] = I('discount_money');
            $authorization = I('authorization') ? I('authorization') : 0;
            $status = I('status') ? I('status') : 1;
            $role_id = M('merchants_role_users')->where(array('uid' => $user_id))->getField('role_id');
            if ($role_id == '7') {
                $pid = M('merchants_users')->where(array('id' => $user_id))->getField('pid');
                $merchant_id = M('merchants')->where(array('uid' => $pid))->getField('id');
                $checker_id = $this->userId;
            } else {
                $merchant_id = M('merchants')->where(array('uid' => $user_id))->getField('id');
                $checker_id = '0';
            }
            M()->startTrans(); // 开启事务
            $data = M('order');
            $res = $data->add($order_info);
            if ($res) { // 加入订单表
                $order_goods = array();
                $goods = M("order_goods");
                $goods_id = explode(",", I("goods_id"));
                //$bar_code = explode(",",I("bar_code"));
                $goods_num = explode(",", I("goods_num"));
                //$goods_name = explode(",",I("goods_name"));
                //$goods_price = explode(",",I("goods_price"));
                //$discount = explode(",",I("goods_discount"));
                $sku_id = explode(",", I("sku_id"));
                foreach ($goods_id as $key => $val) {
                    $order_goods[$key]['order_id'] = $res;
                    $order_goods[$key]["goods_id"] = $val;
                    //$order_goods[$key]["goods_name"] = $goods_name[$key];
                    //$order_goods[$key]["bar_code"] = $bar_code[$key];
                    $goods_info = $this->_get_goods_info($val);
                    $order_goods[$key]["goods_name"] = $goods_info['goods_name'];
                    $order_goods[$key]["goods_num"] = $goods_num[$key];
                    //$order_goods[$key]["goods_price"] = $goods_price[$key];
                    $order_goods[$key]["goods_price"] = $goods_info['shop_price'];

                    //$order_goods[$key]["discount"] = $discount[$key];
                    //$order_goods[$key]["sku"] = $sku[$key];
                    $order_goods[$key]["sku"] = $this->_get_sku($sku_id[$key]);
                    //M('goods')->where("goods_id=$val")->setDec('goods_number');
                    //M('goods')->where("goods_id=$val")->setInc('sales');
                };
                $result = $goods->addAll($order_goods);
                if ($result && $res) {
                    M()->commit();
                    if ($card_code != '') {
                        $card_info = M('screen_memcard m')
                            ->join('__SCREEN_MEMCARD_USE__ mu on m.card_id=mu.card_id')
                            ->where(array('card_code' => $card_code))
                            ->field('m.expense,m.expense_credits,mu.card_amount,mu.card_id,mu.memid')
                            ->find();
                        $ass = floor($order_amount / $card_info['expense']) * $card_info['expense_credits'];
                        M('screen_memcard_use')->where(array('card_code' => $card_code))->setInc('card_amount', $ass);
                        M('screen_memcard_use')->where(array('card_code' => $card_code))->setInc('card_balance', $ass);
                        M('screen_memcard_use')->where(array('card_code' => $card_code))->setDec('card_balance', I('dikoufen'));
                        //M('memcard_user')->where(array('card_code'=>$card_code))->setInc('card_balance',$ass);
                        $memcard_info = array(
                            "memid" => $card_info['memid'],
                            "status" => 1,
                            "point" => $ass,
                            "card_id" => $card_info['card_id'],
                            "add_time" => time(),
                            "merchants_id" => $this->userId
                        );
                        M('memcard_user')->data($memcard_info)->add();
                    }
                    if ($paystyle_id == 5) {
                        $pay_info = array(
                            "remark" => $order_sn,
                            "mode" => 7,
                            "merchant_id" => $merchant_id,
                            "checker_id" => $checker_id,
                            "paystyle_id" => 5,
                            "price" => $order_amount,
                            "status" => $status,
                            "cate_id" => 1,
                            "authorization" => $authorization,
                            "paytime" => time()
                        );
                        $pay = $this->pays;
                        $pay->add($pay_info);
                        if ($code != '') {
                            //使用优惠券
                            $ab = A("Apiscreen/Twocoupon")->use_card($code);
                            if ($ab['code'] == "success") {
                                M()->commit();
                                $this->ajaxReturn(array("code" => "success", "msg" => "支付成功1111"));
                            } else {
                                M()->rollback();
                                $this->ajaxReturn(array("code" => "error", "msg" => "支付失败", "data" => $ab['data']));
                            }
                        }
                    } else if ($paystyle_id == 3) {
                        $cardtype = I('cardtype', '');
                        $card_rate = '0.00';
                        if (isset($cardtype)) {
                            if ($cardtype == '00' || $cardtype == '03') {
                                $card_rate_name = 'debit_rate';
                                $card_rate = M('merchants_xdl')->where(array('m_id' => $merchant_id))->getField($card_rate_name);
                            } elseif ($cardtype == '01' || $cardtype == '02') {
                                $card_rate_name = 'credit_rate';
                                $card_rate = M('merchants_xdl')->where(array('m_id' => $merchant_id))->getField($card_rate_name);
                            } elseif ($cardtype == '04') {
                                $card_rate = '0.6';
                            }
                        }
                        $pay_info = array(
                            "remark" => $order_sn,
                            "mode" => 9,
                            "merchant_id" => $merchant_id,
                            "checker_id" => $checker_id,
                            "paystyle_id" => 3,
                            "price" => $order_amount,
                            "status" => $status,
                            "cost_rate" => $card_rate,
                            "bank" => $card_rate > 0 ? 11 : null,
                            "cardtype" => $cardtype,
                            "cate_id" => 1,
                            "authorization" => $authorization,
                            "paytime" => time()
                        );
                        $pay = $this->pays;
                        $pay->add($pay_info);
                        if ($code != '') {
                            //使用优惠券
                            $ab = A("Apiscreen/Twocoupon")->use_card($code);
                            if ($ab['code'] == "success") {
                                M()->commit();
                                $this->ajaxReturn(array("code" => "success", "msg" => "支付成功1111"));
                            } else {
                                M()->rollback();
                                $this->ajaxReturn(array("code" => "error", "msg" => "支付失败", "data" => $ab['data']));
                            }
                        }
                    } else {
                        M()->commit();
                        //扫码支付
                        $value = A('Apiscreen/Pay')->two_get_card($user_id, $order_sn, 6);
                        $this->ajaxReturn(array("code" => "success", "data" => $value));
                    }
                    $this->ajaxReturn(array("code" => "success", "msg" => "支付成功2222"));
                } else {
                    M()->rollback();
                    $this->ajaxReturn(array("code" => "error", "msg" => "支付失败", "data" => "网络错误！"));
                }
            } else {
                M()->rollback();
                $this->ajaxReturn(array("code" => "error", "msg" => "失败", "data" => "网络错误！"));
            }
        } else {
            $this->ajaxReturn(array("code" => "error", 'msg' => '参数错误'));
        }
    }

    //无商品点击支付
    public function pos_pay_order_0()
    {
        if (IS_POST) {
            $order_info = array();
            $order_sn = I("order_sn") ? I("order_sn") : $this->pos_get_order_sn();//流水号
            $order_info["order_sn"] = $order_sn;
            $order_amount = I("order_amount");
            $order_info["order_amount"] = $order_amount;//应收金额
            //$order_info["pay_status"]  = 1;//支付状态为1
            $order_info["pay_status"] = I('paystyle_id') ? 1 : 0;//支付状态为1
            $order_info["type"] = "3";//3为pos机订单
            $order_info['integral'] = I('dikoufen');//该订单使用积分
            $order_info['integral_money'] = I('dikoujin');//该订单使用积分抵扣金额
            $order_info['discount'] = I('discount');//该订单会员卡折扣
            $code = I("coupon_code", "");
            //$order_info["coupon_code"]  = $code;//优惠券ID
            strlen($code) == 12 ? $order_info["coupon_code"] = $code : $order_info["coupon_code"] = '';//会员卡号
            $order_info["coupon_price"] = I("coupon_price");//使用优惠券抵扣多少金额
            $order_info["order_goods_num"] = I("order_goods_num");//商品数量为0
            $order_info["total_amount"] = I("total_amount");//订单总价
            $user_id = I('uid') ? I('uid') : $this->userId;
            $order_info["user_id"] = $user_id;//当前使用双屏的用户ID
            $order_info["pay_time"] = I("timestamp");
            $order_info["order_benefit"] = I("order_benefit");//整单优惠金额
            $card_code = I("card_id", "");
            // $order_info["order_status"]  = 5;//订单状态为1
            $authorization = I('authorization') ? I('authorization') : 0;
            $order_info["card_code"] = $card_code;
            strlen($card_code) == 12 ? $order_info["card_code"] = $card_code : $order_info["card_code"] = '';//会员卡号
            $status = I('status') ? I('status') : 1;
            $paystyle_id = I('paystyle_id');
            $order_info["user_money"] = I('user_money');  //使用余额
            $order_info["discount_money"] = I('discount_money');
            $role_id = M('merchants_role_users')->where(array('uid' => $user_id))->getField('role_id');
            if ($role_id == '7') {
                $pid = M('merchants_users')->where(array('id' => $user_id))->getField('pid');
                $merchant_id = M('merchants')->where(array('uid' => $pid))->getField('id');
                $checker_id = $this->userId;
            } else {
                $merchant_id = M('merchants')->where(array('uid' => $user_id))->getField('id');
                $checker_id = '0';
            }
            M()->startTrans(); // 开启事务
            $data = M('order');
            $res = $data->add($order_info);
            if ($res) {
                M()->commit();
                /*if($card_code!=''){
                    $card_info = M('screen_memcard m')
                        ->join('__SCREEN_MEMCARD_USE__ mu on m.card_id=mu.card_id')
                        ->where(array('card_code'=>$card_code))
                        ->field('m.expense,m.expense_credits,mu.card_amount,mu.card_id,mu.memid')
                        ->find();
                    $ass = floor($order_amount/$card_info['expense'])*$card_info['expense_credits'];
                    M('screen_memcard_use')->where(array('card_code'=>$card_code))->setInc('card_amount',$ass);
                    M('screen_memcard_use')->where(array('card_code'=>$card_code))->setInc('card_balance',$ass);
					M('screen_memcard_use')->where(array('card_code'=>$card_code))->setDec('card_balance',I('dikoufen'));
                    //M('memcard_user')->where(array('card_code'=>$card_code))->setInc('card_balance',$ass);
                    $memcard_info=array(
                        "memid"=>$card_info['memid'],
                        "status"=>1,
                        "point"=>$ass,
                        "card_id"=>$card_info['card_id'],
                        "add_time" =>time(),
                        "merchants_id"=>$this->userId
                    );
                    M('memcard_user')->data($memcard_info)->add();
                }*/
                if ($paystyle_id == 5) {
                    $pay_info = array(
                        "remark" => $order_sn,
                        "mode" => 7,
                        "merchant_id" => $merchant_id,
                        "checker_id" => $checker_id,
                        "paystyle_id" => 5,
                        "price" => $order_amount,
                        "status" => $status,
                        "cate_id" => 1,
                        "authorization" => $authorization,
                        "paytime" => time()
                    );
                    $pay = $this->pays;
                    $pay->add($pay_info);
                    if ($code != '') {
                        //使用优惠券
                        $ab = A("Apiscreen/Twocoupon")->use_card($code);
                        if ($ab['code'] == "success") {
                            M()->commit();
                            $this->ajaxReturn(array("code" => "success", "msg" => "支付成功1111"));
                        } else {
                            M()->rollback();
                            $this->ajaxReturn(array("code" => "error", "msg" => "支付失败", "data" => $ab['data']));
                        }
                    }
                } else if ($paystyle_id == 3) {
                    $cardtype = I('cardtype', '');
                    $card_rate = '0.00';
                    if (isset($cardtype)) {
                        if ($cardtype == '00' || $cardtype == '03') {
                            $card_rate_name = 'debit_rate';
                            $card_rate = M('merchants_xdl')->where(array('m_id' => $merchant_id))->getField($card_rate_name);
                        } elseif ($cardtype == '01' || $cardtype == '02') {
                            $card_rate_name = 'credit_rate';
                            $card_rate = M('merchants_xdl')->where(array('m_id' => $merchant_id))->getField($card_rate_name);
                        } elseif ($cardtype == '04') {
                            $card_rate = '0.6';
                        }
                    }
                    $pay_info = array(
                        "remark" => $order_sn,
                        "mode" => 9,
                        "merchant_id" => $merchant_id,
                        "checker_id" => $checker_id,
                        "paystyle_id" => 3,
                        "price" => $order_amount,
                        "status" => $status,
                        "cost_rate" => $card_rate,
                        "bank" => $card_rate > 0 ? 11 : null,
                        "cardtype" => $cardtype,
                        "cate_id" => 1,
                        "authorization" => $authorization,
                        "paytime" => time()
                    );
                    $pay = $this->pays;
                    $pay->add($pay_info);
                    if ($code != '') {
                        //使用优惠券
                        $ab = A("Apiscreen/Twocoupon")->use_card($code);
                        if ($ab['code'] == "success") {
                            M()->commit();
                            $this->ajaxReturn(array("code" => "success", "msg" => "支付成功1111"));
                        } else {
                            M()->rollback();
                            $this->ajaxReturn(array("code" => "error", "msg" => "支付失败", "data" => $ab['data']));
                        }
                    }
                } else {
                    M()->commit();
                    //扫码支付
                    $value = A('Apiscreen/Pay')->two_get_card($user_id, $order_sn, 6);
                    $this->ajaxReturn(array("code" => "success", "data" => $value));
                }
                $this->ajaxReturn(array("code" => "success", "msg" => "支付成功2222"));
            } else {//res
                M()->rollback();
                $this->ajaxReturn(array("code" => "error", "msg" => "失败", "data" => "网络错误！"));
            }
        } else {//post
            $this->ajaxReturn(array("code" => "error", 'msg' => '参数错误'));
        }
    }


    //随机流水号
    private function pos_get_order_sn()
    {
        $order_sn = date('YmdHis') . mt_rand(10000, 99999);
        return $order_sn;
    }

    //通过商品id查询商品信息
    private function _get_goods_info($goods_id)
    {
        $goods_info = M('goods')->field('goods_name,shop_price')->where("goods_id=$goods_id")->find();
        return $goods_info;
    }

    //通过规格id查询规格属性
    private function _get_sku($sku_id)
    {
        if ($sku_id) {
            $data = M('goods_sku')->where("sku_id=$sku_id")->getField('properties');
            return $data;
        } else {
            return null;
        }
    }

    //点击支付
    public function pay_order()
    {
        if (IS_POST) {
            $order_info = array();
            $order_sn = I("order_sn");//流水号
            $order_info["order_sn"] = $order_sn;
            $order_amount = I("order_amount");
            $order_info["discount"] = I('order_discount');
            $order_info["order_amount"] = $order_amount;//应收金额
            $order_info["pay_status"] = I("pay_status");//支付状态为1
            $order_info["type"] = "3";//3为pos机订单
            $order_info['integral'] = I('dikoufen');//该订单使用积分
            $order_info['integral_money'] = I('dikoujin');//该订单使用积分抵扣金额
            $code = I("coupon_code", "");
            $order_info["coupon_code"] = $code;//优惠券ID
            $order_info["coupon_price"] = I("coupon_price");//使用优惠券抵扣多少金额
            $order_info["order_goods_num"] = I("order_goods_num");
            $order_info["total_amount"] = I("total_amount");//订单总价
            $user_id = I('uid') ? I('uid') : $this->userId;
            $order_info["user_id"] = $user_id;//当前使用双屏的用户ID
            $order_info["pay_time"] = I("timestamp");
            $order_info["order_benefit"] = I("order_benefit");//整单优惠金额
            $card_code = I("card_id", "");
            $order_info["card_code"] = $card_code;//会员卡号
            $paystyle_id = I('paystyle_id');
            $role_id = M('merchants_role_users')->where(array('uid' => $user_id))->getField('role_id');
            if ($role_id == '7') {
                $pid = M('merchants_users')->where(array('id' => $user_id))->getField('pid');
                $merchant_id = M('merchants')->where(array('uid' => $pid))->getField('id');
                $checker_id = $this->userId;
            } else {

                $merchant_id = M('merchants')->where(array('uid' => $user_id))->getField('id');
                $checker_id = '0';
            }

            /*$order_info["order_sn"] = date('YmdHis').rand(1000,9999).UID;
            $order_info["goods_num"]  = 4;
            $order_info["goods_price"]  = 32;
            $order_info["total_amount"]  = 30;
            $order_info["user_id"]  = 71;*/
            M()->startTrans(); // 开启事务
            $data = M('order');
            $res = $data->add($order_info);
            //$card_code  = '442743416296';
            //$price  = '12';
            /*if($card_code!==''){
                $card_info = M('screen_memcard m')
                ->join('__SCREEN_MEMCARD_USE__ mu on m.card_id=mu.card_id')
                ->where(array('card_code'=>$card_code))
                ->field('m.expense,m.expense_credits,mu.card_amount,mu.card_id,mu.memid')
                ->find();
                $ass = floor($order_amount/$card_info['expense'])*$card_info['expense_credits'];
                M('screen_memcard_use')->where(array('card_code'=>$card_code))->setInc('card_amount',$ass);
                M('screen_memcard_use')->where(array('card_code'=>$card_code))->setInc('card_balance',$ass);
                //M('memcard_user')->where(array('card_code'=>$card_code))->setInc('card_balance',$ass);
                $memcard_info=array(
                    "memid"=>$card_info['memid'],
                    "status"=>1,
                    "point"=>$ass,
                    "card_id"=>$card_info['card_id'],
                    "add_time" =>time(),
                    "merchants_id"=>$this->userId
                );
                M('memcard_user')->data($memcard_info)->add();
            }*/
            if ($res) { // 加入订单表
                $order_goods = array();
                $goods = M("order_goods");
                $bar_code = explode(",", I("bar_code"));
                $goods_num = explode(",", I("goods_num"));
                $goods_name = explode(",", I("goods_name"));
                $goods_price = explode(",", I("goods_price"));
                $discount = explode(",", I("goods_discount"));
                $sku = explode(",", I("sku"));
                /* $bar_code = "5588585,5668885,11111111";
                 $bar_code = explode(",",$bar_code);
                 $discount = "50,90,10";
                 $discount = explode(",",$discount);
                 $goods_num = "5,3,14";
                 $goods_num = explode(",",$goods_num);*/
                foreach ($bar_code as $key => $val) {
                    $order_goods[$key]['order_id'] = $res;
                    $order_goods[$key]["bar_code"] = $val;
                    $order_goods[$key]["goods_name"] = $goods_name[$key];
                    $order_goods[$key]["goods_num"] = $goods_num[$key];
                    $order_goods[$key]["goods_price"] = $goods_price[$key];
                    $order_goods[$key]["discount"] = $discount[$key];
                    $order_goods[$key]["sku"] = $sku[$key];
                };
                $result = $goods->addAll($order_goods);
                //M('memcard_user');
                if ($result && $res) {
                    M()->commit();
                    if ($card_code != '') {
                        $card_info = M('screen_memcard m')
                            ->join('__SCREEN_MEMCARD_USE__ mu on m.card_id=mu.card_id')
                            ->where(array('card_code' => $card_code))
                            ->field('m.expense,m.expense_credits,mu.card_amount,mu.card_id,mu.memid')
                            ->find();
                        $ass = floor($order_amount / $card_info['expense']) * $card_info['expense_credits'];
                        M('screen_memcard_use')->where(array('card_code' => $card_code))->setInc('card_amount', $ass);
                        M('screen_memcard_use')->where(array('card_code' => $card_code))->setInc('card_balance', $ass);
                        //M('memcard_user')->where(array('card_code'=>$card_code))->setInc('card_balance',$ass);
                        $memcard_info = array(
                            "memid" => $card_info['memid'],
                            "status" => 1,
                            "point" => $ass,
                            "card_id" => $card_info['card_id'],
                            "add_time" => time(),
                            "merchants_id" => $this->userId
                        );
                        M('memcard_user')->data($memcard_info)->add();
                    }
                    if ($paystyle_id == 5) {
                        $pay_info = array(
                            "remark" => $order_sn,
                            "mode" => 7,
                            "merchant_id" => $merchant_id,
                            "checker_id" => $checker_id,
                            "paystyle_id" => 5,
                            "price" => $order_amount,
                            "status" => 1,
                            "cate_id" => 1,
                            "paytime" => time()
                        );
                        $pay = $this->pays;
                        $pay->add($pay_info);
                        if ($code != '') {
                            //使用优惠券
                            $ab = A("Apiscreen/Twocoupon")->use_card($code);
                            if ($ab['code'] == "success") {
                                M()->commit();
                                $this->ajaxReturn(array("code" => "success", "msg" => "支付成功1111"));
                            } else {
                                M()->rollback();
                                $this->ajaxReturn(array("code" => "error", "msg" => "支付失败", "data" => $ab['data']));
                            }
                        }
                    } else {
                        M()->commit();
                        //扫码支付
                        $value = A('Apiscreen/Pay')->two_get_card($user_id, $order_sn, 6);
                        $this->ajaxReturn(array("code" => "success", "data" => $value));
                    }
                    $this->ajaxReturn(array("code" => "success", "msg" => "支付成功1111"));
                } else {
                    M()->rollback();
                    $this->ajaxReturn(array("code" => "error", "msg" => "支付失败", "data" => "网络错误！"));
                }
            } else {
                M()->rollback();
                $this->ajaxReturn(array("code" => "error", "msg" => "失败", "data" => "网络错误！"));
            }
        } else {
            $this->ajaxReturn(array("code" => "error", 'msg' => '参数错误'));
        }
    }


    //商户资料
    public function merchants()
    {
        $user_id = $this->userId;
        //p($user_id);
        $role_id = $this->role_users->where(array('uid' => $user_id))->getField('role_id');
        if ($role_id == 7) {
            $uid = $this->users->where(array('id' => $user_id))->getField('pid');
            $agent_id = $this->users->where(array('id' => $uid))->getField('agent_id');
        } else {
            $uid = $user_id;
            $agent_id = $this->users->where(array('id' => $uid))->getField('agent_id');
        }
        $res = $this->merchants->where(array('uid' => $uid))->field('referrer,merchant_name,id,industry,if(account_type=0,"个人","企业")account_type,province,city,county,address')->find();
        //p($res);
        $agent_name = $this->agent->where(array('uid' => $agent_id))->getField('agent_name');
        $res['agent_name'] = $agent_name;
        //p($res);
        if ($res) $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $res));
        else $this->ajaxReturn(array("code" => "error", "msg" => "失败"));
    }

    /**
     *app更新
     */
    public function app_update()
    {
        $client = I('post.client');
        $appModel = M('app_version');
        $client = isset($client) && $client == 'ios' ? 2 : 1;
        $info = $appModel->where(array("client" => $client))->order('id desc')->find();
        $info['change_log'] = $client == 1 ? strip_tags(htmlspecialchars_decode($info['change_log'])) : htmlspecialchars_decode($info['change_log']);

        $version = array(
            "versionCode" => $info['version_code'],
            "change_log" => $info['change_log'],
            "versionName" => $info['version_name'],
            "app_name" => $info['app_name'],
            "apk_url" => $info['apk_url']
        );
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $version));
    }

    //退出登录
    public function logout()
    {
        if ($this->userId) {
            M("post_token")->where(array("token" => $this->token))->delete();
            $this->ajaxReturn(array("code" => "success", "msg" => "成功"));
        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => "还未登陆"));
        }

    }

    //点击筛选查询当前商户的收银员
    public function shouyin()
    {
        $user_id = $this->userId;
        $role_id = $this->role_users->where(array('uid' => $user_id))->getField('role_id');
        if ($role_id == 7) {
            $uid = $this->users->where(array('id' => $user_id))->getField('pid');
        } else {
            $uid = $user_id;
        }
        $shouyin = $this->users->where(array('pid' => $uid))->field('id,user_name')->select();
        if ($shouyin) $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $shouyin));
        else$this->ajaxReturn(array("code" => "error", "msg" => "失败", "data" => ""));
    }

    public function chaxun()
    {
        $dasd = '
                今天
                select * from 表名 where to_days(时间字段名) = to_days(now());
                
                昨天
                SELECT * FROM 表名 WHERE TO_DAYS( NOW( ) ) - TO_DAYS( 时间字段名) <= 1
                
                近7天               
                SELECT * FROM 表名 where DATE_SUB(CURDATE(), INTERVAL 7 DAY) <= date(时间字段名)
                
                近30天               
                SELECT * FROM 表名 where DATE_SUB(CURDATE(), INTERVAL 30 DAY) <= date(时间字段名)
                
                本月               
                SELECT * FROM 表名 WHERE DATE_FORMAT( 时间字段名, \'%Y%m\' ) = DATE_FORMAT( CURDATE( ) , \'%Y%m\' )
                select name,submittime from enterprise   where date_format(submittime,\'%Y-%m\')=date_format(now(),\'%Y-%m\')
                
                上一月                
                SELECT * FROM 表名 WHERE PERIOD_DIFF( date_format( now( ) , \'%Y%m\' ) , date_format( 时间字段名, \'%Y%m\' ) ) =1
                
                查询本季度数据               
                select * from `ht_invoice_information` where QUARTER(create_date)=QUARTER(now());
                
                查询上季度数据                
                select * from `ht_invoice_information` where QUARTER(create_date)=QUARTER(DATE_SUB(now(),interval 1 QUARTER));';
    }

    //验证会员卡号
    public function check_card_id()
    {

        $card_code = I("card_code");
        //$card_code = '426064753830';
        /* $user_id = $this->userId;
             $role_id = M('merchants_role_users')->where(array('uid'=>$user_id))->getField('role_id');
             if($role_id == 7){
                 $uid = M('merchants_users')->where(array('id'=>$user_id))->getField('pid');
             }else{
                 $uid = $user_id;
             }*/
        if ($card_code) {
            $res = M('screen_memcard_use')->where(array('card_code' => $card_code))->field('card_id,card_balance')->find();
            //p($res);
            $card_id = $res['card_id'];
            //p($res);
            if ($res) {
                $ress = M('screen_memcard')->where(array('card_id' => $card_id))->field('credits_use,credits_discount')->find();
                $dikoujin = $res['card_balance'] / $ress['credits_use'] * $ress['credits_discount'];
                // $dikoujin=$order_amount<$credits?$order_amount:$credits;
                //$dikoufen=$dikoujin/$ress['credits_discount']*$ress['credits_use'];
                $data = array();
                $data['huiyuankaID'] = $card_id;
                $data['card_code'] = $card_code;
                $data['dikoujin'] = $dikoujin;
                //p($data);
                $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $data));
            } else {
                $this->ajaxReturn(array("code" => "error", "msg" => "失败", "data" => "该会员卡无效！"));
            }
        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => "失败", "data" => "当前没有输入会员卡"));
        }

    }

    //验证会员卡积分
    public function check_jifen()
    {
        $card_id = I("huiyuankaID");
        $card_code = I("card_code");
        $dikoujin = I("dikoujin");
        $res = M('screen_memcard_use')->where(array('card_code' => $card_code))->field('card_id,card_balance')->find();
        $ress = M('screen_memcard')->where(array('card_id' => $card_id))->field('credits_use,credits_discount')->find();
        $dikoufen = $dikoujin / $ress['credits_discount'] * $ress['credits_use'];
        if ($dikoufen <= $res['card_balance']) {
            $this->ajaxReturn(array("code" => "success", "msg" => "成功"));
        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => "失败", "data" => "该会员卡积分不足！"));
        }
    }

    //    收银员流水
    public function customer_coin()
    {
        $this->checkLogin();
        $user_id = $this->userId;
        $role_id = $this->role_users->where(array('uid' => $user_id))->getField('role_id');

        $type = I("type");
        $paystyle = I("paystyle");
        $status = I("status");
        $mode = I("mode");
        $time = $this->type_time($type);
        if ($role_id == 7) {
            $pays = $this->customer_detail($user_id, $time, $paystyle, $status, $mode);
        } else {
            $pays = $this->merchant_detail($user_id, $time, $paystyle, $status, $mode);
        }
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $pays));
    }

    /**
     * @param $id   商户的id
     * @param $time  时间区间
     * return 返回商户所有的流水
     */
    public function merchant_detail($id, $time, $paystyle = "", $status = "", $mode = "")
    {
        $map['u.id'] = $id;
        if ($time != null) $map['p.paytime'] = array("between", $time);
        if ($paystyle !== "0") $map['paystyle_id'] = $paystyle;
        if ($status !== "") $map['p.status'] = $status;
        if ($mode !== "") {
            if ($mode == "1") {
                $map['p.mode'] = array('in', '1,2');
            } elseif ($mode == "3") {
                $map['p.mode'] = array('in', '3,4');
            } else {
                $map['p.mode'] = $mode;
            }
        }

        $pays = M('merchants_users')->alias("u")
            ->join("__MERCHANTS__ m on m.uid=u.id")
            ->join("__PAY__ p on p.merchant_id=m.id")
            ->order("paytime desc")
            ->where($map)
            ->field("p.paystyle_id,p.status,p.paytime,p.price,p.price_back,p.back_status")
            ->select();
//        return M('merchants_users')->getLastSql();
        return $pays;
    }

//    判断优惠券和会员卡
    public function cou_to_men()
    {
        $code = I("code");
        if (M("screen_user_coupons")->where(array("usercard" => $code, "status" => 1))->find()) $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array('case' => "优惠券")));
        if (M("screen_memcard_use")->where(array("card_code" => $code, "status" => 1))->find()) $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array('case' => "会员卡")));
        $this->ajaxReturn(array("code" => "error", "msg" => "EOF"));
    }

    /**
     * @param $id   收银员的id
     * @param $time  时间区间
     * return 返回收银员所有的流水
     */
    public function customer_detail($id, $time, $paystyle = "", $status = "", $mode = "")
    {
        $map['u.id'] = $id;
        if ($time != null) $map['p.paytime'] = array("between", $time);
        if ($paystyle !== "0") $map['paystyle_id'] = $paystyle;
        if ($status !== "") $map['p.status'] = $status;
        if ($mode !== "") {
            if ($mode == "1") {
                $map['p.mode'] = array('in', '1,2');
            } elseif ($mode == "3") {
                $map['p.mode'] = array('in', '3,4');
            } else {
                $map['p.mode'] = $mode;
            }
        }

        $pays = M('merchants_users')->alias("u")
            ->join("__PAY__ p on p.checker_id = u.id")
            ->where($map)
            ->field("p.paystyle_id,p.status,p.paytime,p.price,p.price_back")
            ->select();
        return $pays;
    }

    /**
     * @param $type   支付的类型;
     * @return int    0: 所有1 :今日 2:昨日 3:本周 4:本月 5:上周 6:上月
     */
    function type_time($type)
    {
        switch ($type) {
            case 0:
                return;
            case 1:
                //  今天
                $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
                $endToday = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
                return array($beginToday, $endToday);
            case 2:
                //昨天
                $beginYesterday = mktime(0, 0, 0, date('m'), date('d') - 1, date('Y'));
                $endYesterday = mktime(0, 0, 0, date('m'), date('d'), date('Y')) - 1;
                return array($beginYesterday, $endYesterday);
            case 3:
                //        本周
                $beginThisweek = mktime(0, 0, 0, date('m'), date('d') - date('w') + 1, date('Y'));
                $endToday = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;

                //                $endThisweek=mktime(23,59,59,date('m'),date('d')-date('w')+7,date('Y'));
                return array($beginThisweek, $endToday);
            case 4:
                //        本月
                $beginThismonth = mktime(0, 0, 0, date('m'), 1, date('Y'));
                $endToday = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;

                //                $endThismonth = mktime(23,59,59,date('m'),date('t'),date('Y'));
                return array($beginThismonth, $endToday);
            case 5:
                //上周
                $beginLastweek = mktime(0, 0, 0, date('m'), date('d') - date('w') + 1 - 7, date('Y'));
                $endLastweek = mktime(23, 59, 59, date('m'), date('d') - date('w') + 7 - 7, date('Y'));
                return array($beginLastweek, $endLastweek);
            case 6:
                //上月
                $beginLastmonth = mktime(0, 0, 0, date("m") - 1, 1, date("Y"));
                $endLastmonth = mktime(23, 59, 59, date("m"), 0, date("Y"));
                return array($beginLastmonth, $endLastmonth);
        }
    }

    //卡券核销
    public function card_coupon_check()
    {
        $code = I("code");  //卡号
        $price = I("price");    //订单总价
        $mch_uid = $this->get_merchant_uid($this->userId);  //商户uid
        $now = time();
        //优惠券
        if ($data = M("screen_user_coupons")->where(array("usercard" => $code, "status" => 1))->find()) {
            $uid = $this->userId;
            //是会员
            if ($memeber = M('screen_mem')->where(array('userid' => $uid, 'status' => 1, 'openid' => $data['fromname'], 'unionid' => $data['unionid']))->find()) {
                $map = array('u.memid' => $memeber['id'],);

                $res = M('screen_memcard')->alias('m')
                    ->join('join ypt_screen_memcard_use u on m.id=u.memcard_id')
                    ->where($map)
                    ->field('u.card_amount,u.memid,u.yue,u.card_id,u.card_balance,u.card_code,m.id,m.max_reduce_bonus,m.credits_set,m.integral_dikou,m.max_reduce_bonus,m.credits_use,m.credits_discount,m.discount_set,m.discount,m.mid,m.level_set')
                    ->find();
                // dump($res);
                // dump(M()->getLastSql());die;
                // $where = array("" => $memeber['userid'])
                //不是本店会员卡时
                if ($res['mid'] != $mch_uid) {
                    $where = array("c.id" => $data['coupon_id']);
                    $result = M('screen_coupons')->alias('c')
                        ->join('join ypt_merchants m on m.id=c.mid')
                        ->join('join ypt_merchants_users mu on mu.id=m.uid')
                        ->where($where)
                        ->field('c.total_price,c.de_price,c.status,c.begin_timestamp,c.end_timestamp,mu.id')
                        ->find();
                    if ($result['id'] != $mch_uid) {
                        $this->ajaxReturn(array("code" => "error", "msg" => "该优惠券不是本店优惠券"));
                    }
                    if ($result['total_price'] > $price) {
                        $this->ajaxReturn(array("code" => "error", "msg" => "消费金额未达到优惠券需求金额！"));
                    }
                    if ($result['status'] == 5) {
                        $this->ajaxReturn(array("code" => "error", "msg" => "该优惠券已失效"));
                    }
                    if ($now < $result['begin_timestamp'] || $now > $result['end_timestamp']) $this->ajaxReturn(array("code" => "error", "msg" => "该优惠券不在使用时间范围"));
                    if ($result['de_price'] > $price) {
                        $this->ajaxReturn(array("code" => "error", "msg" => "消费金额未达到优惠券使用金额！"));
                    }
                    $result['memid'] = M('screen_mem')->where("unionid='$data[unionid]' and userid=$mch_uid")->getField('id');
                    $result['code'] = $code;
                    $result = array(
                        'coupon_code' => $code,
                        'coupon_price' => $result['de_price'],
                        'memid' => $result['memid'],
                        'total_de_price' => $result['de_price'],
                        'code_type' => '1'

                    );
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功111", "data" => $result));
                }
                //1折扣
                $da = array();
                //等级设置开启
                if ($res['level_set'] == '1') {
                    $da['discount'] = M('screen_memcard_level')->where("c_id=$res[id] and level_integral<=$res[card_amount]")->order('level desc')->getField('level_discount') * 0.1;
                    //折扣设置未开
                } elseif ($res['discount_set'] == 0 || $res['discount'] == 0 || !$res['discount']) {
                    $da['discount'] = '1';
                    //折扣设置开启
                } else {
                    $da['discount'] = $res['discount'] * 0.1;
                }
                $new_price = $price * $da['discount'];  //折扣后金额
                $discount_price = $price - $new_price;    //折扣金额
                //2优惠券
                $w = array("c.id" => $data['coupon_id']);
                $coupon = M('screen_coupons')->alias('c')
                    ->join('join ypt_merchants m on m.id=c.mid')
                    ->join('join ypt_merchants_users mu on mu.id=m.uid')
                    ->where($w)
                    ->field('c.total_price,c.de_price,c.status,c.begin_timestamp,c.end_timestamp,mu.id')
                    ->find();
                // dump($coupon);dump($new_price);
                if ($coupon['id'] != $mch_uid) {
                    $this->ajaxReturn(array("code" => "error", "msg" => "该优惠券不是本店优惠券"));
                }
                if ($coupon['total_price'] > $new_price) {
                    $this->ajaxReturn(array("code" => "error", "msg" => "消费金额未达到优惠券需求金额！"));
                }
                if ($coupon['status'] == 5) {
                    $this->ajaxReturn(array("code" => "error", "msg" => "该优惠券已失效"));
                }
                if ($now < $coupon['begin_timestamp'] || $now > $coupon['end_timestamp']) $this->ajaxReturn(array("code" => "error", "msg" => "该优惠券不在使用时间范围"));
                if ($coupon['de_price'] > $new_price) {
                    $this->ajaxReturn(array("code" => "error", "msg" => "消费金额未达到优惠券使用金额！"));
                }
                $coupon['memid'] = M('screen_mem')->where("unionid='$data[unionid]' and userid=$mch_uid")->getField('id');  //会员id
                $coupon['code'] = $code;    //优惠券code
                $coupon_price = $coupon['de_price'];    // 优惠券抵扣金额
                $new_price2 = $new_price - $coupon_price;  //使用优惠券后金额
                //3算积分
                // dump($res);die;
                if ($res['integral_dikou'] == 0) {      //积分开关
                    $value = array('card_de_price' => '0', 'jifen_use' => '0');     //抵扣分 抵扣金
                } else {
                    if ($res['card_balance'] < $res['max_reduce_bonus']) {
                        $p = floor($res['card_balance'] / $res['credits_use']) * $res['credits_discount'];
                    } else {
                        $p = floor($res['max_reduce_bonus'] / $res['credits_use']) * $res['credits_discount'];
                    }
                    //抵扣金额小于使用金额
                    if ($p < $new_price2) {
                        $value['card_de_price'] = "$p"; //积分抵扣金额
                        $value['jifen_use'] = $res['card_balance']; //使用积分
                    } else {
                        $value['card_de_price'] = "$new_price2";//积分抵扣金额
                        $value['jifen_use'] = floor("$new_price2" / $res['credits_discount']) * $res['credits_use'];//使用积分
                    }
                }
                if ($res && $coupon) {
                    $result = array(
                        'card_code' => strval($res['card_code']),
                        'dikoufen' => strval($value['jifen_use']),
                        'dikoujin' => strval($value['card_de_price']),
                        'coupon_code' => strval($data['usercard']),
                        'coupon_price' => strval($coupon['de_price']),
                        'discount' => strval($da['discount']),
                        'discount_price' => strval($discount_price),
                        'total_de_price' => strval($value['card_de_price'] + $coupon['de_price'] + $discount_price),
                        'yue' => strval($res['yue'])
                    );
                } else {
                    $result = array(
                        'card_code' => strval($res['card_code']),
                        'dikoufen' => strval($value['jifen_use']),
                        'dikoujin' => strval($value['card_de_price']),
                        'coupon_code' => '',
                        'coupon_price' => '',
                        'discount' => strval($da['discount']),
                        'discount_price' => strval($discount_price),
                        'total_de_price' => strval($value['card_de_price'] + $discount_price),
                        'yue' => '0'
                    );
                }
                $result['memid'] = $res['memid'];
                $result['code_type'] = '1';
                $this->ajaxReturn(array("code" => "success", "msg" => "成功222", "data" => $result));
            }
            $map = array("c.id" => $data['coupon_id']);
            $res = M('screen_coupons')->alias('c')
                ->join('join ypt_merchants m on m.id=c.mid')
                ->join('join ypt_merchants_users mu on mu.id=m.uid')
                ->where($map)
                ->field('c.total_price,c.de_price,c.status,c.begin_timestamp,c.end_timestamp,mu.id')
                ->find();
            if ($res['id'] != $mch_uid) {
                $this->ajaxReturn(array("code" => "error", "msg" => "该优惠券不是本店优惠券"));
            }
            if ($res['total_price'] > $price) {
                $this->ajaxReturn(array("code" => "error", "msg" => "消费金额未达到优惠券需求金额！"));
            }
            if ($res['status'] == 5) {
                $this->ajaxReturn(array("code" => "error", "msg" => "该优惠券已失效"));
            }
            if ($now < $res['begin_timestamp'] || $now > $res['end_timestamp']) $this->ajaxReturn(array("code" => "error", "msg" => "该优惠券不在使用时间范围"));
            if ($coupon['de_price'] > $price) {
                $this->ajaxReturn(array("code" => "error", "msg" => "消费金额未达到优惠券使用金额！"));
            }
            $res['memid'] = M('screen_mem')->where("unionid='$data[unionid]' and userid=$mch_uid")->getField('id');
            $res['code'] = $code;
            $result = array(
                'coupon_code' => $code,
                'coupon_price' => $res['de_price'],
                'memid' => $res['memid'],
                'total_de_price' => $res['de_price'],
                'code_type' => '1'

            );
            $this->ajaxReturn(array("code" => "success", "msg" => "成功333", "data" => $result));
            //会员卡
        } elseif ($d = M("screen_memcard_use")->where(array("card_code" => $code, "status" => 1))->find()) {
            $map = array("u.card_code" => $code);

            $res = M('screen_memcard')->alias('m')
                ->join('join ypt_screen_memcard_use u on m.id=u.memcard_id')
                ->where($map)
                ->field('u.card_amount,u.memid,u.yue,u.card_id,u.card_balance,m.id,m.max_reduce_bonus,m.credits_set,m.integral_dikou,m.max_reduce_bonus,m.credits_use,m.credits_discount,m.discount_set,m.discount,m.mid,m.level_set')
                ->find();
            if ($res['mid'] != $mch_uid) {
                $this->ajaxReturn(array("code" => "error", "msg" => "该会员卡不是本店会员卡2222"));
            }
            //1算折扣
            if ($res['level_set'] == '1') {
                $d['discount'] = M('screen_memcard_level')->where("c_id=$res[id] and level_integral<=$res[card_amount]")->order('level desc')->getField('level_discount') * 0.1;
            } elseif ($res['discount_set'] == 0 || $res['discount'] == 0 || !$res['discount']) {
                $d['discount'] = '1';
            } else {
                $d['discount'] = $res['discount'] * 0.1;
            }
            $new_price = $price * $d['discount'];
            //dump($new_price);
            $discount_price = $price - $new_price;
            //2算优惠券
            $where = array('m.uid' => $mch_uid, 'mem.id' => $res['memid'], 'uc.status' => '1', 'c.total_price' => array('ELT', $new_price));
            $coupon = M('screen_user_coupons')->alias('uc')
                ->join('join ypt_screen_coupons c on uc.coupon_id=c.id')
                ->join('join ypt_screen_mem mem on mem.unionid=uc.unionid')
                ->join('left join ypt_merchants m on m.id=c.mid')
                ->field('c.de_price,uc.usercard')
                ->where($where)
                ->order('c.de_price DESC')
                ->find();
            if ($coupon) {
                $new_price2 = $new_price - $coupon['de_price'];
            } else {
                $new_price2 = $new_price;
            }
            //dump($new_price2);
            //3算积分
            if ($res['integral_dikou'] == 0) {
                $data = array('card_de_price' => '0', 'jifen_use' => '0');
            } else {
                if ($res['card_balance'] < $res['max_reduce_bonus']) {
                    $p = floor($res['card_balance'] / $res['credits_use']) * $res['credits_discount'];
                } else {
                    $p = floor($res['max_reduce_bonus'] / $res['credits_use']) * $res['credits_discount'];
                }
                if ($p < $new_price2) {
                    $data['card_de_price'] = "$p";
                    $data['jifen_use'] = $res['card_balance'];
                } else {
                    $data['card_de_price'] = "$new_price2";
                    $data['jifen_use'] = floor("$new_price2" / $res['credits_discount']) * $res['credits_use'];
                }
            }
            //dump($data['card_de_price']);
            if ($res && $coupon) {
                $result = array(
                    'card_code' => strval($code),
                    'dikoufen' => strval($data['jifen_use']),
                    'dikoujin' => strval($data['card_de_price']),
                    'coupon_code' => strval($coupon['usercard']),
                    'coupon_price' => strval($coupon['de_price']),
                    'discount' => strval($d['discount']),
                    'discount_price' => strval($discount_price),
                    'total_de_price' => strval($data['card_de_price'] + $coupon['de_price'] + $discount_price),
                    'yue' => strval($res['yue'])
                );
            } else {
                $result = array(
                    'card_code' => strval($code),
                    'dikoufen' => strval($data['jifen_use']),
                    'dikoujin' => strval($data['card_de_price']),
                    'coupon_code' => '',
                    'coupon_price' => '',
                    'discount' => strval($d['discount']),
                    'discount_price' => strval($discount_price),
                    'total_de_price' => strval($data['card_de_price'] + $discount_price),
                    'yue' => '0'
                );
            }
            $result['memid'] = $res['memid'];
            $result['code_type'] = '2';
            $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $result));
        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => "无效卡号"));
        }
    }

    //点击支付
    public function pos_pay_order_new()
    {
        if (IS_POST) {
            //获取数据
            $order_info = array();
            $order_sn = I("order_sn") ? I("order_sn") : $this->pos_get_order_sn();//订单编号
            $order_info["order_sn"] = $order_sn;
            $order_amount = I("order_amount");
            $order_info["order_amount"] = $order_amount;//应收金额
            $order_info["pay_status"] = 1;//支付状态为1
            $order_info["type"] = "3";//3为pos机订单
            $dikoufen = I('dikoufen'); //订单使用积分
            $order_info['integral'] = $dikoufen;//该订单使用积分
            $order_info['integral_money'] = I('dikoujin');//该订单使用积分抵扣金额
            $code = I("coupon_code", "");//优惠券code
            $order_info["coupon_code"] = $code;//优惠券ID
            $order_info["coupon_price"] = I("coupon_price");//使用优惠券抵扣多少金额
            $order_info["order_goods_num"] = I("order_goods_num");//订单总数
            $order_info["total_amount"] = I("total_amount");//订单总价
            $user_id = I('uid') ? I('uid') : $this->userId;
            $order_info["user_id"] = $user_id;//当前使用双屏的用户ID
            $order_info["pay_time"] = I("timestamp"); //支付时间
            $card_code = I("card_id", "");  //会员卡号
            $order_info["card_code"] = $card_code;//会员卡号
            $paystyle_id = I('paystyle_id');  //支付方式
            $user_money = I('user_money');  //使用余额
            $order_info["paystyle"] = $paystyle_id;
            $order_info["user_money"] = $user_money;
            //查找用户的角色
            $role_id = M('merchants_role_users')->where(array('uid' => $user_id))->getField('role_id');
            //获取检验员
            if ($role_id == '7') {
                $pid = M('merchants_users')->where(array('id' => $user_id))->getField('pid');
                $merchant_id = M('merchants')->where(array('uid' => $pid))->getField('id');
                $checker_id = $this->userId;
            } else {
                $merchant_id = M('merchants')->where(array('uid' => $user_id))->getField('id');
                $checker_id = '0';
            }
            M()->startTrans(); // 开务启事
            //添加至订单表
            $data = M('order');
            $res = $data->add($order_info);
            if ($res) {
                // 加入订单商品表
                $order_goods = array();
                $goods = M("order_goods");
                $goods_id = explode(",", I("goods_id"));
                $goods_num = explode(",", I("goods_num"));
                $sku_id = explode(",", I("sku_id"));
                foreach ($goods_id as $key => $va) {
                    $order_goods[$key]['order_id'] = $res;
                    $order_goods[$key]["goods_id"] = $va;
                    $goods_info = $this->_get_goods_info($va);
                    $order_goods[$key]["goods_name"] = $goods_info['goods_name'];
                    $order_goods[$key]["goods_num"] = $goods_num[$key];
                    $order_goods[$key]["goods_price"] = $goods_info['shop_price'];
                    $order_goods[$key]["sku"] = $this->_get_sku($sku_id[$key]);
                };
                $result = $goods->addAll($order_goods);
                if ($result && $res) {
                    M()->commit();
                    //有会员
                    if ($card_code) {
                        $card = M("screen_memcard_use")->alias('u')
                            ->join('left join ypt_screen_memcard m on u.card_id=m.card_id')
                            ->field('m.id,m.credits_set,m.expense,m.expense_credits,m.expense_credits_max,u.card_balance,u.yue,u.card_id,u.card_amount')
                            ->where("u.card_code='$card_code'")
                            ->find();

                        //会员卡消费送积分
                        if ($card['credits_set'] == 1) {
                            $send = floor($order_amount / $card['expense']) * $card['expense_credits'];
                            //如果送的积分大于最多可送的分
                            if ($send > $card['expense_credits_max']) {
                                $send = $card['expense_credits_max'];
                            }
                        }
                        if ($dikoufen) {
                            $val['card_balance'] = $card['card_balance'] - $dikoufen + $send;
                        } else {
                            $val['card_balance'] = $card['card_balance'] + $send;
                        }
                        if ($user_money) {
                            $val['yue'] = $card['yue'] - $user_money;
                        }
                        $card_off = M("screen_memcard_use")->where("card_code='$card_code'")->save($val);
                        $ts['code'] = urlencode($card_code);
                        $ts['card_id'] = urlencode($card['card_id']);
                        $ts['custom_field_value1'] = urlencode($card['yue'] - $user_money);//会员卡余额
                        $ts['custom_field_value2'] = urlencode(M('screen_memcard_level')->where("c_id=$card[id] and level_integral<=$card[card_amount]")->order('level desc')->getField('level_name'));//会员卡名称
                        $ts["add_bonus"] = urlencode($send - $dikoufen);//会员卡积分
                        $token = get_weixin_token();
                        file_put_contents('./data/log/testcoupon.log', date("Y-m-d H:i:s") . json_encode($ts) . PHP_EOL, FILE_APPEND | LOCK_EX);
                        request_post('https://api.weixin.qq.com/card/membercard/updateuser?access_token=' . $token, urldecode(json_encode($ts)));
                    }

                    //选取支付方式
                    if ($paystyle_id == 5) {
                        //现金支付
                        $pay_info = array(
                            "remark" => $order_sn,
                            "mode" => 7,
                            "merchant_id" => $merchant_id,
                            "checker_id" => $checker_id,
                            "paystyle_id" => 5,
                            "price" => $order_amount,
                            "status" => 1,
                            "cate_id" => 1,
                            "la_ka_la" => 1,
                            "paytime" => time()
                        );
                        $time = time();
                        $pay = $this->pays;
                        $pay->query("INSERT INTO ypt_pay(remark,mode,merchant_id,checker_id,paystyle_id,price,status,cate_id,paytime,la_ka_la) VALUES($order_sn,'7',$merchant_id,$checker_id,'5',$order_amount,'1','1',$time,'1')");
                        //核销优惠券
                        if ($code) {
                            $url = "https://api.weixin.qq.com/card/code/consume?access_token=" . get_weixin_token();
                            $c['code'] = $code;
                            $use_coupon = request_post($url, json_encode($c));
                            $result = json_decode($use_coupon, true);
                            M("screen_user_coupons")->where("usercard=$code")->setField('status', '0');
                            file_put_contents('./data/log/wz/weixin/coupon.log', date("Y-m-d H:i:s") . '用户使用优惠券' . json_encode($use_coupon) . PHP_EOL, FILE_APPEND | LOCK_EX);
                            if ($result['errmsg'] != "ok") {
                                $coupon_off = false;
                                file_put_contents('./data/log/wz/weixin/coupon.log', date("Y-m-d H:i:s") . '用户使用优惠券失败' . json_encode($use_coupon) . PHP_EOL, FILE_APPEND | LOCK_EX);
                            } else if ($result['errmsg'] == "ok") {
                                $coupon_off = true;
                            }
                            if ($coupon_off) {
                                M()->commit();
                                $this->ajaxReturn(array("code" => "success", "msg" => "支付成功1111"));
                            } else {
                                M()->rollback();
                                $this->ajaxReturn(array("code" => "error", "msg" => "支付失败"));
                            }
                        }
                    } else if ($paystyle_id == 3) {
                        //信用卡支付
                        $pay_info = array(
                            "remark" => $order_sn,
                            "mode" => 9,
                            "merchant_id" => $merchant_id,
                            "checker_id" => $checker_id,
                            "paystyle_id" => 3,
                            "price" => $order_amount,
                            "status" => 1,
                            "cate_id" => 1,
                            "la_ka_la" => 1,
                            "paytime" => time()
                        );
                        $time = time();
                        $pay = $this->pays;
                        $pay->query("INSERT INTO ypt_pay(remark,mode,merchant_id,checker_id,paystyle_id,price,status,cate_id,paytime,la_ka_la) VALUES($order_sn,'9',$merchant_id,$checker_id,'3',$order_amount,'1','1',$time,'1')");
                        //核销优惠券
                        if ($code) {
                            $url = "https://api.weixin.qq.com/card/code/consume?access_token=" . get_weixin_token();
                            $c['code'] = $code;
                            $use_coupon = request_post($url, json_encode($c));
                            $result = json_decode($use_coupon, true);
                            M("screen_user_coupons")->where("usercard=$code")->setField('status', '0');
                            file_put_contents('./data/log/wz/weixin/coupon.log', date("Y-m-d H:i:s") . '用户使用优惠券' . json_encode($use_coupon) . PHP_EOL, FILE_APPEND | LOCK_EX);
                            if ($result['errmsg'] != "ok") {
                                $coupon_off = false;
                                file_put_contents('./data/log/wz/weixin/coupon.log', date("Y-m-d H:i:s") . '用户使用优惠券失败' . json_encode($use_coupon) . PHP_EOL, FILE_APPEND | LOCK_EX);
                            } else if ($result['errmsg'] == "ok") {
                                $coupon_off = true;
                            }
                            if ($coupon_off) {
                                M()->commit();
                                $this->ajaxReturn(array("code" => "success", "msg" => "支付成功11"));
                            } else {
                                M()->rollback();
                                $this->ajaxReturn(array("code" => "error", "msg" => "支付失败"));
                            }
                        }
                    } else if ($paystyle_id == 1) {
                        //微信支付
                        $pay_info = array(
                            "remark" => $order_sn,
                            "mode" => 5,
                            "merchant_id" => $merchant_id,
                            "checker_id" => $checker_id,
                            "paystyle_id" => 1,
                            "price" => $order_amount,
                            "status" => 1,
                            "cate_id" => 1,
                            "la_ka_la" => 1,
                            "paytime" => time()
                        );
                        $time = time();
                        $pay = $this->pays;
                        $pay->query("INSERT INTO ypt_pay(remark,mode,merchant_id,checker_id,paystyle_id,price,status,cate_id,paytime,la_ka_la) VALUES($order_sn,'5',$merchant_id,$checker_id,'1',$order_amount,'1','1',$time,'1')");
                        //核销优惠券
                        if ($code) {
                            $url = "https://api.weixin.qq.com/card/code/consume?access_token=" . get_weixin_token();
                            $c['code'] = $code;
                            $use_coupon = request_post($url, json_encode($c));
                            $result = json_decode($use_coupon, true);
                            M("screen_user_coupons")->where("usercard=$code")->setField('status', '0');
                            file_put_contents('./data/log/wz/weixin/coupon.log', date("Y-m-d H:i:s") . '用户使用优惠券' . json_encode($use_coupon) . PHP_EOL, FILE_APPEND | LOCK_EX);
                            if ($result['errmsg'] != "ok") {
                                $coupon_off = false;
                                file_put_contents('./data/log/wz/weixin/coupon.log', date("Y-m-d H:i:s") . '用户使用优惠券失败' . json_encode($use_coupon) . PHP_EOL, FILE_APPEND | LOCK_EX);
                            } else if ($result['errmsg'] == "ok") {
                                $coupon_off = true;
                            }
                            if ($coupon_off) {
                                M()->commit();
                                $this->ajaxReturn(array("code" => "success", "msg" => "支付成功111"));
                            } else {
                                M()->rollback();
                                $this->ajaxReturn(array("code" => "error", "msg" => "支付失败"));
                            }
                        }
                    } else {
                        //支付宝支付
                        $pay_info = array(
                            "remark" => $order_sn,
                            "mode" => 5,
                            "merchant_id" => $merchant_id,
                            "checker_id" => $checker_id,
                            "paystyle_id" => 2,
                            "price" => $order_amount,
                            "status" => 1,
                            "cate_id" => 1,
                            "la_ka_la" => 1,
                            "paytime" => time()
                        );
                        $time = time();
                        $pay = $this->pays;
                        $pay->query("INSERT INTO ypt_pay(remark,mode,merchant_id,checker_id,paystyle_id,price,status,cate_id,paytime,la_ka_la) VALUES($order_sn,'5',$merchant_id,$checker_id,'2',$order_amount,'1','1',$time,'1')");
                        //核销优惠券
                        if ($code) {
                            $url = "https://api.weixin.qq.com/card/code/consume?access_token=" . get_weixin_token();
                            $c['code'] = $code;
                            $use_coupon = request_post($url, json_encode($c));
                            $result = json_decode($use_coupon, true);
                            M("screen_user_coupons")->where("usercard=$code")->setField('status', '0');
                            file_put_contents('./data/log/wz/weixin/coupon.log', date("Y-m-d H:i:s") . '用户使用优惠券' . json_encode($use_coupon) . PHP_EOL, FILE_APPEND | LOCK_EX);
                            if ($result['errmsg'] != "ok") {
                                $coupon_off = false;
                                file_put_contents('./data/log/wz/weixin/coupon.log', date("Y-m-d H:i:s") . '用户使用优惠券失败' . json_encode($use_coupon) . PHP_EOL, FILE_APPEND | LOCK_EX);
                            } else if ($result['errmsg'] == "ok") {
                                $coupon_off = true;
                            }
                            if ($coupon_off) {
                                M()->commit();
                                $this->ajaxReturn(array("code" => "success", "msg" => "支付成功1111"));
                            } else {
                                M()->rollback();
                                $this->ajaxReturn(array("code" => "error", "msg" => "支付失败"));
                            }
                        }
                    }
                    $this->ajaxReturn(array("code" => "success", "msg" => "支付成功2222"));
                } else {
                    M()->rollback();
                    $this->ajaxReturn(array("code" => "error", "msg" => "支付失败", "data" => "网络错误！"));
                }
            } else {
                M()->rollback();
                $this->ajaxReturn(array("code" => "error", "msg" => "失败", "data" => "网络错误！"));
            }
        } else {
            $this->ajaxReturn(array("code" => "error", 'msg' => '参数错误'));
        }
    }

    //无商品点击支付
    public function pos_pay_order_zero()
    {
        if (IS_POST) {
            $order_info = array();
            $order_sn = I("order_sn") ? I("order_sn") : $this->pos_get_order_sn();//流水号
            $order_info["order_sn"] = $order_sn;//订单编号
            $order_amount = I("order_amount");
            $order_info["order_amount"] = $order_amount;//应收金额
            $order_info["pay_status"] = 1;//支付状态为1
            $order_info["type"] = "3";//3为pos机订单
            $order_info['integral'] = I('dikoufen');//该订单使用积分
            $dikoufen = I('dikoufen');
            $order_info['integral_money'] = I('dikoujin');//该订单使用积分抵扣金额
            $code = I("coupon_code", "");
            $order_info["coupon_code"] = $code;//优惠券ID
            $order_info["coupon_price"] = I("coupon_price");//使用优惠券抵扣多少金额
            $order_info["order_goods_num"] = I("order_goods_num");//商品数量为0
            $order_info["total_amount"] = I("total_amount");//订单总价
            $user_id = I('uid') ? I('uid') : $this->userId;
            $order_info["user_id"] = $user_id;//当前使用双屏的用户ID
            $order_info["pay_time"] = I("timestamp");
            $card_code = I("card_id", "");
            $order_info["card_code"] = $card_code;//会员卡号
            $paystyle_id = I('paystyle_id');//支付方式
            $user_money = I('user_money');  //使用余额
            $order_info["paystyle"] = $paystyle_id;
            $order_info["user_money"] = $user_money;
            //查找用户的角色
            $role_id = M('merchants_role_users')->where(array('uid' => $user_id))->getField('role_id');
            //获取检验员
            if ($role_id == '7') {
                $pid = M('merchants_users')->where(array('id' => $user_id))->getField('pid');
                $merchant_id = M('merchants')->where(array('uid' => $pid))->getField('id');
                $checker_id = $this->userId;
            } else {
                $merchant_id = M('merchants')->where(array('uid' => $user_id))->getField('id');
                $checker_id = '0';
            }
            M()->startTrans(); // 开务启事
            //添加至订单表
            $data = M('order');
            $res = $data->add($order_info);
            if ($res) {
                M()->commit();
                //有会员
                //dump($card_code);
                if ($card_code) {//dump($card_code);die;
                    // $pay = $this->pays;
                    $card = M("screen_memcard_use")->alias('u')
                        ->join('left join ypt_screen_memcard m on u.card_id=m.card_id')
                        ->field('m.id,m.credits_set,m.expense,m.expense_credits,m.expense_credits_max,u.card_balance,u.yue,u.card_id,u.card_amount')
                        ->where("u.card_code='$card_code'")
                        ->find();
                    // echo $card->_sql();
                    // dump(M()->getLastSql());die;
                    //会员卡消费送积分
                    if ($card['credits_set'] == 1) {
                        $send = floor($order_amount / $card['expense']) * $card['expense_credits'];
                        //如果送的积分大于最多可送的分
                        if ($send > $card['expense_credits_max']) {
                            $send = $card['expense_credits_max'];
                        }
                    }
                    if ($dikoufen) {
                        $val['card_balance'] = $card['card_balance'] - $dikoufen + $send;
                    } else {
                        $val['card_balance'] = $card['card_balance'] + $send;
                    }
                    if ($user_money) {
                        $val['yue'] = $card['yue'] - $user_money;
                    }
                    $card_off = M("screen_memcard_use")->where("card_code='$card_code'")->save($val);
                    $ts['code'] = urlencode($card_code);
                    $ts['card_id'] = urlencode($card['card_id']);
                    $ts['custom_field_value1'] = urlencode($card['yue'] - $user_money);//会员卡余额
                    // echo "111";die;
                    // dump($card['card_amount']);die;
                    $ts['custom_field_value2'] = urlencode(M('screen_memcard_level')->where('c_id=' . $card['id'] . ' and level_integral <=' . $card['card_amount'])->order('level desc')->getField('level_name'));//会员卡名称
                    // $a = M('screen_memcard_level')->where('c_id='. $card['id'] . ' and level_integral <='. $card['card_amount'])->order('level desc')->getField('level_name');
                    // echo $a->_sql();die;
                    // echo 2222;die;
                    $ts["add_bonus"] = urlencode($send - $dikoufen);//会员卡积分
                    $token = get_weixin_token();
                    file_put_contents('./data/log/testcoupon.log', date("Y-m-d H:i:s") . json_encode($ts) . PHP_EOL, FILE_APPEND | LOCK_EX);
                    request_post('https://api.weixin.qq.com/card/membercard/updateuser?access_token=' . $token, urldecode(json_encode($ts)));
                }
                //选取支付方式
                if ($paystyle_id == 5) {
                    //现金支付
                    $pay_info = array(
                        "remark" => $order_sn,
                        "mode" => 7,
                        "merchant_id" => $merchant_id,
                        "checker_id" => $checker_id,
                        "paystyle_id" => 5,
                        "price" => $order_amount,
                        "status" => 1,
                        "cate_id" => 1,
                        "la_ka_la" => 1,
                        "paytime" => time()
                    );
                    $time = time();
                    $pay = $this->pays;
                    $pay->query("INSERT INTO ypt_pay(remark,mode,merchant_id,checker_id,paystyle_id,price,status,cate_id,paytime,la_ka_la) VALUES($order_sn,'7',$merchant_id,$checker_id,'5',$order_amount,'1','1',$time,'1')");
                    //核销优惠券
                    // dump($code);die;
                    if ($code) {
                        $url = "https://api.weixin.qq.com/card/code/consume?access_token=" . get_weixin_token();
                        $c['code'] = $code;
                        $use_coupon = request_post($url, json_encode($c));
                        $result = json_decode($use_coupon, true);
                        // dump($result);die;
                        M("screen_user_coupons")->where("usercard=$code")->setField('status', '0');
                        file_put_contents('./data/log/wz/weixin/coupon.log', date("Y-m-d H:i:s") . '用户使用优惠券' . json_encode($use_coupon) . PHP_EOL, FILE_APPEND | LOCK_EX);
                        // dump($result['errmsg']);
                        if ($result['errmsg'] != "ok") {
                            // echo 111;
                            $coupon_off = false;
                            file_put_contents('./data/log/wz/weixin/coupon.log', date("Y-m-d H:i:s") . '用户使用优惠券失败' . json_encode($use_coupon) . PHP_EOL, FILE_APPEND | LOCK_EX);
                        } else if ($result['errmsg'] == "ok") {
                            $coupon_off = true;
                            // echo  2222;
                        }
                        if ($coupon_off) {
                            M()->commit();
                            $this->ajaxReturn(array("code" => "success", "msg" => "支付成功1"));
                        } else {
                            M()->rollback();
                            $this->ajaxReturn(array("code" => "error", "msg" => "支付失败1"));
                        }
                    }
                } else if ($paystyle_id == 3) {
                    //信用卡支付
                    $pay_info = array(
                        "remark" => $order_sn,
                        "mode" => 9,
                        "merchant_id" => $merchant_id,
                        "checker_id" => $checker_id,
                        "paystyle_id" => 3,
                        "price" => $order_amount,
                        "status" => 1,
                        "cate_id" => 1,
                        "la_ka_la" => 1,
                        "paytime" => time()
                    );
                    $time = time();
                    $pay = $this->pays;
                    $pay->query("INSERT INTO ypt_pay(remark,mode,merchant_id,checker_id,paystyle_id,price,status,cate_id,paytime,la_ka_la) VALUES($order_sn,'9',$merchant_id,$checker_id,'3',$order_amount,'1','1',$time,'1')");
                    //核销优惠券
                    if ($code) {
                        $url = "https://api.weixin.qq.com/card/code/consume?access_token=" . get_weixin_token();
                        $c['code'] = $code;
                        $use_coupon = request_post($url, json_encode($c));
                        $result = json_decode($use_coupon, true);
                        M("screen_user_coupons")->where("usercard=$code")->setField('status', '0');
                        file_put_contents('./data/log/wz/weixin/coupon.log', date("Y-m-d H:i:s") . '用户使用优惠券' . json_encode($use_coupon) . PHP_EOL, FILE_APPEND | LOCK_EX);
                        if ($result['errmsg'] != "ok") {
                            $coupon_off = false;
                            file_put_contents('./data/log/wz/weixin/coupon.log', date("Y-m-d H:i:s") . '用户使用优惠券失败' . json_encode($use_coupon) . PHP_EOL, FILE_APPEND | LOCK_EX);
                        } else if ($result['errmsg'] == "ok") {
                            $coupon_off = true;
                        }
                        if ($coupon_off) {
                            M()->commit();
                            $this->ajaxReturn(array("code" => "success", "msg" => "支付成功11"));
                        } else {
                            M()->rollback();
                            $this->ajaxReturn(array("code" => "error", "msg" => "支付失败"));
                        }
                    }
                } else if ($paystyle_id == 1) {
                    //微信支付
                    $pay_info = array(
                        "remark" => $order_sn,
                        "mode" => 5,
                        "merchant_id" => $merchant_id,
                        "checker_id" => $checker_id,
                        "paystyle_id" => 1,
                        "price" => $order_amount,
                        "status" => 1,
                        "cate_id" => 1,
                        "paytime" => time()
                    );
                    $pay_info['la_ka_la'] = 1;
                    // dump($pay_info);
                    $time = time();
                    $pay = $this->pays;
                    $pay->query("INSERT INTO ypt_pay(remark,mode,merchant_id,checker_id,paystyle_id,price,status,cate_id,paytime,la_ka_la) VALUES($order_sn,'5',$merchant_id,$checker_id,'1',$order_amount,'1','1',$time,'1')");

                    // $pay->add($pay_info);
                    // echo $pay->_sql();die;
                    //核销优惠券
                    if ($code) {
                        $url = "https://api.weixin.qq.com/card/code/consume?access_token=" . get_weixin_token();
                        $c['code'] = $code;
                        $use_coupon = request_post($url, json_encode($c));
                        $result = json_decode($use_coupon, true);
                        M("screen_user_coupons")->where("usercard=$code")->setField('status', '0');
                        file_put_contents('./data/log/wz/weixin/coupon.log', date("Y-m-d H:i:s") . '用户使用优惠券' . json_encode($use_coupon) . PHP_EOL, FILE_APPEND | LOCK_EX);
                        if ($result['errmsg'] != "ok") {
                            $coupon_off = false;
                            file_put_contents('./data/log/wz/weixin/coupon.log', date("Y-m-d H:i:s") . '用户使用优惠券失败' . json_encode($use_coupon) . PHP_EOL, FILE_APPEND | LOCK_EX);
                        } else if ($result['errmsg'] == "ok") {
                            $coupon_off = true;
                        }
                        if ($coupon_off) {
                            M()->commit();
                            $this->ajaxReturn(array("code" => "success", "msg" => "支付成功111"));
                        } else {
                            M()->rollback();
                            $this->ajaxReturn(array("code" => "error", "msg" => "支付失败"));
                        }
                    }
                } else {
                    //支付宝支付
                    $pay_info = array(
                        "remark" => $order_sn,
                        "mode" => 5,
                        "merchant_id" => $merchant_id,
                        "checker_id" => $checker_id,
                        "paystyle_id" => 2,
                        "price" => $order_amount,
                        "status" => 1,
                        "cate_id" => 1,
                        "la_ka_la" => 1,
                        "paytime" => time()
                    );
                    $time = time();
                    $pay = $this->pays;
                    $pay->query("INSERT INTO ypt_pay(remark,mode,merchant_id,checker_id,paystyle_id,price,status,cate_id,paytime,la_ka_la) VALUES($order_sn,'5',$merchant_id,$checker_id,'2',$order_amount,'1','1',$time,'1')");
                    //核销优惠券
                    if ($code) {
                        $url = "https://api.weixin.qq.com/card/code/consume?access_token=" . get_weixin_token();
                        $c['code'] = $code;
                        $use_coupon = request_post($url, json_encode($c));
                        $result = json_decode($use_coupon, true);
                        M("screen_user_coupons")->where("usercard=$code")->setField('status', '0');
                        file_put_contents('./data/log/wz/weixin/coupon.log', date("Y-m-d H:i:s") . '用户使用优惠券' . json_encode($use_coupon) . PHP_EOL, FILE_APPEND | LOCK_EX);
                        if ($result['errmsg'] != "ok") {
                            $coupon_off = false;
                            file_put_contents('./data/log/wz/weixin/coupon.log', date("Y-m-d H:i:s") . '用户使用优惠券失败' . json_encode($use_coupon) . PHP_EOL, FILE_APPEND | LOCK_EX);
                        } else if ($result['errmsg'] == "ok") {
                            $coupon_off = true;
                        }
                        if ($coupon_off) {
                            M()->commit();
                            $this->ajaxReturn(array("code" => "success", "msg" => "支付成功1111"));
                        } else {
                            M()->rollback();
                            $this->ajaxReturn(array("code" => "error", "msg" => "支付失败"));
                        }
                    }
                }
                M()->commit();
                $this->ajaxReturn(array("code" => "success", "msg" => "支付成功2222"));
            } else {//res
                M()->rollback();
                $this->ajaxReturn(array("code" => "error", "msg" => "失败", "data" => "网络错误！"));
            }
        } else {//post
            $this->ajaxReturn(array("code" => "error", 'msg' => '参数错误'));
        }
    }

    //退款 拉卡拉
    public function pos_pay_back()
    {
        $order_sn = I('order_sn');
        $price_back = I('price_back');
        if ($d = $this->pays->where(array("remark" => $order_sn, "la_ka_la" => 1))->find()) {
            if ($price_back > $d['price']) $this->ajaxReturn(array("code" => "error", "msg" => "退款金额不能大于原有金额"));
            $da['price_back'] = $price_back;
            $da['status'] = 2;
            $da['back_status'] = 1;
            $res = $this->pays->where("remark=$order_sn")->data($da)->save();
            $data = array();
            $data['merchant_id'] = $d['merchant_id'];
            $data['checker_id'] = $d['checker_id'];
            $data['paystyle_id'] = $d['paystyle_id'];
            $data['mode'] = 98;
            $data['price'] = $d['price'];
            $data['price_back'] = $price_back;
            $data['cate_id'] = $d['cate_id'];
            $data['remark'] = $d['remark'];
            $data['new_order_sn'] = $d['remark'];
            $data['status'] = 5;
            $data['paytime'] = time();
            $data['bill_date'] = date('Ymd');
            $data['back_pid'] = $d['id'];
            $data['type'] = 2;
            $result = M("pay_back")->add($data);
            if ($result) {
                $this->ajaxReturn(array("code" => "success", "msg" => "退款成功"));
            } else {
                $this->ajaxReturn(array("code" => "error", "msg" => "退款失败"));
            }
        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => "未找到订单"));
        }
    }

    //获取商户uid
    private function get_merchant_uid($uid)
    {
        $role_id = M("merchants_role_users")->where("uid='$uid'")->getField('role_id');
        if ($role_id == 3) {
            $m_uid = $uid;
        } else {
            $user = M("merchants_users")->where("id='$uid'")->find();
            $m_uid = $user['pid'];
        }
        return $m_uid;
    }

    //双屏点击挂单
    public function double_res_order()
    {
        if (IS_POST) {
            //获取数据
            $order_info = array();
            $order_sn = I("order_sn") ? I("order_sn") : $this->pos_get_order_sn();//订单编号
            $order_info["order_sn"] = $order_sn;
            $order_amount = I("order_amount");
            $order_info["order_amount"] = $order_amount;//应收金额
            $order_info["type"] = "4";//4为双屏订单
            $dikoufen = I('dikoufen'); //订单使用积分
            $order_info['integral'] = $dikoufen;//该订单使用积分
            $order_info['integral_money'] = I('dikoujin');//该订单使用积分抵扣金额
            $code = I("coupon_code", "");//优惠券code
            $order_info["coupon_code"] = $code;//优惠券ID
            $order_info["coupon_price"] = I("coupon_price");//使用优惠券抵扣多少金额
            $order_info["order_goods_num"] = I("order_goods_num");//订单总数
            $order_info["total_amount"] = I("total_amount");//订单总价
            $user_id = I('uid') ? I('uid') : $this->userId;
            $order_info["user_id"] = $user_id;//当前使用双屏的用户ID
            $order_info["add_time"] = I("timestamp"); //支付时间
            $card_code = I("card_id", "");  //会员卡号
            $order_info["card_code"] = $card_code;//会员卡号
            // $paystyle_id  = I('paystyle_id');  //支付方式
            $user_money = I('user_money');  //使用余额
            // $order_info["paystyle"]= $paystyle_id;
            $order_info["user_money"] = $user_money;
            $order_info["order_benefit"] = I("order_benefit");//整单优惠金额
            $order_info["discount"] = I('order_discount');
            $order_info["order_status"] = 1;
            //查找用户的角色
            $role_id = M('merchants_role_users')->where(array('uid' => $user_id))->getField('role_id');
            //获取检验员
            if ($role_id == '7') {
                $pid = M('merchants_users')->where(array('id' => $user_id))->getField('pid');
                $merchant_id = M('merchants')->where(array('uid' => $pid))->getField('id');
                $checker_id = $this->userId;
            } else {
                $merchant_id = M('merchants')->where(array('uid' => $user_id))->getField('id');
                $checker_id = '0';
            }
            M()->startTrans(); // 开务启事
            //添加至订单表
            $data = M('order');
            $res = $data->add($order_info);
            if ($res) {
                // 加入订单商品表
                $order_goods = array();
                $goods = M("order_goods");
                $bar_code = explode(",", I("bar_code"));
                $goods_num = explode(",", I("goods_num"));
                $goods_name = explode(",", I("goods_name"));
                $goods_price = explode(",", I("goods_price"));
                $discount = explode(",", I("goods_discount"));
                $sku = explode(",", I("sku"));
                $group_id = explode(",", I("group_id"));
                $goods_weight = explode(",", I("goods_weight"));
                foreach ($bar_code as $key => $val) {
                    $goods_id = M('goods')->where(array('bar_code' => $val))->find();
                    $order_goods[$key]['order_id'] = $res;
                    $order_goods[$key]["bar_code"] = $val;
                    $order_goods[$key]["goods_name"] = $goods_name[$key];
                    $order_goods[$key]["goods_num"] = $goods_num[$key];
                    $order_goods[$key]["goods_weight"] = $goods_weight[$key];
                    $order_goods[$key]["goods_price"] = $goods_price[$key];
                    $order_goods[$key]["discount"] = $discount[$key];
                    $order_goods[$key]["sku"] = $sku[$key];
                    $order_goods[$key]["group_id"] = $group_id[$key];
                    $order_goods[$key]["goods_id"] = $goods_id['goods_id'];
                };
                $result = $goods->addAll($order_goods);
                if ($result && $res) {
                    M()->commit();
                    $this->ajaxReturn(array("code" => "success", "msg" => "挂单成功"));
                } else {
                    M()->rollback();
                    $this->ajaxReturn(array("code" => "error", "msg" => "挂单失败", "data" => "网络错误！"));
                }
            } else {
                M()->rollback();
                $this->ajaxReturn(array("code" => "error", "msg" => "失败", "data" => "网络错误！"));
            }
        } else {
            $this->ajaxReturn(array("code" => "error", 'msg' => '参数错误'));
        }
    }

    //退款 预授权
    public function pos_yu_pay_back()
    {

        $order_sn = I('order_sn');
        $type = I('type');
        if (M("pay_back")->where(array("remark" => $order_sn, "type" => $type))->find()) {
            $this->ajaxReturn(array("code" => "error", "msg" => "订单已经退款"));
        }
        if ($d = $this->pays->where(array("remark" => $order_sn, "authorization" => 1))->find()) {

            $da['price_back'] = $d['price'];
            $da['status'] = 2;
            $da['back_status'] = 1;
            $res = $this->pays->where("remark=$order_sn")->data($da)->save();
            $data = array();
            $data['merchant_id'] = $d['merchant_id'];
            $data['checker_id'] = $d['checker_id'];
            $data['paystyle_id'] = $d['paystyle_id'];
            $data['mode'] = 98;
            $data['price'] = $d['price'];
            $data['price_back'] = $d['price'];
            $data['cate_id'] = $d['cate_id'];
            $data['remark'] = $d['remark'];
            $data['new_order_sn'] = $d['remark'];
            $data['status'] = 5;
            $data['paytime'] = time();
            $data['bill_date'] = date('Ymd');
            $data['back_pid'] = $d['id'];
            $data['type'] = $type;

            $order = M('order')->where(array('order_sn' => $order_sn))->find();
            if ($order && $order['user_money']) {
                $card = M("screen_memcard_use")
                    ->field('yue,card_id')
                    ->where(array('card_code' => $order['card_code']))
                    ->find();
                $ts["record_bonus"] = urlencode("退款返回储值");
                $ts['code'] = urlencode($order['card_code']);
                $ts['card_id'] = urlencode($card['card_id']);
                $ts['custom_field_value1'] = urlencode($card['yue'] + $order['user_money']);//会员卡余额
                $ts['notify_optional']['is_notify_custom_field1'] = true;
                $token = get_weixin_token();
                $msg = request_post('https://api.weixin.qq.com/card/membercard/updateuser?access_token=' . $token, urldecode(json_encode($ts)));
                $info = json_decode($msg, true);
                if ($info['errmsg'] == 'ok') {
                    M('screen_memcard_use')->where(array('card_code' => $order['card_code']))->setField('yue', $card['yue'] + $order['user_money']);
                }
            }
            $result = M("pay_back")->add($data);

            if ($result) {
                $this->add_order_goods_number($order_sn);
                $this->ajaxReturn(array("code" => "success", "msg" => "退款成功"));
            } else {
                $this->ajaxReturn(array("code" => "error", "msg" => "退款失败"));
            }
        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => "未找到订单"));
        }
    }

    /**
     * 支付成功后更新库存
     * 传入订单唯一标识$order_sn
     * @param int $order_sn
     */
    private function add_order_goods_number($remark = 0)
    {
        if (!$remark) exit();
        $new_order_sn = $this->pays->where("remark='$remark'")->getField("new_order_sn");

        if ($new_order_sn) {
            $order_sn = $remark;
            $order_id = M("order")->where(array("order_sn" => $order_sn))->getField("order_id");
            $order_goods_list = M("order_goods")->where(array("order_id" => $order_id))->field("goods_id,goods_num")->select();
            if ($order_goods_list) {
                foreach ($order_goods_list as $k => $v) {
                    if ($v['goods_id'] && $v['goods_num']) M("goods")->where(array("goods_id" => $v['goods_id']))->setInc('goods_number', $v['goods_num']); //更新库存
                }
            }
        }
    }

    /**
     * 预授权完成
     */
    public function pos_yu_succ()
    {
        $order_sn = I("order_sn");
        $price = I("price");
        if ($this->pays->where(array("remark" => $order_sn, "status" => 1, "authorization" => 1))->find()) {
            $this->ajaxReturn(array("code" => "error", "msg" => "该订单已经预授权完成"));
        }
        if ($this->pays->where(array("remark" => $order_sn, "status" => 6, "authorization" => 1))->setField(array('status' => 1, 'price' => $price))) {
            M("order")->where(array("order_sn" => $order_sn, "status" => 6, "authorization" => 1))->setField('status', 1);
            $this->ajaxReturn(array("code" => "success", "msg" => "预授权完成成功"));
        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => "请求失败"));
        }

    }

    //双屏餐饮点击挂单
    public function double_order()
    {
        if (IS_POST) {
            //获取数据
            $order_info = array();
            $order_sn = I("order_sn") ? I("order_sn") : $this->pos_get_order_sn();//订单编号
            $order_info["order_sn"] = $order_sn;
            $order_amount = I("order_amount");
            $order_info["order_amount"] = $order_amount;//应收金额
            $order_info["type"] = "4";//4为双屏订单
            $dikoufen = I('dikoufen'); //订单使用积分
            $order_info['integral'] = $dikoufen;//该订单使用积分
            $order_info['integral_money'] = I('dikoujin');//该订单使用积分抵扣金额
            $code = I("coupon_code", "");//优惠券code
            $order_info["coupon_code"] = $code;//优惠券ID
            $order_info["coupon_price"] = I("coupon_price");//使用优惠券抵扣多少金额
            $order_info["order_goods_num"] = I("order_goods_num");//订单总数
            $order_info["total_amount"] = I("total_amount");//订单总价
            $user_id = I('uid') ? I('uid') : $this->userId;
            $order_info["user_id"] = $user_id;//当前使用双屏的用户ID
            $order_info["add_time"] = I("timestamp"); //支付时间
            $card_code = I("card_code", "");  //会员卡号
            $order_info["card_code"] = $card_code;//会员卡号
            // $paystyle_id  = I('paystyle_id');  //支付方式
            $user_money = I('user_money');  //使用余额
            // $order_info["paystyle"]= $paystyle_id;
            $order_info["user_money"] = $user_money;
            $order_info["order_benefit"] = I("order_benefit");//整单优惠金额
            $order_info["discount"] = I('order_discount');
            $order_info["order_status"] = 1;
            //查找用户的角色
            $role_id = M('merchants_role_users')->where(array('uid' => $user_id))->getField('role_id');
            //获取检验员
            if ($role_id == '7') {
                $pid = M('merchants_users')->where(array('id' => $user_id))->getField('pid');
                $merchant_id = M('merchants')->where(array('uid' => $pid))->getField('id');
                $checker_id = $this->userId;
            } else {
                $merchant_id = M('merchants')->where(array('uid' => $user_id))->getField('id');
                $checker_id = '0';
            }
            M()->startTrans(); // 开务启事
            //添加至订单表
            $data = M('order');
            $res = $data->add($order_info);
            if ($res) {
                // 加入订单商品表
                $order_goods = array();
                $goods = M("order_goods");
                $goods_id = explode(",", I("goods_id"));
                $goods_num = explode(",", I("goods_num"));
                $goods_name = explode(",", I("goods_name"));
                $goods_price = explode(",", I("goods_price"));
                $discount = explode(",", I("goods_discount"));
                $sku = explode(",", I("sku"));
                $group_id = explode(",", I("group_id"));
                foreach ($goods_id as $key => $val) {
                    $order_goods[$key]['goods_img'] = M('goods')->where(array('goods_id' => $val))->getField('goods_img1');
                    $order_goods[$key]['order_id'] = $res;
                    $order_goods[$key]["goods_name"] = $goods_name[$key];
                    $order_goods[$key]["goods_num"] = $goods_num[$key];
                    $order_goods[$key]["goods_price"] = $goods_price[$key];
                    $order_goods[$key]["discount"] = $discount[$key];
                    $order_goods[$key]["sku"] = $sku[$key];
                    $order_goods[$key]["spec_key"] = $sku[$key];
                    $order_goods[$key]["spec_key_name"] = M('goods_sku')->where(array('sku_id' => $sku[$key]))->getField('properties');
                    $order_goods[$key]["group_id"] = $group_id[$key];
                    $order_goods[$key]["goods_id"] = $val;
                };
                $result = $goods->addAll($order_goods);
                if ($result && $res) {
                    M()->commit();
                    $this->ajaxReturn(array("code" => "success", "msg" => "挂单成功"));
                } else {
                    M()->rollback();
                    $this->ajaxReturn(array("code" => "error", "msg" => "挂单失败", "data" => "网络错误！"));
                }
            } else {
                M()->rollback();
                $this->ajaxReturn(array("code" => "error", "msg" => "失败", "data" => "网络错误！"));
            }
        } else {
            $this->ajaxReturn(array("code" => "error", 'msg' => '参数错误'));
        }
    }
}