<?php
namespace Screen\Controller;
use Common\Controller\AdminbaseController;

class CouponadminController extends AdminbaseController{
    protected $coupons;
    protected $merchants;
    protected $host;
    public function __construct()
    {
        parent::__construct();
        $this->coupons = M("screen_coupons");
        $this->merchants=M("merchants");
        $this->host = 'http://' . $_SERVER['HTTP_HOST'];
        $colors=get_color();
        $this->assign("colors",$colors);
    }

    public function index(){
        $select=I("");
        if($select['start_time'] ||$select['end_time']){
            $start_time = strtotime($select['start_time']);
            $end_time = strtotime($select['end_time']);
            $map['c.create_time'] = array(array('EGT', $start_time), array('ELT', $end_time));
        }
        if($select['status'] !=""){
            $map['c.status'] =$select['status'];
        }
        if($select['merchant_name'] !=""){
            $merchant_name=$select['merchant_name'];
            $map['m.merchant_name'] =array('like',"%$merchant_name%") ;
        }
        if($select['title'] !=""){
            $title=$select['title'];
            $map['c.title'] =array('like',"%$title%") ;
        }
        $coupons=$this->coupons
            ->alias("c")
            ->join("left join __MERCHANTS__ m on m.id =c.mid")
            ->where($map)
            ->field("m.merchant_name,c.*")
            ->order("id desc")
            ->select();
        $data=array();
        foreach ($coupons as $k=>$v)
        {
            $data[$k]['indate'] =get_time_detail($v);
            $data[$k]['card_type'] =get_card_type($v);
            $data[$k]['title'] = $v['title'];
            $data[$k]['id'] = $v['id'];
            $data[$k]['status'] = get_status($v);
            $data[$k]['quantity'] = $v['quantity'];
            $data[$k]['merchant_name'] = $v['merchant_name'];
            $data[$k]['content'] = get_pay_content($v);
            $data[$k]['create_time']=date("Y-m-d H:i:s",$v['create_time']);
            $data[$k]['base_url']=$this->host.$v['base_url'];
        }

        $count=count($data);
        $page = $this->page($count, 10);
        $list=array_slice($data,$page->firstRow,$page->listRows);
        $this->assign("page", $page->show('Admin'));
        $this->assign("formget", array_merge($_GET, $_POST));

        $this->assign('coupons',$list);
		$this->display();
	}
	public function get_merchant_name($v)
    {
	    $mid=$v['mid'];
        $merchant_name=$this->merchants->where("id=$mid")->getField("merchant_name");
        return $merchant_name;
    }
    public function add()
    {
        $this->display();
    }

