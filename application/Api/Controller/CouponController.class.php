<?php
namespace Api\Controller;
use Common\Controller\ApibaseController;

class CouponController extends ApibaseController
{
    public $coupons;
    public $merchants;
    public $host;
    public $mid;

    function _initialize()
    {
        parent::_initialize();
        $this->host = 'http://' . $_SERVER['HTTP_HOST'];
        $this->coupons=M("screen_coupons");
        $this->merchants=M("merchants");
        $this->mid=$this->get_merchant($this->userInfo['uid']);

    }

    public function check()
    {
        var_dump($this->mid);exit;
        $this->ajaxReturn(213);
    }

//    优惠券
    public function coupon_detail()
    {
        $mid=$this->mid;
        $coupons=$this->coupons->where(array('mid'=>$mid,'card_type'=>'GENERAL_COUPON'))->order("create_time desc")->select();
        $data=array();
        foreach ($coupons as $k=>$v)
        {
            if($v['end_timestamp'] < time()){
                $this->trash_coupon_fn($v['id']);
            }
            $data[$k]['card'] = $v['id'];
            $data[$k]['indate'] =$this->get_time_detail($v);
            $data[$k]['title'] = $v['title'];
            $data[$k]['color'] = $v['color'];
            $data[$k]['status'] =$v['status'];
            $data[$k]['date_status'] =$this->get_data_status($v);
            $data[$k]['quantity'] = $v['quantity'];
            $data[$k]['quan_detail'] = $this->get_quantity($v['id']);
            $data[$k]['content'] = $this->get_pay_content($v);
            $data[$k]['de_price'] = floatval($v['de_price']);
            $data[$k]['total_price'] = floatval($v['total_price']);
            $data[$k]['auto_price'] = floatval($v['auto_price']);
            $data[$k]['begin_timestamp']=date("Y-m-d",$v['begin_timestamp']);
            $data[$k]['end_timestamp']=date("Y-m-d",$v['end_timestamp']);
//            $data[$k]['base_url']=$this->get_base_url($v);
            $data[$k]['base_url'] =$this->host.$v['base_url'];
            $data[$k]['style']=$this->get_style($v);
            $data[$k]['is_barcode']=$v['is_barcode'];
            $data[$k]['is_cashier']=$v['is_cashier'];
            $data[$k]['is_auto']=$v['is_auto'];
            $data[$k]['is_miniapp']=$v['is_miniapp'];
            $data[$k]['can_give_friend']=$v['can_give_friend'];

        }
        $this->ajaxReturn(array("code" => "success","msg"=>"成功", "data"=>$data));
    }

//    查看优惠券详情
    public function coupon_one_detail()
    {
        $mid=$this->mid;
        $card=I("card");
        $coupon=$this->coupons->where("id=$card")->find();
        $merchant=$this->merchants->where("id=$mid")->find();
        if(!$coupon){
            $this->ajaxReturn(array("code" => "errror","msg"=>"失败", "data"=>"获取优惠券失败"));
        }
        if(!$merchant){
            $this->ajaxReturn(array("code" => "errror","msg"=>"失败", "data"=>"获取用户失败"));
        }
        $data=array();
        $data['logo_url'] =$this->host.$merchant['base_url'];
        $data['brand_name'] =$coupon['brand_name'];
        $data['card'] = $coupon['id'];
        $data['indate'] =$this->get_time_detail($coupon);
        $data['title'] = $coupon['title'];
        $data['color'] = $coupon['color'];
        $data['status'] = $coupon['status'];
        $data['quantity'] = $this->get_quantity($card);
        $data['content'] = $this->get_pay_content($coupon);
        $data['de_price'] = $coupon['de_price'];
        $data['total_price'] = $coupon['total_price'];
        $data['description'] = $coupon['description'];
        $data['begin_timestamp']=date("Y-m-d",$coupon['begin_timestamp']);
        $data['end_timestamp']=date("Y-m-d",$coupon['end_timestamp']);
        $data['service_phone'] = $coupon['service_phone'];
        $data['can_give_friend']=$coupon['can_give_friend'];

        $this->ajaxReturn(array("code" => "success","msg"=>"成功", "data"=>$data));
    }
//    新建优惠券
    public function create_coupon()
    {
        $mid=$this->mid;
        if(!I("title",'trim'))$this->ajaxReturn(array("code" => "error","msg"=>"失败", "data"=>"没有填写优惠券标题"));
        $map['title']=I("title");
        $map['mid']=$mid;
        $map['status']=3;
        if($this->coupons->where($map)->find()){
            $this->ajaxReturn(array("code" => "error","msg"=>"不能重复添加"));
        }
        if(!I("brand_name",'','trim'))$this->ajaxReturn(array("code" => "error","msg"=>"没有填写商户简称"));
        //if(!I("color",'','trim'))$this->ajaxReturn(array("code" => "error","msg"=>"失败", "data"=>"没有选择背景颜色"));
        if(!I("de_price",'','trim'))$this->ajaxReturn(array("code" => "error","msg"=>"没有填写优惠券面值"));
        if(!I("total_price",'','trim'))$this->ajaxReturn(array("code" => "error","msg"=>"没有填写最低消费值"));
        if(!I("begin_timestamp",'','trim'))$this->ajaxReturn(array("code" => "error","msg"=>"没有填写开始时间"));
        if(!I("end_timestamp",'','trim'))$this->ajaxReturn(array("code" => "error","msg"=>"没有填写结束时间"));
        if(!I("quantity",'','trim'))$this->ajaxReturn(array("code" => "error","msg"=>"没有填写库存"));
//        if(mb_strlen(I("brand_name",'','trim'), 'utf8') > 12)$this->ajaxReturn(array("code" => "error", "msg" => "商户简称不能超过12个汉字"));
        if(mb_strlen(I("title",'','trim'), 'utf8') > 9)$this->ajaxReturn(array("code" => "error", "msg" => "优惠券标题不能超过9个汉字"));
        if(I("de_price",'','trim')-I("total_price",'','trim') > 0){
            $this->ajaxReturn(array("code" => "error","msg"=>"卡券的面值不能大于最低消费值"));
        }
        if ($_FILES) {
            $upload = new \Think\Upload();
            $upload->maxSize = 3145728;
            $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
            $upload->rootPath = C('_WEB_UPLOAD_');
            $upload->savePath = 'coupons/';
            $upload->saveName = uniqid();//保持文件名不变
            $info = $upload->upload();
            if (!$info)$this->ajaxReturn(array("code" => "error","msg"=>"上传图片失败"));
            header("Content-type:text/html;charset=utf-8");
            $url='/data/upload/'.$info['logo']['savepath'].$info['logo']['savename'];
            $arr=array();
            $arr['buffer']='@'.$_SERVER['DOCUMENT_ROOT'].$url;
            $url_getlog="https://api.weixin.qq.com/cgi-bin/media/uploadimg?access_token=".get_weixin_token();
            $result = request_post($url_getlog, $arr);
            $this->writeLog('coupon.log','上传logo',$result,0);
            $result = json_decode($result, true);
            $logo_url=$result['url'];
            $base_url=$url;
        }else{
            $logo=I("logo");
            $logo_url=M("merchants")->where("id=$mid")->getField("logo_url");
            $base_url=M("merchants")->where("id=$mid")->getField("base_url");
        }
//        var_dump($logo_url);exit;
        if(!$logo_url)$logo_url = "http://mmbiz.qpic.cn/mmbiz_png/XgCbCud1UyzTfPXJ3bIXkCtgUp6j207QY7VZggu5NexsAJhEGOK92rSVRTr6fGO2fUw9t0mQPRKXHLcn0a2PJA/0";
        $code_type="CODE_TYPE_ONLY_QRCODE";
        $brand_name = M("merchants")->where("id=$mid")->getField("merchant_jiancheng"); //店铺简称
        if(!$brand_name) $brand_name = M("merchants")->where("id=$mid")->getField("merchant_name");
        $color=I("color",'Color100');
        $title=I("title");
        $share=I('share',1);
        if($share==1){
            $can_give_friend=false;
        }else if($share==2){
            $can_give_friend=true;
        }
        $quantity=I("quantity");//库存
        $description=I("description");
        $begin_timestamp=$this->time_transform(I("begin_timestamp"));
        $end_timestamp=$this->time_transform(I("end_timestamp"))+60*60*24-1;
        if((int)$end_timestamp <time()){
            $this->ajaxReturn(array("code" => "error","msg"=>"结束时间不能小于当前时间"));
        }
        $service_phone =I("service_phone")?I("service_phone"):"";
        $total_price=I("total_price");
        $de_price=I("de_price");
        $default_detail= "满".$total_price."元减".$de_price."元";
        $notice="请向店员出示二维码";
        $type="DATE_TYPE_FIX_TIME_RANGE";
//        $use_custom_code=false;
        $get_limit=1;
        $can_share=	false;
//        $can_give_friend= false;
        $kqinfo = array("card" => array());
        $kqinfo['card']['card_type'] = 'GENERAL_COUPON';
        $kqinfo['card']['general_coupon'] = array('base_info' => array(),'default_detail'=>array());
        $kqinfo['card']['general_coupon']['base_info']['logo_url'] = $logo_url;
        $kqinfo['card']['general_coupon']['base_info']['code_type'] = $code_type;
        $kqinfo['card']['general_coupon']['base_info']['brand_name'] = urlencode($brand_name);
        $kqinfo['card']['general_coupon']['base_info']['title'] = urlencode($title);
        $kqinfo['card']['general_coupon']['base_info']['color'] = $color;
        $kqinfo['card']['general_coupon']['base_info']['service_phone'] = $service_phone;
        $kqinfo['card']['general_coupon']['base_info']['notice'] = urlencode($notice);
        $kqinfo['card']['general_coupon']['base_info']['description'] = urlencode($description);
        $kqinfo['card']['general_coupon']['base_info']['date_info']['type'] = $type;
        $kqinfo['card']['general_coupon']['base_info']['date_info']['begin_timestamp'] = "$begin_timestamp";
        $kqinfo['card']['general_coupon']['base_info']['date_info']['end_timestamp'] = "$end_timestamp";
        $kqinfo['card']['general_coupon']['base_info']['sku']['quantity'] = $quantity;
        $kqinfo['card']['general_coupon']['base_info']['can_give_friend'] =$can_give_friend;
        $kqinfo['card']['general_coupon']['base_info']['get_limit'] =1;//每个人领取限制
        $kqinfo['card']['general_coupon']['base_info']['can_share']=$can_share;
        $kqinfo['card']['general_coupon']['default_detail']= urlencode($default_detail);
        $data=urldecode(json_encode($kqinfo));
        $token=get_weixin_token();
        $url_merchant="https://api.weixin.qq.com/card/create?access_token=$token";
        $this->writeLog('coupon.log','创建优惠券',$data,0);
        $result = request_post($url_merchant, $data);
        $result = object2array(json_decode($result));
        if($result['errmsg'] == 'ok' && $result['errcode'] == 0){
            $this->writeLog('coupon.log','创建成功',$result);
            $card_id=$result['card_id'];
        }else{
            $this->writeLog('coupon.log','创建失败',$result);
            $this->ajaxReturn(array("code" => "error","msg"=>"失败", "data"=>"添加失败"));
        }
        $map['mid']=$mid;
        $map['card_type']='GENERAL_COUPON';
        $map['code_type']=$code_type;
        $map['brand_name']=$brand_name;
        $map['code_type']=$code_type;
        $map['color']=$color;
        $map['title']=$title;
        $map['quantity']=$quantity;
        $map['description']=$description;
        $map['begin_timestamp']=$begin_timestamp;
        $map['end_timestamp']=$end_timestamp;
        $map['service_phone']=$service_phone;
        $map['notice']=$notice;
        $map['type']=$type;
        $map['get_limit']=$get_limit;
        $map['can_share']=$can_share;
        $map['can_give_friend']=$share;
        $map['total_price']=$total_price;
        $map['de_price']=$de_price;
        $map['card_id']=$card_id;
        $map['status']=3;
        $map['base_url']=$base_url;
        $map['create_time']=time();
        if(M("screen_coupons")->add($map)){
            if($base_url&&$this->merchants->where("id=$mid")->find()){
                $this->merchants->where("id=$mid")->save(array("base_url" =>$base_url,"logo_url"=>$logo_url));
            }
            $this->ajaxReturn(array("code" => "success","msg"=>"成功", "data"=>"恭喜你添加成功"));
        }else{
            $this->ajaxReturn(array("code" => "error","msg"=>"失败", "data"=>"添加失败"));
        };
    }

//    编辑优惠券
    function edit_coupon()
    {
        $card=I("card");
        $mid=$this->mid;
//        if (mb_strlen(I("brand_name",'trim'), 'utf8') > 12)$this->ajaxReturn(array("code" => "error", "msg" => "商户简称不能超过12个汉字"));
        if (mb_strlen(I("title",'trim'), 'utf8') > 9)$this->ajaxReturn(array("code" => "error", "msg" => "优惠券标题不能超过9个汉字"));
        $coupon=$this->coupons->where("id='$card'")->find();
        $card_id=$coupon['card_id'];
        $quantity_old=$coupon['quantity'];
        $begin_timestamp_old=$coupon['begin_timestamp'];
        $end_timestamp_old=$coupon['end_timestamp'];
        $token=get_weixin_token();
        if ($_FILES) {
            $upload = new \Think\Upload();
            $upload->maxSize = 3145728;
            $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
            $upload->rootPath = C('_WEB_UPLOAD_');
            $upload->savePath = 'coupons/';
            $upload->saveName = uniqid;//保持文件名不变
            $info = $upload->upload();
            if (!$info)$this->ajaxReturn(array("code" => "error","msg"=>"失败", "data"=>"上传图片失败"));
            header("Content-type:text/html;charset=utf-8");
            $url='/data/upload/'.$info['logo']['savepath'].$info['logo']['savename'];
            $arr=array();
            $arr['buffer']='@'.$_SERVER['DOCUMENT_ROOT'].$url;
            $url_getlog="https://api.weixin.qq.com/cgi-bin/media/uploadimg?access_token=".get_weixin_token();
            $result = request_post($url_getlog, $arr);
            $this->writeLog('coupon.log','上传logo',$result,0);
            $result = json_decode($result, true);
            $logo_url=$result['url'];
            $base_url=$url;
        }else{
            $logo=I("logo");
            $logo_url=M("merchants")->where("id=$mid")->getField("logo_url");
        }
        if(!$logo_url)$logo_url = "http://mmbiz.qpic.cn/mmbiz_png/XgCbCud1UyzTfPXJ3bIXkCtgUp6j207QY7VZggu5NexsAJhEGOK92rSVRTr6fGO2fUw9t0mQPRKXHLcn0a2PJA/0";
        $color=I("color")?I("color"):'Color100';
        $share=I('share')?I("share"):1;
        if($share==1){
            $can_give_friend=false;
        }else if($share==2){
            $can_give_friend=true;
        }
        $brand_name=M("merchants")->where("id=$mid")->getField("merchant_jiancheng"); //店铺简称
        $title=I("title");
        $description=I("description");
        $service_phone=I("service_phone");
        $type="DATE_TYPE_FIX_TIME_RANGE";
        $begin_timestamp=$this->time_transform(I("begin_timestamp"));
        $end_timestamp=$this->time_transform(I("end_timestamp"));
        if((int)$end_timestamp <time()){
            $this->ajaxReturn(array("code" => "error","msg"=>"失败", "data"=>"结束时间不能小于当前时间"));
        }
        if($begin_timestamp - $begin_timestamp_old >0){
            $this->ajaxReturn(array("code" => "error","msg"=>"失败", "data"=>"开始不能大于原来时间"));
        }
        if($end_timestamp -$end_timestamp_old<0){
            $this->ajaxReturn(array("code" => "error","msg"=>"失败", "data"=>"结束时间不能小于原来结束时间"));
        }
        $kqinfo['card_id'] =$card_id;
        $kqinfo['general_coupon'] = array('base_info' => array());
        $kqinfo['general_coupon']['base_info']['logo_url'] = $logo_url;
        $kqinfo['general_coupon']['base_info']['title'] = urlencode($title);
        $kqinfo['general_coupon']['base_info']['color'] = $color;
        $kqinfo['general_coupon']['base_info']['service_phone'] = $service_phone;
        $kqinfo['general_coupon']['base_info']['description'] = urlencode($description);
        $kqinfo['general_coupon']['base_info']['date_info']['type'] = $type;
        $kqinfo['general_coupon']['base_info']['date_info']['begin_timestamp'] = $begin_timestamp;
        $kqinfo['general_coupon']['base_info']['date_info']['end_timestamp'] = $end_timestamp;
        $kqinfo['general_coupon']['base_info']['can_give_friend'] =$can_give_friend;

//        $kqinfo['general_coupon']['base_info']['can_share']=$can_share;
        $data=urldecode(json_encode($kqinfo));
//        编辑优惠券
        $url_edit_copoun="https://api.weixin.qq.com/card/update?access_token=".$token;
        $this->writeLog('coupon.log','编辑优惠券',$data,0);
        $edit_copoun= request_post($url_edit_copoun,$data);
        $this->writeLog('coupon.log','编辑结果',$edit_copoun,0);
        $edit_copoun=json_decode($edit_copoun);
        if($edit_copoun->errmsg !="ok"){
            $this->ajaxReturn(array("code" => "error","msg"=>"失败", "data"=>"编辑失败"));
        }
//        查看 优惠券详情
        $card_detail=$this->get_card_detatil($card_id);
        $this->writeLog('coupon.log','查询结果',$card_detail,0);
        $card_detail=json_decode($card_detail);
        if($card_detail->errmsg =="ok"){
            $status=$this->get_card_status($card_detail);
            $this->coupons->where("id=$card")->save(array('status'=>$status));
        }else{
            $this->ajaxReturn(array("code" => "error","msg"=>"编辑失败"));
        }

        $quantity_new=I("quantity");
        $map['card_id']=$card_id;
        if($quantity_old !== $quantity_new)
        {
            if($quantity_old > $quantity_new){
                $map['reduce_stock_value'] =$quantity_old -$quantity_new;
            }else{
                $map['increase_stock_value'] =$quantity_new-$quantity_old;
            }
//            修改优惠券
            $url_change_quantity="https://api.weixin.qq.com/card/modifystock?access_token=".$token;
            $this->writeLog('coupon.log','修改库存',$map);
            $change_quantity= request_post($url_change_quantity,json_encode($map) );
            $this->writeLog('coupon.log','修改库存结果',$change_quantity,0);
            $change_quantity=json_decode($change_quantity);
            if($change_quantity->errmsg !="ok"){
                $this->ajaxReturn(array("code" => "error","msg"=>"编辑失败"));
            }
        }
        $map['can_give_friend'] = $share;
        $cou=array();
        $cou['brand_name']=$brand_name;
        $cou['color']=$color;
        $cou['share']=$share;
        $cou['title']=$title;
        $cou['type']=$type;
        $cou['begin_timestamp']=$begin_timestamp;
        $cou['end_timestamp']=$end_timestamp;
        $cou['service_phone']=$service_phone;
        $cou['description']=$description;
        $cou['total_price']=I("total_price");
        $cou['de_price']=I("de_price");
        $cou['base_url']=$base_url;
        $cou['quantity']=$quantity_new;
        if($this->coupons->where("card_id='$card_id'")->find())$this->coupons->where("card_id='$card_id'")->save($cou);
        $this->ajaxReturn(array("code" => "success","msg"=>"成功", "data"=>"编辑成功"));
    }
//   编辑优惠券库存
    public function edit_coupon_quantity()
    {
        $card=I("card");
        $quantity_new=I("quantity");
        $coupon=$this->coupons->where("id='$card'")->find();
        if(!$coupon)$this->ajaxReturn(array("code" => "error","msg"=>"EOF"));
        if(!$quantity_new)$this->ajaxReturn(array("code" => "error","msg"=>"未填写库存"));
        $token=get_weixin_token();
        $quantity_old=$coupon['quantity'];
        $card_id=$coupon['card_id'];
        $map['card_id']=$card_id;
        if($quantity_old !== $quantity_new)
        {
            if($quantity_old > $quantity_new){
                $map['reduce_stock_value'] =$quantity_old -$quantity_new;
            }else{
                $map['increase_stock_value'] =$quantity_new-$quantity_old;
            }
//            修改优惠券
            $url_change_quantity="https://api.weixin.qq.com/card/modifystock?access_token=".$token;
            $this->writeLog('coupon.log','修改库存',$map);
            $change_quantity= request_post($url_change_quantity,json_encode($map) );
            $this->writeLog('coupon.log','修改库存结果',$change_quantity,0);
            $change_quantity=json_decode($change_quantity);
            if($change_quantity->errmsg !="ok"){
                $this->ajaxReturn(array("code" => "error","msg"=>"编辑失败"));
            }else{
                if($this->coupons->where(array('id'=>$card))->find())$this->coupons->where(array('id'=>$card))->save(array('quantity'=>$quantity_new));
                $this->ajaxReturn(array("code" => "success","msg"=>"修改成功"));

            }
        }
    }

//  生成优惠券二位码@2
    public function card_pull()
    {
        $card=I("card");
        $card_id=$this->coupons->where("id='$card'")->getField("card_id");
        $quantity=$this->coupons->where("id='$card'")->getField("quantity");
        if($quantity <= 0){
            $this->ajaxReturn(array("code" =>"error" ,"msg"=>"库存已用完"));
        }
        if(!$card_id){
            $this->ajaxReturn(array("code" =>"error" ,"msg"=>"card_id不存在"));
        }
        $data = array(
            "action_name" => "QR_CARD",
            "action_info" => array(
                "card" => array(
                    "card_id" => "$card_id",
                    "is_unique_code" => false,
                    "outer_id" => 1
                )
            )
        );
      $url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=".get_weixin_token();
        $res = request_post($url,json_encode($data));
        $QRMeg = json_decode($res, true);
        $url=$QRMeg['url'];
        if($url){
            $this->ajaxReturn(array("code" => "success","msg"=>"成功", "data"=>$url));
        }else{
            $this->ajaxReturn(array("code" => "error","msg"=>"失败", "data"=>"生成二维码地址失败"));
        }
    }

//    获取商户logo
    public function logo_url()
    {
        $mid=$this->mid;
        $coupon=M("merchants")->where("id=$mid")->getField("base_url");
        if($coupon)$data['logo_url']=$this->host.$coupon;
        else {$data['logo_url']="";}
        $this->ajaxReturn(array("code" => "success","msg"=>"成功", "data"=>$data));
    }

//    二维码投放
    public function coupon_barcode_throw()
    {
        $mid=$this->mid;
        $card=I("card");
        if($this->coupons->where("id='$card'And mid='$mid'")->find())$this->coupons->where("id='$card'And mid='$mid'")->save(array("is_barcode" => 2));
        if($this->coupons->where("id!='$card'And mid='$mid' And is_auto=1")->find()){
            $this->coupons->where("id!='$card'And mid='$mid'")->save(array("is_barcode" => 1));
        }
        $card_id=$this->coupons->where("id='$card'")->getField("card_id");
        if(!$card_id){
            $this->ajaxReturn(array("code" =>"error" ,"msg"=>"失败", "data"=>"优惠券不存在"));
        }
        $quantity=$this->coupons->where("id='$card'")->getField("quantity");
        if($quantity <= 0){
            $this->ajaxReturn(array("code" =>"error" ,"msg"=>"库存已用完"));
        }
        $data = '{
        "action_name": "QR_CARD",
            "action_info":{
                "card":{
                    "card_id": "'.$card_id.'",
                }
            }
        }';
        $url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=".get_weixin_token();
        $this->writeLog('coupon.log','获取二维码',$data,0);
        $res = request_post($url, $data);
        $this->writeLog('coupon.log','获取二维码结果',$res,0);
        $QRMeg = json_decode($res, true);
        $url=$QRMeg['url'];
        if($url){
            $this->ajaxReturn(array("code" => "success","msg"=>"成功", "data"=>$url));
        }else{
            $this->ajaxReturn(array("code" => "error","msg"=>"失败", "data"=>"生成二维码地址失败"));
        }
    }

    //    收银台投放
    public function coupon_cashier_throw()
    {
        $mid=$this->mid;
        $card=I("card");
        $agency_business=M("merchants")->where("id=$mid")->getField("agency_business");
        $ab=$this->coupons->where("mid='$mid' And is_cashier =2")->count('id');
        if($ab == 5){$this->ajaxReturn(array("code" => "error","msg"=>"失败", "data"=>"双屏收银最多投入5个"));}
        if($agency_business ==1){
            $this->ajaxReturn(array("code" => "error","msg"=>"失败", "data"=>"还未开通双屏收银业务"));
        }
        if($agency_business ==2){
            if($this->coupons->where("id='$card'And mid='$mid'")->find())$this->coupons->where("id='$card'And mid='$mid'")->save(array("is_cashier" => 2));
            $this->ajaxReturn(array("code" => "success","msg"=>"成功", "data"=>"该优惠券收银台投放成功"));
        }
    }

