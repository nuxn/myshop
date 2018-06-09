<?php

namespace Api\Controller;
use Common\Controller\ApibaseController;
/**
 * 积分商城接口
 */
class IntegralController extends ApibaseController
{
	public $memcardModel;
    public $memberModel;
    public $memcard_use_Model;
    public $user_coupons;
    public $coupons;
    public $host;
    public $merchants;
    public $merchants_agent;
    public $merchants_user;
    public static $appid = 'wx3fa82ee7deaa4a21';
    public static $secret = '6b6a7b6994c220b5d2484e7735c0605a';

    public function __construct()
    {
        parent::__construct();
        $this->memcardModel = M("screen_memcard");
        $this->memberModel = M("screen_mem");
        $this->memcard_use_Model = M("screen_memcard_use");
        $this->user_coupons = M("screen_user_coupons");
        $this->coupons = M("screen_coupons");
        $this->host = 'http://' . $_SERVER['HTTP_HOST'];
        $this->merchants = M("merchants");
        $this->merchants_agent = M("merchants_agent");
        $this->merchants_user = M("merchants_users");
    }

    /**
     * 积分商城首页
     *  card_code   会员卡卡号   231371980215
     */
    public function index()
    {
    	$card_id = I('card_id');
        cookie('card_id',$card_id);
        $fromname = I('openid');
        cookie('openid',$fromname);
    	$memuse = $this->memcard_use_Model->where(array('status'=>1,'card_id'=>$card_id,'fromname'=>$fromname))->field('memcard_id,fromname,card_balance,card_code')->find();
        cookie('code',$memuse['card_code']);
        $memcard = $this->memcardModel->where(array('id'=>$memuse['memcard_id'],'cardstatus'=>4))->field('mid,is_agent')->find();
        if($memcard['is_agent']==0){
            //商户会员卡
            $mid = $this->_get_mch_id($memcard['mid']); //获取商户id
            $where['mid'] = $mid;
            $where['agent_id'] = 0;
            $where['card_type'] = 'GIFT';
            $where['status'] = array('in','3,4');
            $where['on_status'] = array('in','1,2');
            $data = $this->get_coupons($where);
        }else{
            //代理商会员卡
            $agent_id = $this->_get_agent_id($memcard['mid']);//获取代理商id
            $where['agent_id'] = $agent_id;
            $where['card_type'] = 'GIFT';
            $where['status'] = array('in','3,4');
            $where['on_status'] = array('in','1,2');
            $data = $this->get_coupons($where);
        }
        if (count($data)==0) {
            //无积分券
            $this->card_balance = $memuse['card_balance'];
            $this->display('member_shopNo');
        }else{
            $this->card_balance = $memuse['card_balance'];
            $this->data = $data;
            $this->display();   
        }
        
    }

    /**
     * 积分券详情
     */
    public function coupons_detail()
    {
        $id = I('id'); //礼品劵id
        $where['card_type'] = 'GIFT';
        $where['id'] = $id;
        $where['status'] = array('in','3,4');
        $data = $this->details($where); //获取商品详情
        $hint = explode('；', $data['description']);
        if($data['agent_id']==0){
            //单店
            $merchants = $this->merchants->where(array('id'=>$data['mid']))->find();
            $address = array();
            $address['address'] = $merchants['province'].$merchants['city'].$merchants['county'].$merchants['address'];
            $address['lon'] = $merchants['lon'];
            $address['lat'] = $merchants['lat'];
            $this->address = $address;
            $this->data = $data;
            $this->hint = $hint;
            $this->display('coupons_info');
        }else{
            //多店
            $this->data = $data;
            $this->hint = $hint;
            $this->display();
        }
        
    } 

    /**
     * 百度地图
     */
    public function map()
    {
    	
    	//
       	$this->display(); 
    }