	public function add_post()
    {
//        dump($_POST);exit;
        if(!I("title"))$this->error("没有填写优惠券标题");
        if(!I("color"))$this->error("没有选择背景颜色");
        if(!I("total_price"))$this->error("没有填写卡券面值");
        if(!I("de_price"))$this->error("没有填写卡券面值");
        if(!I("begin_timestamp"))$this->error("没有填写开始时间");
        if(!I("end_timestamp"))$this->error("没有填写结束时间");
        if(!I("quantity"))$this->error("没有填写库存");
        $token=get_weixin_token();
        $user_phone=I("user_phone");
        $mid=check_merchant_phone($user_phone);
        if($mid==""){
            $this->error("商户的手机号不存在,添加失败");
        }
        $merchant = get_merchant_detail($mid);

        //生成logo图片
        if ($_FILES) {
            $upload = new \Think\Upload();// 实例化上传类
            $upload->maxSize = 3145728;
            $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
            $upload->rootPath = C('_WEB_UPLOAD_');
            $upload->savePath = 'coupons/';
            $upload->saveName = uniqid();//保持文件名不变
            $info = $upload->upload();
            if (!$info)$this->error($upload->getError());
            header("Content-type:text/html;charset=utf-8");
            $url='/data/upload/'.$info['logo']['savepath'].$info['logo']['savename'];
            $arr=array();
            $arr['buffer']='@'.$_SERVER['DOCUMENT_ROOT'].$url;
            $url_getlog="https://api.weixin.qq.com/cgi-bin/media/uploadimg?access_token=".get_weixin_token();
            $result = request_post($url_getlog, $arr);
            $result = json_decode($result, true);
            $logo_url=$result['url'];
            $base_url=$url;
        }else{
            $logo1=I("logo1");
            $logo_url=$logo1;
        }
        $code_type="CODE_TYPE_ONLY_QRCODE";
        $brand_name =$merchant['user_name']; //店铺名称
        $color=I("color");
        $title=I("title");
        $quantity=I("quantity");//库存
        $description="不可与其他优惠同享";
        $begin_timestamp=time_transform(I("begin_timestamp"));
        $end_timestamp=time_transform(I("end_timestamp"));
        if($end_timestamp<time()){
            $this->error("结束时间不能小于当前时间");
        }
        $service_phone =I("service_phone");
        $notice="请向店员出示二维码";
        $type="DATE_TYPE_FIX_TIME_RANGE";
//        $use_custom_code=false;
        $get_limit=1;
        $can_share=	false;
        $can_give_friend= false;
        $total_price=I("total_price");
        $de_price=I("de_price");
        $default_detail= "满".$total_price."元减".$de_price."元";
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
        $kqinfo['card']['general_coupon']['base_info']['date_info']['begin_timestamp'] = $begin_timestamp;
        $kqinfo['card']['general_coupon']['base_info']['date_info']['end_timestamp'] = $end_timestamp;
        $kqinfo['card']['general_coupon']['base_info']['sku']['quantity'] = $quantity;
        $kqinfo['card']['general_coupon']['base_info']['can_give_friend'] =$can_give_friend;
        $kqinfo['card']['general_coupon']['base_info']['get_limit'] =$get_limit;
        $kqinfo['card']['general_coupon']['default_detail']= urlencode($default_detail);
        $data=urldecode(json_encode($kqinfo));
        $url_merchant="https://api.weixin.qq.com/card/create?access_token=$token";
        get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/weixin/','coupon','screen/couponadmin商户申请提交',$data);
        $result = request_post($url_merchant, $data);
        get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/weixin/','coupon','screen/couponadmin商户申请提交结果',$result);
        $result = object2array(json_decode($result));
        if($result['errmsg'] == 'ok' && $result['errcode'] == 0){
            $card_id=$result['card_id'];
        }else{
            $this->error("提交失败,请于彭鼎细说");
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
        $map['can_give_friend']=$can_give_friend;
        $map['total_price']=$total_price;
        $map['de_price']=$de_price;
        $map['card_id']=$card_id;
        $map['status']=1;
        $map['create_time']=time();
        if(M("screen_coupons")->add($map)){
            $this->success("恭喜你添加成功",U('index'));
            $ab=M("merchants");
            if(!$logo1&&$ab->where("id=$mid")->find())$ab->where("id=$mid")->save(array("base_url" =>$base_url,"logo_url"=>$logo_url));
        }else{
            $this->error("数据添加失败");
        };
    }

    public function detail()
    {
        $card_id="pyaFdwKYgR1SLuoftC55RfT43SJg";
        $card_detail=get_card_detatil($card_id);
        var_dump($card_detail);
        $status=get_card_status($card_detail);
        var_dump($status);
        $this->display();
    }

    public function edit()
    {
        $this->display();
    }

    public function edit_post()
    {


//        if (!I("title")) $this->error("没有填写优惠券标题");
//        if (!I("color")) $this->error("没有选择背景颜色");
//        if (!I("total_price")) $this->error("没有填写卡券面值");
//        if (!I("de_price")) $this->error("没有填写卡券面值");
//        if (!I("begin_timestamp")) $this->error("没有填写开始时间");
//        if (!I("end_timestamp")) $this->error("没有填写结束时间");
//        if (!I("quantity")) $this->error("没有填写库存");
        $logo_url="http://mmbiz.qpic.cn/mmbiz_png/XgCbCud1UywSicicKCfQYKGzsmnCeJJlKI46nlnu5rJkRhopXAxxTXGiaOmlnD0cib0SmhOibdUMwDhRmYxcxI1ic1og/0";
        $title="编辑优惠券";
        $color ="Color030";
        $brand_name ="彭鼎";
        $service_phone="1771507422";
        $type="DATE_TYPE_FIX_TIME_RANGE";
        $begin_timestamp ="1493222400";
        $end_timestamp = "1493395201";
        $total_price=150;
        $de_price=20;
        $default_detail= "满".$total_price."元减".$de_price."元";
        $quantity=150;
        $kqinfo['card_id'] ="pyaFdwKYgR1SLuoftC55RfT43SJg";
        $kqinfo['general_coupon'] = array('base_info' => array());
        $kqinfo['general_coupon']['base_info']['logo_url'] = $logo_url;
        $kqinfo['general_coupon']['base_info']['title'] = urlencode($title);
        $kqinfo['general_coupon']['base_info']['color'] = $color;
        $kqinfo['general_coupon']['base_info']['service_phone'] = $service_phone;
        $kqinfo['general_coupon']['base_info']['date_info']['type'] = $type;
        $kqinfo['general_coupon']['base_info']['date_info']['begin_timestamp'] = $begin_timestamp;
        $kqinfo['general_coupon']['base_info']['date_info']['end_timestamp'] = $end_timestamp;

        $data=urldecode(json_encode($kqinfo));
        $url_edit_copoun="https://api.weixin.qq.com/card/update?access_token=".get_weixin_token();
        $edit_copoun= request_post($url_edit_copoun,$data);

        var_dump($edit_copoun);
        exit;

    }
//    生成卡券二维码
    public function get_ticket()
    {
        $id=I("get.id");
        $card_id=M("screen_coupons")->where("id=$id")->getField("card_id");
        vendor("phpqrcode.phpqrcode");

    }

//    检查logo
    public function check_logo()
    {
        $user_phone=I("user_phone");
        $mid=check_merchant_phone($user_phone);
        if($mid == ""){
            $this->ajaxReturn(array("status"=>0,"msg"=>'商户不存在'));
        }else{
            $logo=M("merchants")->where("id=$mid")->getField("base_url");
            if($logo){
                $this->ajaxReturn(array("status"=>1,"msg"=>$logo));
            }else{
                $this->ajaxReturn(array("status"=>2,"msg"=>"改商户没有logo"));
            }
        }

    }

//    改变库存
    public function change_quatity()
    {
        $card = I("card");
        $url_change_quatity="https://api.weixin.qq.com/card/modifystock?access_token=Tj2zAZa0ycS5Q5vkoTgsFVQkZCKHwOFOvBm3a44LJijuF3tQrpoMS3w0O6Irsia-SlbpvSsY3HqDMsR7QngKsgegUn5_qctH3I0MXixarcyDN2Egd4tKjzjojGuf0wARJJDiAJARRL";
        $coupon=M("screen_coupons")->where("id='$card'")->find();
        $data['card_id']=$coupon['card_id'];
        $quality_old =(int)$coupon['quantity'];
        $quality_new =(int)I("quantity");
        if($quality_old !== $quality_new)
        {
            if($quality_old > $quality_new){
                $data['reduce_stock_value'] =$quality_old -$quality_new;
            }else{
                $data['increase_stock_value'] =$quality_new-$quality_old;
            }
            $change_quatity= request_post($url_change_quatity,json_encode($data) );
            var_dump($change_quatity);
        }
    }

//    用户使用优惠券
    public function use_ticket()
    {

        $url="https://api.weixin.qq.com/card/code/consume?access_token=3xqfW9vS4JN_BofXLT6LopZEPsFWTTkeq4C0nR2TpD8v_7ZI8zbS8qtHQ9Z9aIJLYQZgK1Vwo6VS84-VDSVqkOVd8PluV2i9QsdljtlIfJBhI9Y4pACK2Wuv1iWbA3S3OIYeAJAZTJ";
        $code=690034308426;
        $data['code']=$code;
        $use_ticket=request_post($url,json_encode($data));
        M("screen_user_coupons")->where("user_card=$code")->save(array("status"=>0,"delete_time"=>time()));
        var_dump($use_ticket);
        exit;
    }

//    卡券详情
    public function card_detail()
    {
        $card_id="pyaFdwKYgR1SLuoftC55RfT43SJg";
        $card_detail=get_card_detatil($card_id);
        var_dump($card_detail);
        $status=get_card_status($card_detail);
        var_dump($status);
        exit;
    }

    public function pull_card_one()
    {
        $openid="oyaFdwBXWgEckdh3rL-L6pS12ZFk";
        $data ='{
                "touser": "'.$openid.'", 
                "wxcard": {
                    "card_id": "pyaFdwMNbjx1QPHMpC-DlMfTPGhI"
                }, 
                "msgtype": "wxcard"
                }';
        $url = "https://api.weixin.qq.com/cgi-bin/message/mass/preview?access_token=".get_weixin_token();
        $res = request_post($url, $data);
        $QRMeg = json_decode($res, true);
        var_dump($QRMeg) ;exit;
    }
}