// 自动投放
    public function coupon_auto_throw()
    {
        $mid=$this->mid;
        $card=I("card");
        $auto_price=I("price");
        /*if(!$auto_price){
            $this->ajaxReturn(array("code" =>"error" ,"msg"=>"未填写自动投放价格"));
        }*/
        if($this->coupons->where("id='$card'And mid='$mid'")->find()){
            $this->coupons->where("id='$card'And mid='$mid'")->save(array("auto_price"=>$auto_price));
        }
        $quantity=$this->coupons->where("id='$card'")->getField("quantity");
        if($quantity <= 0){
            $this->ajaxReturn(array("code" =>"error" ,"msg"=>"该优惠券库存用完"));
        }

        if($this->coupons->where("id='$card'And mid='$mid'")->find())$this->coupons->where("id='$card'And mid='$mid'")->save(array("is_auto" => 2));
        if($this->coupons->where("id!='$card'And mid='$mid'")->find())$this->coupons->where("id!='$card'And mid='$mid'")->save(array("is_auto" => 1));

        $card_id=$this->coupons->where("id='$card'")->getField("card_id");
        if(!$card_id){
            $this->ajaxReturn(array("code" => "失败","msg"=>"优惠券不存在"));
        }
        $this->ajaxReturn(array("code" => "success","msg"=>"自动投放成功"));
    }

    //    小程序投放
    public function coupon_miniapp_throw()
    {
        $mid=$this->mid;
        $card=I("card");
        $quantity=$this->coupons->where("id='$card'")->getField("quantity");
        if($quantity <= 0){
            $this->ajaxReturn(array("code" =>"error" ,"msg"=>"该优惠券库存用完"));
        }
        if($this->coupons->where("id='$card'And mid='$mid'")->find())$this->coupons->where("id='$card'And mid='$mid'")->save(array("is_miniapp" => 2));
        $this->ajaxReturn(array("code" => "success","msg"=>"小程序投放成功"));
    }