    /**
     * 查询店铺位置
     */
    public function store_list()
    {
    	$card_id=cookie('card_id');
    	$fromname=cookie('openid');
    	$memuse = $this->memcard_use_Model->where(array('status'=>1,'card_id'=>$card_id,'fromname'=>$fromname))->field('memcard_id,fromname,card_balance')->find();
        $memcard = $this->memcardModel->where(array('id'=>$memuse['memcard_id']))->field('mid,is_agent,id')->find();
        if($memcard['is_agent']==0){
        	//单店
	        $merchants = $this->merchants->where(array('uid'=>$memcard['mid']))->find();
	        $address['address'] = $merchants['province'].$merchants['city'].$merchants['county'].$merchants['address'];
	        $address['lon'] = $merchants['lon'];
	        $address['lat'] = $merchants['lat'];
	        $address['merchant_jiancheng'] = $merchants['merchant_jiancheng'];
	        $this->ajaxReturn(array("code" => "success", "msg" => $address,'type'=>1));
    	}else{
    		//多店
    		$id = I('coupons_id'); //礼品劵id
    		$where['card_type'] = 'GIFT';
	        $where['id'] = $id;
            $where['status'] = array('in','3,4');
	        $data = $this->details($where);
	        if($data['use_merchant']==0){
	            // //所有门店
                //查询会员卡店铺
                $use_merchants = M('screen_cardset')->where(array('c_id'=>$memcard['id']))->field('use_merchants')->find();
                $merchant_id = explode(',', $use_merchants['use_merchants']);
	            foreach ($merchant_id as $key => $value) {
	                //获取商户id
	                if($this->merchants_user->where(array('id'=>$value,'agent_id'=>$data['agent_id']))->find()){
	                    $this->_get_mch_id($value)?$mid[] = $this->_get_mch_id($value):'';
	                }
	            }
	            $res = $this->shop_location($mid,$id); //店铺坐标定位
                $this->ajaxReturn(array("code" => "success", "msg" => $res,'type'=>2));
	        }else{
	            //部分门店
	            $merchant_id = explode(',', $data['use_merchant']);
	            $res = $this->shop_location($merchant_id,$id); //店铺坐标定位
	            $this->ajaxReturn(array("code" => "success", "msg" => $res,'type'=>2));
	        }
    	}
    }

    /**
     * 店铺坐标定位
     */
    private function shop_location($merchant_id,$id)
    {
        foreach ($merchant_id as $k => $v) {
            $where = 'id='.$v.' and status=1';
            $sql = 'SELECT uid,base_url,city,county,address,merchant_name,merchant_jiancheng,logo_url,lon,lat,shipping_range,industry,shipping_type  FROM  ypt_merchants where '.$where;
            $merchants = M()->query($sql);
            //坐标转换
            $result = $this->coordinate_switchf($merchants[0]['lat'],$merchants[0]['lon']);
            $store['address'] = $merchants[0]['province'].$merchants[0]['city'].$merchants[0]['county'].$merchants[0]['address'];
            $store['lon'] = $result['Lon'];
            $store['lat'] = $result['Lat'];
            $store['merchant_jiancheng'] = $merchants[0]['merchant_jiancheng'];
            
            $store['uid'] = $merchants[0]['uid'];
            $store['coupons_id'] = $id;
            $res[] =$store;
        }
        return $res;
    }

    //腾讯转百度坐标转换
    private function coordinate_switchf($a,$b)
    {
        $x = (double)$b;
        $y = (double)$a;
        $x_pi = 3.14159265358979324;
        $z = sqrt($x * $x+$y * $y) + 0.00002 * sin($y * $x_pi);
        $theta = atan2($y,$x) + 0.000003 * cos($x*$x_pi);
        $gb = number_format($z * cos($theta) + 0.0065,6);
        $ga = number_format($z * sin($theta) + 0.006,6);
        return array('Lat'=>$ga,'Lon'=>$gb);
    }

    /**
     *可兑换门店列表
     */
    public function member_shop()
    {
        $id = I('id'); //礼品劵id
        $lat = I('lat');
        $lon = I('lon');
        $where['card_type'] = 'GIFT';
        $where['id'] = $id;
        $where['status'] = array('in','3,4');
        $data = $this->details($where);
        if($data['use_merchant']==0){
            // //所有门店
            $memcard = $this->memcardModel->where(array('card_id'=>cookie('card_id')))->field('mid,is_agent,id')->find();
            $use_merchants = M('screen_cardset')->where(array('c_id'=>$memcard['id']))->field('use_merchants')->find();
            // echo M('screen_cardset')->_sql();
            $merchant_id = explode(',', $use_merchants['use_merchants']);
            foreach ($merchant_id as $key => $value) {
                //获取商户id
                if($this->merchants_user->where(array('id'=>$value,'agent_id'=>$data['agent_id']))->find()){
                    $this->_get_mch_id($value)?$mid[] = $this->_get_mch_id($value):'';
                }
            }
            //查询门店列表
            $res = $this->shop_list($mid,$lon,$lat,$id);
            $this->store = $res;
            $this->display();
        }else{
            //部分门店
            $merchant_id = explode(',', $data['use_merchant']);
            //查询门店列表
            $res = $this->shop_list($merchant_id,$lon,$lat,$id);
            $this->store = $res;
            $this->display();
        }
    }

