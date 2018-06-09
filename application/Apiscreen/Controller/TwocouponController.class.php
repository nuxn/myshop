<?php
namespace Apiscreen\Controller;

use Common\Controller\ScreenbaseController;
use Think\Upload;

class  TwocouponController extends ScreenbaseController
{
    public $coupons;
    public $merchants;
    public $use_coupons;
    public $host;

    function _initialize()
    {
        parent::_initialize();
        $this->host = 'http://' . $_SERVER['HTTP_HOST'];
        $this->coupons=M("screen_coupons");
        $this->merchants=M("merchants");
        $this->use_coupons=M("screen_user_coupons");
        $this->log_path = $_SERVER['DOCUMENT_ROOT'] . "/data/log/coupon/";
    }
// 获得优惠券详情
    public function get_card_detail()
    {
        $this->checkLogin();
        $code =I("code");
        $price=I("price");
        $mid=$this->get_merchant($this->userInfo['uid']);
        if(!$this->use_coupons->where("usercard='$code' And status=1 ")->find())$this->ajaxReturn(array("code" => "error","msg"=>"该优惠券不存在"));
        $card_id=$this->use_coupons->where("usercard='$code'")->getField("card_id");
        $status=$this->use_coupons->where("usercard='$code'")->getField("status");
        $unionid=$this->use_coupons->where("usercard='$code'")->getField("unionid");
        if($status == 0)$this->ajaxReturn(array("code" => "error","msg"=>"该优惠券已使用"));
        $coupon=$this->coupons->where("card_id='$card_id'And mid='$mid'")->find();
        if(!$coupon)$this->ajaxReturn(array("code" => "error","msg"=>"非该商家的优惠券"));
        //if($coupon['is_cashier'] == 1)$this->ajaxReturn(array("code" => "error","msg"=>"该优惠券没有投放到收银台"));
        if($coupon['status'] == 5)$this->ajaxReturn(array("code" => "error","msg"=>"该优惠券已失效"));
        if($coupon['total_price']>$price)$this->ajaxReturn(array("code" => "error","msg"=>"价格未达到使用优惠券的使用"));
        if(time()<$coupon['begin_timestamp']||time()>$coupon['end_timestamp'])$this->ajaxReturn(array("code" => "error","msg"=>"没有达到优惠券的使用时间"));
        $data['de_price'] =$coupon['de_price'];
		//$data['memid'] = M('screen_mem')->where("openid='$coupon[fromname]'")->getField('id');
		$data['memid'] = M('screen_mem')->where("unionid='$unionid'")->getField('id');
        $this->ajaxReturn(array("code" => "success","msg"=>"成功", "data"=>$data));
    }

//使用优惠券
    public function use_card($code_one=0)
    {
        $code=$code_one?$code_one:I("code");
        $data['code']=$code;
        $coupon_user_one = M("screen_user_coupons")->where("usercard='$code' And status =1")->find();
        if ($coupon_user_one){
            $url="https://api.weixin.qq.com/card/code/consume?access_token=".get_weixin_token();
            $result=request_post($url,json_encode($data));
            get_date_dir($this->log_path,'twocoupon','使用优惠券返回值',$result);
            $result=json_decode($result);
            if($result->errmsg == "ok"){
                $this->use_coupons->where("usercard='$code'")->save(array("status"=>0));
                return array("code" => "success","msg"=>"成功","data"=>"优惠券使用成功");
            }else{
               return array("code" => "error","msg"=>"失败","data"=>"优惠券使用失败");
            }
        }else{
            return array("code" => "error","msg"=>"失败","data"=>"未找到优惠券");
        }
    }

//    向用户发放优惠券 (无) 返回领券的接口
    public function pull_card_one1()
    {
//        $opendid=I("opendid");
        $card_id=I("card_id");
        $data='{
            "action_name": "QR_CARD", 
            "expire_seconds": 1800,
            "action_info": {
                "card": {
                    "card_id": "'.$card_id.'", 
                    "is_unique_code": true ,
                    "outer_id" : 1
                }
            }
        }';

        $url = "https://api.weixin.qq.com/card/qrcode/create?access_token=".get_weixin_token();
        $res = request_post($url, $data);
        $res = json_decode($res, true);
        $this->ajaxReturn(array("code" => "success","msg"=>"成功", "data"=>$res));
        if($res['errcode']==0){
            $url=$res['url'];
            $this->ajaxReturn(array("code" => "success","msg"=>"成功","data"=>$url));
        }else{
            $this->ajaxReturn(array("code" => "error","msg"=>"失败", "data"=>"优惠券领取失败"));
        }
    }