//  删除优惠券
    function trash_coupon()
    {
        $card=I("card");
        $card_id = $this->coupons->where("id=$card")->getField('card_id');
        if($card_id){
            $this->coupons->where("id=$card")->save(array("status" => 5,"is_barcode" =>1,"is_cashier"=>1,"is_auto"=>1));
            $this->wxdelete($card_id);
        }else{
            $this->ajaxReturn(array("code" => "error","msg"=>"失败", "data"=>"获取card失败"));
        }
        $this->ajaxReturn(array("code" => "success","msg"=>"成功", "data"=>"删除优惠券成功"));
    }
    protected function wxdelete($card_id)
    {
        $token = get_weixin_token();
        $url = "https://api.weixin.qq.com/card/delete?access_token=$token";
        $data = array('card_id'=>$card_id);
        $result = request_post($url,json_encode($data));

        file_put_contents('./data/log/weixin.log',date("Y-m-d H:i:s").'删除优惠券'.$result. PHP_EOL,FILE_APPEND | LOCK_EX);

    }
//  设置失效卡券
    function trash_coupon_fn($card)
    {
        if($this->coupons->where("id=$card")->find()){
            $this->coupons->where("id=$card")->save(array("status" => 5,"is_barcode" =>1,"is_cashier"=>1,"is_auto"=>1));
        }
    }