    /**
     * 查询门店列表
     */
    private function shop_list($merchant_id,$lon,$lat,$id)
    {
        foreach ($merchant_id as $k => $v) {
            $where = 'id='.$v.' and status=1';
            $sql = 'SELECT uid,base_url,city,county,address,merchant_name,merchant_jiancheng,logo_url,lon,lat,shipping_range,industry,shipping_type,slc(lat,lon,'.$lat.','.$lon.') as distance  FROM  ypt_merchants where '.$where.' order by distance';
            $merchants = M()->query($sql);
            $store['address'] = $merchants[0]['province'].$merchants[0]['city'].$merchants[0]['county'].$merchants[0]['address'];
            $store['lon'] = $merchants[0]['lon'];
            $store['lat'] = $merchants[0]['lat'];
            $store['merchant_jiancheng'] = $merchants[0]['merchant_jiancheng'];
            if($merchants[0]['distance']<1){
                $merchants[0]['distance'] = (int)($merchants[0]['distance']*1000).'m';
            }else{
                $merchants[0]['distance'] = round($merchants[0]['distance'] ,2).'km';
            }
            $store['distance'] = $merchants[0]['distance'];
            $store['uid'] = $merchants[0]['uid'];
            $store['coupons_id'] = $id;
            $res[] =$store;
        }
        return $res;
    }


    /**
     * 创建货架
     * 跳转会员卡领取的第三方页面
     * 用于支付后回调页面领取会员卡链接
     */
    public function create_shelves()
    {
        if ($_REQUEST) {
            $id = I('id'); //礼品劵id
            $where['card_type'] = 'GIFT';
            $where['id'] = $id;
            $where['status'] = array('in','3,4');
            $data = $this->details($where);
            $time =time();
            if($data['quantity']<=0){
                $this->ajaxReturn(array("code" => "error", "msg" => "积分卷库存不足！"));  //积分卷库存不足
            }
            if($data['status']==2){
                if($time<=$data['begin_on_timestamp']||$time>$data['end_on_timestamp']){
                    $this->ajaxReturn(array("code" => "error", "msg" => "该积分商品不在兑换时效内！"));  //该积分商品不在兑换时效内
                }
            }
            if($data['type']=='DATE_TYPE_FIX_TIME_RANGE'){
                if($time<=$data['begin_timestamp']||$time>$data['end_timestamp']){
                    $this->ajaxReturn(array("code" => "error", "msg" => "该积分商品不在有效期内！"));  //该积分商品不在有效期内
                }
            }
            $card_id = cookie('card_id');
            $openid = cookie('openid');
            $memuse = $this->memcard_use_Model->where(array('status'=>1,'card_id'=>$card_id,'fromname'=>$openid))->field('memcard_id,fromname,card_balance')->find();
            // echo $this->memcard_use_Model->_sql();
            $count = $this->user_coupons->where(array('fromname'=>$openid,'coupon_id'=>$id))->count();
            // dump($count);dump($data['get_limit']);
            if($count>=$data['get_limit']){ 
                $this->ajaxReturn(array("code" => "error", "msg" => "无法兑换！已达到该商品的个人兑换上限！"));
            }
            if((int)$data['integral']>(int)$memuse['card_balance']){
                $this->ajaxReturn(array("code" => "error", "msg" => "非常遗憾！您的积分不足！")); 
            }
            
            $token = $this->get_token();
            $card_code = cookie('code');
            $ts['code'] = urlencode($card_code);//卡号
            $ts['card_id'] = urlencode($card_id);//卡id
            $ts["add_bonus"] = urlencode('-'.$data['integral']);//增加的积分，负数为减
            $ts["record_bonus"] = urlencode('积分券兑换使用积分');//增加的积分，负数为减
            $res = request_post('https://api.weixin.qq.com/card/membercard/updateuser?access_token='.$token,urldecode(json_encode($ts)));
            file_put_contents('./data/log/weixin/'.date("Y_m_").'card_coupon.log', date("Y-m-d H:i:s") .',积分券兑换使用，会员卡code:'.$card_code.',请求参数:'. json_encode($ts). PHP_EOL, FILE_APPEND | LOCK_EX);
            file_put_contents('./data/log/weixin/'.date("Y_m_").'card_coupon.log', date("Y-m-d H:i:s") .',积分券兑换使用，会员卡code:'.$card_code.',返回结果:'. json_encode($res). PHP_EOL.PHP_EOL, FILE_APPEND | LOCK_EX);
            $res = json_decode($res,true);
            if($res['errcode']==0){
                //成功
                M("screen_memcard_use")->where("card_code='$card_code'")->setDec('card_balance',$data['integral']);
            }else{
                $this->ajaxReturn(array("code" => "error", "msg" => "4无法兑换！请稍后再试！"));
            }
            // $card_id = 'pyaFdwJ-m1uf9S2P2MXcSvo2xX1Y';
            $cardid = $data['card_id'];
            $arr = array(
                "banner" => "http://sy.youngport.com.cn/themes/simplebootx/Public/pay/img/img1.jpg",//货架背景图
                "page_title" => urlencode("洋仆淘"),//地址栏标题
                "can_share" => false,
                "scene" => 'SCENE_NEAR_BY',
                "card_list" => array(
                    array(
                        "card_id" => $cardid,
                        "thumb_url" => "http://sy.youngport.com.cn/themes/simplebootx/Public/pay/images/smalllogo.png",//列表logo
                    )
                )
            );

            $mem_card_query_url = "https://api.weixin.qq.com/card/landingpage/create?access_token=$token";
            $result = request_post($mem_card_query_url, urldecode(json_encode($arr)));
            $result = json_decode($result, true);
            // redirect($result['url']);
            $this->ajaxReturn(array("code" => "success", "msg" => $result['url']));
        }
    }