//向指定用户发放优惠券
    public function pull_card_one2()
    {
        $opendid=I("opendid");
        $card_id=I("card_id");
        if(!$this->coupons->where("card_id='$card_id'")->find())$this->ajaxReturn(array("code" => "error","msg"=>"卡券不存在"));
        $data ='{
                "touser": "'.$opendid.'", 
                "wxcard": {
                    "card_id": "'.$card_id.'"
                }, 
                "msgtype": "wxcard"
                }';
        $url = "https://api.weixin.qq.com/cgi-bin/message/mass/preview?access_token=".get_weixin_token();
        $res = request_post($url, $data);
        get_date_dir($this->log_path,'twocoupon','自动投放二维码',$res);
        $res = json_decode($res, true);
        if($res['errcode']==0){
            $this->ajaxReturn(array("code" => "success","msg"=>"成功"));
        }else{
            $this->ajaxReturn(array("code" => "error","msg"=>"失败"));
        }

    }

//    双屏收银 扫码领券
    public function pull_card_all()
    {
        $uid=$this->userInfo['uid'];
        $mid=$this->get_merchant($uid);
        $time = time();
        $coupons=$this->coupons->where("mid=$mid And is_cashier=2 And begin_timestamp <$time And end_timestamp >$time And quantity > 0 And status=3")->select();
        $map = array();
        foreach ($coupons as $k => $v)
        {
            $map[$k]['card_id']=$v['card_id'];
            $map[$k]['outer_str']='12b';
        }
        $data = array(
            "action_name" => "QR_MULTIPLE_CARD",
            "action_info" => array(
                "multiple_card" => array(
//                    "card_list" =>array(
//                        array("card_id"=> "pyaFdwJoDrzK9vcXYh0aPfBDfWcs","outer_str"=>"12b"),
//                        array("card_id"=> "pyaFdwPAsKl7S2GOwOC9iamg3aSA","outer_str"=>"12b")
//                    )
                "card_list" =>$map
                )
            )
        );
        $url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=".get_weixin_token();
        $res = request_post($url,  json_encode($data));
        get_date_dir($this->log_path,'twocoupon','生成二维码地址',$res);
        $QRMeg = json_decode($res, true);
        $url=$QRMeg['url'];
        if($url){
            $this->ajaxReturn(array("code" => "success","msg"=>"成功", "data"=>$url));
        }else{
            $this->ajaxReturn(array("code" => "error","msg"=>"失败", "data"=>"生成二维码地址失败"));
        }

    }

    /**
     * 创建货架
     * 跳转会员卡领取的第三方页面
     * 用于支付后回调页面领取会员卡链接
     */
    public function pull_card_one()
    {
        $card_id=I("card_id");
//        $mid=30;
        $coupons=$this->coupons->where("card_id='$card_id'And is_cashier =2")->field("card_id,base_url as thumb_url")->select();
        foreach ($coupons as $k =>&$v){
            $v['thumb_url']=$this->host.$v['thumb_url'];
        }
        $token = get_weixin_token();
        $arr = array(
            "banner" => "http://sy.youngport.com.cn/public/images/img/coupon_back.png",//货架背景图
            "page_title" => urlencode("洋仆淘"),//地址栏标题
            "can_share" => true,
            "scene" => 'SCENE_NEAR_BY',
            "card_list" =>
                $coupons
            );
			//dump(urldecode(json_encode($arr)));exit;
        $mem_card_query_url = "https://api.weixin.qq.com/card/landingpage/create?access_token=$token";
        $result = request_post($mem_card_query_url, urldecode(json_encode($arr)));
        $result = json_decode($result, true);
        redirect($result['url']);
    }

    /**
     * @param $uid
     * @return 获取商户id
     */
    protected function get_merchant($uid)
    {
        $role_id=M("merchants_role_users")->where("uid=$uid")->getField('role_id');
        if($role_id == 3){
            $muid= $uid;
        }else{
            $muid= M("merchants_users")->where("id=$uid")->getField("pid");
        }
        $mid=$this->merchants->where("uid=$muid")->getField("id");
        return $mid;
    }


}