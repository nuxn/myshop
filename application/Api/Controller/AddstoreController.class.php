<?php

namespace Api\Controller;

use Common\Controller\ApibaseController;

class AddstoreController extends ApibaseController
{
    protected $merchants;
    protected $users;
    protected $user_role;
    protected $upwzs;
    protected $cates;

    function _initialize()
    {
        parent::_initialize();
        $this->merchants = M("merchants");
        $this->users = M("merchants_users");
        $this->user_role = M("merchants_role_users");
        $this->upwzs = M("merchants_upwz");
        $this->cates = M("merchants_cate");


    }

    //商户或者代理新增门店

    public function add_shop()
    {
        try {
            if (IS_POST) {
                M()->startTrans();
   /*             $mid = I('mid') || $this->ajaxReturn(array('code' => 'error', 'msg' => '总店id为空'));*/
                ($uid=I('uid')) || $this->ajaxReturn(array('code' => 'error', 'msg' => '商户id不存在'));
                $merchant_one = $this->merchants->where("uid = $uid")->find();
                if ($merchant_one['mid'] != 0 || empty($merchant_one)) $this->ajaxReturn(array('code' => 'error', 'msg' => '多门店模式错误'));
                $mid = $merchant_one['id'];
                $user_one = $this->users->where("id=" . $merchant_one['uid'])->find();

                $number = $this->merchants->where("mid = $mid")->count("id");
                $number = $number + 1;
                ($telephone = I('telephone')) ||   $this->ajaxReturn(array('code' => 'error', 'msg' =>'手机号码为空'));

                //$phone_begin = substr($user_one['user_phone'], 0, 7);
               // $phone_end = substr("00000" . $number, -4);
//            机器用户添加
                unset($user_one['id']);
                $user_one['user_name'] = $user_one['user_name'] . $number;
                $user_one['user_phone'] = $telephone;
                $user_one['user_pwd'] = md5(123456);
                $user_one['auth'] = "";
                $user_one['add_time'] = time();
                $machine_user = $this->users->add($user_one);

                //门店添加
                $merchant = array(
                    'mid' => $mid,
                    'uid' => $machine_user,
                    'merchant_name' =>I('merchant_name'),
                    'province' => I("province"),
                    'city' => I("city"),
                    'county' => I("county"),
                    'address' => I("address"),
                    'industry' => I("industry"),
                    'account_type' => I("account_type"),
                    'is_miniapp' => I("is_miniapp")
                );
                $machine_merchant = $this->merchants->add($merchant);

                //机器人角色添加
                $role['uid'] = $machine_user;
                $role['role_id'] = 3;
                $role['add_time'] = time();
                $this->user_role->add($role);
                $user_data = array(
                    'user_login' => $user_one['user_phone'],
                    'user_pass' => sp_password('123456'),
                    'user_nicename' => $user_one['user_phone'],
                    'create_time' => date('Y-m-d H:i:s'),
                    'mobile' => $user_one['user_phone'],
                    'platform' => 1,
                    'muid' => $machine_user,
                    'pid' => $mid,
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

                if ($machine_user && $machine_merchant && $id) {
                    //同步到进件
                    $this->uptosame($machine_merchant);
                    M()->commit();
                    $this->ajaxReturn(array('code' => 'success', 'msg' =>'添加门店成功'));


                } else {
                    M()->rollback();
                   throw new Exception("添加失败");

                }


            } else {
                throw new Exception("请求错误");
            }
        }catch (Exception $e){
            $this->ajaxReturn(array('code' => 'error', 'msg' =>$e->getMessage()));


        }

    }



    public function uptosame($id)
    {



        $small_merchant =$this->merchants->where(array('id'=>$id))->find();
        if($small_merchant['mid'] == 0) throw new Exception('该商户并不是多门店模式');
        $small_cate =$this->cates->where(array('merchant_id'=>$small_merchant['id']))->find();

        $big_merchant =$this->merchants->where(array('id'=>$small_merchant['mid']))->find();
        $big_cate =$this->cates->where(array('merchant_id'=>$big_merchant['id'],'status'=>1,'checker_id'=>0))->find();
        if(!$big_merchant) throw new Exception('未找到上级大商户');
        if(!$big_cate) throw new Exception('大商户还未绑定台签');
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
            return true;
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
            return true;
        }
        throw new Exception('同步进件失败');
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
    //展示商户所有门店列表
    public function shop_list()
    {
        if (IS_POST) {
            ($uid = I('uid'))  || $this->ajaxReturn(array('code'=>'error','msg'=>'没有商户id'));
            $merchant_one = $this->merchants->alias('mer')->join('ypt_merchants_users yus on yus.id = mer.uid')->where(array('mer.uid'=>$uid))->field('mer.id,mer.uid,mer.mid,mer.merchant_name,yus.user_phone')->find();//先查出该商户的总店
            if (empty($merchant_one) || $merchant_one['mid']!=0)   $this->ajaxReturn(array('code'=>'error','msg'=>'不是多门店商户'));

                $merchant_one['name'] = '总店';
                $merchant_one_arr[]=$merchant_one;
                $mid = $merchant_one['id'];


            $merchant_list = $this->merchants->alias('mer')->join('ypt_merchants_users yus on yus.id = mer.uid')->where('mer.mid='.$mid)->field('mer.id,mer.uid,mer.mid,mer.merchant_name,yus.user_phone')->select();
            if (is_array($merchant_list) && count($merchant_list)>0) {
                foreach ($merchant_list as $key => &$val) {
                    $num=$key+1;
                    $val['name']='门店'.$num;
                }

            }
            $data = array_merge($merchant_list,$merchant_one_arr);
            $this->ajaxReturn(array('code'=>'success','msg'=>'请求成功','data'=>$data));

        } else {
            $this->ajaxReturn(array('code'=>'error','msg'=>'请求错误'));
        }

    }

}