    /**
     * 判断库存 自动下架
     */
    public function sold_out()
    {
        $id = I('id'); //礼品劵id
        $where['card_type'] = 'GIFT';
        $where['id'] = $id;
        $data = $this->coupons->where($where)->field('quantity')->find();
        if ($data['quantity']<=0) {
            if($this->coupons->where($where)->setField('on_status',3)){
                $this->ajaxReturn(array("code" => "success", "msg" => '库存不足下架成功！'));
            }else{
                $this->ajaxReturn(array("code" => "success", "msg" => '库存不足下架失败！'));
            }
        }
    }

    /**
     * 添加失败 返还积分
     */
    public function return_out()
    {
        $integral = I('integral');
        $card_code = cookie('code');
        $card_id = cookie('card_id');
        $access_token = $this->get_token();
        $ts['code'] = urlencode($card_code);//卡号
        $ts['card_id'] = urlencode($card_id);//卡id
        $ts["add_bonus"] = urlencode($integral);//增加的积分，负数为减
        $ts["record_bonus"] = urlencode('积分券兑换失败返还');//增加的积分，负数为减
        $resu = request_post('https://api.weixin.qq.com/card/membercard/updateuser?access_token='.$access_token,urldecode(json_encode($ts)));
        $res = json_decode($resu,true);
        if($res['errcode']==0){
            //增加领取记录
            M("screen_memcard_use")->where("card_code='$card_code'")->setInc('card_balance',$integral);
            $card_balance = M("screen_memcard_use")->where("card_code='$card_code'")->getField('card_balance');
            M('screen_memcard_log')->add(array('add_time' => time(), 'update_time' => time(), 'value' => $integral, 'balance' => $card_balance, 'ts' => json_encode($ts), 'code' => $card_code,'ts_status'=>1,'msg'=>$resu,'record_bonus'=>'积分商城兑换失败'));
            //成功
            $this->path = $_SERVER['DOCUMENT_ROOT'] . '/data/log/member/';
            get_date_dir($this->path,'card_coupon','核销','积分商城兑换失败，会员卡code:'.$card_code.',返还积分:'.$integral);
            get_date_dir($this->path,'card_coupon','核销','积分商城兑换失败，会员卡code:'.$card_code.',请求参数:'.json_encode($ts));
            get_date_dir($this->path,'card_coupon','核销','积分商城兑换失败，会员卡code:'.$card_code.',返回结果:'. $resu);
            
            $this->ajaxReturn(array("code" => "success", "msg" => '返还成功'));
        }else{
            $this->ajaxReturn(array("code" => "error", "msg" => "返还失败！","data"=>$res['errmsg']));
        } 

    }