// 获得卡卷的颜色
    function get_color()
    {
        $color=array(
            array('name'=>'Color010','value'=>'#63b359'),
            array('name'=>'Color020','value'=>'#2c9f67'),
            array('name'=>'Color030','value'=>'#509fc9'),
            array('name'=>'Color040','value'=>'#5885cf'),
            array('name'=>'Color050','value'=>'#9062c0'),
            array('name'=>'Color060','value'=>'#d09a45'),
            array('name'=>'Color070','value'=>'#e4b138'),
            array('name'=>'Color080','value'=>'#ee903c'),
            array('name'=>'Color090','value'=>'#dd6549'),
            array('name'=>'Color100','value'=>'#cc463d'),
        );
        return $color;
    }

    /**
     * @param $card_type  $counpon
     * @return string  根据优惠卷的种类进行分类
     */
    function get_card_type($counpon)
    {
        switch ($counpon['card_type']){
            case "GROUPON":
                return "团购券";
            case "CASH":
                return "代金券";
            case "card_type":
                return "折扣劵";
            case "GIFT":
                return "礼品劵";
            case "GENERAL_COUPON":
                return "优惠卷";
            default:
                return "";
        }
    }

    /**
     * @param $counpon  当前id的数组
     * @return string   得到有效期
     */
    function get_time_detail($counpon)
    {
        if($counpon['type'] =="DATE_TYPE_FIX_TIME_RANGE"){
            $begin_timestamp=date("Y-m-d",$counpon['begin_timestamp']);
            $end_timestamp=date("Y-m-d",$counpon['end_timestamp']);
            $indate=$begin_timestamp."至".$end_timestamp;
        }
        if($counpon['type'] =="DATE_TYPE_FIX_TERM"){
            $fixed_term =$counpon['fixed_term'];
            $fixed_begin_term =$counpon['fixed_begin_term'];
            if($fixed_begin_term == "0"){
                $fixed_begin_term = "当天";
            }else{
                $fixed_begin_term = $fixed_begin_term."天后";
            }
            $indate="领取后".$fixed_begin_term."生效".$fixed_term."天有效";
        }
        return $indate;
    }

    /**
     * @param $counpon  当前id的数组
     * @return string   日期状态
     */
    function get_data_status($counpon)
    {
        if ($counpon['type'] == "DATE_TYPE_FIX_TIME_RANGE") {
            $begin_timestamp = $counpon['begin_timestamp'];
            $end_timestamp = $counpon['end_timestamp'];
            if ($begin_timestamp > time()) {
                $status = 1;
            } else if ($begin_timestamp <= time() && $end_timestamp >= time()) {
                $status = 2;
            } else if ($end_timestamp < time()) {
                $status = 3;
            }

        }
        if ($counpon['type'] == "DATE_TYPE_FIX_TERM") {
            $status = 1;
        }
        if ($counpon['status'] == 5) {
            $status = 3;
        }
        return $status;
    }

    /**
     * @param $counpon
     * @return string  得到的状态
     */
    function get_status($counpon)
    {
        switch ($counpon['status']){
            case "1":
                return "审核中";
            case "2":
                return "未通过";
            case "3":
                return "待投放";
            case "4":
                return "已投放";
            case "5":
                return "已删除";
            default:
                return "";
        }
    }

    /**
     * @param $counpon
     * @return string  优惠卷的具体内容
     */
    function get_pay_content($counpon)
    {
        $total_price = floatval($counpon['total_price']);
        $de_price = floatval($counpon['de_price']);
        return "满".$total_price."减".$de_price;
    }

    /**
     * @param $counpon
     * @return string  获得优惠券的本地logo
     */
    function get_base_url($counpon)
    {
        $mid=$counpon['mid'];
        return $this->merchants->where("id=$mid")->getField("base_url");
    }

    /**
     * @param $counpon
     * @return string
     */
    public function get_style($counpon)
    {
        $begin_timestamp=$counpon['begin_timestamp'];
        $end_timestamp=$counpon['end_timestamp'];
        $time=time();
        if($time >$begin_timestamp &&$time<$end_timestamp){
            return "2";
        }else if($time <$begin_timestamp){
            return "1";
        }else if($time >$end_timestamp){
            return "3";
        }
    }

    /**
     * @param $card
     * @return mixed 库存的排布
     */
    public function get_quantity($card)
    {
        $coupon=$this->coupons->where("id='$card'")->find();
        $quantity['quantity_get'] = $coupon['use_quantity'];
        $quantity['quantity_remain'] = $coupon['quantity'];
        $card_id = $coupon['card_id'];
        $quantity_remain = M("screen_user_coupons")->where("card_id='$card_id' And status=0")->count("id");
        $quantity['quantity_use'] =$quantity_remain;
        return $quantity;
    }
    /**
     * @param $card_id  优惠券的card_id
     * @return object  获得优惠券的具体信息
     */
    function get_card_detatil($card_id)
    {
        $url_card_detail="https://api.weixin.qq.com/card/get?access_token=".get_weixin_token();
        $data['card_id'] ="$card_id";
        $card_detail=request_post($url_card_detail,json_encode($data));
        return $card_detail;

    }
    /**
     * @param $card_detail  json格式
     * @return object  获得优惠券的具体信息
     */
    function get_card_status($card_detail)
    {
        $status=$card_detail->card->general_coupon->base_info->status;
        switch ($status){
            case "CARD_STATUS_NOT_VERIFY";  //待审核
                return 1;
            case "CARD_STATUS_VERIFY_FAIL"; //审核失败；
                return 2;
            case "CARD_STATUS_VERIFY_OK";   //通过审核；
                return 3;
            case "CARD_STATUS_DISPATCH";   //在公众平台投放过的优惠券
                return 4;
            case "CARD_STATUS_USER_DELETE"; //优惠券被商户删除；
                return 5;
        }
    }

    /**
     * @param $time
     * @return false|int  转化时间
     */
    function time_transform($time)
    {
        $begin_timestamp=date("Y-m-d",$time);
        $time=strtotime($begin_timestamp);
        return $time;
    }

    /**
     * @param $uid
     * @return 获取商户id
     */
    protected function get_merchant($uid)
    {
        if(!$uid)$this->ajaxReturn(array("code" => "error","msg"=>"失败", "data"=>" 未登录"));
        $role_id=M("merchants_role_users")->where("uid=$uid")->getField('role_id');
        if($role_id == 3){
            $muid= $uid;
        }else{
            $muid= M("merchants_users")->where("id=$uid")->getField("pid");
        }
        $mid=$this->merchants->where("uid=$muid")->getField("id");
        return $mid;
    }

    private function writeLog($file_name, $title, $param, $json=true)
    {
        $path = $this->get_date_dir();
        if($json){
            $param = json_encode($param);
        }
        file_put_contents($path . $file_name, date("H:i:s") . $title.':'. $param . PHP_EOL . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    private function get_date_dir($path = '/data/log/coupon/')
    {
        $Y = $_SERVER['DOCUMENT_ROOT'] . $path . date("Y-m");
        $d = $Y . '/' . date("Y-m-d");
        if (!file_exists($Y)) mkdir($Y, 0777, true);
        if (!file_exists($d)) mkdir($d, 0777);

        return $d . '/';
    }
}