    /**
     * 立即兑换
     */
    public function trade_coupons()
    {
        $id = I('id'); //礼品劵id
        $url = I('url');
        $where['card_type'] = 'GIFT';
        $where['id'] = $id;
        $where['status'] = array('in','3,4');
        $where['on_status'] = array('in','1,2');
        $data = $this->details($where);
        if(!$data){
            $this->ajaxReturn(array("code" => "error", "msg" => "无法兑换！请稍后再试！"));
        }
        if($data['quantity']<=0){
            $this->ajaxReturn(array("code" => "error", "msg" => "积分券库存不足"));
        }
        $time = time();
        if($data['on_status']==2){
            if($time<=$data['begin_on_timestamp']||$time>$data['end_on_timestamp']){
                $this->ajaxReturn(array("code" => "error", "msg" => "该积分券商品不在兑换时效内"));  
            }
        }
        if($data['status']==5){
            $this->ajaxReturn(array("code" => "error", "msg" => "该积分券商品已删除"));  
            
        }
        if($data['on_status']==3){
            $this->ajaxReturn(array("code" => "error", "msg" => "该积分券商品已下架"));  
            
        }
        if($data['type']=='DATE_TYPE_FIX_TIME_RANGE'){
                if($time<=$data['begin_timestamp']||$time>$data['end_timestamp']){
                    $this->ajaxReturn(array("code" => "error", "msg" => "该积分商品不在有效期内！"));  //该积分商品不在有效期内
                }
            }
        $card_id = cookie('card_id');
        $openid = cookie('openid');
        $memuse = $this->memcard_use_Model->where(array('status'=>1,'card_id'=>$card_id,'fromname'=>$openid))->field('memcard_id,fromname,card_balance')->find();
        // echo $this->memcard_use_Model->_sql();
        if((int)$data['integral']>(int)$memuse['card_balance']){
            $this->ajaxReturn(array("code" => "error", "msg" => "非常遗憾！您的积分不足！")); 
        }
        $count = $this->user_coupons->where(array('fromname'=>$openid,'coupon_id'=>$id))->count();
        if($count>=(int)$data['get_limit']){
            $this->ajaxReturn(array("code" => "error", "msg" => "无法兑换！已达到该商品的个人兑换上限！"));
        }
        $cardid = $data['card_id'];
        $this->get_ticket($url,$cardid,$data['integral']);
    }

    /**
     * 获取api_ticket
     */
    private function get_ticket($js_url,$cardid,$integral)
    {
        //获取openid
        $openid = cookie('openid');
        $nonce_str = $this->generateNonceStr();
        $access_token = $this->get_token();
        $api_ticket = $this->getapiTicket($access_token);
        $jsapi_ticket = $this->getJsapiTicket($access_token);
        $timestamp = time();
        $card_id=cookie('card_id');;
        $arr = array($cardid,$api_ticket,$nonce_str,$timestamp);//组装参数
        asort($arr, SORT_STRING);
        $sortString = "";
        foreach($arr as $temp){
            $sortString = $sortString.$temp;
        }
        $signature = sha1($sortString);
        $js_signature = $this->getJsSign($jsapi_ticket,$js_url, $timestamp, $nonce_str);
        $result = array('js_signature'=>$js_signature,'signature'=>$signature,'nonce_str'=>$nonce_str,'timestamp'=>$timestamp,'card_id'=>$cardid,'api_ticket'=>$api_ticket,'jsapi_ticket'=>$jsapi_ticket,'js_url'=>$js_url);
        $card_code = cookie('code');
        $ts['code'] = urlencode($card_code);//卡号
        $ts['card_id'] = urlencode($card_id);//卡id
        $ts["add_bonus"] = urlencode('-'.$integral);//增加的积分，负数为减
        $ts["record_bonus"] = urlencode('积分券兑换使用积分');//增加的积分，负数为减
        $resu = request_post('https://api.weixin.qq.com/card/membercard/updateuser?access_token='.$access_token,urldecode(json_encode($ts)));
        $res = json_decode($resu,true);
        if($res['errcode']==0){
            //增加领取记录
            M("screen_memcard_use")->where("card_code='$card_code'")->setDec('card_balance',$integral);
            $card_balance = M("screen_memcard_use")->where("card_code='$card_code'")->getField('card_balance');
            M('screen_memcard_log')->add(array('add_time' => time(), 'update_time' => time(), 'value' => '-'.$integral, 'balance' => $card_balance, 'ts' => json_encode($ts), 'code' => $card_code,'ts_status'=>1,'msg'=>$resu,'record_bonus'=>'积分商城兑换'));
            //成功
            $this->path = $_SERVER['DOCUMENT_ROOT'] . '/data/log/member/';
            get_date_dir($this->path,'card_coupon','核销','积分券兑换使用，会员卡code:'.$card_code.',使用积分:'.$integral);
            get_date_dir($this->path,'card_coupon','核销','积分券兑换使用，会员卡code:'.$card_code.',请求参数:'.json_encode($ts));
            get_date_dir($this->path,'card_coupon','核销','积分券兑换使用，会员卡code:'.$card_code.',返回结果:'. $resu);
            
            $this->ajaxReturn(array("code" => "success", "msg" => $result));
        }else{
            $this->ajaxReturn(array("code" => "error", "msg" => "无法兑换！请稍后再试！","data"=>$res['errmsg']));
        }
    }

    private function get_ticket1()
    {
        //获取openid
        $openid = cookie('openid');
        $nonce_str = $this->generateNonceStr();
        $access_token = $this->get_token();
        $api_ticket = $this->getapiTicket($access_token);
        $timestamp = time();
        $card_id=cookie('card_id');
        $arr = array($card_id,$api_ticket,$nonce_str,$timestamp);//组装参数
        asort($arr, SORT_STRING);
        // dump($arr);
        $sortString = "";
        foreach($arr as $temp){
            $sortString = $sortString.$temp;
        }
        $signature = sha1($sortString);
        $data = array('signature'=>$signature,'nonce_str'=>$nonce_str,'timestamp'=>$timestamp,'card_id'=>$card_id,'api_ticket'=>$api_ticket);
        // dump($signature);=
        $this->ajaxReturn(array("code" => "success", "msg" => $data));
        
    }

    //该方法用于获取和全局缓存微信JS-SDK使用权限签名算法需要的jsapi_ticket
    private function getJsapiTicket($access_token)
    {
        //我们将jsapi_ticket全局缓存在文件中,每次获取的时候,先判断是否过期,如果过期重新获取再全局缓存
        //我们缓存的在文件中的数据，包括jsapi_ticket和该jsapi_ticket的过期时间戳.
        //获取缓存的access_token
        // F('jsapi_ticket',NULL);
        $jsapi_ticket_data = json_decode(F('jsapi_ticket'),true);
        //判断缓存的access_token是否存在和过期，如果不存在和过期则重新获取.
        if($jsapi_ticket_data !== null && $jsapi_ticket_data['ticket'] && $jsapi_ticket_data['expires_in'] > time()){

            return $jsapi_ticket_data['ticket'];

        }else{
            //重新获取jsapi_ticket,并全局缓存
            $curl = curl_init();

            //getAccessToken()函数获取js-sdk需要的access_token,我在上一篇博客中写到过.
            curl_setopt($curl,CURLOPT_URL,'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token='.$access_token.'&type=jsapi');

            curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);

            //获取jsapi_ticket
            $data = json_decode(curl_exec($curl),true);
            if($data != null && $data['ticket']){
                //设置jsapi_ticket的过期时间,有效期是7200s
                $data['expires_in'] = $data['expires_in'] + time();

                //将jsapi_ticket全局缓存，快速缓存到文件中.
                F('jsapi_ticket',json_encode($data));

                //返回jsapi_ticket
                return $data['ticket'];

            }else{
                exit('微信获取jsapi_ticket失败');
            }
        }
    }

    //该方法用于获取和全局缓存微信JS-SDK使用权限签名算法需要的api_ticket
    private function getapiTicket($access_token)
    {
        //我们将api_ticket全局缓存在文件中,每次获取的时候,先判断是否过期,如果过期重新获取再全局缓存
        //我们缓存的在文件中的数据，包括api_ticket和该api_ticket的过期时间戳.
        //获取缓存的access_token
        // F('api_ticket',NULL);
        $api_ticket_data = json_decode(F('api_ticket'),true);
        //判断缓存的access_token是否存在和过期，如果不存在和过期则重新获取.
        if($api_ticket_data !== null && $api_ticket_data['ticket'] && $api_ticket_data['expires_in'] > time()){

            return $api_ticket_data['ticket'];

        }else{
            //重新获取jsapi_ticket,并全局缓存
            $curl = curl_init();

            //getAccessToken()函数获取js-sdk需要的access_token,我在上一篇博客中写到过.
            curl_setopt($curl,CURLOPT_URL,'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token='.$access_token.'&type=wx_card');

            curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);

            //获取jsapi_ticket
            $data = json_decode(curl_exec($curl),true);
            if($data != null && $data['ticket']){
                //设置jsapi_ticket的过期时间,有效期是7200s
                $data['expires_in'] = $data['expires_in'] + time();

                //将jsapi_ticket全局缓存，快速缓存到文件中.
                F('api_ticket',json_encode($data));

                //返回jsapi_ticket
                return $data['ticket'];

            }else{
                exit('微信获取jsapi_ticket失败');
            }
        }
    }

    /*
     * 获取url
     * url（当前网页的URL，不包含#及其后面部分）
     * */
    private function getUrl()
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        return $url;
    }

    //获得access_token
    private function get_token()
    {
        $token = M('weixin_token')->where('type',1)->find();
        $time = time();
        if(empty($token) || $token['a_time']+5000<$time){
            //获取token
            $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.self::$appid.'&secret='.self::$secret;
            $token = request_post($url);
            $token = json_decode($token,true);
            $token = $token['access_token'];
            $token&&M('weixin_token')->where('type','1')->save(array('access_token'=>$token,'a_time'=>$time));
        }else{
             $token = $token['access_token'];
        }
        return $token;
    }

    /**
     * 获取jsapi_ticket签名
     * @param  [type] $jsapi_ticket [description]
     * @param  [type] $url          [description]
     * @param  [type] $timestamp    [description]
     * @param  [type] $noncestr     [description]
     * @return [type]               [description]
     */
    private function getJsSign($jsapi_ticket,$url, $timestamp, $noncestr)
    {
        if (!$timestamp)
            $timestamp = time();
        if (!$noncestr)
            $noncestr = $this->generateNonceStr();
        $ret = strpos($url,'#');
        if ($ret)
            $url = substr($url,0,$ret);
        $url = trim($url);
        if (empty($url))
            return false;
        $arrdata = array("timestamp" => $timestamp, "noncestr" => $noncestr, "url" => $url, "jsapi_ticket" => $jsapi_ticket);
        ksort($arrdata);
        $paramstring = "";
        foreach($arrdata as $key => $value){
            if(strlen($paramstring) == 0)
                $paramstring .= $key . "=" . $value;
            else
                $paramstring .= "&" . $key . "=" . $value;
        }
        // echo $paramstring;
        //sapi_ticket=IpK_1T69hDhZkLQTlwsAX21GLyhCd6BkOP8tZXRxbipNpgiGbl3LAu4cr3pr-cjKV2u1k5_kylP04gNPkQ-LQQ&noncestr=8EZfLGECycFja8JU×tamp=1521008689&url=http://sy.youngport.com.cn/Api/integral/coupons_detail/id/630
        $sign = sha1($paramstring);
        if (!$sign)
            return false;
        return $sign;

    }
    
    /**
     * 随机字符串
     * @param  integer $length 字符串长度
     * @return string       noncestr字符串
     */
    private function generateNonceStr($length=16)
    {
        // 密码字符集，可任意添加你需要的字符
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for($i = 0; $i < $length; $i++){
            $str .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $str;
    }

    /**
     * 获取商家ID
     * @Param uid 商家uid
     */
    private function _get_mch_id($uid)
    {
        $id = $this->merchants->where(array('uid'=>$uid,'status'=>1))->getField('id');
        return $id;
    }

    /**
     * 获取代理商ID
     * @Param uid 商家uid
     */
    private function _get_agent_id($uid)
    {
        $id = $this->merchants_agent->where(array('uid'=>$uid))->getField('id');
        return $id;
    }

    /**
     * 获取礼品卷列表
     */
    private function get_coupons($where)
    {
        
        $field = 'id,on_status,begin_on_timestamp,end_on_timestamp,type,begin_timestamp,end_timestamp,use_merchant,fixed_term,integral,title,color,description,base_url';
        $coupons = $this->coupons->where($where)->field($field)->select();
        // M()->query("SELECT * FROM 'ypt_screen_user_coupons' WHERE mid=$mid and agent_id=0 and card_code='GIFT'");
        // echo M()->getlastSql();
        $data = array();
        $time = time();
        foreach ($coupons as $key => $value) {
            $picture = $value['base_url'];
            if(preg_match("/\x20*https?\:\/\/.*/i",$value['base_url'])){
                $value['base_url'] = $picture;
            }else{
                $value['base_url'] = 'http://agent.youngport.com.cn'.$picture;
            }
            //上架状态
            if($value['on_status']==1){
                if($value['type']=='DATE_TYPE_FIX_TIME_RANGE'){
                    //DATE_TYPE_FIX_TIME_RANGE 表示固定日期区间
                    if($time>$value['begin_timestamp']&&$time<=$value['end_timestamp']){
                        $value['date_due'] = '有效期至'.date('Y-m-d',$value['end_timestamp']);
                        $data[]=$value;
                    }else{
                        $this->coupons->where(array('id'=>$value['id']))->setField('on_status',3);
                    }
                    
                }elseif($value['type']=='DATE_TYPE_FIX_TERM'){
                    //DATE_TYPE_FIX_TERM表示固定时长（自领取后按天算
                    $value['date_due'] = '有效期'.$value['fixed_term'].'天内';
                    $data[]=$value;
                }
                
            }elseif($value['on_status']==2){
                if($time>$value['begin_on_timestamp']&&$time<=$value['end_on_timestamp']){
                    if($value['type']=='DATE_TYPE_FIX_TIME_RANGE'){
                        //DATE_TYPE_FIX_TIME_RANGE 表示固定日期区间
                        if($time>$value['begin_timestamp']&&$time<=$value['end_timestamp']){
                            $value['date_due'] = '有效期至'.date('Y-m-d',$value['end_timestamp']);
                            $data[]=$value;
                        }else{
                            $this->coupons->where(array('id'=>$value['id']))->setField('on_status',3);
                        }
                    }elseif($value['type']=='DATE_TYPE_FIX_TERM'){
                        //DATE_TYPE_FIX_TERM表示固定时长（自领取后按天算
                        $value['date_due'] = '有效期'.$value['fixed_term'].'天内';
                        $data[]=$value;
                    }
                    
                }
            }

        }
        return $data; 
    }

    /**
     * 查看礼品卷详情
     */
    private function details($where)
    {
        
        $field = 'id,mid,agent_id,on_status,begin_on_timestamp,end_on_timestamp,type,begin_timestamp,end_timestamp,use_merchant,fixed_term,integral,title,color,description,base_url,quantity,get_limit,card_id';
        $coupons = $this->coupons->where($where)->field($field)->find();
        // M()->query("SELECT * FROM 'ypt_screen_user_coupons' WHERE mid=$mid and agent_id=0 and card_code='GIFT'");
        // echo M()->getlastSql();
        $time = time();
        $picture = $coupons['base_url'];
        if(preg_match("/\x20*https?\:\/\/.*/i",$coupons['base_url'])){
            $coupons['base_url'] = $picture;
        }else{
            $coupons['base_url'] = 'http://agent.youngport.com.cn'.$picture;
        }
        //上架状态
        if($coupons['on_status']==1){
            if($coupons['type']=='DATE_TYPE_FIX_TIME_RANGE'){
                //DATE_TYPE_FIX_TIME_RANGE 表示固定日期区间
                $coupons['date_due'] = '有效期至'.date('Y-m-d',$coupons['end_timestamp']);
            }elseif($coupons['type']=='DATE_TYPE_FIX_TERM'){
                //DATE_TYPE_FIX_TERM表示固定时长（自领取后按天算
                $coupons['date_due'] = '有效期'.$coupons['fixed_term'].'天内';
            }
        }elseif($coupons['on_status']==2){
            if($time>$coupons['begin_on_timestamp']&&$time<=$coupons['end_on_timestamp']){
                if($coupons['type']=='DATE_TYPE_FIX_TIME_RANGE'){
                    //DATE_TYPE_FIX_TIME_RANGE 表示固定日期区间
                    $coupons['date_due'] = '有效期至'.date('Y-m-d',$coupons['end_timestamp']);
                }elseif($coupons['type']=='DATE_TYPE_FIX_TERM'){
                    //DATE_TYPE_FIX_TERM表示固定时长（自领取后按天算
                    $coupons['date_due'] = '有效期'.$coupons['fixed_term'].'天内';
                }
            }
        } 
        
        return $coupons; 
    }

    
}